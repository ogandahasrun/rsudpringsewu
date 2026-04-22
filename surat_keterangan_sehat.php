<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan Sehat - RSUD Pringsewu</title>
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
        function printSurat(data) {
            const win = window.open('', '_blank');
            win.document.write(`
                <html><head><title>Surat Keterangan Sehat</title>
                <style>
                    body { font-family: Tahoma, Geneva, Verdana, sans-serif; padding: 40px; }
                    .kop { text-align: center; font-weight: bold; font-size: 18px; margin-bottom: 20px; }
                    .judul { text-align: center; font-size: 20px; font-weight: bold; text-decoration: underline; margin-bottom: 10px; }
                    .nomor { text-align: center; margin-bottom: 20px; }
                    .isi { margin-bottom: 20px; }
                    .ttd { margin-top: 40px; text-align: right; }
                </style>
                </head><body>
                <div class="kop">KOP SURAT<br>RSUD PRINGSEWU</div>
                <div class="judul">SURAT KETERANGAN SEHAT</div>
                <div class="nomor">Nomor : ${data.no_surat}</div>
                <div class="isi">
                Yang bertanda tangan di bawah ini, Dokter ${data.nm_dokter} dengan ini menerangkan bahwa :<br><br>
                Nama : ${data.nm_pasien}<br>
                Umur : ${data.umurdaftar} ${data.sttsumur}<br>
                Pekerjaan : ${data.pekerjaan}<br>
                Status : ${data.stts_nikah}<br>
                Alamat : ${data.alamat}, ${data.nm_kel}, ${data.nm_kec}, ${data.nm_kab}, ${data.nm_prop}<br>
                Tinggi / Berat : ${data.tinggi} cm / ${data.berat} Kg<br>
                Dengan Hasil Pemeriksaan : ${data.kesimpulan}<br>
                Surat Keterangan Ini Untuk Keperluan : ${data.keperluan}<br><br>
                Demikianlah Surat Keterangan ini dibuat dengan sebenarnya
                </div>
                <div class="ttd">
                Dikeluarkan di : Pringsewu<br>
                Pada tanggal : ${data.tanggalsurat}<br><br>
                <img src="${data.qr_barcode}" alt="QR Barcode" style="width:100px;"><br><br>
                ${data.nm_dokter}
                </div>
                </body></html>
            `);
            win.document.close();
            win.print();
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Surat Keterangan Sehat</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="dashboard.php">← Kembali ke Dashboard</a>
            </div>
            <?php
            include 'koneksi.php';
            $tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : '';
            $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : '';
            ?>
            <form method="POST" class="filter-form">
                <div class="filter-title">🔍 Filter Surat Keterangan Sehat</div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">📊 Tampilkan Data</button>
                    <button type="button" onclick="document.getElementById('tanggal_awal').value='';document.getElementById('tanggal_akhir').value='';" class="btn btn-secondary">🔄 Reset Filter</button>
                </div>
            </form>
            <?php
            if (isset($_POST['filter']) && !empty($tanggal_awal) && !empty($tanggal_akhir)) {
                $query = "SELECT
                    surat_keterangan_sehat.no_surat,
                    surat_keterangan_sehat.no_rawat,
                    pasien.nm_pasien,
                    pasien.no_rkm_medis,
                    pasien.pekerjaan,
                    pasien.stts_nikah,
                    pasien.alamat,
                    kelurahan.nm_kel,
                    kecamatan.nm_kec,
                    kabupaten.nm_kab,
                    surat_keterangan_sehat.tinggi,
                    surat_keterangan_sehat.berat,
                    surat_keterangan_sehat.kesimpulan,
                    surat_keterangan_sehat.keperluan,
                    propinsi.nm_prop,
                    reg_periksa.umurdaftar,
                    reg_periksa.sttsumur,
                    surat_keterangan_sehat.tanggalsurat,
                    dokter.nm_dokter
                FROM
                    surat_keterangan_sehat
                INNER JOIN reg_periksa ON surat_keterangan_sehat.no_rawat = reg_periksa.no_rawat
                INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                INNER JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
                INNER JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
                INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
                INNER JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
                INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                WHERE
                    surat_keterangan_sehat.tanggalsurat BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
                $result = mysqli_query($koneksi, $query);
                if ($result && mysqli_num_rows($result) > 0) {
                        echo "<div class='table-responsive'><table>";
                        echo "<tr>
                            <th>No</th>
                            <th>No. Surat</th>
                            <th>No. Rawat</th>
                            <th>Nama Pasien</th>
                            <th>No. RM</th>
                            <th>Pekerjaan</th>
                            <th>Status Nikah</th>
                            <th>Alamat</th>
                            <th>Kelurahan</th>
                            <th>Kecamatan</th>
                            <th>Kabupaten</th>
                            <th>Tinggi</th>
                            <th>Berat</th>
                            <th>Kesimpulan</th>
                            <th>Keperluan</th>
                            <th>Propinsi</th>
                            <th>Umur</th>
                            <th>Status Umur</th>
                            <th>Cetak</th>
                        </tr>";
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>
                                <td>{$no}</td>
                                <td>{$row['no_surat']}</td>
                                <td>{$row['no_rawat']}</td>
                                <td>{$row['nm_pasien']}</td>
                                <td>{$row['no_rkm_medis']}</td>
                                <td>{$row['pekerjaan']}</td>
                                <td>{$row['stts_nikah']}</td>
                                <td>{$row['alamat']}</td>
                                <td>{$row['nm_kel']}</td>
                                <td>{$row['nm_kec']}</td>
                                <td>{$row['nm_kab']}</td>
                                <td>{$row['tinggi']}</td>
                                <td>{$row['berat']}</td>
                                <td>{$row['kesimpulan']}</td>
                                <td>{$row['keperluan']}</td>
                                <td>{$row['nm_prop']}</td>
                                <td>{$row['umurdaftar']}</td>
                                <td>{$row['sttsumur']}</td>
                                <td><a href='cetaksuratketerangansehat.php?no_surat=" . urlencode($row['no_surat']) . "' target='_blank' class='btn btn-success'>Cetak</a></td>
                            </tr>";
                            $no++;
                        }
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        // QR Barcode dummy (replace with actual QR code logic if needed)
                        $qr_barcode = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($row['no_surat']);
                        $row['qr_barcode'] = $qr_barcode;
                        $row_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        echo "<tr>
                            <td>{$no}</td>
                            <td>{$row['no_surat']}</td>
                            <td>{$row['no_rawat']}</td>
                            <td>{$row['nm_pasien']}</td>
                            <td>{$row['no_rkm_medis']}</td>
                            <td>{$row['pekerjaan']}</td>
                            <td>{$row['stts_nikah']}</td>
                            <td>{$row['alamat']}</td>
                            <td>{$row['nm_kel']}</td>
                            <td>{$row['nm_kec']}</td>
                            <td>{$row['nm_kab']}</td>
                            <td>{$row['tinggi']}</td>
                            <td>{$row['berat']}</td>
                            <td>{$row['kesimpulan']}</td>
                            <td>{$row['keperluan']}</td>
                            <td>{$row['nm_prop']}</td>
                            <td>{$row['umurdaftar']}</td>
                            <td>{$row['sttsumur']}</td>
                        </tr>";
                        $no++;
                    }
                    echo "</table></div>";
                } else {
                    echo "<div class='no-data'>📋 Tidak ada data surat keterangan sehat pada periode yang dipilih</div>";
                }
                mysqli_close($koneksi);
            }
            ?>
        </div>
    </div>
</body>
</html>
