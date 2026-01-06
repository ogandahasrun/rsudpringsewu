<?php
include 'koneksi.php';

// Default tanggal hari ini
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Query data
$query = "SELECT ipsrspengeluaran.tanggal, ipsrspengeluaran.no_keluar, ipsrsdetailpengeluaran.kode_brng, ipsrsbarang.nama_brng, ipsrsdetailpengeluaran.kode_sat, ipsrsdetailpengeluaran.jumlah, ipsrspengeluaran.keterangan FROM ipsrspengeluaran INNER JOIN ipsrsdetailpengeluaran ON ipsrsdetailpengeluaran.no_keluar = ipsrspengeluaran.no_keluar INNER JOIN ipsrsbarang ON ipsrsdetailpengeluaran.kode_brng = ipsrsbarang.kode_brng WHERE ipsrspengeluaran.tanggal BETWEEN ? AND ? ORDER BY ipsrspengeluaran.tanggal, ipsrspengeluaran.no_keluar, ipsrsdetailpengeluaran.kode_brng";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Ambil data untuk pengelompokan
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $key = $row['tanggal'] . '|' . $row['no_keluar'];
    if (!isset($data[$key])) {
        $data[$key] = [
            'tanggal' => $row['tanggal'],
            'no_keluar' => $row['no_keluar'],
            'keterangan' => $row['keterangan'],
            'items' => []
        ];
    }
    $data[$key]['items'][] = [
        'kode_brng' => $row['kode_brng'],
        'nama_brng' => $row['nama_brng'],
        'kode_sat' => $row['kode_sat'],
        'jumlah' => $row['jumlah']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengeluaran Non Medis</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            margin: 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.6em;
            font-weight: bold;
        }
        .content {
            padding: 25px;
        }
        .filter-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        .filter-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-group label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
        }
        .filter-group input {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 900px;
        }
        th {
            background: linear-gradient(45deg, #343a40, #495057);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
            white-space: nowrap;
            position: relative;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        tr:nth-child(even) td {
            background: #f8f9fa;
        }
        tr:hover td {
            background: #e3f2fd;
        }
        @media (max-width: 768px) {
            .content { padding: 15px; }
            .filter-form { padding: 20px 15px; }
            th, td { padding: 8px 6px; font-size: 12px; }
            table { min-width: 700px; }
        }
        @media (max-width: 480px) {
            .header h1 { font-size: 1.3em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Daftar Pengeluaran Non Medis</h1>
        </div>
        <div class="content">
            <form method="POST" class="filter-form">
                <div class="filter-title">🔍 Filter Periode Pengeluaran</div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">Tanggal Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">Tanggal Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>No Keluar</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    foreach ($data as $group) {
                        $rowspan = count($group['items']);
                        $first = true;
                        foreach ($group['items'] as $item) {
                            echo '<tr>';
                            if ($first) {
                                echo '<td rowspan="' . $rowspan . '" style="text-align:center;font-weight:bold;">' . $no++ . '</td>';
                                echo '<td rowspan="' . $rowspan . '" style="text-align:center;">' . htmlspecialchars($group['tanggal']) . '</td>';
                                echo '<td rowspan="' . $rowspan . '" style="text-align:center;">'
                                    . '<a href="bapengeluarannonmedis.php?no_keluar=' . urlencode($group['no_keluar']) . '" style="color:#007bff;text-decoration:underline;font-weight:bold;">'
                                    . htmlspecialchars($group['no_keluar']) . '</a></td>';
                            }
                            echo '<td>' . htmlspecialchars($item['kode_brng']) . '</td>';
                            echo '<td>' . htmlspecialchars($item['nama_brng']) . '</td>';
                            echo '<td style="text-align:center;">' . htmlspecialchars($item['kode_sat']) . '</td>';
                            echo '<td style="text-align:right;">' . htmlspecialchars($item['jumlah']) . '</td>';
                            if ($first) {
                                echo '<td rowspan="' . $rowspan . '" style="text-align:left;">' . htmlspecialchars($group['keterangan']) . '</td>';
                            }
                            echo '</tr>';
                            $first = false;
                        }
                    }
                    if (empty($data)) {
                        echo '<tr><td colspan="8" style="text-align:center;">Tidak ada data untuk periode ini.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
