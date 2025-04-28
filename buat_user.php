<?php
include 'koneksi.php';

if (isset($_POST['submit'])) {
    $nik = mysqli_real_escape_string($koneksi, $_POST['nik']);
    $password_asli = mysqli_real_escape_string($koneksi, $_POST['password']);
    $hash_password = password_hash($password_asli, PASSWORD_DEFAULT);

    $query = "INSERT INTO bw_user (nik, password) VALUES ('$nik', '$hash_password')";
    if (mysqli_query($koneksi, $query)) {
        echo "User berhasil dibuat!";
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Buat User Baru</title>
</head>
<body>
    <h2>Form Buat User Baru</h2>
    <form method="POST">
        <input type="text" name="nik" placeholder="NIK" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit" name="submit">Buat User</button>
    </form>
</body>
</html>
