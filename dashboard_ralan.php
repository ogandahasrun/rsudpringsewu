<?php
include 'koneksi.php';
$tgl_today = date('Y-m-d');
// Query rekap kunjungan pasien ralan hari ini
$sql = "SELECT
    reg_periksa.tgl_registrasi,
    reg_periksa.no_rawat,
    poliklinik.nm_poli,
    pasien.jk,
    dokter.nm_dokter
FROM
    reg_periksa
INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
WHERE reg_periksa.tgl_registrasi = '$tgl_today'";
$result = mysqli_query($koneksi, $sql);
// Rekap by poli, dokter, jk
$rekap_poli = [];
$rekap_dokter = [];
$rekap_jk = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Rekap poli
        $key_poli = $row['nm_poli'];
        if (!isset($rekap_poli[$key_poli])) $rekap_poli[$key_poli] = 0;
        $rekap_poli[$key_poli]++;
        // Rekap dokter
        $key_dokter = $row['nm_dokter'];
        if (!isset($rekap_dokter[$key_dokter])) $rekap_dokter[$key_dokter] = 0;
        $rekap_dokter[$key_dokter]++;
        // Rekap jenis kelamin
        $key_jk = $row['jk'];
        if (!isset($rekap_jk[$key_jk])) $rekap_jk[$key_jk] = 0;
        $rekap_jk[$key_jk]++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kunjungan Rawat Jalan - RSUD Pringsewu</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .container { background: #fff; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); max-width: 98vw; margin: auto; padding: 30px; }
        h1 { text-align: center; color: #007bff; margin-bottom: 20px; }
        .summary { text-align:center; margin-bottom:18px; color:#333; font-size:16px; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 10px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        th { background: linear-gradient(45deg,#007bff,#00c6ff); color: #fff; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e3f2fd; }
        .nowrap { white-space: nowrap; }
        @media (max-width: 900px) { .container { padding: 8px; } th, td { font-size: 12px; } }
    </style>
</head>
<body>
<div class="container">
    <h1>Dashboard Kunjungan Rawat Jalan</h1>
    <div class="summary">Rekap kunjungan pasien hari ini: <b><?= date('d/m/Y', strtotime($tgl_today)) ?></b></div>
    <h2 style="margin-top:0;color:#007bff;font-size:20px;">Rekap Berdasarkan Poliklinik</h2>
    <table>
        <thead>
            <tr>
                <th>Poliklinik</th>
                <th>Jumlah Kunjungan</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($rekap_poli)): foreach ($rekap_poli as $nm_poli => $jumlah): ?>
            <tr>
                <td><?= htmlspecialchars($nm_poli) ?></td>
                <td style="text-align:center;font-weight:bold;"><?= $jumlah ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="2" style="text-align:center;color:#888;font-style:italic;">Tidak ada kunjungan hari ini</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h2 style="margin-top:32px;color:#007bff;font-size:20px;">Rekap Berdasarkan Dokter</h2>
    <table>
        <thead>
            <tr>
                <th>Nama Dokter</th>
                <th>Jumlah Kunjungan</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($rekap_dokter)): foreach ($rekap_dokter as $nm_dokter => $jumlah): ?>
            <tr>
                <td><?= htmlspecialchars($nm_dokter) ?></td>
                <td style="text-align:center;font-weight:bold;"><?= $jumlah ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="2" style="text-align:center;color:#888;font-style:italic;">Tidak ada kunjungan hari ini</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h2 style="margin-top:32px;color:#007bff;font-size:20px;">Rekap Berdasarkan Jenis Kelamin</h2>
    <table>
        <thead>
            <tr>
                <th>Jenis Kelamin</th>
                <th>Jumlah Kunjungan</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($rekap_jk)): foreach ($rekap_jk as $jk => $jumlah): ?>
            <tr>
                <td><?= htmlspecialchars($jk) ?></td>
                <td style="text-align:center;font-weight:bold;"><?= $jumlah ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="2" style="text-align:center;color:#888;font-style:italic;">Tidak ada kunjungan hari ini</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
