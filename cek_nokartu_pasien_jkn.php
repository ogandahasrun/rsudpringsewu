<?php
/**
 * Cek Nomor Kartu Pasien JKN
 * Halaman untuk mengecek data peserta BPJS Kesehatan via VClaim API
 */

// Include file konfigurasi dan signature
require_once 'koneksi.php';
require_once 'bpjssignature.php';

// Inisialisasi variabel
$result = null;
$error = null;
$nokartu = '';
$tanggal = date('Y-m-d'); // Tanggal hari ini

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nokartu'])) {
    $nokartu = trim($_POST['nokartu']);

    // Validasi input
    if (empty($nokartu)) {
        $error = "Nomor kartu tidak boleh kosong!";
    } elseif (!preg_match('/^\d{13}$/', $nokartu)) {
        $error = "Nomor kartu harus 13 digit angka!";
    } else {
        // Buat URL API
        $api_url = $URLVCLAIM . "/Peserta/nokartu/" . $nokartu . "/tglSEP/" . $tanggal;

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
    <title>Cek Nomor Kartu Pasien JKN - RSUD Pringsewu</title>
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
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
        }
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-input:focus {
            outline: none;
            border-color: #28a745;
        }
        .btn {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: #218838; }
        .result-section {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error-section {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #721c24;
        }
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
        .info-box h4 { margin-bottom: 10px; color: #856404; }
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
            <h1>🔍 Cek Nomor Kartu Pasien JKN</h1>
            <p>Verifikasi Data Peserta BPJS Kesehatan - RSUD Pringsewu</p>
        </div>

        <div class="content">
            <div class="info-box">
                <h4>ℹ️ Informasi API</h4>
                <p><strong>Service:</strong> Pencarian data peserta BPJS Kesehatan</p>
                <p><strong>Method:</strong> GET</p>
                <p><strong>Format:</strong> JSON</p>
                <div class="api-info">
URL: <?php echo htmlspecialchars($URLVCLAIM); ?>/Peserta/nokartu/{nomor_kartu}/tglSEP/{tanggal}<br>
Contoh: <?php echo htmlspecialchars($URLVCLAIM); ?>/Peserta/nokartu/0002241716567/tglSEP/<?php echo $tanggal; ?>
                </div>
            </div>

            <div class="form-section">
                <h3>📝 Masukkan Data Pasien</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="nokartu">Nomor Kartu BPJS:</label>
                        <input type="text" id="nokartu" name="nokartu" class="form-input"
                               value="<?php echo htmlspecialchars($nokartu); ?>"
                               placeholder="Masukkan 13 digit nomor kartu (contoh: 0002241716567)"
                               maxlength="13" pattern="\d{13}" required>
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
        // Auto-format nomor kartu input
        document.getElementById('nokartu').addEventListener('input', function(e) {
            // Hanya izinkan angka
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>