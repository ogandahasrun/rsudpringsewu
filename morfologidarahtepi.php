
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Morfologi Darah Tepi</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; letter-spacing: 1px; }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .filter-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .filter-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .filter-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus { outline: none; border-color: #28a745; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; cursor: pointer; user-select: none; position: relative; transition: background 0.3s ease; }
        th:hover { background: linear-gradient(45deg, #495057, #5a6268); }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .patient-info { margin: 20px 0; padding: 15px; background-color: #e9f7ef; border-radius: 8px; border-left: 5px solid #28a745; font-family: Tahoma, Geneva, Verdana, sans-serif; }
        .patient-info p { margin: 5px 0; font-size: 16px; }
        .col-uraian { width: 30%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .col-hasil { width: 70%; }
        .input-hasil { width: 100%; padding: 10px; font-size: 15px; border-radius: 7px; border: 1.5px solid #e9ecef; transition: border 0.3s; }
        .input-hasil:focus { border: 1.5px solid #28a745; outline: none; }
        .save-btn { margin-top: 18px; }
        @media (max-width: 900px) { .container { max-width: 100%; } .content { padding: 10px; } .header { padding: 18px 8px; } }
        @media (max-width: 600px) { th, td { padding: 8px 4px; font-size: 12px; } .header h1 { font-size: 1.2em; } }
    </style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.input-hasil').forEach(input => {
        // Format nilai yang sudah ada saat halaman dimuat
        if (input.value) {
            input.value = formatText(input.value);
        }

        input.addEventListener('input', function (e) {
            // Simpan posisi kursor dan nilai sebelum perubahan
            const cursorPos = this.selectionStart;
            const originalValue = this.value;
            
            // Format teks
            this.value = formatText(originalValue);
            
            // Kembalikan posisi kursor
            if (cursorPos === 1 && originalValue.length === 0) {
                this.setSelectionRange(2, 2); // Penanganan khusus untuk karakter pertama
            } else {
                this.setSelectionRange(cursorPos, cursorPos);
            }
        });

        // Fungsi untuk memformat teks
        function formatText(text) {
            if (text.length === 0) return text;
            
            // Ambil karakter pertama (kapital) dan sisanya (biarkan asli)
            return text.charAt(0).toUpperCase() + text.slice(1);
        }
    });
});
</script>

</head>
<body>
<div class="container">
    <div class="header">
        <h1>🧬 MORFOLOGI DARAH TEPI</h1>
    </div>
    <div class="content">
        <div class="back-button">
            <a href="laboratorium.php">← Kembali ke Menu Laboratorium</a>
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

    <form method="POST" class="filter-form">
        <div class="filter-title">🔍 Filter Data Morfologi Darah Tepi</div>
        <div class="filter-grid">
            <div class="filter-group">
                <label for="no_rawat">No. Rawat</label>
                <input type="text" id="no_rawat" name="no_rawat" required value="<?php echo htmlspecialchars($no_rawat); ?>">
            </div>
        </div>
        <div class="filter-actions">
            <button type="submit" name="filter" class="btn btn-primary">🔍 Tampilkan Data</button>
        </div>
    </form>

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
            <div class="table-container">
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
            </div>
            <div class="save-btn">
                <button type="submit" name="simpan_semua" class="btn btn-primary">💾 Simpan Semua Perubahan</button>
            </div>
        </form>
    <?php } elseif (isset($_POST['filter'])) { ?>
        <div class="no-data"><em>Data tidak ditemukan untuk nomor rawat yang dipilih.</em></div>
    <?php } ?>
    </div>
</div>
</body>
</html>
