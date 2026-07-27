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

// Handle AJAX Request untuk SATUSEHAT Encounter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'kirim_encounter') {
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
        $send_res = sendEncounter($token, $payload_decoded);

        // Jalankan Langkah 3: Simpan Ke DB SIMRS Lokal jika sukses dan nomor rawat terisi
        $db_saved = false;
        $db_error = '';
        if ($send_res['success'] && !empty($no_rawat)) {
            $id_encounter = $send_res['response']['id'] ?? '';
            if (!empty($id_encounter)) {
                // Cek data duplikat key di tabel satu_sehat_encounter
                $check_stmt = $koneksi->prepare("SELECT id_encounter FROM satu_sehat_encounter WHERE no_rawat = ?");
                if ($check_stmt) {
                    $check_stmt->bind_param("s", $no_rawat);
                    $check_stmt->execute();
                    $check_res = $check_stmt->get_result();
                    
                    if ($check_res->num_rows > 0) {
                        // Update
                        $save_stmt = $koneksi->prepare("UPDATE satu_sehat_encounter SET id_encounter = ? WHERE no_rawat = ?");
                        $save_stmt->bind_param("ss", $id_encounter, $no_rawat);
                    } else {
                        // Insert
                        $save_stmt = $koneksi->prepare("INSERT INTO satu_sehat_encounter (no_rawat, id_encounter) VALUES (?, ?)");
                        $save_stmt->bind_param("ss", $no_rawat, $id_encounter);
                    }
                    
                    if ($save_stmt && $save_stmt->execute()) {
                        $db_saved = true;
                    } else {
                        $db_error = $save_stmt ? $save_stmt->error : $koneksi->error;
                    }
                } else {
                    $db_error = "Tabel satu_sehat_encounter belum tersedia atau query error: " . $koneksi->error;
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

    if ($_POST['action'] === 'cari_nakes_nik') {
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
        $search_res = searchPractitionerByNIK($token, $nik);
        
        echo json_encode($search_res);
        exit();
    }

    if ($_POST['action'] === 'cari_location_id') {
        $loc_id = trim($_POST['location_id'] ?? '');
        
        if (empty($loc_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Location ID / UUID tidak boleh kosong.'
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
        $search_res = getLocationByID($token, $loc_id);
        
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
 * Mengirim data Encounter ke FHIR server SATUSEHAT
 */
function sendEncounter($token, $payload) {
    global $URLFHIRSATUSEHAT;

    $url = $URLFHIRSATUSEHAT . "/Encounter";
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
            'message' => 'Resource Encounter berhasil terkirim dan disimpan di SATUSEHAT.',
            'response' => $data
        ];
    } else {
        $err_msg = 'Gagal mengirim data Encounter. Kode HTTP: ' . $http_code;
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
 * Mencari data pasien di SATUSEHAT berdasarkan NIK
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

/**
 * Mencari data praktisi (nakes) di SATUSEHAT berdasarkan NIK
 */
function searchPractitionerByNIK($token, $nik) {
    global $URLFHIRSATUSEHAT;
    
    $url = $URLFHIRSATUSEHAT . "/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|" . urlencode($nik);
    
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
            'message' => 'Curl Error searching practitioner: ' . $curl_error
        ];
    }
    
    $data = json_decode($response, true);
    if ($http_code == 200 && isset($data['resourceType']) && $data['resourceType'] === 'Bundle') {
        if (isset($data['total']) && $data['total'] > 0 && isset($data['entry'][0]['resource'])) {
            $nakes = $data['entry'][0]['resource'];
            $id = $nakes['id'] ?? '';
            
            $name = '';
            if (isset($nakes['name'][0]['text'])) {
                $name = $nakes['name'][0]['text'];
            } elseif (isset($nakes['name'][0]['given'])) {
                $name = implode(' ', $nakes['name'][0]['given']);
                if (isset($nakes['name'][0]['family'])) {
                    $name .= ' ' . $nakes['name'][0]['family'];
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
                'message' => 'Nakes dengan NIK ' . $nik . ' tidak ditemukan di basis data SATUSEHAT.'
            ];
        }
    } else {
        $err_msg = 'Gagal mencari data Nakes. Kode HTTP: ' . $http_code;
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

/**
 * Mencari data Location di SATUSEHAT berdasarkan Location ID
 */
function getLocationByID($token, $loc_id) {
    global $URLFHIRSATUSEHAT;
    
    $loc_id = preg_replace('/^Location\//i', '', $loc_id);
    $url = $URLFHIRSATUSEHAT . "/Location/" . urlencode($loc_id);
    
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
            'message' => 'Curl Error searching location: ' . $curl_error
        ];
    }
    
    $data = json_decode($response, true);
    if ($http_code == 200 && isset($data['resourceType']) && $data['resourceType'] === 'Location') {
        $name = $data['name'] ?? ($data['description'] ?? '');
        return [
            'success' => true,
            'id' => $data['id'] ?? $loc_id,
            'name' => $name,
            'response' => $data
        ];
    } else {
        $err_msg = 'Gagal mencari data Lokasi. Kode HTTP: ' . $http_code;
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

// Default datetime WIB saat ini
$default_datetime = date('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Encounter SATUSEHAT - <?php echo htmlspecialchars($nama_instansi); ?></title>
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
            margin-bottom: 8px;
            color: #94a3b8;
            font-size: 13px;
        }

        .step-item.active {
            color: #38bdf8;
            font-weight: 600;
        }

        .step-item.success {
            color: #4ade80;
        }

        .step-item.failed {
            color: #f87171;
        }

        .step-icon {
            width: 20px;
            text-align: center;
        }

        /* Pre Code Display */
        pre.response-box {
            background: #0f172a;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            max-height: 250px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-all;
            margin-top: 10px;
            border: 1px solid #334155;
        }

        footer {
            text-align: center;
            padding: 30px;
            color: var(--text-muted);
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <div class="header-nav">
        <div class="hospital-identity">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo RS">
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
                <h1><i class="fa-solid fa-user-nurse"></i> Kirim Encounter (Kunjungan Pasien)</h1>
                <p style="color: var(--text-muted); margin-top: 4px; font-size: 14px;">Membuat data kunjungan/pemeriksaan pasien di rumah sakit pada platform SATUSEHAT.</p>
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
                    <form id="encounter-form">
                        
                        <!-- Bridging SIMRS Section -->
                        <div class="section-divider">Bridging SIMRS & Identifier</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="no_rawat">Nomor Rawat / Registrasi SIMRS</label>
                                <input type="text" id="no_rawat" class="form-input" value="2024/06/14/000001" placeholder="Contoh: 2024/06/14/000001">
                                <small style="color: var(--text-muted); font-size: 11px;">Nomor registrasi lokal. Hasil UUID Encounter akan disimpan ke tabel <code>satu_sehat_encounter</code>.</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="identifier_value">Identifier Value (No. Kunjungan)</label>
                                <input type="text" id="identifier_value" class="form-input" value="P20240001" placeholder="Contoh: P20240001" required>
                                <small style="color: var(--text-muted); font-size: 11px;">Sistem: <code>http://sys-ids.kemkes.go.id/encounter/<?php echo htmlspecialchars($ORGANIZATIONID); ?></code></small>
                            </div>
                        </div>

                        <!-- Status & Class Section -->
                        <div class="section-divider">Status & Jenis Kunjungan</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="status">Status Encounter</label>
                                <select id="status" class="form-input" style="height: 44px; background-color: #fafafa;">
                                    <option value="arrived" selected>arrived (Pasien Tiba)</option>
                                    <option value="in-progress">in-progress (Dalam Pelayanan)</option>
                                    <option value="finished">finished (Selesai)</option>
                                    <option value="triaged">triaged (Skrining / Triase)</option>
                                    <option value="planned">planned (Direncanakan)</option>
                                    <option value="onleave">onleave (Cuti / Izin)</option>
                                    <option value="cancelled">cancelled (Dibatalkan)</option>
                                    <option value="entered-in-error">entered-in-error (Salah Input)</option>
                                    <option value="unknown">unknown (Tidak Diketahui)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="class_code">Kelas Pelayanan (Class Code)</label>
                                <select id="class_code" class="form-input" style="height: 44px; background-color: #fafafa;" onchange="updateClassDisplay()">
                                    <option value="AMB" data-display="ambulatory" selected>AMB - Rawat Jalan (Ambulatory)</option>
                                    <option value="IMP" data-display="inpatient encounter">IMP - Rawat Inap (Inpatient Encounter)</option>
                                    <option value="EMER" data-display="emergency">EMER - Gawat Darurat (Emergency)</option>
                                    <option value="SS" data-display="short stay">SS - Short Stay</option>
                                    <option value="HH" data-display="home health">HH - Home Health</option>
                                </select>
                            </div>
                        </div>
                        
                        <input type="hidden" id="class_display" value="ambulatory">

                        <!-- Data Pasien Section -->
                        <div class="section-divider">Data Pasien (Subject)</div>
                        
                        <div class="form-group full-width" style="margin-bottom: 15px;">
                            <label class="form-label">Cari ID Pasien via NIK Kemenkes (Lookup)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="patient_nik_lookup" class="form-input" placeholder="Masukkan 16 digit NIK Pasien (contoh: 317306...)" maxlength="16" style="flex: 1;">
                                <button type="button" class="btn-primary" id="btn-patient-lookup" onclick="lookupPatientByNIK()" style="padding: 10px 20px; box-shadow: none; white-space: nowrap;">
                                    <i class="fa-solid fa-magnifying-glass"></i> Cari IHS Pasien
                                </button>
                            </div>
                            <div id="patient-lookup-feedback" style="font-size: 12px; margin-top: 5px; font-weight: 500;"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="patient_id">ID Pasien SATUSEHAT (IHS ID / UUID)</label>
                                <input type="text" id="patient_id" class="form-input" value="100000030009" placeholder="Contoh: 100000030009" required>
                                <small style="color: var(--text-muted); font-size: 11px;">Format ref: <code>Patient/100000030009</code></small>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="patient_name">Nama Pasien</label>
                                <input type="text" id="patient_name" class="form-input" value="Budi Santoso" placeholder="Nama Pasien" required>
                            </div>
                        </div>

                        <!-- Data Dokter / Nakes Section -->
                        <div class="section-divider">Data Dokter / DPJP (Participant)</div>
                        
                        <div class="form-group full-width" style="margin-bottom: 15px;">
                            <label class="form-label">Cari ID Nakes via NIK Dokter (Lookup)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="nakes_nik_lookup" class="form-input" placeholder="Masukkan 16 digit NIK Dokter/Nakes" maxlength="16" style="flex: 1;">
                                <button type="button" class="btn-primary" id="btn-nakes-lookup" onclick="lookupPractitionerByNIK()" style="padding: 10px 20px; box-shadow: none; white-space: nowrap;">
                                    <i class="fa-solid fa-user-doctor"></i> Cari IHS Nakes
                                </button>
                            </div>
                            <div id="nakes-lookup-feedback" style="font-size: 12px; margin-top: 5px; font-weight: 500;"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="practitioner_id">ID Nakes SATUSEHAT (Practitioner ID)</label>
                                <input type="text" id="practitioner_id" class="form-input" value="N10000001" placeholder="Contoh: N10000001" required>
                                <small style="color: var(--text-muted); font-size: 11px;">Format ref: <code>Practitioner/N10000001</code></small>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="practitioner_name">Nama Dokter / Nakes</label>
                                <input type="text" id="practitioner_name" class="form-input" value="Dokter Bronsig" placeholder="Nama Dokter" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="participant_type_code">Peran Partisipan (Participation Code)</label>
                                <select id="participant_type_code" class="form-input" style="height: 44px; background-color: #fafafa;" onchange="updateParticipantTypeDisplay()">
                                    <option value="ATND" data-display="attender" selected>ATND - Attender (Dokter Pemeriksa / Pendamping)</option>
                                    <option value="CON" data-display="consultant">CON - Consultant (Konsultan)</option>
                                    <option value="ADM" data-display="admitter">ADM - Admitter (Dokter Admisi)</option>
                                    <option value="DIS" data-display="discharger">DIS - Discharger (Dokter DPJP Pulang)</option>
                                    <option value="PPRF" data-display="primary performer">PPRF - Primary Performer (Pelaksana Utama)</option>
                                    <option value="SPRF" data-display="secondary performer">SPRF - Secondary Performer (Pelaksana Pendamping)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="participant_type_display">Participation Display</label>
                                <input type="text" id="participant_type_display" class="form-input" value="attender" readonly style="background-color: #f1f5f9;">
                            </div>
                        </div>

                        <!-- Waktu Perawatan Section -->
                        <div class="section-divider">Waktu Kunjungan (Period)</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="start_time">Waktu Mulai (Period Start)</label>
                                <input type="datetime-local" id="start_time" class="form-input" value="2022-06-14T07:00" required>
                                <small style="color: var(--text-muted); font-size: 11px;">Otomatis diformat ISO-8601 (+07:00 WIB)</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="end_time">Waktu Selesai (Period End - Opsional)</label>
                                <input type="datetime-local" id="end_time" class="form-input">
                                <small style="color: var(--text-muted); font-size: 11px;">Diisi jika status <code>finished</code></small>
                            </div>
                        </div>

                        <!-- Lokasi Pelayanan Section -->
                        <div class="section-divider">Lokasi Pelayanan (Location)</div>
                        
                        <div class="form-group full-width" style="margin-bottom: 15px;">
                            <label class="form-label">Cari Validasi Location ID SATUSEHAT (Lookup)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="location_id_lookup" class="form-input" placeholder="Masukkan Location UUID SATUSEHAT" style="flex: 1;">
                                <button type="button" class="btn-primary" id="btn-location-lookup" onclick="lookupLocationByID()" style="padding: 10px 20px; box-shadow: none; white-space: nowrap;">
                                    <i class="fa-solid fa-location-dot"></i> Cek Lokasi
                                </button>
                            </div>
                            <div id="location-lookup-feedback" style="font-size: 12px; margin-top: 5px; font-weight: 500;"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="location_id">Location UUID SATUSEHAT</label>
                                <input type="text" id="location_id" class="form-input" value="b017aa54-f1df-4ec2-9d84-8823815d7228" placeholder="Contoh: b017aa54-f1df-4ec2-9d84-8823815d7228" required>
                                <small style="color: var(--text-muted); font-size: 11px;">Format ref: <code>Location/b017aa54...</code></small>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="location_display">Deskripsi Lokasi / Poliklinik / Ruangan</label>
                                <input type="text" id="location_display" class="form-input" value="Ruang 1A, Poliklinik Bedah Rawat Jalan Terpadu, Lantai 2, Gedung G" placeholder="Nama Ruangan/Poli" required>
                            </div>
                        </div>

                        <!-- Data Diagnosis (Condition) Section -->
                        <div class="section-divider">Data Diagnosis / Kondisi (Diagnosis)</div>
                        
                        <div class="form-group full-width">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                <input type="checkbox" id="include_diagnosis" checked onchange="updateJSONFromForm()" style="width: 16px; height: 16px;">
                                Sertakan Elemen Diagnosis (Wajib di SATUSEHAT)
                            </label>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="condition_id">ID Condition SATUSEHAT (Condition UUID)</label>
                                <input type="text" id="condition_id" class="form-input" value="10000001" placeholder="Contoh: 10000001 atau UUID Condition" required>
                                <small style="color: var(--text-muted); font-size: 11px;">Format ref: <code>Condition/10000001</code></small>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="condition_display">Deskripsi Diagnosa / Nama Penyakit</label>
                                <input type="text" id="condition_display" class="form-input" value="Kecelakaan lalu lintas" placeholder="Nama Diagnosa/Penyakit" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="diagnosis_use_code">Peran Diagnosa (Diagnosis Role / Use)</label>
                                <select id="diagnosis_use_code" class="form-input" style="height: 44px; background-color: #fafafa;" onchange="updateDiagnosisUseDisplay()">
                                    <option value="DD" data-display="Discharge diagnosis" selected>DD - Discharge diagnosis (Diagnosa Akhir / Utama)</option>
                                    <option value="AD" data-display="Admission diagnosis">AD - Admission diagnosis (Diagnosa Masuk)</option>
                                    <option value="CC" data-display="Chief complaint">CC - Chief complaint (Keluhan Utama)</option>
                                    <option value="CM" data-display="Comorbidity diagnosis">CM - Comorbidity diagnosis (Diagnosa Penyerta)</option>
                                    <option value="pre-op" data-display="Pre-operative diagnosis">pre-op - Pre-operative diagnosis (Diagnosa Pre-Op)</option>
                                    <option value="post-op" data-display="Post-operative diagnosis">post-op - Post-operative diagnosis (Diagnosa Post-Op)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="diagnosis_use_display">Diagnosis Role Display</label>
                                <input type="text" id="diagnosis_use_display" class="form-input" value="Discharge diagnosis" readonly style="background-color: #f1f5f9;">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="diagnosis_rank">Rank Diagnosa (Peringkat)</label>
                            <input type="number" id="diagnosis_rank" class="form-input" value="1" min="1" required style="width: 140px;">
                            <small style="color: var(--text-muted); font-size: 11px;">1 untuk Diagnosa Utama, 2+ untuk Diagnosa Sekunder</small>
                        </div>

                    </form>
                </div>

                <!-- Tab content JSON Manual -->
                <div class="json-editor-container" id="tab-json-content" style="display: none;">
                    <textarea id="raw_json_textarea" class="json-textarea" spellcheck="false" placeholder="Tuliskan payload FHIR Encounter JSON di sini..."></textarea>
                </div>
            </div>

            <!-- Right Workspace: Preview & Response Console -->
            <div class="workspace-card" style="height: auto;">
                <div class="card-header-tabs">
                    <div class="tabs-list">
                        <span class="tab-btn active" style="border-bottom-color: transparent; cursor: default;">
                            <i class="fa-solid fa-eye"></i> Pratinjau Payload JSON FHIR
                        </span>
                    </div>
                    <div class="header-actions">
                        <button type="button" class="btn-outline" onclick="copyJSONPayload()" title="Salin Payload JSON">
                            <i class="fa-solid fa-copy"></i> Salin JSON
                        </button>
                    </div>
                </div>

                <div class="preview-pane">
                    <div style="background-color: #0f172a; padding: 20px; overflow-y: auto; max-height: 480px;">
                        <pre id="live-json-preview" style="font-family: 'Fira Code', monospace; font-size: 13px; color: #38bdf8; white-space: pre-wrap; word-break: break-all; margin: 0;"></pre>
                    </div>

                    <div class="preview-actions-bar">
                        <button type="button" class="btn-outline" onclick="resetFormToDefault()" style="padding: 12px 20px;">
                            <i class="fa-solid fa-rotate-left"></i> Reset Form
                        </button>
                        <button type="button" class="btn-primary" id="btn-submit-fhir" onclick="submitToSatuSehat()">
                            <i class="fa-solid fa-paper-plane"></i> Kirim ke SATUSEHAT
                        </button>
                    </div>
                </div>

                <!-- Response Console -->
                <div class="console-container" id="console-output">
                    <div class="console-header">
                        <div class="console-title">
                            <i class="fa-solid fa-terminal"></i> Status & Log Respons Server
                        </div>
                        <span id="response-time" style="color: #94a3b8; font-size: 12px;"></span>
                    </div>
                    
                    <div class="console-body">
                        <!-- Progress Stepper -->
                        <div class="stepper">
                            <div class="step-item" id="step-validate">
                                <span class="step-icon"><i class="fa-solid fa-circle-dot"></i></span>
                                <span>Langkah 1: Validasi Payload JSON Client-side</span>
                            </div>
                            <div class="step-item" id="step-auth">
                                <span class="step-icon"><i class="fa-solid fa-circle-dot"></i></span>
                                <span>Langkah 2: Autentikasi Access Token OAuth2 SATUSEHAT</span>
                            </div>
                            <div class="step-item" id="step-send">
                                <span class="step-icon"><i class="fa-solid fa-circle-dot"></i></span>
                                <span>Langkah 3: Pengiriman Resource Encounter (POST /Encounter)</span>
                            </div>
                            <div class="step-item" id="step-db">
                                <span class="step-icon"><i class="fa-solid fa-circle-dot"></i></span>
                                <span id="step-db-text">Langkah 4: Sinkronisasi Database SIMRS Lokal (satu_sehat_encounter)</span>
                            </div>
                        </div>

                        <div id="console-message" style="margin-top: 10px; font-weight: 500;"></div>
                        <pre id="console-json" class="response-box" style="display: none;"></pre>
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
            const formInputs = document.querySelectorAll('#encounter-form input, #encounter-form select');
            formInputs.forEach(input => {
                input.addEventListener('input', updateJSONFromForm);
                input.addEventListener('change', updateJSONFromForm);
            });
            
            // Generate awal
            updateJSONFromForm();
        });

        function updateClassDisplay() {
            const selectElem = document.getElementById('class_code');
            const selectedOption = selectElem.options[selectElem.selectedIndex];
            const displayVal = selectedOption.getAttribute('data-display') || 'ambulatory';
            document.getElementById('class_display').value = displayVal;
            updateJSONFromForm();
        }

        function updateParticipantTypeDisplay() {
            const selectElem = document.getElementById('participant_type_code');
            const selectedOption = selectElem.options[selectElem.selectedIndex];
            const displayVal = selectedOption.getAttribute('data-display') || 'attender';
            document.getElementById('participant_type_display').value = displayVal;
            updateJSONFromForm();
        }

        function updateDiagnosisUseDisplay() {
            const selectElem = document.getElementById('diagnosis_use_code');
            const selectedOption = selectElem.options[selectElem.selectedIndex];
            const displayVal = selectedOption.getAttribute('data-display') || 'Discharge diagnosis';
            document.getElementById('diagnosis_use_display').value = displayVal;
            updateJSONFromForm();
        }

        function switchTab(tab) {
            currentTab = tab;
            
            document.getElementById('tab-form-btn').classList.toggle('active', tab === 'form');
            document.getElementById('tab-json-btn').classList.toggle('active', tab === 'json');
            
            document.getElementById('tab-form-content').style.display = tab === 'form' ? 'block' : 'none';
            document.getElementById('tab-json-content').style.display = tab === 'json' ? 'block' : 'none';
            
            if (tab === 'json') {
                rawJsonTextarea.value = generateJSONFromFields();
                liveJsonPreview.textContent = rawJsonTextarea.value;
                
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

        // Membaca input fields dan mengembalikan string JSON standar HL7 FHIR Encounter
        function generateJSONFromFields() {
            const status = document.getElementById('status').value;
            const classCode = document.getElementById('class_code').value;
            const classDisplay = document.getElementById('class_display').value || "ambulatory";
            
            let patientId = document.getElementById('patient_id').value.trim();
            patientId = patientId.replace(/^Patient\//i, '');
            const patientName = document.getElementById('patient_name').value.trim();
            
            let practitionerId = document.getElementById('practitioner_id').value.trim();
            practitionerId = practitionerId.replace(/^Practitioner\//i, '');
            const practitionerName = document.getElementById('practitioner_name').value.trim();
            
            const participantTypeCode = document.getElementById('participant_type_code').value;
            const participantTypeDisplay = document.getElementById('participant_type_display').value || "attender";
            
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            let locationId = document.getElementById('location_id').value.trim();
            locationId = locationId.replace(/^Location\//i, '');
            const locationDisplay = document.getElementById('location_display').value.trim();
            
            const identifierValue = document.getElementById('identifier_value').value.trim();

            const includeDiagnosis = document.getElementById('include_diagnosis') ? document.getElementById('include_diagnosis').checked : true;
            let conditionId = document.getElementById('condition_id') ? document.getElementById('condition_id').value.trim() : '';
            conditionId = conditionId.replace(/^Condition\//i, '');
            const conditionDisplay = document.getElementById('condition_display') ? document.getElementById('condition_display').value.trim() : '';
            const diagnosisUseCode = document.getElementById('diagnosis_use_code') ? document.getElementById('diagnosis_use_code').value : 'DD';
            const diagnosisUseDisplay = document.getElementById('diagnosis_use_display') ? document.getElementById('diagnosis_use_display').value : 'Discharge diagnosis';
            const diagnosisRank = document.getElementById('diagnosis_rank') ? (parseInt(document.getElementById('diagnosis_rank').value) || 1) : 1;

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
                "resourceType": "Encounter",
                "status": status,
                "class": {
                    "system": "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    "code": classCode,
                    "display": classDisplay
                },
                "subject": {
                    "reference": "Patient/" + patientId,
                    "display": patientName
                },
                "participant": [
                    {
                        "type": [
                            {
                                "coding": [
                                    {
                                        "system": "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                        "code": participantTypeCode,
                                        "display": participantTypeDisplay
                                    }
                                ]
                            }
                        ],
                        "individual": {
                            "reference": "Practitioner/" + practitionerId,
                            "display": practitionerName
                        }
                    }
                ],
                "period": {
                    "start": startFormatted
                },
                "location": [
                    {
                        "location": {
                            "reference": "Location/" + locationId,
                            "display": locationDisplay
                        }
                    }
                ],
                "statusHistory": [
                    {
                        "status": status,
                        "period": {
                            "start": startFormatted
                        }
                    }
                ],
                "serviceProvider": {
                    "reference": "Organization/" + organizationId
                },
                "identifier": [
                    {
                        "system": "http://sys-ids.kemkes.go.id/encounter/" + organizationId,
                        "value": identifierValue
                    }
                ]
            };

            if (includeDiagnosis && conditionId) {
                jsonObject.diagnosis = [
                    {
                        "condition": {
                            "reference": "Condition/" + conditionId,
                            "display": conditionDisplay
                        },
                        "use": {
                            "coding": [
                                {
                                    "system": "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                    "code": diagnosisUseCode,
                                    "display": diagnosisUseDisplay
                                }
                            ]
                        },
                        "rank": diagnosisRank
                    }
                ];
            }

            if (endFormatted) {
                jsonObject.period.end = endFormatted;
                if (jsonObject.statusHistory[0]) {
                    jsonObject.statusHistory[0].period.end = endFormatted;
                }
            }

            return JSON.stringify(jsonObject, null, 2);
        }

        // Search Patient By NIK
        function lookupPatientByNIK() {
            const nikInput = document.getElementById('patient_nik_lookup');
            const feedbackElem = document.getElementById('patient-lookup-feedback');
            const btnLookup = document.getElementById('btn-patient-lookup');
            const nik = nikInput.value.trim();

            if (!nik || nik.length !== 16 || !/^\d+$/.test(nik)) {
                feedbackElem.style.color = 'var(--error)';
                feedbackElem.textContent = '❌ Masukkan 16 digit NIK berupa angka.';
                return;
            }

            feedbackElem.style.color = 'var(--accent)';
            feedbackElem.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mencari pasien di SATUSEHAT...';
            btnLookup.disabled = true;

            const formData = new FormData();
            formData.append('action', 'cari_pasien_nik');
            formData.append('nik', nik);

            fetch('satu_sehat_kirim_encounter.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                btnLookup.disabled = false;
                if (data.success) {
                    document.getElementById('patient_id').value = data.id;
                    document.getElementById('patient_name').value = data.name;
                    feedbackElem.style.color = 'var(--success)';
                    feedbackElem.innerHTML = '✔ Pasien Ditemukan! ID: <strong>' + data.id + '</strong> (' + data.name + ')';
                    updateJSONFromForm();
                } else {
                    feedbackElem.style.color = 'var(--error)';
                    feedbackElem.textContent = '❌ ' + (data.message || 'Pasien tidak ditemukan.');
                }
            })
            .catch(err => {
                btnLookup.disabled = false;
                feedbackElem.style.color = 'var(--error)';
                feedbackElem.textContent = '❌ Terjadi kesalahan jaringan / server.';
            });
        }

        // Search Practitioner By NIK
        function lookupPractitionerByNIK() {
            const nikInput = document.getElementById('nakes_nik_lookup');
            const feedbackElem = document.getElementById('nakes-lookup-feedback');
            const btnLookup = document.getElementById('btn-nakes-lookup');
            const nik = nikInput.value.trim();

            if (!nik || nik.length !== 16 || !/^\d+$/.test(nik)) {
                feedbackElem.style.color = 'var(--error)';
                feedbackElem.textContent = '❌ Masukkan 16 digit NIK berupa angka.';
                return;
            }

            feedbackElem.style.color = 'var(--accent)';
            feedbackElem.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mencari Nakes di SATUSEHAT...';
            btnLookup.disabled = true;

            const formData = new FormData();
            formData.append('action', 'cari_nakes_nik');
            formData.append('nik', nik);

            fetch('satu_sehat_kirim_encounter.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                btnLookup.disabled = false;
                if (data.success) {
                    document.getElementById('practitioner_id').value = data.id;
                    document.getElementById('practitioner_name').value = data.name;
                    feedbackElem.style.color = 'var(--success)';
                    feedbackElem.innerHTML = '✔ Nakes Ditemukan! ID: <strong>' + data.id + '</strong> (' + data.name + ')';
                    updateJSONFromForm();
                } else {
                    feedbackElem.style.color = 'var(--error)';
                    feedbackElem.textContent = '❌ ' + (data.message || 'Nakes tidak ditemukan.');
                }
            })
            .catch(err => {
                btnLookup.disabled = false;
                feedbackElem.style.color = 'var(--error)';
                feedbackElem.textContent = '❌ Terjadi kesalahan jaringan / server.';
            });
        }

        // Lookup Location By ID
        function lookupLocationByID() {
            const locInput = document.getElementById('location_id_lookup');
            const feedbackElem = document.getElementById('location-lookup-feedback');
            const btnLookup = document.getElementById('btn-location-lookup');
            const locId = locInput.value.trim();

            if (!locId) {
                feedbackElem.style.color = 'var(--error)';
                feedbackElem.textContent = '❌ Masukkan Location UUID SATUSEHAT.';
                return;
            }

            feedbackElem.style.color = 'var(--accent)';
            feedbackElem.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memeriksa Lokasi di SATUSEHAT...';
            btnLookup.disabled = true;

            const formData = new FormData();
            formData.append('action', 'cari_location_id');
            formData.append('location_id', locId);

            fetch('satu_sehat_kirim_encounter.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                btnLookup.disabled = false;
                if (data.success) {
                    document.getElementById('location_id').value = data.id;
                    if (data.name) {
                        document.getElementById('location_display').value = data.name;
                    }
                    feedbackElem.style.color = 'var(--success)';
                    feedbackElem.innerHTML = '✔ Lokasi Ditemukan! Name: <strong>' + (data.name || data.id) + '</strong>';
                    updateJSONFromForm();
                } else {
                    feedbackElem.style.color = 'var(--error)';
                    feedbackElem.textContent = '❌ ' + (data.message || 'Lokasi tidak ditemukan.');
                }
            })
            .catch(err => {
                btnLookup.disabled = false;
                feedbackElem.style.color = 'var(--error)';
                feedbackElem.textContent = '❌ Terjadi kesalahan jaringan / server.';
            });
        }

        // Submit to SATUSEHAT
        function submitToSatuSehat() {
            const consoleArea = document.getElementById('console-output');
            consoleArea.style.display = 'block';
            
            resetSteps();
            
            const btnSubmit = document.getElementById('btn-submit-fhir');
            const originalBtnHtml = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...';

            let payloadToSend = "";
            if (currentTab === 'form') {
                payloadToSend = generateJSONFromFields();
            } else {
                payloadToSend = rawJsonTextarea.value;
            }

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

            setStepStatus('step-auth', 'active');

            const formData = new FormData();
            formData.append('action', 'kirim_encounter');
            formData.append('payload', payloadToSend);
            formData.append('no_rawat', document.getElementById('no_rawat').value.trim());

            const startTime = performance.now();

            fetch('satu_sehat_kirim_encounter.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
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

                if (data.step === 'token' && !data.success) {
                    setStepStatus('step-auth', 'failed');
                    setStepStatus('step-send', 'failed');
                    setStepStatus('step-db', 'failed');
                    showConsoleError(data.message, data.http_code || 401, data.response);
                    return;
                } else {
                    setStepStatus('step-auth', 'success');
                }

                setStepStatus('step-send', 'active');
                
                if (data.success) {
                    setStepStatus('step-send', 'success');
                    
                    setStepStatus('step-db', 'active');
                    if (data.db_saved) {
                        setStepStatus('step-db', 'success');
                        showConsoleSuccess(data.message + ' & Berhasil sinkronisasi ke tabel satu_sehat_encounter.', data.http_code, data.response);
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
                setStepStatus('step-send', 'failed');
                setStepStatus('step-db', 'failed');
                showConsoleError('Terjadi kesalahan jaringan atau server: ' + error.message, 500);
            })
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalBtnHtml;
            });
        }

        function resetSteps() {
            ['step-validate', 'step-auth', 'step-send', 'step-db'].forEach(id => {
                const elem = document.getElementById(id);
                elem.className = 'step-item';
                elem.querySelector('.step-icon').innerHTML = '<i class="fa-solid fa-circle-dot"></i>';
            });
            document.getElementById('step-db-text').textContent = 'Langkah 4: Sinkronisasi Database SIMRS Lokal (satu_sehat_encounter)';
            document.getElementById('console-json').style.display = 'none';
        }

        function setStepStatus(stepId, status) {
            const elem = document.getElementById(stepId);
            if (!elem) return;
            elem.className = 'step-item ' + status;
            const icon = elem.querySelector('.step-icon');
            if (status === 'active') {
                icon.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
            } else if (status === 'success') {
                icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
            } else if (status === 'failed') {
                icon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
            }
        }

        function showConsoleSuccess(msg, httpCode, jsonResponse) {
            const msgElem = document.getElementById('console-message');
            msgElem.style.color = '#4ade80';
            msgElem.innerHTML = '✔ HTTP ' + (httpCode || 201) + ' OK - ' + msg;
            
            if (jsonResponse) {
                const jsonElem = document.getElementById('console-json');
                jsonElem.style.display = 'block';
                jsonElem.textContent = JSON.stringify(jsonResponse, null, 2);
            }
        }

        function showConsoleError(msg, httpCode, jsonResponse) {
            const msgElem = document.getElementById('console-message');
            msgElem.style.color = '#f87171';
            msgElem.innerHTML = '❌ Error ' + (httpCode ? '(HTTP ' + httpCode + ') ' : '') + ': ' + msg;
            
            if (jsonResponse) {
                const jsonElem = document.getElementById('console-json');
                jsonElem.style.display = 'block';
                jsonElem.textContent = JSON.stringify(jsonResponse, null, 2);
            }
        }

        function clearTokenCache() {
            if (!confirm('Apakah Anda yakin ingin mengosongkan cache access token SATUSEHAT?')) return;
            
            const formData = new FormData();
            formData.append('action', 'clear_token');

            fetch('satu_sehat_kirim_encounter.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
            });
        }

        function copyJSONPayload() {
            let jsonText = "";
            if (currentTab === 'form') {
                jsonText = generateJSONFromFields();
            } else {
                jsonText = rawJsonTextarea.value;
            }

            navigator.clipboard.writeText(jsonText).then(() => {
                alert('Payload JSON Encounter berhasil disalin ke clipboard!');
            }).catch(err => {
                alert('Gagal menyalin text: ' + err);
            });
        }

        function resetFormToDefault() {
            if (!confirm('Kembalikan seluruh isi form ke contoh bawaan?')) return;
            
            document.getElementById('no_rawat').value = "2024/06/14/000001";
            document.getElementById('identifier_value').value = "P20240001";
            document.getElementById('status').value = "arrived";
            document.getElementById('class_code').value = "AMB";
            updateClassDisplay();
            
            document.getElementById('patient_nik_lookup').value = "";
            document.getElementById('patient_id').value = "100000030009";
            document.getElementById('patient_name').value = "Budi Santoso";
            
            document.getElementById('nakes_nik_lookup').value = "";
            document.getElementById('practitioner_id').value = "N10000001";
            document.getElementById('practitioner_name').value = "Dokter Bronsig";
            document.getElementById('participant_type_code').value = "ATND";
            updateParticipantTypeDisplay();
            
            document.getElementById('start_time').value = "2022-06-14T07:00";
            document.getElementById('end_time').value = "";
            
            document.getElementById('location_id_lookup').value = "";
            document.getElementById('location_id').value = "b017aa54-f1df-4ec2-9d84-8823815d7228";
            document.getElementById('location_display').value = "Ruang 1A, Poliklinik Bedah Rawat Jalan Terpadu, Lantai 2, Gedung G";
            
            if (document.getElementById('include_diagnosis')) {
                document.getElementById('include_diagnosis').checked = true;
                document.getElementById('condition_id').value = "10000001";
                document.getElementById('condition_display').value = "Kecelakaan lalu lintas";
                document.getElementById('diagnosis_use_code').value = "DD";
                updateDiagnosisUseDisplay();
                document.getElementById('diagnosis_rank').value = "1";
            }
            
            updateJSONFromForm();
        }
    </script>
</body>
</html>
