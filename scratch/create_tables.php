<?php
$koneksi = mysqli_connect("localhost", "bpjsfktl", "bpjsfktl", "sikbaru");
if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Drop tabel lama terlebih dahulu untuk menghindari konflik foreign key atau constraint lama
mysqli_query($koneksi, "DROP TABLE IF EXISTS persetujuan_cuti;");
mysqli_query($koneksi, "DROP TABLE IF EXISTS atasan_pegawai;");

// 1. Buat tabel atasan_pegawai dengan FOREIGN KEY constraints
$sql_atasan = "CREATE TABLE atasan_pegawai (
    nik VARCHAR(20) NOT NULL,
    nik_atasan VARCHAR(20) NOT NULL,
    PRIMARY KEY (nik),
    FOREIGN KEY (nik) REFERENCES pegawai(nik) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (nik_atasan) REFERENCES pegawai(nik) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

if (mysqli_query($koneksi, $sql_atasan)) {
    echo "Tabel atasan_pegawai dengan kunci asing berhasil dibuat.\n";
} else {
    die("Gagal membuat tabel atasan_pegawai: " . mysqli_error($koneksi));
}

// 2. Buat tabel persetujuan_cuti dengan FOREIGN KEY constraints
$sql_persetujuan = "CREATE TABLE persetujuan_cuti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_pengajuan VARCHAR(17) NOT NULL,
    level INT NOT NULL,
    nik_approver VARCHAR(20) NOT NULL,
    status ENUM('Pending', 'Disetujui', 'Ditolak') DEFAULT 'Pending',
    tanggal_keputusan DATETIME NULL,
    catatan VARCHAR(150) NULL,
    FOREIGN KEY (no_pengajuan) REFERENCES pengajuan_cuti(no_pengajuan) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (nik_approver) REFERENCES pegawai(nik) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

if (mysqli_query($koneksi, $sql_persetujuan)) {
    echo "Tabel persetujuan_cuti dengan kunci asing berhasil dibuat.\n";
} else {
    die("Gagal membuat tabel persetujuan_cuti: " . mysqli_error($koneksi));
}

// 3. Masukkan data uji coba hirarki (Staff -> Karu -> Kasi -> HRD)
$test_hierarchy = [
    ['123124', 'D0000004'],   // FREDIAN AHMAD -> dr. Hilyatul Nadia
    ['D0000004', 'D0000002'], // dr. Hilyatul Nadia -> dr. Aisyah
    ['D0000002', '010101'],   // dr. Aisyah -> AGUS SALIM
];

foreach ($test_hierarchy as $row) {
    $nik = $row[0];
    $nik_atasan = $row[1];
    
    $sql_insert = "REPLACE INTO atasan_pegawai (nik, nik_atasan) VALUES ('$nik', '$nik_atasan')";
    if (mysqli_query($koneksi, $sql_insert)) {
        echo "Data hirarki $nik -> $nik_atasan berhasil didaftarkan.\n";
    } else {
        echo "Gagal mendaftarkan data hirarki $nik -> $nik_atasan: " . mysqli_error($koneksi) . "\n";
    }
}

echo "Migrasi database selesai!\n";
?>
