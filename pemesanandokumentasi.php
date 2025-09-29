<?php
include 'koneksi.php';

// Ambil daftar suplier untuk dropdown
$suplier_options = [];
$suplier_query = "SELECT nama_suplier FROM datasuplier ORDER BY nama_suplier";
$suplier_result = mysqli_query($koneksi, $suplier_query);
while ($row = mysqli_fetch_assoc($suplier_result)) {
    $suplier_options[] = $row['nama_suplier'];
}

// Ambil filter dari form
$tgl_pesan_awal = isset($_GET['tgl_pesan_awal']) ? $_GET['tgl_pesan_awal'] : date('Y-m-d');
$tgl_pesan_akhir = isset($_GET['tgl_pesan_akhir']) ? $_GET['tgl_pesan_akhir'] : date('Y-m-d');
$tgl_faktur_awal = isset($_GET['tgl_faktur_awal']) ? $_GET['tgl_faktur_awal'] : '';
$tgl_faktur_akhir = isset($_GET['tgl_faktur_akhir']) ? $_GET['tgl_faktur_akhir'] : '';
$nama_suplier = isset($_GET['nama_suplier']) ? $_GET['nama_suplier'] : '';

// Bangun WHERE clause
$where = [];
if (!empty($tgl_pesan_awal) && !empty($tgl_pesan_akhir)) {
    $where[] = "pemesanan.tgl_pesan BETWEEN '$tgl_pesan_awal' AND '$tgl_pesan_akhir'";
}
if (!empty($tgl_faktur_awal) && !empty($tgl_faktur_akhir)) {
    $where[] = "pemesanan.tgl_faktur BETWEEN '$tgl_faktur_awal' AND '$tgl_faktur_akhir'";
}
if (!empty($nama_suplier)) {
    $where[] = "datasuplier.nama_suplier = '" . mysqli_real_escape_string($koneksi, $nama_suplier) . "'";
}
if (empty($where)) {
    // Default: tampilkan data tgl_pesan hari ini
    $today = date('Y-m-d');
    $where[] = "pemesanan.tgl_pesan = '$today'";
}
$where_sql = implode(' AND ', $where);

// Query data
$sql = "SELECT
    pemesanan.tgl_pesan,
    pemesanan.tgl_faktur,
    pemesanan.no_faktur,
    datasuplier.nama_suplier,
    detailpesan.kode_brng,
    databarang.nama_brng,
    detailpesan.jumlah,
    databarang.kode_sat
FROM
    pemesanan
INNER JOIN datasuplier ON pemesanan.kode_suplier = datasuplier.kode_suplier
INNER JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
INNER JOIN databarang ON detailpesan.kode_brng = databarang.kode_brng
WHERE $where_sql
ORDER BY pemesanan.tgl_pesan DESC, pemesanan.no_faktur DESC";

$result = mysqli_query($koneksi, $sql);

// Proses data untuk rowspan
$data = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['tgl_pesan'] . '|' . $row['tgl_faktur'] . '|' . $row['no_faktur'] . '|' . $row['nama_suplier'];
        if (!isset($data[$key])) $data[$key] = [];
        $data[$key][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Faktur Barang Medis</title>
    <style>
        body, table, th, td, input, select, button {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
        }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #007bff; color: #fff; }
        tr:nth-child(even) { background: #f2f2f2; }
        tr:hover { background: #e3f2fd; }
        .filter-form { margin-bottom: 18px; }
        .filter-form label { margin-right: 8px; }
        .filter-form input, .filter-form select { margin-right: 16px; padding: 4px 8px; }
        .filter-form button { padding: 4px 16px; }
    </style>
</head>
<body>
    <h1>Daftar Faktur Barang Medis</h1>
    <form method="get" class="filter-form">
        <label>Periode Tgl Pesan:</label>
        <input type="date" name="tgl_pesan_awal" value="<?php echo htmlspecialchars($tgl_pesan_awal); ?>">
        <input type="date" name="tgl_pesan_akhir" value="<?php echo htmlspecialchars($tgl_pesan_akhir); ?>">
        <label>Periode Tgl Faktur:</label>
        <input type="date" name="tgl_faktur_awal" value="<?php echo htmlspecialchars($tgl_faktur_awal); ?>">
        <input type="date" name="tgl_faktur_akhir" value="<?php echo htmlspecialchars($tgl_faktur_akhir); ?>">
        <label>Nama Suplier:</label>
        <select name="nama_suplier">
            <option value="">Semua</option>
            <?php foreach ($suplier_options as $opt): ?>
                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($nama_suplier == $opt) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($opt); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
    </form>

    <table>
        <tr>
            <th>Tgl Pesan</th>
            <th>Tgl Faktur</th>
            <th>No Faktur</th>
            <th>Nama Suplier</th>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Jumlah</th>
            <th>Kode Satuan</th>
            <th>Aksi</th>
        </tr>
        <?php if (!empty($data)): ?>
            <?php foreach ($data as $key => $rows): 
                $rowspan = count($rows);
                $first = true;
                foreach ($rows as $row): ?>
                <tr>
                    <?php if ($first): ?>
                        <td rowspan="<?= $rowspan ?>"><?php echo htmlspecialchars($row['tgl_pesan']); ?></td>
                        <td rowspan="<?= $rowspan ?>"><?php echo htmlspecialchars($row['tgl_faktur']); ?></td>
                        <td rowspan="<?= $rowspan ?>">
                            <?php echo htmlspecialchars($row['no_faktur']); ?>
                        </td>
                        <td rowspan="<?= $rowspan ?>"><?php echo htmlspecialchars($row['nama_suplier']); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($row['kode_brng']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_brng']); ?></td>
                    <td style="text-align:right;"><?php echo htmlspecialchars($row['jumlah']); ?></td>
                    <td><?php echo htmlspecialchars($row['kode_sat']); ?></td>
                    <?php if ($first): ?>
                        <td rowspan="<?= $rowspan ?>">
                            <a href="dokumentasifaktur.php?no_faktur=<?php echo urlencode($row['no_faktur']); ?>" style="padding:4px 10px; background:#007bff; color:#fff; border-radius:4px; text-decoration:none;">Upload Foto</a>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php $first = false; endforeach; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9" style="text-align:center;">Tidak ada data ditemukan.</td></tr>
        <?php endif; ?>
    </table>
    <?php mysqli_close($koneksi); ?>
</body>
</html>