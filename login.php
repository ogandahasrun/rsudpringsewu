<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$error = '';

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
