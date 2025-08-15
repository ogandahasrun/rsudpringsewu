<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Surveilans</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        a {
            text-decoration: none;
        }
        .logo-link {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .container {
            max-width: 700px;
            margin: 40px auto 60px auto;
            padding: 22px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 28px;
            letter-spacing: 1px;
        }
        .icon-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 22px;
            margin-top: 10px;
        }
        .icon-menu a {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 22px 8px 16px 8px;
            background: #f8f9fa;
            border-radius: 12px;
            text-decoration: none;
            color: #007bff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: background 0.2s, box-shadow 0.2s, color 0.2s;
            font-size: 15px;
            min-height: 120px;
        }
        .icon-menu a:hover {
            background: #e3f2fd;
            box-shadow: 0 4px 16px rgba(0,123,255,0.08);
            color: #0056b3;
        }
        .icon-menu i {
            font-size: 2.2em;
            margin-bottom: 12px;
            color: #007bff;
            transition: color 0.2s;
        }
        .icon-menu a:hover i {
            color: #0056b3;
        }
        .icon-menu span {
            margin-top: 2px;
            font-size: 1em;
            color: #333;
            text-align: center;
            word-break: break-word;
        }
        footer {
            text-align: center;
            padding: 18px 0;
            background-color: #333;
            color: #fff;
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 15px;
            letter-spacing: 1px;
        }
        @media (max-width: 600px) {
            .container {
                padding: 10px;
            }
            .icon-menu {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .icon-menu a {
                padding: 14px 4px 10px 4px;
                min-height: 90px;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="logo-link">
        <img src="images/logo.png" alt="Logo" width="80" height="100">
    </a>
    <div class="container">
        <h1>RSUD PRINGSEWU</h1>
        <div class="icon-menu">
            <a href="borperkamar.php" title="BOR per Kamar">
                <i class="fas fa-bed"></i>
                <span>BOR per Kamar</span>
            </a>
            <a href="penyakit.php" title="Laporan Penyakit">
                <i class="fas fa-virus"></i>
                <span>Laporan Penyakit</span>
            </a>
            <a href="datapasienranap.php" title="Data Pasien Ranap non IGD dan Ponek">
                <i class="fas fa-users"></i>
                <span>Data Pasien Ranap</span>
            </a>
            <a href="hasillab.php" title="Hasil Lab">
                <i class="fas fa-vial"></i>
                <span>Hasil Lab</span>
            </a>
            <a href="penyakitkematian.php" title="Diagnosa Penyebab Kematian">
                <i class="fas fa-heart-broken"></i>
                <span>Diagnosa Kematian</span>
            </a>
            <a href="laporanigd.php" title="Laporan IGD">
                <i class="fas fa-ambulance"></i>
                <span>Laporan IGD</span>
            </a>
            <a href="diagnosaralan.php" title="Diagnosa Pasien Rawat Jalan">
                <i class="fas fa-user-md"></i>
                <span>Diagnosa Ralan</span>
            </a>
            <a href="triaseigd.php" title="Data Triase IGD">
                <i class="fas fa-notes-medical"></i>
                <span>Data Triase IGD</span>
            </a>
            <a href="kontroltriaseigd.php" title="Kontrol Triase IGD">
                <i class="fas fa-clipboard-check"></i>
                <span>Kontrol Triase IGD</span>
            </a>
            <a href="kunjunganpasien.php" title="Kunjungan Pasien">
                <i class="fas fa-user-friends"></i>
                <span>Kunjungan Pasien</span>
            </a>
            <a href="rl35kunjunganralan.php" title="RL 3.5 Kunjungan Rawat Jalan">
                <i class="fas fa-chart-bar"></i>
                <span>RL 3.5 Kunjungan Ralan</span>
            </a>
            <a href="rl38laboratorium.php" title="RL 3.8 Kegiatan Laboratorium">
                <i class="fas fa-microscope"></i>
                <span>RL 3.8 Kegiatan Laboratorium</span>
            </a>
            <a href="rl39radiologi.php" title="RL 3.9 Kegiatan Radiologi">
                <i class="fas fa-x-ray"></i>
                <span>RL 3.9 Radiologi</span>
            </a>
            <a href="rl318farmasiresep.php" title="RL 3.18 Resep Farmasi">
                <i class="fas fa-pills"></i>
                <span>RL 3.18 Resep Farmasi</span>
            </a>
        </div>
    </div>
    <footer>by IT rsudpringsewu</footer>
</body>
</html>