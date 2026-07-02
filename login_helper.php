<?php
session_start();
include 'koneksi.php';

// Membantu login instan sebagai NIK apa saja untuk kebutuhan pengujian
if (isset($_GET['nik'])) {
    $nik = $_GET['nik'];
    
    // Cek apakah NIK tersebut ada di tabel pegawai
    $query = "SELECT nama FROM pegawai WHERE nik = ? LIMIT 1";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $_SESSION['username'] = $nik;
        $_SESSION['status'] = "login";
        echo "<div style='font-family:sans-serif; padding:20px; background:#dcfce7; color:#15803d; border-radius:8px;'>";
        echo "<strong>Berhasil Login!</strong> Anda sekarang masuk sebagai <strong>" . htmlspecialchars($row['nama']) . " (NIK: " . htmlspecialchars($nik) . ")</strong>.<br><br>";
        echo "<a href='pengajuan_cuti.php' style='background:#15803d; color:#fff; padding:8px 16px; text-decoration:none; border-radius:5px;'>Buka Halaman Cuti</a>";
        echo "</div>";
    } else {
        echo "<div style='font-family:sans-serif; padding:20px; background:#fee2e2; color:#b91c1c; border-radius:8px;'>";
        echo "<strong>Gagal!</strong> NIK <strong>" . htmlspecialchars($nik) . "</strong> tidak ditemukan di database tabel pegawai.";
        echo "</div>";
    }
} else {
    // Tampilkan daftar NIK untuk login instan cepat
    echo "<div style='font-family:sans-serif; padding:20px;'>";
    echo "<h2>Helper Login Cepat untuk Pengujian:</h2>";
    echo "<ul>";
    echo "<li><a href='login_helper.php?nik=123124'>Login sebagai FREDIAN AHMAD (Staf)</a></li>";
    echo "<li><a href='login_helper.php?nik=D0000004'>Login sebagai dr. Hilyatul Nadia (Karu - Level 1)</a></li>";
    echo "<li><a href='login_helper.php?nik=D0000002'>Login sebagai dr. Aisyah (Kasi - Level 2)</a></li>";
    echo "<li><a href='login_helper.php?nik=010101'>Login sebagai AGUS SALIM (HRD - Level 3)</a></li>";
    echo "</ul>";
    echo "</div>";
}
?>
