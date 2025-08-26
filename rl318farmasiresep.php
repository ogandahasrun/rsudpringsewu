<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RL 3.18 Farmasi Resep</title>
    <style>
        h1 {
            font-family: Arial, sans-serif;
            color: green;
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            border-right: 1px solid #ddd;
        }
        th:last-child, td:last-child {
            border-right: none;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        th {
            background-color: #4CAF50;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .back-button {
            margin: 15px 0;
        }
        .filter-form {
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .filter-form input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-form button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-form button:hover {
            background-color: #45a049;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }
        .export-button {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<?php
include 'koneksi.php';

$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

$query = "SELECT
            industrifarmasi.nama_industri,
            COUNT(detail_pemberian_obat.kode_brng) AS jumlah_resep
        FROM
            detail_pemberian_obat
        INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
        INNER JOIN industrifarmasi ON databarang.kode_industri = industrifarmasi.kode_industri
        WHERE
            databarang.kode_kategori = 'OBT' AND
            detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
        GROUP BY
            industrifarmasi.nama_industri
        ORDER BY
            industrifarmasi.nama_industri ASC
";

$result = mysqli_query($koneksi, $query);
?>

<header>
    <h1>RL 3.18 Farmasi Resep</h1>
</header>

<div class="back-button">
    <a href="surveilans.php">← Kembali ke Menu Surveilans</a>
</div>

<form method="POST" class="filter-form">
    Filter Tanggal Resep :
    <input type="date" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
    <input type="date" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
    <button type="submit" name="filter">Filter</button>
</form>

<?php
if ($result && mysqli_num_rows($result) > 0) {
    echo "<h2>Laporan Resep Farmasi</h2>";
    echo "<p>Periode : " . htmlspecialchars($tanggal_awal) . " s/d " . htmlspecialchars($tanggal_akhir) . "</p>";
    echo "<button class='export-button' onclick='copyTableToClipboard(\"tabel-farmasi\")'>Copy Data</button>";
    echo "<table id='tabel-farmasi'>";
    echo "<thead>
            <tr>
                <th>No</th>
                <th>Nama Industri</th>
                <th>Jumlah Resep</th>
            </tr>
        </thead>
        <tbody>";
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td>" . $no++ . "</td>
                <td>" . htmlspecialchars($row['nama_industri']) . "</td>
                <td>" . htmlspecialchars($row['jumlah_resep']) . "</td>
            </tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<div class='no-data'>Tidak ada data ditemukan untuk periode yang dipilih.</div>";
}
?>

<script>
function copyTableToClipboard(tableID) {
    const table = document.getElementById(tableID);
    if (!table) return;
    let text = "";
    for (let row of table.rows) {
        let rowData = [];
        for (let cell of row.cells) {
            rowData.push(cell.innerText);
        }
        text += rowData.join("\t") + "\n";
    }
    const textarea = document.createElement("textarea");
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    document.body.removeChild(textarea);
    alert("Data tabel telah disalin ke clipboard!");
}
</script>

</body>
</html>