<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Status Pulang Pasien</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
    }
    .container {
        max-width: 800px;
        margin: 20px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    h1 {
        text-align: center;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    tr:hover {
        background-color: #f2f2f2;
    }
    form {
        margin-bottom: 20px;
        text-align: center;
    }
    input[type="date"] {
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ccc;
    }
    input[type="submit"] {
        padding: 8px 20px;
        background-color: #4caf50;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    input[type="submit"]:hover {
        background-color: #45a049;
    }
</style>
</head>
<body>
    <div class="container">
        <h1>DATA STATUS PULANG PASIEN</h1>
        <?php
        // Sertakan file koneksi.php
        include 'koneksi.php';

        // Set default values for start date and end date
        $start_date = '';
        $end_date = '';
        $data_displayed = false;

        // Check if form is submitted and "Cari" button is clicked
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $data_displayed = true;

            // Query untuk mengambil data dengan filter periode
            $sql = "SELECT
                        reg_periksa.no_rawat,
                        pasien.no_rkm_medis,
                        pasien.nm_pasien,
                        pasien.jk,
                        kamar_inap.tgl_keluar,
                        kamar_inap.stts_pulang
                    FROM
                        reg_periksa
                    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                    INNER JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat
                    WHERE
                        kamar_inap.tgl_keluar BETWEEN '$start_date' AND '$end_date'
                    ORDER BY
                        kamar_inap.tgl_keluar ASC";

            $result = $koneksi->query($sql);

            if ($result->num_rows > 0) {
                // Tampilkan data dalam tabel
                echo "<table>
                        <tr>
                            <th>No. Rawat</th>
                            <th>No. Rekam Medis</th>
                            <th>Nama Pasien</th>
                            <th>Jenis Kelamin</th>
                            <th>Tanggal Keluar</th>
                            <th>Status Pulang</th>
                        </tr>";
                while($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>".$row["no_rawat"]."</td>
                            <td>".$row["no_rkm_medis"]."</td>
                            <td>".$row["nm_pasien"]."</td>
                            <td>".$row["jk"]."</td>
                            <td>".$row["tgl_keluar"]."</td>
                            <td>".$row["stts_pulang"]."</td>
                        </tr>";
                }
                echo "</table>";
            } else {
                echo "0 results";
            }
        }

        // Tampilkan form filter
        if (!$data_displayed) {
            echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" value="' . $start_date . '">
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" value="' . $end_date . '">
                    <input type="submit" name="submit" value="Cari">
                </form>';
        }

        $koneksi->close();
        ?>
    </div>
</body>
</html>
