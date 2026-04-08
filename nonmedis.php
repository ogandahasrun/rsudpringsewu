<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU";
$logo_src = "images/logo.png";
if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    if (!empty($row_instansi['logo'])) {
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
    <title>Menu Non Medis - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #f9f9f9;
            border-radius: 10px;
            padding: 15px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .menu-item i {
            margin-bottom: 10px;
        }
        .logout {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: red;
            text-decoration: none;
        }
        footer {
            margin-top: 40px;
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>

<a href="index.php" style="display: block; text-align: center; margin-top: 30px;">
    <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo" width="80" height="100">
</a>

<div class="container">
    <h1>Menu Non Medis - <?php echo htmlspecialchars($nama_instansi); ?></h1>

    <div class="menu-grid">
        <a href="pengeluarannonmedis.php" class="menu-item">
            <i class="fas fa-dolly-flatbed fa-2x" style="color:#4f46e5;"></i>
            <span style="color:#222;font-weight:bold;">Pengeluaran Barang Non Medis</span>
        </a>
        <!-- Tambahkan menu lain jika ada kebutuhan -->
    </div>

    <a href="logout.php" class="logout">Logout</a>
</div>

<footer>by IT RSUD Pringsewu</footer>

</body>
</html>