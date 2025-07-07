<?php
include 'koneksi.php';
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

// Ambil daftar semua dokter untuk isi dropdown
$dokterList = [];
$dokterQuery = "SELECT DISTINCT nm_dokter FROM dokter ORDER BY nm_dokter ASC";
$dokterResult = $koneksi->query($dokterQuery);
while ($row = $dokterResult->fetch_assoc()) {
    $dokterList[] = $row['nm_dokter'];
}

// Siapkan query data pasien
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
    <meta http-equiv="refresh" content="5">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        select, input[type="submit"] { padding: 5px; }
    </style>
</head>
<body>

<div style="text-align: center;">
    <a href="index.php">
        <img src="images/logo.png" alt="Logo" width="40" height="50">
    </a>
</div>

<h3 style="text-align: center;">DASHBOARD ANTRIAN POLI</h3>
<h4 style="text-align: center;"><?= tanggal_indo(date('Y-m-d')) ?></h4>

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
            <th>No. Reg</th>
            <th>No. Rawat</th>
            <th>No. RM</th>
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
            <tr><td colspan="4">Tidak ada data pasien untuk hari ini.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
