<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informed Consent</title>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .signature {
            text-align: left;
            margin-top: 30px;
            width: 100%;
            max-width: 370px;
            padding: 10px 20px;
            background: #fff;
            border-radius: 8px;
            border: none;
            box-sizing: border-box;
        }
        /* Signature khusus untuk pemberi dan penerima - ukuran lebih besar */
        .signature[data-slot="pemberi"], 
        .signature[data-slot="penerima"] {
            max-width: none;
            width: 100%;
            margin-top: 10px;
            padding: 15px;
        }
        .signature[data-slot="pemberi"] .sig-pad,
        .signature[data-slot="penerima"] .sig-pad {
            height: 150px;
            min-height: 150px;
        }
        .signature img {
            display: block;
            margin: 10px 0 10px 0;
        }
        .signature p {
            margin: 4px 0;
        }
        .signature button { 
            margin-top: 6px; 
        }
        .no-border-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .no-border-table td {
            padding: 5px;
            vertical-align: top;
        }
        .center-text {
            text-align: center;
        }
        .form-section table {
            width: 100%;
            border-collapse: collapse;
        }
        /* Izinkan scroll horizontal bila tabel melebar di layar kecil */
        .form-section { 
            overflow-x: auto; 
        }
        .form-section td, .form-section th {
            padding: 8px;
            vertical-align: top;
            border: 1px solid #ddd;
        }
        .form-section th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        .search-form {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .print-button {
            margin: 10px 0;
            text-align: right;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            align-items: center;
        }
        .btn-pdf {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-pdf:hover {
            background: #c82333;
        }
        .btn-print {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-print:hover {
            background: #0056b3;
        }
        .pdf-status {
            font-size: 12px;
            color: #28a745;
            font-weight: bold;
        }
        .content {
            margin: 20px 0;
        }

        /* Canvas responsif mengikuti lebar container */
        .sig-pad { 
            display: block; 
            width: 100%; 
            height: auto; 
        }

        /* Lebih rapat untuk blok teks tertentu */
        .compact-lines { 
            line-height: 1.2; 
        }
        .form-section td.compact-lines { 
            padding-top: 4px; 
            padding-bottom: 4px; 
        }

        @media (max-width: 600px) {
            .form-section td { 
                padding: 6px; 
            }
            .form-section table { 
                font-size: 14px; 
            }
        }

        /* Sembunyikan elemen non-perlu saat cetak */
        @media print {
            .search-form,
            .print-button,
            .signature button,
            .pdf-status { 
                display: none !important; 
            }
            .signature { 
                border: none !important; 
                box-shadow: none !important; 
            }
            /* Pastikan signature tetap sejajar saat print */
            .signatures-row {
                display: flex !important;
                flex-direction: row !important;
                gap: 16px !important;
                align-items: flex-start !important;
            }
            .signatures-row .sig-col {
                flex: 1 1 0 !important;
            }
        }

        /* Baris tanda tangan 3 kolom sama lebar */
        .signatures-row { 
            display: flex; 
            gap: 16px; 
            align-items: flex-start; 
        }
        .signatures-row .sig-col { 
            flex: 1 1 0; 
        }
        /* Hilangkan batas max agar ketiganya mengikuti lebar kolom masing-masing */
        .signatures-row .signature { 
            max-width: none; 
        }
        .sig-label { 
            text-align: center; 
            font-weight: 600; 
            margin-bottom: 6px; 
        }
        @media (max-width: 768px) { 
            .signatures-row { 
                flex-direction: column; 
                gap: 12px; 
            } 
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'header.php'; ?>

        <!-- Form Pencarian -->
        <div class="search-form">
            <form method="POST">
                Cari Berdasarkan Nomor Rawat : 
                <input type="text" name="no_rawat" required value="<?php echo isset($_POST['no_rawat']) ? htmlspecialchars($_POST['no_rawat']) : ''; ?>">
                <button type="submit" name="filter">Cari</button>
            </form>
        </div>

        <!-- Tombol akan ditampilkan setelah inisialisasi variabel -->

        <!-- Konten Surat -->
        <div class="content">
            <h3 class="center-text">PEMBERIAN INFORMASI DAN PERSETUJUAN TINDAKAN MEDIK</h3>

            <?php
            include 'koneksi.php';

            // Fungsi untuk format tanggal Indonesia
            function formatTanggalIndo($tanggal) {
                $bulanIndo = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                $parts = explode('-', $tanggal);
                if (count($parts) == 3) {
                    return intval($parts[2]) . ' ' . $bulanIndo[intval($parts[1])] . ' ' . $parts[0];
                }
                return $tanggal;
            }

            // Inisialisasi variabel dengan nilai default
            $nm_pasien = $no_rkm_medis = $tgl_lahir = $pekerjaan = $stts_nikah = $alamat = "";
            $tgl_registrasi = $p_jawab = $keluarga = $hubunganpj = $jk = $namakeluarga = "";
            $umur = $alamatpj = $ktp = $tlp = $dokter = "";
            
            // Variabel dari tabel persetujuan_penolakan_tindakan
            $no_pernyataan = $tanggal_pernyataan = $penerima_informasi = "";
            $saksi_keluarga = $nama_petugas = "";
            
            $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
            $kabupaten = "Bandar Lampung";
            $signature_file = '';

            // Cek apakah file PDF sudah ada
            $pdf_exists = false;
            $pdf_filename = '';
            if (!empty($no_pernyataan)) {
                $pdf_filename = "informedconsent/persetujuan_" . preg_replace('/[^a-zA-Z0-9]/', '_', $no_pernyataan) . ".pdf";
                $pdf_exists = file_exists($pdf_filename);
            }
            ?>
            
            <!-- Tombol untuk preview cetak dan save PDF -->
            <div class="print-button">
                <div class="pdf-status" id="pdfStatus" style="<?php echo $pdf_exists ? 'display: block;' : 'display: none;'; ?>">
                    ✅ PDF sudah tersimpan
                </div>
                <!-- Debug info (hapus setelah testing) -->
                <div style="font-size: 10px; color: #666; margin: 5px 0;">
                    Debug: no_pernyataan = "<?php echo htmlspecialchars($no_pernyataan); ?>" | 
                    no_rawat = "<?php echo htmlspecialchars($no_rawat); ?>" |
                    empty: <?php echo empty($no_pernyataan) ? 'true' : 'false'; ?>
                </div>
                <button type="button" id="btnSavePDF" class="btn-pdf">
                    💾 Simpan ke PDF
                </button>
                <button type="button" id="btnPrint" class="btn-print">🖨️ Preview Cetak</button>
                <button type="button" id="btnTestMPDF" class="btn-secondary" style="background: #ffc107; color: #000; margin-left: 5px; font-size: 12px; padding: 8px 12px;">
                    🧪 Test mPDF
                </button>
                <button type="button" id="btnDebugJS" class="btn-secondary" style="background: #17a2b8; color: #fff; margin-left: 5px; font-size: 12px; padding: 8px 12px;">
                    🐛 Debug JS
                </button>
            </div>
            
            <?php
            // Proses filter jika tombol "Filter" diklik
            if (isset($_POST['filter'])) {
                // Validasi nomor rawat
                if (empty($no_rawat)) {
                    echo "<p style='color: red;'>Masukkan nomor rawat</p>";
                } else {
                    $query = "SELECT 
                                ppt.no_pernyataan,
                                ppt.tanggal,
                                ppt.penerima_informasi,
                                ppt.saksi_keluarga,
                                pt.nama as nama_petugas,
                                rp.tgl_registrasi,
                                rp.no_rawat,
                                rp.p_jawab,
                                rp.hubunganpj,
                                p.no_rkm_medis,
                                p.nm_pasien,
                                p.tgl_lahir,
                                p.umur,
                                p.jk,
                                p.namakeluarga,
                                p.keluarga,
                                p.pekerjaan,
                                p.stts_nikah,
                                p.alamat,
                                p.no_ktp,
                                p.no_tlp,
                                p.alamatpj,
                                d.nm_dokter
                            FROM persetujuan_penolakan_tindakan ppt
                            INNER JOIN reg_periksa rp ON ppt.no_rawat = rp.no_rawat
                            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                            LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
                            LEFT JOIN petugas pt ON ppt.nip = pt.nip
                            WHERE ppt.no_rawat = ?";

                    $stmt = mysqli_prepare($koneksi, $query);
                    if (!$stmt) {
                        die("Query prepare error: " . mysqli_error($koneksi));
                    }
                    mysqli_stmt_bind_param($stmt, "s", $no_rawat);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if (!$result) {
                        die("Query error: " . mysqli_error($koneksi));
                    }

                    // Ambil data dari hasil query
                    if (mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_assoc($result);
                        
                        // Data dari tabel persetujuan_penolakan_tindakan
                        $no_pernyataan          = $row['no_pernyataan'] ?? '';
                        $tanggal_pernyataan     = $row['tanggal'] ?? '';
                        $penerima_informasi     = $row['penerima_informasi'] ?? '';
                        $saksi_keluarga         = $row['saksi_keluarga'] ?? '';
                        $nama_petugas           = $row['nama_petugas'] ?? '';
                        
                        // Data dari tabel lainnya
                        $nm_pasien      = $row['nm_pasien'] ?? '';
                        $no_rkm_medis   = $row['no_rkm_medis'] ?? '';
                        $tgl_lahir      = $row['tgl_lahir'] ?? '';
                        $umur           = $row['umur'] ?? '';
                        $pekerjaan      = $row['pekerjaan'] ?? '';
                        $stts_nikah     = $row['stts_nikah'] ?? '';
                        $alamat         = $row['alamat'] ?? '';
                        $tgl_registrasi = $row['tgl_registrasi'] ?? '';
                        $p_jawab        = $row['p_jawab'] ?? '';
                        $keluarga       = $row['keluarga'] ?? '';
                        $hubunganpj     = $row['hubunganpj'] ?? '';
                        $jk             = $row['jk'] ?? '';
                        $namakeluarga   = $row['namakeluarga'] ?? '';
                        $alamatpj       = $row['alamatpj'] ?? '';
                        $ktp            = $row['no_ktp'] ?? '';
                        $tlp            = $row['no_tlp'] ?? '';
                        $dokter         = $row['nm_dokter'] ?? '';
                        
                        // Cari file tanda tangan
                        $files = glob("image/" . preg_replace('/[^a-zA-Z0-9]/', '', $no_rawat) . "_*.png");
                        if ($files && count($files) > 0) {
                            // Ambil file terbaru
                            usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
                            $signature_file = $files[0];
                        }
                    } else {
                        echo "<p style='color: red;'>Data tidak ditemukan untuk nomor rawat: " . htmlspecialchars($no_rawat) . "</p>";
                    }
                }
            }
            ?>

            <table class="no-border-table" style="margin-bottom:10px;">
                <tr>
                    <td style="width: 120px;"><strong>No. Pernyataan</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars($no_pernyataan); ?></strong></td>
                    <td style="width: 120px;"><strong>Tanggal</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars(formatTanggalIndo($tanggal_pernyataan)); ?></strong></td>
                </tr>
                <tr>
                    <td style="width: 120px;"><strong>Nomor RM</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars($no_rkm_medis); ?></strong></td>
                    <td style="width: 120px;"><strong>Nama Pasien</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars($nm_pasien); ?></strong></td>
                </tr>
                <tr>
                    <td style="width: 120px;"><strong>Nomor Rawat</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars($no_rawat); ?></strong></td>
                    <td style="width: 120px;"><strong>Tanggal Lahir</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars(formatTanggalIndo($tgl_lahir)); ?></strong></td>
                </tr>
                <tr>
                    <td style="width: 120px;"><strong>Penerima Informasi</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars($penerima_informasi ? $penerima_informasi : $p_jawab); ?></strong></td>
                    <td style="width: 120px;"><strong>Petugas</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars($nama_petugas ? $nama_petugas : $dokter); ?></strong></td>
                </tr>
            </table>

            <div class="form-section">
                <table>
                    <!-- PEMBERIAN INFORMASI DAN PERSETUJUAN TINDAKAN MEDIK -->
                    <tr>
                        <td colspan="6">
                            Dokter Pelaksana Tindakan : <strong><?php echo htmlspecialchars($dokter); ?></strong><br>
                            Penerima Informasi : <strong><?php echo htmlspecialchars($penerima_informasi ? $penerima_informasi : $keluarga); ?></strong><br>
                            Pemberi Informasi : <strong><?php echo htmlspecialchars($nama_petugas ? $nama_petugas : 'Perawat'); ?></strong>
                        </td>
                    </tr>                    
                    <tr>
                        <th colspan="1">No</th>
                        <th colspan="1">Jenis Informasi</th>
                        <th colspan="3">Isi Informasi</th>
                        <th colspan="1">Tanda</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>Diagnosis ( WD dan DD )</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Dasar diagnosis</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Tindakan kedokteran</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Indikasi tindakan</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Tata cara</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Tujuan</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>Resiko</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>Komplikasi</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>Alternatif dan resiko</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>Prognosis</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>Biaya*</td>
                        <td colspan="3"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td colspan="4">Tanggal : <?php echo htmlspecialchars(formatTanggalIndo($tgl_registrasi)); ?></td>
                        <td colspan="2">Pukul :</td>
                    </tr>
                    <tr>
                        <td colspan="4">Dengan ini menyatakan bahwa saya telah menerangkan hal-hal diatas secara benar dan jujur dan memberikan kesempatan untuk bertanya dan /atau berdiskusi</td>
                        <td colspan="2">
                            <div class="signature" data-slot="pemberi">
                                <canvas class="sig-pad" width="300" height="150" style="border:1px solid #888; background:#fff;"></canvas>
                                <br>
                                <button type="button" data-action="clear">Hapus Tanda Tangan</button>
                                <br>
                                <button type="button" data-action="save">Simpan Tanda Tangan</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">Dengan ini menyatakan bahwa saya telah menerima informasi sebagaimana adanya di atas yang sudah diberi tanda /paraf dikolom kanannya dan saya telah memahaminya.</td>
                        <td colspan="2">
                            <div class="signature" data-slot="penerima">
                                <canvas class="sig-pad" width="300" height="150" style="border:1px solid #888; background:#fff;"></canvas>
                                <br>
                                <button type="button" data-action="clear">Hapus Tanda Tangan</button>
                                <br>
                                <button type="button" data-action="save">Simpan Tanda Tangan</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="6">PERSETUJUAN TINDAKAN MEDIK</th>
                    </tr>
                    <tr>
                        <td colspan="6" class="compact-lines">
                            Setelah mendapatkan informasi mengenai tindakan medik yang akan dilakukan, oleh dokter yang tertera pada pemberian informasi di atas. Maka, yang bertandatangan dibawah ini, saya :
                            <br>
                            Nama : <strong><?php echo htmlspecialchars($penerima_informasi ? $penerima_informasi : $namakeluarga); ?> &nbsp; (<?php echo htmlspecialchars($jk); ?>)</strong>
                            <br>
                            Hubungan dengan pasien : <strong><?php echo htmlspecialchars($hubunganpj); ?></strong>
                            <br>
                            Alamat : <strong><?php echo htmlspecialchars($alamatpj); ?></strong>&nbsp; No. Telepon : <strong><?php echo htmlspecialchars($tlp); ?></strong>
                            <br>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="6">
                            Dengan ini menyatakan <u>PERSETUJUAN TINDAKAN MEDIK</u> :
                            <br>
                                (..............................................................................................................................)
                            </th>
                        </th>
                    </tr>
                    <tr>
                        <td colspan="6" class="compact-lines">
                            <!--Terhadap :   Saya    Anak     Istri      Suami    Orang Tua     Lain-lain : ...............-->
                            <br>
                            Nama : <strong><?php echo htmlspecialchars($nm_pasien); ?> &nbsp; (<?php echo htmlspecialchars($jk); ?>)</strong>
                            <br>
                            Umur : <strong><?php echo htmlspecialchars($umur); ?></strong>
                            <br>
                            Alamat : <strong><?php echo htmlspecialchars($alamat); ?></strong>
                            <br>
                            Identitas : <strong><?php echo htmlspecialchars($ktp); ?></strong>&nbsp;
                            NORM : <strong><?php echo htmlspecialchars($no_rkm_medis); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <p><?php echo htmlspecialchars($kabupaten); ?>, <?php echo formatTanggalIndo($tanggal_pernyataan ? $tanggal_pernyataan : $tgl_registrasi); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <div class="signatures-row">
                                <div class="sig-col">
                                    <div class="sig-label">Yang menyatakan</div>
                                    <div class="signature" data-slot="yangmenyatakan">
                                        <canvas class="sig-pad" width="200" height="120" style="border:1px solid #888; background:#fff;"></canvas>
                                        <br>
                                        <button type="button" data-action="clear">Hapus Tanda Tangan</button>
                                        <br>
                                        <button type="button" data-action="save">Simpan Tanda Tangan</button>
                                    </div>
                                </div>
                                <div class="sig-col">
                                    <div class="sig-label">Saksi 1 (Keluarga)</div>
                                    <div class="signature" data-slot="saksi1">
                                        <canvas class="sig-pad" width="200" height="120" style="border:1px solid #888; background:#fff;"></canvas>
                                        <br>
                                        <button type="button" data-action="clear">Hapus Tanda Tangan</button>
                                        <br>
                                        <button type="button" data-action="save">Simpan Tanda Tangan</button>
                                        <?php if (!empty($saksi_keluarga)): ?>
                                        <br>
                                        <strong><?php echo htmlspecialchars($saksi_keluarga); ?></strong>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="sig-col">
                                    <div class="sig-label">Saksi 2</div>
                                    <div class="signature" data-slot="saksi2">
                                        <canvas class="sig-pad" width="200" height="120" style="border:1px solid #888; background:#fff;"></canvas>
                                        <br>
                                        <button type="button" data-action="clear">Hapus Tanda Tangan</button>
                                        <br>
                                        <button type="button" data-action="save">Simpan Tanda Tangan</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            Biaya adalah perkiraan biaya yang harus dibayarkan oleh pihak pasien berdasarkan perkiraan dalam kasus-kasus sewajarnya dan tidak mengikat kedua belah pihak apabila ada perluasan
                            <!--* isilah kolom dan ……  diatas dengan benar, berilah tanda ceklis (√) pada jawaban yang benar dan sesuai. Bubuhkan tandatangan dan nama jelas pada kolom yang disediakan.-->
                        </td>
                    </tr>
                </table>
            </div>


        </div>
    </div>

    <script>
        // Save to PDF
        document.addEventListener('DOMContentLoaded', function() {
            const btnSavePDF = document.getElementById('btnSavePDF');
            if (!btnSavePDF) {
                console.error('Tombol btnSavePDF tidak ditemukan');
                return;
            }
            
            btnSavePDF.addEventListener('click', function () {
                console.log('Tombol PDF diklik');
                
                const noPernyataan = <?php echo json_encode($no_pernyataan); ?>;
                const noRawat = <?php echo json_encode($no_rawat); ?>;
                
                console.log('noPernyataan:', noPernyataan);
                console.log('noRawat:', noRawat);
                
                if (!noPernyataan) {
                    alert('❌ Tidak ada data pernyataan untuk disimpan ke PDF');
                    return;
                }
                
                if (!noRawat) {
                    alert('❌ Nomor rawat tidak ditemukan');
                    return;
                }
                
                // Show loading
                this.disabled = true;
                this.innerHTML = '⏳ Menyimpan...';
                console.log('Mulai request ke save_persetujuan_pdf.php');
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'save_persetujuan_pdf.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = () => {
                    console.log('ReadyState:', xhr.readyState, 'Status:', xhr.status);
                    
                    if (xhr.readyState === 4) {
                        // Reset button
                        this.disabled = false;
                        this.innerHTML = '💾 Simpan ke PDF';
                        
                        console.log('Response Text:', xhr.responseText);
                        
                        try {
                            const res = JSON.parse(xhr.responseText || '{}');
                            console.log('Parsed Response:', res);
                            
                            if (xhr.status === 200 && res.success) {
                                alert('✅ PDF berhasil disimpan!\n📁 File: ' + res.filename);
                                // Show PDF status
                                const pdfStatus = document.getElementById('pdfStatus');
                                if (pdfStatus) {
                                    pdfStatus.style.display = 'block';
                                }
                            } else if (res.file_exists) {
                                // File sudah ada, tanya user mau replace atau tidak
                                if (confirm('⚠️ File PDF sudah ada!\n📁 File: ' + res.filename + '\n\nApakah Anda ingin mengganti file yang sudah ada?')) {
                                    // User mau replace, kirim request ulang dengan parameter force
                                    this.disabled = true;
                                    this.innerHTML = '⏳ Mengganti file...';
                                    
                                    const xhr2 = new XMLHttpRequest();
                                    xhr2.open('POST', 'save_persetujuan_pdf.php', true);
                                    xhr2.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                    
                                    xhr2.onreadystatechange = () => {
                                        if (xhr2.readyState === 4) {
                                            this.disabled = false;
                                            this.innerHTML = '💾 Simpan ke PDF';
                                            
                                            try {
                                                const res2 = JSON.parse(xhr2.responseText || '{}');
                                                if (xhr2.status === 200 && res2.success) {
                                                    alert('✅ PDF berhasil disimpan (mengganti file lama)!\n📁 File: ' + res2.filename);
                                                    const pdfStatus = document.getElementById('pdfStatus');
                                                    if (pdfStatus) {
                                                        pdfStatus.style.display = 'block';
                                                    }
                                                } else {
                                                    alert('❌ Gagal mengganti PDF: ' + (res2.message || 'Error tidak diketahui'));
                                                }
                                            } catch (e) {
                                                alert('❌ Error saat mengganti PDF: ' + e.message);
                                            }
                                        }
                                    };
                                    
                                    const postData2 = 'no_pernyataan=' + encodeURIComponent(noPernyataan) + '&no_rawat=' + encodeURIComponent(noRawat) + '&force=1';
                                    xhr2.send(postData2);
                                } else {
                                    // User tidak mau replace
                                    console.log('User membatalkan penggantian file');
                                }
                            } else {
                                alert('❌ Gagal menyimpan PDF: ' + (res.message || 'Error tidak diketahui'));
                            }
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                            console.error('Raw Response:', xhr.responseText);
                            alert('❌ Error: Response tidak valid dari server\n\nResponse: ' + xhr.responseText.substring(0, 200));
                        }
                    }
                };
                
                xhr.onerror = function() {
                    console.error('Network Error');
                    this.disabled = false;
                    this.innerHTML = '💾 Simpan ke PDF';
                    alert('❌ Network Error: Tidak dapat menghubungi server');
                };
                
                const postData = 'no_pernyataan=' + encodeURIComponent(noPernyataan) + '&no_rawat=' + encodeURIComponent(noRawat);
                console.log('Sending data:', postData);
                xhr.send(postData);
            });
        });

        // Preview Cetak
        document.getElementById('btnPrint').addEventListener('click', function () {
            window.print();
        });

        // Test mPDF
        const btnTestMPDF = document.getElementById('btnTestMPDF');
        if (btnTestMPDF) {
            btnTestMPDF.addEventListener('click', function () {
                console.log('Test mPDF diklik');
                this.disabled = true;
                this.innerHTML = '🔄 Testing...';
                
                fetch('test_mpdf.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Test mPDF results:', data);
                    this.disabled = false;
                    this.innerHTML = '🧪 Test mPDF';
                    
                    let message = 'Test mPDF Results:\n\n';
                    message += 'Autoload exists: ' + (data.autoload_exists ? '✅' : '❌') + '\n';
                    message += 'mPDF class exists: ' + (data.mpdf_class_exists ? '✅' : '❌') + '\n';
                    message += 'mPDF instance created: ' + (data.mpdf_instance_created ? '✅' : '❌') + '\n';
                    message += 'PDF directory writable: ' + (data.pdf_dir_writable ? '✅' : '❌') + '\n';
                    message += 'Test PDF created: ' + (data.test_pdf_created ? '✅' : '❌') + '\n';
                    message += 'Database connected: ' + (data.database_connected ? '✅' : '❌') + '\n';
                    
                    if (data.mpdf_error) {
                        message += '\n❌ mPDF Error: ' + data.mpdf_error;
                    }
                    if (data.database_error) {
                        message += '\n❌ Database Error: ' + data.database_error;
                    }
                    
                    alert(message);
                })
                .catch(error => {
                    console.error('Test mPDF error:', error);
                    this.disabled = false;
                    this.innerHTML = '🧪 Test mPDF';
                    alert('❌ Error testing mPDF: ' + error.message);
                });
            });
        }

        // Debug JavaScript
        const btnDebugJS = document.getElementById('btnDebugJS');
        if (btnDebugJS) {
            btnDebugJS.addEventListener('click', function () {
                const noPernyataan = <?php echo json_encode($no_pernyataan); ?>;
                const noRawat = <?php echo json_encode($no_rawat); ?>;
                const pdfExists = <?php echo json_encode($pdf_exists); ?>;
                
                let debugInfo = 'JavaScript Debug Info:\n\n';
                debugInfo += 'no_pernyataan: "' + noPernyataan + '"\n';
                debugInfo += 'no_rawat: "' + noRawat + '"\n'; 
                debugInfo += 'pdfExists: ' + pdfExists + '\n';
                debugInfo += 'btnSavePDF element: ' + (document.getElementById('btnSavePDF') ? 'Found' : 'NOT FOUND') + '\n';
                debugInfo += 'btnSavePDF disabled: ' + (document.getElementById('btnSavePDF')?.disabled || false) + '\n';
                debugInfo += 'DOMContentLoaded fired: ' + (document.readyState) + '\n';
                
                console.log('Debug Info:', {
                    noPernyataan, noRawat, pdfExists,
                    btnSavePDF: document.getElementById('btnSavePDF'),
                    readyState: document.readyState
                });
                
                alert(debugInfo);
            });
        }

        // Inisialisasi banyak canvas tanda tangan, masing-masing dengan kontrol sendiri
        const signatureContainers = Array.from(document.querySelectorAll('.signature'));
        const pads = new Map();

        signatureContainers.forEach(container => {
            const canvas = container.querySelector('.sig-pad');
            if (!canvas) return;
            const pad = new SignaturePad(canvas);
            pads.set(container, { pad, canvas });

            // Tombol Clear
            const btnClear = container.querySelector('button[data-action="clear"]');
            if (btnClear) {
                btnClear.addEventListener('click', () => pad.clear());
            }

            // Tombol Save
            const btnSave = container.querySelector('button[data-action="save"]');
            if (btnSave) {
                btnSave.addEventListener('click', () => {
                    if (pad.isEmpty()) { alert('Silakan tanda tangan dulu.'); return; }
                    const noRawat = (document.querySelector('input[name="no_rawat"]').value || '').trim();
                    if (!noRawat) { alert('Silakan cari data pasien terlebih dahulu dengan nomor rawat.'); return; }
                    const dataURL = pad.toDataURL();
                    const slot = (container.getAttribute('data-slot') || 'ttd');
                    const now = new Date();
                    const waktu = now.getFullYear().toString()
                        + ("0"+(now.getMonth()+1)).slice(-2)
                        + ("0"+now.getDate()).slice(-2)
                        + ("0"+now.getHours()).slice(-2)
                        + ("0"+now.getMinutes()).slice(-2)
                        + ("0"+now.getSeconds()).slice(-2)
                        + '_' + slot; // bedakan per slot

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'simpan_ttd.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                alert(xhr.responseText || 'Tanda tangan disimpan.');
                            } else {
                                alert('Error menyimpan tanda tangan.');
                            }
                        }
                    };
                    xhr.send('img=' + encodeURIComponent(dataURL)
                        + '&no_rawat=' + encodeURIComponent(noRawat)
                        + '&waktu=' + encodeURIComponent(waktu)
                        + '&slot=' + encodeURIComponent(slot));
                });
            }

            // Auto-resize: skala internal canvas ke lebar actual, pertahankan coretan bila ada
            const resizeCanvas = () => {
                const data = pad.toData();
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                if (data && data.length) {
                    pad.fromData(data);
                } else {
                    pad.clear();
                }
            };
            window.addEventListener('resize', resizeCanvas);
            // Panggil sekali saat inisialisasi untuk menyesuaikan dengan lebar container saat ini
            resizeCanvas();
        });
    </script>
</body>
</html>