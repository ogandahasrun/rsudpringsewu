<!DOCTYPE html>
<html lang="en">
<head>
    <title>Triase IGD</title>
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
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .back-button, .search-box {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Triase IGD</h1>
    </header>

    <div class="back-button">
        <a href="index.php">Kembali ke Menu Surveilans</a>
    </div>

    <div class="search-box">
        <form method="GET">
            <label for="tanggal">Pilih Tanggal:</label>
            <input type="date" id="tanggal" name="tanggal" value="<?php echo isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d'); ?>" required>

            <label for="kd_bangsal">Pilih Kode Bangsal:</label>
            <select id="kd_bangsal" name="kd_bangsal">
                <option value="">Semua</option>
                <?php
                include 'koneksi.php';
                $query_bangsal = "SELECT DISTINCT kd_bangsal FROM riwayat_barang_medis ORDER BY kd_bangsal";
                $result_bangsal = mysqli_query($koneksi, $query_bangsal);
                while ($row_bangsal = mysqli_fetch_assoc($result_bangsal)) {
                    $selected = (isset($_GET['kd_bangsal']) && $_GET['kd_bangsal'] == $row_bangsal['kd_bangsal']) ? 'selected' : '';
                    echo "<option value='{$row_bangsal['kd_bangsal']}' $selected>{$row_bangsal['kd_bangsal']}</option>";
                }
                ?>
            </select>

            <button type="submit">Cari</button>
        </form>
    </div>

    <?php
    if (isset($_GET['tanggal'])) {
        $tanggal_diminta = mysqli_real_escape_string($koneksi, $_GET['tanggal']);
        $kd_bangsal = isset($_GET['kd_bangsal']) ? mysqli_real_escape_string($koneksi, $_GET['kd_bangsal']) : '';

        // Query untuk mendapatkan stok terbaru per barang dalam bangsal tertentu (jika dipilih)
        $sql = "SELECT rbm.kode_brng, rbm.stok_akhir, rbm.kd_bangsal 
                FROM riwayat_barang_medis rbm
                INNER JOIN (
                    SELECT kode_brng, kd_bangsal, MAX(jam) AS max_jam
                    FROM riwayat_barang_medis
                    WHERE tanggal = '$tanggal_diminta'";

        if (!empty($kd_bangsal)) {
            $sql .= " AND kd_bangsal = '$kd_bangsal'";
        }

        $sql .= " GROUP BY kode_brng, kd_bangsal
                ) latest 
                ON rbm.kode_brng = latest.kode_brng 
                AND rbm.kd_bangsal = latest.kd_bangsal 
                AND rbm.jam = latest.max_jam
                WHERE rbm.tanggal = '$tanggal_diminta'";

        if (!empty($kd_bangsal)) {
            $sql .= " AND rbm.kd_bangsal = '$kd_bangsal'";
        }

        $sql .= " ORDER BY rbm.kode_brng, rbm.kd_bangsal";

        $result = mysqli_query($koneksi, $sql);

        echo "<table>";
        echo "<tr>
                <th>Kode Barang</th>
                <th>Stok Akhir</th>
                <th>Kode Bangsal</th>
              </tr>";

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$row['kode_brng']}</td>
                        <td>{$row['stok_akhir']}</td>
                        <td>{$row['kd_bangsal']}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Tidak ada data ditemukan untuk tanggal dan bangsal yang dipilih.</td></tr>";
        }

        echo "</table>";
    }

    mysqli_close($koneksi);
    ?>
</body>
</html>
