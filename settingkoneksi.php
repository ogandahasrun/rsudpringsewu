<?php 
$koneksi = mysqli_connect("host","user","password","database");
// Check connection
if (mysqli_connect_errno()){
	echo "Koneksi database gagal : " . mysqli_connect_error();
}else{
echo "";
}

// ========================================
// KONFIGURASI BPJS MOBILE JKN API
// ========================================
$URLAPIMOBILEJKN = "https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev"; // URL API BPJS (ganti dengan production jika sudah live)
$CONSIDAPIMOBILEJKN = "your_consumer_id_here"; // Consumer ID dari BPJS
$SECRETKEYAPIMOBILEJKN = "your_secret_key_here"; // Secret Key dari BPJS
$USERKEYAPIMOBILEJKN = "your_user_key_here"; // User Key dari BPJS

?>