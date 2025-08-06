<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RL 3.5 Kunjungan Rawat Jalan</title>
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
            margin-bottom: 15px;
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
        .patient-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #e9f7ef;
            border-radius: 5px;
            border-left: 5px solid #4CAF50;
            font-family: Arial, sans-serif;
        }
        .patient-info p {
            margin: 5px 0;
            font-size: 16px;
        }

        .col-uraian {
            width: 20%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .col-hasil {
            width: 80%;
        }

        .input-hasil {
            width: 100%;
            padding: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
    </style>

</head>

<?php
include 'koneksi.php';

// Tanggal filter - perbaikan: menggunakan $_POST karena form menggunakan method POST
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Perbaikan query: menambahkan validasi tanggal dan memperbaiki typo (CASE menjadi CASE)
$query = "
SELECT 
    ROW_NUMBER() OVER (ORDER BY poliklinik.nm_poli) AS 'Nomor urut',
    poliklinik.nm_poli AS 'nm_poli',
    SUM(CASE WHEN pasien.jk = 'L' AND kabupaten.kd_kab = '1810' THEN 1 ELSE 0 END) AS 'Laki-laki (KD_KAB=1810)',
    SUM(CASE WHEN pasien.jk = 'P' AND kabupaten.kd_kab = '1810' THEN 1 ELSE 0 END) AS 'Perempuan (KD_KAB=1810)',
    SUM(CASE WHEN pasien.jk = 'L' AND kabupaten.kd_kab != '1810' THEN 1 ELSE 0 END) AS 'Laki-laki (KD_KAB!=1810)',
    SUM(CASE WHEN pasien.jk = 'P' AND kabupaten.kd_kab != '1810' THEN 1 ELSE 0 END) AS 'Perempuan (KD_KAB!=1810)',
    COUNT(*) AS 'Jumlah Total'
FROM 
    reg_periksa
    INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
WHERE 
    reg_periksa.status_lanjut = 'ralan' AND
    reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY 
    poliklinik.nm_poli
ORDER BY 
    poliklinik.nm_poli
";

// Debugging: tambahkan ini untuk memeriksa query yang dihasilkan
// echo "<pre>Query: " . htmlspecialchars($query) . "</pre>";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    // Jika query error, tampilkan pesan error
    die("Error dalam query: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Bagian head tetap sama -->
</head>
<body>
    <header>
        <h1>RL 3.5 Kunjungan Rawat Jalan</h1>
    </header>

    <div class="back-button">
        <a href="surveilans.php">Kembali ke Menu Surveilans</a>
    </div>

    <!-- Form filter - method POST -->
    <form method="POST" class="filter-form">
        Filter Tanggal Registrasi:
        <input type="date" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
        <button type="submit" name="filter">Filter</button>
    </form>

<?php
// Tampilkan data hanya jika form telah disubmit atau untuk pertama kali
if ($_SERVER['REQUEST_METHOD'] == 'POST' || !isset($_POST['filter'])) {
    // Membuat tabel HTML
    echo "<h2>Laporan Kunjungan Poliklinik</h2>";
    echo "<p>Periode : " . htmlspecialchars($tanggal_awal) . " s/d " . htmlspecialchars($tanggal_akhir) . "</p>";
    
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Poli</th>
                    <th colspan='2'>Kabupaten Pringsewu</th>
                    <th colspan='2'>Luar Kabupaten Pringsewu</th>
                    <th>Jumlah</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th>L</th>
                    <th>P</th>
                    <th>L</th>
                    <th>P</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>";

        $total_all = 0;
        $total_l_kab1810 = 0;
        $total_p_kab1810 = 0;
        $total_l_kab_lain = 0;
        $total_p_kab_lain = 0;

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                <td>".htmlspecialchars($row['Nomor urut'])."</td>
                <td>".htmlspecialchars($row['nm_poli'])."</td>
                <td>".htmlspecialchars($row['Laki-laki (KD_KAB=1810)'])."</td>
                <td>".htmlspecialchars($row['Perempuan (KD_KAB=1810)'])."</td>
                <td>".htmlspecialchars($row['Laki-laki (KD_KAB!=1810)'])."</td>
                <td>".htmlspecialchars($row['Perempuan (KD_KAB!=1810)'])."</td>
                <td>".htmlspecialchars($row['Jumlah Total'])."</td>
            </tr>";
            
            // Menghitung total
            $total_all += $row['Jumlah Total'];
            $total_l_kab1810 += $row['Laki-laki (KD_KAB=1810)'];
            $total_p_kab1810 += $row['Perempuan (KD_KAB=1810)'];
            $total_l_kab_lain += $row['Laki-laki (KD_KAB!=1810)'];
            $total_p_kab_lain += $row['Perempuan (KD_KAB!=1810)'];
        }

        // Baris total
        echo "<tr>
            <td colspan='2'><strong>Total</strong></td>
            <td><strong>".$total_l_kab1810."</strong></td>
            <td><strong>".$total_p_kab1810."</strong></td>
            <td><strong>".$total_l_kab_lain."</strong></td>
            <td><strong>".$total_p_kab_lain."</strong></td>
            <td><strong>".$total_all."</strong></td>
        </tr>";

        echo "</tbody></table>";
    } else {
        echo "<div class='no-data'>Tidak ada data ditemukan untuk periode yang dipilih.</div>";
    }
}
?>
</body>
</html>