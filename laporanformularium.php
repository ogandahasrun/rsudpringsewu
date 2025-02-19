<!DOCTYPE html>
<html lang="en">
<head>
    <title>Laporan Formularium</title>
    <style>
        h1 {
            font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
            color: green; /* Mengubah warna teks */
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <h1>Laporan Formularium per Periode</h1>
    </header>

    <div class="back-button">
        <a href="index.php">Kembali ke Menu Farmasi</a>
    </div>

    <?php
    include 'koneksi.php';

    // Inisialisasi variabel tanggal awal dan akhir
    $tanggal_awal = $tanggal_akhir = "";

    // Proses filter jika tombol "Filter" diklik
    if(isset($_POST['filter'])) {
        $tanggal_awal = $_POST['tanggal_awal'];
        $tanggal_akhir = $_POST['tanggal_akhir'];
        
        // Query dengan filter tanggal
        $query = "SELECT
                    databarang.kode_industri,
                    industrifarmasi.nama_industri,
                    Count(detail_pemberian_obat.tgl_perawatan) AS jumlah
                    FROM
                    detail_pemberian_obat
                    INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
                    INNER JOIN industrifarmasi ON databarang.kode_industri = industrifarmasi.kode_industri
                    WHERE
                    detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND
                    databarang.kode_kategori = 'OBT'
                    GROUP BY
                    databarang.kode_industri
                    ORDER BY
                    databarang.kode_industri ASC";
        

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
    // Tampilkan tabel hanya jika hasil query ada
    if(isset($result)) {
        echo "<table>
            <tr>
                <th>KODE</th>
                <th>JENIS FORMULARIUM</th>
                <th>JUMLAH</th>
            </tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['kode_industri'] . "</td>";
            echo "<td>" . $row['nama_industri'] . "</td>";
            echo "<td>" . $row['jumlah'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }    
    
    mysqli_close($koneksi);
    ?>
</body>
</html>
