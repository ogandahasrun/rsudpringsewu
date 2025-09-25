<?php
include 'koneksi.php';
$query_instansi = "SELECT nama_instansi, alamat_instansi, kabupaten, propinsi, kontak, email, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    $alamat = $row_instansi['alamat_instansi'];
    $kabupaten = $row_instansi['kabupaten'];
    $propinsi = $row_instansi['propinsi'];
    $kontak = $row_instansi['kontak'];
    $email = $row_instansi['email'];
    if (!empty($row_instansi['logo'])) {
        $logo_blob = $row_instansi['logo'];
        $logo_base64 = base64_encode($logo_blob);
        $logo_src = "data:image/png;base64," . $logo_base64;
    } else {
        $logo_src = "images/logo.png";
    }
}
?>

<!-- File: header.php -->
<div class="header-container">
    <!-- Logo dari URL eksternal -->
    <img src="<?php echo $logo_src; ?>" alt="Logo RSUD Pringsewu" class="logo">
    <!-- Konten -->
    <div class="header-content">
        <h1>PEMERINTAH KABUPATEN PRINGSEWU</h1>
        <h1><strong><?php echo htmlspecialchars($nama_instansi); ?></strong></h1>
        <p><?php echo htmlspecialchars($alamat); ?></p>
        <p>Phone: <?php echo htmlspecialchars($kontak); ?> | Email: <?php echo htmlspecialchars($email); ?></p>
    </div>
</div>

<!-- Garis Pembatas -->
<div class="garis-pembatas"></div>