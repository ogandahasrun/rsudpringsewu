<?php
include 'koneksi.php';

// Ambil filter dari form
$tgl_pesan_awal   = isset($_GET['tgl_pesan_awal']) ? $_GET['tgl_pesan_awal'] : '';
$tgl_pesan_akhir  = isset($_GET['tgl_pesan_akhir']) ? $_GET['tgl_pesan_akhir'] : '';
$tgl_faktur_awal  = isset($_GET['tgl_faktur_awal']) ? $_GET['tgl_faktur_awal'] : '';
$tgl_faktur_akhir = isset($_GET['tgl_faktur_akhir']) ? $_GET['tgl_faktur_akhir'] : '';
$nama_suplier     = isset($_GET['nama_suplier']) ? $_GET['nama_suplier'] : '';
$status           = isset($_GET['status']) ? $_GET['status'] : '';
$order_by         = isset($_GET['order_by']) ? $_GET['order_by'] : 'tgl_faktur';
$order_dir        = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC';

// Ambil daftar suplier untuk dropdown
$suplier_options = [];
$q_suplier = mysqli_query($koneksi, "SELECT DISTINCT nama_suplier FROM datasuplier ORDER BY nama_suplier");
while ($row = mysqli_fetch_assoc($q_suplier)) {
    $suplier_options[] = $row['nama_suplier'];
}

// Ambil daftar status untuk dropdown
$status_options = [];
$q_status = mysqli_query($koneksi, "SELECT DISTINCT status FROM pemesanan ORDER BY status");
while ($row = mysqli_fetch_assoc($q_status)) {
    if ($row['status'] !== '') $status_options[] = $row['status'];
}

// Bangun WHERE clause
$where = [];
if ($tgl_pesan_awal && $tgl_pesan_akhir) {
    $where[] = "pemesanan.tgl_pesan BETWEEN '$tgl_pesan_awal' AND '$tgl_pesan_akhir'";
}
if ($tgl_faktur_awal && $tgl_faktur_akhir) {
    $where[] = "pemesanan.tgl_faktur BETWEEN '$tgl_faktur_awal' AND '$tgl_faktur_akhir'";
}
if ($nama_suplier) {
    $where[] = "datasuplier.nama_suplier = '" . mysqli_real_escape_string($koneksi, $nama_suplier) . "'";
}
if ($status) {
    $where[] = "pemesanan.status = '" . mysqli_real_escape_string($koneksi, $status) . "'";
}
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Validasi kolom untuk sorting
$valid_order = ['no_faktur','nama_suplier','tgl_pesan','tgl_faktur','total2','ppn','tagihan','status'];
if (!in_array($order_by, $valid_order)) $order_by = 'tgl_faktur';
$order_dir = ($order_dir == 'DESC') ? 'DESC' : 'ASC';

// Query data hanya jika filter sudah dipilih
$data = [];
if (!empty($where)) {
    $sql = "SELECT
        pemesanan.no_faktur,
        datasuplier.nama_suplier,
        pemesanan.tgl_pesan,
        pemesanan.tgl_faktur,
        pemesanan.total2,
        pemesanan.ppn,
        pemesanan.tagihan,
        pemesanan.status
    FROM
        pemesanan
    INNER JOIN datasuplier ON pemesanan.kode_suplier = datasuplier.kode_suplier
    $where_sql
    ORDER BY $order_by $order_dir";
    $result = mysqli_query($koneksi, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hutang Medis</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; }
        .container { max-width: 1100px; margin: 30px auto; background: #fff; padding: 30px 30px 20px 30px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08);}
        h1 { text-align: center; color: #007bff; margin-bottom: 24px; }
        .filter-form { margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: center;}
        .filter-form label { margin-right: 6px; }
        .filter-form input, .filter-form select { padding: 4px 8px; border-radius: 4px; border: 1px solid #ccc; }
        .filter-form button { padding: 6px 18px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer;}
        .filter-form button:hover { background: #0056b3; }
        .back-button { margin-bottom: 16px; }
        .back-button a { color: #fff; background: #6c757d; padding: 6px 16px; border-radius: 4px; text-decoration: none;}
        .back-button a:hover { background: #495057; }
        .copy-btn { margin-bottom: 16px; background: #28a745; color: #fff; border: none; padding: 6px 18px; border-radius: 4px; cursor: pointer;}
        .copy-btn:hover { background: #218838; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #007bff; color: #fff; cursor: pointer; }
        tr:nth-child(even) { background: #f2f2f2; }
        tr:hover { background: #e3f2fd; }
        .no-data { text-align: center; color: #888; padding: 20px; }
        @media (max-width: 700px) {
            .container { padding: 8px; }
            .filter-form { flex-direction: column; gap: 8px;}
            th, td { font-size: 13px; padding: 6px;}
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Hutang Medis</h1>
    <form method="get" class="filter-form">
        <label>Tgl Pesan:</label>
        <input type="date" name="tgl_pesan_awal" value="<?php echo htmlspecialchars($tgl_pesan_awal); ?>">
        <input type="date" name="tgl_pesan_akhir" value="<?php echo htmlspecialchars($tgl_pesan_akhir); ?>">
        <label>Tgl Faktur:</label>
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
        <label>Status:</label>
        <select name="status">
            <option value="">Semua</option>
            <?php foreach ($status_options as $opt): ?>
                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($status == $opt) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($opt); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Tampilkan</button>
    </form>

    <div class="back-button">
        <a href="farmasi.php">Kembali ke Menu Farmasi</a>
    </div>
    <button class="copy-btn" id="copyTableBtn">Copy Tabel ke Clipboard</button>

    <?php if (!empty($where)): ?>
        <table id="hutangTable">
            <thead>
                <tr>
                    <?php
                    $columns = [
                        'no_faktur'   => 'No Faktur',
                        'nama_suplier'=> 'Nama Suplier',
                        'tgl_pesan'   => 'Tgl Pesan',
                        'tgl_faktur'  => 'Tgl Faktur',
                        'total2'      => 'Total',
                        'ppn'         => 'PPN',
                        'tagihan'     => 'Tagihan',
                        'status'      => 'Status'
                    ];
                    foreach ($columns as $col => $label) {
                        $dir = ($order_by == $col && $order_dir == 'ASC') ? 'DESC' : 'ASC';
                        $arrow = ($order_by == $col) ? ($order_dir == 'ASC' ? ' ▲' : ' ▼') : '';
                        $params = $_GET;
                        $params['order_by'] = $col;
                        $params['order_dir'] = $dir;
                        $url = '?' . http_build_query($params);
                        echo "<th onclick=\"window.location='$url'\">$label$arrow</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($data) > 0): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['no_faktur']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_suplier']); ?></td>
                            <td><?php echo htmlspecialchars($row['tgl_pesan']); ?></td>
                            <td><?php echo htmlspecialchars($row['tgl_faktur']); ?></td>
                            <td style="text-align:right;"><?php echo number_format($row['total2'],0,',','.'); ?></td>
                            <td style="text-align:right;"><?php echo number_format($row['ppn'],0,',','.'); ?></td>
                            <td style="text-align:right;"><?php echo number_format($row['tagihan'],0,',','.'); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="no-data">Tidak ada data ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">Silakan pilih filter untuk menampilkan data hutang medis.</div>
    <?php endif; ?>
</div>
<script>
document.getElementById('copyTableBtn').onclick = function() {
    var table = document.getElementById('hutangTable');
    if (!table) return;
    var range = document.createRange();
    range.selectNode(table);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            alert('Tabel berhasil disalin ke clipboard!');
        } else {
            alert('Gagal menyalin tabel.');
        }
    } catch (err) {
        alert('Browser tidak mendukung copy tabel otomatis.');
    }
    window.getSelection().removeAllRanges();
};
</script>
</body>
</html>