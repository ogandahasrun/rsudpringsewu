<?php
/**
 * Halaman Uji Bridging BPJS Apotek Online (Apol)
 * RSUD Pringsewu
 * Mendukung pengujian endpoint DPHO, Setting PPK, Pencarian Obat, dan Custom Request
 */

require_once 'koneksi.php';
require_once 'bpjssignature.php';
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// ----------------------------------------------------------------------------
// DATA INSTANSI DARI DATABASE
// ----------------------------------------------------------------------------
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU";
$logo_src = "images/logo.png";

if ($result_instansi && $row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    if (!empty($row_instansi['logo'])) {
        $logo_src = "data:image/png;base64," . base64_encode($row_instansi['logo']);
    }
}

// ----------------------------------------------------------------------------
// NILAI DEFAULT DARI koneksi.php
// ----------------------------------------------------------------------------
$defBaseUrl   = $URLAPOTEK ?? 'https://apijkn-dev.bpjs-kesehatan.go.id/apotek-rest-dev';
$defConsId    = $CONSIDAPOTEK ?? '31878';
$defSecretKey = $SECRETKEYAPOTEK ?? '2uP8A0AAEA';
$defUserKey   = $USERKEYAPOTEK ?? '2edc08496cf5742249e43478c6a228df';
$defKodeIF    = $KODEIFAPOTEK ?? '0511A001';

// ----------------------------------------------------------------------------
// AJAX HANDLER: PENGUJIAN APOTEK REAL-TIME
// ----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'run_test') {
    header('Content-Type: application/json; charset=utf-8');

    $baseUrl   = isset($_POST['base_url']) ? trim($_POST['base_url']) : $defBaseUrl;
    $consId    = isset($_POST['consid']) ? trim($_POST['consid']) : $defConsId;
    $secretKey = isset($_POST['secret_key']) ? trim($_POST['secret_key']) : $defSecretKey;
    $userKey   = isset($_POST['user_key']) ? trim($_POST['user_key']) : $defUserKey;
    $endpoint  = isset($_POST['endpoint']) ? trim($_POST['endpoint']) : '/referensi/dpho';
    $method    = isset($_POST['http_method']) ? strtoupper(trim($_POST['http_method'])) : 'GET';
    $payload   = isset($_POST['payload']) ? trim($_POST['payload']) : '';
    $timeout   = isset($_POST['timeout']) ? (int)$_POST['timeout'] : 15;

    $startTime = microtime(true);
    date_default_timezone_set('UTC');
    $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));

    $res = [
        'success'        => false,
        'http_code'      => 0,
        'execution_time' => 0,
        'status_badge'   => 'error',
        'status_title'   => 'Gagal',
        'status_message' => '',
        'meta_code'      => null,
        'meta_message'   => null,
        'timestamp'      => $tStamp,
        'signature'      => '',
        'target_url'     => '',
        'headers_sent'   => [],
        'curl_error'     => '',
        'raw_response'   => '',
        'decrypted_data' => null,
        'notes'          => ''
    ];

    if (empty($baseUrl) || empty($consId) || empty($secretKey) || empty($userKey)) {
        $res['status_title']   = 'Kredensial Tidak Lengkap';
        $res['status_message'] = 'Base URL, Consumer ID, Secret Key, dan User Key wajib diisi.';
        $res['execution_time'] = round((microtime(true) - $startTime) * 1000);
        echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $signature = base64_encode(hash_hmac('sha256', $consId . '&' . $tStamp, $secretKey, true));
    $res['signature'] = $signature;

    $headers = [
        'X-cons-id: ' . $consId,
        'X-timestamp: ' . $tStamp,
        'X-signature: ' . $signature,
        'user_key: ' . $userKey,
        'Content-Type: application/json'
    ];
    $res['headers_sent'] = $headers;

    $targetUrl = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    $res['target_url'] = $targetUrl;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if (!empty($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $execTime = round((microtime(true) - $startTime) * 1000);
    curl_close($ch);

    $res['http_code']      = $httpCode;
    $res['execution_time'] = $execTime;
    $res['curl_error']     = $curlError;
    $res['raw_response']   = $raw;

    if ($curlErrno !== 0) {
        $res['success']        = false;
        $res['status_badge']   = 'error';
        $res['status_title']   = 'Koneksi Gagal (cURL)';
        $res['status_message'] = "Error cURL ({$curlErrno}): {$curlError}";
        $res['notes']          = 'Pastikan server Anda dapat mengakses domain BPJS dan koneksi internet stabil.';
    } else {
        $json = json_decode($raw, true);
        $meta = $json['metaData'] ?? $json['metadata'] ?? null;

        if ($meta) {
            $mCode = (string)($meta['code'] ?? '');
            $mMsg  = $meta['message'] ?? '';
            $res['meta_code']    = $mCode;
            $res['meta_message'] = $mMsg;

            if ($mCode === '200' || $mCode === '1') {
                $res['success']        = true;
                $res['status_badge']   = 'success';
                $res['status_title']   = 'Terhubung & Valid (200)';
                $res['status_message'] = "Sukses! Respon BPJS [{$mCode}]: {$mMsg}";
            } elseif ($mCode === '404' && stripos($mMsg, 'Unauthorized') !== false) {
                $res['success']        = false;
                $res['status_badge']   = 'warning';
                $res['status_title']   = 'Koneksi Diterima BPJS (404 Unauthorized)';
                $res['status_message'] = "Server BPJS merespon: {$mMsg}. Kredensial terhubung ke Gateway BPJS, namun belum di-whitelist atau berbeda hak akses modul.";
                $res['notes']          = 'Response ini menandakan otentikasi signature berhasil sampai ke BPJS API Gateway, namun User Key atau Consumer ID belum diotorisasi untuk service endpoint ini.';
            } else {
                $res['success']        = false;
                $res['status_badge']   = 'warning';
                $res['status_title']   = "Respon BPJS [{$mCode}]";
                $res['status_message'] = "BPJS merespon: {$mMsg}";
            }
        } elseif ($httpCode === 200) {
            $res['success']        = true;
            $res['status_badge']   = 'success';
            $res['status_title']   = 'HTTP 200 OK';
            $res['status_message'] = 'Server merespon dengan status HTTP 200 OK.';
        } elseif ($httpCode === 500) {
            $res['success']        = false;
            $res['status_badge']   = 'error';
            $res['status_title']   = 'BPJS Server Error (HTTP 500)';
            if (stripos($raw, 'Runtime Error') !== false) {
                $res['status_message'] = 'Server BPJS (ASP.NET) mengalami Runtime Error / Sedang Maintenance.';
                $res['notes']          = 'Pada server Development BPJS (apotek-rest-dev), Runtime Error 500 sering terjadi saat server sandbox BPJS sedang offline atau konfigurasi internal database BPJS sandbox sedang dalam pemeliharaan berkala.';
            } else {
                $res['status_message'] = 'Server merespon HTTP 500 Internal Server Error.';
            }
        } elseif ($httpCode === 404) {
            $res['success']        = false;
            $res['status_badge']   = 'error';
            $res['status_title']   = 'Endpoint Tidak Ditemukan (404)';
            $res['status_message'] = 'URL atau endpoint tidak ditemukan di server tujuan.';
        } else {
            $res['success']        = false;
            $res['status_badge']   = 'error';
            $res['status_title']   = "HTTP {$httpCode}";
            $res['status_message'] = "Server mengembalikan status HTTP {$httpCode}.";
        }

        // Dekripsi jika respon terenkripsi
        if (isset($json['response']) && is_string($json['response']) && !empty($json['response'])) {
            try {
                $key = $consId . $secretKey . $tStamp;
                $keyHash = hex2bin(hash('sha256', $key));
                $iv = substr($keyHash, 0, 16);
                $aesDecrypted = openssl_decrypt(base64_decode($json['response']), 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, $iv);

                if ($aesDecrypted !== false && class_exists('\LZCompressor\LZString')) {
                    $decompressed = \LZCompressor\LZString::decompressFromEncodedURIComponent($aesDecrypted);
                    if (!empty($decompressed)) {
                        $res['decrypted_data'] = json_decode($decompressed, true) ?? $decompressed;
                    } else {
                        $res['decrypted_data'] = $aesDecrypted;
                    }
                } else {
                    $res['decrypted_data'] = $aesDecrypted ?: 'Dekripsi AES tidak menghasilkan teks valid.';
                }
            } catch (\Exception $e) {
                $res['decrypted_data'] = 'Error saat dekripsi: ' . $e->getMessage();
            }
        } elseif (isset($json['response'])) {
            $res['decrypted_data'] = $json['response'];
        }
    }

    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Bridging BPJS Apotek Online (Apol) - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --primary-light: #ecfdf5;
            --secondary: #0284c7;
            --secondary-light: #e0f2fe;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f0fdf4;
            background: linear-gradient(180deg, #ecfdf5 0%, #f8fafc 240px);
            color: var(--gray-800);
            min-height: 100vh;
            padding: 24px 16px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Top Nav & Header */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .nav-links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: #fff;
            color: var(--gray-700);
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
        }
        .btn-nav:hover {
            color: var(--primary);
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .header-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-logo {
            width: 52px;
            height: 52px;
            object-fit: contain;
        }

        .header-titles h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header-titles p {
            font-size: 13px;
            color: var(--gray-600);
            margin-top: 2px;
        }

        .badge-apol {
            background: #10b981;
            color: white;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .header-right {
            text-align: right;
            font-size: 12px;
            color: var(--gray-600);
        }

        /* Credentials Summary Bar */
        .cred-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 18px 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            margin-bottom: 24px;
        }

        .cred-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-200);
        }

        .cred-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cred-env-switch {
            display: flex;
            gap: 6px;
        }

        .btn-env-switch {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid var(--gray-300);
            background: var(--gray-100);
            color: var(--gray-700);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-env-switch.active, .btn-env-switch:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .cred-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .cred-item {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            padding: 10px 12px;
            border-radius: 8px;
        }

        .cred-lbl {
            font-size: 11px;
            color: var(--gray-600);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .cred-val {
            font-family: 'Consolas', monospace;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-800);
            word-break: break-all;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* 1-Click Quick Diagnostics Section */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .quick-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 16px 18px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 12px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .quick-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .quick-top {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .quick-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .quick-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .quick-desc {
            font-size: 12px;
            color: var(--gray-600);
            margin-top: 2px;
        }

        .quick-endpoint {
            font-family: 'Consolas', monospace;
            font-size: 11px;
            color: var(--secondary);
            background: var(--secondary-light);
            padding: 4px 8px;
            border-radius: 6px;
            word-break: break-all;
        }

        .btn-quick-run {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-quick-run:hover {
            background: var(--primary-dark);
        }

        /* Two Column Layout: Custom Request Form & Results */
        .workspace-grid {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 20px;
            align-items: start;
        }

        @media (max-width: 992px) {
            .workspace-grid {
                grid-template-columns: 1fr;
            }
        }

        .panel-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 5px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 13px;
            transition: border-color 0.2s;
            background: #fff;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5,150,105,0.15);
        }

        .monospace {
            font-family: 'Consolas', monospace;
        }

        .btn-submit {
            width: 100%;
            padding: 11px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-submit:hover:not(:disabled) {
            background: var(--primary-dark);
            box-shadow: var(--shadow-md);
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Results Panel */
        .result-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
            color: var(--gray-600);
        }
        .result-empty i {
            font-size: 48px;
            color: var(--gray-300);
            margin-bottom: 12px;
        }

        .result-banner {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .result-banner.success { background: var(--success-light); border-left: 5px solid var(--success); color: #065f46; }
        .result-banner.warning { background: var(--warning-light); border-left: 5px solid var(--warning); color: #92400e; }
        .result-banner.error   { background: var(--danger-light); border-left: 5px solid var(--danger); color: #991b1b; }

        .result-meta-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .result-pill {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: 14px;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-600);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        .code-box {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            font-family: 'Consolas', monospace;
            font-size: 12px;
            line-height: 1.5;
            max-height: 480px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .alert-info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 12px;
            color: #1e40af;
            margin-top: 14px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: var(--gray-600);
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Top Navigation -->
    <div class="top-bar">
        <div class="nav-links">
            <a href="bpjs.php" class="btn-nav">
                <i class="fas fa-arrow-left"></i> Menu BPJS
            </a>
            <a href="uji_koneksi_bpjs.php" class="btn-nav">
                <i class="fas fa-network-wired"></i> Dashboard Semua Service BPJS
            </a>
        </div>
        <div style="font-size:12px; color:var(--gray-600);">
            <i class="fas fa-clock"></i> UTC: <?php echo gmdate('Y-m-d H:i:s'); ?> | Local: <?php echo date('H:i:s'); ?>
        </div>
    </div>

    <!-- Header Branding -->
    <div class="header-card">
        <div class="header-left">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo" class="header-logo">
            <div class="header-titles">
                <h1>
                    <span>Uji Bridging BPJS Apotek Online (Apol)</span>
                    <span class="badge-apol"><i class="fas fa-pills"></i> APOTEK-REST</span>
                </h1>
                <p><?php echo htmlspecialchars($nama_instansi); ?> &bull; Instalasi Farmasi RS (Kode IF: <strong><?php echo htmlspecialchars($defKodeIF); ?></strong>)</p>
            </div>
        </div>
        <div class="header-right">
            <div>Consumer ID: <strong><?php echo htmlspecialchars($defConsId); ?></strong></div>
            <div style="margin-top:3px; color:var(--primary); font-weight:600;">
                <i class="fas fa-shield-halved"></i> AES-256-CBC Decryptor Ready
            </div>
        </div>
    </div>

    <!-- Credentials Status Summary -->
    <div class="cred-card">
        <div class="cred-header">
            <div class="cred-title">
                <i class="fas fa-key"></i>
                <span>Konfigurasi Bridging Apotek Aktif (koneksi.php)</span>
            </div>
            <div class="cred-env-switch">
                <button type="button" class="btn-env-switch <?php echo stripos($defBaseUrl, 'dev') !== false ? 'active' : ''; ?>"
                        onclick="setEnvironment('dev')">
                    <i class="fas fa-vial"></i> Development (Sandbox)
                </button>
                <button type="button" class="btn-env-switch <?php echo stripos($defBaseUrl, 'dev') === false ? 'active' : ''; ?>"
                        onclick="setEnvironment('prod')">
                    <i class="fas fa-server"></i> Production
                </button>
            </div>
        </div>

        <div class="cred-grid">
            <div class="cred-item">
                <div class="cred-lbl">Base URL Apol</div>
                <div class="cred-val" id="dispBaseUrl"><?php echo htmlspecialchars($defBaseUrl); ?></div>
            </div>
            <div class="cred-item">
                <div class="cred-lbl">Kode IF RSUD Pringsewu</div>
                <div class="cred-val" style="color:var(--primary);"><?php echo htmlspecialchars($defKodeIF); ?></div>
            </div>
            <div class="cred-item">
                <div class="cred-lbl">Consumer ID</div>
                <div class="cred-val"><?php echo htmlspecialchars($defConsId); ?></div>
            </div>
            <div class="cred-item">
                <div class="cred-lbl">Secret Key</div>
                <div class="cred-val">
                    <span id="txtSecretKey">••••••••••</span>
                    <button type="button" onclick="toggleSecret()" style="background:none;border:none;cursor:pointer;color:var(--gray-600);">
                        <i class="fas fa-eye" id="eyeSecret"></i>
                    </button>
                </div>
            </div>
            <div class="cred-item">
                <div class="cred-lbl">User Key</div>
                <div class="cred-val">
                    <span id="txtUserKey">••••••••••••••••</span>
                    <button type="button" onclick="toggleUserKey()" style="background:none;border:none;cursor:pointer;color:var(--gray-600);">
                        <i class="fas fa-eye" id="eyeUserKey"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 1-Click Quick Diagnostics Section -->
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-bolt" style="color:var(--warning);"></i>
            <span>Diagnostik Cepat Endpoint Apotek Online</span>
        </div>
        <div style="font-size:12px; color:var(--gray-600);">
            Klik salah satu tombol untuk langsung menguji endpoint spesifik
        </div>
    </div>

    <div class="quick-grid">
        <!-- 1. DPHO -->
        <div class="quick-card" onclick="quickTest('/referensi/dpho', 'GET')">
            <div class="quick-top">
                <div class="quick-icon"><i class="fas fa-tablets"></i></div>
                <div>
                    <div class="quick-name">Referensi DPHO</div>
                    <div class="quick-desc">Daftar & Plafon Harga Obat JKN</div>
                </div>
            </div>
            <div class="quick-endpoint">GET /referensi/dpho</div>
            <button type="button" class="btn-quick-run">
                <i class="fas fa-play"></i> Uji DPHO
            </button>
        </div>

        <!-- 2. Setting PPK -->
        <div class="quick-card" onclick="quickTest('/referensi/settingppk/read/<?php echo htmlspecialchars($defKodeIF); ?>', 'GET')">
            <div class="quick-top">
                <div class="quick-icon"><i class="fas fa-hospital"></i></div>
                <div>
                    <div class="quick-name">Setting Profil PPK</div>
                    <div class="quick-desc">Profil IF RSUD Pringsewu</div>
                </div>
            </div>
            <div class="quick-endpoint">GET /referensi/settingppk/read/<?php echo htmlspecialchars($defKodeIF); ?></div>
            <button type="button" class="btn-quick-run">
                <i class="fas fa-play"></i> Uji Setting PPK
            </button>
        </div>

        <!-- 3. Poli -->
        <div class="quick-card" onclick="quickTest('/referensi/poli', 'GET')">
            <div class="quick-top">
                <div class="quick-icon"><i class="fas fa-stethoscope"></i></div>
                <div>
                    <div class="quick-name">Referensi Poli</div>
                    <div class="quick-desc">Daftar unit rawat jalan / poli</div>
                </div>
            </div>
            <div class="quick-endpoint">GET /referensi/poli</div>
            <button type="button" class="btn-quick-run">
                <i class="fas fa-play"></i> Uji Ref Poli
            </button>
        </div>

        <!-- 4. Faskes -->
        <div class="quick-card" onclick="quickTest('/referensi/faskes/pringsewu/2', 'GET')">
            <div class="quick-top">
                <div class="quick-icon"><i class="fas fa-map-location-dot"></i></div>
                <div>
                    <div class="quick-name">Referensi Faskes</div>
                    <div class="quick-desc">Pencarian fasilitas kesehatan</div>
                </div>
            </div>
            <div class="quick-endpoint">GET /referensi/faskes/pringsewu/2</div>
            <button type="button" class="btn-quick-run">
                <i class="fas fa-play"></i> Uji Faskes
            </button>
        </div>
    </div>

    <!-- Workspace Grid: Request Form on Left, Live Results on Right -->
    <div class="workspace-grid">
        <!-- LEFT: CUSTOM REQUEST FORM -->
        <div class="panel-card">
            <div class="section-title" style="margin-bottom:14px; font-size:15px;">
                <i class="fas fa-terminal"></i>
                <span>Konsol Pengujian Interaktif</span>
            </div>

            <form id="formApotekTest" onsubmit="executeTest(event)">
                <div class="form-group">
                    <label class="form-label" for="inp_base_url"><i class="fas fa-link"></i> Base URL:</label>
                    <input type="text" id="inp_base_url" name="base_url" class="form-control monospace"
                           value="<?php echo htmlspecialchars($defBaseUrl); ?>" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div class="form-group">
                        <label class="form-label" for="inp_consid"><i class="fas fa-id-badge"></i> Cons ID:</label>
                        <input type="text" id="inp_consid" name="consid" class="form-control monospace"
                               value="<?php echo htmlspecialchars($defConsId); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inp_kode_if"><i class="fas fa-clinic-medical"></i> Kode IF:</label>
                        <input type="text" id="inp_kode_if" name="kode_if" class="form-control monospace"
                               value="<?php echo htmlspecialchars($defKodeIF); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inp_secret_key"><i class="fas fa-key"></i> Secret Key:</label>
                    <input type="text" id="inp_secret_key" name="secret_key" class="form-control monospace"
                           value="<?php echo htmlspecialchars($defSecretKey); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inp_user_key"><i class="fas fa-user-shield"></i> User Key:</label>
                    <input type="text" id="inp_user_key" name="user_key" class="form-control monospace"
                           value="<?php echo htmlspecialchars($defUserKey); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inp_preset_endpoint"><i class="fas fa-list"></i> Pilihan Endpoint Preset:</label>
                    <select id="inp_preset_endpoint" class="form-control" onchange="applyPresetEndpoint(this.value)">
                        <option value="/referensi/dpho">1. Referensi DPHO (/referensi/dpho)</option>
                        <option value="/referensi/settingppk/read/<?php echo htmlspecialchars($defKodeIF); ?>">2. Setting Profil PPK (/referensi/settingppk/read/<?php echo htmlspecialchars($defKodeIF); ?>)</option>
                        <option value="/referensi/poli">3. Referensi Poli (/referensi/poli)</option>
                        <option value="/referensi/faskes/pringsewu/2">4. Referensi Faskes (/referensi/faskes/pringsewu/2)</option>
                        <option value="/referensi/obat/daftar">5. Referensi Daftar Obat (/referensi/obat/daftar)</option>
                        <option value="/pelayanan/obat/daftar">6. Pelayanan Obat (/pelayanan/obat/daftar)</option>
                        <option value="custom">-- Kustom Manual --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inp_endpoint"><i class="fas fa-route"></i> Endpoint API:</label>
                    <input type="text" id="inp_endpoint" name="endpoint" class="form-control monospace"
                           value="/referensi/dpho" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div class="form-group">
                        <label class="form-label" for="inp_method"><i class="fas fa-exchange-alt"></i> Method:</label>
                        <select id="inp_method" name="http_method" class="form-control">
                            <option value="GET" selected>GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inp_timeout"><i class="fas fa-stopwatch"></i> Timeout (dtk):</label>
                        <input type="number" id="inp_timeout" name="timeout" class="form-control"
                               value="15" min="5" max="60">
                    </div>
                </div>

                <div class="form-group" id="grpPayload" style="display:none;">
                    <label class="form-label" for="inp_payload"><i class="fas fa-code"></i> Request Body (JSON):</label>
                    <textarea id="inp_payload" name="payload" class="form-control monospace" rows="3"
                              placeholder='{"kodedokter": 0, ...}'></textarea>
                </div>

                <button type="submit" class="btn-submit" id="btnSubmitTest">
                    <i class="fas fa-paper-plane"></i> Jalankan Pengujian Apotek
                </button>
            </form>
        </div>

        <!-- RIGHT: LIVE RESULTS VIEWER -->
        <div class="panel-card" id="resultContainer">
            <div class="section-title" style="margin-bottom:14px; font-size:15px;">
                <i class="fas fa-square-poll-vertical"></i>
                <span>Hasil Respon API BPJS</span>
            </div>

            <!-- Empty State -->
            <div class="result-empty" id="resultEmpty">
                <i class="fas fa-network-wired"></i>
                <div style="font-weight:600; font-size:15px; margin-bottom:4px;">Belum Ada Pengujian Dijalankan</div>
                <div style="font-size:12px;">Pilih salah satu tombol diagnostik di atas atau klik "Jalankan Pengujian Apotek".</div>
            </div>

            <!-- Loading State -->
            <div class="result-empty" id="resultLoading" style="display:none;">
                <i class="fas fa-spinner spinner" style="color:var(--primary);"></i>
                <div style="font-weight:600; font-size:15px; margin-bottom:4px;">Menghubungi Server BPJS...</div>
                <div style="font-size:12px;">Menghitung HMAC-SHA256 signature, mengirim headers, dan mendekripsi respon.</div>
            </div>

            <!-- Actual Result Content -->
            <div id="resultBox" style="display:none;">
                <div class="result-banner" id="bannerStatus">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-circle-check" id="iconBanner" style="font-size:20px;"></i>
                        <div>
                            <div style="font-weight:700; font-size:14px;" id="lblStatusTitle">-</div>
                            <div style="font-size:12px;" id="lblStatusMessage">-</div>
                        </div>
                    </div>
                </div>

                <div class="result-meta-pills">
                    <span class="result-pill" id="pillHttp"><i class="fas fa-globe"></i> HTTP: -</span>
                    <span class="result-pill" id="pillLatency"><i class="fas fa-gauge"></i> Latency: -</span>
                    <span class="result-pill" id="pillCode"><i class="fas fa-tag"></i> Code: -</span>
                    <span class="result-pill" id="pillTime"><i class="fas fa-clock"></i> UTC: -</span>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button type="button" class="tab-btn active" onclick="switchTab('tabDecrypted')">
                        <i class="fas fa-unlock"></i> Data Terdekripsi / JSON
                    </button>
                    <button type="button" class="tab-btn" onclick="switchTab('tabRaw')">
                        <i class="fas fa-file-code"></i> Raw Body
                    </button>
                    <button type="button" class="tab-btn" onclick="switchTab('tabHeaders')">
                        <i class="fas fa-paper-plane"></i> Headers & Target
                    </button>
                </div>

                <div class="tab-content active" id="tabDecrypted">
                    <pre class="code-box" id="jsonDecrypted">Tidak ada data.</pre>
                </div>

                <div class="tab-content" id="tabRaw">
                    <pre class="code-box" id="jsonRaw">Tidak ada data.</pre>
                </div>

                <div class="tab-content" id="tabHeaders">
                    <div style="font-size:12px; margin-bottom:10px;">
                        <strong>Target URL Lengkap:</strong><br>
                        <code style="word-break:break-all;" id="txtTargetUrl">-</code>
                    </div>
                    <div style="font-size:12px; margin-bottom:6px;"><strong>Headers Terkirim:</strong></div>
                    <pre class="code-box" id="jsonHeaders">-</pre>
                </div>

                <!-- Technical Notes -->
                <div class="alert-info-box" id="boxNotes" style="display:none;">
                    <i class="fas fa-circle-info" style="font-size:16px; margin-top:1px;"></i>
                    <div id="txtNotes">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; <?php echo date('Y'); ?> IT RSUD Pringsewu &bull; BPJS Apotek Online (Apol) Bridging Health Check Engine
    </div>
</div>

<script>
const rawSecret = '<?php echo addslashes($defSecretKey); ?>';
const rawUserKey = '<?php echo addslashes($defUserKey); ?>';
let secretVisible = false;
let userKeyVisible = false;

function toggleSecret() {
    secretVisible = !secretVisible;
    document.getElementById('txtSecretKey').innerText = secretVisible ? rawSecret : '••••••••••';
    document.getElementById('eyeSecret').className = secretVisible ? 'fas fa-eye-slash' : 'fas fa-eye';
}

function toggleUserKey() {
    userKeyVisible = !userKeyVisible;
    document.getElementById('txtUserKey').innerText = userKeyVisible ? rawUserKey : '••••••••••••••••';
    document.getElementById('eyeUserKey').className = userKeyVisible ? 'fas fa-eye-slash' : 'fas fa-eye';
}

function setEnvironment(env) {
    const inpUrl = document.getElementById('inp_base_url');
    if (env === 'dev') {
        inpUrl.value = 'https://apijkn-dev.bpjs-kesehatan.go.id/apotek-rest-dev';
    } else {
        inpUrl.value = 'https://apijkn.bpjs-kesehatan.go.id/apotek-rest';
    }
    document.getElementById('dispBaseUrl').innerText = inpUrl.value;
    document.querySelectorAll('.btn-env-switch').forEach(b => b.classList.remove('active'));
    if (event && event.target) {
        event.target.closest('.btn-env-switch').classList.add('active');
    }
}

function applyPresetEndpoint(val) {
    if (val !== 'custom') {
        document.getElementById('inp_endpoint').value = val;
    }
}

document.getElementById('inp_method').addEventListener('change', function() {
    const isBodyMethod = (this.value === 'POST' || this.value === 'PUT');
    document.getElementById('grpPayload').style.display = isBodyMethod ? 'block' : 'none';
});

function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    if (event && event.target) {
        event.target.closest('.tab-btn').classList.add('active');
    }
}

function quickTest(endpoint, method) {
    document.getElementById('inp_endpoint').value = endpoint;
    document.getElementById('inp_method').value = method || 'GET';
    document.getElementById('inp_method').dispatchEvent(new Event('change'));
    document.getElementById('formApotekTest').dispatchEvent(new Event('submit'));
}

async function executeTest(e) {
    if (e) e.preventDefault();

    const btn = document.getElementById('btnSubmitTest');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner spinner"></i> Menghubungi BPJS...';

    document.getElementById('resultEmpty').style.display = 'none';
    document.getElementById('resultBox').style.display = 'none';
    document.getElementById('resultLoading').style.display = 'flex';

    const formData = new FormData(document.getElementById('formApotekTest'));

    try {
        const response = await fetch('uji_bridging_apotek.php?action=run_test', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ' ' + response.statusText);
        }

        const data = await response.json();
        renderResult(data);
    } catch (err) {
        renderResult({
            success: false,
            status_badge: 'error',
            status_title: 'Gagal Eksekusi',
            status_message: 'Gagal memproses request: ' + err.message,
            http_code: 0,
            execution_time: 0,
            meta_code: null,
            target_url: document.getElementById('inp_base_url').value + document.getElementById('inp_endpoint').value,
            headers_sent: [],
            raw_response: '',
            decrypted_data: null,
            notes: 'Periksa koneksi jaringan server XAMPP ke internet.'
        });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Jalankan Pengujian Apotek';
        document.getElementById('resultLoading').style.display = 'none';
        document.getElementById('resultBox').style.display = 'block';
    }
}

function renderResult(data) {
    const banner = document.getElementById('bannerStatus');
    banner.className = 'result-banner ' + (data.status_badge || 'error');

    const icon = document.getElementById('iconBanner');
    if (data.status_badge === 'success') {
        icon.className = 'fas fa-circle-check';
    } else if (data.status_badge === 'warning') {
        icon.className = 'fas fa-triangle-exclamation';
    } else {
        icon.className = 'fas fa-circle-xmark';
    }

    document.getElementById('lblStatusTitle').innerText = data.status_title || '-';
    document.getElementById('lblStatusMessage').innerText = data.status_message || '-';

    document.getElementById('pillHttp').innerHTML = `<i class="fas fa-globe"></i> HTTP: ${data.http_code || '-'}`;
    document.getElementById('pillLatency').innerHTML = `<i class="fas fa-gauge"></i> Latency: ${data.execution_time || 0} ms`;
    document.getElementById('pillCode').innerHTML = `<i class="fas fa-tag"></i> Code: ${data.meta_code ?? '-'}`;
    document.getElementById('pillTime').innerHTML = `<i class="fas fa-clock"></i> UTC: ${data.timestamp || '-'}`;

    // Decrypted tab
    const preDecrypted = document.getElementById('jsonDecrypted');
    if (data.decrypted_data) {
        preDecrypted.innerText = typeof data.decrypted_data === 'object'
            ? JSON.stringify(data.decrypted_data, null, 2)
            : data.decrypted_data;
    } else if (data.raw_response) {
        try {
            preDecrypted.innerText = JSON.stringify(JSON.parse(data.raw_response), null, 2);
        } catch (e) {
            preDecrypted.innerText = data.raw_response;
        }
    } else {
        preDecrypted.innerText = 'Tidak ada response body.';
    }

    // Raw tab
    document.getElementById('jsonRaw').innerText = data.raw_response || '(Kosong)';

    // Headers & Target tab
    document.getElementById('txtTargetUrl').innerText = data.target_url || '-';
    document.getElementById('jsonHeaders').innerText = (data.headers_sent && data.headers_sent.length)
        ? data.headers_sent.join('\n')
        : 'Tidak ada headers.';

    // Notes
    const boxNotes = document.getElementById('boxNotes');
    if (data.notes) {
        boxNotes.style.display = 'flex';
        document.getElementById('txtNotes').innerText = data.notes;
    } else {
        boxNotes.style.display = 'none';
    }

    // Auto switch to first tab
    switchTab('tabDecrypted');
}
</script>

</body>
</html>
