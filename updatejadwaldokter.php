<?php
// Include koneksi dan signature generator
require_once 'koneksi.php';
require_once 'bpjssignature.php';

// Proses update jadwal dokter
$response_data = null;
$error_message = null;
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_jadwal'])) {
    $kodepoli = trim($_POST['kodepoli']);
    $kodesubspesialis = $kodepoli; // Sama dengan kodepoli
    $kodedokter = trim($_POST['kodedokter']);
    
    // Ambil data jadwal (bisa multiple)
    $jadwal_data = [];
    
    // Loop untuk mengambil semua jadwal yang diinput
    for ($i = 1; $i <= 7; $i++) {
        if (isset($_POST['hari_' . $i])) {
            // Jika jam buka dan tutup kosong, kirim sebagai jadwal libur
            $buka = isset($_POST['buka_' . $i]) ? trim($_POST['buka_' . $i]) : '';
            $tutup = isset($_POST['tutup_' . $i]) ? trim($_POST['tutup_' . $i]) : '';
            
            $jadwal_data[] = array(
                'hari' => strval($i),
                'buka' => $buka,
                'tutup' => $tutup
            );
        }
    }
    
    if (!empty($kodepoli) && !empty($kodedokter) && !empty($jadwal_data)) {
        // Prepare data untuk dikirim
        $data = array(
            'kodepoli' => $kodepoli,
            'kodesubspesialis' => $kodesubspesialis,
            'kodedokter' => intval($kodedokter),
            'jadwal' => $jadwal_data
        );
        
        // Convert to JSON
        $json_data = json_encode($data);
        
        // Get headers
        $headers = getBPJSHeaders();
        $headers[] = 'Content-Type: application/json';
        
        // API endpoint untuk update jadwal dokter
        $url = $URLAPIMOBILEJKN . "/jadwaldokter/updatejadwaldokter";
        
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
        
        if ($response === false) {
            $error_message = "Curl Error: " . $curl_error;
        } else {
            $response_data = json_decode($response, true);
            
            if ($http_code == 200 && isset($response_data['metadata']) && $response_data['metadata']['code'] == 200) {
                $success_message = "Jadwal dokter berhasil diupdate!";
            } else {
                $error_message = isset($response_data['metadata']['message']) 
                    ? $response_data['metadata']['message'] 
                    : "Gagal update jadwal dokter. HTTP Code: " . $http_code;
            }
        }
    } else {
        $error_message = "Kode Poli, Kode Dokter, dan minimal 1 Jadwal harus diisi!";
    }
}

// Ambil data poli dari database
$poli_list = [];
$q_poli = mysqli_query($koneksi, "SELECT kd_poli_bpjs, nm_poli_bpjs FROM maping_poli_bpjs ORDER BY nm_poli_bpjs ASC");
if ($q_poli) {
    while ($row = mysqli_fetch_assoc($q_poli)) {
        $poli_list[] = $row;
    }
}

// Ambil data dokter dari database
$dokter_list = [];
$q_dokter = mysqli_query($koneksi, "SELECT kd_dokter, kd_dokter_bpjs, nm_dokter_bpjs FROM maping_dokter_dpjpvclaim ORDER BY nm_dokter_bpjs ASC");
if ($q_dokter) {
    while ($row = mysqli_fetch_assoc($q_dokter)) {
        $dokter_list[] = $row;
    }
}

// Array nama hari
$nama_hari = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Jadwal Dokter BPJS - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #007bff, #0056b3); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .form-group { margin-bottom: 25px; }
        .form-label { display: block; font-weight: bold; color: #333; margin-bottom: 8px; font-size: 14px; }
        .form-input, .form-select { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; font-family: inherit; }
        .form-input:focus, .form-select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1); }
        .btn { padding: 14px 28px; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #007bff, #0056b3); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3); }
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
        .jadwal-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
        .jadwal-title { font-weight: bold; color: #333; margin-bottom: 15px; font-size: 16px; }
        .jadwal-item { background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 2px solid #e9ecef; }
        .jadwal-item.active { border-color: #007bff; background: #e3f2fd; }
        .jadwal-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .jadwal-checkbox { width: 20px; height: 20px; cursor: pointer; }
        .jadwal-day { font-weight: bold; color: #007bff; font-size: 15px; }
        .jadwal-time { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px; }
        .time-group { display: flex; flex-direction: column; gap: 5px; }
        .time-label { font-size: 13px; color: #6c757d; font-weight: 600; }
        .time-input { padding: 8px; border: 2px solid #e9ecef; border-radius: 6px; font-size: 14px; }
        .time-input:disabled { background: #e9ecef; cursor: not-allowed; }
        @media (max-width: 768px) {
            .container { margin: 10px; }
            .header h1 { font-size: 1.5em; }
            .content { padding: 20px; }
            .jadwal-time { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Update Jadwal Dokter BPJS</h1>
            <p>Update Jadwal Praktek Dokter ke Aplikasi HFIS BPJS</p>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="bpjs.php">← Kembali</a>
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
                    <li>Pilih kode poli dan dokter yang akan diupdate jadwalnya</li>
                    <li>Centang hari-hari yang ingin diupdate, lalu isi jam buka dan tutup</li>
                    <li><strong>Untuk jadwal LIBUR:</strong> Centang hari saja, kosongkan jam buka dan tutup</li>
                    <li>Anda bisa update 1 hari saja atau beberapa hari sekaligus</li>
                    <li>Format jam harus HH:MM (contoh: 09:00, 14:30)</li>
                    <li>Jadwal akan tersinkronisasi dengan aplikasi HFIS BPJS</li>
                </ul>
            </div>
            
            <form method="POST" id="formJadwal">
                <div class="form-group">
                    <label class="form-label" for="kodepoli">
                        🏥 Kode Poli <span style="color: red;">*</span>
                    </label>
                    <select class="form-select" id="kodepoli" name="kodepoli" required>
                        <option value="">-- Pilih Poli --</option>
                        <?php foreach ($poli_list as $poli): ?>
                            <option value="<?php echo htmlspecialchars($poli['kd_poli_bpjs']); ?>"
                                <?php echo (isset($_POST['kodepoli']) && $_POST['kodepoli'] == $poli['kd_poli_bpjs']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($poli['kd_poli_bpjs'] . ' - ' . $poli['nm_poli_bpjs']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="kodedokter">
                        👨‍⚕️ Kode Dokter BPJS <span style="color: red;">*</span>
                    </label>
                    <select class="form-select" id="kodedokter" name="kodedokter" required>
                        <option value="">-- Pilih Dokter --</option>
                        <?php foreach ($dokter_list as $dokter): ?>
                            <option value="<?php echo htmlspecialchars($dokter['kd_dokter_bpjs']); ?>"
                                <?php echo (isset($_POST['kodedokter']) && $_POST['kodedokter'] == $dokter['kd_dokter_bpjs']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dokter['kd_dokter_bpjs'] . ' - ' . $dokter['nm_dokter_bpjs']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="jadwal-section">
                    <div class="jadwal-title">📋 Jadwal Praktek (Pilih hari yang akan diupdate)</div>
                    
                    <?php foreach ($nama_hari as $num => $hari): ?>
                    <div class="jadwal-item" id="jadwal_<?php echo $num; ?>">
                        <div class="jadwal-header">
                            <input type="checkbox" 
                                   class="jadwal-checkbox" 
                                   id="check_hari_<?php echo $num; ?>" 
                                   name="hari_<?php echo $num; ?>" 
                                   value="1"
                                   onchange="toggleJadwal(<?php echo $num; ?>)">
                            <label for="check_hari_<?php echo $num; ?>" class="jadwal-day">
                                <?php echo $hari; ?> (<?php echo $num; ?>)
                            </label>
                        </div>
                        <div class="jadwal-time">
                            <div class="time-group">
                                <label class="time-label">🕐 Jam Buka</label>
                                <input type="time" 
                                       class="time-input" 
                                       id="buka_<?php echo $num; ?>" 
                                       name="buka_<?php echo $num; ?>"
                                       value="<?php echo isset($_POST['buka_' . $num]) ? $_POST['buka_' . $num] : ''; ?>"
                                       disabled>
                            </div>
                            <div class="time-group">
                                <label class="time-label">🕐 Jam Tutup</label>
                                <input type="time" 
                                       class="time-input" 
                                       id="tutup_<?php echo $num; ?>" 
                                       name="tutup_<?php echo $num; ?>"
                                       value="<?php echo isset($_POST['tutup_' . $num]) ? $_POST['tutup_' . $num] : ''; ?>"
                                       disabled>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_jadwal" class="btn btn-primary">
                        📅 Update Jadwal Dokter
                    </button>
                    <button type="reset" class="btn btn-secondary" onclick="resetAll()">
                        🔄 Reset Form
                    </button>
                </div>
            </form>
            
            <?php if ($response_data): ?>
                <div class="response-box">
                    <div class="response-title">📡 Response dari BPJS:</div>
                    <div class="response-content">
                        <?php echo json_encode($response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-weight: bold; color: #333; margin-bottom: 15px;">📌 Contoh Request JSON:</div>
                <div style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; overflow-x: auto;">
<pre style="margin: 0; color: #f8f8f2;">// Jadwal Praktek Normal:
{
   "kodepoli": "MAT",
   "kodesubspesialis": "MAT",
   "kodedokter": 17496,
   "jadwal": [
      {
         "hari": "2",
         "buka": "09:00",
         "tutup": "11:00"
      },
      {
         "hari": "4",
         "buka": "09:00",
         "tutup": "11:00"
      }
   ]
}

// Jadwal LIBUR (jam kosong):
{
   "kodepoli": "MAT",
   "kodesubspesialis": "MAT",
   "kodedokter": 17496,
   "jadwal": [
      {
         "hari": "1",
         "buka": "",
         "tutup": ""
      },
      {
         "hari": "7",
         "buka": "",
         "tutup": ""
      }
   ]
}</pre>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleJadwal(hari) {
            const checkbox = document.getElementById('check_hari_' + hari);
            const bukaInput = document.getElementById('buka_' + hari);
            const tutupInput = document.getElementById('tutup_' + hari);
            const jadwalItem = document.getElementById('jadwal_' + hari);
            
            if (checkbox.checked) {
                bukaInput.disabled = false;
                tutupInput.disabled = false;
                bukaInput.required = false; // Tidak wajib untuk akomodasi jadwal libur
                tutupInput.required = false; // Tidak wajib untuk akomodasi jadwal libur
                jadwalItem.classList.add('active');
            } else {
                bukaInput.disabled = true;
                tutupInput.disabled = true;
                bukaInput.required = false;
                tutupInput.required = false;
                bukaInput.value = '';
                tutupInput.value = '';
                jadwalItem.classList.remove('active');
            }
        }
        
        function resetAll() {
            // Reset semua checkbox dan input
            for (let i = 1; i <= 7; i++) {
                const checkbox = document.getElementById('check_hari_' + i);
                if (checkbox) {
                    checkbox.checked = false;
                    toggleJadwal(i);
                }
            }
        }
        
        // Validasi form sebelum submit
        document.getElementById('formJadwal').addEventListener('submit', function(e) {
            const kodepoli = document.getElementById('kodepoli').value.trim();
            const kodedokter = document.getElementById('kodedokter').value.trim();
            
            if (!kodepoli || !kodedokter) {
                e.preventDefault();
                alert('⚠️ Kode Poli dan Kode Dokter harus dipilih!');
                return false;
            }
            
            // Cek apakah minimal 1 jadwal dipilih
            let hasJadwal = false;
            for (let i = 1; i <= 7; i++) {
                const checkbox = document.getElementById('check_hari_' + i);
                if (checkbox && checkbox.checked) {
                    const buka = document.getElementById('buka_' + i).value.trim();
                    const tutup = document.getElementById('tutup_' + i).value.trim();
                    
                    // Validasi: jika salah satu diisi, keduanya harus diisi
                    if ((buka && !tutup) || (!buka && tutup)) {
                        e.preventDefault();
                        alert('⚠️ Jika mengisi jam, jam buka dan tutup harus diisi keduanya!\n\nUntuk jadwal LIBUR, kosongkan kedua jam.');
                        return false;
                    }
                    
                    hasJadwal = true;
                }
            }
            
            if (!hasJadwal) {
                e.preventDefault();
                alert('⚠️ Minimal pilih 1 hari untuk diupdate!');
                return false;
            }
            
            // Konfirmasi
            if (!confirm('Apakah Anda yakin ingin mengupdate jadwal dokter ke BPJS?')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Auto-focus
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('kodepoli').focus();
        });
    </script>
</body>
</html>
