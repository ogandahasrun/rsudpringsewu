<?php
$c = mysqli_connect("localhost","bpjsfktl","bpjsfktl","sikbaru");
if (!$c) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$q = mysqli_query($c, "SELECT no_pengajuan, tanggal, nik, status FROM pengajuan_cuti WHERE status = 'Proses Pengajuan'");
echo "Active leave requests in pengajuan_cuti:\n";
while ($row = mysqli_fetch_assoc($q)) {
    $no = $row['no_pengajuan'];
    // Cek di persetujuan_cuti
    $q_pc = mysqli_query($c, "SELECT COUNT(*) FROM persetujuan_cuti WHERE no_pengajuan = '$no'");
    $r_pc = mysqli_fetch_row($q_pc);
    $pc_count = $r_pc[0];
    
    echo "No: $no, NIK Pemohon: {$row['nik']}, Tanggal: {$row['tanggal']}, Status: {$row['status']}, Approval Records Count: $pc_count\n";
}
?>
