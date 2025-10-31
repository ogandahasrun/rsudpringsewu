<?php
include 'koneksi.php';
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU";
$logo_src = "images/logo.png"; // default jika tidak ada di database

if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    if (!empty($row_instansi['logo'])) {
        // Konversi BLOB ke base64
        $logo_blob = $row_instansi['logo'];
        $logo_base64 = base64_encode($logo_blob);
        $logo_src = "data:image/png;base64," . $logo_base64;
    }
}
?>

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
        .icon-menu img {
            width: 60px;
            height: 60px;
            margin-bottom: 12px;
            object-fit: contain;
            transition: transform 0.2s, opacity 0.2s;
        }
        .icon-menu a:hover img {
            transform: scale(1.1);
            opacity: 0.8;
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
        <img src="<?php echo $logo_src; ?>" alt="Logo" width="80" height="100">
    </a>
    <div class="container">
        <h1><?php echo htmlspecialchars($nama_instansi); ?></h1>
        <div class="icon-menu">
            <a href="umbalbpjs.php" title="Umpan Balik BPJS">
                <img src="images/bpjs.png" alt="BPJS Icon" style="width: 60px; height: 60px; margin-bottom: 12px; object-fit: contain;">
                <span>Umpan Balik BPJS</span>
            </a>
        </div>
    </div>
    <footer>by IT rsudpringsewu</footer>
</body>
</html>