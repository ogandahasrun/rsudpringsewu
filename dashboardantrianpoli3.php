<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Ambil nama instansi
$instansi = '';
$qSetting = $koneksi->query("SELECT nama_instansi FROM setting LIMIT 1");
if ($row = $qSetting->fetch_assoc()) {
    $instansi = $row['nama_instansi'];
}

// Ambil daftar dokter
$dokterList = [];
$dokterQuery = "SELECT DISTINCT nm_dokter FROM dokter ORDER BY nm_dokter ASC";
$dokterResult = $koneksi->query($dokterQuery);
while ($row = $dokterResult->fetch_assoc()) {
    $dokterList[] = $row['nm_dokter'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Konfigurasi khusus untuk browser TV digital -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Dashboard Antrian Poli</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.12), 0 6px 12px rgba(0,0,0,0.06);
            backdrop-filter: blur(6px);
            margin-top: 12px;
            margin-bottom: 12px;
            /* Make container use most of viewport so inner grid can stretch */
            min-height: calc(100vh - 40px);
        }
        .header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding: 12px 14px;
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 25%, #1e5f99 50%, #0f4c75 100%);
            border-radius: 10px;
            color: white;
            box-shadow: 0 6px 16px rgba(74, 144, 226, 0.18);
            position: relative;
            overflow: hidden;
            align-items: center;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
            pointer-events: none;
        }
        .header img {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1;
            position: relative;
        }
        .header-text {
            flex: 1;
            z-index: 1;
            position: relative;
        }
        .instansi {
            font-size: 16px;
            font-weight: 700;
            color: white;
            margin-bottom: 0;
            letter-spacing: 0.3px;
        }
        .header-title {
            font-size: 18px;
            font-weight: 700;
            color: #eaf6ff;
            margin-top: 2px;
            letter-spacing: 0.4px;
        }

        /* Grid untuk 3 tabel */
        .doctor-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .doctor-block {
            background: rgba(255,255,255,0.98);
            border-radius: 10px;
            padding: 10px 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            overflow-x: auto;
            transition: all 0.25s ease;
            border: 1px solid rgba(0,0,0,0.04);
            min-height: 320px;
        }
        .doctor-block:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
        }
        .doctor-block form {
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        .doctor-block label {
            display: block;
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .doctor-block select {
            width: 100%;
            padding: 12px 15px;
            margin: 8px 0 15px 0;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            transition: all 0.3s ease;
        }
        .doctor-block select:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        .doctor-block input[type="submit"] {
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doctor-block input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 144, 226, 0.3);
        }
        .doctor-block button {
            width: 100%;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        .doctor-block button:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        /* Khusus untuk browser TV digital */
        select:focus, button:focus, input:focus {
            outline: 3px solid #4a90e2 !important;
            outline-offset: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        th {
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border: none;
        }
        td {
            padding: 8px 6px;
            border-bottom: 1px solid #f3f7fb;
            font-size: 13px;
            color: #495057;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        tr:hover {
            background: #e3f2fd;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        tbody tr {
            transition: all 0.2s ease;
        }
        .no-data {
            text-align: center;
            color: #6c757d;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            font-style: italic;
            font-size: 14px;
            border: 2px dashed #dee2e6;
        }

        /* Floating video */
        .floating-video {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 420px;
            z-index: 9999;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2), 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 16px;
            background: linear-gradient(135deg, #000 0%, #333 100%);
            padding: 8px;
            border: 2px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        .floating-video video {
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .floating-video button {
            display: block;
            width: 100%;
            margin-top: 8px;
            padding: 12px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .floating-video button:hover {
            background: linear-gradient(135deg, #218838 0%, #17a2b8 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }

        /* Responsif */
        @media (max-width: 992px) {
            .doctor-container {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
            .container {
                padding: 15px;
            }
        }
        @media (max-width: 600px) {
            .doctor-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .container {
                padding: 10px;
                margin: 10px;
            }
            .header {
                padding: 20px;
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .header-title {
                font-size: 24px;
            }
            .instansi {
                font-size: 18px;
            }
            .floating-video {
                width: 90%;
                right: 5%;
                bottom: 10px;
            }
            .doctor-block {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <a href="index.php"><img src="images/logo.png" alt="Logo"></a>
        <div class="header-text">
            <div class="instansi"><?= htmlspecialchars($instansi) ?></div>
            <div class="header-title">DASHBOARD ANTRIAN POLI</div>
        </div>
    </div>

    <!-- 3 blok tabel dokter -->
    <div class="doctor-container">
        <?php for ($i=1; $i<=3; $i++): ?>
        <div class="doctor-block">
            <form method="get" onsubmit="loadTable(event, <?= $i ?>)">
                <label for="dokter<?= $i ?>">Pilih Dokter <?= $i ?>:</label>
                <select id="dokter<?= $i ?>" name="dokter<?= $i ?>" onchange="loadTable(null, <?= $i ?>)">
                    <option value="">-- Pilih Dokter --</option>
                    <?php foreach ($dokterList as $dokter): ?>
                        <option value="<?= htmlspecialchars($dokter) ?>" <?= (isset($_GET["dokter$i"]) && $_GET["dokter$i"] == $dokter) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dokter) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Tampilkan">
            </form>
            <div id="table<?= $i ?>">
                <?php 
                // Fallback PHP untuk menampilkan data jika AJAX tidak berfungsi
                if (isset($_GET["dokter$i"]) && !empty($_GET["dokter$i"])) {
                    $selected_dokter = $_GET["dokter$i"];
                    
                    // Query untuk mendapatkan data antrian
                    $query = "SELECT 
                                reg_periksa.no_rawat,
                                reg_periksa.no_rkm_medis,
                                pasien.nm_pasien,
                                reg_periksa.jam_reg,
                                reg_periksa.no_reg
                              FROM reg_periksa 
                              INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                              INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                              WHERE dokter.nm_dokter = ? 
                              AND reg_periksa.tgl_registrasi = CURDATE()
                              ORDER BY reg_periksa.jam_reg ASC";
                    
                    $stmt = $koneksi->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param("s", $selected_dokter);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            echo '<table>';
                            echo '<thead><tr><th>No Reg</th><th>No RM</th><th>Nama Pasien</th><th>Jam</th></tr></thead>';
                            echo '<tbody>';
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['no_reg']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['no_rkm_medis']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['nm_pasien']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['jam_reg']) . '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                        } else {
                            echo '<div class="no-data">Tidak ada antrian untuk dokter ini hari ini.</div>';
                        }
                        $stmt->close();
                    }
                } else {
                    echo 'Silakan pilih dokter.';
                }
                ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Floating Video -->
<div class="floating-video">
    <video id="videoEdu" autoplay muted loop>
        <source src="videos/edukasi.mp4" type="video/mp4">
        Browser Anda tidak mendukung video.
    </video>
    <button onclick="toggleMute()">🔊 Aktifkan / Matikan Suara</button>
</div>

<script>
    const vid = document.getElementById("videoEdu");

    function toggleMute() {
        vid.muted = !vid.muted;
    }

    // Fungsi load data tabel via AJAX - kompatibel dengan browser TV digital
    function loadTable(event, id) {
        if (event) event.preventDefault();
        
        var dokter = document.getElementById("dokter"+id).value;
        if (!dokter) {
            document.getElementById("table"+id).innerHTML = "Silakan pilih dokter.";
            return;
        }
        
        // Fallback untuk browser lama yang tidak support XMLHttpRequest
        if (typeof XMLHttpRequest === "undefined") {
            // Redirect ke halaman dengan parameter
            window.location.href = "dashboardantrianpoli3.php?dokter"+id+"="+encodeURIComponent(dokter);
            return;
        }
        
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "load_data.php?nm_dokter="+encodeURIComponent(dokter), true);
        
        // Timeout untuk browser TV yang lambat
        xhr.timeout = 10000;
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    document.getElementById("table"+id).innerHTML = xhr.responseText;
                } else {
                    document.getElementById("table"+id).innerHTML = "Error loading data. Status: " + xhr.status;
                }
            }
        };
        
        xhr.ontimeout = function() {
            document.getElementById("table"+id).innerHTML = "Request timeout. Silakan coba lagi.";
        };
        
        xhr.onerror = function() {
            document.getElementById("table"+id).innerHTML = "Network error. Silakan coba lagi.";
        };
        
        try {
            xhr.send();
        } catch (e) {
            document.getElementById("table"+id).innerHTML = "Error: " + e.message;
        }
    }

    // Tambahkan event listener untuk dropdown change - kompatibel TV digital
    document.addEventListener('DOMContentLoaded', function() {
        for (var i=1; i<=3; i++) {
            var select = document.getElementById("dokter"+i);
            if (select) {
                // Event listener untuk perubahan dropdown
                select.onchange = (function(id) {
                    return function() {
                        if (this.value) {
                            loadTable(null, id);
                        } else {
                            document.getElementById("table"+id).innerHTML = "Silakan pilih dokter.";
                        }
                    };
                })(i);
                
                // Tambahan: event untuk keyboard navigation pada TV
                select.onkeydown = function(e) {
                    if (e.keyCode === 13) { // Enter key
                        if (this.value) {
                            var id = this.id.replace('dokter', '');
                            loadTable(null, parseInt(id));
                        }
                    }
                };
            }
        }
    });
    
    // Auto refresh setiap 30 detik (diperlambat untuk TV digital)
    setInterval(function() {
        for (var i=1; i<=3; i++) {
            var dokter = document.getElementById("dokter"+i).value;
            if (dokter) {
                loadTable(null, i);
            }
        }
    }, 30000);
</script>

</body>
</html>
