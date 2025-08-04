<!DOCTYPE html>
<html lang="en">
<head>
    <title>Mutasi Barang Medis</title>
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
            cursor: pointer;
        }
        .copy-button {
            margin: 10px 0;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        th .sort-indicator {
            margin-left: 6px;
            font-size: 12px;
        }
        th.sorted-asc,
        th.sorted-desc {
            background-color: #45a049;
        }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector("table");
            let range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand("copy");
            window.getSelection().removeAllRanges();
            alert("Tabel berhasil disalin!");
        }

        function sortTable(n) {
            const table = document.querySelector("table");
            let rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            switching = true;
            dir = "asc";

            // Hapus semua indikator sort
            const ths = table.querySelectorAll("th");
            ths.forEach((th, index) => {
                th.classList.remove("sorted-asc", "sorted-desc");
                th.querySelector(".sort-indicator").innerHTML = "";
            });

            while (switching) {
                switching = false;
                rows = table.rows;

                for (i = 1; i < rows.length - 1; i++) {
                    shouldSwitch = false;
                    x = rows[i].getElementsByTagName("TD")[n];
                    y = rows[i + 1].getElementsByTagName("TD")[n];

                    let xContent = x.textContent || x.innerText;
                    let yContent = y.textContent || y.innerText;

                    let xVal = parseFloat(xContent.replace(/[^0-9.-]+/g, ""));
                    let yVal = parseFloat(yContent.replace(/[^0-9.-]+/g, ""));

                    if (isNaN(xVal) || isNaN(yVal)) {
                        xVal = xContent.toLowerCase();
                        yVal = yContent.toLowerCase();
                    }

                    if ((dir === "asc" && xVal > yVal) || (dir === "desc" && xVal < yVal)) {
                        shouldSwitch = true;
                        break;
                    }
                }

                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount === 0 && dir === "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }

            // Tambah indikator sort
            const activeTh = ths[n];
            const indicator = activeTh.querySelector(".sort-indicator");
            if (dir === "asc") {
                activeTh.classList.add("sorted-asc");
                indicator.innerHTML = "▲";
            } else {
                activeTh.classList.add("sorted-desc");
                indicator.innerHTML = "▼";
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Mutasi Barang Medis</h1>
    </header>
    <div class="back-button">
        <a href="farmasi.php">Kembali ke Menu Farmasi</a>
    </div>

    <?php
    include 'koneksi.php';

    $tanggal_awal = $_POST['tanggal_awal'] ?? '';
    $tanggal_akhir = $_POST['tanggal_akhir'] ?? '';
    ?>

    <form method="POST">
        <label>Filter Tanggal Mutasi:</label>
        <input type="date" name="tanggal_awal" value="<?= $tanggal_awal ?>">
        <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">
        <button type="submit" name="filter">Filter</button>
    </form>

    <header>
        <h3>Klik Pada Judul Kolom Untuk Mengurutkan Data</h3>
    </header>


    <?php
    function format_rupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }

    if (isset($_POST['filter'])) {
        $query = "SELECT
                    mutasibarang.tanggal,
                    mutasibarang.kd_bangsaldari,
                    mutasibarang.kd_bangsalke,
                    databarang.kode_brng,
                    databarang.nama_brng,
                    mutasibarang.jml,
                    databarang.kode_sat,
                    mutasibarang.keterangan
                  FROM mutasibarang
                  INNER JOIN databarang ON mutasibarang.kode_brng = databarang.kode_brng
                  WHERE 1";

        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
            $query .= " AND mutasibarang.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
        }

        $result = mysqli_query($koneksi, $query);

        if ($result) {
            echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
            echo "<table>
                <tr>
                    <th onclick='sortTable(0)'>NO <span class='sort-indicator'></span></th>
                    <th onclick='sortTable(1)'>TANGGAL <span class='sort-indicator'></span></th>
                    <th onclick='sortTable(2)'>BANGSAL DARI <span class='sort-indicator'></span></th>
                    <th onclick='sortTable(3)'>BANGSAL KE <span class='sort-indicator'></span></th>
                    <th onclick='sortTable(4)'>KODE BARANG <span class='sort-indicator'></span></th>
                    <th onclick='sortTable(5)'>NAMA BARANG <span class='sort-indicator'></span></th>
                    <th onclick='sortTable(6)'>JUMLAH <span class='sort-indicator'></span></th>
                    <th onclick='sortTable(7)'>SATUAN <span class='sort-indicator'></span></th>
                    <th onclick='sortTable(8)'>KETERANGAN <span class='sort-indicator'></span></th>
                </tr>";

            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                    <td>$no</td>
                    <td>{$row['tanggal']}</td>
                    <td>{$row['kd_bangsaldari']}</td>
                    <td>{$row['kd_bangsalke']}</td>
                    <td>{$row['kode_brng']}</td>
                    <td>{$row['nama_brng']}</td>
                    <td>{$row['jml']}</td>
                    <td>{$row['kode_sat']}</td>
                    <td>{$row['keterangan']}</td>
                </tr>";
                $no++;
            }

            echo "</table>";
        } else {
            echo "Gagal mengambil data.";
        }

        mysqli_close($koneksi);
    }
    ?>
</body>
</html>
