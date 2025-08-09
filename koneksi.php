<?php 
$koneksi = mysqli_connect("localhost","backup","backup","sik");
// 103.144.213.212 lec
// 103.151.140.164 psw
// Check connection
if (mysqli_connect_errno()){
	echo "Koneksi database gagal : " . mysqli_connect_error();
}else{
echo "";
}
?>