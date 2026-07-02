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
$sql = "SELECT 
            penjualan.tgl_jual,
            detailjual.nota_jual,
            detailjual.kode_brng,
            databarang.nama_brng,
            databarang.kode_sat,
            detailjual.h_jual,
            detailjual.jumlah,
            detailjual.subtotal
        FROM 
            penjualan
        INNER JOIN detailjual ON detailjual.nota_jual = penjualan.nota_jual
        INNER JOIN databarang ON detailjual.kode_brng = databarang.kode_brng
        WHERE 
            penjualan.tgl_jual BETWEEN '$tgl_awal' AND '$tgl_akhir'
        ORDER BY 
            penjualan.tgl_jual, detailjual.nota_jual, databarang.nama_brng";

$result = mysqli_query($koneksi, $sql);

// Proses data untuk tampilan merge kolom
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $nota = $row['nota_jual'];
    if (!isset($data[$nota])) {
        $data[$nota] = [
            'tgl_jual' => $row['tgl_jual'],
            'nota_jual' => $row['nota_jual'],
            'items' => [],
            'total_nota_jual' => 0
        ];
    }
    
    $subtotal = $row['subtotal'];
    $ppn = $subtotal * 0.11;
    $total_item = $subtotal + $ppn;
    
    $data[$nota]['items'][] = [
        'kode_brng' => $row['kode_brng'],
        'nama_brng' => $row['nama_brng'],
        'kode_sat' => $row['kode_sat'],
        'h_jual' => $row['h_jual'],
        'jumlah' => $row['jumlah'],
        'subtotal' => $subtotal,
        'ppn' => $ppn,
        'total' => $total_item
    ];
    
    $data[$nota]['total_nota_jual'] += $total_item;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjualan Obat Bebas - RSUD Pringsewu</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1500px;
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
        .filter-form input[type="date"],
        .filter-form select {
            padding: 7px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
            background-color: #fff;
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
    <h1>Laporan Penjualan Obat Bebas</h1>
    <div class="subtitle">Laporan detail penjualan obat bebas beserta perhitungan PPN</div>
    <form class="filter-form" method="get">
        <label>Dari: <input type="date" name="tgl_awal" value="<?php echo $tgl_awal; ?>"></label>
        <label>Sampai: <input type="date" name="tgl_akhir" value="<?php echo $tgl_akhir; ?>"></label>
        <button type="submit">Tampilkan</button>
    </form>
    <div style="display:flex; gap:10px; margin-bottom:18px;">
        <button onclick="copyTableToClipboard()" style="background:#667eea;color:#fff;border:none;padding:8px 18px;border-radius:6px;font-size:15px;cursor:pointer;transition:background 0.2s;">Copy to Clipboard</button>
        <a href="keuangan.php" style="background:#aaa;color:#fff;border:none;padding:8px 18px;border-radius:6px;font-size:15px;text-decoration:none;display:inline-block;line-height:28px;">Kembali ke Keuangan</a>
    </div>
    <table id="tabelpenjualan">
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl Jual</th>
                <th>Nota Jual</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Harga Jual</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
                <th>PPN (11%)</th>
                <th>Total</th>
                <th>Total Per Nota</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        $grand_total_jumlah = 0;
        $grand_total_subtotal = 0;
        $grand_total_ppn = 0;
        $grand_total_all = 0;
        $grand_total_nota = 0;
        
        foreach ($data as $nota => $row) {
            $rowspan = count($row['items']);
            $first = true;
            $grand_total_nota += $row['total_nota_jual'];
            
            foreach ($row['items'] as $item) {
                $grand_total_jumlah += $item['jumlah'];
                $grand_total_subtotal += $item['subtotal'];
                $grand_total_ppn += $item['ppn'];
                $grand_total_all += $item['total'];
                
                echo '<tr>';
                if ($first) {
                    echo '<td rowspan="'.$rowspan.'">'.$no.'</td>';
                    echo '<td rowspan="'.$rowspan.'">'.$row['tgl_jual'].'</td>';
                    echo '<td rowspan="'.$rowspan.'">'.$row['nota_jual'].'</td>';
                }
                echo '<td>'.$item['kode_brng'].'</td>';
                echo '<td>'.$item['nama_brng'].'</td>';
                echo '<td>'.$item['kode_sat'].'</td>';
                echo '<td>'.number_format($item['h_jual'],2,',','.').'</td>';
                echo '<td>'.$item['jumlah'].'</td>';
                echo '<td>'.number_format($item['subtotal'],2,',','.').'</td>';
                echo '<td>'.number_format($item['ppn'],2,',','.').'</td>';
                echo '<td>'.number_format($item['total'],2,',','.').'</td>';
                if ($first) {
                    echo '<td rowspan="'.$rowspan.'">'.number_format($row['total_nota_jual'],2,',','.').'</td>';
                    $first = false;
                    $no++;
                }
                echo '</tr>';
            }
        }
        ?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f1f5f9; color: #0f172a;">
                <td colspan="7" style="text-align: right; padding: 10px;">Total Keseluruhan:</td>
                <td style="padding: 10px;"><?php echo number_format($grand_total_jumlah, 0, ',', '.'); ?></td>
                <td style="padding: 10px;"><?php echo number_format($grand_total_subtotal, 2, ',', '.'); ?></td>
                <td style="padding: 10px;"><?php echo number_format($grand_total_ppn, 2, ',', '.'); ?></td>
                <td style="padding: 10px;"><?php echo number_format($grand_total_all, 2, ',', '.'); ?></td>
                <td style="padding: 10px;"><?php echo number_format($grand_total_nota, 2, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>
</div>
<script>
function copyTableToClipboard() {
    var table = document.getElementById('tabelpenjualan');
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
