<?php
require_once 'bpjssignature.php';

// Inisialisasi variabel
$result = null;
$error = null;
$nik = '';
$tanggal = date('Y-m-d'); // Tanggal hari ini

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nik'])) {
    $nik = trim($_POST['nik']);

    // Validasi input
    if (empty($nik)) {
        $error = "NIK tidak boleh kosong!";
    } elseif (!preg_match('/^\d{16}$/', $nik)) {
        $error = "NIK harus 16 digit angka!";
    } else {
        // Buat URL API
        $api_url = $URLVCLAIM . "/Peserta/nik/" . $nik . "/tglSEP/" . $tanggal;

        // Get signature dan headers untuk VClaim
        $signature = generateVClaimSignature();
        $headers = getVClaimHeaders();

        // Inisialisasi cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Untuk development, hapus di production
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Untuk development, hapus di production

        // Eksekusi request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // Proses response
        if ($curl_error) {
            $error = "Error cURL: " . $curl_error;
        } elseif ($http_code !== 200) {
            $error = "HTTP Error: " . $http_code;
            if ($response) {
                $error .= " - Response: " . $response;
            }
        } else {
            // Decode JSON response
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = "Error parsing JSON response: " . json_last_error_msg();
                $result = $response; // Tampilkan raw response jika JSON error
            } else {
                // Dekripsi response jika ada
                if (isset($result['response']) && isset($result['metaData'])) {
                    // Dekripsi response menggunakan AES + LZString decompress
                    $decrypted = decryptVClaimResponse($result['response'], $signature['x-timestamp']);

                    if (empty($decrypted)) {
                        $error = "Gagal mendekripsi response. Mungkin konfigurasi secret key salah.";
                        $result['raw_encrypted_response'] = $result['response'];
                    } else {
                        // Decode JSON dari hasil dekripsi
                        $decryptedData = json_decode($decrypted, true);

                        if (json_last_error() === JSON_ERROR_NONE) {
                            $result['response'] = $decryptedData;
                        } else {
                            $error = "Gagal parsing JSON dari hasil dekripsi: " . json_last_error_msg() . " | Decrypted: " . substr($decrypted, 0, 200) . "...";
                            $result['decrypted_raw'] = $decrypted;
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek NIK Pasien JKN - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .form-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            outline: none;
            transition: border-color 0.2s;
            font-size: 16px;
        }
        .form-input:focus { border-color: #5a67d8; }
        .btn {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 0;
            border-radius: 8px;
            color: white;
            background: #28a745;
            font-weight: 700;
            letter-spacing: 0.02em;
            box-shadow: 0 8px 18px rgba(40, 167, 69, 0.3);
            margin-top: 10px;
            cursor: pointer;
            transition: transform 0.15s, background 0.3s;
        }
        .btn:hover { background: #218838; transform: translateY(-1px); }
        .result-section, .error-section {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .result-section { background: #e7f3ff; border-left: 4px solid #007bff; }
        .error-section { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .json-output {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 400px;
            overflow-y: auto;
        }
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .api-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔎 Cek NIK Pasien JKN</h1>
            <p>Verifikasi Data Peserta BPJS Kesehatan berdasarkan NIK</p>
        </div>

        <div class="content">
            <div class="info-box">
                <h4>ℹ️ Informasi API</h4>
                <p><strong>Service:</strong> Pencarian data peserta BPJS Kesehatan</p>
                <p><strong>Method:</strong> GET</p>
                <p><strong>Format:</strong> JSON</p>
                <div class="api-info">
URL: <?php echo htmlspecialchars($URLVCLAIM); ?>/Peserta/nik/{nik}/tglSEP/{tanggal}<br>
Contoh: <?php echo htmlspecialchars($URLVCLAIM); ?>/Peserta/nik/1806024506480003/tglSEP/<?php echo $tanggal; ?>
                </div>
            </div>

            <div class="form-section">
                <h3>📝 Masukkan Data Pasien</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="nik">NIK BPJS:</label>
                        <input type="text" id="nik" name="nik" class="form-input"
                               value="<?php echo htmlspecialchars($nik); ?>"
                               placeholder="Masukkan 16 digit NIK (contoh: 1806024506480003)"
                               maxlength="16" pattern="\d{16}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tanggal">Tanggal Pelayanan:</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-input"
                               value="<?php echo htmlspecialchars($tanggal); ?>" required>
                    </div>

                    <button type="submit" class="btn">🔍 Cek Data Pasien</button>
                </form>
            </div>

            <?php if ($error): ?>
                <div class="error-section">
                    <h4>❌ Error</h4>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($result): ?>
                <div class="result-section">
                    <h4>✅ Hasil Pencarian</h4>
                    <?php if (isset($result['response']) && is_array($result['response'])): ?>
                        <p><strong>Status Dekripsi:</strong> ✅ Berhasil didekripsi</p>
                    <?php elseif (isset($result['raw_encrypted_response'])): ?>
                        <p><strong>Status Dekripsi:</strong> ❌ Gagal didekripsi - menampilkan data terenkripsi</p>
                    <?php endif; ?>
                    <div class="json-output"><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('nik').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>
