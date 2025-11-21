<?php
// Include koneksi dan signature generator
require_once 'koneksi.php';
require_once 'bpjssignature.php';

// Proses pembatalan antrian
$response_data = null;
$error_message = null;
$success_message = null;
$results = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batal_antrian'])) {
    $kodebooking_input = trim($_POST['kodebooking']);
    $keterangan = trim($_POST['keterangan']);
    
    if (!empty($kodebooking_input) && !empty($keterangan)) {
        // Pisahkan kode booking berdasarkan koma atau baris baru
        $kodebooking_array = preg_split('/[,\n\r]+/', $kodebooking_input);
        
        // Bersihkan array
        $kodebooking_list = array();
        foreach ($kodebooking_array as $kb) {
            $kb = trim($kb);
            if (!empty($kb)) {
                $kodebooking_list[] = $kb;
            }
        }
        
        if (!empty($kodebooking_list)) {
            $total = count($kodebooking_list);
            $success_count = 0;
            $failed_count = 0;
            
            foreach ($kodebooking_list as $kodebooking) {
                // Prepare data untuk dikirim
                $data = array(
                    'kodebooking' => $kodebooking,
                    'keterangan' => $keterangan
                );
                
                // Convert to JSON
                $json_data = json_encode($data);
                
                // Get headers (generate baru untuk setiap request)
                $headers = getBPJSHeaders();
                
                // API endpoint untuk batal antrian
                $url = $URLAPIMOBILEJKN . "/antrean/batal";
                
                // Initialize cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                // Execute
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                // Simpan hasil
                if ($response === false) {
                    $results[] = array(
                        'kodebooking' => $kodebooking,
                        'status' => 'error',
                        'message' => 'Curl Error: ' . $curl_error
                    );
                    $failed_count++;
                } else {
                    $response_decode = json_decode($response, true);
                    
                    if ($http_code == 200 && isset($response_decode['metadata']) && $response_decode['metadata']['code'] == 200) {
                        $results[] = array(
                            'kodebooking' => $kodebooking,
                            'status' => 'success',
                            'message' => $response_decode['metadata']['message'] ?? 'Berhasil dibatalkan'
                        );
                        $success_count++;
                    } else {
                        $results[] = array(
                            'kodebooking' => $kodebooking,
                            'status' => 'failed',
                            'message' => isset($response_decode['metadata']['message']) 
                                ? $response_decode['metadata']['message'] 
                                : 'Gagal (HTTP: ' . $http_code . ')'
                        );
                        $failed_count++;
                    }
                }
                
                // Delay sedikit antar request untuk menghindari rate limit
                if (count($kodebooking_list) > 1) {
                    usleep(500000); // 0.5 detik
                }
            }
            
            // Set success/error message
            if ($success_count > 0 && $failed_count == 0) {
                $success_message = "Semua antrian berhasil dibatalkan! Total: " . $success_count . " dari " . $total;
            } elseif ($success_count > 0 && $failed_count > 0) {
                $error_message = "Sebagian berhasil dibatalkan. Berhasil: " . $success_count . ", Gagal: " . $failed_count . " dari " . $total;
            } else {
                $error_message = "Semua pembatalan gagal! Total: " . $failed_count . " dari " . $total;
            }
            
            $response_data = $results;
        } else {
            $error_message = "Kode Booking tidak valid!";
        }
    } else {
        $error_message = "Kode Booking dan Keterangan harus diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batal Antrian BPJS - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #dc3545, #c82333); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .form-group { margin-bottom: 25px; }
        .form-label { display: block; font-weight: bold; color: #333; margin-bottom: 8px; font-size: 14px; }
        .form-input { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; font-family: inherit; }
        .form-input:focus { outline: none; border-color: #dc3545; box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1); }
        textarea.form-input { resize: vertical; min-height: 100px; }
        .btn { padding: 14px 28px; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-danger { background: linear-gradient(45deg, #dc3545, #c82333); color: white; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .alert-error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; }
        .response-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-top: 20px; }
        .response-title { font-weight: bold; color: #333; margin-bottom: 10px; font-size: 16px; }
        .response-content { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 13px; max-height: 400px; overflow-y: auto; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-start; margin-top: 30px; }
        .info-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
        .info-box strong { color: #856404; }
        .info-box ul { margin: 10px 0 0 20px; color: #856404; }
        .example-box { background: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; border-radius: 8px; margin-top: 10px; }
        .example-text { font-family: 'Courier New', monospace; color: #0056b3; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>❌ Batal Antrian BPJS</h1>
            <p>Pembatalan Antrian Pasien - RSUD Pringsewu</p>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="dashboard.php">← Kembali</a>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <strong>✅ Berhasil!</strong> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <strong>❌ Error!</strong> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <strong>ℹ️ Informasi:</strong>
                <ul>
                    <li>Form ini digunakan untuk membatalkan antrian pasien yang sudah terdaftar di BPJS Mobile JKN</li>
                    <li><strong>Bisa membatalkan multiple kode booking sekaligus!</strong> Pisahkan dengan koma atau baris baru</li>
                    <li>Pastikan kode booking yang dimasukkan benar dan valid</li>
                    <li>Keterangan pembatalan akan dikirimkan ke pasien melalui aplikasi Mobile JKN</li>
                </ul>
            </div>
            
            <form method="POST" id="formBatal">
                <div class="form-group">
                    <label class="form-label" for="kodebooking">
                        📋 Kode Booking <span style="color: red;">*</span>
                        <small style="font-weight: normal; color: #6c757d;">(Bisa multiple, pisahkan dengan koma atau baris baru)</small>
                    </label>
                    <textarea 
                        class="form-input" 
                        id="kodebooking" 
                        name="kodebooking" 
                        rows="5"
                        placeholder="Contoh:&#10;20251204000016&#10;20251204000017, 20251204000018&#10;20251204000019"
                        required
                    ><?php echo isset($_POST['kodebooking']) ? htmlspecialchars($_POST['kodebooking']) : ''; ?></textarea>
                    <div class="example-box" style="margin-top: 8px;">
                        <div class="example-text">💡 Format: YYYYMMDDXXXXXX (14 digit) - Bisa masukkan banyak kode booking sekaligus</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="keterangan">
                        📝 Keterangan Pembatalan <span style="color: red;">*</span>
                    </label>
                    <textarea 
                        class="form-input" 
                        id="keterangan" 
                        name="keterangan" 
                        placeholder="Masukkan alasan pembatalan antrian..."
                        required
                    ><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
                    <div class="example-box" style="margin-top: 8px;">
                        <div class="example-text">💡 Contoh: "Mohon maaf, ada pergantian jam praktek dokter. Terima kasih"</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="batal_antrian" class="btn btn-danger">
                        ❌ Batalkan Antrian
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        🔄 Reset Form
                    </button>
                </div>
            </form>
            
            <?php if ($response_data): ?>
                <div class="response-box">
                    <div class="response-title">📊 Hasil Pembatalan:</div>
                    <div style="margin-top: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">No</th>
                                    <th style="padding: 10px; border: 1px solid #dee2e6;">Kode Booking</th>
                                    <th style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">Status</th>
                                    <th style="padding: 10px; border: 1px solid #dee2e6;">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($response_data as $result): 
                                    $badge_color = '';
                                    $badge_text = '';
                                    if ($result['status'] == 'success') {
                                        $badge_color = '#28a745';
                                        $badge_text = '✅ Berhasil';
                                    } elseif ($result['status'] == 'failed') {
                                        $badge_color = '#ffc107';
                                        $badge_text = '⚠️ Gagal';
                                    } else {
                                        $badge_color = '#dc3545';
                                        $badge_text = '❌ Error';
                                    }
                                ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #dee2e6; text-align: center;"><?php echo $no++; ?></td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6; font-family: 'Courier New', monospace; font-weight: bold;">
                                        <?php echo htmlspecialchars($result['kodebooking']); ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">
                                        <span style="background: <?php echo $badge_color; ?>; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                                            <?php echo $badge_text; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;">
                                        <?php echo htmlspecialchars($result['message']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-weight: bold; color: #333; margin-bottom: 15px;">📌 Catatan Penting:</div>
                <ul style="margin-left: 20px; color: #666; line-height: 1.8;">
                    <li>Pembatalan antrian akan mengirimkan notifikasi ke aplikasi Mobile JKN pasien</li>
                    <li>Pastikan keterangan yang diberikan jelas dan informatif</li>
                    <li>Setelah dibatalkan, pasien harus mendaftar ulang jika ingin berobat</li>
                    <li>Proses ini menggunakan API BPJS Mobile JKN secara realtime</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        // Validasi form sebelum submit
        document.getElementById('formBatal').addEventListener('submit', function(e) {
            const kodebooking_input = document.getElementById('kodebooking').value.trim();
            const keterangan = document.getElementById('keterangan').value.trim();
            
            // Split kode booking
            const kodebooking_array = kodebooking_input.split(/[,\n\r]+/).filter(kb => kb.trim() !== '');
            
            // Validasi setiap kode booking
            let invalid_count = 0;
            kodebooking_array.forEach(kb => {
                if (kb.trim().length !== 14) {
                    invalid_count++;
                }
            });
            
            if (invalid_count > 0) {
                e.preventDefault();
                alert('⚠️ Ada ' + invalid_count + ' kode booking yang tidak valid (harus 14 digit)!\n\nSilakan periksa kembali.');
                return false;
            }
            
            if (keterangan.length < 10) {
                e.preventDefault();
                alert('⚠️ Keterangan minimal 10 karakter!');
                return false;
            }
            
            // Konfirmasi sebelum batalkan
            const total = kodebooking_array.length;
            const message = total > 1 
                ? 'Apakah Anda yakin ingin membatalkan ' + total + ' antrian sekaligus?' 
                : 'Apakah Anda yakin ingin membatalkan antrian dengan kode booking: ' + kodebooking_array[0] + '?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading message untuk multiple request
            if (total > 1) {
                alert('⏳ Proses pembatalan ' + total + ' antrian akan dimulai.\n\nMohon tunggu, ini mungkin memakan waktu beberapa detik...');
            }
        });
        
        // Auto-focus ke input pertama
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('kodebooking').focus();
        });
        
        // Counter untuk menghitung jumlah kode booking yang diinput
        document.getElementById('kodebooking').addEventListener('input', function() {
            const kodebooking_array = this.value.split(/[,\n\r]+/).filter(kb => kb.trim() !== '');
            const count = kodebooking_array.length;
            
            if (count > 1) {
                this.style.borderColor = '#28a745';
                this.style.borderWidth = '3px';
            } else {
                this.style.borderColor = '#e9ecef';
                this.style.borderWidth = '2px';
            }
        });
    </script>
</body>
</html>
