<?php
include 'koneksi.php';

date_default_timezone_set('Asia/Jakarta');
function tanggal_indo($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $tgl = date('j', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return "$tgl $bln $thn";
}

// Ambil filter dari form
$tgl_peresepan = isset($_GET['tgl_peresepan']) ? $_GET['tgl_peresepan'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Ambil opsi status dari database
$status_options = [];
$status_query = "SELECT DISTINCT `status` FROM resep_obat ORDER BY `status`";
$status_result = mysqli_query($koneksi, $status_query);
while ($row = mysqli_fetch_assoc($status_result)) {
    if ($row['status'] !== '') $status_options[] = $row['status'];
}

// Query pasien belum dilayani dengan filter
$sql = "SELECT
    resep_obat.tgl_peresepan,
    reg_periksa.no_rawat,
    pasien.no_rkm_medis,
    pasien.nm_pasien,
    resep_obat.`status`,
    resep_obat.tgl_perawatan
FROM
    resep_obat
INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
WHERE
    resep_obat.tgl_perawatan = '0000-00-00'
    AND resep_obat.tgl_peresepan = ?
";

$params = [$tgl_peresepan];
$types = "s";

if ($status_filter !== '') {
    $sql .= " AND resep_obat.`status` = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY resep_obat.`status` ASC, resep_obat.tgl_peresepan ASC, resep_obat.jam_peresepan ASC";

$stmt = $koneksi->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Antrian Apotek</title>
    <meta http-equiv="refresh" content="10">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
        }
        .logo img {
            width: 60px;
            height: auto;
        }
        .title {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 5px;
            font-size: 22px;
            font-weight: bold;
            color: #007bff;
        }
        .date {
            text-align: center;
            color: #555;
            font-size: 16px;
            margin-bottom: 20px;
        }
        form {
            text-align: center;
            margin-bottom: 20px;
        }
        form label {
            margin-right: 8px;
        }
        form input, form select {
            margin-right: 16px;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        form button {
            padding: 6px 18px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        form button:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        th {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #888;
        }
        @media (max-width: 600px) {
            .container {
                padding: 8px;
            }
            .title {
                font-size: 18px;
            }
            .date {
                font-size: 14px;
            }
            th, td {
                padding: 6px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="logo">
        <a href="index.php">
            <img src="images/logo.png" alt="Logo">
        </a>
    </div>
    <div class="title">DASHBOARD ANTRIAN APOTEK</div>
    <div class="date"><?= tanggal_indo($tgl_peresepan) ?></div>

    <!-- Filter Form -->
    <form method="get">
        <label for="tgl_peresepan">Tanggal Peresepan:</label>
        <input type="date" id="tgl_peresepan" name="tgl_peresepan" value="<?= htmlspecialchars($tgl_peresepan) ?>" required>
        <label for="status">Status Resep:</label>
        <select id="status" name="status">
            <option value="">Semua</option>
            <?php foreach ($status_options as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($status_filter == $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Nomor Rawat</th>
                <th>Nomor Rekam Medik</th>
                <th>Nama Pasien</th>
                <th>Status Resep</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['no_rawat']) ?></td>
                        <td><?= htmlspecialchars($row['no_rkm_medis']) ?></td>
                        <td><?= htmlspecialchars($row['nm_pasien']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="no-data">Tidak ada data pasien untuk filter ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="position:fixed;left:0;right:0;bottom:0;background:#007bff;color:#fff;padding:8px 0;z-index:99;">
    <marquee behavior="scroll" direction="left" scrollamount="8" style="font-size:16px;font-family:Tahoma, Geneva, Verdana, sans-serif;">
        Mohon bersabar, kami dahulukan pasien DARURAT dan OPERASI
    </marquee>
</div>

</body>
</html>