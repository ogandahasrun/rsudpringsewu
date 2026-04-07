<?php
session_start();
include 'koneksi.php';
include 'functions.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Ambil tanggal awal dan akhir dari form, default hari ini
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Query data
$sql = "SELECT reg_periksa.tgl_registrasi, reg_periksa.no_rawat, pasien.no_rkm_medis, pasien.nm_pasien, penjab.png_jawab, databarang.kode_brng, databarang.nama_brng, databarang.kode_sat, detail_pemberian_obat.jml, detail_pemberian_obat.total FROM reg_periksa INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj INNER JOIN detail_pemberian_obat ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng WHERE reg_periksa.status_lanjut = 'ralan' AND reg_periksa.tgl_registrasi BETWEEN '$tgl_awal' AND '$tgl_akhir' ORDER BY reg_periksa.tgl_registrasi, reg_periksa.no_rawat, databarang.kode_brng";
$result = mysqli_query($koneksi, $sql);

// Proses data untuk tampilan merge kolom
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $key = $row['tgl_registrasi'] . '|' . $row['no_rawat'];
    if (!isset($data[$key])) {
        $data[$key] = [
            'tgl_registrasi' => $row['tgl_registrasi'],
            'no_rawat' => $row['no_rawat'],
            'no_rkm_medis' => $row['no_rkm_medis'],
            'nm_pasien' => $row['nm_pasien'],
            'png_jawab' => $row['png_jawab'],
            'obat' => []
        ];
    }
    $data[$key]['obat'][] = [
        'kode_brng' => $row['kode_brng'],
        'nama_brng' => $row['nama_brng'],
        'kode_sat' => $row['kode_sat'],
        'jml' => $row['jml'],
        'total' => $row['total']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPN Obat Pasien Ralan - RSUD Pringsewu</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .filter-form {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 25px;
        }
        .filter-form input[type="date"] {
            padding: 7px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        .filter-form button {
            background: #667eea;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .filter-form button:hover {
            background: #764ba2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #fff;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background: #667eea;
            color: #fff;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>PPN Obat Pasien Ralan</h1>
    <div class="subtitle">Laporan detail obat pasien rawat jalan beserta perhitungan PPN</div>
    <form class="filter-form" method="get">
        <label>Dari: <input type="date" name="tgl_awal" value="<?php echo $tgl_awal; ?>"></label>
        <label>Sampai: <input type="date" name="tgl_akhir" value="<?php echo $tgl_akhir; ?>"></label>
        <button type="submit">Tampilkan</button>
    </form>
    <div style="display:flex; gap:10px; margin-bottom:18px;">
        <button onclick="copyTableToClipboard()" style="background:#667eea;color:#fff;border:none;padding:8px 18px;border-radius:6px;font-size:15px;cursor:pointer;transition:background 0.2s;">Copy to Clipboard</button>
        <a href="keuangan.php" style="background:#aaa;color:#fff;border:none;padding:8px 18px;border-radius:6px;font-size:15px;text-decoration:none;display:inline-block;line-height:28px;">Kembali ke Keuangan</a>
    </div>
    <table id="tabelppn">
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl Registrasi</th>
                <th>No Rawat</th>
                <th>No RM</th>
                <th>Nama Pasien</th>
                <th>PJ</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Jml</th>
                <th>Total</th>
                <th>PPN</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        foreach ($data as $key => $row) {
            $rowspan = count($row['obat']);
            $first = true;
            foreach ($row['obat'] as $obat) {
                echo '<tr>';
                if ($first) {
                    echo '<td rowspan="'.$rowspan.'">'.$no.'</td>';
                    echo '<td rowspan="'.$rowspan.'">'.$row['tgl_registrasi'].'</td>';
                    echo '<td rowspan="'.$rowspan.'">'.$row['no_rawat'].'</td>';
                    echo '<td rowspan="'.$rowspan.'">'.$row['no_rkm_medis'].'</td>';
                    echo '<td rowspan="'.$rowspan.'">'.$row['nm_pasien'].'</td>';
                    echo '<td rowspan="'.$rowspan.'">'.$row['png_jawab'].'</td>';
                    $first = false;
                    $no++;
                }
                echo '<td>'.$obat['kode_brng'].'</td>';
                echo '<td>'.$obat['nama_brng'].'</td>';
                echo '<td>'.$obat['kode_sat'].'</td>';
                echo '<td>'.$obat['jml'].'</td>';
                echo '<td>'.number_format($obat['total'],2,',','.').'</td>';
                echo '<td>'.number_format($obat['total']*0.11,2,',','.').'</td>';
                echo '</tr>';
            }
        }
        ?>
        </tbody>
    </table>
</div>
<script>
function copyTableToClipboard() {
    var table = document.getElementById('tabelppn');
    var range, sel;
    if (document.createRange && window.getSelection) {
        var body = document.body, html = document.documentElement;
        var prevActive = document.activeElement;
        range = document.createRange();
        range.selectNode(table);
        sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
        try {
            document.execCommand('copy');
        } catch (e) {}
        sel.removeAllRanges();
        if (prevActive) prevActive.focus();
        alert('Tabel berhasil disalin ke clipboard!');
    }
}
</script>
</body>
</html>
