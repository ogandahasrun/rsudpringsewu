<?php 
$koneksi = mysqli_connect("103.56.207.66","backup","backup","sik");
// Check connection
if (mysqli_connect_errno()){
	echo "Koneksi database gagal : " . mysqli_connect_error();
}else{
echo "";
}
?>