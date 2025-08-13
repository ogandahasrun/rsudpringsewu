<?php

include 'koneksi.php';

// Ambil filter tanggal dari form, default hari ini
$tanggal_awal  = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Query sesuai permintaan (hanya pasien L)
$query = "
    SELECT
        jns_perawatan_lab.nm_perawatan,
        template_laboratorium.Pemeriksaan,
        COUNT(pasien.jk) AS Jumlah_L
    FROM detail_periksa_lab
    INNER JOIN reg_periksa ON detail_periksa_lab.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN template_laboratorium ON detail_periksa_lab.id_template = template_laboratorium.id_template
    INNER JOIN jns_perawatan_lab ON detail_periksa_lab.kd_jenis_prw = jns_perawatan_lab.kd_jenis_prw
    WHERE detail_periksa_lab.tgl_periksa BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
      AND pasien.jk = 'L'
    GROUP BY template_laboratorium.Pemeriksaan, jns_perawatan_lab.nm_perawatan
    ORDER BY jns_perawatan_lab.nm_perawatan ASC, template_laboratorium.urut ASC
";
$result = mysqli_query($koneksi, $query);
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