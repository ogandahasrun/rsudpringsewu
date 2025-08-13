<?php
include 'koneksi.php';

// Ambil filter tanggal dari form, default hari ini
$tanggal_awal  = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Query ringkas: Group by nm_perawatan & Pemeriksaan
$query = "
    SELECT 
        jns_perawatan_lab.nm_perawatan,
        template_laboratorium.Pemeriksaan,
        COALESCE(SUM(CASE WHEN pasien.jk = 'L' THEN 1 ELSE 0 END), 0) AS L,
        COALESCE(SUM(CASE WHEN pasien.jk = 'P' THEN 1 ELSE 0 END), 0) AS P,
        COUNT(*) AS Jumlah
    FROM detail_periksa_lab
    INNER JOIN reg_periksa ON detail_periksa_lab.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN template_laboratorium ON detail_periksa_lab.id_template = template_laboratorium.id_template
    INNER JOIN jns_perawatan_lab ON detail_periksa_lab.kd_jenis_prw = jns_perawatan_lab.kd_jenis_prw
    WHERE detail_periksa_lab.tgl_periksa BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY jns_perawatan_lab.nm_perawatan, template_laboratorium.Pemeriksaan
    ORDER BY jns_perawatan_lab.nm_perawatan, template_laboratorium.Pemeriksaan
";

$result = mysqli_query($koneksi, $query);

$query_pa = "
    SELECT 
        jns_perawatan_lab.nm_perawatan,
        SUM(CASE WHEN pasien.jk = 'L' THEN 1 ELSE 0 END) AS L,
        SUM(CASE WHEN pasien.jk = 'P' THEN 1 ELSE 0 END) AS P,
        COUNT(*) AS Jumlah
    FROM detail_periksa_labpa
    INNER JOIN reg_periksa ON detail_periksa_labpa.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN jns_perawatan_lab ON detail_periksa_labpa.kd_jenis_prw = jns_perawatan_lab.kd_jenis_prw
    WHERE detail_periksa_labpa.tgl_periksa BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY jns_perawatan_lab.nm_perawatan
    ORDER BY jns_perawatan_lab.nm_perawatan
";
$result_pa = mysqli_query($koneksi, $query_pa);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RL 3.8 Kegiatan Laboratorium</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: green; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { padding: 8px; text-align: center; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        th { background-color: #4CAF50; color: white; }
        .filter-form { margin: 20px 0; padding: 15px; background-color: #f5f5f5; border-radius: 5px; }
        .filter-form input { padding: 8px; margin-right: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-form button { padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form button:hover { background-color: #45a049; }
        .no-data { text-align: center; color: #666; font-style: italic; margin: 20px 0; }
        tfoot { font-weight: bold; background-color: #eee; }
    </style>
</head>
<body>

<h1>RL 3.8 Kegiatan Laboratorium</h1>

<form method="POST" class="filter-form">
    Filter Tanggal Pemeriksaan :
    <input type="date" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>" required>
    <input type="date" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" required>
    <button type="submit" name="filter">Filter</button>
</form>

<h2 style="color:#4CAF50;text-align:center;">Rekapitulasi Patologi Klinik</h2>

<?php
if ($result && mysqli_num_rows($result) > 0) {
    // Variabel total keseluruhan
    $total_L = 0;
    $total_P = 0;
    $total_jumlah = 0;

    echo "<table>";
    echo "<thead>
            <tr>
                <th>No</th>
                <th>Nama Pemeriksaan</th>
                <th>Pemeriksaan</th>
                <th>L</th>
                <th>P</th>
                <th>Jumlah</th>
            </tr>
          </thead>
          <tbody>";

    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td>{$no}</td>
                <td>" . htmlspecialchars($row['nm_perawatan']) . "</td>
                <td>" . htmlspecialchars($row['Pemeriksaan']) . "</td>
                <td>{$row['L']}</td>
                <td>{$row['P']}</td>
                <td>{$row['Jumlah']}</td>
            </tr>";

        // Tambahkan ke total
        $total_L += $row['L'];
        $total_P += $row['P'];
        $total_jumlah += $row['Jumlah'];

        $no++;
    }

    echo "</tbody>";
    echo "<tfoot>
            <tr>
                <td colspan='3'>TOTAL</td>
                <td>{$total_L}</td>
                <td>{$total_P}</td>
                <td>{$total_jumlah}</td>
            </tr>
          </tfoot>";
    echo "</table>";
} else {
    echo "<div class='no-data'>Tidak ada data untuk periode ini.</div>";
}
?>

<br><br>
<h2 style="color:#4CAF50;text-align:center;">Rekapitulasi Patologi Anatomi</h2>
<?php
if ($result_pa && mysqli_num_rows($result_pa) > 0) {
    // Variabel total keseluruhan PA
    $total_L_pa = 0;
    $total_P_pa = 0;
    $total_jumlah_pa = 0;

    echo "<table>";
    echo "<thead>
            <tr>
                <th>No</th>
                <th>Nama Pemeriksaan</th>
                <th>L</th>
                <th>P</th>
                <th>Jumlah</th>
            </tr>
          </thead>
          <tbody>";

    $no_pa = 1;
    while ($row_pa = mysqli_fetch_assoc($result_pa)) {
        echo "<tr>
                <td>{$no_pa}</td>
                <td>" . htmlspecialchars($row_pa['nm_perawatan']) . "</td>
                <td>{$row_pa['L']}</td>
                <td>{$row_pa['P']}</td>
                <td>{$row_pa['Jumlah']}</td>
            </tr>";

        $total_L_pa += $row_pa['L'];
        $total_P_pa += $row_pa['P'];
        $total_jumlah_pa += $row_pa['Jumlah'];

        $no_pa++;
    }

    echo "</tbody>";
    echo "<tfoot>
            <tr>
                <td colspan='2'>TOTAL</td>
                <td>{$total_L_pa}</td>
                <td>{$total_P_pa}</td>
                <td>{$total_jumlah_pa}</td>
            </tr>
          </tfoot>";
    echo "</table>";
} else {
    echo "<div class='no-data'>Tidak ada data Patologi Anatomi untuk periode ini.</div>";
}
?>

</body>
</html>
