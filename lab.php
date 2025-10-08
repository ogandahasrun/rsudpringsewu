<?php

include 'koneksi.php';

// Timezone (optional, ensures consistent "today")
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Jakarta');
}

// Helper: validate Y-m-d date
function valid_tanggal($v) {
    $dt = DateTime::createFromFormat('Y-m-d', (string)$v);
    return $dt && $dt->format('Y-m-d') === $v;
}

// Ambil filter tanggal dari form, default hari ini
$tanggal_awal  = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Sanitasi dasar: fallback ke hari ini jika format tidak valid
if (!valid_tanggal($tanggal_awal))  { $tanggal_awal  = date('Y-m-d'); }
if (!valid_tanggal($tanggal_akhir)) { $tanggal_akhir = date('Y-m-d'); }

// Jika rentang terbalik, tukar
if ($tanggal_awal > $tanggal_akhir) {
    [$tanggal_awal, $tanggal_akhir] = [$tanggal_akhir, $tanggal_awal];
}

// Query sesuai permintaan (hanya pasien L), pakai prepared statement
// Catatan: gunakan filter inclusive-exclusive agar aman untuk kolom DATE maupun DATETIME
//   d.tgl_periksa >= ? AND d.tgl_periksa < DATE_ADD(?, INTERVAL 1 DAY)
$sql = "
    SELECT
        j.nm_perawatan,
        t.Pemeriksaan,
        COUNT(p.jk) AS Jumlah_L
    FROM detail_periksa_lab d
    INNER JOIN reg_periksa r ON d.no_rawat = r.no_rawat
    INNER JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    INNER JOIN template_laboratorium t ON d.id_template = t.id_template
    INNER JOIN jns_perawatan_lab j ON d.kd_jenis_prw = j.kd_jenis_prw
    WHERE d.tgl_periksa >= ?
      AND d.tgl_periksa < DATE_ADD(?, INTERVAL 1 DAY)
      AND p.jk = 'L'
    GROUP BY t.Pemeriksaan, j.nm_perawatan
    ORDER BY j.nm_perawatan ASC, t.urut ASC
";

$result = false;
if ($stmt = mysqli_prepare($koneksi, $sql)) {
    mysqli_stmt_bind_param($stmt, 'ss', $tanggal_awal, $tanggal_akhir);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
    } else {
        // Opsional: tampilkan error eksekusi saat debug
        // echo 'Query execute error: ' . htmlspecialchars(mysqli_error($koneksi));
    }
    mysqli_stmt_close($stmt);
} else {
    // Opsional: tampilkan error prepare saat debug
    // echo 'Query prepare error: ' . htmlspecialchars(mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laboratorium (Laki-laki)</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        h1 { text-align: center; color: #2e7d32; }
        .filter-form { margin: 20px auto; max-width: 500px; background: #fff; padding: 16px; border-radius: 8px; }
        .filter-form input, .filter-form button { padding: 8px; margin-right: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .filter-form button { background: #2e7d32; color: #fff; border: none; }
        table { border-collapse: collapse; width: 100%; background: #fff; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #388e3c; color: #fff; }
        tr:nth-child(even) { background: #f2f2f2; }
        .no-data { text-align: center; color: #888; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Laporan Laboratorium (Laki-laki)</h1>
    <form method="POST" class="filter-form">
        Filter Tanggal Pemeriksaan:
        <input type="date" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
        <button type="submit" name="filter">Tampilkan</button>
    </form>

    <?php
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<table>";
        echo "<tr>
                <th>No</th>
                <th>Nama Pemeriksaan</th>
                <th>Pemeriksaan</th>
                <th>Jumlah L</th>
            </tr>";
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>{$no}</td>
                    <td>" . htmlspecialchars($row['nm_perawatan']) . "</td>
                    <td>" . htmlspecialchars($row['Pemeriksaan']) . "</td>
                    <td>{$row['Jumlah_L']}</td>
                </tr>";
            $no++;
        }
        echo "</table>";
    } else {
        echo "<div class='no-data'>Tidak ada data untuk periode ini.</div>";
        }
    ?>
</body>
</html>