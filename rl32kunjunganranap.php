<?php
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kamar Inap - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .filter-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .filter-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus { outline: none; border-color: #667eea; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #667eea, #764ba2); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .info-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .info-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
        .info-card h3 { margin: 0 0 5px 0; font-size: 2em; }
        .info-card p { margin: 0; font-size: 13px; opacity: 0.9; }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; font-size: 12px; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 12px 8px; text-align: center; font-weight: bold; font-size: 11px; white-space: nowrap; position: sticky; top: 0; z-index: 10; }
        td { padding: 10px 8px; border-bottom: 1px solid #e9ecef; text-align: center; }
        td:nth-child(2) { text-align: left; font-weight: 600; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f4f8; }
        .total-row { background: #e9ecef !important; font-weight: bold; }
        .total-row td { background: #e9ecef !important; border-top: 2px solid #495057; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        
        @media print {
            body { background: white; padding: 0; }
            .header { background: #667eea !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .back-button, .filter-form, .btn { display: none; }
            .container { box-shadow: none; }
            table { font-size: 10px; }
            th, td { padding: 6px 4px; }
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px 15px; }
            .header h1 { font-size: 1.5em; }
            .content { padding: 15px; }
            .filter-form { padding: 20px 15px; }
            .filter-grid { grid-template-columns: 1fr; }
            th, td { padding: 8px 4px; font-size: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 RL 3.2 Kunjungan Kamar Inap</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali</a>
            </div>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Periode Laporan
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tgl_awal">📆 Tanggal Awal</label>
                        <input type="date" id="tgl_awal" name="tgl_awal" 
                               value="<?php echo isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01'); ?>" 
                               required>
                    </div>
                    
                    <div class="filter-group">
                        <label for="tgl_akhir">📆 Tanggal Akhir</label>
                        <input type="date" id="tgl_akhir" name="tgl_akhir" 
                               value="<?php echo isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-t'); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        🔍 Tampilkan Laporan
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-success">
                        🖨️ Cetak
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset
                    </button>
                </div>
            </form>

            <?php
            if (isset($_POST['filter'])) {
                $tgl_awal = $_POST['tgl_awal'];
                $tgl_akhir = $_POST['tgl_akhir'];
                
                // Query utama untuk mendapatkan daftar kamar
                $query_kamar = "SELECT
                    kamar.kd_kamar,
                    bangsal.nm_bangsal,
                    kamar.kelas,
                    bangsal.kd_bangsal
                FROM
                    kamar
                INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
                WHERE
                    kamar.statusdata = '1'
                ORDER BY
                    bangsal.nm_bangsal ASC,
                    kamar.kelas ASC,
                    kamar.kd_kamar ASC";
                
                $result_kamar = mysqli_query($koneksi, $query_kamar);
                
                if ($result_kamar && mysqli_num_rows($result_kamar) > 0):
                    // Array untuk menyimpan data per bangsal
                    $data_per_bangsal = array();
                    
                    while ($row_kamar = mysqli_fetch_assoc($result_kamar)) {
                        $kd_kamar = $row_kamar['kd_kamar'];
                        $nm_bangsal = $row_kamar['nm_bangsal'];
                        
                        // 1. PASIEN AWAL BULAN
                        // Pasien yang masuk sebelum tgl_awal dan belum keluar sampai tgl_awal (atau keluar setelah tgl_awal)
                        $query_awal = "SELECT COUNT(DISTINCT ki.no_rawat) as jumlah
                            FROM kamar_inap ki
                            WHERE ki.kd_kamar = '$kd_kamar'
                            AND ki.tgl_masuk < '$tgl_awal'
                            AND (ki.tgl_keluar >= '$tgl_awal' OR ki.tgl_keluar IS NULL OR ki.tgl_keluar = '0000-00-00')";
                        $result_awal = mysqli_query($koneksi, $query_awal);
                        $pasien_awal = mysqli_fetch_assoc($result_awal)['jumlah'];
                        
                        // 2. PASIEN MASUK
                        $query_masuk = "SELECT COUNT(*) as jumlah
                            FROM kamar_inap
                            WHERE kd_kamar = '$kd_kamar'
                            AND tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'";
                        $result_masuk = mysqli_query($koneksi, $query_masuk);
                        $pasien_masuk = mysqli_fetch_assoc($result_masuk)['jumlah'];
                        
                        // 3. PASIEN PINDAHAN (Masuk dengan status Pindah Kamar)
                        $query_pindahan = "SELECT COUNT(*) as jumlah
                            FROM kamar_inap
                            WHERE kd_kamar = '$kd_kamar'
                            AND tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            AND stts_pulang = 'Pindah Kamar'";
                        $result_pindahan = mysqli_query($koneksi, $query_pindahan);
                        $pasien_pindahan = mysqli_fetch_assoc($result_pindahan)['jumlah'];
                        
                        // 4. PASIEN DIPINDAHKAN (Keluar dengan status Pindah Kamar)
                        $query_dipindahkan = "SELECT COUNT(*) as jumlah
                            FROM kamar_inap
                            WHERE kd_kamar = '$kd_kamar'
                            AND tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            AND stts_pulang = 'Pindah Kamar'";
                        $result_dipindahkan = mysqli_query($koneksi, $query_dipindahkan);
                        $pasien_dipindahkan = mysqli_fetch_assoc($result_dipindahkan)['jumlah'];
                        
                        // 5. PASIEN KELUAR HIDUP
                        $query_hidup = "SELECT COUNT(*) as jumlah
                            FROM kamar_inap
                            WHERE kd_kamar = '$kd_kamar'
                            AND tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            AND stts_pulang NOT IN ('Meninggal', 'Pindah Kamar')";
                        $result_hidup = mysqli_query($koneksi, $query_hidup);
                        $pasien_hidup = mysqli_fetch_assoc($result_hidup)['jumlah'];
                        
                        // 6. PASIEN PRIA MATI < 48 JAM
                        $query_pria_mati_48 = "SELECT COUNT(DISTINCT ki.no_rawat) as jumlah
                            FROM kamar_inap ki
                            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
                            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                            WHERE ki.kd_kamar = '$kd_kamar'
                            AND ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            AND ki.stts_pulang = 'Meninggal'
                            AND p.jk = 'L'
                            AND (SELECT SUM(lama) FROM kamar_inap WHERE no_rawat = ki.no_rawat) < 2";
                        $result_pria_mati_48 = mysqli_query($koneksi, $query_pria_mati_48);
                        $pria_mati_48 = mysqli_fetch_assoc($result_pria_mati_48)['jumlah'];
                        
                        // 7. PASIEN PRIA MATI >= 48 JAM
                        $query_pria_mati_48plus = "SELECT COUNT(DISTINCT ki.no_rawat) as jumlah
                            FROM kamar_inap ki
                            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
                            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                            WHERE ki.kd_kamar = '$kd_kamar'
                            AND ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            AND ki.stts_pulang = 'Meninggal'
                            AND p.jk = 'L'
                            AND (SELECT SUM(lama) FROM kamar_inap WHERE no_rawat = ki.no_rawat) > 1";
                        $result_pria_mati_48plus = mysqli_query($koneksi, $query_pria_mati_48plus);
                        $pria_mati_48plus = mysqli_fetch_assoc($result_pria_mati_48plus)['jumlah'];
                        
                        // 8. PASIEN WANITA MATI < 48 JAM
                        $query_wanita_mati_48 = "SELECT COUNT(DISTINCT ki.no_rawat) as jumlah
                            FROM kamar_inap ki
                            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
                            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                            WHERE ki.kd_kamar = '$kd_kamar'
                            AND ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            AND ki.stts_pulang = 'Meninggal'
                            AND p.jk = 'P'
                            AND (SELECT SUM(lama) FROM kamar_inap WHERE no_rawat = ki.no_rawat) < 2";
                        $result_wanita_mati_48 = mysqli_query($koneksi, $query_wanita_mati_48);
                        $wanita_mati_48 = mysqli_fetch_assoc($result_wanita_mati_48)['jumlah'];
                        
                        // 9. PASIEN WANITA MATI >= 48 JAM
                        $query_wanita_mati_48plus = "SELECT COUNT(DISTINCT ki.no_rawat) as jumlah
                            FROM kamar_inap ki
                            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
                            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                            WHERE ki.kd_kamar = '$kd_kamar'
                            AND ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            AND ki.stts_pulang = 'Meninggal'
                            AND p.jk = 'P'
                            AND (SELECT SUM(lama) FROM kamar_inap WHERE no_rawat = ki.no_rawat) > 1";
                        $result_wanita_mati_48plus = mysqli_query($koneksi, $query_wanita_mati_48plus);
                        $wanita_mati_48plus = mysqli_fetch_assoc($result_wanita_mati_48plus)['jumlah'];
                        
                        // 10. JUMLAH LAMA DIRAWAT
                        $query_lama = "SELECT SUM(lama) as total_lama
                            FROM kamar_inap
                            WHERE kd_kamar = '$kd_kamar'
                            AND ((tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir')
                                OR (tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir')
                                OR (tgl_masuk < '$tgl_awal' AND tgl_keluar > '$tgl_akhir'))";
                        $result_lama = mysqli_query($koneksi, $query_lama);
                        $total_lama = mysqli_fetch_assoc($result_lama)['total_lama'] ?? 0;
                        
                        // 11. PASIEN AKHIR BULAN
                        // Pasien yang masuk sebelum atau pada tgl_akhir dan belum keluar sampai tgl_akhir (atau keluar setelah tgl_akhir)
                        $query_akhir = "SELECT COUNT(DISTINCT ki.no_rawat) as jumlah
                            FROM kamar_inap ki
                            WHERE ki.kd_kamar = '$kd_kamar'
                            AND ki.tgl_masuk <= '$tgl_akhir'
                            AND (ki.tgl_keluar > '$tgl_akhir' OR ki.tgl_keluar IS NULL OR ki.tgl_keluar = '0000-00-00')";
                        $result_akhir = mysqli_query($koneksi, $query_akhir);
                        $pasien_akhir = mysqli_fetch_assoc($result_akhir)['jumlah'];
                        
                        // Simpan data per bangsal untuk pengelompokan
                        if (!isset($data_per_bangsal[$nm_bangsal])) {
                            $data_per_bangsal[$nm_bangsal] = array(
                                'rows' => array(),
                                'total_awal' => 0,
                                'total_masuk' => 0,
                                'total_pindahan' => 0,
                                'total_dipindahkan' => 0,
                                'total_hidup' => 0,
                                'total_pria_mati_48' => 0,
                                'total_pria_mati_48plus' => 0,
                                'total_wanita_mati_48' => 0,
                                'total_wanita_mati_48plus' => 0,
                                'total_lama' => 0,
                                'total_akhir' => 0
                            );
                        }
                        
                        $data_per_bangsal[$nm_bangsal]['rows'][] = array(
                            'kd_kamar' => $kd_kamar,
                            'kelas' => $row_kamar['kelas'],
                            'pasien_awal' => $pasien_awal,
                            'pasien_masuk' => $pasien_masuk,
                            'pasien_pindahan' => $pasien_pindahan,
                            'pasien_dipindahkan' => $pasien_dipindahkan,
                            'pasien_hidup' => $pasien_hidup,
                            'pria_mati_48' => $pria_mati_48,
                            'pria_mati_48plus' => $pria_mati_48plus,
                            'wanita_mati_48' => $wanita_mati_48,
                            'wanita_mati_48plus' => $wanita_mati_48plus,
                            'total_lama' => $total_lama,
                            'pasien_akhir' => $pasien_akhir
                        );
                        
                        // Akumulasi total per bangsal
                        $data_per_bangsal[$nm_bangsal]['total_awal'] += $pasien_awal;
                        $data_per_bangsal[$nm_bangsal]['total_masuk'] += $pasien_masuk;
                        $data_per_bangsal[$nm_bangsal]['total_pindahan'] += $pasien_pindahan;
                        $data_per_bangsal[$nm_bangsal]['total_dipindahkan'] += $pasien_dipindahkan;
                        $data_per_bangsal[$nm_bangsal]['total_hidup'] += $pasien_hidup;
                        $data_per_bangsal[$nm_bangsal]['total_pria_mati_48'] += $pria_mati_48;
                        $data_per_bangsal[$nm_bangsal]['total_pria_mati_48plus'] += $pria_mati_48plus;
                        $data_per_bangsal[$nm_bangsal]['total_wanita_mati_48'] += $wanita_mati_48;
                        $data_per_bangsal[$nm_bangsal]['total_wanita_mati_48plus'] += $wanita_mati_48plus;
                        $data_per_bangsal[$nm_bangsal]['total_lama'] += $total_lama;
                        $data_per_bangsal[$nm_bangsal]['total_akhir'] += $pasien_akhir;
                    }
            ?>
            
            <div style="margin-bottom: 20px; text-align: center;">
                <h3 style="margin: 0; color: #495057;">
                    📊 RL 3.2 Kunjungan Kamar Inap  
                    <span style="color: #667eea;"><?php echo date('d/m/Y', strtotime($tgl_awal)); ?></span> 
                    s/d 
                    <span style="color: #667eea;"><?php echo date('d/m/Y', strtotime($tgl_akhir)); ?></span>
                </h3>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Jenis Pelayanan</th>
                            <th rowspan="2">Pasien<br>Awal<br>Bulan</th>
                            <th rowspan="2">Pasien<br>Masuk</th>
                            <th rowspan="2">Pasien<br>Pindahan</th>
                            <th rowspan="2">Pasien<br>Dipindahkan</th>
                            <th rowspan="2">Pasien<br>Keluar<br>Hidup</th>
                            <th colspan="2">Pasien Pria<br>Keluar Mati</th>
                            <th colspan="2">Pasien Wanita<br>Keluar Mati</th>
                            <th rowspan="2">Jumlah<br>Lama<br>Dirawat</th>
                            <th rowspan="2">Pasien<br>Akhir<br>Bulan</th>
                        </tr>
                        <tr>
                            <th>&lt;48 Jam</th>
                            <th>≥48 Jam</th>
                            <th>&lt;48 Jam</th>
                            <th>≥48 Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $grand_total = array(
                            'awal' => 0, 'masuk' => 0, 'pindahan' => 0, 'dipindahkan' => 0,
                            'hidup' => 0, 'pria_48' => 0, 'pria_48plus' => 0,
                            'wanita_48' => 0, 'wanita_48plus' => 0, 'lama' => 0, 'akhir' => 0
                        );
                        
                        foreach ($data_per_bangsal as $nm_bangsal => $data):
                        ?>
                            <!-- Header Bangsal -->
                            <tr style="background: #d4d4ff !important; font-weight: bold;">
                                <td colspan="13" style="text-align: left; padding-left: 15px; background: #d4d4ff !important;">
                                    🏥 <?php echo strtoupper($nm_bangsal); ?>
                                </td>
                            </tr>
                            
                            <?php foreach ($data['rows'] as $row): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $nm_bangsal . ' - ' . $row['kelas']; ?></td>
                                <td><?php echo number_format($row['pasien_awal']); ?></td>
                                <td><?php echo number_format($row['pasien_masuk']); ?></td>
                                <td><?php echo number_format($row['pasien_pindahan']); ?></td>
                                <td><?php echo number_format($row['pasien_dipindahkan']); ?></td>
                                <td><?php echo number_format($row['pasien_hidup']); ?></td>
                                <td><?php echo number_format($row['pria_mati_48']); ?></td>
                                <td><?php echo number_format($row['pria_mati_48plus']); ?></td>
                                <td><?php echo number_format($row['wanita_mati_48']); ?></td>
                                <td><?php echo number_format($row['wanita_mati_48plus']); ?></td>
                                <td><?php echo number_format($row['total_lama']); ?></td>
                                <td><?php echo number_format($row['pasien_akhir']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Subtotal per Bangsal -->
                            <tr style="background: #e9ecef !important; font-weight: bold;">
                                <td colspan="2" style="text-align: right; background: #e9ecef !important;">
                                    Subtotal <?php echo $nm_bangsal; ?>:
                                </td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_awal']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_masuk']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_pindahan']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_dipindahkan']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_hidup']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_pria_mati_48']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_pria_mati_48plus']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_wanita_mati_48']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_wanita_mati_48plus']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_lama']); ?></td>
                                <td style="background: #e9ecef !important;"><?php echo number_format($data['total_akhir']); ?></td>
                            </tr>
                            
                            <?php
                            // Akumulasi grand total
                            $grand_total['awal'] += $data['total_awal'];
                            $grand_total['masuk'] += $data['total_masuk'];
                            $grand_total['pindahan'] += $data['total_pindahan'];
                            $grand_total['dipindahkan'] += $data['total_dipindahkan'];
                            $grand_total['hidup'] += $data['total_hidup'];
                            $grand_total['pria_48'] += $data['total_pria_mati_48'];
                            $grand_total['pria_48plus'] += $data['total_pria_mati_48plus'];
                            $grand_total['wanita_48'] += $data['total_wanita_mati_48'];
                            $grand_total['wanita_48plus'] += $data['total_wanita_mati_48plus'];
                            $grand_total['lama'] += $data['total_lama'];
                            $grand_total['akhir'] += $data['total_akhir'];
                            endforeach;
                            ?>
                            
                            <!-- Grand Total -->
                            <tr class="total-row">
                                <td colspan="2" style="text-align: right;">GRAND TOTAL:</td>
                                <td><?php echo number_format($grand_total['awal']); ?></td>
                                <td><?php echo number_format($grand_total['masuk']); ?></td>
                                <td><?php echo number_format($grand_total['pindahan']); ?></td>
                                <td><?php echo number_format($grand_total['dipindahkan']); ?></td>
                                <td><?php echo number_format($grand_total['hidup']); ?></td>
                                <td><?php echo number_format($grand_total['pria_48']); ?></td>
                                <td><?php echo number_format($grand_total['pria_48plus']); ?></td>
                                <td><?php echo number_format($grand_total['wanita_48']); ?></td>
                                <td><?php echo number_format($grand_total['wanita_48plus']); ?></td>
                                <td><?php echo number_format($grand_total['lama']); ?></td>
                                <td><?php echo number_format($grand_total['akhir']); ?></td>
                            </tr>
                    </tbody>
                </table>
            </div>
            
            <?php 
                else:
            ?>
                <div class="no-data">
                    <h3>❌ Data Tidak Ditemukan</h3>
                    <p>Tidak ada data kamar aktif dalam sistem.</p>
                </div>
            <?php 
                endif;
            } else {
            ?>
                <div class="no-data">
                    <h3>📋 Laporan Kamar Inap</h3>
                    <p>Silakan pilih periode tanggal pada form di atas untuk menampilkan laporan.</p>
                    <br>
                    <div style="text-align: left; max-width: 600px; margin: 0 auto;">
                        <strong>📊 Informasi Laporan:</strong>
                        <ul style="text-align: left; color: #6c757d;">
                            <li>Laporan menampilkan data kamar inap per bangsal dan kelas</li>
                            <li>Data dikelompokkan berdasarkan nama bangsal</li>
                            <li>Terdapat subtotal per bangsal dan grand total keseluruhan</li>
                            <li>Data mencakup pasien masuk, keluar, pindah kamar, dan kematian</li>
                            <li>Kematian dibedakan berdasarkan jenis kelamin dan durasi rawat inap</li>
                        </ul>
                    </div>
                </div>
            <?php } ?>
            
            <?php mysqli_close($koneksi); ?>
        </div>
    </div>

    <script>
        function resetForm() {
            document.getElementById('tgl_awal').value = '<?php echo date('Y-m-01'); ?>';
            document.getElementById('tgl_akhir').value = '<?php echo date('Y-m-t'); ?>';
        }
        
        // Validasi tanggal
        document.querySelector('form').addEventListener('submit', function(e) {
            const tgl_awal = new Date(document.getElementById('tgl_awal').value);
            const tgl_akhir = new Date(document.getElementById('tgl_akhir').value);
            
            if (tgl_akhir < tgl_awal) {
                e.preventDefault();
                alert('⚠️ Tanggal akhir tidak boleh lebih kecil dari tanggal awal!');
                return false;
            }
        });
    </script>
</body>
</html>
