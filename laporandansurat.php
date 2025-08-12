<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Laporan dan Surat</title>
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
            max-width: 600px;
            margin: 50px auto 60px auto;
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
            gap: 24px;
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
            <a href="suratketeranganhd.php" title="Surat Keterangan Pasien Hemodialisa">
                <i class="fas fa-file-medical"></i>
                <span>Surat Keterangan Pasien Hemodialisa</span>
            </a>
            <a href="suratketeranganumum.php" title="Surat Keterangan Umum">
                <i class="fas fa-file-signature"></i>
                <span>Surat Keterangan Umum</span>
            </a>
            <a href="laporanrujukan.php" title="Laporan Rujukan">
                <i class="fas fa-file-export"></i>
                <span>Laporan Rujukan</span>
            </a>
            <a href="laporanbpjs.php" title="Laporan BPJS">
                <i class="fas fa-file-invoice"></i>
                <span>Laporan BPJS</span>
            </a>
        </div>
    </div>
    <footer>by IT rsudpringsewu</footer>
</body>
</html>