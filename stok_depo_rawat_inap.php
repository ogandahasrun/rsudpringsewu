<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Depo Rawat Inap</title>
</head>
<body>

<a href="index.php">
    <img src="images/logo.png" alt="Logo" width="80" height="100">
</a>

</body>
</html>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Lokasi Depo Rawat Inap</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"] {
            padding: 5px;
        }

        input[type="submit"] {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<?php
// Include file koneksi
include 'koneksi.php';

// Inisialisasi variabel pencarian
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

// Query dengan penambahan kondisi pencarian
$query = "
    SELECT
        gudangbarang.kode_brng,
        databarang.nama_brng,
        databarang.kode_sat,
        gudangbarang.stok,
        lokasibarangmedisdri.lokasi,
        bangsal.nm_bangsal
    FROM
        gudangbarang
    INNER JOIN databarang ON gudangbarang.kode_brng = databarang.kode_brng
    INNER JOIN lokasibarangmedisdri ON lokasibarangmedisdri.kode_brng = gudangbarang.kode_brng
    INNER JOIN bangsal ON gudangbarang.kd_bangsal = bangsal.kd_bangsal
    WHERE
        gudangbarang.kd_bangsal = 'dri' AND
        (databarang.nama_brng LIKE '%$keyword%' OR lokasibarangmedisdri.lokasi LIKE '%$keyword%')
    ORDER BY
        lokasibarangmedisdri.lokasi ASC,
        databarang.nama_brng ASC
";

// Eksekusi query
$result = $koneksi->query($query);

// Tampilkan form pencarian
echo "<form action='' method='get'>
    <label for='keyword'>Cari:</label>
    <input type='text' name='keyword' id='keyword' value='$keyword'>
    <input type='submit' value='Cari'>
</form>";

// Tampilkan hasil
if ($result->num_rows > 0) {
    echo "<table>
        <tr>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Kode Satuan</th>
            <th>Stok</th>
            <th>Lokasi</th>
            <th>Nama Bangsal</th>
        </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>" . $row["kode_brng"] . "</td>
            <td>" . $row["nama_brng"] . "</td>
            <td>" . $row["kode_sat"] . "</td>
            <td>" . $row["stok"] . "</td>
            <td>" . $row["lokasi"] . "</td>
            <td>" . $row["nm_bangsal"] . "</td>
        </tr>";
    }

    echo "</table>";
} else {
    echo "Tidak ada data.";
}

// Tutup koneksi
$koneksi->close();
?>
</body>
</html>
