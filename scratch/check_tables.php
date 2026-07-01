<?php
$c = mysqli_connect("localhost","bpjsfktl","bpjsfktl","sikbaru");
if (!$c) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
$q = mysqli_query($c, "SELECT DISTINCT nik, status FROM pengajuan_cuti LIMIT 5");
echo "Sample leave requests:\n";
while ($row = mysqli_fetch_assoc($q)) {
    print_r($row);
}
?>
