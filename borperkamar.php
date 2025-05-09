<?php include "koneksi.php"; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kamar Inap</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        form {
            max-width: 500px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        form input[type="date"], form input[type="submit"] {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
        }

        form input[type="submit"] {
            background-color: #2e86de;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }

        form input[type="submit"]:hover {
            background-color: #1b4f72;
        }

        .report {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .periode {
            font-size: 16px;
            font-weight: bold;
            color: #555;
            text-align: center;
            margin-bottom: 10px;
        }

        button.copy-btn {
            display: block;
            margin: 10px auto;
            padding: 10px 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        button.copy-btn:hover {
            background-color: #1e7e34;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        table th {
            background-color: #2e86de;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
    </head>
<body>

<h2>Laporan Kamar Inap</h2>

<form method="GET">
    <label>Tanggal Awal:</label>
    <input type="date" name="tgl_awal" required>

    <label>Tanggal Akhir:</label>
    <input type="date" name="tgl_akhir" required>

    <label>Kelompok Kamar:</label>
    <select name="kelompokkamar">
        <option value="">-- Semua Kelompok --</option>
        <?php
        $result_kamar = $koneksi->query("SELECT DISTINCT kelompok_kamar FROM kelompokkamar ORDER BY kelompok_kamar");
        while ($row = $result_kamar->fetch_assoc()) {
            $selected = (isset($_GET['kelompokkamar']) && $_GET['kelompokkamar'] == $row['kelompok_kamar']) ? 'selected' : '';
            echo "<option value='{$row['kelompok_kamar']}' $selected>{$row['kelompok_kamar']}</option>";
        }
        ?>
    </select>

    <input type="submit" value="Tampilkan">
</form>

<?php
if (isset($_GET['tgl_awal']) && isset($_GET['tgl_akhir'])) {
    $tgl_awal = $_GET['tgl_awal'];
    $tgl_akhir = $_GET['tgl_akhir'];
    $kelompokkamar_filter = isset($_GET['kelompokkamar']) ? $_GET['kelompokkamar'] : '';

    $tanggal = [];
    $period = new DatePeriod(
        new DateTime($tgl_awal),
        new DateInterval('P1D'),
        (new DateTime($tgl_akhir))->modify('+1 day')
    );
    foreach ($period as $date) {
        $tanggal[] = $date->format("Y-m-d");
    }

    // Query Masuk
    $masuk = [];
    $sql_masuk = "SELECT kamar_inap.tgl_masuk, COUNT(kamar_inap.no_rawat) as jumlah 
                  FROM kamar_inap 
                  INNER JOIN kelompokkamar ON kamar_inap.kd_kamar = kelompokkamar.kd_kamar
                  WHERE kamar_inap.tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'";
    if (!empty($kelompokkamar_filter)) {
        $sql_masuk .= " AND kelompokkamar.kelompok_kamar = '$kelompokkamar_filter'";
    }
    $sql_masuk .= " GROUP BY kamar_inap.tgl_masuk";

    $result_masuk = $koneksi->query($sql_masuk);
    while ($row = $result_masuk->fetch_assoc()) {
        $masuk[$row['tgl_masuk']] = $row['jumlah'];
    }

    // Query Keluar
    $keluar = [];
    $sql_keluar = "SELECT kamar_inap.tgl_keluar, COUNT(kamar_inap.no_rawat) as jumlah 
                   FROM kamar_inap 
                   INNER JOIN kelompokkamar ON kamar_inap.kd_kamar = kelompokkamar.kd_kamar
                   WHERE kamar_inap.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'";
    if (!empty($kelompokkamar_filter)) {
        $sql_keluar .= " AND kelompokkamar.kelompok_kamar = '$kelompokkamar_filter'";
    }
    $sql_keluar .= " GROUP BY kamar_inap.tgl_keluar";

    $result_keluar = $koneksi->query($sql_keluar);
    while ($row = $result_keluar->fetch_assoc()) {
        $keluar[$row['tgl_keluar']] = $row['jumlah'];
    }

    echo "<div class='report'>";
    echo "<div class='periode'>Periode: " . date("d M Y", strtotime($tgl_awal)) . " s.d. " . date("d M Y", strtotime($tgl_akhir)) . "</div>";
    if (!empty($kelompokkamar_filter)) {
        echo "<div class='periode'>Kelompok Kamar: <strong>$kelompokkamar_filter</strong></div>";
    }
    echo "<button class='copy-btn' onclick='copyTable()'>📋 Copy ke Clipboard</button>";
    echo "<table id='laporanTable'>";
    echo "<tr><th>Tanggal</th><th>Jumlah Masuk</th><th>Jumlah Keluar</th></tr>";

    foreach ($tanggal as $tgl) {
        $jml_masuk = isset($masuk[$tgl]) ? $masuk[$tgl] : 0;
        $jml_keluar = isset($keluar[$tgl]) ? $keluar[$tgl] : 0;
        echo "<tr>
                <td>" . date("d M Y", strtotime($tgl)) . "</td>
                <td>$jml_masuk</td>
                <td>$jml_keluar</td>
              </tr>";
    }

    echo "</table>";
    echo "</div>";
}
?>

<script>
function copyTable() {
    const table = document.getElementById("laporanTable");
    let text = "";

    for (let row of table.rows) {
        let rowText = [];
        for (let cell of row.cells) {
            rowText.push(cell.innerText);
        }
        text += rowText.join("\t") + "\n";
    }

    navigator.clipboard.writeText(text).then(function() {
        alert("✅ Data berhasil disalin! Tempelkan di Excel atau Google Sheets.");
    }, function(err) {
        alert("❌ Gagal menyalin: " + err);
    });
}
</script>

</body>
</html>
