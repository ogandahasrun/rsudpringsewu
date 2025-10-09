<?php include "koneksi.php"; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Stok Gudang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h2 {
            color: green;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #e0e0e0;
        }
        .filter-form {
            margin-bottom: 10px;
        }
        .filter-form input[type="text"] {
            padding: 6px;
            width: 250px;
        }
        .filter-form input[type="submit"] {
            padding: 6px 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .copy-btn {
            padding: 8px 16px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .copy-btn:hover {
            background-color: #1976D2;
        }
    </style>
</head>
<body>

<h2>Data Stok Barang Farmasi</h2>

        <div class="back-button">
            <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
        </div>

<form method="GET" class="filter-form">
    <label>Cari Barang (Kode atau Nama): </label>
    <input type="text" name="cari" placeholder="Masukkan kata kunci..." value="<?php echo isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : ''; ?>">
    <input type="submit" value="Filter">
</form>

        <div class="export-button">
            <button class="copy-btn" onclick="copyTableToClipboard('dataTable')">Copy ke Clipboard</button>
        </div>

<table id="dataTable">
    <thead>
        <tr>
            <th>No</th>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Harga</th>
            <th>Satuan</th>
            <th>Stok GO</th>
            <th>Stok DRI</th>
            <th>Stok AP</th>
            <th>Stok DI</th>
            <th>Stok DO</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $filter = "";
        if (isset($_GET['cari']) && $_GET['cari'] != "") {
            $cari = mysqli_real_escape_string($koneksi, $_GET['cari']);
            $filter = "WHERE (db.kode_brng LIKE '%$cari%' OR db.nama_brng LIKE '%$cari%')";
        }

        $query = "
            SELECT
                db.kode_brng,
                db.nama_brng,
                db.kode_sat,
                db.dasar,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'GO' THEN gb.stok ELSE 0 END), 0) AS stok_go,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'DRI' THEN gb.stok ELSE 0 END), 0) AS stok_dri,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'AP' THEN gb.stok ELSE 0 END), 0) AS stok_ap,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'DI' THEN gb.stok ELSE 0 END), 0) AS stok_di,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'DO' THEN gb.stok ELSE 0 END), 0) AS stok_do
            FROM
                databarang db
            LEFT JOIN
                gudangbarang gb ON gb.kode_brng = db.kode_brng
            $filter
            GROUP BY
                db.kode_brng, db.nama_brng, db.kode_sat
            ORDER BY
                db.kode_brng ASC
        ";

        $result = mysqli_query($koneksi, $query);
        $no = 1;

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                <td>{$no}</td>
                <td>{$row['kode_brng']}</td>
                <td style='text-align: left;'>{$row['nama_brng']}</td>
                <td style='text-align: right;'>{$row['dasar']}</td>
                <td>{$row['kode_sat']}</td>
                <td>{$row['stok_go']}</td>
                <td>{$row['stok_dri']}</td>
                <td>{$row['stok_ap']}</td>
                <td>{$row['stok_di']}</td>
                <td>{$row['stok_do']}</td>
            </tr>";
            $no++;
        }

        mysqli_close($koneksi);
        ?>
    </tbody>
</table>

    <script>
    function copyTableToClipboard(tableID) {
        const table = document.getElementById(tableID);
        const textarea = document.createElement("textarea");
        let text = "";

        for (let row of table.rows) {
            let rowData = [];
            for (let cell of row.cells) {
                rowData.push(cell.innerText);
            }
            text += rowData.join("\t") + "\n";
        }

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
