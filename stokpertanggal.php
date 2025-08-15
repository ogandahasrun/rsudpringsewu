<!DOCTYPE html>
<html lang="en">
<head>
    <title>Stok Akhir Per Tanggal</title>
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
        .copy-button {
            margin-bottom: 10px;
            padding: 8px 16px;
            background: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .copy-button:hover {
            background: #388e3c;
        }
    </style>
</head>
<body>
    <header>
        <h1>Stok Akhir Per Tanggal</h1>
    </header>

    <div class="back-button">
        <a href="index.php">Kembali ke Menu Farmasi</a>
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

        // Ambil semua kombinasi kode_brng dan kd_bangsal yang ada
        $where_bangsal = !empty($kd_bangsal) ? "WHERE kd_bangsal = '$kd_bangsal'" : "";
        $sql_barang = "SELECT DISTINCT kode_brng, kd_bangsal FROM riwayat_barang_medis $where_bangsal";
        // $sql_barang = "SELECT DISTINCT rbm.kode_brng, rbm.kd_bangsal
               // FROM riwayat_barang_medis rbm
               // INNER JOIN databarang db ON rbm.kode_brng = db.kode_brng
               // $where_bangsal
               // WHERE db.status = '1'"; 
        $result_barang = mysqli_query($koneksi, $sql_barang);

        // Tombol Copy
        echo "<button class='copy-button' onclick=\"copyTableToClipboard('tabel-stok')\">Copy Data</button>";

        echo "<table id='tabel-stok'>";
        echo "<tr>
                <th>Kode Barang</th>
                <th>Stok Akhir</th>
                <th>Kode Bangsal</th>
              </tr>";

        $ada_data = false;
        if ($result_barang && mysqli_num_rows($result_barang) > 0) {
            while ($row_barang = mysqli_fetch_assoc($result_barang)) {
                $kode_brng = $row_barang['kode_brng'];
                $bangsal = $row_barang['kd_bangsal'];

                // Ambil stok akhir terakhir sebelum atau sama dengan tanggal yang dipilih
                $sql_stok = "SELECT stok_akhir 
                             FROM riwayat_barang_medis 
                             WHERE kode_brng = '$kode_brng' 
                               AND kd_bangsal = '$bangsal' 
                               AND tanggal <= '$tanggal_diminta'
                             ORDER BY tanggal DESC, jam DESC
                             LIMIT 1";
                $result_stok = mysqli_query($koneksi, $sql_stok);
                $stok_akhir = 0;
                if ($result_stok && $row_stok = mysqli_fetch_assoc($result_stok)) {
                    $stok_akhir = $row_stok['stok_akhir'];
                }

                echo "<tr>
                        <td>$kode_brng</td>
                        <td>$stok_akhir</td>
                        <td>$bangsal</td>
                      </tr>";
                $ada_data = true;
            }
        }

        if (!$ada_data) {
            echo "<tr><td colspan='3'>Tidak ada data ditemukan untuk tanggal dan bangsal yang dipilih.</td></tr>";
        }

        echo "</table>";
    }

    mysqli_close($koneksi);
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