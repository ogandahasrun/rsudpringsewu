<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MORFOLOGI DARAH TEPI</title>
    <style>
        h1 {
            font-family: Arial, sans-serif;
            color: green;
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            border-right: 1px solid #ddd;
        }
        th:last-child, td:last-child {
            border-right: none;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        th {
            background-color: #4CAF50;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .back-button {
            margin-bottom: 15px;
        }
        .filter-form {
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .filter-form input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-form button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-form button:hover {
            background-color: #45a049;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }
        .patient-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #e9f7ef;
            border-radius: 5px;
            border-left: 5px solid #4CAF50;
            font-family: Arial, sans-serif;
        }
        .patient-info p {
            margin: 5px 0;
            font-size: 16px;
        }

        .col-uraian {
            width: 20%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .col-hasil {
            width: 80%;
        }

        .input-hasil {
            width: 100%;
            padding: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.input-hasil').forEach(input => {
            input.addEventListener('input', function () {
                let value = this.value.toLowerCase();
                this.value = value.charAt(0).toUpperCase() + value.slice(1);
            });

            // Format nilai yang sudah terisi saat halaman dimuat
            let value = input.value.toLowerCase();
            input.value = value.charAt(0).toUpperCase() + value.slice(1);
        });
    });
    </script>
</head>
<body>
    <header>
        <h1>MORFOLOGI DARAH TEPI</h1>
    </header>

    <div class="back-button">
        <a href="laboratorium.php">Kembali ke Menu Laboratorium</a>
    </div>

    <?php
    include 'koneksi.php';

    $no_rawat = "";
    $no_rkm_medis = "";
    $nm_pasien = "";
    $data_lab = [];

    if (isset($_POST['simpan_semua'])) {
        $no_rawat = mysqli_real_escape_string($koneksi, $_POST['no_rawat']);
        $nilai_arr = $_POST['nilai'];
        $id_template_arr = $_POST['id_template'];

        for ($i = 0; $i < count($nilai_arr); $i++) {
            $id_template = mysqli_real_escape_string($koneksi, $id_template_arr[$i]);
            $nilai = mysqli_real_escape_string($koneksi, $nilai_arr[$i]);

            $update_query = "UPDATE detail_periksa_lab
                             SET nilai = '$nilai'
                             WHERE no_rawat = '$no_rawat'
                               AND id_template = '$id_template'
                               AND kd_jenis_prw = 'J000014'";

            mysqli_query($koneksi, $update_query);
        }

        echo "<script>alert('Semua perubahan berhasil disimpan!');</script>";
        $_POST['filter'] = true;
    }

    if (isset($_POST['filter'])) {
        $no_rawat = mysqli_real_escape_string($koneksi, $_POST['no_rawat']);

        $query = "SELECT
                    template_laboratorium.Pemeriksaan,
                    detail_periksa_lab.nilai,
                    template_laboratorium.id_template,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien
                  FROM
                    reg_periksa
                    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                    INNER JOIN detail_periksa_lab ON detail_periksa_lab.no_rawat = reg_periksa.no_rawat
                    INNER JOIN template_laboratorium ON detail_periksa_lab.id_template = template_laboratorium.id_template
                  WHERE
                    detail_periksa_lab.kd_jenis_prw = 'J000014' AND
                    reg_periksa.no_rawat = '$no_rawat'
                  ORDER BY
                    template_laboratorium.urut";

        $result = mysqli_query($koneksi, $query);

        if (!$result) {
            die("Query error: " . mysqli_error($koneksi));
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $no_rkm_medis = $row['no_rkm_medis'];
            $nm_pasien = $row['nm_pasien'];
            $data_lab[] = $row;
        }
    }
    ?>

    <div class="filter-form">
        <form method="POST">
            No. Rawat :
            <input type="text" name="no_rawat" required value="<?php echo htmlspecialchars($no_rawat); ?>">
            <button type="submit" name="filter">Filter</button>
        </form>
    </div>

    <?php
    if (!empty($no_rkm_medis) && !empty($nm_pasien)) {
        echo '<div class="patient-info">';
        echo '<p><strong>Nomor Rekam Medis :</strong> ' . htmlspecialchars($no_rkm_medis) . '</p>';
        echo '<p><strong>Nama Pasien :</strong> ' . htmlspecialchars($nm_pasien) . '</p>';
        echo '</div>';
    }
    ?>

    <?php if (!empty($data_lab)) { ?>
        <form method="POST">
            <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($no_rawat) ?>">
            <table>
                <thead>
                    <tr>
                        <th class="col-uraian">URAIAN</th>
                        <th class="col-hasil">HASIL</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data_lab as $index => $row): ?>
                    <tr>
                        <td class="col-uraian">
                            <?= htmlspecialchars($row['Pemeriksaan']) ?>
                            <input type="hidden" name="id_template[]" value="<?= $row['id_template'] ?>">
                            <input type="hidden" name="pemeriksaan[]" value="<?= htmlspecialchars($row['Pemeriksaan']) ?>">
                        </td>
                        <td class="col-hasil">
                            <input type="text" name="nilai[]" class="input-hasil" value="<?= htmlspecialchars($row['nilai']) ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="simpan_semua">Simpan Semua Perubahan</button>
        </form>
    <?php } elseif (isset($_POST['filter'])) { ?>
        <p class='no-data'><em>Data tidak ditemukan untuk nomor rawat yang dipilih.</em></p>
    <?php } ?>
</body>
</html>
