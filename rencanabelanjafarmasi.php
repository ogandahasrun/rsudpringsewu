<?php
include 'koneksi.php';

// Ambil tanggal filter, default hari ini jika belum dipilih
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Ambil data barang
$barang = [];
$q_barang = mysqli_query($koneksi, "SELECT kode_brng, nama_brng, kode_sat FROM databarang");
while ($row = mysqli_fetch_assoc($q_barang)) {
    $barang[$row['kode_brng']] = [
        'nama_brng' => $row['nama_brng'],
        'kode_sat'  => $row['kode_sat']
    ];
}

// Ambil stok per lokasi
$stok_lokasi = [];
$q_stok = mysqli_query($koneksi, "SELECT kode_brng, kd_bangsal, stok FROM gudangbarang");
while ($row = mysqli_fetch_assoc($q_stok)) {
    $kode = $row['kode_brng'];
    $bangsal = strtoupper($row['kd_bangsal']);
    $stok_lokasi[$kode][$bangsal] = $row['stok'];
}

// Ambil stok keluar GO
$stok_keluar = [];
$q_keluar = mysqli_query($koneksi, "
    SELECT detail_pengeluaran_obat_bhp.kode_brng, SUM(detail_pengeluaran_obat_bhp.jumlah) AS jumlah
    FROM pengeluaran_obat_bhp
    INNER JOIN detail_pengeluaran_obat_bhp ON detail_pengeluaran_obat_bhp.no_keluar = pengeluaran_obat_bhp.no_keluar
    WHERE pengeluaran_obat_bhp.kd_bangsal = 'GO'
      AND pengeluaran_obat_bhp.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY detail_pengeluaran_obat_bhp.kode_brng
");
while ($row = mysqli_fetch_assoc($q_keluar)) {
    $stok_keluar[$row['kode_brng']] = $row['jumlah'];
}

// Pengeluaran obat ke pasien
$pengeluaran_obat = [];
$q_pengeluaran = mysqli_query($koneksi, "
    SELECT detail_pemberian_obat.kode_brng, SUM(detail_pemberian_obat.jml) AS jumlah
    FROM detail_pemberian_obat
    WHERE detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY detail_pemberian_obat.kode_brng
");
while ($row = mysqli_fetch_assoc($q_pengeluaran)) {
    $pengeluaran_obat[$row['kode_brng']] = $row['jumlah'];
}

// Resep pulang
$resep_pulang = [];
$q_resep = mysqli_query($koneksi, "
    SELECT resep_pulang.kode_brng, SUM(resep_pulang.jml_barang) AS jumlah
    FROM resep_pulang
    WHERE resep_pulang.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY resep_pulang.kode_brng
");
while ($row = mysqli_fetch_assoc($q_resep)) {
    $resep_pulang[$row['kode_brng']] = $row['jumlah'];
}

// Penjualan bebas
$penjualan_bebas = [];
$q_jual = mysqli_query($koneksi, "
    SELECT detailjual.kode_brng, SUM(detailjual.jumlah) AS jumlah
    FROM penjualan
    INNER JOIN detailjual ON detailjual.nota_jual = penjualan.nota_jual
    WHERE penjualan.tgl_jual BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY detailjual.kode_brng
");
while ($row = mysqli_fetch_assoc($q_jual)) {
    $penjualan_bebas[$row['kode_brng']] = $row['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rencana Belanja Farmasi</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 30px auto; background: #fff; padding: 30px 30px 20px 30px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08);}
        h1 { text-align: center; color: #4CAF50; margin-bottom: 24px; }
        .filter-form { margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: center;}
        .filter-form label { margin-right: 6px; }
        .filter-form input { padding: 4px 8px; border-radius: 4px; border: 1px solid #ccc; }
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
        .table-scroll {max-height: 500px; overflow-y: auto;}
        @media (max-width: 700px) {
            .container { padding: 8px; }
            .filter-form { flex-direction: column; gap: 8px;}
            th, td { font-size: 12px; padding: 6px;}
        }
    </style>
</head>
<body>
<div class="container" id="allTables">
    <h1>Rencana Belanja Farmasi</h1>
    <div class="back-button">
        <a href="farmasi.php">Kembali ke Menu Farmasi</a>
    </div>
    <form method="post" class="filter-form">
        <label>Periode Tanggal:</label>
        <input type="date" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>">
        <input type="date" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
        <button type="submit">Tampilkan</button>
    </form>
    <button class="copy-btn" id="copyTableBtn">Copy Tabel ke Clipboard</button>
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kode Satuan</th>
                <th>Stok GO</th>
                <th>Stok DRI</th>
                <th>Stok AP</th>
                <th>Stok DI</th>
                <th>Stok DO</th>
                <th>Total Stok</th>
                <th>Stok Keluar GO</th>
                <th>Pengeluaran Obat</th>
                <th>Resep Pulang</th>
                <th>Penjualan Bebas</th>
                <th>Total Pengeluaran</th>
                <th>Rencana Kebutuhan</th>
                <th>Rencana Belanja</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            foreach ($barang as $kode_brng => $info) {
                $stok_go  = isset($stok_lokasi[$kode_brng]['GO'])  ? $stok_lokasi[$kode_brng]['GO']  : 0;
                $stok_dri = isset($stok_lokasi[$kode_brng]['DRI']) ? $stok_lokasi[$kode_brng]['DRI'] : 0;
                $stok_ap  = isset($stok_lokasi[$kode_brng]['AP'])  ? $stok_lokasi[$kode_brng]['AP']  : 0;
                $stok_di  = isset($stok_lokasi[$kode_brng]['DI'])  ? $stok_lokasi[$kode_brng]['DI']  : 0;
                $stok_do  = isset($stok_lokasi[$kode_brng]['DO'])  ? $stok_lokasi[$kode_brng]['DO']  : 0;
                $total_stok = $stok_go + $stok_dri + $stok_ap + $stok_di + $stok_do;

                $keluar_go = isset($stok_keluar[$kode_brng]) ? $stok_keluar[$kode_brng] : 0;
                $pengeluaran = isset($pengeluaran_obat[$kode_brng]) ? $pengeluaran_obat[$kode_brng] : 0;
                $resep = isset($resep_pulang[$kode_brng]) ? $resep_pulang[$kode_brng] : 0;
                $jual = isset($penjualan_bebas[$kode_brng]) ? $penjualan_bebas[$kode_brng] : 0;
                $total_pengeluaran = $keluar_go + $pengeluaran + $resep + $jual;
                $rencana_kebutuhan = $total_pengeluaran - $total_stok;
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($kode_brng); ?></td>
                    <td><?php echo htmlspecialchars($info['nama_brng']); ?></td>
                    <td><?php echo htmlspecialchars($info['kode_sat']); ?></td>
                    <td style="text-align:right;"><?php echo $stok_go; ?></td>
                    <td style="text-align:right;"><?php echo $stok_dri; ?></td>
                    <td style="text-align:right;"><?php echo $stok_ap; ?></td>
                    <td style="text-align:right;"><?php echo $stok_di; ?></td>
                    <td style="text-align:right;"><?php echo $stok_do; ?></td>
                    <td style="text-align:right;"><?php echo $total_stok; ?></td>
                    <td style="text-align:right;"><?php echo $keluar_go; ?></td>
                    <td style="text-align:right;"><?php echo $pengeluaran; ?></td>
                    <td style="text-align:right;"><?php echo $resep; ?></td>
                    <td style="text-align:right;"><?php echo $jual; ?></td>
                    <td style="text-align:right;"><?php echo $total_pengeluaran; ?></td>
                    <td style="text-align:right;"><?php echo $rencana_kebutuhan; ?></td>
                    <td></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
    </div>
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
</body>
</html>