<?php
include 'koneksi.php';

// Ambil daftar no_permintaan untuk filter
$permintaan_options = [];
$q1 = mysqli_query($koneksi, "SELECT DISTINCT no_permintaan FROM permintaan_medis ORDER BY no_permintaan DESC");
while ($row = mysqli_fetch_assoc($q1)) {
    $permintaan_options[] = $row['no_permintaan'];
}

// Ambil daftar keterangan untuk filter
$keterangan_options = [];
$q2 = mysqli_query($koneksi, "SELECT DISTINCT keterangan FROM mutasibarang ORDER BY keterangan DESC");
while ($row = mysqli_fetch_assoc($q2)) {
    $keterangan_options[] = $row['keterangan'];
}

// Ambil filter dari form
$no_permintaan = isset($_GET['no_permintaan']) ? $_GET['no_permintaan'] : '';
$keterangan    = isset($_GET['keterangan']) ? $_GET['keterangan'] : '';

// Query pertama: permintaan_medis
$data_permintaan = [];
if ($no_permintaan) {
    $sql1 = "SELECT
                detail_permintaan_medis.kode_brng,
                databarang.nama_brng,
                detail_permintaan_medis.jumlah,
                detail_permintaan_medis.kode_sat,
                permintaan_medis.no_permintaan
            FROM
                permintaan_medis
            INNER JOIN detail_permintaan_medis ON detail_permintaan_medis.no_permintaan = permintaan_medis.no_permintaan
            INNER JOIN databarang ON detail_permintaan_medis.kode_brng = databarang.kode_brng
            WHERE permintaan_medis.no_permintaan = '" . mysqli_real_escape_string($koneksi, $no_permintaan) . "'";
    $res1 = mysqli_query($koneksi, $sql1);
    while ($row = mysqli_fetch_assoc($res1)) {
        $data_permintaan[$row['kode_brng']] = $row + ['keterangan' => ''];
    }
}

// Query kedua: mutasibarang
$data_mutasi = [];
if ($keterangan) {
    $sql2 = "SELECT
                databarang.kode_brng,
                databarang.nama_brng,
                mutasibarang.jml AS jumlah,
                databarang.kode_sat,
                mutasibarang.keterangan
            FROM
                mutasibarang
            INNER JOIN databarang ON mutasibarang.kode_brng = databarang.kode_brng
            WHERE mutasibarang.keterangan = '" . mysqli_real_escape_string($koneksi, $keterangan) . "'";
    $res2 = mysqli_query($koneksi, $sql2);
    while ($row = mysqli_fetch_assoc($res2)) {
        $data_mutasi[$row['kode_brng']] = $row + ['no_permintaan' => ''];
    }
}

// Gabungkan data: irisan dan selisih
$all_kode = array_unique(array_merge(array_keys($data_permintaan), array_keys($data_mutasi)));
$tabel_data = [];
foreach ($all_kode as $kode) {
    $row = [
        'kode_brng'         => $kode,
        'nama_brng'         => isset($data_permintaan[$kode]) ? $data_permintaan[$kode]['nama_brng'] : (isset($data_mutasi[$kode]) ? $data_mutasi[$kode]['nama_brng'] : ''),
        'jumlah_permintaan' => isset($data_permintaan[$kode]) ? $data_permintaan[$kode]['jumlah'] : '',
        'jumlah_mutasi'     => isset($data_mutasi[$kode]) ? $data_mutasi[$kode]['jumlah'] : '',
        'kode_sat'          => isset($data_permintaan[$kode]) ? $data_permintaan[$kode]['kode_sat'] : (isset($data_mutasi[$kode]) ? $data_mutasi[$kode]['kode_sat'] : ''),
        'no_permintaan'     => isset($data_permintaan[$kode]) ? $data_permintaan[$kode]['no_permintaan'] : '',
        'keterangan'        => isset($data_mutasi[$kode]) ? $data_mutasi[$kode]['keterangan'] : '',
        'sumber'            => (isset($data_permintaan[$kode]) && isset($data_mutasi[$kode])) ? 'Irisan' : ((isset($data_permintaan[$kode])) ? 'Permintaan' : 'Mutasi')
    ];
    $tabel_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kontrol Mutasi Barang Medis</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .container { max-width: 900px; margin: 30px auto; background: #fff; padding: 30px 30px 20px 30px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08);}
        h1 { text-align: center; color: #4CAF50; margin-bottom: 24px; }
        .filter-form { margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: center;}
        .filter-form label { margin-right: 6px; }
        .filter-form select { padding: 4px 8px; border-radius: 4px; border: 1px solid #ccc; }
        .filter-form button { padding: 6px 18px; background: #4CAF50; color: #fff; border: none; border-radius: 4px; cursor: pointer;}
        .filter-form button:hover { background: #388E3C; }
        .back-button { margin-bottom: 16px; }
        .back-button a { color: #fff; background: #6c757d; padding: 6px 16px; border-radius: 4px; text-decoration: none;}
        .back-button a:hover { background: #495057; }
        .copy-btn { margin-bottom: 16px; background: #2196F3; color: #fff; border: none; padding: 6px 18px; border-radius: 4px; cursor: pointer;}
        .copy-btn:hover { background: #1976D2; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; font-size: 13px; }
        th { background: #4CAF50; color: #fff; }
        tr:nth-child(even) { background: #f2f2f2; }
        tr:hover { background: #e3f2fd; }
        .no-data { text-align: center; color: #888; padding: 20px; }
        @media (max-width: 700px) {
            .container { padding: 8px; }
            .filter-form { flex-direction: column; gap: 8px;}
            th, td { font-size: 12px; padding: 6px;}
        }
    </style>

    <!-- Tambahkan sebelum </head> -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</head>
<body>
<div class="container" id="allTables">
    <h1>Kontrol Mutasi Barang Medis</h1>
    <div class="back-button">
        <a href="farmasi.php">Kembali ke Menu Farmasi</a>
    </div>
    <form method="get" class="filter-form">
        <label for="no_permintaan">No Permintaan:</label>
        <select name="no_permintaan" id="no_permintaan" class="select2">
            <option value="">Pilih No Permintaan</option>
            <?php foreach ($permintaan_options as $opt): ?>
                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($no_permintaan == $opt) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($opt); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="keterangan">Keterangan Mutasi:</label>
        <select name="keterangan" id="keterangan" class="select2">
            <option value="">Pilih Keterangan</option>
            <?php foreach ($keterangan_options as $opt): ?>
                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($keterangan == $opt) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($opt); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Tampilkan</button>
    </form>
    <button class="copy-btn" id="copyTableBtn">Copy Tabel ke Clipboard</button>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah Permintaan</th>
                <th>Jumlah Mutasi</th>
                <th>Kode Satuan</th>
                <th>No Permintaan</th>
                <th>Keterangan Mutasi</th>
                <th>Sumber Data</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($no_permintaan || $keterangan): ?>
                <?php if (count($tabel_data) > 0): ?>
                    <?php $no=1; foreach ($tabel_data as $row): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['kode_brng']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_brng']); ?></td>
                            <td style="text-align:right;"><?php echo htmlspecialchars($row['jumlah_permintaan']); ?></td>
                            <td style="text-align:right;"><?php echo htmlspecialchars($row['jumlah_mutasi']); ?></td>
                            <td><?php echo htmlspecialchars($row['kode_sat']); ?></td>
                            <td><?php echo htmlspecialchars($row['no_permintaan']); ?></td>
                            <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                            <td><?php echo htmlspecialchars($row['sumber']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="no-data">Tidak ada data ditemukan.</td></tr>
                <?php endif; ?>
            <?php else: ?>
                <tr><td colspan="9" class="no-data">Silakan pilih filter untuk menampilkan data.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
document.getElementById('copyTableBtn').onclick = function() {
    var allTables = document.getElementById('allTables');
    var range = document.createRange();
    range.selectNode(allTables);
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

<script>
$(document).ready(function() {
    $('.select2').select2({
        width: 'resolve',
        placeholder: 'Ketik untuk mencari...',
        allowClear: true
    });
});
</script>

</body>
</html>