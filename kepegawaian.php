<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Query untuk mengambil nama instansi dan logo dari database
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU"; // default jika tidak ada di database
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
    <title><?php echo htmlspecialchars($nama_instansi); ?> - Menu Kepegawaian</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 80px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 0 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e2e8f0;
        }
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border-color: #14b8a6;
        }
        .menu-item i {
            margin-bottom: 12px;
        }
        .menu-item span {
            font-weight: 600;
            font-size: 14px;
        }
        .logout {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #ef4444;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        footer {
            margin-top: 40px;
            text-align: center;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>

<a href="index.php" style="display: block; text-align: center; margin-top: 30px;">
    <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo" width="80" height="100">
</a>

<div class="container">
    <h1>MENU KEPEGAWAIAN</h1>
    
    <div class="menu-grid">
        <a href="pengajuan_cuti.php" class="menu-item">
            <i class="fas fa-calendar-alt fa-2x" style="color:#0ea5e9;"></i>
            <span>Pengajuan Cuti</span>
        </a>
        <a href="mapping_atasan_pegawai.php" class="menu-item">
            <i class="fas fa-sitemap fa-2x" style="color:#0d9488;"></i>
            <span>Mapping Atasan</span>
        </a>
    </div>

    <a href="logout.php" class="logout">Logout</a>
</div>

<footer>by IT RSUD Pringsewu</footer>

</body>
</html>
