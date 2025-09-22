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
    <title>Dashboard Antrian Poli</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }
        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .header img {
            width: 60px;
        }
        .header-text {
            flex: 1;
        }
        .instansi {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .header-title {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        /* Grid untuk 3 tabel */
        .doctor-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .doctor-block {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .doctor-block form {
            margin-bottom: 10px;
        }
        .doctor-block select, 
        .doctor-block input[type="submit"] {
            padding: 6px 10px;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #007bff;
            color: white;
            padding: 8px;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f1f1f1;
        }
        .no-data {
            text-align: center;
            color: #888;
            padding: 10px;
        }

        /* Floating video */
        .floating-video {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 420px;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border-radius: 12px;
            background: #000;
            padding: 5px;
        }
        .floating-video video {
            width: 100%;
            border-radius: 12px;
        }
        .floating-video button {
            display: block;
            width: 100%;
            margin-top: 5px;
            padding: 8px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        .floating-video button:hover {
            background: #218838;
        }

        /* Responsif */
        @media (max-width: 992px) {
            .doctor-container {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 600px) {
            .doctor-container {
                grid-template-columns: 1fr;
            }
            .floating-video {
                width: 90%;
                right: 5%;
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
                <select id="dokter<?= $i ?>" name="dokter<?= $i ?>">
                    <option value="">-- Pilih Dokter --</option>
                    <?php foreach ($dokterList as $dokter): ?>
                        <option value="<?= htmlspecialchars($dokter) ?>"><?= htmlspecialchars($dokter) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Tampilkan">
            </form>
            <div id="table<?= $i ?>">Silakan pilih dokter.</div>
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

    // Fungsi load data tabel via AJAX
    function loadTable(event, id) {
        if (event) event.preventDefault();
        let dokter = document.getElementById("dokter"+id).value;
        let xhr = new XMLHttpRequest();
        xhr.open("GET", "load_data.php?nm_dokter="+encodeURIComponent(dokter));
        xhr.onload = function() {
            document.getElementById("table"+id).innerHTML = this.responseText;
        };
        xhr.send();
    }

    // Auto refresh setiap 20 detik (hanya tabel)
    setInterval(() => {
        for (let i=1; i<=3; i++) {
            let dokter = document.getElementById("dokter"+i).value;
            if (dokter) {
                loadTable(null, i);
            }
        }
    }, 20000);
</script>

</body>
</html>
