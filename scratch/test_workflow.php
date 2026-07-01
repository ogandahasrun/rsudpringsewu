<?php
$koneksi = mysqli_connect("localhost", "bpjsfktl", "bpjsfktl", "sikbaru");
if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// 1. Bersihkan sisa data tes sebelumnya jika ada
mysqli_query($koneksi, "DELETE FROM persetujuan_cuti WHERE no_pengajuan LIKE 'PCTEST%'");
mysqli_query($koneksi, "DELETE FROM pengajuan_cuti WHERE no_pengajuan LIKE 'PCTEST%'");

echo "Memulai uji coba alur persetujuan berjenjang...\n";

// 2. Simulasikan pembuatan pengajuan cuti baru oleh FREDIAN AHMAD (NIK: 123124)
$nik_pemohon = '123124';
$no_pengajuan = 'PCTEST001';
$tanggal_pengajuan = date('Y-m-d');
$tanggal_awal = '2026-07-06';
$tanggal_akhir = '2026-07-07';
$urgensi = 'Tahunan';
$alamat = 'Jl. Test No. 123';
$jumlah = 2;
$kepentingan = 'Tes Alur Kerja Berjenjang';
$nik_pj = 'D0000004';
$status_default = 'Proses Pengajuan';

$query_insert = "INSERT INTO pengajuan_cuti (no_pengajuan, tanggal, tanggal_awal, tanggal_akhir, nik, urgensi, alamat, jumlah, kepentingan, nik_pj, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_insert = $koneksi->prepare($query_insert);
$stmt_insert->bind_param("sssssssisss", $no_pengajuan, $tanggal_pengajuan, $tanggal_awal, $tanggal_akhir, $nik_pemohon, $urgensi, $alamat, $jumlah, $kepentingan, $nik_pj, $status_default);

if ($stmt_insert->execute()) {
    echo "Langkah 1: Pengajuan $no_pengajuan berhasil dibuat.\n";
} else {
    die("Langkah 1 Gagal: " . mysqli_error($koneksi));
}

// 3. Cari rantai atasan dan insert ke persetujuan_cuti
$approvers = [];
$current_employee = $nik_pemohon;

for ($level = 1; $level <= 3; $level++) {
    $query_atasan = "SELECT nik_atasan FROM atasan_pegawai WHERE nik = ?";
    $stmt_atasan = $koneksi->prepare($query_atasan);
    $stmt_atasan->bind_param("s", $current_employee);
    $stmt_atasan->execute();
    $res_atasan = $stmt_atasan->get_result();
    
    if ($row_atasan = $res_atasan->fetch_assoc()) {
        $atasan_nik = $row_atasan['nik_atasan'];
        if (!empty($atasan_nik)) {
            $approvers[] = [
                'level' => $level,
                'nik_approver' => $atasan_nik
            ];
            $current_employee = $atasan_nik;
        } else {
            break;
        }
    } else {
        break;
    }
}

if (empty($approvers)) {
    $approvers[] = [
        'level' => 1,
        'nik_approver' => $nik_pj
    ];
}

$query_insert_pc = "INSERT INTO persetujuan_cuti (no_pengajuan, level, nik_approver, status) VALUES (?, ?, ?, 'Pending')";
$stmt_insert_pc = $koneksi->prepare($query_insert_pc);

foreach ($approvers as $app) {
    $stmt_insert_pc->bind_param("sis", $no_pengajuan, $app['level'], $app['nik_approver']);
    $stmt_insert_pc->execute();
}

echo "Langkah 2: Menghasilkan rantai persetujuan di database:\n";
$q_pc = mysqli_query($koneksi, "SELECT * FROM persetujuan_cuti WHERE no_pengajuan = '$no_pengajuan'");
while ($r_pc = mysqli_fetch_assoc($q_pc)) {
    echo "  - Level {$r_pc['level']} Approver NIK: {$r_pc['nik_approver']}, Status: {$r_pc['status']}\n";
}

// Fungsi pembantu untuk memproses persetujuan per level
function approve_level($koneksi, $no_pengajuan, $level, $nik_approver) {
    $now = date('Y-m-d H:i:s');
    $catatan = "Approved by level $level";
    
    // Ambil ID persetujuan
    $q = mysqli_query($koneksi, "SELECT id FROM persetujuan_cuti WHERE no_pengajuan = '$no_pengajuan' AND level = $level AND nik_approver = '$nik_approver'");
    $r = mysqli_fetch_assoc($q);
    $persetujuan_id = $r['id'];
    
    $query_update_pc = "UPDATE persetujuan_cuti SET status = 'Disetujui', tanggal_keputusan = ?, catatan = ? WHERE id = ?";
    $stmt = $koneksi->prepare($query_update_pc);
    $stmt->bind_param("ssi", $now, $catatan, $persetujuan_id);
    $stmt->execute();
    
    // Cek max level
    $q_max = mysqli_query($koneksi, "SELECT MAX(level) AS max_level FROM persetujuan_cuti WHERE no_pengajuan = '$no_pengajuan'");
    $r_max = mysqli_fetch_assoc($q_max);
    $max_level = (int)$r_max['max_level'];
    
    if ($level === $max_level) {
        mysqli_query($koneksi, "UPDATE pengajuan_cuti SET status = 'Disetujui' WHERE no_pengajuan = '$no_pengajuan'");
    }
}

// 4. Uji Persetujuan Berjenjang:
// Level 1: dr. Hilyatul Nadia (D0000004)
approve_level($koneksi, $no_pengajuan, 1, 'D0000004');
echo "Langkah 3: Level 1 disetujui.\n";

// Level 2: dr. Aisyah (D0000002)
approve_level($koneksi, $no_pengajuan, 2, 'D0000002');
echo "Langkah 4: Level 2 disetujui.\n";

// Level 3: AGUS SALIM (010101)
approve_level($koneksi, $no_pengajuan, 3, '010101');
echo "Langkah 5: Level 3 disetujui.\n";

// 5. Cek Status Pengajuan Utama
$q_main = mysqli_query($koneksi, "SELECT status FROM pengajuan_cuti WHERE no_pengajuan = '$no_pengajuan'");
$r_main = mysqli_fetch_assoc($q_main);
echo "Status Akhir Pengajuan Cuti di Database: " . $r_main['status'] . "\n";

if ($r_main['status'] === 'Disetujui') {
    echo "VERIFIKASI WORKFLOW BERHASIL!\n";
} else {
    echo "VERIFIKASI WORKFLOW GAGAL!\n";
    exit(1);
}

// Bersihkan data tes
mysqli_query($koneksi, "DELETE FROM persetujuan_cuti WHERE no_pengajuan = '$no_pengajuan'");
mysqli_query($koneksi, "DELETE FROM pengajuan_cuti WHERE no_pengajuan = '$no_pengajuan'");
?>
