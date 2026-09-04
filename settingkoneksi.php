<?php 
$koneksi = mysqli_connect("host","user","password","database");
if (mysqli_connect_errno()){
	echo "Koneksi database gagal : " . mysqli_connect_error();
}else{
echo "";
}

// KONFIGURASI BPJS MOBILE JKN API
$URLAPIMOBILEJKN = "https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev"; // URL API BPJS (ganti dengan production jika sudah live)
$CONSIDAPIMOBILEJKN = "your_consumer_id_here"; // Consumer ID dari BPJS
$SECRETKEYAPIMOBILEJKN = "your_secret_key_here"; // Secret Key dari BPJS
$USERKEYAPIMOBILEJKN = "your_user_key_here"; // User Key dari BPJS

// KONFIGURASI BPJS VCLAIM API
$URLVCLAIM = "https://apijkn.bpjs-kesehatan.go.id/vclaim-rest"; // URL API BPJS (ganti dengan production jika sudah live)
$CONSIDVCLAIM = "your_consumer_id_here"; // Consumer ID dari BPJS
$SECRETKEYVCLAIM = "your_secret_key_here"; // Secret Key dari BPJS
$USERKEYVCLAIM = "your_user_key_here"; // User Key dari BPJS

// KONFIGURASI BPJS APLICARE API
$URLAPLICARE = "https://new-api.bpjs-kesehatan.go.id/aplicaresws"; // URL API BPJS (ganti dengan production jika sudah live)
$CONSIDAPLICARE = ""; // Consumer ID dari BPJS
$SECRETKEYAPLICARE = ""; // Secret Key dari BPJS
$USERKEYAPLICARE = ""; // User Key dari BPJS
$KODEPPKAPLICARE = ""; // Kode PPK untuk APLICARE

// KONFIGURASI MOBILE JKN AUTH
$URLAUTHMJKN = "https://localhost/auth"; // URL Auth Mobile JKN
$USERNAMEAUTHMJKN = "usermjkn"; // Username untuk Auth
$PASSWORDAUTHMJKN = "passwordmjkn"; // Password untuk Auth

//KONFIGURASI SATU SEHAT
$URLAUTHSATUSEHAT = "https://api-satusehat.kemkes.go.id/oauth2/v1";
$URLFHIRSATUSEHAT = "https://api-satusehat.kemkes.go.id/fhir-r4/v1";
$ORGANIZATIONID = "zzz";
$CLIENTID = "zzz";
$CLIENTSECRET = "zzz";

// KONFIGURASI E-KLAIM
$URLEKLAIM = '';
$KODERS = '';
$TYPEKELAS = '';
$ENCRYPTIONKEY = ''

?>