<!DOCTYPE html>
<html lang="en">
<head>
    <title>Kontrol Kamar Inap</title>
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
        tr:nth-child(odd) {
            background-color: #ffffff;
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
        .warning {
            background-color: #ffb3b3 !important;
            color: #b30000;
            font-weight: bold;
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
        <h1>Kontrol Kamar Inap</h1>
    </header>
    <div class="back-button">
        <a href="laporandansurat.php">Kembali ke Menu Laporan</a>
    </div>

    <?php
    include 'koneksi.php';
        $query = "SELECT
                    kamar_inap.no_rawat,
                    reg_periksa.no_rkm_medis,
                    pasien.nm_pasien,
                    kamar_inap.tgl_masuk,
                    kamar_inap.jam_masuk,
                    bangsal.nm_bangsal,
                    kamar.kd_kamar,
                    kamar.`status`
                  FROM kamar_inap
                  INNER JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat
                  INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                  INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
                  INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
                  WHERE kamar_inap.tgl_keluar = '0000-00-00'";

        $result = mysqli_query($koneksi, $query);

        if ($result) {
            $data = [];
            $kamar_count = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
                // Hitung jumlah kemunculan kd_kamar
                if (!isset($kamar_count[$row['kd_kamar']])) {
                    $kamar_count[$row['kd_kamar']] = 0;
                }
                $kamar_count[$row['kd_kamar']]++;
            }

            echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
echo "<table>
    <tr>
        <th>No</th>
        <th>NOMOR RAWAT</th>
        <th>NOMOR REKAM MEDIS</th>
        <th>NAMA PASIEN</th>
        <th>TANGGAL MASUK</th>
        <th>JAM MASUK</th>
        <th>NAMA BANGSAL</th>
        <th>KAMAR / BED</th>
        <th>STATUS</th>
    </tr>";

$no = 1;
foreach ($data as $row) {
    // Cek kondisi warning
    $class = "";
    if ($kamar_count[$row['kd_kamar']] > 1 || strtoupper($row['status']) == "KOSONG") {
        $class = "warning";
    }

    echo "<tr class='{$class}'>
            <td>{$no}</td>
            <td>{$row['no_rawat']}</td>
            <td>{$row['no_rkm_medis']}</td>
            <td>{$row['nm_pasien']}</td>
            <td>{$row['tgl_masuk']}</td>
            <td>{$row['jam_masuk']}</td>
            <td>{$row['nm_bangsal']}</td>
            <td>{$row['kd_kamar']}</td>
            <td>{$row['status']}</td>
        </tr>";
    $no++;
}
// Baris jumlah data
$total_data = count($data);
echo "<tr>
        <td colspan='9' style='text-align:right;font-weight:bold;'>Jumlah Data: {$total_data}</td>
      </tr>";
echo "</table>";
        }
        mysqli_close($koneksi);
    ?>
</body>
</html>

