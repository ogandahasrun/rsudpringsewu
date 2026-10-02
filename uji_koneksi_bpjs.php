<?php
/**
 * Halaman Uji Koneksi API BPJS (Multi-Service & Manual Tester)
 * Mendukung: VClaim, Antrean Mobile JKN, Aplicare, I-Care JKN, Apotek Online (Apol), dan Auth Mobile JKN
 * Berdasarkan Konfigurasi koneksi.php
 * RSUD Pringsewu
 */

require_once 'koneksi.php';
require_once 'bpjssignature.php';
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// ============================================================================
// 1. AJAX HANDLER: PENGUJIAN PER LAYANAN (SINGLE / BATCH VIA AJAX)
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'test_service') {
    header('Content-Type: application/json; charset=utf-8');
    
    $service = isset($_GET['service']) ? trim($_GET['service']) : '';
    $startTime = microtime(true);
    date_default_timezone_set('UTC');
    $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));

    $res = [
        'service' => $service,
        'service_name' => '',
        'env' => 'UNKNOWN',
        'url' => '',
        'endpoint' => '',
        'target_full' => '',
        'success' => false,
        'http_code' => 0,
        'execution_time' => 0,
        'status_badge' => 'error',
        'status_title' => 'Gagal',
        'status_message' => '',
        'meta_code' => null,
        'meta_message' => null,
        'timestamp' => $tStamp,
        'signature' => '',
        'headers_sent' => [],
        'curl_error' => '',
        'raw_response' => '',
        'decrypted_data' => null
    ];

    switch ($service) {
        // --------------------------------------------------------------------
        // VCLAIM
        // --------------------------------------------------------------------
        case 'vclaim':
            $res['service_name'] = 'BPJS VClaim';
            $url = $URLVCLAIM ?? '';
            $consid = $CONSIDVCLAIM ?? '';
            $secret = $SECRETKEYVCLAIM ?? '';
            $userkey = $USERKEYVCLAIM ?? '';
            $endpoint = '/referensi/diagnosa/A00';
            $method = 'GET';
            $payload = null;
            $needDecrypt = true;
            break;

        // --------------------------------------------------------------------
        // ANTREAN MOBILE JKN
        // --------------------------------------------------------------------
        case 'antrean':
            $res['service_name'] = 'Antrean Mobile JKN';
            $url = $URLAPIMOBILEJKN ?? '';
            $consid = $CONSIDAPIMOBILEJKN ?? '';
            $secret = $SECRETKEYAPIMOBILEJKN ?? '';
            $userkey = $USERKEYAPIMOBILEJKN ?? '';
            $endpoint = '/ref/poli';
            $method = 'GET';
            $payload = null;
            $needDecrypt = true;
            break;

        // --------------------------------------------------------------------
        // APLICARE
        // --------------------------------------------------------------------
        case 'aplicare':
            $res['service_name'] = 'BPJS Aplicare (Kamar)';
            $url = $URLAPLICARE ?? '';
            $consid = $CONSIDAPLICARE ?? '';
            $secret = $SECRETKEYAPLICARE ?? '';
            $userkey = $USERKEYAPLICARE ?? '';
            $endpoint = '/rest/ref/kelas';
            $method = 'GET';
            $payload = null;
            $needDecrypt = false;
            break;

        // --------------------------------------------------------------------
        // I-CARE JKN
        // --------------------------------------------------------------------
        case 'icare':
            $res['service_name'] = 'BPJS I-Care JKN';
            $url = $URLICARE ?? '';
            $consid = $CONSIDICARE ?? '';
            $secret = $SECRETKEYICARE ?? '';
            $userkey = $USERKEYICARE ?? '';
            $endpoint = '/api/rs/validate';
            $method = 'POST';
            $payload = json_encode(['param' => '0000000000000', 'kodedokter' => 0]);
            $needDecrypt = false;
            break;

        // --------------------------------------------------------------------
        // APOTEK ONLINE (APOL)
        // --------------------------------------------------------------------
        case 'apotek':
            $res['service_name'] = 'BPJS Apotek Online (Apol)';
            $url = $URLAPOTEK ?? '';
            $consid = $CONSIDAPOTEK ?? '';
            $secret = $SECRETKEYAPOTEK ?? '';
            $userkey = $USERKEYAPOTEK ?? '';
            $endpoint = '/referensi/dpho';
            $method = 'GET';
            $payload = null;
            $needDecrypt = true;
            break;

        // --------------------------------------------------------------------
        // MOBILE JKN AUTH (Lokal RS)
        // --------------------------------------------------------------------
        case 'auth':
            $res['service_name'] = 'Auth Mobile JKN (Lokal)';
            $url = $URLAUTHMJKN ?? '';
            $authUsername = $USERNAMEAUTHMJKN ?? '';
            $authPassword = $PASSWORDAUTHMJKN ?? '';
            $consid = '';
            $secret = '';
            $userkey = '';
            $endpoint = '';
            $method = 'GET';
            $payload = null;
            $needDecrypt = false;
            break;

        default:
            $res['status_message'] = 'Layanan tidak dikenali: ' . htmlspecialchars($service);
            echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
    }

    $res['url'] = $url;
    $res['endpoint'] = $endpoint;
    $res['env'] = (stripos($url, 'dev') !== false) ? 'DEVELOPMENT' : 'PRODUCTION';

    // Cek kelengkapan konfigurasi
    $isConfigMissing = false;
    if ($service === 'auth') {
        $isConfigMissing = empty($url) || empty($authUsername) || empty($authPassword);
    } else {
        $isConfigMissing = empty($url) || empty($consid) || empty($secret) || empty($userkey);
    }

    if ($isConfigMissing) {
        $res['status_title'] = 'Konfigurasi Belum Lengkap';
        $res['status_message'] = "Variabel konfigurasi untuk {$res['service_name']} di koneksi.php belum diisi lengkap.";
        $res['execution_time'] = round((microtime(true) - $startTime) * 1000);
        echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $targetUrl = rtrim($url, '/') . ($endpoint ? '/' . ltrim($endpoint, '/') : '');
    $res['target_full'] = $targetUrl;

    // Generate Signature dan Headers untuk BPJS
    $headers = [];
    if ($service !== 'auth') {
        $sig = base64_encode(hash_hmac('sha256', $consid . '&' . $tStamp, $secret, true));
        $res['signature'] = $sig;
        $headers = [
            'X-cons-id: ' . $consid,
            'X-timestamp: ' . $tStamp,
            'X-signature: ' . $sig,
            'user_key: ' . $userkey,
            'Content-Type: application/json'
        ];
    } else {
        $headers = [
            'x-username: ' . $authUsername,
            'x-password: ' . $authPassword,
            'Content-Type: application/json'
        ];
    }
    $res['headers_sent'] = $headers;

    // cURL Execution
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
    }

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $res['execution_time'] = round((microtime(true) - $startTime) * 1000);
    curl_close($ch);

    $res['http_code'] = $httpCode;
    $res['curl_error'] = $curlError;
    $res['raw_response'] = $raw;

    if ($curlErrno !== 0) {
        $res['success'] = false;
        $res['status_badge'] = 'error';
        $res['status_title'] = 'Koneksi Gagal (cURL)';
        $res['status_message'] = "Error cURL ({$curlErrno}): {$curlError}";
    } else {
        $json = json_decode($raw, true);
        $meta = $json['metaData'] ?? $json['metadata'] ?? null;

        if ($meta) {
            $mCode = (string)($meta['code'] ?? '');
            $mMsg = $meta['message'] ?? '';
            $res['meta_code'] = $mCode;
            $res['meta_message'] = $mMsg;

            if ($mCode === '200' || $mCode === '1') {
                $res['success'] = true;
                $res['status_badge'] = 'success';
                $res['status_title'] = 'Terhubung & Valid';
                $res['status_message'] = "Sukses! Respon BPJS [{$mCode}]: {$mMsg}";
            } elseif ($service === 'icare' && $httpCode === 200) {
                // I-Care validate merespon 200 OK gateway meski kodedokter/param dummy
                $res['success'] = true;
                $res['status_badge'] = 'success';
                $res['status_title'] = 'Terhubung ke Gateway I-Care';
                $res['status_message'] = "Koneksi gateway I-Care aktif (Respon BPJS [{$mCode}]: {$mMsg})";
            } else {
                $res['success'] = false;
                $res['status_badge'] = 'warning';
                $res['status_title'] = "Respon BPJS [{$mCode}]";
                $res['status_message'] = "BPJS merespon: {$mMsg}";
            }
        } elseif ($httpCode === 200) {
            $res['success'] = true;
            $res['status_badge'] = 'success';
            $res['status_title'] = 'HTTP 200 OK';
            $res['status_message'] = 'Server merespon dengan status HTTP 200 OK.';
        } elseif ($httpCode === 500) {
            $res['success'] = false;
            $res['status_badge'] = 'error';
            $res['status_title'] = 'Server Error (HTTP 500)';
            if (stripos($raw, 'Runtime Error') !== false) {
                $res['status_message'] = 'Server BPJS mengalami Runtime Error / Sedang Maintenance.';
            } else {
                $res['status_message'] = 'Server merespon HTTP 500 Internal Server Error.';
            }
        } elseif ($httpCode === 404) {
            $res['success'] = false;
            $res['status_badge'] = 'error';
            $res['status_title'] = 'Endpoint Tidak Ditemukan (404)';
            $res['status_message'] = 'URL atau endpoint tidak ditemukan di server tujuan.';
        } else {
            $res['success'] = false;
            $res['status_badge'] = 'error';
            $res['status_title'] = "HTTP {$httpCode}";
            $res['status_message'] = "Server mengembalikan status HTTP {$httpCode}.";
        }

        // Dekripsi jika data terenkripsi (VClaim dan Antrean)
        if ($needDecrypt && isset($json['response']) && is_string($json['response'])) {
            try {
                $key = $consid . $secret . $tStamp;
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
                    $res['decrypted_data'] = $aesDecrypted ?: 'Dekripsi AES gagal.';
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

// ============================================================================
// 1b. AJAX HANDLER: UJI KONEKSI DARI BPJS KE SERVER LOKAL
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'test_local_service') {
    header('Content-Type: application/json; charset=utf-8');

    $localService = isset($_GET['service']) ? trim($_GET['service']) : '';
    $startTime    = microtime(true);

    // Ambil konfigurasi dari koneksi.php
    $baseLocalUrl = rtrim($URLAUTHMJKN ?? '', '/'); // mis. https://rsudpringsewu.my.id/auth
    // Hapus '/auth' di akhir untuk mendapatkan base URL (karena endpoint adalah /auth, /ambilantrean, dst)
    $baseWithoutAuth = preg_replace('#/auth$#i', '', $baseLocalUrl);
    $username     = $USERNAMEAUTHMJKN ?? '';
    $password     = $PASSWORDAUTHMJKN ?? '';

    $res = [
        'service'         => $localService,
        'service_name'    => '',
        'success'         => false,
        'http_code'       => 0,
        'execution_time'  => 0,
        'status_badge'    => 'error',
        'status_title'    => 'Gagal',
        'status_message'  => '',
        'meta_code'       => null,
        'meta_message'    => null,
        'raw_response'    => '',
        'target_full'     => '',
        'curl_error'      => '',
        'headers_sent'    => [],
        'payload_sent'    => null,
    ];

    if (empty($baseWithoutAuth) || empty($username) || empty($password)) {
        $res['status_title']   = 'Konfigurasi Belum Lengkap';
        $res['status_message'] = 'URLAUTHMJKN, USERNAMEAUTHMJKN, atau PASSWORDAUTHMJKN di koneksi.php belum diisi.';
        $res['execution_time'] = round((microtime(true) - $startTime) * 1000);
        echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --- Step 1: Dapatkan token terlebih dahulu (kecuali jika service=gettoken itu sendiri) ---
    $token = null;
    if ($localService !== 'gettoken') {
        $chToken = curl_init();
        curl_setopt($chToken, CURLOPT_URL, $baseLocalUrl); // GET ke /auth
        curl_setopt($chToken, CURLOPT_HTTPHEADER, [
            'x-username: ' . $username,
            'x-password: ' . $password,
            'Content-Type: application/json'
        ]);
        curl_setopt($chToken, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chToken, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chToken, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($chToken, CURLOPT_TIMEOUT, 10);
        $rawToken  = curl_exec($chToken);
        $errToken  = curl_errno($chToken);
        curl_close($chToken);

        if ($errToken === 0) {
            $parsedToken = json_decode($rawToken, true);
            $token = $parsedToken['token'] ?? ($parsedToken['data']['token'] ?? null);
            // fallback: beberapa implementasi mengembalikan token di root
            if (empty($token) && isset($parsedToken['response']['token'])) {
                $token = $parsedToken['response']['token'];
            }
        }
    }

    // --- Tentukan endpoint, method, dan payload per service ---
    $method  = 'GET';
    $payload = null;
    $headers = [
        'x-username: ' . $username,
        'Content-Type: application/json'
    ];

    switch ($localService) {
        case 'gettoken':
            $res['service_name'] = 'GetToken (auth)';
            $targetUrl = $baseLocalUrl; // GET /auth
            $method    = 'GET';
            $headers   = [
                'x-username: ' . $username,
                'x-password: ' . $password,
                'Content-Type: application/json'
            ];
            break;

        case 'getantrean':
            $res['service_name'] = 'GetAntrean (ambilantrean)';
            $targetUrl = $baseWithoutAuth . '/ambilantrean';
            $method    = 'POST';
            // Payload dummy - hanya validasi koneksi & auth
            $payload   = json_encode([
                'nomorkartu'    => '0000000000001',
                'nik'           => '0000000000000001',
                'nohp'          => '08123456789',
                'kodepoli'      => 'TEST',
                'kodedokter'    => 'TEST',
                'tanggalperiksa'=> date('Y-m-d', strtotime('+1 day')),
                'jampraktek'    => '08:00-14:00',
                'jeniskunjungan'=> '1',
                'nomorreferensi'=> 'TEST_UJI_' . time(),
            ], JSON_UNESCAPED_UNICODE);
            $headers = [
                'x-username: ' . $username,
                'x-token: ' . ($token ?? ''),
                'Content-Type: application/json'
            ];
            break;

        case 'getstatus':
            $res['service_name'] = 'GetStatus (statusantrean)';
            $targetUrl = $baseWithoutAuth . '/statusantrean';
            $method    = 'POST';
            $payload   = json_encode([
                'kodepoli'      => 'TEST',
                'kodedokter'    => 'TEST',
                'tanggalperiksa'=> date('Y-m-d', strtotime('+1 day')),
                'jampraktek'    => '08:00-14:00',
            ], JSON_UNESCAPED_UNICODE);
            $headers = [
                'x-username: ' . $username,
                'x-token: ' . ($token ?? ''),
                'Content-Type: application/json'
            ];
            break;

        case 'getsisa':
            $res['service_name'] = 'GetSisa (sisaantrean)';
            $targetUrl = $baseWithoutAuth . '/sisaantrean';
            $method    = 'POST';
            $payload   = json_encode([
                'kodebooking' => 'TEST_BOOKING_' . time(),
            ], JSON_UNESCAPED_UNICODE);
            $headers = [
                'x-username: ' . $username,
                'x-token: ' . ($token ?? ''),
                'Content-Type: application/json'
            ];
            break;

        case 'batal':
            $res['service_name'] = 'Batal (batalantrean)';
            $targetUrl = $baseWithoutAuth . '/batalantrean';
            $method    = 'POST';
            $payload   = json_encode([
                'kodebooking' => 'TEST_BOOKING_' . time(),
                'keterangan'  => 'Uji Koneksi',
            ], JSON_UNESCAPED_UNICODE);
            $headers = [
                'x-username: ' . $username,
                'x-token: ' . ($token ?? ''),
                'Content-Type: application/json'
            ];
            break;

        case 'pasienbaru':
            $res['service_name'] = 'PasienBaru';
            $targetUrl = $baseWithoutAuth . '/pasienbaru';
            $method    = 'POST';
            $payload   = json_encode([
                'nomorkartu' => '0000000000001',
                'nik'        => '0000000000000001',
                'nohp'       => '08123456789',
                'nama'       => 'PASIEN UJI KONEKSI',
                'tanggallahir'=> '1990-01-01',
                'jeniskelamin'=> 'L',
                'alamat'     => 'Jl. Uji Koneksi No. 1',
            ], JSON_UNESCAPED_UNICODE);
            $headers = [
                'x-username: ' . $username,
                'x-token: ' . ($token ?? ''),
                'Content-Type: application/json'
            ];
            break;

        case 'checkin':
            $res['service_name'] = 'CheckIn (checkinantrean)';
            $targetUrl = $baseWithoutAuth . '/checkinantrean';
            $method    = 'POST';
            $payload   = json_encode([
                'kodebooking' => 'TEST_BOOKING_' . time(),
                'waktu'       => time() * 1000,
            ], JSON_UNESCAPED_UNICODE);
            $headers = [
                'x-username: ' . $username,
                'x-token: ' . ($token ?? ''),
                'Content-Type: application/json'
            ];
            break;

        default:
            $res['status_message'] = 'Service lokal tidak dikenali: ' . htmlspecialchars($localService);
            echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
    }

    $res['target_full']  = $targetUrl;
    $res['headers_sent'] = $headers;
    $res['payload_sent'] = $payload;

    // --- Jalankan request ---
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
    }

    $raw       = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $res['execution_time'] = round((microtime(true) - $startTime) * 1000);
    curl_close($ch);

    $res['http_code']   = $httpCode;
    $res['curl_error']  = $curlError;
    $res['raw_response']= $raw;

    if ($curlErrno !== 0) {
        $res['success']        = false;
        $res['status_badge']   = 'error';
        $res['status_title']   = 'Koneksi Gagal (cURL)';
        $res['status_message'] = "Error cURL ({$curlErrno}): {$curlError}";
    } else {
        $json = json_decode($raw, true);
        $meta = $json['metadata'] ?? $json['metaData'] ?? null;

        // Khusus gettoken: cek apakah ada token di response
        if ($localService === 'gettoken') {
            $gotToken = $json['token'] ?? ($json['data']['token'] ?? ($json['response']['token'] ?? null));
            if (!empty($gotToken)) {
                $res['success']       = true;
                $res['status_badge']  = 'success';
                $res['status_title']  = 'Token Diterima';
                $res['status_message']= 'Server lokal berhasil mengembalikan token autentikasi.';
                $res['meta_code']     = 200;
                $res['meta_message']  = 'Token OK';
            } elseif ($meta) {
                $mCode = (string)($meta['code'] ?? '');
                $mMsg  = $meta['message'] ?? '';
                $res['meta_code']    = $mCode;
                $res['meta_message'] = $mMsg;
                $res['success']      = false;
                $res['status_badge'] = 'warning';
                $res['status_title'] = "Respon [{$mCode}]";
                $res['status_message']= $mMsg;
            } elseif ($httpCode === 200) {
                $res['success']       = true;
                $res['status_badge']  = 'success';
                $res['status_title']  = 'HTTP 200 OK';
                $res['status_message']= 'Server lokal merespon HTTP 200. Periksa body respon.';
            } else {
                $res['success']       = false;
                $res['status_badge']  = 'error';
                $res['status_title']  = "HTTP {$httpCode}";
                $res['status_message']= 'Server lokal merespon HTTP ' . $httpCode . '.';
            }
        } elseif ($meta) {
            $mCode = (string)($meta['code'] ?? '');
            $mMsg  = $meta['message'] ?? '';
            $res['meta_code']    = $mCode;
            $res['meta_message'] = $mMsg;

            if ($mCode === '200' || $httpCode === 200) {
                $res['success']       = true;
                $res['status_badge']  = 'success';
                $res['status_title']  = 'Server Lokal Merespon';
                $res['status_message']= "OK [{$mCode}]: {$mMsg}";
            } else {
                // Kode selain 200 (mis 201 = validasi gagal) berarti endpoint AKTIF
                // tapi payload dummy ditolak - ini BENAR / koneksi OK
                $res['success']       = true; // koneksi berhasil meski data dummy ditolak
                $res['status_badge']  = 'warning';
                $res['status_title']  = 'Endpoint Aktif (Validasi Gagal)';
                $res['status_message']= "Endpoint merespon [{$mCode}]: {$mMsg} — Payload dummy ditolak (normal).";
            }
        } elseif ($httpCode === 200) {
            $res['success']       = true;
            $res['status_badge']  = 'success';
            $res['status_title']  = 'HTTP 200 OK';
            $res['status_message']= 'Server lokal merespon HTTP 200.';
        } else {
            $res['success']       = false;
            $res['status_badge']  = 'error';
            $res['status_title']  = "HTTP {$httpCode}";
            $res['status_message']= 'Server lokal merespon HTTP ' . $httpCode . '. Pastikan URL lokal benar.';
        }
    }

    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================================
// 2. DATA INSTANSI DARI DATABASE
// ============================================================================
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

// ============================================================================
// 3. HANDLER FORM MANUAL TESTER (POST btn_uji)
// ============================================================================
$manualBaseUrl   = isset($_POST['base_url']) ? trim($_POST['base_url']) : '';
$manualConsid    = isset($_POST['consid']) ? trim($_POST['consid']) : '';
$manualSecretKey = isset($_POST['secret_key']) ? trim($_POST['secret_key']) : '';
$manualUserKey   = isset($_POST['user_key']) ? trim($_POST['user_key']) : '';
$manualEndpoint  = isset($_POST['endpoint']) ? trim($_POST['endpoint']) : '';
$manualHttpMethod = isset($_POST['http_method']) ? trim($_POST['http_method']) : 'GET';
$manualTimeout   = isset($_POST['timeout']) ? (int)$_POST['timeout'] : 15;

$testedManual = false;
$manualResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_uji'])) {
    $testedManual = true;
    $startTime = microtime(true);

    if (empty($manualBaseUrl) || empty($manualConsid) || empty($manualSecretKey) || empty($manualUserKey)) {
        $manualResult = [
            'success' => false,
            'status' => 'error',
            'title' => 'Input Tidak Lengkap',
            'message' => 'Harap isi Base URL, Consumer ID, Secret Key, dan User Key!',
            'http_code' => 0,
            'execution_time' => 0
        ];
    } else {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $signature = base64_encode(hash_hmac('sha256', $manualConsid . '&' . $tStamp, $manualSecretKey, true));

        $headers = [
            'X-cons-id: ' . $manualConsid,
            'X-timestamp: ' . $tStamp,
            'X-signature: ' . $signature,
            'user_key: ' . $manualUserKey,
            'Content-Type: application/json'
        ];

        $targetUrl = rtrim($manualBaseUrl, '/') . '/' . ltrim($manualEndpoint, '/');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $manualTimeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        if ($manualHttpMethod === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
        }

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $totalTime = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);

        $decryptedData = null;
        $metaData = null;
        $isSuccess = false;
        $statusMessage = '';

        if ($curlErrno !== 0) {
            $isSuccess = false;
            $statusMessage = "cURL Error ({$curlErrno}): {$curlError}";
        } else {
            $parsedJson = json_decode($rawResponse, true);
            if (isset($parsedJson['metaData']) || isset($parsedJson['metadata'])) {
                $metaData = $parsedJson['metaData'] ?? $parsedJson['metadata'];
                $metaCode = (string)($metaData['code'] ?? '');
                $metaMsg = $metaData['message'] ?? '';

                if ($metaCode === '200' || $metaCode === '1') {
                    $isSuccess = true;
                    $statusMessage = "Koneksi Berhasil! [{$metaCode}] {$metaMsg}";
                } else {
                    $isSuccess = false;
                    $statusMessage = "Respon BPJS [{$metaCode}]: {$metaMsg}";
                }
            } elseif ($httpCode === 200) {
                $isSuccess = true;
                $statusMessage = "HTTP 200 OK (Respon diterima)";
            } else {
                $isSuccess = false;
                $statusMessage = "HTTP Status: {$httpCode}";
            }

            if (isset($parsedJson['response']) && is_string($parsedJson['response'])) {
                try {
                    $key = $manualConsid . $manualSecretKey . $tStamp;
                    $keyHash = hex2bin(hash('sha256', $key));
                    $iv = substr($keyHash, 0, 16);
                    $aesDecrypted = openssl_decrypt(base64_decode($parsedJson['response']), 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, $iv);

                    if ($aesDecrypted !== false && class_exists('\LZCompressor\LZString')) {
                        $decompressed = \LZCompressor\LZString::decompressFromEncodedURIComponent($aesDecrypted);
                        if (!empty($decompressed)) {
                            $decryptedData = json_decode($decompressed, true) ?? $decompressed;
                        } else {
                            $decryptedData = $aesDecrypted;
                        }
                    }
                } catch (\Exception $e) {
                    $decryptedData = 'Error Dekripsi: ' . $e->getMessage();
                }
            } elseif (isset($parsedJson['response'])) {
                $decryptedData = $parsedJson['response'];
            }
        }

        $manualResult = [
            'success' => $isSuccess,
            'target_url' => $targetUrl,
            'http_code' => $httpCode,
            'execution_time' => $totalTime,
            'timestamp' => $tStamp,
            'signature' => $signature,
            'headers_sent' => $headers,
            'status_message' => $statusMessage,
            'curl_error' => $curlError,
            'meta_data' => $metaData,
            'raw_response' => $rawResponse,
            'decrypted_data' => $decryptedData
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Koneksi BPJS Kesehatan - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --primary-light: #e0f2fe;
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
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            color: var(--gray-800);
            min-height: 100vh;
            padding: 24px 16px;
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
        }

        /* Top Header */
        .page-header {
            background: linear-gradient(135deg, #0284c7 0%, #0f766e 100%);
            color: white;
            border-radius: var(--radius);
            padding: 26px 30px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .page-header::after {
            content: "";
            position: absolute;
            right: -20px;
            bottom: -30px;
            width: 220px;
            height: 220px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            pointer-events: none;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            background: rgba(255,255,255,0.18);
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-back:hover { background: rgba(255,255,255,0.28); }

        .server-time {
            font-size: 12px;
            background: rgba(0,0,0,0.2);
            padding: 6px 12px;
            border-radius: 6px;
            font-family: 'Consolas', monospace;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 12px;
            padding: 6px;
            object-fit: contain;
            box-shadow: var(--shadow-sm);
        }

        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .header-title p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Multi-Service Diagnostic Section */
        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            padding: 24px;
            margin-bottom: 24px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 14px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--gray-200);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .section-title i {
            color: var(--primary);
        }

        .action-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(2,132,199,0.3);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-outline {
            background: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-outline:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }

        /* KPI Counter Bar */
        .kpi-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .kpi-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            padding: 14px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .kpi-card.total .kpi-icon { background: var(--primary-light); color: var(--primary); }
        .kpi-card.success .kpi-icon { background: var(--success-light); color: var(--success); }
        .kpi-card.warning .kpi-icon { background: var(--warning-light); color: var(--warning); }
        .kpi-card.error .kpi-icon { background: var(--danger-light); color: var(--danger); }

        .kpi-info .kpi-val { font-size: 20px; font-weight: 700; }
        .kpi-info .kpi-lbl { font-size: 12px; color: var(--gray-600); }

        /* Grid Cards Layanan */
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
        }

        .service-card {
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            background: #fff;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s;
            position: relative;
        }

        .service-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--gray-300);
        }

        .service-card.status-success { border-left: 5px solid var(--success); }
        .service-card.status-warning { border-left: 5px solid var(--warning); }
        .service-card.status-error { border-left: 5px solid var(--danger); }
        .service-card.status-idle { border-left: 5px solid var(--gray-300); }
        .service-card.status-loading { border-left: 5px solid var(--primary); }

        .service-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .service-title-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .service-avatar {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: var(--gray-100);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .service-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .env-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .env-badge.prod { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .env-badge.dev { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }

        .service-url-box {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            padding: 8px 10px;
            font-family: 'Consolas', monospace;
            font-size: 11px;
            color: var(--gray-600);
            word-break: break-all;
            margin-bottom: 14px;
        }

        .service-status-box {
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 14px;
            min-height: 58px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .service-card.status-idle .service-status-box {
            background: var(--gray-100);
            color: var(--gray-600);
        }
        .service-card.status-loading .service-status-box {
            background: var(--primary-light);
            color: var(--primary-dark);
        }
        .service-card.status-success .service-status-box {
            background: var(--success-light);
            color: #065f46;
        }
        .service-card.status-warning .service-status-box {
            background: var(--warning-light);
            color: #92400e;
        }
        .service-card.status-error .service-status-box {
            background: var(--danger-light);
            color: #991b1b;
        }

        .status-title-row {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 2px;
        }

        .status-desc-row {
            font-size: 12px;
            opacity: 0.9;
        }

        .service-meta-tags {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .meta-pill {
            font-size: 11px;
            font-family: 'Consolas', monospace;
            padding: 2px 7px;
            border-radius: 4px;
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }

        .service-actions {
            display: flex;
            gap: 8px;
            border-top: 1px solid var(--gray-100);
            padding-top: 12px;
            margin-top: auto;
        }

        .btn-card-test {
            flex: 1;
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
            padding: 7px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-card-test:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .btn-card-detail {
            background: transparent;
            border: 1px solid var(--gray-300);
            color: var(--gray-600);
            padding: 7px 10px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-card-detail:hover {
            background: var(--gray-100);
            color: var(--gray-800);
        }

        /* Detail Accordion Panel */
        .detail-panel {
            display: none;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed var(--gray-200);
            font-size: 12px;
        }
        .detail-panel.open { display: block; }

        .json-viewer {
            background: #1e293b;
            color: #f8fafc;
            border-radius: 6px;
            padding: 10px;
            font-family: 'Consolas', monospace;
            font-size: 11px;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
            margin-top: 6px;
        }

        /* Manual Tester Section */
        .collapsible-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
        }

        .form-group-full { grid-column: span 2; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 13px;
            transition: border-color 0.2s;
            background: #fff;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(2,132,199,0.15);
        }
        .monospace { font-family: 'Consolas', monospace; }

        .preset-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .btn-preset {
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-preset:hover, .btn-preset.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* Spinner Animation */
        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Result Section for Manual Test */
        .manual-result-box {
            margin-top: 20px;
            border-radius: var(--radius);
            padding: 20px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
        }
        .manual-result-box.success { border-left: 5px solid var(--success); }
        .manual-result-box.error { border-left: 5px solid var(--danger); }

        .footer {
            text-align: center;
            font-size: 13px;
            color: var(--gray-600);
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Top Header -->
    <div class="page-header">
        <div class="top-nav">
            <a href="bpjs.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Menu BPJS
            </a>
            <a href="uji_bridging_apotek.php" class="btn-back" style="margin-left:8px; border-color:#059669; color:#059669;" title="Buka Tester Khusus Apotek Online">
                <i class="fas fa-prescription-bottle-medical"></i> Konsol Apotek Online (Apol)
            </a>
            <div class="server-time">
                <i class="fas fa-clock"></i> Server UTC: <?php echo gmdate('Y-m-d H:i:s'); ?> | Local: <?php echo date('H:i:s'); ?>
            </div>
        </div>
        <div class="header-content">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo" class="header-logo">
            <div class="header-title">
                <h1>Uji Koneksi Web Service BPJS</h1>
                <p><?php echo htmlspecialchars($nama_instansi); ?></p>
            </div>
        </div>
    </div>

    <!-- 1. MULTI-SERVICE DIAGNOSTIC DASHBOARD -->
    <div class="card">
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-network-wired"></i>
                <span>Status Koneksi Layanan BPJS (Multi-Service)</span>
            </div>
            <div class="action-bar">
                <button type="button" class="btn-primary" id="btnTestAll" onclick="runAllTests()">
                    <i class="fas fa-bolt"></i>
                    <span>Uji Semua Koneksi Sekaligus</span>
                </button>
                <button type="button" class="btn-outline" onclick="resetAllCards()">
                    <i class="fas fa-rotate-left"></i> Reset
                </button>
            </div>
        </div>

        <!-- KPI Metrics -->
        <div class="kpi-bar">
            <div class="kpi-card total">
                <div class="kpi-icon"><i class="fas fa-server"></i></div>
                <div class="kpi-info">
                    <div class="kpi-val" id="kpiTotal">6</div>
                    <div class="kpi-lbl">Total Layanan</div>
                </div>
            </div>
            <div class="kpi-card success">
                <div class="kpi-icon"><i class="fas fa-circle-check"></i></div>
                <div class="kpi-info">
                    <div class="kpi-val" id="kpiSuccess">0</div>
                    <div class="kpi-lbl">Terhubung (Online)</div>
                </div>
            </div>
            <div class="kpi-card warning">
                <div class="kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="kpi-info">
                    <div class="kpi-val" id="kpiWarning">0</div>
                    <div class="kpi-lbl">Respon Khusus</div>
                </div>
            </div>
            <div class="kpi-card error">
                <div class="kpi-icon"><i class="fas fa-circle-xmark"></i></div>
                <div class="kpi-info">
                    <div class="kpi-val" id="kpiError">0</div>
                    <div class="kpi-lbl">Gagal / Error</div>
                </div>
            </div>
        </div>

        <!-- Cards Layanan BPJS -->
        <div class="service-grid">
            
            <!-- 1. VCLAIM -->
            <?php 
                $envVclaim = (isset($URLVCLAIM) && stripos($URLVCLAIM, 'dev') !== false) ? 'dev' : 'prod';
            ?>
            <div class="service-card status-idle" id="card-vclaim">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar"><i class="fas fa-file-medical"></i></div>
                            <div>
                                <div class="service-name">BPJS VClaim</div>
                                <div style="font-size:11px;color:var(--gray-600)">SEP, Rujukan, Peserta</div>
                            </div>
                        </div>
                        <span class="env-badge <?php echo $envVclaim; ?>">
                            <?php echo strtoupper($envVclaim); ?>
                        </span>
                    </div>

                    <div class="service-url-box" title="<?php echo htmlspecialchars($URLVCLAIM ?? '-'); ?>">
                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($URLVCLAIM ?? 'Belum disetting'); ?>
                    </div>

                    <div class="service-status-box">
                        <div class="status-title-row">
                            <i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span>
                        </div>
                        <div class="status-desc-row">Klik tombol "Uji Sekarang" untuk memeriksa koneksi.</div>
                    </div>

                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>

                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" onclick="testSingleService('vclaim')">
                            <i class="fas fa-play"></i> Uji VClaim
                        </button>
                        <button type="button" class="btn-card-detail" onclick="toggleDetail('vclaim')">
                            <i class="fas fa-code"></i> Detail
                        </button>
                    </div>

                    <div class="detail-panel" id="detail-vclaim">
                        <strong>Target Endpoint:</strong> <code>/referensi/diagnosa/A00</code><br>
                        <div style="margin-top:6px;"><strong>Respon / Dekripsi:</strong></div>
                        <pre class="json-viewer" id="json-vclaim">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- 2. ANTREAN MOBILE JKN -->
            <?php 
                $envAntrean = (isset($URLAPIMOBILEJKN) && stripos($URLAPIMOBILEJKN, 'dev') !== false) ? 'dev' : 'prod';
            ?>
            <div class="service-card status-idle" id="card-antrean">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar"><i class="fas fa-users-line"></i></div>
                            <div>
                                <div class="service-name">Antrean Mobile JKN</div>
                                <div style="font-size:11px;color:var(--gray-600)">Antrean RS, Poli, Jadwal</div>
                            </div>
                        </div>
                        <span class="env-badge <?php echo $envAntrean; ?>">
                            <?php echo strtoupper($envAntrean); ?>
                        </span>
                    </div>

                    <div class="service-url-box" title="<?php echo htmlspecialchars($URLAPIMOBILEJKN ?? '-'); ?>">
                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($URLAPIMOBILEJKN ?? 'Belum disetting'); ?>
                    </div>

                    <div class="service-status-box">
                        <div class="status-title-row">
                            <i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span>
                        </div>
                        <div class="status-desc-row">Klik tombol "Uji Sekarang" untuk memeriksa koneksi.</div>
                    </div>

                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>

                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" onclick="testSingleService('antrean')">
                            <i class="fas fa-play"></i> Uji Antrean
                        </button>
                        <button type="button" class="btn-card-detail" onclick="toggleDetail('antrean')">
                            <i class="fas fa-code"></i> Detail
                        </button>
                    </div>

                    <div class="detail-panel" id="detail-antrean">
                        <strong>Target Endpoint:</strong> <code>/ref/poli</code><br>
                        <div style="margin-top:6px;"><strong>Respon / Dekripsi:</strong></div>
                        <pre class="json-viewer" id="json-antrean">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- 3. APLICARE -->
            <?php 
                $envAplicare = (isset($URLAPLICARE) && stripos($URLAPLICARE, 'dev') !== false) ? 'dev' : 'prod';
            ?>
            <div class="service-card status-idle" id="card-aplicare">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar"><i class="fas fa-bed"></i></div>
                            <div>
                                <div class="service-name">BPJS Aplicare</div>
                                <div style="font-size:11px;color:var(--gray-600)">Ketersediaan Kamar / Bed</div>
                            </div>
                        </div>
                        <span class="env-badge <?php echo $envAplicare; ?>">
                            <?php echo strtoupper($envAplicare); ?>
                        </span>
                    </div>

                    <div class="service-url-box" title="<?php echo htmlspecialchars($URLAPLICARE ?? '-'); ?>">
                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($URLAPLICARE ?? 'Belum disetting'); ?>
                    </div>

                    <div class="service-status-box">
                        <div class="status-title-row">
                            <i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span>
                        </div>
                        <div class="status-desc-row">Klik tombol "Uji Sekarang" untuk memeriksa koneksi.</div>
                    </div>

                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>

                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" onclick="testSingleService('aplicare')">
                            <i class="fas fa-play"></i> Uji Aplicare
                        </button>
                        <button type="button" class="btn-card-detail" onclick="toggleDetail('aplicare')">
                            <i class="fas fa-code"></i> Detail
                        </button>
                    </div>

                    <div class="detail-panel" id="detail-aplicare">
                        <strong>Target Endpoint:</strong> <code>/rest/ref/kelas</code><br>
                        <div style="margin-top:6px;"><strong>Respon Data:</strong></div>
                        <pre class="json-viewer" id="json-aplicare">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- 4. I-CARE JKN -->
            <?php 
                $envIcare = (isset($URLICARE) && stripos($URLICARE, 'dev') !== false) ? 'dev' : 'prod';
            ?>
            <div class="service-card status-idle" id="card-icare">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar"><i class="fas fa-laptop-medical"></i></div>
                            <div>
                                <div class="service-name">BPJS I-Care JKN</div>
                                <div style="font-size:11px;color:var(--gray-600)">Riwayat Pelayanan Pasien</div>
                            </div>
                        </div>
                        <span class="env-badge <?php echo $envIcare; ?>">
                            <?php echo strtoupper($envIcare); ?>
                        </span>
                    </div>

                    <div class="service-url-box" title="<?php echo htmlspecialchars($URLICARE ?? '-'); ?>">
                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($URLICARE ?? 'https://apijkn.bpjs-kesehatan.go.id/wsihs'); ?>
                    </div>

                    <div class="service-status-box">
                        <div class="status-title-row">
                            <i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span>
                        </div>
                        <div class="status-desc-row">Klik tombol "Uji Sekarang" untuk memeriksa koneksi.</div>
                    </div>

                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>

                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" onclick="testSingleService('icare')">
                            <i class="fas fa-play"></i> Uji I-Care
                        </button>
                        <button type="button" class="btn-card-detail" onclick="toggleDetail('icare')">
                            <i class="fas fa-code"></i> Detail
                        </button>
                    </div>

                    <div class="detail-panel" id="detail-icare">
                        <strong>Target Endpoint:</strong> <code>/api/rs/validate</code> (POST)<br>
                        <div style="margin-top:6px;"><strong>Respon Data:</strong></div>
                        <pre class="json-viewer" id="json-icare">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- 5. BPJS APOTEK ONLINE (APOL) -->
            <?php 
                $envApotek = (isset($URLAPOTEK) && stripos($URLAPOTEK, 'dev') !== false) ? 'dev' : 'prod';
            ?>
            <div class="service-card status-idle" id="card-apotek">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar" style="color:#059669;background:#ecfdf5;"><i class="fas fa-prescription-bottle-medical"></i></div>
                            <div>
                                <div class="service-name">BPJS Apotek Online (Apol)</div>
                                <div style="font-size:11px;color:var(--gray-600)">
                                    DPHO, Resep & Obat Kronis
                                    <?php if (!empty($KODEIFAPOTEK)): ?>
                                        &bull; IF: <code><?php echo htmlspecialchars($KODEIFAPOTEK); ?></code>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <span class="env-badge <?php echo $envApotek; ?>">
                            <?php echo strtoupper($envApotek); ?>
                        </span>
                    </div>

                    <div class="service-url-box" title="<?php echo htmlspecialchars($URLAPOTEK ?? '-'); ?>">
                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($URLAPOTEK ?? 'Belum disetting'); ?>
                    </div>

                    <div class="service-status-box">
                        <div class="status-title-row">
                            <i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span>
                        </div>
                        <div class="status-desc-row">Klik tombol "Uji Sekarang" untuk memeriksa koneksi Apotek Online.</div>
                    </div>

                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>

                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" onclick="testSingleService('apotek')">
                            <i class="fas fa-play"></i> Uji Apotek
                        </button>
                        <button type="button" class="btn-card-detail" onclick="toggleDetail('apotek')">
                            <i class="fas fa-code"></i> Detail
                        </button>
                        <a href="uji_bridging_apotek.php" class="btn-card-detail" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:5px;" title="Buka Konsol Lengkap Bridging Apotek">
                            <i class="fas fa-arrow-up-right-from-square"></i> Konsol
                        </a>
                    </div>

                    <div class="detail-panel" id="detail-apotek">
                        <strong>Target Endpoint:</strong> <code>/referensi/dpho</code><br>
                        <?php if (!empty($KODEIFAPOTEK)): ?>
                            <span style="font-size:12px;color:var(--gray-600);">Kode IF: <strong><?php echo htmlspecialchars($KODEIFAPOTEK); ?></strong></span><br>
                        <?php endif; ?>
                        <div style="margin-top:6px;"><strong>Respon / Dekripsi:</strong></div>
                        <pre class="json-viewer" id="json-apotek">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- 6. MOBILE JKN AUTH (RS LOCAL) -->
            <div class="service-card status-idle" id="card-auth">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar"><i class="fas fa-shield-halved"></i></div>
                            <div>
                                <div class="service-name">Auth Mobile JKN</div>
                                <div style="font-size:11px;color:var(--gray-600)">Endpoint Token / Auth RS</div>
                            </div>
                        </div>
                        <span class="env-badge prod">LOKAL RS</span>
                    </div>

                    <div class="service-url-box" title="<?php echo htmlspecialchars($URLAUTHMJKN ?? '-'); ?>">
                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($URLAUTHMJKN ?? 'Belum disetting'); ?>
                    </div>

                    <div class="service-status-box">
                        <div class="status-title-row">
                            <i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span>
                        </div>
                        <div class="status-desc-row">Menguji respon endpoint auth token rumah sakit.</div>
                    </div>

                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                    </div>
                </div>

                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" onclick="testSingleService('auth')">
                            <i class="fas fa-play"></i> Uji Auth
                        </button>
                        <button type="button" class="btn-card-detail" onclick="toggleDetail('auth')">
                            <i class="fas fa-code"></i> Detail
                        </button>
                    </div>

                    <div class="detail-panel" id="detail-auth">
                        <strong>Target URL:</strong> <code><?php echo htmlspecialchars($URLAUTHMJKN ?? '-'); ?></code><br>
                        <strong>Username:</strong> <code><?php echo htmlspecialchars($USERNAMEAUTHMJKN ?? '-'); ?></code><br>
                        <div style="margin-top:6px;"><strong>Respon Data:</strong></div>
                        <pre class="json-viewer" id="json-auth">Belum ada data.</pre>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 2b. UJI KONEKSI DARI BPJS KE SERVER LOKAL -->
    <div class="card" style="border-top: 4px solid #7c3aed;">
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-server" style="color:#7c3aed;"></i>
                <span>Uji Koneksi: BPJS → Server Lokal RS</span>
            </div>
            <div class="action-bar">
                <button type="button" class="btn-primary" id="btnTestAllLocal"
                    style="background:#7c3aed; box-shadow:0 2px 4px rgba(124,58,237,0.35);"
                    onclick="runAllLocalTests()">
                    <i class="fas fa-bolt"></i>
                    <span>Uji Semua Endpoint Lokal</span>
                </button>
                <button type="button" class="btn-outline" onclick="resetAllLocalCards()">
                    <i class="fas fa-rotate-left"></i> Reset
                </button>
            </div>
        </div>

        <div style="background:#f5f3ff; border:1px solid #ddd6fe; border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:13px; color:#5b21b6;">
            <i class="fas fa-info-circle"></i>
            Bagian ini mensimulasikan permintaan yang dikirim <strong>BPJS → Server Lokal RS</strong>.
            Menggunakan konfigurasi <code>URLAUTHMJKN</code>, <code>USERNAMEAUTHMJKN</code>, dan <code>PASSWORDAUTHMJKN</code> dari <code>koneksi.php</code>.
            URL Server Lokal: <strong><code><?php echo htmlspecialchars(preg_replace('#/auth$#i', '', rtrim($URLAUTHMJKN ?? 'Belum disetting', '/'))); ?></code></strong>
        </div>

        <!-- KPI Lokal -->
        <div class="kpi-bar" style="margin-bottom:20px;">
            <div class="kpi-card total">
                <div class="kpi-icon" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-server"></i></div>
                <div class="kpi-info">
                    <div class="kpi-val" id="kpiLocalTotal">7</div>
                    <div class="kpi-lbl">Total Endpoint</div>
                </div>
            </div>
            <div class="kpi-card success">
                <div class="kpi-icon"><i class="fas fa-circle-check"></i></div>
                <div class="kpi-info">
                    <div class="kpi-val" id="kpiLocalSuccess">0</div>
                    <div class="kpi-lbl">Aktif / Merespon</div>
                </div>
            </div>
            <div class="kpi-card warning">
                <div class="kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="kpi-info">
                    <div class="kpi-val" id="kpiLocalWarning">0</div>
                    <div class="kpi-lbl">Validasi Gagal (Normal)</div>
                </div>
            </div>
            <div class="kpi-card error">
                <div class="kpi-icon"><i class="fas fa-circle-xmark"></i></div>
                <div class="kpi-info">
                    <div class="kpi-val" id="kpiLocalError">0</div>
                    <div class="kpi-lbl">Gagal / Tidak Terjangkau</div>
                </div>
            </div>
        </div>

        <!-- Grid Endpoint Lokal -->
        <div class="service-grid">

            <!-- GETTOKEN -->
            <div class="service-card status-idle" id="lcard-gettoken">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-key"></i></div>
                            <div>
                                <div class="service-name">GetToken</div>
                                <div style="font-size:11px;color:var(--gray-600)">GET /auth — Autentikasi & ambil token</div>
                            </div>
                        </div>
                        <span class="env-badge prod" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;">LOKAL RS</span>
                    </div>
                    <div class="service-url-box">
                        <i class="fas fa-link"></i>
                        <code><?php echo htmlspecialchars(rtrim($URLAUTHMJKN ?? 'Belum disetting', '/')); ?></code>
                    </div>
                    <div class="service-status-box">
                        <div class="status-title-row"><i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span></div>
                        <div class="status-desc-row">Klik "Uji" untuk menguji endpoint ini.</div>
                    </div>
                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>
                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;" onclick="testLocalService('gettoken')"><i class="fas fa-play"></i> Uji GetToken</button>
                        <button type="button" class="btn-card-detail" onclick="toggleLocalDetail('gettoken')"><i class="fas fa-code"></i> Detail</button>
                    </div>
                    <div class="detail-panel" id="ldetail-gettoken">
                        <strong>Method:</strong> <code>GET</code> &nbsp; <strong>Header:</strong> <code>x-username, x-password</code><br>
                        <div style="margin-top:6px;"><strong>Respon:</strong></div>
                        <pre class="json-viewer" id="ljson-gettoken">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- GETANTREAN -->
            <div class="service-card status-idle" id="lcard-getantrean">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-ticket-simple"></i></div>
                            <div>
                                <div class="service-name">GetAntrean</div>
                                <div style="font-size:11px;color:var(--gray-600)">POST /ambilantrean — Ambil nomor antrean</div>
                            </div>
                        </div>
                        <span class="env-badge prod" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;">LOKAL RS</span>
                    </div>
                    <div class="service-url-box">
                        <i class="fas fa-link"></i>
                        <code><?php echo htmlspecialchars(preg_replace('#/auth$#i', '', rtrim($URLAUTHMJKN ?? '-', '/')) . '/ambilantrean'); ?></code>
                    </div>
                    <div class="service-status-box">
                        <div class="status-title-row"><i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span></div>
                        <div class="status-desc-row">Klik "Uji" untuk menguji endpoint ini.</div>
                    </div>
                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>
                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;" onclick="testLocalService('getantrean')"><i class="fas fa-play"></i> Uji GetAntrean</button>
                        <button type="button" class="btn-card-detail" onclick="toggleLocalDetail('getantrean')"><i class="fas fa-code"></i> Detail</button>
                    </div>
                    <div class="detail-panel" id="ldetail-getantrean">
                        <strong>Method:</strong> <code>POST</code> &nbsp; <strong>Header:</strong> <code>x-username, x-token</code><br>
                        <span style="font-size:11px;color:var(--gray-600);">Payload dummy — validasi koneksi & autentikasi.</span>
                        <div style="margin-top:6px;"><strong>Respon:</strong></div>
                        <pre class="json-viewer" id="ljson-getantrean">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- GETSTATUS -->
            <div class="service-card status-idle" id="lcard-getstatus">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-chart-simple"></i></div>
                            <div>
                                <div class="service-name">GetStatus</div>
                                <div style="font-size:11px;color:var(--gray-600)">POST /statusantrean — Status antrean poli</div>
                            </div>
                        </div>
                        <span class="env-badge prod" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;">LOKAL RS</span>
                    </div>
                    <div class="service-url-box">
                        <i class="fas fa-link"></i>
                        <code><?php echo htmlspecialchars(preg_replace('#/auth$#i', '', rtrim($URLAUTHMJKN ?? '-', '/')) . '/statusantrean'); ?></code>
                    </div>
                    <div class="service-status-box">
                        <div class="status-title-row"><i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span></div>
                        <div class="status-desc-row">Klik "Uji" untuk menguji endpoint ini.</div>
                    </div>
                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>
                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;" onclick="testLocalService('getstatus')"><i class="fas fa-play"></i> Uji GetStatus</button>
                        <button type="button" class="btn-card-detail" onclick="toggleLocalDetail('getstatus')"><i class="fas fa-code"></i> Detail</button>
                    </div>
                    <div class="detail-panel" id="ldetail-getstatus">
                        <strong>Method:</strong> <code>POST</code> &nbsp; <strong>Header:</strong> <code>x-username, x-token</code><br>
                        <span style="font-size:11px;color:var(--gray-600);">Payload dummy — validasi koneksi & autentikasi.</span>
                        <div style="margin-top:6px;"><strong>Respon:</strong></div>
                        <pre class="json-viewer" id="ljson-getstatus">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- GETSISA -->
            <div class="service-card status-idle" id="lcard-getsisa">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-list-ol"></i></div>
                            <div>
                                <div class="service-name">GetSisa</div>
                                <div style="font-size:11px;color:var(--gray-600)">POST /sisaantrean — Sisa & posisi antrean</div>
                            </div>
                        </div>
                        <span class="env-badge prod" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;">LOKAL RS</span>
                    </div>
                    <div class="service-url-box">
                        <i class="fas fa-link"></i>
                        <code><?php echo htmlspecialchars(preg_replace('#/auth$#i', '', rtrim($URLAUTHMJKN ?? '-', '/')) . '/sisaantrean'); ?></code>
                    </div>
                    <div class="service-status-box">
                        <div class="status-title-row"><i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span></div>
                        <div class="status-desc-row">Klik "Uji" untuk menguji endpoint ini.</div>
                    </div>
                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>
                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;" onclick="testLocalService('getsisa')"><i class="fas fa-play"></i> Uji GetSisa</button>
                        <button type="button" class="btn-card-detail" onclick="toggleLocalDetail('getsisa')"><i class="fas fa-code"></i> Detail</button>
                    </div>
                    <div class="detail-panel" id="ldetail-getsisa">
                        <strong>Method:</strong> <code>POST</code> &nbsp; <strong>Header:</strong> <code>x-username, x-token</code><br>
                        <span style="font-size:11px;color:var(--gray-600);">Payload dummy (kode booking tidak ada — validasi aktif).</span>
                        <div style="margin-top:6px;"><strong>Respon:</strong></div>
                        <pre class="json-viewer" id="ljson-getsisa">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- BATAL -->
            <div class="service-card status-idle" id="lcard-batal">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-ban"></i></div>
                            <div>
                                <div class="service-name">Batal</div>
                                <div style="font-size:11px;color:var(--gray-600)">POST /batalantrean — Pembatalan antrean</div>
                            </div>
                        </div>
                        <span class="env-badge prod" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;">LOKAL RS</span>
                    </div>
                    <div class="service-url-box">
                        <i class="fas fa-link"></i>
                        <code><?php echo htmlspecialchars(preg_replace('#/auth$#i', '', rtrim($URLAUTHMJKN ?? '-', '/')) . '/batalantrean'); ?></code>
                    </div>
                    <div class="service-status-box">
                        <div class="status-title-row"><i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span></div>
                        <div class="status-desc-row">Klik "Uji" untuk menguji endpoint ini.</div>
                    </div>
                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>
                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;" onclick="testLocalService('batal')"><i class="fas fa-play"></i> Uji Batal</button>
                        <button type="button" class="btn-card-detail" onclick="toggleLocalDetail('batal')"><i class="fas fa-code"></i> Detail</button>
                    </div>
                    <div class="detail-panel" id="ldetail-batal">
                        <strong>Method:</strong> <code>POST</code> &nbsp; <strong>Header:</strong> <code>x-username, x-token</code><br>
                        <span style="font-size:11px;color:var(--gray-600);">Payload dummy (kode booking tidak ada — validasi aktif).</span>
                        <div style="margin-top:6px;"><strong>Respon:</strong></div>
                        <pre class="json-viewer" id="ljson-batal">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- PASIENBARU -->
            <div class="service-card status-idle" id="lcard-pasienbaru">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-user-plus"></i></div>
                            <div>
                                <div class="service-name">PasienBaru</div>
                                <div style="font-size:11px;color:var(--gray-600)">POST /pasienbaru — Daftarkan pasien baru</div>
                            </div>
                        </div>
                        <span class="env-badge prod" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;">LOKAL RS</span>
                    </div>
                    <div class="service-url-box">
                        <i class="fas fa-link"></i>
                        <code><?php echo htmlspecialchars(preg_replace('#/auth$#i', '', rtrim($URLAUTHMJKN ?? '-', '/')) . '/pasienbaru'); ?></code>
                    </div>
                    <div class="service-status-box">
                        <div class="status-title-row"><i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span></div>
                        <div class="status-desc-row">Klik "Uji" untuk menguji endpoint ini.</div>
                    </div>
                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>
                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;" onclick="testLocalService('pasienbaru')"><i class="fas fa-play"></i> Uji PasienBaru</button>
                        <button type="button" class="btn-card-detail" onclick="toggleLocalDetail('pasienbaru')"><i class="fas fa-code"></i> Detail</button>
                    </div>
                    <div class="detail-panel" id="ldetail-pasienbaru">
                        <strong>Method:</strong> <code>POST</code> &nbsp; <strong>Header:</strong> <code>x-username, x-token</code><br>
                        <span style="font-size:11px;color:var(--gray-600);">Payload dummy — menguji apakah endpoint aktif & autentikasi diterima.</span>
                        <div style="margin-top:6px;"><strong>Respon:</strong></div>
                        <pre class="json-viewer" id="ljson-pasienbaru">Belum ada data.</pre>
                    </div>
                </div>
            </div>

            <!-- CHECKIN -->
            <div class="service-card status-idle" id="lcard-checkin">
                <div>
                    <div class="service-top">
                        <div class="service-title-wrap">
                            <div class="service-avatar" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-circle-check"></i></div>
                            <div>
                                <div class="service-name">CheckIn</div>
                                <div style="font-size:11px;color:var(--gray-600)">POST /checkinantrean — Konfirmasi checkin pasien</div>
                            </div>
                        </div>
                        <span class="env-badge prod" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;">LOKAL RS</span>
                    </div>
                    <div class="service-url-box">
                        <i class="fas fa-link"></i>
                        <code><?php echo htmlspecialchars(preg_replace('#/auth$#i', '', rtrim($URLAUTHMJKN ?? '-', '/')) . '/checkinantrean'); ?></code>
                    </div>
                    <div class="service-status-box">
                        <div class="status-title-row"><i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span></div>
                        <div class="status-desc-row">Klik "Uji" untuk menguji endpoint ini.</div>
                    </div>
                    <div class="service-meta-tags">
                        <span class="meta-pill pill-latency"><i class="fas fa-gauge"></i> Latency: -</span>
                        <span class="meta-pill pill-http"><i class="fas fa-globe"></i> HTTP: -</span>
                        <span class="meta-pill pill-code"><i class="fas fa-tag"></i> Code: -</span>
                    </div>
                </div>
                <div>
                    <div class="service-actions">
                        <button type="button" class="btn-card-test" style="background:#f3e8ff; color:#5b21b6; border-color:#ddd6fe;" onclick="testLocalService('checkin')"><i class="fas fa-play"></i> Uji CheckIn</button>
                        <button type="button" class="btn-card-detail" onclick="toggleLocalDetail('checkin')"><i class="fas fa-code"></i> Detail</button>
                    </div>
                    <div class="detail-panel" id="ldetail-checkin">
                        <strong>Method:</strong> <code>POST</code> &nbsp; <strong>Header:</strong> <code>x-username, x-token</code><br>
                        <span style="font-size:11px;color:var(--gray-600);">Payload dummy (kode booking tidak ada — validasi aktif).</span>
                        <div style="margin-top:6px;"><strong>Respon:</strong></div>
                        <pre class="json-viewer" id="ljson-checkin">Belum ada data.</pre>
                    </div>
                </div>
            </div>

        </div><!-- /service-grid local -->

        <!-- REKAP RESPONSE TIME -->
        <div id="localRekap" style="display:none; margin-top:28px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid #e9d5ff;">
                <div style="font-size:15px; font-weight:700; color:#5b21b6; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-stopwatch" style="color:#7c3aed;"></i>
                    Rekap Response Time Endpoint Lokal RS
                </div>
                <div style="font-size:12px; color:#7c3aed;" id="rekapTimestamp"></div>
            </div>

            <!-- Bar Chart Summary -->
            <div id="rekapBarchart" style="display:flex; flex-direction:column; gap:8px; margin-bottom:20px;"></div>

            <!-- Tabel Detail -->
            <div style="overflow-x:auto;">
                <table id="rekapTable" style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="background:#f3e8ff;">
                            <th style="padding:10px 12px; text-align:left; font-weight:700; color:#5b21b6; border-bottom:2px solid #ddd6fe; white-space:nowrap;"><i class="fas fa-plug"></i> Endpoint</th>
                            <th style="padding:10px 12px; text-align:left; font-weight:700; color:#5b21b6; border-bottom:2px solid #ddd6fe; white-space:nowrap;"><i class="fas fa-link"></i> URL</th>
                            <th style="padding:10px 12px; text-align:center; font-weight:700; color:#5b21b6; border-bottom:2px solid #ddd6fe; white-space:nowrap;"><i class="fas fa-globe"></i> HTTP</th>
                            <th style="padding:10px 12px; text-align:center; font-weight:700; color:#5b21b6; border-bottom:2px solid #ddd6fe; white-space:nowrap;"><i class="fas fa-stopwatch"></i> Response Time</th>
                            <th style="padding:10px 12px; text-align:center; font-weight:700; color:#5b21b6; border-bottom:2px solid #ddd6fe; white-space:nowrap;"><i class="fas fa-check-double"></i> Standar BPJS</th>
                            <th style="padding:10px 12px; text-align:center; font-weight:700; color:#5b21b6; border-bottom:2px solid #ddd6fe; white-space:nowrap;"><i class="fas fa-tag"></i> Meta Code</th>
                            <th style="padding:10px 12px; text-align:left; font-weight:700; color:#5b21b6; border-bottom:2px solid #ddd6fe;"><i class="fas fa-info-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody id="rekapTableBody"></tbody>
                    <tfoot id="rekapTableFoot"></tfoot>
                </table>
            </div>

            <!-- Keterangan -->
            <div style="margin-top:12px; font-size:11px; color:#7c3aed; display:flex; gap:18px; flex-wrap:wrap;">
                <span><span style="display:inline-block;width:10px;height:10px;background:#10b981;border-radius:2px;"></span> Memenuhi Standar BPJS</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#ef4444;border-radius:2px;"></span> Melebihi Standar BPJS</span>
                <span style="margin-left:auto; color:#9ca3af;">*Payload dummy — endpoint aktif jika merespon</span>
            </div>
        </div>
        <!-- /REKAP -->

    </div><!-- /card local -->

    <!-- 2. MANUAL / ADVANCED ENDPOINT TESTER -->
    <div class="card">
        <div class="collapsible-toggle" onclick="toggleManualSection()">
            <div class="section-title">
                <i class="fas fa-terminal"></i>
                <span>Pengujian Endpoint Kustom / Manual Tester</span>
            </div>
            <button type="button" class="btn-outline" id="btnToggleManual">
                <i class="fas fa-chevron-down" id="iconToggleManual"></i> Buka Tester Manual
            </button>
        </div>

        <div id="manualSectionContent" style="display: <?php echo $testedManual ? 'block' : 'none'; ?>; margin-top: 20px;">
            <p style="font-size:13px;color:var(--gray-600);margin-bottom:14px;">
                Gunakan form ini jika Anda ingin menguji endpoint spesifik, kustom Consumer ID, Secret Key, atau payload kustom.
            </p>

            <div class="preset-buttons">
                <span style="font-size:12px;font-weight:600;align-self:center;color:var(--gray-600);">Shortcut Kredensial:</span>
                <button type="button" class="btn-preset active" onclick="loadManualPreset('vclaim')">
                    <i class="fas fa-file-medical"></i> VClaim koneksi.php
                </button>
                <button type="button" class="btn-preset" onclick="loadManualPreset('antrean')">
                    <i class="fas fa-users-line"></i> Antrean koneksi.php
                </button>
                <button type="button" class="btn-preset" onclick="loadManualPreset('aplicare')">
                    <i class="fas fa-bed"></i> Aplicare koneksi.php
                </button>
                <button type="button" class="btn-preset" onclick="loadManualPreset('icare')">
                    <i class="fas fa-laptop-medical"></i> I-Care koneksi.php
                </button>
                <button type="button" class="btn-preset" onclick="loadManualPreset('apotek')">
                    <i class="fas fa-prescription-bottle-medical"></i> Apotek (Apol) koneksi.php
                </button>
            </div>

            <form method="POST" action="" id="formManualUji">
                <div class="form-grid">
                    <div class="form-group-full">
                        <label class="form-label" for="base_url"><i class="fas fa-link"></i> Base URL:</label>
                        <input type="text" id="base_url" name="base_url" class="form-input monospace"
                               value="<?php echo htmlspecialchars($manualBaseUrl); ?>" required
                               placeholder="Contoh: https://apijkn.bpjs-kesehatan.go.id/vclaim-rest (atau klik shortcut di atas)">
                    </div>

                    <div>
                        <label class="form-label" for="consid"><i class="fas fa-id-badge"></i> Consumer ID (consid):</label>
                        <input type="text" id="consid" name="consid" class="form-input monospace"
                               value="<?php echo htmlspecialchars($manualConsid); ?>" required
                               placeholder="Consumer ID dari BPJS...">
                    </div>

                    <div>
                        <label class="form-label" for="secret_key"><i class="fas fa-key"></i> Secret Key (conspwd):</label>
                        <input type="password" id="secret_key" name="secret_key" class="form-input monospace"
                               value="<?php echo htmlspecialchars($manualSecretKey); ?>" required
                               placeholder="Secret Key dari BPJS...">
                    </div>

                    <div class="form-group-full">
                        <label class="form-label" for="user_key"><i class="fas fa-user-shield"></i> User Key:</label>
                        <input type="password" id="user_key" name="user_key" class="form-input monospace"
                               value="<?php echo htmlspecialchars($manualUserKey); ?>" required
                               placeholder="User Key modul BPJS...">
                    </div>

                    <div class="form-group-full">
                        <label class="form-label" for="endpoint"><i class="fas fa-route"></i> Endpoint API:</label>
                        <input type="text" id="endpoint" name="endpoint" class="form-input monospace"
                               value="<?php echo htmlspecialchars($manualEndpoint); ?>" required
                               placeholder="Contoh: /referensi/diagnosa/A00 atau /ref/poli...">
                    </div>

                    <div>
                        <label class="form-label" for="http_method"><i class="fas fa-exchange-alt"></i> HTTP Method:</label>
                        <select id="http_method" name="http_method" class="form-input">
                            <option value="GET" <?php echo $manualHttpMethod === 'GET' ? 'selected' : ''; ?>>GET</option>
                            <option value="POST" <?php echo $manualHttpMethod === 'POST' ? 'selected' : ''; ?>>POST</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="timeout"><i class="fas fa-stopwatch"></i> Timeout (detik):</label>
                        <input type="number" id="timeout" name="timeout" class="form-input" min="5" max="60"
                               value="<?php echo htmlspecialchars($manualTimeout); ?>">
                    </div>
                </div>

                <div style="margin-top: 18px; text-align: right;">
                    <button type="submit" name="btn_uji" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Jalankan Pengujian Kustom
                    </button>
                </div>
            </form>

            <?php if ($testedManual && $manualResult): ?>
                <div class="manual-result-box <?php echo $manualResult['success'] ? 'success' : 'error'; ?>" id="manualResultBox">
                    <h3 style="margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                        <?php if ($manualResult['success']): ?>
                            <i class="fas fa-check-circle" style="color:var(--success);"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle" style="color:var(--danger);"></i>
                        <?php endif; ?>
                        Hasil Pengujian Manual
                    </h3>
                    <p style="margin-bottom:12px;"><strong>Status:</strong> <?php echo htmlspecialchars($manualResult['status_message']); ?></p>
                    <div class="service-meta-tags">
                        <span class="meta-pill"><i class="fas fa-globe"></i> HTTP <?php echo $manualResult['http_code']; ?></span>
                        <span class="meta-pill"><i class="fas fa-stopwatch"></i> <?php echo $manualResult['execution_time']; ?> ms</span>
                        <span class="meta-pill"><i class="fas fa-clock"></i> UTC <?php echo $manualResult['timestamp']; ?></span>
                    </div>

                    <div style="margin-top:14px;">
                        <strong>Target URL Lengkap:</strong><br>
                        <code><?php echo htmlspecialchars($manualResult['target_url']); ?></code>
                    </div>

                    <div style="margin-top:14px;">
                        <strong>Hasil Dekripsi / Respon:</strong>
                        <pre class="json-viewer"><?php 
                            if (!empty($manualResult['decrypted_data'])) {
                                echo htmlspecialchars(is_array($manualResult['decrypted_data']) ? json_encode($manualResult['decrypted_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $manualResult['decrypted_data']);
                            } else {
                                echo htmlspecialchars($manualResult['raw_response']);
                            }
                        ?></pre>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; <?php echo date('Y'); ?> IT RSUD Pringsewu &bull; BPJS Bridging Health Check Engine
    </div>
</div>

<script>
// Preset Data untuk Manual Tester
const PRESETS = {
    vclaim: {
        url: '<?php echo addslashes($URLVCLAIM ?? ""); ?>',
        consid: '<?php echo addslashes($CONSIDVCLAIM ?? ""); ?>',
        secret: '<?php echo addslashes($SECRETKEYVCLAIM ?? ""); ?>',
        userkey: '<?php echo addslashes($USERKEYVCLAIM ?? ""); ?>',
        endpoint: '/referensi/diagnosa/A00',
        method: 'GET'
    },
    antrean: {
        url: '<?php echo addslashes($URLAPIMOBILEJKN ?? ""); ?>',
        consid: '<?php echo addslashes($CONSIDAPIMOBILEJKN ?? ""); ?>',
        secret: '<?php echo addslashes($SECRETKEYAPIMOBILEJKN ?? ""); ?>',
        userkey: '<?php echo addslashes($USERKEYAPIMOBILEJKN ?? ""); ?>',
        endpoint: '/ref/poli',
        method: 'GET'
    },
    aplicare: {
        url: '<?php echo addslashes($URLAPLICARE ?? ""); ?>',
        consid: '<?php echo addslashes($CONSIDAPLICARE ?? ""); ?>',
        secret: '<?php echo addslashes($SECRETKEYAPLICARE ?? ""); ?>',
        userkey: '<?php echo addslashes($USERKEYAPLICARE ?? ""); ?>',
        endpoint: '/rest/ref/kelas',
        method: 'GET'
    },
    icare: {
        url: '<?php echo addslashes($URLICARE ?? ""); ?>',
        consid: '<?php echo addslashes($CONSIDICARE ?? ""); ?>',
        secret: '<?php echo addslashes($SECRETKEYICARE ?? ""); ?>',
        userkey: '<?php echo addslashes($USERKEYICARE ?? ""); ?>',
        endpoint: '/api/rs/validate',
        method: 'POST'
    },
    apotek: {
        url: '<?php echo addslashes($URLAPOTEK ?? ""); ?>',
        consid: '<?php echo addslashes($CONSIDAPOTEK ?? ""); ?>',
        secret: '<?php echo addslashes($SECRETKEYAPOTEK ?? ""); ?>',
        userkey: '<?php echo addslashes($USERKEYAPOTEK ?? ""); ?>',
        endpoint: '/referensi/dpho',
        method: 'GET'
    }
};

function loadManualPreset(key) {
    const data = PRESETS[key];
    if (!data) return;
    document.getElementById('base_url').value = data.url;
    document.getElementById('consid').value = data.consid;
    document.getElementById('secret_key').value = data.secret;
    document.getElementById('user_key').value = data.userkey;
    document.getElementById('endpoint').value = data.endpoint;
    document.getElementById('http_method').value = data.method;

    document.querySelectorAll('.preset-buttons .btn-preset').forEach(b => b.classList.remove('active'));
    if (event && event.target) {
        const btn = event.target.closest('.btn-preset');
        if (btn) btn.classList.add('active');
    }
}

function toggleManualSection() {
    const section = document.getElementById('manualSectionContent');
    const icon = document.getElementById('iconToggleManual');
    const btn = document.getElementById('btnToggleManual');
    if (section.style.display === 'none') {
        section.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
        btn.innerHTML = '<i class="fas fa-chevron-up"></i> Tutup Tester Manual';
    } else {
        section.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
        btn.innerHTML = '<i class="fas fa-chevron-down"></i> Buka Tester Manual';
    }
}

function toggleDetail(service) {
    const p = document.getElementById('detail-' + service);
    if (p) p.classList.toggle('open');
}

// ----------------------------------------------------------------------------
// ASYNCHRONOUS ENGINE DIAGNOSTIK MULTI-SERVICE
// ----------------------------------------------------------------------------
const SERVICES = ['vclaim', 'antrean', 'aplicare', 'icare', 'apotek', 'auth'];
const LOCAL_SERVICES = ['gettoken', 'getantrean', 'getstatus', 'getsisa', 'batal', 'pasienbaru', 'checkin'];
let resultsTracker = {};
let localResultsTracker = {};

function updateKPIs() {
    let success = 0, warning = 0, error = 0;
    Object.values(resultsTracker).forEach(r => {
        if (r.status_badge === 'success') success++;
        else if (r.status_badge === 'warning') warning++;
        else if (r.status_badge === 'error') error++;
    });
    document.getElementById('kpiSuccess').innerText = success;
    document.getElementById('kpiWarning').innerText = warning;
    document.getElementById('kpiError').innerText = error;
}

function resetAllCards() {
    resultsTracker = {};
    SERVICES.forEach(s => {
        const card = document.getElementById('card-' + s);
        if (!card) return;
        card.className = 'service-card status-idle';
        card.querySelector('.status-title-row').innerHTML = '<i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span>';
        card.querySelector('.status-desc-row').innerText = 'Klik tombol "Uji Sekarang" untuk memeriksa koneksi.';
        card.querySelector('.pill-latency').innerHTML = '<i class="fas fa-gauge"></i> Latency: -';
        card.querySelector('.pill-http').innerHTML = '<i class="fas fa-globe"></i> HTTP: -';
        const pillCode = card.querySelector('.pill-code');
        if (pillCode) pillCode.innerHTML = '<i class="fas fa-tag"></i> Code: -';
        const pre = document.getElementById('json-' + s);
        if (pre) pre.innerText = 'Belum ada data.';
    });
    updateKPIs();
}

async function testSingleService(s) {
    const card = document.getElementById('card-' + s);
    if (!card) return;

    // Set status loading
    card.className = 'service-card status-loading';
    card.querySelector('.status-title-row').innerHTML = '<i class="fas fa-spinner spinner"></i> <span class="status-title-text">Menguji Koneksi...</span>';
    card.querySelector('.status-desc-row').innerText = 'Menghubungi server BPJS dan memverifikasi respon...';

    try {
        const response = await fetch('uji_koneksi_bpjs.php?action=test_service&service=' + encodeURIComponent(s));
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ' ' + response.statusText);
        }
        const data = await response.json();
        renderServiceResult(s, data);
    } catch (err) {
        renderServiceResult(s, {
            service: s,
            success: false,
            status_badge: 'error',
            status_title: 'Gagal Request',
            status_message: 'Gagal menghubungi server lokal uji: ' + err.message,
            http_code: 0,
            execution_time: 0,
            meta_code: null
        });
    }
}

function renderServiceResult(s, data) {
    resultsTracker[s] = data;
    const card = document.getElementById('card-' + s);
    if (!card) return;

    // Class card
    card.className = 'service-card status-' + (data.status_badge || 'error');

    // Icon status
    let icon = 'fa-circle-xmark';
    if (data.status_badge === 'success') icon = 'fa-circle-check';
    else if (data.status_badge === 'warning') icon = 'fa-triangle-exclamation';

    card.querySelector('.status-title-row').innerHTML = `<i class="fas ${icon}"></i> <span class="status-title-text">${data.status_title || 'Selesai'}</span>`;
    card.querySelector('.status-desc-row').innerText = data.status_message || '-';

    // Meta tags
    card.querySelector('.pill-latency').innerHTML = `<i class="fas fa-gauge"></i> ${data.execution_time || 0} ms`;
    card.querySelector('.pill-http').innerHTML = `<i class="fas fa-globe"></i> HTTP ${data.http_code || '-'}`;
    
    const pillCode = card.querySelector('.pill-code');
    if (pillCode) {
        pillCode.innerHTML = `<i class="fas fa-tag"></i> Code: ${data.meta_code ?? '-'}`;
    }

    // Detail payload
    const pre = document.getElementById('json-' + s);
    if (pre) {
        let displayContent = '';
        if (data.decrypted_data) {
            displayContent = JSON.stringify(data.decrypted_data, null, 2);
        } else if (data.raw_response) {
            try {
                displayContent = JSON.stringify(JSON.parse(data.raw_response), null, 2);
            } catch (e) {
                displayContent = data.raw_response;
            }
        } else {
            displayContent = 'Tidak ada response body / Error: ' + (data.curl_error || 'Koneksi gagal');
        }
        pre.innerText = displayContent;
    }

    updateKPIs();
}

// ============================================================================
// ENGINE UJI LOKAL (BPJS → SERVER LOKAL RS)
// ============================================================================
function updateLocalKPIs() {
    let success = 0, warning = 0, error = 0;
    Object.values(localResultsTracker).forEach(r => {
        if (r.status_badge === 'success') success++;
        else if (r.status_badge === 'warning') warning++;
        else if (r.status_badge === 'error') error++;
    });
    document.getElementById('kpiLocalSuccess').innerText = success;
    document.getElementById('kpiLocalWarning').innerText = warning;
    document.getElementById('kpiLocalError').innerText = error;
}

function resetAllLocalCards() {
    localResultsTracker = {};
    LOCAL_SERVICES.forEach(s => {
        const card = document.getElementById('lcard-' + s);
        if (!card) return;
        card.className = 'service-card status-idle';
        card.querySelector('.status-title-row').innerHTML = '<i class="fas fa-circle-pause"></i> <span class="status-title-text">Belum Diuji</span>';
        card.querySelector('.status-desc-row').innerText = 'Klik "Uji" untuk menguji endpoint ini.';
        card.querySelector('.pill-latency').innerHTML = '<i class="fas fa-gauge"></i> Latency: -';
        card.querySelector('.pill-http').innerHTML = '<i class="fas fa-globe"></i> HTTP: -';
        const pillCode = card.querySelector('.pill-code');
        if (pillCode) pillCode.innerHTML = '<i class="fas fa-tag"></i> Code: -';
        const pre = document.getElementById('ljson-' + s);
        if (pre) pre.innerText = 'Belum ada data.';
    });
    updateLocalKPIs();
}

function toggleLocalDetail(service) {
    const p = document.getElementById('ldetail-' + service);
    if (p) p.classList.toggle('open');
}

async function testLocalService(s) {
    const card = document.getElementById('lcard-' + s);
    if (!card) return;

    card.className = 'service-card status-loading';
    card.querySelector('.status-title-row').innerHTML = '<i class="fas fa-spinner spinner"></i> <span class="status-title-text">Menguji Endpoint...</span>';
    card.querySelector('.status-desc-row').innerText = 'Menghubungi server lokal RS dan memverifikasi respon...';

    try {
        const response = await fetch('uji_koneksi_bpjs.php?action=test_local_service&service=' + encodeURIComponent(s));
        if (!response.ok) throw new Error('HTTP ' + response.status + ' ' + response.statusText);
        const data = await response.json();
        renderLocalResult(s, data);
    } catch (err) {
        renderLocalResult(s, {
            service: s,
            success: false,
            status_badge: 'error',
            status_title: 'Gagal Request',
            status_message: 'Gagal menghubungi server lokal: ' + err.message,
            http_code: 0,
            execution_time: 0,
            meta_code: null
        });
    }
}

function renderLocalResult(s, data) {
    localResultsTracker[s] = data;
    const card = document.getElementById('lcard-' + s);
    if (!card) return;

    card.className = 'service-card status-' + (data.status_badge || 'error');

    let icon = 'fa-circle-xmark';
    if (data.status_badge === 'success') icon = 'fa-circle-check';
    else if (data.status_badge === 'warning') icon = 'fa-triangle-exclamation';

    card.querySelector('.status-title-row').innerHTML = `<i class="fas ${icon}"></i> <span class="status-title-text">${data.status_title || 'Selesai'}</span>`;
    card.querySelector('.status-desc-row').innerText = data.status_message || '-';
    card.querySelector('.pill-latency').innerHTML = `<i class="fas fa-gauge"></i> ${data.execution_time || 0} ms`;
    card.querySelector('.pill-http').innerHTML = `<i class="fas fa-globe"></i> HTTP ${data.http_code || '-'}`;
    const pillCode = card.querySelector('.pill-code');
    if (pillCode) pillCode.innerHTML = `<i class="fas fa-tag"></i> Code: ${data.meta_code ?? '-'}`;

    const pre = document.getElementById('ljson-' + s);
    if (pre) {
        try {
            pre.innerText = JSON.stringify(JSON.parse(data.raw_response), null, 2);
        } catch (e) {
            pre.innerText = data.raw_response || ('Error: ' + (data.curl_error || 'Koneksi gagal'));
        }
    }
    updateLocalKPIs();
}

async function runAllLocalTests() {
    const btn = document.getElementById('btnTestAllLocal');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner spinner"></i> Sedang Menguji Semua Endpoint Lokal...';
    const promises = LOCAL_SERVICES.map(s => testLocalService(s));
    await Promise.all(promises);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-bolt"></i> Uji Semua Endpoint Lokal';
    buildRekapTable();
}

async function runAllTests() {
    const btn = document.getElementById('btnTestAll');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner spinner"></i> Sedang Menguji Seluruh Layanan...';

    // Jalankan satu per satu atau bersamaan
    const promises = SERVICES.map(s => testSingleService(s));
    await Promise.all(promises);

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-bolt"></i> Uji Semua Koneksi Sekaligus';
}

// ============================================================================
// REKAP RESPONSE TIME LOKAL
// ============================================================================
const LOCAL_LABELS = {
    gettoken:   { name: 'GetToken',    url: '...auth',             icon: 'fa-key', std: 2000 },
    getantrean: { name: 'GetAntrean',  url: '.../ambilantrean',    icon: 'fa-ticket-simple', std: 10000 },
    getstatus:  { name: 'GetStatus',   url: '.../statusantrean',   icon: 'fa-chart-simple', std: 2000 },
    getsisa:    { name: 'GetSisa',     url: '.../sisaantrean',     icon: 'fa-list-ol', std: 2000 },
    batal:      { name: 'Batal',       url: '.../batalantrean',    icon: 'fa-ban', std: 5000 },
    pasienbaru: { name: 'PasienBaru',  url: '.../pasienbaru',      icon: 'fa-user-plus', std: 5000 },
    checkin:    { name: 'CheckIn',     url: '.../checkinantrean',  icon: 'fa-circle-check', std: 5000 }
};

function rtColor(ms, std = 2000) {
    if (ms === null || ms === undefined) return '#94a3b8';
    if (ms <= std) return '#10b981'; // Memenuhi Standar
    return '#ef4444'; // Melebihi Standar
}

function rtLabel(ms, std = 2000) {
    if (ms === null || ms === undefined) return '-';
    if (ms <= std) return 'Sesuai Standar';
    return 'Melebihi Standar';
}

function badgeHtml(data) {
    if (!data) return '<span style="color:#94a3b8;">—</span>';
    const colors = {
        success: { bg: '#d1fae5', color: '#065f46', icon: 'fa-circle-check' },
        warning: { bg: '#fef3c7', color: '#92400e', icon: 'fa-triangle-exclamation' },
        error:   { bg: '#fee2e2', color: '#991b1b', icon: 'fa-circle-xmark' }
    };
    const c = colors[data.status_badge] || colors.error;
    return `<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:6px;background:${c.bg};color:${c.color};font-weight:600;font-size:12px;">
        <i class="fas ${c.icon}"></i> ${data.status_title || data.status_badge}
    </span>`;
}

function buildRekapTable() {
    const tbody = document.getElementById('rekapTableBody');
    const tfoot = document.getElementById('rekapTableFoot');
    const barchart = document.getElementById('rekapBarchart');
    const rekap = document.getElementById('localRekap');
    const tsEl = document.getElementById('rekapTimestamp');

    if (!tbody) return;

    // Tampilkan section
    rekap.style.display = 'block';
    rekap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    tsEl.textContent = 'Diperbarui: ' + new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'});

    const times = LOCAL_SERVICES.map(s => localResultsTracker[s]?.execution_time ?? null);
    const validTimes = times.filter(t => t !== null);
    const maxTime = validTimes.length ? Math.max(...validTimes) : 1000;

    // --- Bar chart ---
    barchart.innerHTML = '';
    LOCAL_SERVICES.forEach((s, i) => {
        const d = localResultsTracker[s];
        const ms = d?.execution_time ?? null;
        const pct = ms !== null ? Math.max(4, Math.round((ms / Math.max(maxTime, 1)) * 100)) : 4;
        const lbl = LOCAL_LABELS[s];
        const color = rtColor(ms, lbl.std);
        const statusIcon = d ? (d.status_badge === 'success' ? '✓' : d.status_badge === 'warning' ? '⚠' : '✕') : '–';
        barchart.innerHTML += `
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:110px; font-size:12px; font-weight:600; color:#5b21b6; text-align:right; white-space:nowrap;">
                <i class="fas ${lbl.icon}" style="margin-right:4px;"></i>${lbl.name}
            </div>
            <div style="flex:1; background:#ede9fe; border-radius:6px; height:22px; position:relative; overflow:hidden;">
                <div style="width:${pct}%; background:${color}; height:100%; border-radius:6px; transition:width 0.6s ease; display:flex; align-items:center; justify-content:flex-end; padding-right:6px;">
                    ${ms !== null ? `<span style="color:white;font-size:10px;font-weight:700;white-space:nowrap;">${ms} ms</span>` : ''}
                </div>
                ${ms === null ? '<span style="position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:11px;color:#9ca3af;">Belum diuji</span>' : ''}
            </div>
            <div style="width:130px; font-size:11px; font-weight:600; color:${color}; text-align:left;">${ms !== null ? rtLabel(ms, lbl.std) + ' (&lt;' + (lbl.std/1000) + 's)' : '—'}</div>
            <div style="width:20px; font-size:13px; text-align:center;">${d ? (d.status_badge==='success'?'<i class="fas fa-circle-check" style="color:#10b981;"></i>':d.status_badge==='warning'?'<i class="fas fa-triangle-exclamation" style="color:#f59e0b;"></i>':'<i class="fas fa-circle-xmark" style="color:#ef4444;"></i>') : '<i class="fas fa-circle-pause" style="color:#94a3b8;"></i>'}</div>
        </div>`;
    });

    // --- Tabel ---
    tbody.innerHTML = '';
    LOCAL_SERVICES.forEach((s, i) => {
        const d = localResultsTracker[s];
        const ms = d?.execution_time ?? null;
        const lbl = LOCAL_LABELS[s];
        const color = rtColor(ms, lbl.std);
        const rowBg = i % 2 === 0 ? '#faf5ff' : '#ffffff';
        tbody.innerHTML += `<tr style="background:${rowBg}; transition: background 0.2s;" onmouseover="this.style.background='#f3e8ff'" onmouseout="this.style.background='${rowBg}'">
            <td style="padding:10px 12px; border-bottom:1px solid #ede9fe; white-space:nowrap; font-weight:600; color:#5b21b6;">
                <i class="fas ${lbl.icon}" style="margin-right:6px; opacity:0.8;"></i>${lbl.name}
            </td>
            <td style="padding:10px 12px; border-bottom:1px solid #ede9fe; font-family:monospace; font-size:11px; color:#6b7280;">${d?.target_full ? d.target_full : lbl.url}</td>
            <td style="padding:10px 12px; border-bottom:1px solid #ede9fe; text-align:center;">
                ${d ? `<span style="font-weight:700; color:${d.http_code===200?'#065f46':d.http_code===0?'#991b1b':'#92400e'}">${d.http_code || '–'}</span>` : '<span style="color:#94a3b8;">–</span>'}
            </td>
            <td style="padding:10px 12px; border-bottom:1px solid #ede9fe; text-align:center; white-space:nowrap;">
                ${ms !== null
                    ? `<span style="font-weight:800; font-size:15px; color:${color};">${ms}</span> <span style="font-size:11px;color:#9ca3af;">ms</span>
                       <div style="height:4px;background:#ede9fe;border-radius:2px;margin-top:4px;">
                           <div style="height:4px;background:${color};border-radius:2px;width:${Math.max(4,Math.round((ms/Math.max(maxTime,1))*100))}%;"></div>
                       </div>`
                    : '<span style="color:#94a3b8;">–</span>'
                }
            </td>
            <td style="padding:10px 12px; border-bottom:1px solid #ede9fe; text-align:center; font-weight:600; color:#4b5563; font-size:12px;">
                &lt; ${lbl.std/1000}s
            </td>
            <td style="padding:10px 12px; border-bottom:1px solid #ede9fe; text-align:center;">
                ${d?.meta_code !== null && d?.meta_code !== undefined
                    ? `<span style="font-family:monospace; font-weight:600; color:#5b21b6;">${d.meta_code}</span>`
                    : '<span style="color:#94a3b8;">–</span>'
                }
            </td>
            <td style="padding:10px 12px; border-bottom:1px solid #ede9fe;">${badgeHtml(d)}</td>
        </tr>`;
    });

    // --- Footer Statistik ---
    const tested = validTimes.length;
    const avg = tested ? Math.round(validTimes.reduce((a,b)=>a+b,0)/tested) : null;
    const minT = tested ? Math.min(...validTimes) : null;
    const maxT = tested ? Math.max(...validTimes) : null;
    const totalT = tested ? validTimes.reduce((a,b)=>a+b,0) : null;

    tfoot.innerHTML = `<tr style="background:#f3e8ff; font-weight:700;">
        <td colspan="3" style="padding:10px 12px; color:#5b21b6; font-size:13px;">
            <i class="fas fa-calculator"></i> Statistik (${tested}/${LOCAL_SERVICES.length} diuji)
        </td>
        <td style="padding:10px 12px; text-align:center;">
            ${avg !== null
                ? `<div style="color:${rtColor(avg)}; font-size:15px;">${avg} <span style="font-size:10px;">ms avg</span></div>
                   <div style="font-size:11px; color:#6b7280;">Min: ${minT}ms &bull; Max: ${maxT}ms</div>
                   <div style="font-size:11px; color:#6b7280;">Total: ${totalT}ms</div>`
                : '<span style="color:#94a3b8;">–</span>'
            }
        </td>
        <td colspan="2" style="padding:10px 12px; color:#5b21b6; font-size:12px;">
            ${tested === LOCAL_SERVICES.length
                ? `<i class="fas fa-circle-check" style="color:#10b981;"></i> Semua endpoint telah diuji`
                : `<i class="fas fa-triangle-exclamation" style="color:#f59e0b;"></i> ${LOCAL_SERVICES.length - tested} endpoint belum diuji`
            }
        </td>
    </tr>`;
}

// Perbarui rekap otomatis setiap kali 1 endpoint selesai
const _origRenderLocalResult = renderLocalResult;
renderLocalResult = function(s, data) {
    _origRenderLocalResult(s, data);
    // Update rekap jika sudah ada setidaknya 1 hasil
    if (Object.keys(localResultsTracker).length > 0) {
        buildRekapTable();
    }
};

// Reset rekap saat reset cards
const _origResetAllLocalCards = resetAllLocalCards;
function resetAllLocalCards() {
    _origResetAllLocalCards();
    const rekap = document.getElementById('localRekap');
    if (rekap) rekap.style.display = 'none';
}

// Auto scroll ke hasil jika pengujian manual selesai
<?php if ($testedManual): ?>
window.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('manualResultBox');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>
</script>

</body>
</html>
