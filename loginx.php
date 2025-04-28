<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['nik'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $nik = mysqli_real_escape_string($koneksi, $_POST['nik']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $query = "SELECT * FROM bw_user WHERE nik='$nik'";
    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['nik'] = $user['nik'];
            header('Location: index.php');
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "NIK tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login RSUD Pringsewu</title>
    <style>
        body {
            background: linear-gradient(to right, #00c6ff, #0072ff);
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .login-container {
            width: 350px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            margin: 10px 0;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            margin-top: 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .error {
            background: #ffdddd;
            border: 1px solid #ff5c5c;
            padding: 10px;
            margin-bottom: 15px;
            color: #d8000c;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Login</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="nik" placeholder="NIK" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>
</div>

</body>
</html>
