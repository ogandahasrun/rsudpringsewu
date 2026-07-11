<?php
session_start();
require_once 'koneksi.php';

// Cek Login Pengguna
if (!isset($_SESSION['username'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Sesi Anda telah berakhir. Silakan login kembali.']);
        exit();
    }
    header('Location: login.php');
    exit();
}

// Ambil Informasi Rumah Sakit (Instansi) dari Database
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU";
$logo_src = "images/logo.png";

if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    if (!empty($row_instansi['logo'])) {
        $logo_blob = $row_instansi['logo'];
        $logo_base64 = base64_encode($logo_blob);
        $logo_src = "data:image/png;base64," . $logo_base64;
    }
}

// Handle AJAX Request untuk SATUSEHAT EpisodeOfCare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'kirim_episodeofcare') {
        $payload_raw = $_POST['payload'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        
        // Validasi Payload JSON
        $payload_decoded = json_decode($payload_raw, true);
        if ($payload_decoded === null) {
            echo json_encode([
                'success' => false,
                'message' => 'Payload JSON tidak valid: ' . json_last_error_msg()
            ]);
            exit();
        }

        // Jalankan Langkah 1: Dapatkan Access Token
        $token_res = getSatuSehatToken();
        if (!$token_res['success']) {
            echo json_encode([
                'success' => false,
                'step' => 'token',
                'message' => $token_res['message'],
                'response' => $token_res['response'] ?? null
            ]);
            exit();
        }

        $token = $token_res['token'];
        $token_cached = $token_res['cached'] ?? false;

        // Jalankan Langkah 2: Kirim ke FHIR Server
        $send_res = sendEpisodeOfCare($token, $payload_decoded);

        // Jalankan Langkah 3: Simpan Ke DB SIMRS Lokal jika sukses dan nomor rawat terisi
        $db_saved = false;
        $db_error = '';
        if ($send_res['success'] && !empty($no_rawat)) {
            $id_episodeofcare = $send_res['response']['id'] ?? '';
            if (!empty($id_episodeofcare)) {
                // Cek data duplikat key di satu_sehat_episodeofcare
                // Catatan: Kolom id_encounter di tabel ini digunakan untuk menyimpan id_episodeofcare UUID
                $check_stmt = $koneksi->prepare("SELECT id_encounter FROM satu_sehat_episodeofcare WHERE no_rawat = ?");
                $check_stmt->bind_param("s", $no_rawat);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();
                
                if ($check_res->num_rows > 0) {
                    // Update
                    $save_stmt = $koneksi->prepare("UPDATE satu_sehat_episodeofcare SET id_encounter = ? WHERE no_rawat = ?");
                    $save_stmt->bind_param("ss", $id_episodeofcare, $no_rawat);
                } else {
                    // Insert
                    $save_stmt = $koneksi->prepare("INSERT INTO satu_sehat_episodeofcare (no_rawat, id_encounter) VALUES (?, ?)");
                    $save_stmt->bind_param("ss", $no_rawat, $id_episodeofcare);
                }
                
                if ($save_stmt->execute()) {
                    $db_saved = true;
                } else {
                    $db_error = $save_stmt->error;
                }
            }
        }

        echo json_encode([
            'success' => $send_res['success'],
            'step' => 'send',
            'token_cached' => $token_cached,
            'http_code' => $send_res['http_code'] ?? null,
            'message' => $send_res['message'],
            'response' => $send_res['response'] ?? null,
            'payload_sent' => $payload_decoded,
            'db_saved' => $db_saved,
            'db_error' => $db_error
        ]);
        exit();
    }

    if ($_POST['action'] === 'cari_pasien_nik') {
        $nik = trim($_POST['nik'] ?? '');
        
        if (empty($nik) || !preg_match('/^\d{16}$/', $nik)) {
            echo json_encode([
                'success' => false,
                'message' => 'NIK harus 16 digit angka.'
            ]);
            exit();
        }
        
        $token_res = getSatuSehatToken();
        if (!$token_res['success']) {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal mendapatkan token: ' . $token_res['message']
            ]);
            exit();
        }
        
        $token = $token_res['token'];
        $search_res = searchPatientByNIK($token, $nik);
        
        echo json_encode($search_res);
        exit();
    }

    if ($_POST['action'] === 'clear_token') {
        unset($_SESSION['satu_sehat_token']);
        unset($_SESSION['satu_sehat_token_expires']);
        echo json_encode(['success' => true, 'message' => 'Cache access token SATUSEHAT berhasil dibersihkan.']);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
    exit();
}

/**
 * Mendapatkan Access Token dari SATUSEHAT (dengan caching session)
 */
function getSatuSehatToken() {
    global $URLAUTHSATUSEHAT, $CLIENTID, $CLIENTSECRET;

    if (isset($_SESSION['satu_sehat_token']) && isset($_SESSION['satu_sehat_token_expires']) && $_SESSION['satu_sehat_token_expires'] > time() + 60) {
        return [
            'success' => true,
            'token' => $_SESSION['satu_sehat_token'],
            'cached' => true
        ];
    }

    $url = $URLAUTHSATUSEHAT . "/accesstoken?grant_type=client_credentials";
    $postData = http_build_query([
        'client_id' => $CLIENTID,
        'client_secret' => $CLIENTSECRET
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'message' => 'Curl Error: ' . $curl_error
        ];
    }

    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
        $_SESSION['satu_sehat_token'] = $data['access_token'];
        $expires_in = isset($data['expires_in']) ? intval($data['expires_in']) : 3600;
        $_SESSION['satu_sehat_token_expires'] = time() + $expires_in;

        return [
            'success' => true,
            'token' => $data['access_token'],
            'cached' => false,
            'response' => $data
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Gagal mendapatkan token. Respons server: ' . $response,
            'response' => $data
        ];
    }
}

/**
 * Mengirim data EpisodeOfCare ke FHIR server SATUSEHAT
 */
function sendEpisodeOfCare($token, $payload) {
    global $URLFHIRSATUSEHAT;

    $url = $URLFHIRSATUSEHAT . "/EpisodeOfCare";
    $payload_string = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'http_code' => 0,
            'message' => 'Curl Error: ' . $curl_error
        ];
    }

    $data = json_decode($response, true);
    
    if ($http_code == 201 || $http_code == 200) {
        return [
            'success' => true,
            'http_code' => $http_code,
            'message' => 'Resource EpisodeOfCare berhasil terkirim dan disimpan di SATUSEHAT.',
            'response' => $data
        ];
    } else {
        $err_msg = 'Gagal mengirim data EpisodeOfCare. Kode HTTP: ' . $http_code;
        if (isset($data['issue'][0]['diagnostics'])) {
            $err_msg .= ' | Detail: ' . $data['issue'][0]['diagnostics'];
        }
        return [
            'success' => false,
            'http_code' => $http_code,
            'message' => $err_msg,
            'response' => $data
        ];
    }
}

/**
 * Mencari data pasien di SATUSEHAT berdasarkan NIK (mengembalikan IHS ID & Nama)
 */
function searchPatientByNIK($token, $nik) {
    global $URLFHIRSATUSEHAT;
    
    $url = $URLFHIRSATUSEHAT . "/Patient?identifier=https://fhir.kemkes.go.id/id/nik|" . urlencode($nik);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        return [
            'success' => false,
            'message' => 'Curl Error searching patient: ' . $curl_error
        ];
    }
    
    $data = json_decode($response, true);
    if ($http_code == 200 && isset($data['resourceType']) && $data['resourceType'] === 'Bundle') {
        if (isset($data['total']) && $data['total'] > 0 && isset($data['entry'][0]['resource'])) {
            $patient = $data['entry'][0]['resource'];
            $id = $patient['id'] ?? '';
            
            $name = '';
            if (isset($patient['name'][0]['text'])) {
                $name = $patient['name'][0]['text'];
            } elseif (isset($patient['name'][0]['given'])) {
                $name = implode(' ', $patient['name'][0]['given']);
                if (isset($patient['name'][0]['family'])) {
                    $name .= ' ' . $patient['name'][0]['family'];
                }
            }
            
            return [
                'success' => true,
                'id' => $id,
                'name' => $name,
                'response' => $data
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Pasien dengan NIK ' . $nik . ' tidak ditemukan di basis data SATUSEHAT.'
            ];
        }
    } else {
        $err_msg = 'Gagal mencari data pasien. Kode HTTP: ' . $http_code;
        if (isset($data['issue'][0]['diagnostics'])) {
            $err_msg .= ' | Detail: ' . $data['issue'][0]['diagnostics'];
        }
        return [
            'success' => false,
            'message' => $err_msg,
            'response' => $data
        ];
    }
}

// Atur default datetime untuk input form (Local Waktu Saat Ini)
$default_datetime = date('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim EpisodeOfCare SATUSEHAT - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary: #0d9488;
            --primary-hover: #0f766e;
            --primary-light: #f0fdfa;
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --accent: #f59e0b;
            --success: #10b981;
            --error: #ef4444;
            --radius: 12px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            line-height: 1.5;
            padding-bottom: 60px;
        }

        .header-nav {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .hospital-identity {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .hospital-identity img {
            height: 48px;
            object-fit: contain;
        }

        .hospital-identity h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
        }

        .hospital-identity p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-outline:hover {
            border-color: var(--text-muted);
            color: var(--text-main);
            background: var(--bg-main);
        }

        .main-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-title-section {
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title-section h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title-section h1 i {
            color: var(--primary);
        }

        .connection-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }

        .connection-status-pill.online {
            background: #dcfce7;
            color: #15803d;
        }

        /* Split-screen Layout */
        .workspace-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .workspace-grid {
                grid-template-columns: 1fr;
            }
        }

        .workspace-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
            overflow: hidden;
            height: calc(100vh - 200px);
            min-height: 600px;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 1024px) {
            .workspace-card {
                height: auto;
            }
        }

        .card-header-tabs {
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
        }

        .tabs-list {
            display: flex;
            gap: 5px;
        }

        .tab-btn {
            padding: 15px 20px;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: var(--text-main);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body-scrollable {
            flex: 1;
            overflow-y: auto;
            padding: 25px;
        }

        /* Form Styling */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-main);
            outline: none;
            transition: var(--transition);
            background-color: #fafafa;
        }

        .form-input:focus {
            border-color: var(--primary);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }

        .section-divider {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--primary);
            margin: 25px 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Code/JSON Editor Style */
        .json-editor-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
        }

        .json-textarea {
            width: 100%;
            height: 100%;
            min-height: 480px;
            border: none;
            padding: 20px;
            font-family: 'Fira Code', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #d1d5db;
            background-color: #0f172a;
            resize: none;
            outline: none;
            border-radius: 0;
        }

        /* Preview Panel */
        .preview-pane {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .preview-actions-bar {
            padding: 15px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(13, 148, 136, 0.25);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Output Logs Console */
        .console-container {
            background: #1e293b;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .console-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #334155;
            padding-bottom: 10px;
        }

        .console-title {
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .console-body {
            font-family: 'Fira Code', monospace;
            font-size: 13px;
        }

        /* Progress Steps */
        .stepper {
            margin-bottom: 20px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 13px;
            color: #94a3b8;
        }

        .step-item.active {
            color: #38bdf8;
            font-weight: 500;
        }

        .step-item.success {
            color: #34d399;
        }

        .step-item.failed {
            color: #f87171;
        }

        .step-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #334155;
            color: #fff;
            font-size: 10px;
        }

        .step-item.active .step-icon {
            background: #0284c7;
            box-shadow: 0 0 8px rgba(2, 132, 199, 0.5);
            animation: pulse 1.5s infinite;
        }

        .step-item.success .step-icon {
            background: #059669;
        }

        .step-item.failed .step-icon {
            background: #dc2626;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.9; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 0.9; }
        }

        .response-code-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 11px;
            margin-left: 8px;
        }

        .response-code-badge.s2xx {
            background: #065f46;
            color: #a7f3d0;
        }

        .response-code-badge.s4xx, .response-code-badge.s5xx {
            background: #7f1d1d;
            color: #fecaca;
        }

        .pretty-json-box {
            background: #0f172a;
            color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #334155;
            white-space: pre-wrap;
            word-break: break-all;
            margin-top: 10px;
        }

        /* Footer copy */
        footer {
            text-align: center;
            padding: 20px 0;
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 40px;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <div class="header-nav">
        <div class="hospital-identity">
            <img src="<?php echo $logo_src; ?>" alt="Logo Instansi">
            <div>
                <h2><?php echo htmlspecialchars($nama_instansi); ?></h2>
                <p>Integrasi Sistem SATUSEHAT Kemenkes RI</p>
            </div>
        </div>
        
        <div class="nav-actions">
            <a href="bpjs.php" class="btn-outline">
                <i class="fas fa-chevron-left"></i> Menu BPJS
            </a>
            <a href="index.php" class="btn-outline">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="main-container">
        
        <!-- Page Title & Status -->
        <div class="page-title-section">
            <div>
                <h1><i class="fa-solid fa-folder-open"></i> Kirim EpisodeOfCare (Episode Perawatan)</h1>
                <p style="color: var(--text-muted); margin-top: 4px; font-size: 14px;">Membuat episode perawatan untuk program medis (ANC, Kanker, TB, dll.) di platform SATUSEHAT.</p>
            </div>
            
            <div class="header-actions">
                <span class="connection-status-pill online" id="status-pill">
                    <i class="fa-solid fa-circle-check"></i> SATUSEHAT Connected
                </span>
                <button type="button" class="btn-outline" onclick="clearTokenCache()" title="Reset Session Token">
                    <i class="fa-solid fa-arrows-rotate"></i> Reset Token
                </button>
            </div>
        </div>

        <div class="workspace-grid">
            
            <!-- Left Workspace: Form Input or JSON Manual -->
            <div class="workspace-card">
                <div class="card-header-tabs">
                    <div class="tabs-list">
                        <button class="tab-btn active" id="tab-form-btn" onclick="switchTab('form')">
                            <i class="fa-solid fa-pen-to-square"></i> Input Form
                        </button>
                        <button class="tab-btn" id="tab-json-btn" onclick="switchTab('json')">
                            <i class="fa-solid fa-code"></i> JSON Manual
                        </button>
                    </div>
                    <div class="header-actions">
                        <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;">
                            Org ID: <code style="background: #e2e8f0; padding: 2px 5px; border-radius: 4px;"><?php echo htmlspecialchars($ORGANIZATIONID); ?></code>
                        </span>
                    </div>
                </div>

                <!-- Tab content Form -->
                <div class="card-body-scrollable" id="tab-form-content">
                    <form id="episodeofcare-form">
                        
                        <!-- Pasien Section -->
                        <div class="section-divider">Data Pasien</div>
                        
                        <!-- Pencarian NIK -->
                        <div class="form-group full-width" style="margin-bottom: 20px;">
                            <label class="form-label">Cari ID Pasien via NIK Kemenkes (Lookup)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="nik_lookup" class="form-input" placeholder="Masukkan 16 digit NIK Pasien (contoh: 317306...)" maxlength="16" style="flex: 1;">
                                <button type="button" class="btn-primary" id="btn-nik-lookup" onclick="lookupPatientByNIK()" style="padding: 10px 20px; box-shadow: none; white-space: nowrap;">
                                    <i class="fa-solid fa-magnifying-glass"></i> Cari IHS
                                </button>
                            </div>
                            <div id="nik-lookup-feedback" style="font-size: 12px; margin-top: 5px; font-weight: 500;"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="patient_id">ID Pasien (Satu Sehat UUID)</label>
                                <input type="text" id="patient_id" class="form-input" value="P00154318282" placeholder="Patient UUID" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="patient_name">Nama Pasien</label>
                                <input type="text" id="patient_name" class="form-input" value="FABIAN AUFAR MAULANA" placeholder="Nama Pasien" required>
                            </div>
                        </div>

                        <!-- Episode Details -->
                        <div class="section-divider">Detail Episode Perawatan</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="status">Status Episode</label>
                                <select id="status" class="form-input" style="height: 44px; background-color: #fafafa;">
                                    <option value="active" selected>active (Sedang Berjalan)</option>
                                    <option value="planned">planned (Direncanakan)</option>
                                    <option value="waitlist">waitlist (Daftar Tunggu)</option>
                                    <option value="onhold">onhold (Ditangguhkan)</option>
                                    <option value="finished">finished (Selesai)</option>
                                    <option value="cancelled">cancelled (Dibatalkan)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="identifier_value">ID Episode SIMRS (Identifier)</label>
                                <input type="text" id="identifier_value" class="form-input" value="EOC-2026-0001" placeholder="Contoh: EOC-2026-0001" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="start_time">Waktu Mulai Episode</label>
                                <input type="datetime-local" id="start_time" class="form-input" value="<?php echo $default_datetime; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="end_time">Waktu Selesai (Opsional)</label>
                                <input type="datetime-local" id="end_time" class="form-input">
                            </div>
                        </div>

                        <!-- Bridging SIMRS Section -->
                        <div class="section-divider">Bridging SIMRS Lokal (Untuk Simpan DB)</div>
                        
                        <div class="form-group">
                            <label class="form-label" for="no_rawat">Nomor Rawat Pasien (no_rawat)</label>
                            <input type="text" id="no_rawat" class="form-input" placeholder="Contoh: 2026/07/11/000001">
                            <small style="color: var(--text-muted); font-size: 11px;">Jika diisi, relasi EOC terkirim akan disimpan ke tabel local <code>satu_sehat_episodeofcare</code>.</small>
                        </div>

                    </form>
                </div>

                <!-- Tab content JSON Manual -->
                <div class="json-editor-container" id="tab-json-content" style="display: none;">
                    <textarea id="raw_json_textarea" class="json-textarea" spellcheck="false"></textarea>
                </div>
            </div>

            <!-- Right Workspace: Preview & Send Console -->
            <div class="workspace-card">
                <div class="card-header-tabs" style="border-bottom: 1px solid var(--border);">
                    <div class="tab-btn active">
                        <i class="fa-solid fa-code-compare"></i> Preview FHIR Resource
                    </div>
                    <div class="header-actions" style="padding-right: 10px;">
                        <button class="btn-outline" style="padding: 4px 8px; font-size: 12px;" onclick="copyJSONToClipboard()" title="Salin Payload">
                            <i class="fa-regular fa-copy"></i> Salin JSON
                        </button>
                    </div>
                </div>

                <div class="card-body-scrollable" style="background: #0b0f19; padding: 0;">
                    <!-- Live JSON Code Preview -->
                    <pre style="margin: 0; padding: 20px; font-family: 'Fira Code', monospace; font-size: 13px; line-height: 1.6; color: #a9b2c3; overflow-x: auto;" id="live-json-preview"></pre>
                </div>

                <!-- Execution Bar -->
                <div class="preview-actions-bar">
                    <button type="button" class="btn-primary" id="btn-submit-fhir" onclick="submitToSatuSehat()">
                        <i class="fa-solid fa-paper-plane"></i> Kirim ke SATUSEHAT
                    </button>
                </div>

                <!-- Console Output Log -->
                <div class="card-body-scrollable" id="console-output" style="display: none; background: #0f172a; border-top: 2px solid var(--border); max-height: 350px;">
                    <div class="console-body">
                        <!-- Progress Stepper -->
                        <div class="stepper">
                            <div class="step-item" id="step-auth">
                                <span class="step-icon"><i class="fa-solid fa-key"></i></span>
                                <span>Langkah 1: Otorisasi & Pertukaran Token...</span>
                            </div>
                            <div class="step-item" id="step-validate">
                                <span class="step-icon"><i class="fa-solid fa-check-double"></i></span>
                                <span>Langkah 2: Validasi Format FHIR Resource...</span>
                            </div>
                            <div class="step-item" id="step-send">
                                <span class="step-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                <span>Langkah 3: Transmisi Data ke Endpoint EpisodeOfCare...</span>
                            </div>
                            <div class="step-item" id="step-db">
                                <span class="step-icon"><i class="fa-solid fa-database"></i></span>
                                <span id="step-db-text">Langkah 4: Sinkronisasi Database SIMRS Lokal...</span>
                            </div>
                        </div>

                        <!-- Response Header Detail -->
                        <div class="console-header" style="margin-top: 15px;">
                            <div class="console-title">
                                <i class="fa-solid fa-terminal"></i>
                                <span>Respons Server</span>
                                <span id="status-code-badge" class="response-code-badge"></span>
                            </div>
                            <span id="response-time" style="font-size: 11px; color: #64748b;"></span>
                        </div>

                        <div id="response-message" style="margin: 5px 0 10px 0; font-size: 13px; color: #e2e8f0; font-weight: 500;"></div>
                        
                        <div style="font-size: 12px; color: #94a3b8; margin-bottom: 5px;">Response JSON Payload:</div>
                        <div class="pretty-json-box" id="response-json-box"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 IT <?php echo htmlspecialchars($nama_instansi); ?> | SATUSEHAT FHIR R4 Integration Helper</p>
    </footer>

    <!-- Logic JS -->
    <script>
        let currentTab = 'form';
        const organizationId = "<?php echo $ORGANIZATIONID; ?>";
        const rawJsonTextarea = document.getElementById('raw_json_textarea');
        const liveJsonPreview = document.getElementById('live-json-preview');

        // Initial loading
        document.addEventListener('DOMContentLoaded', function() {
            // Pasang event listener ke seluruh input form untuk regenerasi JSON
            const formInputs = document.querySelectorAll('#episodeofcare-form input, #episodeofcare-form select');
            formInputs.forEach(input => {
                input.addEventListener('input', updateJSONFromForm);
            });
            
            // Generate awal
            updateJSONFromForm();
        });

        function switchTab(tab) {
            currentTab = tab;
            
            document.getElementById('tab-form-btn').classList.toggle('active', tab === 'form');
            document.getElementById('tab-json-btn').classList.toggle('active', tab === 'json');
            
            document.getElementById('tab-form-content').style.display = tab === 'form' ? 'block' : 'none';
            document.getElementById('tab-json-content').style.display = tab === 'json' ? 'block' : 'none';
            
            if (tab === 'json') {
                // Salin JSON tergenerasi terakhir ke editor manual
                rawJsonTextarea.value = generateJSONFromFields();
                liveJsonPreview.textContent = rawJsonTextarea.value;
                
                // Sinkronkan editor perubahan
                rawJsonTextarea.addEventListener('input', function() {
                    liveJsonPreview.textContent = rawJsonTextarea.value;
                });
            } else {
                updateJSONFromForm();
            }
        }

        function updateJSONFromForm() {
            if (currentTab === 'form') {
                const generated = generateJSONFromFields();
                liveJsonPreview.textContent = generated;
            }
        }

        // Membaca input fields dan mengembalikan string JSON standar HL7 FHIR EpisodeOfCare
        function generateJSONFromFields() {
            const patientId = document.getElementById('patient_id').value.trim();
            const patientName = document.getElementById('patient_name').value.trim();
            const status = document.getElementById('status').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            const identifierValue = document.getElementById('identifier_value').value.trim();

            // Format datetime ISO 8601 dengan offset local +07:00 (WIB)
            let startFormatted = startTime;
            if (startFormatted && startFormatted.length === 16) {
                startFormatted += ":00+07:00";
            }
            let endFormatted = endTime;
            if (endFormatted && endFormatted.length === 16) {
                endFormatted += ":00+07:00";
            }

            const jsonObject = {
                "resourceType": "EpisodeOfCare",
                "status": status,
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/episodeofcare/" + organizationId,
                        "use": "official",
                        "value": identifierValue
                    }
                ],
                "patient": {
                    "reference": "Patient/" + patientId,
                    "display": patientName
                },
                "managingOrganization": {
                    "reference": "Organization/" + organizationId
                },
                "period": {
                    "start": startFormatted
                }
            };

            if (endFormatted) {
                jsonObject.period.end = endFormatted;
            }

            return JSON.stringify(jsonObject, null, 2);
        }

        // Mengirimkan JSON ke SATUSEHAT melalui perantara backend
        function submitToSatuSehat() {
            // Tampilkan console area
            const consoleArea = document.getElementById('console-output');
            consoleArea.style.display = 'block';
            
            // Reset status-status stepper
            resetSteps();
            
            // Tombol loading
            const btnSubmit = document.getElementById('btn-submit-fhir');
            const originalBtnHtml = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...';

            // Dapatkan payload yang aktif
            let payloadToSend = "";
            if (currentTab === 'form') {
                payloadToSend = generateJSONFromFields();
            } else {
                payloadToSend = rawJsonTextarea.value;
            }

            // Validasi client-side JSON
            try {
                JSON.parse(payloadToSend);
                setStepStatus('step-validate', 'success');
            } catch (e) {
                setStepStatus('step-validate', 'failed');
                showConsoleError('JSON Validation Error: ' + e.message, 400);
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalBtnHtml;
                return;
            }

            // Set Step Auth ke active
            setStepStatus('step-auth', 'active');

            // Persiapkan AJAX payload
            const formData = new FormData();
            formData.append('action', 'kirim_episodeofcare');
            formData.append('payload', payloadToSend);
            formData.append('no_rawat', document.getElementById('no_rawat').value.trim());

            const startTime = performance.now();

            fetch('satu_sehat_kirim_episode_of_care.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Respons jaringan bermasalah (HTTP ' + response.status + ')');
                }
                return response.json();
            })
            .then(data => {
                const endTime = performance.now();
                const latency = Math.round(endTime - startTime) + 'ms';
                document.getElementById('response-time').textContent = 'Latency: ' + latency;

                // Handle status dari auth token
                if (data.step === 'token' && !data.success) {
                    setStepStatus('step-auth', 'failed');
                    setStepStatus('step-send', 'failed');
                    setStepStatus('step-db', 'failed');
                    showConsoleError(data.message, data.http_code || 401, data.response);
                    return;
                } else {
                    setStepStatus('step-auth', 'success');
                }

                // Handle status kirim data
                setStepStatus('step-send', 'active');
                
                if (data.success) {
                    setStepStatus('step-send', 'success');
                    
                    // Handle DB status
                    setStepStatus('step-db', 'active');
                    if (data.db_saved) {
                        setStepStatus('step-db', 'success');
                        showConsoleSuccess(data.message + ' & Berhasil sinkronisasi ke tabel satu_sehat_episodeofcare.', data.http_code, data.response);
                    } else if (data.db_error) {
                        setStepStatus('step-db', 'failed');
                        showConsoleError(data.message + ' | Database Error: ' + data.db_error, data.http_code, data.response);
                    } else {
                        setStepStatus('step-db', 'success');
                        document.getElementById('step-db-text').textContent = 'Langkah 4: Sinkronisasi Database SIMRS Lokal (Dilewati - Nomor Rawat Kosong)';
                        showConsoleSuccess(data.message + ' (Sinkronisasi DB dilewati)', data.http_code, data.response);
                    }
                } else {
                    setStepStatus('step-send', 'failed');
                    setStepStatus('step-db', 'failed');
                    showConsoleError(data.message, data.http_code, data.response);
                }
            })
            .catch(error => {
                setStepStatus('step-auth', 'failed');
                setStepStatus('step-send', 'failed');
                setStepStatus('step-db', 'failed');
                showConsoleError(error.message, 500);
            })
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalBtnHtml;
            });
        }

        // Fungsi Reset Status Indikator Progress
        function resetSteps() {
            const steps = ['step-auth', 'step-validate', 'step-send', 'step-db'];
            steps.forEach(stepId => {
                const el = document.getElementById(stepId);
                el.className = 'step-item';
                el.querySelector('.step-icon').innerHTML = getStepDefaultIcon(stepId);
            });
            document.getElementById('step-db-text').textContent = 'Langkah 4: Sinkronisasi Database SIMRS Lokal...';
            document.getElementById('status-code-badge').className = 'response-code-badge';
            document.getElementById('status-code-badge').textContent = '';
            document.getElementById('response-message').textContent = '';
            document.getElementById('response-json-box').textContent = '';
            document.getElementById('response-time').textContent = '';
        }

        function getStepDefaultIcon(stepId) {
            if (stepId === 'step-auth') return '<i class="fa-solid fa-key"></i>';
            if (stepId === 'step-validate') return '<i class="fa-solid fa-check-double"></i>';
            if (stepId === 'step-send') return '<i class="fa-solid fa-cloud-arrow-up"></i>';
            if (stepId === 'step-db') return '<i class="fa-solid fa-database"></i>';
            return '';
        }

        function setStepStatus(stepId, status) {
            const el = document.getElementById(stepId);
            el.className = `step-item ${status}`;
            const iconContainer = el.querySelector('.step-icon');
            
            if (status === 'active') {
                iconContainer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            } else if (status === 'success') {
                iconContainer.innerHTML = '<i class="fa-solid fa-check"></i>';
            } else if (status === 'failed') {
                iconContainer.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            }
        }

        function showConsoleError(message, httpCode, fullResponse = null) {
            const badge = document.getElementById('status-code-badge');
            badge.className = 'response-code-badge s5xx';
            badge.textContent = httpCode ? `HTTP ${httpCode}` : 'ERROR';
            
            const msgBox = document.getElementById('response-message');
            msgBox.style.color = 'var(--error)';
            msgBox.textContent = message;

            const jsonBox = document.getElementById('response-json-box');
            if (fullResponse) {
                jsonBox.textContent = JSON.stringify(fullResponse, null, 2);
            } else {
                jsonBox.textContent = JSON.stringify({ "error": message, "timestamp": new Date().toISOString() }, null, 2);
            }
        }

        function showConsoleSuccess(message, httpCode, fullResponse) {
            const badge = document.getElementById('status-code-badge');
            badge.className = 'response-code-badge s2xx';
            badge.textContent = `HTTP ${httpCode || 201}`;
            
            const msgBox = document.getElementById('response-message');
            msgBox.style.color = 'var(--success)';
            msgBox.textContent = message;

            const jsonBox = document.getElementById('response-json-box');
            jsonBox.textContent = JSON.stringify(fullResponse, null, 2);
        }

        // Salin payload JSON ke Clipboard
        function copyJSONToClipboard() {
            let jsonText = "";
            if (currentTab === 'form') {
                jsonText = generateJSONFromFields();
            } else {
                jsonText = rawJsonTextarea.value;
            }

            navigator.clipboard.writeText(jsonText).then(() => {
                alert("✅ Payload JSON berhasil disalin ke clipboard!");
            }).catch(err => {
                alert("❌ Gagal menyalin JSON: " + err);
            });
        }

        // Clear Session Token Cache
        function clearTokenCache() {
            if (!confirm("Apakah Anda yakin ingin menghapus cache token SATUSEHAT di sesi ini?")) return;
            
            const formData = new FormData();
            formData.append('action', 'clear_token');

            fetch('satu_sehat_kirim_episode_of_care.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
            })
            .catch(err => alert("Gagal membersihkan cache token: " + err));
        }

        // Jalankan lookup jika menekan tombol Enter di input NIK
        document.getElementById('nik_lookup').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                lookupPatientByNIK();
            }
        });

        // Mencari Pasien Berdasarkan NIK ke SATUSEHAT
        function lookupPatientByNIK() {
            const nikInput = document.getElementById('nik_lookup');
            const nik = nikInput.value.trim();
            const feedback = document.getElementById('nik-lookup-feedback');
            const btn = document.getElementById('btn-nik-lookup');
            
            if (nik.length === 0) {
                feedback.style.color = 'var(--error)';
                feedback.textContent = '⚠️ Silakan masukkan NIK terlebih dahulu.';
                return;
            }
            if (!/^\d{16}$/.test(nik)) {
                feedback.style.color = 'var(--error)';
                feedback.textContent = '⚠️ NIK harus berisi tepat 16 digit angka.';
                return;
            }
            
            feedback.style.color = 'var(--text-muted)';
            feedback.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sedang mencari di platform SATUSEHAT...';
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'cari_pasien_nik');
            formData.append('nik', nik);
            
            fetch('satu_sehat_kirim_episode_of_care.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP status ' + res.status);
                return res.json();
            })
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    feedback.style.color = 'var(--success)';
                    feedback.innerHTML = '✅ Pasien ditemukan!';
                    
                    // Update input fields
                    document.getElementById('patient_id').value = data.id;
                    document.getElementById('patient_name').value = data.name;
                    
                    // Regenerasi Live JSON Preview
                    updateJSONFromForm();
                } else {
                    feedback.style.color = 'var(--error)';
                    feedback.textContent = '❌ ' + data.message;
                }
            })
            .catch(err => {
                btn.disabled = false;
                feedback.style.color = 'var(--error)';
                feedback.textContent = '❌ Terjadi kesalahan: ' + err.message;
            });
        }
    </script>
</body>
</html>
