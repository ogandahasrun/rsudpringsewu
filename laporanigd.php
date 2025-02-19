<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAPORAN BULANAN IGD</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            border: 1px solid #dddddd;
            padding: 8px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
        }

        form {
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            margin-right: 10px;
        }

        .form-group input[type="date"] {
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .form-group input[type="submit"] {
            padding: 8px 16px;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .form-group input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>LAPORAN BULANAN IGD</h1>

    <form method="post">
        <div class="form-group">
            <label for="filter_tanggal_start">Dari Tanggal:</label>
            <input type="date" id="filter_tanggal_start" name="filter_tanggal_start" required>
        </div>
        <div class="form-group">
            <label for="filter_tanggal_end">Sampai Tanggal:</label>
            <input type="date" id="filter_tanggal_end" name="filter_tanggal_end" required>
        </div>
        <div class="form-group">
            <input type="submit" value="Filter">
        </div>
    </form>


<?php
// Include file koneksi.php
include 'koneksi.php';

// Inisialisasi variabel filter tanggal
$filter_tanggal_start = "";
$filter_tanggal_end = "";

// Periksa apakah form filter tanggal telah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Periksa apakah tanggal awal dan akhir telah diisi
    if (!empty($_POST['filter_tanggal_start']) && !empty($_POST['filter_tanggal_end'])) {
        // Ambil nilai tanggal awal dan akhir dari form
        $filter_tanggal_start = $_POST['filter_tanggal_start'];
        $filter_tanggal_end = $_POST['filter_tanggal_end'];

        // Lakukan sanitasi input tanggal
        $filter_tanggal_start = mysqli_real_escape_string($koneksi, $filter_tanggal_start);
        $filter_tanggal_end = mysqli_real_escape_string($koneksi, $filter_tanggal_end);

        // Buat query dengan filter tanggal
        $query = "SELECT
                    reg_periksa.tgl_registrasi,
                    reg_periksa.no_rawat,
                    pasien.nm_pasien,
                    pasien.no_rkm_medis,
                    kelurahan.nm_kel,
                    kecamatan.nm_kec,
                    kabupaten.nm_kab,
                    propinsi.nm_prop,
                    reg_periksa.stts_daftar,
                    penjab.png_jawab,
                    poliklinik.nm_poli,
                    reg_periksa.status_lanjut,
                    bangsal.nm_bangsal,
                    dokter.nm_dokter,
                    diagnosa_pasien.kd_penyakit,
                    penyakit.nm_penyakit
                  FROM
                    reg_periksa
                    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                    INNER JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
                    INNER JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
                    INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
                    INNER JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
                    INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj AND pasien.kd_pj = penjab.kd_pj
                    INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                    LEFT JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat
                    LEFT JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
                    LEFT JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
                    INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                    LEFT JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
                    INNER JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
                  WHERE
                    reg_periksa.tgl_registrasi BETWEEN '$filter_tanggal_start' AND '$filter_tanggal_end'
                  ORDER BY
                    reg_periksa.tgl_registrasi ASC,
                    reg_periksa.no_rawat ASC";

        $result = mysqli_query($koneksi, $query);

        // Tampilkan hasil query
        if ($result) {
            // Cetak header tabel
            echo "<table border='1'>
                    <tr>
                        <th>Tanggal Registrasi</th>
                        <th>No Rawat</th>
                        <th>Nama Pasien</th>
                        <th>No Rekam Medis</th>
                        <th>Kelurahan</th>
                        <th>Kecamatan</th>
                        <th>Kabupaten</th>
                        <th>Propinsi</th>
                        <th>Status Daftar</th>
                        <th>PJ</th>
                        <th>Poliklinik</th>
                        <th>Status Lanjut</th>
                        <th>Bangsal</th>
                        <th>Nama Dokter</th>
                        <th>Kode Penyakit</th>
                        <th>Nama Penyakit</th>
                    </tr>";

            // Ambil data dari hasil query dan tampilkan
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$row['tgl_registrasi']}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_kel']}</td>
                        <td>{$row['nm_kec']}</td>
                        <td>{$row['nm_kab']}</td>
                        <td>{$row['nm_prop']}</td>
                        <td>{$row['stts_daftar']}</td>
                        <td>{$row['png_jawab']}</td>
                        <td>{$row['nm_poli']}</td>
                        <td>{$row['status_lanjut']}</td>
                        <td>{$row['nm_bangsal']}</td>
                        <td>{$row['nm_dokter']}</td>
                        <td>{$row['kd_penyakit']}</td>
                        <td>{$row['nm_penyakit']}</td>
                      </tr>";
            }

            echo "</table>";
        } else {
            echo "Error: " . mysqli_error($koneksi);
        }
    } else {
        echo "Silakan masukkan filter tanggal.";
    }
}

// Tutup koneksi
mysqli_close($koneksi);
?>

</body>
</html>
