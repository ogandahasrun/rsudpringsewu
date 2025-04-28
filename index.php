<?php
session_start();
include 'koneksi.php';

// Cek session username, bukan nik
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Pindahkan logika redirect sebelum keluar HTML
if (isset($_GET['menu'])) {
    $menu = $_GET['menu'];
    $allowed_menus = ['farmasi.php', 'keuangan.php', 'surveilans.php', 'casemix.php', 'laporandansurat.php'];
    
    if (in_array($menu, $allowed_menus)) {
        header("Location: $menu");
        exit();
    } else {
        // Kalau menu tidak dikenal, kembalikan ke index
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSUD PRINGSEWU</title>
    <style>
        /* Styling keren */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 80px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            margin-top: 20px;
            text-align: center;
        }
        select, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
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
    <img src="images/logo.png" alt="Logo" width="80" height="100">
</a>

<div class="container">
    <h1>RSUD PRINGSEWU</h1>
    <form method="get">
        <select name="menu" required>
            <option value="">Pilih Menu</option>
            <option value="farmasi.php">Farmasi</option>
            <option value="keuangan.php">Keuangan</option>
            <option value="surveilans.php">Surveilans</option>
            <option value="casemix.php">Casemix</option>
            <option value="laporandansurat.php">Laporan dan Surat</option>
        </select>
        <button type="submit">Pilih Menu</button>
    </form>
    <a href="logout.php" class="logout">Logout</a>
</div>

<footer>by IT RSUD Pringsewu</footer>

</body>
</html>
