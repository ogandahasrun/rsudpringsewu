<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman dengan Logo</title>
</head>
<body>

<a href="index.php">
    <img src="images/logo.png" alt="Logo" width="80" height="100">
</a>

</body>
</html>

<?php
include "koneksi.php";

$query = "SELECT
            gudangbarang.kode_brng,
            databarang.nama_brng,
            kodesatuan.satuan,
            gudangbarang.stok,
            databarang.dasar,
            bangsal.nm_bangsal
          FROM
            gudangbarang
          INNER JOIN databarang ON gudangbarang.kode_brng = databarang.kode_brng
          INNER JOIN kodesatuan ON databarang.kode_sat = kodesatuan.kode_sat
          INNER JOIN bangsal ON gudangbarang.kd_bangsal = bangsal.kd_bangsal";

$searchTerm = '';

if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $query .= " WHERE
                gudangbarang.kode_brng LIKE '%$searchTerm%'
                OR databarang.nama_brng LIKE '%$searchTerm%'
                OR kodesatuan.satuan LIKE '%$searchTerm%'
                OR gudangbarang.stok LIKE '%$searchTerm%'
                OR bangsal.nm_bangsal LIKE '%$searchTerm%'";
}

$result = $koneksi->query($query);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Stok Barang</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa; /* Light Gray */
        }

        table {
            width: 100%;
            margin-top: 20px;
            background-color: #ffffff; /* White */
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        /* Alternate row colors */
        tr:nth-child(odd) {
            background-color: #f2f2f2; /* Light gray */
        }

        tr:nth-child(even) {
            background-color: #ffffff; /* White */
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Data Stok Barang</h2>

        <!-- Search form -->
        <form action="" method="GET">
            <div class="form-group">
                <label for="search">Pencarian :</label>
                <input type="text" class="form-control" name="search" id="search" placeholder="Masukkan Keyword"
                    value="<?= isset($searchTerm) ? $searchTerm : '' ?>">
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>

        <?php
        if (!$result) {
            echo "Query error: " . $koneksi->error;
        } else {
        ?>
            <table class="table table-bordered">
                <tr>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Satuan</th>
                    <th>Stok</th>
                    <th>Harga Dasar</th>
                    <th>Nama Bangsal</th>
                </tr>
                <?php
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['kode_brng'] . "</td>";
                    echo "<td>" . $row['nama_brng'] . "</td>";
                    echo "<td>" . $row['satuan'] . "</td>";
                    echo "<td>" . $row['stok'] . "</td>";
                    echo "<td>" . $row['dasar'] . "</td>";
                    echo "<td>" . $row['nm_bangsal'] . "</td>";
                    echo "</tr>";
                }
                ?>
            </table>
        <?php
        }
        ?>

    </div>

    <?php
    $koneksi->close();
    ?>

</body>

</html>
