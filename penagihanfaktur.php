<!DOCTYPE html>
<html lang="en">
<head>
    <title>Daftar Faktur Barang Medis</title>
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
        .copy-button {
            margin: 10px 0;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        /* STATUS color shadows */
        .status-belumdibayar {
            box-shadow: inset 0 0 10px red;
            background-color: #ffe5e5;
            font-weight: bold;
        }
        .status-titipfaktur {
            box-shadow: inset 0 0 10px orange;
            background-color: #fff3e0;
            font-weight: bold;
        }
        .status-belumlunas {
            box-shadow: inset 0 0 10px gold;
            background-color: #fffde7;
            font-weight: bold;
        }
        .status-sudahdibayar {
            box-shadow: inset 0 0 10px green;
            background-color: #e8f5e9;
            font-weight: bold;
        }

        /* Tanggal Penagihan kosong */
        .penagihan-kosong {
            box-shadow: inset 0 0 10px red;
            background-color: #ffecec;
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
    </script>
</head>
<body>
    <header>
        <h1>Daftar Faktur Barang Medis</h1>
    </header>
    <div class="back-button">
        <a href="keuangan.php">Kembali ke Menu Keuangan</a>
    </div>

    <?php
    include 'koneksi.php';

    if (isset($_POST['simpan_tanggal'])) {
        $no_faktur = $_POST['no_faktur'];
        $tanggal_penagihan = $_POST['tanggal_penagihan'];
        $cek = mysqli_query($koneksi, "SELECT * FROM pemesanan_tanggal_penagihan WHERE no_faktur='$no_faktur'");
        if (mysqli_num_rows($cek) > 0) {
            mysqli_query($koneksi, "UPDATE pemesanan_tanggal_penagihan SET tanggal_penagihan='$tanggal_penagihan' WHERE no_faktur='$no_faktur'");
        } else {
            mysqli_query($koneksi, "INSERT INTO pemesanan_tanggal_penagihan (no_faktur, tanggal_penagihan) VALUES ('$no_faktur', '$tanggal_penagihan')");
        }
    }

    $tanggal_awal = $_POST['tanggal_awal'] ?? '';
    $tanggal_akhir = $_POST['tanggal_akhir'] ?? '';
    $tgl_pesan = $_POST['tgl_pesan'] ?? '';
    $perusahaan = $_POST['perusahaan'] ?? '';
    ?>

    <!-- Filter Form -->
    <form method="POST">
        <label>Filter Tanggal Faktur:</label>
        <input type="date" name="tanggal_awal" value="<?= $tanggal_awal ?>">
        <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">

        <label>Filter Tanggal Datang :</label>
        <input type="date" name="tgl_pesan" value="<?= $tgl_pesan ?>">

        <label>Filter Perusahaan:</label>
        <input type="text" name="perusahaan" placeholder="Nama Supplier" value="<?= $perusahaan ?>">

        <button type="submit" name="filter">Filter</button>
    </form>

    <?php
    function format_rupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }

    if (isset($_POST['filter'])) {
        $query = "SELECT
                    pemesanan.no_faktur,
                    datasuplier.nama_suplier,
                    pemesanan.tgl_pesan,
                    pemesanan.tgl_faktur,
                    pemesanan.tgl_tempo,
                    pemesanan_tanggal_penagihan.tanggal_penagihan,
                    pemesanan.total2,
                    pemesanan.ppn,
                    pemesanan.tagihan,
                    pemesanan.status
                FROM
                    pemesanan
                INNER JOIN datasuplier ON pemesanan.kode_suplier = datasuplier.kode_suplier
                LEFT JOIN pemesanan_tanggal_penagihan ON pemesanan.no_faktur = pemesanan_tanggal_penagihan.no_faktur
                WHERE 1=1";

        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
            $query .= " AND pemesanan.tgl_faktur BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
        }

        if (!empty($tgl_pesan)) {
            $query .= " AND pemesanan.tgl_pesan = '$tgl_pesan'";
        }

        if (!empty($perusahaan)) {
            $query .= " AND datasuplier.nama_suplier LIKE '%$perusahaan%'";
        }

        $result = mysqli_query($koneksi, $query);

        if ($result) {
            echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
            echo "<table>
                <tr>
                    <th>NO FAKTUR</th>
                    <th>NAMA SUPPLIER</th>
                    <th>TGL PESAN</th>
                    <th>TGL FAKTUR</th>
                    <th>TGL TEMPO</th>
                    <th>TGL PENAGIHAN</th>
                    <th>TOTAL</th>
                    <th>PPN</th>
                    <th>TOTAL TAGIHAN</th>
                    <th>STATUS BAYAR</th>
                </tr>";

            while ($row = mysqli_fetch_assoc($result)) {
                // Tentukan class status
                $status_class = '';
                switch (strtolower(trim($row['status']))) {
                    case 'belum dibayar':
                        $status_class = 'status-belumdibayar';
                        break;
                    case 'titip faktur':
                        $status_class = 'status-titipfaktur';
                        break;
                    case 'belum lunas':
                        $status_class = 'status-belumlunas';
                        break;
                    case 'sudah dibayar':
                        $status_class = 'status-sudahdibayar';
                        break;
                }

                // Cek apakah tanggal penagihan kosong
                $penagihan_class = empty($row['tanggal_penagihan']) ? 'penagihan-kosong' : '';

                echo "<tr>
                    <td>{$row['no_faktur']}</td>
                    <td>{$row['nama_suplier']}</td>
                    <td>{$row['tgl_pesan']}</td>
                    <td>{$row['tgl_faktur']}</td>
                    <td>{$row['tgl_tempo']}</td>
                    <td>
                        <form method='POST' style='margin:0; display:inline-block;'>
                            <input type='hidden' name='no_faktur' value='{$row['no_faktur']}'>
                            <input type='date' name='tanggal_penagihan' class='$penagihan_class' value='{$row['tanggal_penagihan']}'>
                            <input type='hidden' name='tanggal_awal' value='{$tanggal_awal}'>
                            <input type='hidden' name='tanggal_akhir' value='{$tanggal_akhir}'>
                            <input type='hidden' name='tgl_pesan' value='{$tgl_pesan}'>
                            <input type='hidden' name='perusahaan' value='{$perusahaan}'>
                            <button type='submit' name='simpan_tanggal'>Simpan</button>
                            <input type='hidden' name='filter' value='1'>
                        </form>
                    </td>
                    <td style='text-align: right;'>" . format_rupiah($row['total2']) . "</td>
                    <td style='text-align: right;'>" . format_rupiah($row['ppn']) . "</td>
                    <td style='text-align: right;'>" . format_rupiah($row['tagihan']) . "</td>
                    <td class='$status_class'>{$row['status']}</td>
                </tr>";
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
