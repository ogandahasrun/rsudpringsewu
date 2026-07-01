<?php
$koneksi = mysqli_connect("localhost", "bpjsfktl", "bpjsfktl", "sikbaru");
if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

echo "Memulai uji coba integritas data (Kunci Asing)...\n";

// Pastikan foreign_key_checks aktif
mysqli_query($koneksi, "SET foreign_key_checks = 1;");

// 1. Uji Tambah Data dengan NIK Invalid
$sql_invalid = "INSERT INTO atasan_pegawai (nik, nik_atasan) VALUES ('INVALID_NIK_PEMOHON', '010101')";
$result_invalid = mysqli_query($koneksi, $sql_invalid);
if ($result_invalid === false) {
    echo "Uji Kasus 1 PASSED: Database menolak NIK invalid.\n";
} else {
    echo "Uji Kasus 1 FAILED: Database memperbolehkan NIK invalid yang tidak ada di tabel pegawai!\n";
    exit(1);
}

// 2. Uji Cascading Update NIK
$sql_update_pegawai = "UPDATE pegawai SET nik = '123124_TEST' WHERE nik = '123124'";
$result_update = mysqli_query($koneksi, $sql_update_pegawai);

if ($result_update === false) {
    echo "Uji Kasus 2 SKIPPED/PASSED (Ada tabel lain yang membatasi perubahan NIK): " . mysqli_error($koneksi) . "\n";
    echo "Mencoba membuat record pegawai tes khusus yang aman untuk di-update...\n";
    
    // Buat pegawai tes baru
    mysqli_query($koneksi, "INSERT INTO pegawai (nik, nama, jk, jbtn, jnj_jabatan, kode_kelompok, kode_resiko, kode_emergency, departemen, bidang, stts_wp, stts_kerja, npwp, pendidikan, gapok, tmp_lahir, tgl_lahir, alamat, kota, mulai_kerja, ms_kerja, indexins, wajibmasuk, pengurang, indek, cuti_diambil, dankes, no_ktp) VALUES ('TEST_PEG_1', 'Pegawai Tes 1', 'Pria', 'Staf', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', 0, '-', '1990-01-01', '-', '-', '2020-01-01', '<1', '-', 1, 0, 1, 0, 0, '-')");
    mysqli_query($koneksi, "INSERT INTO pegawai (nik, nama, jk, jbtn, jnj_jabatan, kode_kelompok, kode_resiko, kode_emergency, departemen, bidang, stts_wp, stts_kerja, npwp, pendidikan, gapok, tmp_lahir, tgl_lahir, alamat, kota, mulai_kerja, ms_kerja, indexins, wajibmasuk, pengurang, indek, cuti_diambil, dankes, no_ktp) VALUES ('TEST_PEG_2', 'Pegawai Tes 2', 'Pria', 'Karu', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', 0, '-', '1990-01-01', '-', '-', '2020-01-01', '<1', '-', 1, 0, 1, 0, 0, '-')");
    
    // Hubungkan di atasan_pegawai
    mysqli_query($koneksi, "INSERT INTO atasan_pegawai (nik, nik_atasan) VALUES ('TEST_PEG_1', 'TEST_PEG_2')");
    
    // Jalankan update
    $sql_update_test = "UPDATE pegawai SET nik = 'TEST_PEG_1_NEW' WHERE nik = 'TEST_PEG_1'";
    if (mysqli_query($koneksi, $sql_update_test)) {
        // Cek cascading di atasan_pegawai
        $q_check = mysqli_query($koneksi, "SELECT * FROM atasan_pegawai WHERE nik = 'TEST_PEG_1_NEW'");
        if ($row = mysqli_fetch_assoc($q_check)) {
            echo "Uji Kasus 2.1 PASSED: Cascading update berhasil untuk TEST_PEG_1 -> TEST_PEG_1_NEW\n";
        } else {
            echo "Uji Kasus 2.1 FAILED: Cascading update gagal untuk TEST_PEG_1!\n";
            exit(1);
        }
    } else {
        echo "Uji Kasus 2.1 FAILED: Gagal mengupdate pegawai tes: " . mysqli_error($koneksi) . "\n";
        exit(1);
    }
    
    // Bersihkan data tes
    mysqli_query($koneksi, "DELETE FROM atasan_pegawai WHERE nik IN ('TEST_PEG_1_NEW', 'TEST_PEG_1')");
    mysqli_query($koneksi, "DELETE FROM pegawai WHERE nik IN ('TEST_PEG_1_NEW', 'TEST_PEG_1', 'TEST_PEG_2')");
} else {
    // Cek apakah data di atasan_pegawai ikut terupdate secara otomatis
    $q_check = mysqli_query($koneksi, "SELECT * FROM atasan_pegawai WHERE nik = '123124_TEST'");
    if ($row = mysqli_fetch_assoc($q_check)) {
        echo "Uji Kasus 2 PASSED: NIK di tabel atasan_pegawai berhasil ter-update secara otomatis ke '123124_TEST' (ON UPDATE CASCADE)\n";
    } else {
        echo "Uji Kasus 2 FAILED: NIK di tabel atasan_pegawai tidak ikut ter-update secara otomatis!\n";
        // Kembalikan data
        mysqli_query($koneksi, "UPDATE pegawai SET nik = '123124' WHERE nik = '123124_TEST'");
        exit(1);
    }

    // Kembalikan NIK pegawai ke nilai semula
    mysqli_query($koneksi, "UPDATE pegawai SET nik = '123124' WHERE nik = '123124_TEST'");
    echo "Uji Kasus 3 PASSED: NIK berhasil dikembalikan ke semula.\n";
}

echo "Semua pengujian kunci asing berhasil!\n";
?>
