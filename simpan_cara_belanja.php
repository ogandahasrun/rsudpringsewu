<?php
include 'koneksi.php';
header('Content-Type: application/json');
$no_faktur = isset($_POST['no_faktur']) ? mysqli_real_escape_string($koneksi, $_POST['no_faktur']) : '';
$jenis_barang = isset($_POST['jenis_barang']) ? mysqli_real_escape_string($koneksi, $_POST['jenis_barang']) : '';
$cara_belanja = isset($_POST['cara_belanja']) ? mysqli_real_escape_string($koneksi, $_POST['cara_belanja']) : '';
if ($no_faktur == '') {
    echo json_encode(['success'=>false, 'msg'=>'No faktur kosong']);
    exit;
}
// Cek apakah sudah ada data
$cek = mysqli_query($koneksi, "SELECT no_faktur FROM pemesanan_cara_belanja WHERE no_faktur='$no_faktur'");
if ($cek && mysqli_num_rows($cek) > 0) {
    // Update
    $q = "UPDATE pemesanan_cara_belanja SET jenis_barang='$jenis_barang', cara_belanja='$cara_belanja' WHERE no_faktur='$no_faktur'";
} else {
    // Insert
    $q = "INSERT INTO pemesanan_cara_belanja (no_faktur, jenis_barang, cara_belanja) VALUES ('$no_faktur', '$jenis_barang', '$cara_belanja')";
}
$ok = mysqli_query($koneksi, $q);
if ($ok) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'msg'=>mysqli_error($koneksi)]);
}
?><?php
include 'koneksi.php';
header('Content-Type: application/json');
$no_faktur = isset($_POST['no_faktur']) ? mysqli_real_escape_string($koneksi, $_POST['no_faktur']) : '';
$jenis_barang = isset($_POST['jenis_barang']) ? mysqli_real_escape_string($koneksi, $_POST['jenis_barang']) : '';
$cara_belanja = isset($_POST['cara_belanja']) ? mysqli_real_escape_string($koneksi, $_POST['cara_belanja']) : '';
if ($no_faktur == '') {
    echo json_encode(['success'=>false, 'msg'=>'No faktur kosong']);
    exit;
}
// Cek apakah sudah ada data
$cek = mysqli_query($koneksi, "SELECT no_faktur FROM pemesanan_cara_belanja WHERE no_faktur='$no_faktur'");
if ($cek && mysqli_num_rows($cek) > 0) {
    // Update
    $q = "UPDATE pemesanan_cara_belanja SET jenis_barang='$jenis_barang', cara_belanja='$cara_belanja' WHERE no_faktur='$no_faktur'";
} else {
    // Insert
    $q = "INSERT INTO pemesanan_cara_belanja (no_faktur, jenis_barang, cara_belanja) VALUES ('$no_faktur', '$jenis_barang', '$cara_belanja')";
}
$ok = mysqli_query($koneksi, $q);
if ($ok) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'msg'=>mysqli_error($koneksi)]);
}
?>