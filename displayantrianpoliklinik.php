<?php
include 'koneksi.php';

// Ambil parameter poli dan dokter dari URL untuk filter display
$filter_poli = isset($_GET['poli']) ? $_GET['poli'] : '';
$filter_dokter = isset($_GET['dokter']) ? $_GET['dokter'] : '';

// Handle AJAX request untuk get antrian panggilan
if (isset($_GET['action']) && $_GET['action'] === 'get_panggilan') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Query dengan filter berdasarkan poli dan dokter jika ada
    $sql = "SELECT 
                ap.nama_pasien, 
                ap.no_reg, 
                ap.no_rawat,
                ap.waktu_panggil,
                ap.kalimat_panggil,
                p.nm_poli,
                d.nm_dokter
            FROM antrian_panggilan_poliklinik ap
            LEFT JOIN reg_periksa rp ON ap.no_rawat = rp.no_rawat
            LEFT JOIN poliklinik p ON rp.kd_poli = p.kd_poli
            LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            WHERE ap.status_tampil = 'belum'";
    
    $params = [];
    $types = "";
    
    if ($filter_poli !== '') {
        $sql .= " AND p.nm_poli = ?";
        $params[] = $filter_poli;
        $types .= "s";
    }
    
    if ($filter_dokter !== '') {
        $sql .= " AND d.nm_dokter = ?";
        $params[] = $filter_dokter;
        $types .= "s";
    }
    
    $sql .= " ORDER BY ap.waktu_panggil DESC LIMIT 1";
    
    if (!empty($params)) {
        $stmt = $koneksi->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = false;
        }
    } else {
        $result = mysqli_query($koneksi, $sql);
    }
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        // Update status menjadi sudah tampil
        $update_sql = "UPDATE antrian_panggilan_poliklinik 
                       SET status_tampil = 'sudah' 
                       WHERE nama_pasien = ? AND no_reg = ? AND waktu_panggil = ?";
        $stmt = $koneksi->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("sss", $row['nama_pasien'], $row['no_reg'], $row['waktu_panggil']);
            $stmt->execute();
            $stmt->close();
        }
        
        echo json_encode([
            'success' => true,
            'data' => $row
        ]);
    } else {
        // Debug: cek apakah ada data di table
        $debug_sql = "SELECT COUNT(*) as total FROM antrian_panggilan_poliklinik";
        $debug_result = mysqli_query($koneksi, $debug_sql);
        $debug_row = mysqli_fetch_assoc($debug_result);
        
        echo json_encode([
            'success' => false,
            'message' => 'Tidak ada panggilan baru',
            'debug' => [
                'total_records' => $debug_row['total'],
                'filter_poli' => $filter_poli,
                'filter_dokter' => $filter_dokter,
                'query' => $sql
            ]
        ]);
    }
    
    mysqli_close($koneksi);
    exit;
}

// Ambil nama organisasi dari database
$nama_organisasi = '';
$org_query = "SELECT nama_instansi FROM setting LIMIT 1";
$org_result = mysqli_query($koneksi, $org_query);
if ($org_result && mysqli_num_rows($org_result) > 0) {
    $org_row = mysqli_fetch_assoc($org_result);
    $nama_organisasi = $org_row['nama_instansi'];
}

// Tentukan judul display berdasarkan filter
$display_title = "DISPLAY ANTRIAN POLIKLINIK";
if ($filter_poli !== '' && $filter_dokter !== '') {
    $display_title = "DISPLAY ANTRIAN " . strtoupper($filter_poli) . " - Dr. " . strtoupper($filter_dokter);
} elseif ($filter_poli !== '') {
    $display_title = "DISPLAY ANTRIAN " . strtoupper($filter_poli);
} elseif ($filter_dokter !== '') {
    $display_title = "DISPLAY ANTRIAN Dr. " . strtoupper($filter_dokter);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Display Antrian Poliklinik</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #007bff 0%, #28a745 100%);
            height: 100vh;
            overflow: hidden;
            color: white;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }
        
        .title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .poli-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #ffeb3b;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            background: rgba(255,255,255,0.1);
            padding: 8px 20px;
            border-radius: 25px;
            display: inline-block;
        }
        
        .subtitle {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .filter-info {
            font-size: 16px;
            margin-top: 10px;
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }
        
        .waiting-state {
            text-align: center;
        }
        
        .waiting-icon {
            font-size: 120px;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }
        
        .waiting-text {
            font-size: 24px;
            opacity: 0.8;
        }
        
        .calling-state {
            display: none;
            text-align: center;
            animation: slideInUp 0.5s ease-out;
        }
        
        .calling-icon {
            font-size: 150px;
            margin-bottom: 30px;
            animation: ring 1s ease-in-out infinite;
        }
        
        .patient-info {
            background: rgba(255,255,255,0.1);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 30px;
            min-width: 500px;
        }
        
        .patient-name {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .patient-details {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .calling-message {
            font-size: 28px;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            animation: blink 1.5s ease-in-out infinite;
        }
        
        .footer {
            background: linear-gradient(135deg, #007bff 0%, #28a745 100%);
            padding: 20px;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            border-top: 3px solid #fff;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.3);
        }
        
        .time-display {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 20px;
            font-weight: bold;
            background: rgba(0,0,0,0.3);
            padding: 10px 20px;
            border-radius: 10px;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes ring {
            0% { transform: rotate(-15deg); }
            25% { transform: rotate(15deg); }
            50% { transform: rotate(-15deg); }
            75% { transform: rotate(15deg); }
            100% { transform: rotate(0deg); }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.5; }
        }
        
        @media (max-width: 768px) {
            .title { font-size: 28px; }
            .patient-name { font-size: 32px; }
            .patient-info { min-width: 300px; padding: 30px; }
            .calling-message { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="time-display" id="current-time"></div>
    
    <div class="header">
        <div class="title"><?= htmlspecialchars($display_title) ?></div>
        <?php if ($filter_poli !== ''): ?>
            <div class="poli-name"><?= htmlspecialchars($filter_poli) ?></div>
        <?php endif; ?>
        <div class="subtitle"><?= htmlspecialchars($nama_organisasi) ?></div>
        <?php if ($filter_poli || $filter_dokter): ?>
            <div class="filter-info">
                <?php if ($filter_poli): ?>
                    📍 Poliklinik: <?= htmlspecialchars($filter_poli) ?>
                <?php endif; ?>
                <?php if ($filter_dokter): ?>
                    👨‍⚕️ Dokter: <?= htmlspecialchars($filter_dokter) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <!-- Tombol test suara untuk troubleshooting -->
        <button onclick="testSuara()" style="margin-top: 10px; padding: 8px 16px; background: #17a2b8; color: white; border: none; border-radius: 5px; cursor: pointer;">
            🔊 Test Suara
        </button>
    </div>
    
    <div class="content">
        <!-- Waiting State -->
        <div class="waiting-state" id="waiting-state">
            <div class="waiting-icon">🏥</div>
            <div class="waiting-text">Menunggu Panggilan Pasien...</div>
        </div>
        
        <!-- Calling State -->
        <div class="calling-state" id="calling-state">
            <div class="calling-icon">🔊</div>
            <div class="patient-info">
                <div class="patient-name" id="patient-name"></div>
                <div class="patient-details">
                    <div><strong>No. Antrian :</strong> <span id="patient-antrian"></span></div>
                    <div><strong>No. Rawat :</strong> <span id="patient-rawat"></span></div>
                    <div><strong>Poliklinik :</strong> <span id="patient-poli"></span></div>
                    <div><strong>Dokter :</strong> <span id="patient-dokter"></span></div>
                    <div><strong>Waktu Panggil :</strong> <span id="call-time"></span></div>
                </div>
            </div>
            <div class="calling-message">Silakan Menuju Poliklinik</div>
        </div>
    </div>
    
    <div class="footer">
        <marquee behavior="scroll" direction="left" scrollamount="5">
            ⚠️ PERHATIAN : Mohon bersabar menunggu - Kami dahulukan pasien DARURAT dan GAWAT DARURAT 
            🩺 Pastikan membawa kartu identitas dan kartu BPJS/asuransi Anda 
            🙏 Terima kasih atas pengertian dan kesabaran Anda ⚠️
        </marquee>
    </div>

    <script>
        // Update jam real-time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('current-time').innerHTML = dateString + '<br>' + timeString;
        }
        
        // Panggil pasien dengan suara
        function panggilPasien(kalimat) {
            console.log('Trying to play speech:', kalimat); // Debug log
            
            if ('speechSynthesis' in window) {
                // Hentikan semua suara yang sedang berjalan
                window.speechSynthesis.cancel();
                
                // Test apakah ada voices tersedia
                let voices = window.speechSynthesis.getVoices();
                console.log('Available voices:', voices.length); // Debug log
                
                // Jika voices belum loaded, tunggu event onvoiceschanged
                if (voices.length === 0) {
                    window.speechSynthesis.onvoiceschanged = function() {
                        panggilPasien(kalimat); // Retry setelah voices loaded
                    };
                    return;
                }
                
                setTimeout(function() {
                    try {
                        var msg = new SpeechSynthesisUtterance();
                        msg.text = kalimat;
                        msg.lang = 'id-ID';
                        msg.rate = 0.8;
                        msg.pitch = 1;
                        msg.volume = 1;
                        
                        // Cari voice Indonesia atau gunakan default
                        let indonesianVoice = voices.find(voice => 
                            voice.lang.includes('id') || 
                            voice.lang.includes('ID') ||
                            voice.name.toLowerCase().includes('indonesia')
                        );
                        
                        if (indonesianVoice) {
                            msg.voice = indonesianVoice;
                            console.log('Using Indonesian voice:', indonesianVoice.name);
                        } else {
                            console.log('Using default voice');
                        }
                        
                        msg.onstart = function() {
                            console.log('Speech started');
                        };
                        
                        msg.onend = function() {
                            console.log('Speech ended, playing second time');
                            // Panggil kedua kalinya setelah 1 detik
                            setTimeout(function() {
                                var msg2 = new SpeechSynthesisUtterance();
                                msg2.text = kalimat;
                                msg2.lang = 'id-ID';
                                msg2.rate = 0.8;
                                msg2.pitch = 1;
                                msg2.volume = 1;
                                if (indonesianVoice) msg2.voice = indonesianVoice;
                                
                                msg2.onend = function() {
                                    console.log('Second speech completed');
                                };
                                
                                msg2.onerror = function(e) {
                                    console.error('Second speech error:', e);
                                };
                                
                                window.speechSynthesis.speak(msg2);
                            }, 1000);
                        };
                        
                        msg.onerror = function(e) {
                            console.error('Speech error:', e);
                            alert('Error playing speech: ' + e.error);
                        };
                        
                        // Panggil pertama kali
                        window.speechSynthesis.speak(msg);
                        console.log('Speech initiated');
                        
                    } catch (error) {
                        console.error('Speech function error:', error);
                        alert('Speech error: ' + error.message);
                    }
                }, 100);
            } else {
                console.error('Speech synthesis not supported');
                alert('Browser tidak mendukung fitur suara panggilan.\nSilakan gunakan browser Chrome, Firefox, atau Edge terbaru.');
            }
        }
        
        // Tampilkan panggilan
        function showCalling(data) {
            document.getElementById('patient-name').textContent = data.nama_pasien;
            document.getElementById('patient-antrian').textContent = data.no_reg;
            document.getElementById('patient-rawat').textContent = data.no_rawat;
            document.getElementById('patient-poli').textContent = data.nm_poli || '-';
            document.getElementById('patient-dokter').textContent = data.nm_dokter || '-';
            document.getElementById('call-time').textContent = new Date(data.waktu_panggil).toLocaleTimeString('id-ID');
            
            document.getElementById('waiting-state').style.display = 'none';
            document.getElementById('calling-state').style.display = 'block';
            
            // Panggil dengan suara
            panggilPasien(data.kalimat_panggil);
            
            // Data tetap tampil sampai ada panggilan berikutnya
            // Tidak ada timeout untuk kembali ke waiting state
        }
        
        // Cek panggilan baru
        function checkPanggilan() {
            var url = 'displayantrianpoliklinik.php?action=get_panggilan';
            <?php if ($filter_poli): ?>
                url += '&poli=<?= urlencode($filter_poli) ?>';
            <?php endif; ?>
            <?php if ($filter_dokter): ?>
                url += '&dokter=<?= urlencode($filter_dokter) ?>';
            <?php endif; ?>
            
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response); // Debug log
                    if (response.success && response.data) {
                        // Ada panggilan baru, tampilkan  
                        showCalling(response.data);
                    } else {
                        // Tidak ada panggilan baru, jangan ubah tampilan yang sudah ada
                        console.log('No new calls:', response.message);
                        if (response.debug) {
                            console.log('Debug info:', response.debug);
                        }
                        // TIDAK mengubah tampilan - biarkan data tetap tampil
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', status, error);
                    console.log('Response text:', xhr.responseText);
                }
            });
        }
        
        // Fungsi test suara
        function testSuara() {
            console.log('Testing speech functionality...');
            panggilPasien('Test suara berhasil. Sistem panggilan poliklinik siap digunakan.');
        }
        
        // Update time setiap detik
        updateTime();
        setInterval(updateTime, 1000);
        
        // Cek panggilan setiap 500ms untuk response cepat
        setInterval(checkPanggilan, 500);
        
        // Cek panggilan pertama kali langsung
        checkPanggilan();
    </script>
</body>
</html>