<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Laporan Narkotik</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            text-align: center;
        }
        label {
            font-weight: bold;
        }
        select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 10px;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Menu Laporan Narkotik</h1>
        <form method="get">
            <label for="menu">Pilih Data:</label>
            <select name="menu" id="menu">
                <option value="">Pilih Data</option>
                <option value="penerimaannarkotik.php">Penerimaan Narkotik</option>
                <option value="mutasinarkotik.php">Mutasi Narkotik</option>
                <option value="resepnarkotik.php">Pemberian Narkotik</option>
                <option value="riwayatnarkotik.php">Riwayat Narkotik</option>
            </select>
            <br>
            <button type="submit">Pilih</button>
        </form>
    </div>

    <?php
    if(isset($_GET['menu'])) {
        $menu = $_GET['menu'];
        if(!empty($menu)) {
            header("Location: $menu");
            exit();
        }
    }
    ?>
</body>
</html>
