<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RL 3.9 Kegiatan Radiologi</title>
    <style>
        h1 {
            font-family: Arial, sans-serif;
            color: green;
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            margin-top: 20px;
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
            margin: 15px 0;
        }
        .filter-form {
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .filter-form input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-form button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-form button:hover {
            background-color: #45a049;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }
        .export-button {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<?php
include 'koneksi.php';

$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

$query = "
    SELECT 
        jns_perawatan_radiologi.nm_perawatan,
        SUM(CASE WHEN pasien.jk = 'L' THEN 1 ELSE 0 END) AS jumlah_l,
        SUM(CASE WHEN pasien.jk = 'P' THEN 1 ELSE 0 END) AS jumlah_p
    FROM periksa_radiologi
    INNER JOIN reg_periksa ON periksa_radiologi.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN jns_perawatan_radiologi ON periksa_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw
    WHERE periksa_radiologi.tgl_periksa BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY jns_perawatan_radiologi.nm_perawatan
    ORDER BY jns_perawatan_radiologi.nm_perawatan
";

$result = mysqli_query($koneksi, $query);
?>

<header>
    <h1>RL 3.9 Kegiatan Radiologi</h1>
</header>

<div class="back-button">
    <a href="surveilans.php">← Kembali ke Menu Surveilans</a>
</div>

<form method="POST" class="filter-form">
    Filter Tanggal Pemeriksaan :
    <input type="date" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
    <input type="date" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
    <button type="submit" name="filter">Filter</button>
</form>

<?php
if ($result && mysqli_num_rows($result) > 0) {
    echo "<h2>Laporan Kunjungan Radiologi</h2>";
        echo "<p>Periode : " . htmlspecialchars($tanggal_awal) . " s/d " . htmlspecialchars($tanggal_akhir) . "</p>";
        ?>
        <!-- Tambahkan tombol copy sebelum tabel -->
        <button class='export-button' onclick='copyTableToClipboard("tabel-radiologi")'>Copy Data</button>
        <?php
        echo "<table id='tabel-radiologi'>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Perawatan</th>
                <th>L</th>
                <th>P</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>";

    $no = 1;
    $total_l = 0;
    $total_p = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $jumlah = $row['jumlah_l'] + $row['jumlah_p'];
        echo "<tr>
            <td>{$no}</td>
            <td>" . htmlspecialchars($row['nm_perawatan']) . "</td>
            <td>{$row['jumlah_l']}</td>
            <td>{$row['jumlah_p']}</td>
            <td>{$jumlah}</td>
        </tr>";
        $total_l += $row['jumlah_l'];
        $total_p += $row['jumlah_p'];
        $no++;
    }

    $total_all = $total_l + $total_p;

    echo "<tr>
        <td colspan='2'><strong>Total</strong></td>
        <td><strong>{$total_l}</strong></td>
        <td><strong>{$total_p}</strong></td>
        <td><strong>{$total_all}</strong></td>
    </tr>";

    echo "</tbody></table>";
} else {
    echo "<div class='no-data'>Tidak ada data ditemukan untuk periode yang dipilih.</div>";
}
?>

<!-- JavaScript Export to Excel -->
<script>
function copyTableToClipboard(tableID) {
    const table = document.getElementById(tableID);
    let range, selection;

    // Buat elemen <textarea> untuk menyalin teks
    const textarea = document.createElement("textarea");
    let text = "";

    // Ambil semua baris dari tabel
    for (let row of table.rows) {
        let rowData = [];
        for (let cell of row.cells) {
            rowData.push(cell.innerText);
        }
        text += rowData.join("\t") + "\n"; // gunakan tab antar kolom
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
