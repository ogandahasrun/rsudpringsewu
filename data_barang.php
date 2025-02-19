<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang</title>
</head>
<body>

<a href="index.php">
    <img src="images/logo.png" alt="Logo" width="80" height="100">
</a>

</body>
</html>

<?php
// Sertakan file koneksi.php
include "koneksi.php";

// Filter pencarian
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$where = $keyword ? "WHERE databarang.nama_brng LIKE '%$keyword%'" : '';

// Query untuk mengambil data dari tabel databarang
$query = "SELECT
            databarang.kode_brng,
            databarang.nama_brng,
            databarang.kode_sat
          FROM
            databarang
          $where
          ORDER BY
            databarang.nama_brng ASC";

$result = $koneksi->query($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang</title>

    <!-- Tambahkan styling CSS dengan variasi warna yang lembut -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            background-color: #f8f8f8;
        }

        h2 {
            color: #333;
            background-color: #e0e0e0;
            padding: 10px;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            color: #555;
        }

        input[type="text"] {
            padding: 5px;
            border: 1px solid #ddd;
        }

        button {
            padding: 5px 10px;
            cursor: pointer;
            background-color: #4caf50;
            color: #fff;
            border: none;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h2>Data Barang</h2>

    <!-- Form pencarian -->
    <form action="" method="get">
        <label for="keyword">Cari Barang:</label>
        <input type="text" id="keyword" name="keyword" value="<?= $keyword ?>">
        <button type="submit">Cari</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kode Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Tampilkan data dalam tabel
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['kode_brng'] . "</td>";
                echo "<td>" . $row['nama_brng'] . "</td>";
                echo "<td>" . $row['kode_sat'] . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>

<?php
// Tutup koneksi
$koneksi->close();
?>
