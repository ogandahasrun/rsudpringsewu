<?php 
$koneksi = mysqli_connect("192.169.1.1","backup","backup","sik");
// Check connection
if (mysqli_connect_errno()){
	echo "Koneksi database gagal : " . mysqli_connect_error();
}else{
echo "";
}
?>