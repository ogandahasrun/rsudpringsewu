<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$today = date('Y-m-d');
$nm_dokter = isset($_GET['nm_dokter']) ? $_GET['nm_dokter'] : '';

if (empty($nm_dokter)) {
    echo "<div class='no-data'>Silakan pilih dokter.</div>";
    exit;
}

$sql = "SELECT
            reg_periksa.no_reg,
            pasien.nm_pasien
        FROM
            reg_periksa
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
        WHERE
            dokter.status = '1'
            AND reg_periksa.stts = 'Belum'
            AND reg_periksa.tgl_registrasi = ?
            AND dokter.nm_dokter = ?
        GROUP BY reg_periksa.no_reg";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("ss", $today, $nm_dokter);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<thead><tr><th>Nomor Registrasi</th><th>Nama Pasien</th></tr></thead><tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".htmlspecialchars($row['no_reg'])."</td>";
        echo "<td>".htmlspecialchars($row['nm_pasien'])."</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<div class='no-data'>Tidak ada pasien hari ini.</div>";
}
