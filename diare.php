<?php
// Tambahkan gaya CSS di sini
echo "
<style>
    table {
        border-collapse: collapse;
        width: 100%;
    }
    th, td {
        padding: 12px;
        text-align: left;
    }
    th {
        background-color: #3498db;
        color: #ffffff;
    }
    tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    tr:hover {
        background-color: #e5e5e5;
    }
</style>";

$host = '192.168.1.88';
$user = 'backup';
$password = 'backup';
$database = 'sik';

try {
    // Membuat koneksi ke database menggunakan PDO
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    
    // Menyiapkan query
    $query = "
    SELECT
    reg_periksa.no_rawat AS no_rawat,
    pasien.no_rkm_medis AS no_rkm_medis,
    pasien.nm_pasien AS nm_pasien,
    pasien.no_ktp AS no_ktp,
    penyakit.nm_penyakit AS nm_penyakit,
    pasien.alamat AS alamat,
    kelurahan.nm_kel AS nm_kel,
    kecamatan.nm_kec AS nm_kec,
    kabupaten.nm_kab AS nm_kab,
    propinsi.nm_prop AS nm_prop,
    kamar_inap.tgl_masuk AS tgl_masuk,
    kamar_inap.tgl_keluar AS tgl_keluar,
    kamar_inap.lama AS lama
    FROM
    ((((((((reg_periksa
    JOIN pasien ON (reg_periksa.no_rkm_medis = pasien.no_rkm_medis))
    JOIN kelurahan ON (pasien.kd_kel = kelurahan.kd_kel))
    JOIN kecamatan ON (pasien.kd_kec = kecamatan.kd_kec))
    JOIN kabupaten ON (pasien.kd_kab = kabupaten.kd_kab))
    JOIN propinsi ON (pasien.kd_prop = propinsi.kd_prop))
    JOIN kamar_inap ON (kamar_inap.no_rawat = reg_periksa.no_rawat))
    JOIN diagnosa_pasien ON (diagnosa_pasien.no_rawat = reg_periksa.no_rawat))
    JOIN penyakit ON (diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit))
    WHERE
    diagnosa_pasien.kd_penyakit IN ('A09', 'B54')
    ";

    // Menjalankan query
    $statement = $pdo->query($query);

    // Mendapatkan hasil query sebagai array asosiatif
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Menampilkan hasil query
    echo "<table border='1'>";
    echo "<tr>";
    foreach ($results[0] as $key => $value) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    foreach ($results as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    // Tangani kesalahan koneksi atau query
    echo "Error: " . $e->getMessage();
}
?>
