<!DOCTYPE html>
<html lang="en">
<head>
    <title>Diagnosa Pasien per Periode</title>
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
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            font-family: Arial, sans-serif;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <header>
        <h1>Data Diagnosa Pasien per Periode</h1>
    </header>
    <div class="back-button">
        <a href="surveilans.php">Kembali ke Menu Surveilans</a>
    </div>

    <!-- Form Filter Tanggal -->
    <form method="POST">
        Filter Tanggal: 
        <input type="date" name="tanggal_awal" required value="<?php echo isset($tanggal_awal) ? $tanggal_awal : ''; ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo isset($tanggal_akhir) ? $tanggal_akhir : ''; ?>">
        <button type="submit" name="filter">Filter</button>
    </form>

    <!-- Form Pencarian Keyword -->
    <form action="" method="get">
        <label for="keyword">Cari Kode Penyakit:</label>
        <input type="text" id="keyword" name="keyword" value="<?= isset($keyword) ? $keyword : ''; ?>">
        <button type="submit">Cari</button>
    </form>

    <?php
    include 'koneksi.php';

    // Inisialisasi variabel tanggal awal dan akhir
    $tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : '';
    $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : '';

    // Filter pencarian keyword
    $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

    // Query default (tanpa filter)
    $query = "SELECT
                reg_periksa.no_rawat,
                pasien.no_rkm_medis,
                pasien.nm_pasien,
                diagnosa_pasien.kd_penyakit,
                diagnosa_pasien.`status`
              FROM
                reg_periksa
              INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
              INNER JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat";

    // Jika ada filter keyword, tambahkan WHERE ke query
    if (!empty($keyword)) {
        $query .= " WHERE diagnosa_pasien.kd_penyakit LIKE '%$keyword%'";
    }

    // Jika ada filter tanggal, tambahkan WHERE dengan kondisi tanggal
    if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
        $query .= (!empty($keyword) ? " AND" : " WHERE") . " reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    }

    // Menambahkan ORDER BY pada query
    $query .= " ORDER BY diagnosa_pasien.status ASC";

    // Eksekusi query
    $result = mysqli_query($koneksi, $query);

    // Jika query gagal
    if (!$result) {
        die("Query error: " . mysqli_error($koneksi));
    }

    // Jika ada hasil, tampilkan dalam tabel
    if (mysqli_num_rows($result) > 0) {
        echo "<table>
            <tr>
                <th>NOMOR RAWAT</th>
                <th>NOMOR RM</th>
                <th>NAMA PASIEN</th>
                <th>KODE PENYAKIT</th>
                <th>STATUS</th>
            </tr>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['no_rawat'] . "</td>";
            echo "<td>" . $row['no_rkm_medis'] . "</td>";
            echo "<td>" . $row['nm_pasien'] . "</td>";
            echo "<td>" . $row['kd_penyakit'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Menampilkan jumlah baris
        $jumlah_baris = mysqli_num_rows($result);
        echo "<p><strong>Jumlah data yang ditemukan: $jumlah_baris</strong></p>";
    } else {
        echo "<p>Data tidak ditemukan.</p>";
    }

    // Tutup koneksi
    mysqli_close($koneksi);
    ?>
</body>
</html>
