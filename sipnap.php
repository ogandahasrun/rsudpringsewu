<?php
// filepath: c:\xampp\htdocs\rsudpringsewu\sipnap.php
include 'koneksi.php';

// Ambil tanggal filter, default hari ini jika belum dipilih
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// 1. Query utama: ambil data barang golongan NK dan PSI
$query_barang = "SELECT kode_brng, nama_brng, kode_sat, kode_golongan FROM databarang WHERE kode_golongan IN ('NK','PSI') ORDER BY kode_golongan ASC";
$result_barang = mysqli_query($koneksi, $query_barang);
$barang_list = [];
while ($row = mysqli_fetch_assoc($result_barang)) {
    $barang_list[$row['kode_brng']] = [
        'nama_brng' => $row['nama_brng'],
        'kode_sat' => $row['kode_sat'],
        'kode_golongan' => $row['kode_golongan']
    ];
}

// 2. Stok awal per bangsal (ambil stok pada tanggal dan jam paling awal di periode untuk setiap barang)
$stok_awal = [];
$bangsals = ['GO','DRI','AP','DI','DO','DPED'];
foreach ($bangsals as $bangsal) {
    $stok_awal[$bangsal] = [];
    foreach ($barang_list as $kode_brng => $barang) {
        // Ambil tanggal dan jam paling awal untuk barang dan bangsal ini dalam periode
        $q_min = "SELECT tanggal FROM riwayat_barang_medis 
                  WHERE kd_bangsal = '$bangsal' 
                    AND kode_brng = '$kode_brng'
                    AND tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                  ORDER BY tanggal ASC LIMIT 1";
        $r_min = mysqli_query($koneksi, $q_min);
        $min_row = mysqli_fetch_assoc($r_min);
        
        if ($min_row) {
            $min_tanggal = $min_row['tanggal'];
            // Ambil stok_awal pada tanggal dan jam paling awal dalam periode
            $q_stok = "SELECT stok_awal FROM riwayat_barang_medis 
                       WHERE kd_bangsal = '$bangsal' 
                         AND kode_brng = '$kode_brng'
                         AND tanggal = '$min_tanggal'
                       LIMIT 1";
            $r_stok = mysqli_query($koneksi, $q_stok);
            $stok_row = mysqli_fetch_assoc($r_stok);
            $stok_awal[$bangsal][$kode_brng] = $stok_row ? $stok_row['stok_awal'] : 0;
        } else {
            // Jika tidak ada data dalam periode, ambil stok_akhir dari transaksi terakhir sebelum periode
            $q_prev = "SELECT stok_akhir FROM riwayat_barang_medis 
                       WHERE kd_bangsal = '$bangsal' 
                         AND kode_brng = '$kode_brng'
                         AND tanggal < '$tanggal_awal'
                       ORDER BY tanggal DESC LIMIT 1";
            $r_prev = mysqli_query($koneksi, $q_prev);
            $prev_row = mysqli_fetch_assoc($r_prev);
            $stok_awal[$bangsal][$kode_brng] = $prev_row ? $prev_row['stok_akhir'] : 0;
        }
    }
}

// 3. Barang masuk
// a. Penerimaan
$penerimaan = [];
$q = "SELECT detailpesan.kode_brng, SUM(detailpesan.jumlah) as jumlah FROM pemesanan
INNER JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
INNER JOIN databarang ON detailpesan.kode_brng = databarang.kode_brng
WHERE databarang.kode_golongan IN ('NK','PSI') AND pemesanan.tgl_pesan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY detailpesan.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error penerimaan: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $penerimaan[$row['kode_brng']] = $row['jumlah'];
}

// b. Hibah
$hibah = [];
$q = "SELECT detailhibah_obat_bhp.kode_brng, SUM(detailhibah_obat_bhp.jumlah) as jumlah FROM hibah_obat_bhp
INNER JOIN detailhibah_obat_bhp ON detailhibah_obat_bhp.no_hibah = hibah_obat_bhp.no_hibah
INNER JOIN databarang ON detailhibah_obat_bhp.kode_brng = databarang.kode_brng
WHERE hibah_obat_bhp.tgl_hibah BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND databarang.kode_golongan IN ('NK','PSI')
GROUP BY detailhibah_obat_bhp.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error hibah: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $hibah[$row['kode_brng']] = $row['jumlah'];
}

// c. Retur pasien
$retur = [];
$q = "SELECT detreturjual.kode_brng, SUM(detreturjual.jml_retur) as jumlah FROM returjual
INNER JOIN detreturjual ON detreturjual.no_retur_jual = returjual.no_retur_jual
INNER JOIN databarang ON detreturjual.kode_brng = databarang.kode_brng
WHERE returjual.tgl_retur BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND databarang.kode_golongan IN ('NK','PSI')
GROUP BY detreturjual.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error retur: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $retur[$row['kode_brng']] = $row['jumlah'];
}

// 4. Barang keluar
// a. Pemberian obat
$pemberian = [];
$q = "SELECT detail_pemberian_obat.kode_brng, SUM(detail_pemberian_obat.jml) as jumlah FROM detail_pemberian_obat
INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
WHERE databarang.kode_golongan IN ('NK','PSI') AND detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY detail_pemberian_obat.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error pemberian: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $pemberian[$row['kode_brng']] = $row['jumlah'];
}

// b. Resep pulang
$resep_pulang = [];
$q = "SELECT resep_pulang.kode_brng, SUM(resep_pulang.jml_barang) as jumlah FROM resep_pulang
INNER JOIN databarang ON resep_pulang.kode_brng = databarang.kode_brng
WHERE databarang.kode_golongan IN ('NK','PSI') AND resep_pulang.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY resep_pulang.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error resep_pulang: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $resep_pulang[$row['kode_brng']] = $row['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SIPNAP</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: green; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; font-size: 13px; }
        th { background: #4CAF50; color: #fff; }
        tr:nth-child(even) { background: #f2f2f2; }
        .filter-form { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        .filter-form input { padding: 8px; margin-right: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-form button { padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form button:hover { background: #45a049; }
        #copyTableBtn { margin-bottom:15px; padding:8px 15px; background:#2196F3; color:#fff; border:none; border-radius:4px; cursor:pointer; }
        #copyTableBtn:hover { background:#1976D2; }
    </style>
</head>
<body>
    <h1>SIPNAP</h1>
    <form method="POST" class="filter-form">
        Periode :
        <input type="date" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
        <button type="submit" name="filter">Tampilkan</button>
    </form>

        <div class="back-button">
            <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
        </div>

    <button id="copyTableBtn">Copy Tabel ke Clipboard</button>

    <div id="sipnapTable">
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Golongan</th>
                <th>Stok Awal GO</th>
                <th>Stok Awal DRI</th>
                <th>Stok Awal AP</th>
                <th>Stok Awal DI</th>
                <th>Stok Awal DO</th>
                <th>Stok Awal DPED</th>
                <th>Total Stok Awal</th>
                <th>Penerimaan</th>
                <th>Hibah</th>
                <th>Retur Pasien</th>
                <th>Total Barang Masuk</th>
                <th>Pemberian Obat</th>
                <th>Resep Pulang</th>
                <th>Total Barang Keluar</th>
                <th>Stok Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
foreach ($barang_list as $kode_brng => $barang) {
    // Stok awal per bangsal
    $go = isset($stok_awal['GO'][$kode_brng]) ? $stok_awal['GO'][$kode_brng] : 0;
    $dri = isset($stok_awal['DRI'][$kode_brng]) ? $stok_awal['DRI'][$kode_brng] : 0;
    $ap = isset($stok_awal['AP'][$kode_brng]) ? $stok_awal['AP'][$kode_brng] : 0;
    $di = isset($stok_awal['DI'][$kode_brng]) ? $stok_awal['DI'][$kode_brng] : 0;
    $do = isset($stok_awal['DO'][$kode_brng]) ? $stok_awal['DO'][$kode_brng] : 0;
    $de = isset($stok_awal['DPED'][$kode_brng]) ? $stok_awal['DPED'][$kode_brng] : 0;
    $total_stok_awal = $go + $dri + $ap + $di + $do + $de;

    // Barang masuk
    $masuk_penerimaan = isset($penerimaan[$kode_brng]) ? $penerimaan[$kode_brng] : 0;
    $masuk_hibah = isset($hibah[$kode_brng]) ? $hibah[$kode_brng] : 0;
    $masuk_retur = isset($retur[$kode_brng]) ? $retur[$kode_brng] : 0;
    $total_masuk = $masuk_penerimaan + $masuk_hibah + $masuk_retur;

    // Barang keluar
    $keluar_pemberian = isset($pemberian[$kode_brng]) ? $pemberian[$kode_brng] : 0;
    $keluar_resep = isset($resep_pulang[$kode_brng]) ? $resep_pulang[$kode_brng] : 0;
    $total_keluar = $keluar_pemberian + $keluar_resep;

    // Stok akhir (perbaikan rumus)
    $stok_akhir = ($total_stok_awal + $total_masuk) - $total_keluar;

    echo "<tr>
        <td>$no</td>
        <td>$kode_brng</td>
        <td>{$barang['nama_brng']}</td>
        <td>{$barang['kode_sat']}</td>
        <td>{$barang['kode_golongan']}</td>
        <td>$go</td>
        <td>$dri</td>
        <td>$ap</td>
        <td>$di</td>
        <td>$do</td>
        <td>$de</td>
        <td>$total_stok_awal</td>
        <td>$masuk_penerimaan</td>
        <td>$masuk_hibah</td>
        <td>$masuk_retur</td>
        <td>$total_masuk</td>
        <td>$keluar_pemberian</td>
        <td>$keluar_resep</td>
        <td>$total_keluar</td>
        <td>$stok_akhir</td>
    </tr>";
    $no++;
}
            ?>
        </tbody>
    </table>
    </div>

    <script>
    document.getElementById('copyTableBtn').onclick = function() {
        var tableDiv = document.getElementById('sipnapTable');
        var range = document.createRange();
        range.selectNode(tableDiv);
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