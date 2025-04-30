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

<a href="index.php" style="display: block; text-align: center; margin-top: 30px;">
    <img src="images/logo.png" alt="Logo" width="80" height="100">
    <h1>RSUD PRINGSEWU</h1>
</a>

<div class="login-container">
    <h2>Login</h2>
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>
</div>

</body>
</html>
