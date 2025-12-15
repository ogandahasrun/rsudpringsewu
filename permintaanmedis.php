<?php
include 'koneksi.php';

// Jika request AJAX detail permintaan
if (isset($_GET['detail'])) {
    $no_permintaan = mysqli_real_escape_string($koneksi, $_GET['detail']);
    $q = "SELECT
        permintaan_medis.no_permintaan,
        detail_permintaan_medis.kode_brng,
        databarang.nama_brng,
        kodesatuan.satuan,
        detail_permintaan_medis.jumlah
    FROM permintaan_medis
    INNER JOIN detail_permintaan_medis ON detail_permintaan_medis.no_permintaan = permintaan_medis.no_permintaan
    INNER JOIN kodesatuan ON detail_permintaan_medis.kode_sat = kodesatuan.kode_sat
    INNER JOIN databarang ON detail_permintaan_medis.kode_brng = databarang.kode_brng
    WHERE permintaan_medis.no_permintaan = '$no_permintaan'
    ORDER BY databarang.kode_brng ASC, databarang.nama_brng ASC";
    $res = mysqli_query($koneksi, $q);
    echo '<table><tr><th>Kode</th><th>Nama Barang</th><th>Satuan</th><th>Jumlah</th></tr>';
    if ($res && mysqli_num_rows($res) > 0) {
        while ($r = mysqli_fetch_assoc($res)) {
            echo '<tr><td>' . htmlspecialchars($r['kode_brng']) . '</td><td>' . htmlspecialchars($r['nama_brng']) . '</td><td>' . htmlspecialchars($r['satuan']) . '</td><td>' . htmlspecialchars($r['jumlah']) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan=4 style="text-align:center;color:#888;font-style:italic;">Tidak ada rincian</td></tr>';
    }
    echo '</table>';
    exit;
}

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Query daftar permintaan
$sql = "SELECT
    permintaan_medis.tanggal,
    permintaan_medis.no_permintaan,
    permintaan_medis.kd_bangsal,
    permintaan_medis.kd_bangsaltujuan,
    permintaan_medis.status,
    pegawai.nama,
    b1.nm_bangsal AS nm_bangsal_asal,
    b2.nm_bangsal AS nm_bangsal_tujuan
FROM permintaan_medis
INNER JOIN bangsal b1 ON permintaan_medis.kd_bangsal = b1.kd_bangsal
INNER JOIN bangsal b2 ON permintaan_medis.kd_bangsaltujuan = b2.kd_bangsal
INNER JOIN pegawai ON permintaan_medis.nip = pegawai.nik
WHERE permintaan_medis.tanggal BETWEEN '".mysqli_real_escape_string($koneksi, $tgl_awal)."' AND '".mysqli_real_escape_string($koneksi, $tgl_akhir)."'
ORDER BY permintaan_medis.status ASC, permintaan_medis.no_permintaan ASC";
$result = mysqli_query($koneksi, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Permintaan Medis</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .container { background: #fff; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); max-width: 98vw; margin: auto; padding: 30px; }
        h1 { text-align: center; color: #007bff; margin-bottom: 20px; }
        form { background: #f1f3f6; padding: 18px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 18px; align-items: flex-end; }
        label { font-weight: bold; color: #333; margin-bottom: 5px; }
        input { padding: 8px 12px; border-radius: 6px; border: 1.5px solid #e0e0e0; font-size: 14px; }
        button { padding: 10px 22px; border-radius: 6px; border: none; background: linear-gradient(45deg,#007bff,#00c6ff); color: #fff; font-weight: bold; cursor: pointer; transition: 0.2s; }
        button:hover { background: linear-gradient(45deg,#0056b3,#007bff); }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 10px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        th { background: linear-gradient(45deg,#007bff,#00c6ff); color: #fff; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e3f2fd; }
        .nowrap { white-space: nowrap; }
        .link { color: #007bff; text-decoration: underline; cursor: pointer; }
        .modal-bg { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.3); z-index:99; }
        .modal { background:#fff; border-radius:8px; max-width:350px; width:95vw; margin:60px auto; padding:18px 12px; box-shadow:0 8px 32px rgba(0,0,0,0.18); position:relative; }
        .modal h2 { font-size:16px; margin:0 0 10px 0; color:#007bff; }
        .modal table { width:100%; font-size:12px; }
        .modal th, .modal td { padding:4px 6px; }
        .close-modal { position:absolute; top:8px; right:12px; font-size:18px; color:#dc3545; background:none; border:none; cursor:pointer; }
        .btn-thermal { background:#222; color:#fff; border-radius:5px; padding:6px 14px; font-size:13px; margin-top:10px; }
        @media (max-width: 900px) { .container { padding: 8px; } th, td { font-size: 12px; } }
    </style>
</head>
<body>
<div class="container">
    <h1>Daftar Permintaan Medis</h1>
    <form method="get">
        <div>
            <label>Periode Tanggal</label><br>
            <input type="date" name="tgl_awal" value="<?= htmlspecialchars($tgl_awal) ?>"> -
            <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>">
        </div>
        <div>
            <button type="submit">🔍 Tampilkan</button>
        </div>
    </form>
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>No Permintaan</th>
                <th>Bangsal Asal</th>
                <th>Bangsal Tujuan</th>
                <th>Status</th>
                <th>Nama Pegawai</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<tr>';
                echo '<td class="nowrap">' . $no . '</td>';
                echo '<td class="nowrap">' . htmlspecialchars($row['tanggal']) . '</td>';
                echo '<td class="nowrap"><span class="link" onclick="showDetail(\'' . htmlspecialchars($row['no_permintaan']) . '\')">' . htmlspecialchars($row['no_permintaan']) . '</span></td>';
                echo '<td class="nowrap">' . htmlspecialchars($row['nm_bangsal_asal']) . '</td>';
                echo '<td class="nowrap">' . htmlspecialchars($row['nm_bangsal_tujuan']) . '</td>';
                echo '<td class="nowrap">' . htmlspecialchars($row['status']) . '</td>';
                echo '<td class="nowrap">' . htmlspecialchars($row['nama']) . '</td>';
                echo '</tr>';
                $no++;
            }
        } else {
            echo '<tr><td colspan="7" style="text-align:center;color:#888;font-style:italic;">Tidak ada data ditemukan</td></tr>';
        }
        ?>
        </tbody>
    </table>
    </div>
</div>
<div class="modal-bg" id="modalBg">
    <div class="modal" id="modalDetail">
        <button class="close-modal" onclick="closeModal()">&times;</button>
        <h2>Rincian Permintaan</h2>
        <div id="modalContent">Loading...</div>
        <button class="btn-thermal" onclick="printThermal()">🖨️ Cetak Thermal 58mm</button>
    </div>
</div>
<script>
function showDetail(no_permintaan) {
    document.getElementById('modalBg').style.display = 'block';
    document.getElementById('modalContent').innerHTML = 'Loading...';
    fetch('permintaanmedis.php?detail=' + encodeURIComponent(no_permintaan))
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalContent').innerHTML = html;
        });
}
function closeModal() {
    document.getElementById('modalBg').style.display = 'none';
}
function printThermal() {
    var printContents = document.getElementById('modalContent').innerHTML;
    var win = window.open('', '', 'width=320,height=600');
    win.document.write('<html><head><title>Cetak Thermal</title>');
    win.document.write('<style>body{font-family:Tahoma,monospace;font-size:11px;margin:0;padding:0;}table{width:100%;border-collapse:collapse;}th,td{padding:2px 4px;}th{background:#eee;}</style>');
    win.document.write('</head><body>' + printContents + '</body></html>');
    win.document.close();
    win.focus();
    win.print();
    setTimeout(function(){win.close();}, 500);
}
// ...existing code...
</script>
</body>
</html>
