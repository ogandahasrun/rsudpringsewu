<?php
$koneksi = mysqli_connect("localhost", "bpjsfktl", "bpjsfktl", "sikbaru");
if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

echo "Memulai sinkronisasi data pengajuan cuti lama...\n";

// Ambil semua pengajuan cuti yang masih dalam status 'Proses Pengajuan' tapi belum punya entri persetujuan
$query = "SELECT no_pengajuan, nik, nik_pj FROM pengajuan_cuti WHERE status = 'Proses Pengajuan' AND no_pengajuan NOT IN (SELECT DISTINCT no_pengajuan FROM persetujuan_cuti)";
$res = mysqli_query($koneksi, $query);

$count = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $no_pengajuan = $row['no_pengajuan'];
    $nik_pemohon = $row['nik'];
    $nik_pj = $row['nik_pj'];
    
    // Cari rantai atasan secara rekursif
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
    
    // Fallback jika tidak ada atasan terdaftar di atasan_pegawai
    if (empty($approvers)) {
        $approvers[] = [
            'level' => 1,
            'nik_approver' => $nik_pj
        ];
    }
    
    // Simpan data persetujuan ke tabel persetujuan_cuti
    $query_insert_pc = "INSERT INTO persetujuan_cuti (no_pengajuan, level, nik_approver, status) VALUES (?, ?, ?, 'Pending')";
    $stmt_insert_pc = $koneksi->prepare($query_insert_pc);
    
    $pc_ok = true;
    foreach ($approvers as $app) {
        $stmt_insert_pc->bind_param("sis", $no_pengajuan, $app['level'], $app['nik_approver']);
        if ($stmt_insert_pc->execute()) {
            echo "Menambahkan persetujuan Level {$app['level']} untuk pengajuan $no_pengajuan (Approver: {$app['nik_approver']})\n";
        } else {
            $pc_ok = false;
        }
    }
    
    if ($pc_ok) {
        $count++;
    }
}

echo "Sinkronisasi selesai! $count data berhasil disinkronkan.\n";
?>
