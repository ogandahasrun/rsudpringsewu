<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// Query untuk mengambil nama instansi dan logo dari database
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU"; // default jika tidak ada di database
$logo_src = "images/logo.png"; // default jika tidak ada di database

if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    if (!empty($row_instansi['logo'])) {
        // Konversi BLOB ke base64
        $logo_blob = $row_instansi['logo'];
        $logo_base64 = base64_encode($logo_blob);
        $logo_src = "data:image/png;base64," . $logo_base64;
    }
}

// Jika ada form login dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Gunakan prepared statement untuk keamanan
    $stmt = $koneksi->prepare("SELECT 
                        user.id_user, user.password 
                        FROM user 
                        WHERE aes_decrypt(user.id_user, 'nur') = ? 
                        AND aes_decrypt(user.password, 'windi') = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $cek = $result->num_rows;

    if ($cek > 0) {
        $_SESSION['username'] = $username;
        $_SESSION['status'] = "login";
        header("Location: index.php?page=Home");
        exit();
    } else {
        $error = "Username atau Password salah!";
    }
}

include 'login_view.php'; // tampilkan tampilan login
?>
