<?php
// filepath: c:\xampp\htdocs\rsudpringsewu\skpfarmasi.php
include 'koneksi.php';

// Ambil tanggal filter, default hari ini jika belum dipilih
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Fungsi untuk membuat array tanggal pada periode
function getDateRange($start, $end) {
    $dates = [];
    $current = strtotime($start);
    $end = strtotime($end);
    while ($current <= $end) {
        $dates[] = date('Y-m-d', $current);
        $current = strtotime('+1 day', $current);
    }
    return $dates;
}
$periode = getDateRange($tanggal_awal, $tanggal_akhir);

// --- Query dan proses data untuk setiap tabel ---
// Tambahkan pengecekan error pada setiap query agar mudah debug

// 1. Permintaan Barang dari Depo Rawat Inap
$data1 = array_fill_keys($periode, 0);
$q1 = "SELECT tanggal, COUNT(*) as jumlah FROM permintaan_medis WHERE kd_bangsal = 'dri' AND tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir' GROUP BY tanggal";
$r1 = mysqli_query($koneksi, $q1);
if (!$r1) die("Query error 1: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r1)) {
    $data1[$row['tanggal']] = $row['jumlah'];
}

// 2. Permintaan Barang ke Gudang Obat
$data2 = array_fill_keys($periode, 0);
$q2 = "SELECT tanggal, COUNT(*) as jumlah FROM permintaan_medis WHERE kd_bangsaltujuan = 'go' AND tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir' GROUP BY tanggal";
$r2 = mysqli_query($koneksi, $q2);
if (!$r2) die("Query error 2: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r2)) {
    $data2[$row['tanggal']] = $row['jumlah'];
}

// 3. Rekap Penerimaan Barang per Tanggal
$data3 = array_fill_keys($periode, 0);
$q3 = "SELECT tgl_pesan, COUNT(*) as jumlah FROM pemesanan WHERE tgl_pesan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' GROUP BY tgl_pesan";
$r3 = mysqli_query($koneksi, $q3);
if (!$r3) die("Query error 3: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r3)) {
    $data3[$row['tgl_pesan']] = $row['jumlah'];
}

// 4. Resep depo Rawat Inap (08.00 - 14.00)
$data4 = array_fill_keys($periode, 0);
$q4 = "SELECT tgl_perawatan, COUNT(*) as jumlah FROM resep_obat WHERE tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND jam BETWEEN '08:00:01' AND '14:00:00' AND status = 'ranap' GROUP BY tgl_perawatan";
$r4 = mysqli_query($koneksi, $q4);
if (!$r4) die("Query error 4: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r4)) {
    $data4[$row['tgl_perawatan']] = $row['jumlah'];
}

// 5. Resep depo Rawat Jalan (08.00 - 14.00)
$data5 = array_fill_keys($periode, 0);
$q5 = "SELECT tgl_perawatan, COUNT(*) as jumlah FROM resep_obat WHERE tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND jam BETWEEN '08:00:01' AND '14:00:00' AND status = 'ralan' GROUP BY tgl_perawatan";
$r5 = mysqli_query($koneksi, $q5);
if (!$r5) die("Query error 5: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r5)) {
    $data5[$row['tgl_perawatan']] = $row['jumlah'];
}

// 6. PIO depo Rawat Inap
$data6 = array_fill_keys($periode, 0);
$q6 = "SELECT tgl_perawatan, COUNT(*) as jumlah FROM rawat_inap_pr WHERE tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND kd_jenis_prw = 'T. FARMASIKL.1' GROUP BY tgl_perawatan";
$r6 = mysqli_query($koneksi, $q6);
if (!$r6) die("Query error 6: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r6)) {
    $data6[$row['tgl_perawatan']] = $row['jumlah'];
}

// 7. PIO depo Rawat Jalan
$data7 = array_fill_keys($periode, 0);
$q7 = "SELECT tgl_perawatan, COUNT(*) as jumlah FROM rawat_jl_pr WHERE tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND kd_jenis_prw = 'FARMASI24' GROUP BY tgl_perawatan";
$r7 = mysqli_query($koneksi, $q7);
if (!$r7) die("Query error 7: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r7)) {
    $data7[$row['tgl_perawatan']] = $row['jumlah'];
}

// 8. Resep Racikan Depo Rawat Inap
$data8 = array_fill_keys($periode, 0);
$q8 = "SELECT resep_obat.tgl_perawatan, COUNT(*) as jumlah FROM resep_obat INNER JOIN resep_dokter_racikan ON resep_dokter_racikan.no_resep = resep_obat.no_resep WHERE resep_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND resep_obat.status = 'ranap' GROUP BY resep_obat.tgl_perawatan";
$r8 = mysqli_query($koneksi, $q8);
if (!$r8) die("Query error 8: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r8)) {
    $data8[$row['tgl_perawatan']] = $row['jumlah'];
}

// 9. Resep Racikan Depo Rawat Jalan
$data9 = array_fill_keys($periode, 0);
$q9 = "SELECT resep_obat.tgl_perawatan, COUNT(*) as jumlah FROM resep_obat INNER JOIN resep_dokter_racikan ON resep_dokter_racikan.no_resep = resep_obat.no_resep WHERE resep_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND resep_obat.status = 'ralan' GROUP BY resep_obat.tgl_perawatan";
$r9 = mysqli_query($koneksi, $q9);
if (!$r9) die("Query error 9: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r9)) {
    $data9[$row['tgl_perawatan']] = $row['jumlah'];
}

// 10. Mutasi Masuk dari Gudang Obat ke Depo Rawat Jalan
$data10 = [];
$data10 = array_fill_keys($periode, 0);
$q10 = "SELECT DATE(mutasibarang.tanggal) AS tgl, COUNT(*) as jumlah
FROM mutasibarang
WHERE mutasibarang.kd_bangsalke = 'AP' 
  AND mutasibarang.kd_bangsaldari = 'GO' 
  AND mutasibarang.tanggal BETWEEN '$tanggal_awal 00:00:00' AND '$tanggal_akhir 23:59:59'
GROUP BY tgl";
$r10 = mysqli_query($koneksi, $q10);
if (!$r10) die("Query error 10: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r10)) {
    $data10[$row['tgl']] = $row['jumlah'];
}

// 11. Penerimaan Barang ke Gudang Farmasi
$data11 = array_fill_keys($periode, 0);
$q11 = "SELECT pemesanan.tgl_pesan, COUNT(detailpesan.kode_brng) as jumlah
FROM pemesanan
INNER JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
WHERE pemesanan.tgl_pesan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY pemesanan.tgl_pesan
ORDER BY pemesanan.tgl_pesan ASC";
$r11 = mysqli_query($koneksi, $q11);
if (!$r11) die("Query error 11: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r11)) {
    $data11[$row['tgl_pesan']] = $row['jumlah'];
}

// 12. Stok Keluar Gudang Farmasi
$data12 = array_fill_keys($periode, 0);
$q12 = "SELECT pengeluaran_obat_bhp.tanggal, COUNT(detail_pengeluaran_obat_bhp.kode_brng) as jumlah
FROM pengeluaran_obat_bhp
INNER JOIN detail_pengeluaran_obat_bhp ON detail_pengeluaran_obat_bhp.no_keluar = pengeluaran_obat_bhp.no_keluar
WHERE pengeluaran_obat_bhp.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY pengeluaran_obat_bhp.tanggal
ORDER BY pengeluaran_obat_bhp.tanggal ASC";
$r12 = mysqli_query($koneksi, $q12);
if (!$r12) die("Query error 12: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r12)) {
    $data12[$row['tanggal']] = $row['jumlah'];
}

// 13. Mutasi dari Gudang Farmasi (format tanggal+jam, tampilkan tanggal dan jumlah)
$data13 = array_fill_keys($periode, 0);
$q13 = "SELECT DATE(mutasibarang.tanggal) AS tgl, COUNT(mutasibarang.kode_brng) as jumlah
FROM mutasibarang
WHERE mutasibarang.kd_bangsaldari = 'go' 
  AND mutasibarang.tanggal BETWEEN '$tanggal_awal 00:00:00' AND '$tanggal_akhir 23:59:59'
GROUP BY tgl
ORDER BY tgl ASC";
$r13 = mysqli_query($koneksi, $q13);
if (!$r13) die("Query error 13: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r13)) {
    $data13[$row['tgl']] = $row['jumlah'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SKP Farmasi</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h2 { margin-top: 30px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #ccc; padding: 6px 10px; font-size: 13px; }
        th { background: #4CAF50; color: #fff; }
        .filter-form { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        .filter-form input { padding: 8px; margin-right: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-form button { padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form button:hover { background: #45a049; }
        #copyTableBtn { margin-bottom:15px; padding:8px 15px; background:#2196F3; color:#fff; border:none; border-radius:4px; cursor:pointer; }
        #copyTableBtn:hover { background:#1976D2; }
    </style>
</head>
<body>
    <div id="allTables">
    <h1>SKP Farmasi</h1>
    <form method="POST" class="filter-form">
        Periode :
        <input type="date" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
        <button type="submit" name="filter">Tampilkan</button>
    </form>

    <div class="back-button">
        <a href="farmasi.php">Kembali ke Menu Farmasi</a>
    </div>

    <button id="copyTableBtn">Copy Semua Tabel ke Clipboard</button>

    <h2>1. Permintaan Barang dari Depo Rawat Inap</h2>
    <table class="skp-table">
        <thead>
            <tr><th>Tanggal</th><th>Jumlah Permintaan</th></tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data1[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>2. Permintaan Barang ke Gudang Obat</h2>
    <table class="skp-table">
        <thead>
            <tr><th>Tanggal</th><th>Jumlah Permintaan</th></tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data2[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>3. Rekap Penerimaan Barang per Tanggal</h2>
    <table class="skp-table">
        <thead>
            <tr><th>Tanggal</th><th>Jumlah Penerimaan</th></tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data3[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>4. Resep depo Rawat Inap (08.00 - 14.00)</h2>
    <table class="skp-table">
        <thead>
            <tr><th>Tanggal</th><th>Jumlah Resep</th></tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data4[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>5. Resep depo Rawat Jalan (08.00 - 14.00)</h2>
    <table class="skp-table">
        <thead>
            <tr><th>Tanggal</th><th>Jumlah Resep</th></tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data5[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>6. PIO depo Rawat Inap</h2>
    <table class="skp-table">
        <thead>
            <tr><th>Tanggal</th><th>Jumlah PIO</th></tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data6[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>7. PIO depo Rawat Jalan</h2>
    <table class="skp-table">
        <thead>
            <tr><th>Tanggal</th><th>Jumlah PIO</th></tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data7[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>8. Resep Racikan Depo Rawat Inap</h2>
    <table class="skp-table">
        <thead>
            <tr><th>Tanggal</th><th>Jumlah Racikan</th></tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data8[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>9. Resep Racikan Depo Rawat Jalan</h2>
    <table class="skp-table">
        <thead>
            <tr><th>Tanggal</th><th>Jumlah Racikan</th></tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data9[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>10. Mutasi Masuk dari Gudang Obat ke Depo Rawat Jalan</h2>
    <table class="skp-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jumlah Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
                <tr>
                    <td><?php echo $tgl; ?></td>
                    <td><?php echo $data10[$tgl]; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>11. Penerimaan Barang ke Gudang Farmasi</h2>
    <table class="skp-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jumlah Penerimaan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data11[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>12. Stok Keluar Gudang Farmasi</h2>
    <table class="skp-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jumlah Keluar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data12[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>13. Mutasi dari Gudang Farmasi</h2>
    <table class="skp-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jumlah Mutasi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($periode as $tgl): ?>
            <tr>
                <td><?php echo $tgl; ?></td>
                <td><?php echo $data13[$tgl]; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

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
            alert('Semua tabel berhasil disalin ke clipboard!');
        } else {
            alert('Gagal menyalin tabel.');
        }
    } catch (err) {
        alert('Browser tidak mendukung copy tabel otomatis.');
    }
    window.getSelection().removeAllRanges();
};
    </script>
    </div>
</body>
</html>