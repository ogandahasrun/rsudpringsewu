<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RSUD PRINGSEWU</title>
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
    .menu-item img {
        width: 50px;
        height: 50px;
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
    <img src="images/logo.png" alt="Logo" width="80" height="100">
</a>

<div class="container">
    <h1>RSUD PRINGSEWU</h1>

    <div class="menu-grid">
        <a href="farmasi.php" class="menu-item">
            <img src="images/farmasi.png" alt="Farmasi">
            <span>Farmasi</span>
        </a>
        <a href="keuangan.php" class="menu-item">
            <img src="images/keuangan.png" alt="Keuangan">
            <span>Keuangan</span>
        </a>
        <a href="surveilans.php" class="menu-item">
            <img src="images/surveilans.png" alt="Surveilans">
            <span>Surveilans</span>
        </a>
        <a href="casemix.php" class="menu-item">
            <img src="images/casemix.png" alt="Casemix">
            <span>Casemix</span>
        </a>
        <a href="dashboard.php" class="menu-item">
            <img src="images/dashboard.png" alt="Dashboard">
            <span>Dashboard</span>
        </a>
        <a href="laporandansurat.php" class="menu-item">
            <img src="images/laporan.png" alt="Laporan dan Surat">
            <span>Laporan & Surat</span>
        </a>
        <a href="pengadaan.php" class="menu-item">
            <img src="images/pengadaan.png" alt="Pengadaan">
            <span>Pengadaan</span>
        </a>
        <a href="bpjs.php" class="menu-item">
            <img src="images/bpjs.png" alt="BPJS">
            <span>BPJS</span>
        </a>
    </div>

    <a href="logout.php" class="logout">Logout</a>
</div>

<footer>by IT RSUD Pringsewu</footer>

</body>
</html>
