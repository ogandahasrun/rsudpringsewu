<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Hapus Billing - RSUD Pringsewu</title>
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
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 900px;
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
        .total-row td {
            background: #e9ecef !important;
            font-weight: bold;
            color: #495057;
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
            table {
                min-width: 720px;
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
            let table = document.querySelector(".table-responsive");
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
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Log Hapus Billing</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="keuangan.php">← Kembali ke Menu Keuangan</a>
            </div>

    <?php
    include 'koneksi.php';

    // Default value
    $tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
    $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
    ?>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Log Hapus Billing
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Awal</label>
                        <input type="date" 
                               id="tanggal_awal" 
                               name="tanggal_awal" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Akhir</label>
                        <input type="date" 
                               id="tanggal_akhir" 
                               name="tanggal_akhir" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        📊 Tampilkan Laporan
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

    <?php
    if (isset($_POST['filter'])) {
        $tanggal_awal = mysqli_real_escape_string($koneksi, $_POST['tanggal_awal']);
        $tanggal_akhir = mysqli_real_escape_string($koneksi, $_POST['tanggal_akhir']);
        
        $query = "SELECT 
                    ob.tanggal,
                    peg.nama,
                    rp.no_rawat,
                    pas.no_rkm_medis,
                    pas.nm_pasien,
                    MAX(cb.tanggal) AS tanggal_close_bill
                FROM 
                    trackersql ob
                INNER JOIN reg_periksa rp 
                    ON rp.no_rawat = SUBSTRING_INDEX(SUBSTRING_INDEX(ob.sqle, \"no_rawat='\", -1), \"'\", 1)
                INNER JOIN pasien pas ON rp.no_rkm_medis = pas.no_rkm_medis
                LEFT JOIN pegawai peg ON ob.usere = peg.nik
                LEFT JOIN trackersql cb 
                    ON cb.sqle LIKE '%update reg_periksa set status_bayar=''Sudah Bayar''%'
                    AND cb.sqle LIKE CONCAT('%no_rawat=''', rp.no_rawat, '''%')
                    AND cb.tanggal <= ob.tanggal
                WHERE
                    ob.sqle LIKE '%delete from billing%'
                    AND ob.sqle LIKE '%no_rawat=''%'
                    AND ob.tanggal BETWEEN '$tanggal_awal 00:00:00' AND '$tanggal_akhir 23:59:59'
                GROUP BY ob.tanggal, peg.nama, rp.no_rawat, pas.no_rkm_medis, pas.nm_pasien
                ORDER BY ob.tanggal DESC";
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            $total_rows = mysqli_num_rows($result);
            
            echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
            echo '<div style="font-weight: bold; color: #495057;">📊 Total Data: <span style="color: #007bff;">' . $total_rows . '</span> log hapus billing</div>';
            echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
            echo '</div>';
            
            echo "<div class='table-responsive'><table>
                <tr>
                    <th>No</th>
                    <th>TANGGAL CLOSE BILL</th>
                    <th>TANGGAL OPEN BILL</th>
                    <th>SELISIH WAKTU</th>
                    <th>NO RAWAT</th>
                    <th>NO RKM MEDIS</th>
                    <th>NAMA PASIEN</th>
                    <th>NAMA PEGAWAI</th>
                </tr>";
            $no = 1; 
            while ($row = mysqli_fetch_assoc($result)) {
                $tanggal       = htmlspecialchars($row['tanggal']);
                $no_rawat      = htmlspecialchars($row['no_rawat']);
                $no_rkm_medis  = htmlspecialchars($row['no_rkm_medis']);
                $nm_pasien     = htmlspecialchars($row['nm_pasien']);
                $nama          = htmlspecialchars($row['nama']);

                // Tanggal close bill & selisih waktu
                if ($row['tanggal_close_bill']) {
                    $tanggal_close_bill = htmlspecialchars($row['tanggal_close_bill']);

                    $dt_close = new DateTime($row['tanggal_close_bill']);
                    $dt_open  = new DateTime($row['tanggal']);
                    $diff     = $dt_close->diff($dt_open);

                    // Format selisih: hari / jam / menit
                    $selisih_parts = [];
                    if ($diff->days > 0)  $selisih_parts[] = $diff->days . ' hari';
                    if ($diff->h > 0)     $selisih_parts[] = $diff->h . ' jam';
                    if ($diff->i > 0)     $selisih_parts[] = $diff->i . ' menit';
                    if (empty($selisih_parts)) $selisih_parts[] = '< 1 menit';
                    $selisih_text = implode(' ', $selisih_parts);

                    // Warna indikator berdasarkan total menit
                    $total_menit = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;
                    if ($total_menit <= 60) {
                        $selisih_color = '#28a745'; // hijau  — <= 1 jam
                        $selisih_bg    = '#d4edda';
                    } elseif ($total_menit <= 360) {
                        $selisih_color = '#856404'; // kuning — <= 6 jam
                        $selisih_bg    = '#fff3cd';
                    } else {
                        $selisih_color = '#721c24'; // merah  — > 6 jam
                        $selisih_bg    = '#f8d7da';
                    }
                    $selisih_html = "<span style='display:inline-block;padding:3px 8px;border-radius:12px;"
                        . "background:{$selisih_bg};color:{$selisih_color};font-weight:bold;font-size:12px;'>"
                        . "⏱ {$selisih_text}</span>";
                } else {
                    $tanggal_close_bill = '<span style="color:#aaa;font-style:italic;">-</span>';
                    $selisih_html       = '<span style="color:#aaa;font-style:italic;">-</span>';
                }

                echo "<tr>
                        <td>{$no}</td>
                        <td>{$tanggal_close_bill}</td>
                        <td>{$tanggal}</td>
                        <td style='text-align:center;'>{$selisih_html}</td>
                        <td>{$no_rawat}</td>
                        <td>{$no_rkm_medis}</td>
                        <td>{$nm_pasien}</td>
                        <td>{$nama}</td>
                    </tr>";
                $no++;
            }
            echo "</table></div>";
            
            if ($total_rows == 0) {
                echo '<div class="no-data">📋 Tidak ada log hapus billing pada rentang tanggal yang dipilih</div>';
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