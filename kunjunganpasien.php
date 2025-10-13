<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunjungan Pasien - RSUD Pringsewu</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body, table, th, td, input, select, button {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            margin: 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.8em;
            font-weight: bold;
        }
        .content {
            padding: 25px;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .back-button a {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .back-button a:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .filter-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        .filter-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-group label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
        }
        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th {
            background: linear-gradient(45deg, #343a40, #495057);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
            white-space: nowrap;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        tr:nth-child(even) td {
            background: #f8f9fa;
        }
        tr:hover td {
            background: #e3f2fd;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
        }
        
        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .header {
                padding: 20px 15px;
            }
            .header h1 {
                font-size: 1.5em;
            }
            .content {
                padding: 15px;
            }
            .filter-form {
                padding: 20px 15px;
            }
            .filter-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .filter-actions {
                justify-content: stretch;
            }
            .btn {
                padding: 10px 15px;
                font-size: 13px;
            }
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.3em;
            }
            .filter-title {
                font-size: 16px;
            }
        }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector("table");
            if (table) {
                let range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try {
                    document.execCommand("copy");
                    alert("✅ Tabel berhasil disalin ke clipboard!");
                } catch(err) {
                    alert("❌ Gagal menyalin tabel");
                }
                window.getSelection().removeAllRanges();
            }
        }

        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-01'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Kunjungan Pasien Rawat Jalan</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali ke Menu Surveilans</a>
            </div>

            <?php
            // Inisialisasi nilai default agar tidak error saat pertama kali dibuka
            $tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-01');
            $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
            ?>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📊 Filter Data Kunjungan
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Registrasi Awal</label>
                        <input type="date" 
                               id="tanggal_awal" 
                               name="tanggal_awal" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Registrasi Akhir</label>
                        <input type="date" 
                               id="tanggal_akhir" 
                               name="tanggal_akhir" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        🔍 Tampilkan Data
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

            <?php
            include 'koneksi.php';

            if (isset($_POST['filter'])) {
                $query = "SELECT
                            reg_periksa.tgl_registrasi,
                            reg_periksa.no_rawat,
                            pasien.no_rkm_medis,
                            pasien.nm_pasien,
                            poliklinik.nm_poli,
                            dokter.nm_dokter,
                            pasien.no_tlp,
                            penjab.png_jawab,
                            pasien.no_ktp,
                            pasien.jk,
                            pasien.tmp_lahir,
                            pasien.tgl_lahir,
                            pasien.alamat,
                            pasien.agama
                          FROM
                            reg_periksa
                          INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                          INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                          INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                          INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                          WHERE
                            reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                          ORDER BY
                            reg_periksa.tgl_registrasi ASC";

                $result = mysqli_query($koneksi, $query);
                if ($result) {
                    $total_rows = mysqli_num_rows($result);
                    
                    echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
                    echo '<div style="font-weight: bold; color: #495057;">📊 Total Data: <span style="color: #007bff;">' . $total_rows . '</span> kunjungan</div>';
                    echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
                    echo '</div>';
                    
                    echo '<div class="table-container">';
                    echo "<table>
                    <tr>
                        <th>NOMOR URUT</th>
                        <th>TANGGAL REGISTRASI</th>
                        <th>NOMOR RAWAT</th>
                        <th>NOMOR REKAM MEDIK</th>
                        <th>NAMA PASIEN</th>
                        <th>NOMOR KTP</th>
                        <th>JENIS KELAMIN</th>
                        <th>TEMPAT LAHIR</th>
                        <th>TANGGAL LAHIR</th>
                        <th>ALAMAT</th>
                        <th>AGAMA</th>
                        <th>NAMA POLI</th>
                        <th>NAMA DOKTER</th>
                        <th>NOMOR TELP</th>
                        <th>PENANGGUNG JAWAB</th>
                    </tr>";

            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>$no</td>
                        <td>{$row['tgl_registrasi']}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['no_ktp']}</td>
                        <td>{$row['jk']}</td>
                        <td>{$row['tmp_lahir']}</td>
                        <td>{$row['tgl_lahir']}</td>
                        <td>{$row['alamat']}</td>
                        <td>{$row['agama']}</td>
                        <td>{$row['nm_poli']}</td>
                        <td>{$row['nm_dokter']}</td>
                        <td>{$row['no_tlp']}</td>
                        <td>{$row['png_jawab']}</td>
                    </tr>";
                    $no++;
                }
                echo "</table>";
                echo '</div>'; // Tutup table-container
                
                if ($total_rows == 0) {
                    echo '<div class="no-data">📋 Tidak ada data kunjungan pada rentang tanggal yang dipilih</div>';
                }
            } else {
                echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb;">';
                echo "❌ Terjadi kesalahan dalam query: " . mysqli_error($koneksi);
                echo '</div>';
            }
            mysqli_close($koneksi);
        }
        ?>
        
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->
</body>
</html>
