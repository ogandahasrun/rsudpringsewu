<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pemeriksaan Laboratorium</title>
    <style>
        body {
            font-family: Arial, sans-serif; /* Ganti jenis huruf sesuai keinginan Anda */
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table thead th,
        .data-table tbody td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .data-table thead th {
            background-color: #f2f2f2;
            text-align: center; /* Meratakan teks ke tengah */
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<a href="index.php">
    <img src="images/logo.png" alt="Logo" width="80" height="100">
</a>

</body>
</html>

<br>
Masukkan data pemeriksaan laboratorium yang dicari
<br>

<?php
// Include file koneksi.php
include 'koneksi.php';

// Ambil keyword pencarian jika ada
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

// Query SQL dengan tambahan kondisi pencarian jika keyword sudah dimasukkan
$query = "SELECT
            template_laboratorium.Pemeriksaan,
            reg_periksa.no_rawat,
            pasien.no_rkm_medis,
            pasien.nm_pasien,
            pasien.tgl_lahir,
            pasien.no_ktp,
            kelurahan.nm_kel,
            kecamatan.nm_kec,
            kabupaten.nm_kab,
            propinsi.nm_prop,
            pasien.pnd,
            pasien.pekerjaan,
            pasien.no_tlp,
            detail_periksa_lab.tgl_periksa,
            detail_periksa_lab.nilai
          FROM
            reg_periksa
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            INNER JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
            INNER JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
            INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
            INNER JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
            INNER JOIN detail_periksa_lab ON detail_periksa_lab.no_rawat = reg_periksa.no_rawat
            INNER JOIN template_laboratorium ON detail_periksa_lab.id_template = template_laboratorium.id_template
          WHERE
            template_laboratorium.Pemeriksaan LIKE '%$keyword%' OR
            pasien.nm_pasien LIKE '%$keyword%' OR
            pasien.no_rkm_medis LIKE '%$keyword%'
          ORDER BY
            template_laboratorium.Pemeriksaan ASC";

// Eksekusi query
$result = mysqli_query($koneksi, $query);

// Memeriksa apakah query berhasil dieksekusi
if (!$result) {
    die("Query gagal: " . mysqli_error($koneksi));
}

// Menampilkan form pencarian
echo "<form method='GET' action=''>
        <input type='text' name='keyword' placeholder='Masukkan kata kunci' value='$keyword'>
        <input type='submit' value='Cari'>
      </form>";

// Jika keyword sudah dimasukkan, tampilkan tabel
if (!empty($keyword)) {
    // Menampilkan data dalam tabel HTML dengan CSS
    echo "<style>
            .data-table {
                width: 100%;
                border-collapse: collapse;
            }

            .data-table thead th,
            .data-table tbody td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }

            .data-table thead th {
                background-color: #f2f2f2;
                text-align: center; /* Meratakan teks ke tengah */
            }

            .data-table tbody tr:nth-child(even) {
                background-color: #f2f2f2;
            }
          </style>";

    
    echo "<table class='data-table'>
            <thead>
              <tr>
                <th>Pemeriksaan</th>
                <th>No Rawat</th>
                <th>No Rekam Medis</th>
                <th>Nama Pasien</th>
                <th>Tanggal Lahir</th>
                <th>No KTP</th>
                <th>Nama Kelurahan</th>
                <th>Nama Kecamatan</th>
                <th>Nama Kabupaten</th>
                <th>Nama Propinsi</th>
                <th>Pendidikan</th>
                <th>Pekerjaan</th>
                <th>No Telepon</th>
                <th>Tanggal Periksa</th>
                <th>Nilai</th>
              </tr>
            </thead>
            <tbody>";

    // Loop untuk menampilkan data baris per baris
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Pemeriksaan'] . "</td>";
        echo "<td>" . $row['no_rawat'] . "</td>";
        echo "<td>" . $row['no_rkm_medis'] . "</td>";
        echo "<td>" . $row['nm_pasien'] . "</td>";
        echo "<td>" . $row['tgl_lahir'] . "</td>";
        echo "<td>" . $row['no_ktp'] . "</td>";
        echo "<td>" . $row['nm_kel'] . "</td>";
        echo "<td>" . $row['nm_kec'] . "</td>";
        echo "<td>" . $row['nm_kab'] . "</td>";
        echo "<td>" . $row['nm_prop'] . "</td>";
        echo "<td>" . $row['pnd'] . "</td>";
        echo "<td>" . $row['pekerjaan'] . "</td>";
        echo "<td>" . $row['no_tlp'] . "</td>";
        echo "<td>" . $row['tgl_periksa'] . "</td>";
        echo "<td>" . $row['nilai'] . "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
}

// Membebaskan hasil query
mysqli_free_result($result);

// Menutup koneksi
mysqli_close($koneksi);
?>
