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

$today = date('Y-m-d');
$nm_dokter = isset($_GET['nm_dokter']) ? $_GET['nm_dokter'] : '';

// Ambil daftar dokter untuk dropdown
$dokterList = [];
$dokterQuery = "SELECT DISTINCT nm_dokter FROM dokter ORDER BY nm_dokter ASC";
$dokterResult = $koneksi->query($dokterQuery);
while ($row = $dokterResult->fetch_assoc()) {
    $dokterList[] = $row['nm_dokter'];
}

// Query pasien belum dilayani
$sql = "SELECT
            reg_periksa.no_reg,
            reg_periksa.no_rawat,
            pasien.no_rkm_medis,
            pasien.nm_pasien
        FROM
            reg_periksa
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
        WHERE
            dokter.status = '1' AND
            reg_periksa.stts = 'Belum' AND
            reg_periksa.tgl_registrasi = ?";
$params = [$today];
$types = "s";

if (!empty($nm_dokter)) {
    $sql .= " AND dokter.nm_dokter = ?";
    $params[] = $nm_dokter;
    $types .= "s";
}

$sql .= " GROUP BY reg_periksa.no_reg";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Antrian Poli</title>
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
        select, input[type="submit"] {
            padding: 8px 12px;
            font-size: 14px;
            margin: 5px;
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
    </style>
</head>
<body>

<div class="container">
    <div class="logo">
        <a href="index.php">
            <img src="images/logo.png" alt="Logo">
        </a>
    </div>
    <div class="title">DASHBOARD ANTRIAN POLI</div>
    <div class="date"><?= tanggal_indo(date('Y-m-d')) ?></div>

    <form method="get">
        <label for="nm_dokter">Pilih Dokter:</label>
        <select name="nm_dokter" id="nm_dokter">
            <option value="">-- Semua Dokter --</option>
            <?php foreach ($dokterList as $dokter): ?>
                <option value="<?= htmlspecialchars($dokter) ?>" <?= ($dokter == $nm_dokter) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dokter) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" value="Tampilkan">
    </form>

    <table>
        <thead>
            <tr>
                <th>Nomor Registrasi</th>
                <th>Nomor Rawat</th>
                <th>Nomor Rekam Medik</th>
                <th>Nama Pasien</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['no_reg']) ?></td>
                        <td><?= htmlspecialchars($row['no_rawat']) ?></td>
                        <td><?= htmlspecialchars($row['no_rkm_medis']) ?></td>
                        <td><?= htmlspecialchars($row['nm_pasien']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="no-data">Tidak ada data pasien untuk hari ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
