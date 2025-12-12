<?php
include 'koneksi.php';
// Ambil filter
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$operator1 = isset($_GET['operator1']) ? $_GET['operator1'] : '';
$dokter_anestesi = isset($_GET['dokter_anestesi']) ? $_GET['dokter_anestesi'] : '';

// Ambil data dokter untuk filter
$dokter_list = [];
$dokter_q = mysqli_query($koneksi, "SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter");
while ($d = mysqli_fetch_assoc($dokter_q)) {
    $dokter_list[] = $d;
}

// Build WHERE
$where = [];
$where[] = "operasi.tgl_operasi BETWEEN '" . mysqli_real_escape_string($koneksi, $tgl_awal) . "' AND '" . mysqli_real_escape_string($koneksi, $tgl_akhir) . "'";
$where[] = "operasi.kode_paket IN ('BPJSOP178','LECOP178')";
if ($operator1 !== '') {
    $where[] = "operasi.operator1 = '" . mysqli_real_escape_string($koneksi, $operator1) . "'";
}
if ($dokter_anestesi !== '') {
    $where[] = "operasi.dokter_anestesi = '" . mysqli_real_escape_string($koneksi, $dokter_anestesi) . "'";
}
$where_sql = implode(' AND ', $where);

// Query utama
$sql = "SELECT
    operasi.tgl_operasi,
    reg_periksa.no_rawat,
    pasien.no_rkm_medis,
    pasien.nm_pasien,
    laporan_operasi.diagnosa_preop,
    paket_operasi.nm_perawatan,
    operasi.operator1,
    operasi.dokter_anestesi,
    d1.nm_dokter AS nm_operator1,
    d2.nm_dokter AS nm_dokter_anestesi,
    databarang.kode_brng,
    databarang.nama_brng,
    detail_pemberian_obat.jml
FROM operasi
INNER JOIN reg_periksa ON operasi.no_rawat = reg_periksa.no_rawat
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
LEFT JOIN laporan_operasi ON laporan_operasi.no_rawat = reg_periksa.no_rawat
LEFT JOIN paket_operasi ON operasi.kode_paket = paket_operasi.kode_paket
LEFT JOIN dokter d1 ON operasi.operator1 = d1.kd_dokter
LEFT JOIN dokter d2 ON operasi.dokter_anestesi = d2.kd_dokter
LEFT JOIN detail_pemberian_obat ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat
LEFT JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
WHERE $where_sql
ORDER BY operasi.tgl_operasi DESC, reg_periksa.no_rawat DESC";
$result = mysqli_query($koneksi, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Obat Farmasi Operasi</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .container { background: #fff; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); max-width: 98vw; margin: auto; padding: 30px; }
        h1 { text-align: center; color: #007bff; margin-bottom: 20px; }
        form { background: #f1f3f6; padding: 18px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 18px; align-items: flex-end; }
        label { font-weight: bold; color: #333; margin-bottom: 5px; }
        input, select { padding: 8px 12px; border-radius: 6px; border: 1.5px solid #e0e0e0; font-size: 14px; }
        button { padding: 10px 22px; border-radius: 6px; border: none; background: linear-gradient(45deg,#007bff,#00c6ff); color: #fff; font-weight: bold; cursor: pointer; transition: 0.2s; }
        button:hover { background: linear-gradient(45deg,#0056b3,#007bff); }
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
    <h1>Rekap Obat Farmasi Operasi</h1>
    <form method="get">
        <div>
            <label>Periode Tgl Operasi</label><br>
            <input type="date" name="tgl_awal" value="<?= htmlspecialchars($tgl_awal) ?>"> -
            <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>">
        </div>
        <div>
            <label>Operator 1</label><br>
            <select name="operator1">
                <option value="">Semua</option>
                <?php foreach($dokter_list as $d): ?>
                <option value="<?= htmlspecialchars($d['kd_dokter']) ?>" <?= $operator1==$d['kd_dokter']?'selected':'' ?>><?= htmlspecialchars($d['nm_dokter']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Dokter Anestesi</label><br>
            <select name="dokter_anestesi">
                <option value="">Semua</option>
                <?php foreach($dokter_list as $d): ?>
                <option value="<?= htmlspecialchars($d['kd_dokter']) ?>" <?= $dokter_anestesi==$d['kd_dokter']?'selected':'' ?>><?= htmlspecialchars($d['nm_dokter']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit">🔍 Tampilkan</button>
        </div>
    </form>
    <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;">
        <div></div>
        <button type="button" onclick="copyTableFarmasi()" style="padding:8px 18px;border-radius:6px;background:#28a745;color:#fff;font-weight:bold;border:none;cursor:pointer;">📋 Copy Tabel</button>
    </div>
    <div style="overflow-x:auto;">
    <table id="farmasiTable">
        </div>
        <script>
        function copyTableFarmasi() {
            var table = document.getElementById('farmasiTable');
            if (!table) return;
            var range = document.createRange();
            range.selectNode(table);
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    alert('✅ Tabel berhasil disalin ke clipboard!');
                } else {
                    alert('❌ Gagal menyalin tabel');
                }
            } catch (err) {
                alert('❌ Browser tidak mendukung copy otomatis.');
            }
            selection.removeAllRanges();
        }
        </script>
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl Operasi</th>
                <th>No Rawat</th>
                <th>No RM</th>
                <th>Nama Pasien</th>
                <th>Diagnosa Preop</th>
                <th>Nama Paket Operasi</th>
                <th>Operator 1</th>
                <th>Dokter Anestesi</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jml</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $last_key = '';
        $no = 1;
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $key = $row['tgl_operasi'] . '|' . $row['no_rawat'] . '|' . $row['no_rkm_medis'] . '|' . $row['nm_pasien'] . '|' . $row['diagnosa_preop'] . '|' . $row['nm_perawatan'] . '|' . $row['operator1'] . '|' . $row['dokter_anestesi'];
                echo '<tr>';
                if ($key != $last_key) {
                    echo '<td class="nowrap" rowspan="1">' . $no . '</td>';
                    echo '<td class="nowrap" rowspan="1">' . htmlspecialchars($row['tgl_operasi']) . '</td>';
                    echo '<td class="nowrap" rowspan="1">' . htmlspecialchars($row['no_rawat']) . '</td>';
                    echo '<td class="nowrap" rowspan="1">' . htmlspecialchars($row['no_rkm_medis']) . '</td>';
                    echo '<td class="nowrap" rowspan="1">' . htmlspecialchars($row['nm_pasien']) . '</td>';
                    echo '<td class="nowrap" rowspan="1">' . htmlspecialchars($row['diagnosa_preop']) . '</td>';
                    echo '<td class="nowrap" rowspan="1">' . htmlspecialchars($row['nm_perawatan']) . '</td>';
                    echo '<td class="nowrap" rowspan="1">' . htmlspecialchars($row['nm_operator1']) . '</td>';
                    echo '<td class="nowrap" rowspan="1">' . htmlspecialchars($row['nm_dokter_anestesi']) . '</td>';
                    $no++;
                } else {
                    echo str_repeat('<td class="nowrap"></td>', 9);
                }
                echo '<td class="nowrap">' . htmlspecialchars($row['kode_brng']) . '</td>';
                echo '<td class="nowrap">' . htmlspecialchars($row['nama_brng']) . '</td>';
                echo '<td class="nowrap">' . htmlspecialchars($row['jml']) . '</td>';
                echo '</tr>';
                $last_key = $key;
            }
        } else {
            echo '<tr><td colspan="12" style="text-align:center;color:#888;font-style:italic;">Tidak ada data ditemukan</td></tr>';
        }
        ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>
