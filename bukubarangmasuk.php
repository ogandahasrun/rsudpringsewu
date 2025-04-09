<!DOCTYPE html>
<html lang="en">
<head>
    <title>BUKU BARANG MASUK</title>
    <style>
        h1 {
            font-family: Arial, sans-serif;
            color: green;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
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
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <header>
        <h1>BUKU BARANG MASUK</h1>
    </header>

    <div class="back-button">
        <a href="index.php">Kembali ke Menu Farmasi</a>
    </div>

    <?php
    include 'koneksi.php';

    $tanggal_awal = $tanggal_akhir = "";
    $result = null;

    if (isset($_POST['filter'])) {
        $tanggal_awal = $_POST['tanggal_awal'];
        $tanggal_akhir = $_POST['tanggal_akhir'];

        $query = "SELECT
                    pemesanan.tgl_faktur,
                    pemesanan.tgl_pesan,
                    pemesanan.no_order,
                    datasuplier.nama_suplier,
                    pemesanan.no_faktur,
                    detailpesan.kode_brng,
                    databarang.nama_brng,
                    detailpesan.jumlah,
                    detailpesan.kode_sat,
                    detailpesan.no_batch,
                    detailpesan.kadaluarsa,
                    pemesanan.ppn,
                    pemesanan.tagihan
                  FROM
                    pemesanan
                  INNER JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
                  INNER JOIN datasuplier ON pemesanan.kode_suplier = datasuplier.kode_suplier
                  INNER JOIN databarang ON detailpesan.kode_brng = databarang.kode_brng
                  WHERE
                    pemesanan.tgl_pesan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                  ORDER BY
                    pemesanan.no_order ASC";

        $result = mysqli_query($koneksi, $query);

        if (!$result) {
            die("Query error: " . mysqli_error($koneksi));
        }
    }
    ?>

    <form method="POST">
        Filter Tanggal:
        <input type="date" name="tanggal_awal" required value="<?php echo $tanggal_awal; ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo $tanggal_akhir; ?>">
        <button type="submit" name="filter">Filter</button>
    </form>

    <?php
    if (isset($result) && mysqli_num_rows($result) > 0) {
        echo "<table>
                <tr>
                    <th>TGL FAKTUR</th>
                    <th>TGL PESAN</th>
                    <th>NO ORDER</th>
                    <th>NO FAKTUR</th>
                    <th>SUPLIER</th>
                    <th>KODE BARANG</th>
                    <th>NAMA BARANG</th>
                    <th>JUMLAH</th>
                    <th>SATUAN</th>
                    <th>NO BATCH</th>
                    <th>KADALUARSA</th>
                    <th>PPN (%)</th>
                    <th>TOTAL TAGIHAN</th>
                </tr>";

        $prev_faktur = ''; // Penanda faktur sebelumnya

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";

            if ($row['no_faktur'] != $prev_faktur) {
                echo "<td>" . $row['tgl_faktur'] . "</td>";
                echo "<td>" . $row['tgl_pesan'] . "</td>";
                echo "<td>" . $row['no_order'] . "</td>";
                echo "<td>" . $row['no_faktur'] . "</td>";
                echo "<td>" . $row['nama_suplier'] . "</td>";
            } else {
                echo "<td></td><td></td><td></td><td></td><td></td>";
            }

            echo "<td>" . $row['kode_brng'] . "</td>";
            echo "<td>" . $row['nama_brng'] . "</td>";
            echo "<td>" . $row['jumlah'] . "</td>";
            echo "<td>" . $row['kode_sat'] . "</td>";
            echo "<td>" . $row['no_batch'] . "</td>";
            echo "<td>" . $row['kadaluarsa'] . "</td>";

            if ($row['no_faktur'] != $prev_faktur) {
                echo "<td>" . $row['ppn'] . "</td>";
                echo "<td>" . number_format($row['tagihan'], 0, ',', '.') . "</td>";
            } else {
                echo "<td></td><td></td>";
            }

            echo "</tr>";

            $prev_faktur = $row['no_faktur'];
        }

        echo "</table>";
    } elseif (isset($result)) {
        echo "<p><em>Data tidak ditemukan untuk rentang tanggal yang dipilih.</em></p>";
    }

    mysqli_close($koneksi);
    ?>
</body>
</html>
