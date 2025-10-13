<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informed Consent</title>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .signa                    <tr>
                        <th colspan="6">
                            Dengan ini menyatakan <u>PERSETUJUAN TINDAKAN MEDIK</u> :
                            <br>
                            (..............................................................................................................................)
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
                            Alamat : <strong><?php echo htmlspecialchars($alamat); ?></strong><br>
                            Identitas : <strong><?php echo htmlspecialchars($ktp); ?></strong>&nbsp;
                            NORM : <strong><?php echo htmlspecialchars($no_rkm_medis); ?></strong>     text-align: left;
            margin-top: 30px;
            width: 100%;
            max-width: 370px;
            padding: 10px 20px;
            background: #fff;
            border-radius: 8px;
            border: none;
            box-sizing: border-box;
        }
        .signature img {
            display: block;
            margin: 10px 0 10px 0;
        }
        .signature p {
            margin: 4px 0;
        }
        .signature button { margin-top: 6px; }
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
        .form-section { overflow-x: auto; }
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
        }
        .content {
            margin: 20px 0;
        }

        /* Canvas responsif mengikuti lebar container */
        .sig-pad { display: block; width: 100%; height: auto; }

        /* Lebih rapat untuk blok teks tertentu */
        .compact-lines { line-height: 1.2; }
        .form-section td.compact-lines { padding-top: 4px; padding-bottom: 4px; }

        @media (max-width: 600px) {
            .form-section td { padding: 6px; }
            .form-section table { font-size: 14px; }
        }

        /* Sembunyikan elemen non-perlu saat cetak */
        @media print {
            .search-form,
            .print-button,
            .signature button { display: none !important; }
            .signature { border: none !important; box-shadow: none !important; }
        }

        /* Baris tanda tangan 3 kolom sama lebar */
        .signatures-row { display: flex; gap: 16px; align-items: flex-start; }
        .signatures-row .sig-col { flex: 1 1 0; }
        /* Hilangkan batas max agar ketiganya mengikuti lebar kolom masing-masing */
        .signatures-row .signature { max-width: none; }
    .sig-label { text-align: center; font-weight: 600; margin-bottom: 6px; }
        @media (max-width: 768px) { .signatures-row { flex-direction: column; gap: 12px; } }
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

        <!-- Tombol untuk preview cetak -->
        <div class="print-button">
            <button id="btnPrint">Preview Cetak</button>
        </div>

        <!-- Konten Surat -->
        <div class="content">

            <h3 class="center-text">PEMBERIAN INFORMASI DAN PERSETUJUAN TINDAKAN MEDIK</h3>

            <?php
            include 'koneksi.php';

            // Inisialisasi variabel dengan nilai default
            $nm_pasien = $no_rkm_medis = $tgl_lahir = $pekerjaan = $stts_nikah = $alamat = "";
            $tgl_registrasi = $p_jawab = $keluarga = $hubunganpj = $jk = $namakeluarga = "";
            $umur = $alamatpj = $ktp = $tlp = $dokter = "";
            $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
            $kabupaten = "Bandar Lampung";
            $signature_file = '';

            // Proses filter jika tombol "Filter" diklik
            if (isset($_POST['filter'])) {
                // Validasi nomor rawat
                if (empty($no_rawat)) {
                    echo "<p style='color: red;'>Masukkan nomor rawat</p>";
                } else {
                    $query = "SELECT 
                                rp.tgl_registrasi ,
                                rp.no_rawat ,
                                rp.p_jawab ,
                                rp.hubunganpj ,
                                p.no_rkm_medis ,
                                p.nm_pasien ,
                                p.tgl_lahir ,
                                p.umur ,
                                p.jk ,
                                p.namakeluarga ,
                                p.keluarga ,
                                p.pekerjaan ,
                                p.stts_nikah ,
                                p.alamat ,
                                p.no_ktp ,
                                p.no_tlp ,
                                p.alamatpj ,
                                d.nm_dokter 								
                            FROM reg_periksa rp
                            LEFT JOIN pasien p ON rp.no_rkm_medis  = p.no_rkm_medis 
                            LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter 
                            WHERE rp.no_rawat = ? ";

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
                        $alamatpj   = $row['alamatpj'] ?? '';
                        $ktp            = $row['no_ktp'] ?? '';
                        $tlp            = $row['no_tlp'] ?? '';
                        $dokter         = $row['nm_dokter'] ?? '';
                        
                        // Format tanggal
                        // Simpan apa adanya (format dari DB), format saat render
                        
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
                    <td style="width: 90px;"><strong>Nomor RM</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars($no_rkm_medis); ?></strong></td>
                </tr>
                <tr>
                    <td style="width: 90px;"><strong>Nama Pasien</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars($nm_pasien); ?></strong></td>
                </tr>
                <tr>
                    <td style="width: 90px;"><strong>Nomor Rawat</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars($no_rawat); ?></strong></td>
                </tr>
                <tr>
                    <td style="width: 90px;"><strong>Tanggal Lahir</strong></td>
                    <td style="width: 10px;"><strong>:</strong></td>
                    <td><strong><?php echo htmlspecialchars(formatTanggalIndo($tgl_lahir)); ?></strong></td>
                </tr>
            </table>

            <div class="form-section">
                <table>
                    <!-- PEMBERIAN INFORMASI DAN PERSETUJUAN TINDAKAN MEDIK -->
                    <tr>
                        <td colspan="2">Dokter Pelaksana Tindakan </td>
                        <td colspan="2"><strong><?php echo htmlspecialchars($dokter); ?></strong></td>
                        <td colspan="2">Penerima Informasi : <strong><?php echo htmlspecialchars($keluarga); ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="2">Pemberi Informasi</td>
                        <td colspan="2"><b>Perawat</b></td>
                    </tr>
                    <tr>
                        <th colspan="1">No</th>
                        <th colspan="1">Jenis Informasi</th>
                        <th colspan="2">Isi Informasi</th>
                        <th colspan="1">tanda</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>Diagnosis ( WD dan DD )</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Dasar diagnosis</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Tindakan kedokteran</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Indikasi tindakan</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Tata cara</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Tujuan</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>Resiko</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>Komplikasi</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>Alternatif dan resiko</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>Prognosis</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>Biaya*</td>
                        <td colspan="2"></td>
                        <td>√</td>
                    </tr>
                    <tr>
                        <td colspan="3">Tanggal :</td>
                        <td colspan="1">Pukul :</td>
                    </tr>
                    <tr>
                        <td colspan="4">Dengan ini menyatakan bahwa saya telah menerangkan hal-hal diatas secara benar dan jujur dan memberikan kesempatan untuk bertanya dan /atau berdiskusi</td>
                        <td colspan="2">
                            <div class="signature" data-slot="pemberi">
                                <canvas class="sig-pad" width="200" height="120" style="border:1px solid #888; background:#fff;"></canvas>
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
                <canvas class="sig-pad" width="200" height="120" style="border:1px solid #888; background:#fff;"></canvas>
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
                            Nama : <strong><?php echo htmlspecialchars($namakeluarga); ?> &nbsp; (<?php echo htmlspecialchars($jk); ?>)</strong>
                            <br>
                            Hubungan dengan pasien : <strong><?php echo htmlspecialchars($hubunganpj); ?></strong>
                            <br>
                            Alamat : <strong><?php echo htmlspecialchars($alamatpj); ?></strong>&nbsp; No. Telepon : <strong><?php echo htmlspecialchars($tlp); ?></strong><br>
                        </td>
  </tr>
  <tr>
  
  
    <th colspan="6">Dengan ini menyatakan  <u>PERSETUJUAN TINDAKAN MEDIK</u> :
    <br>
    (..............................................................................................................................)
    </th>
  </tr>
<tr>
    <td colspan="6" class="compact-lines"><!--Terhadap :   Saya    Anak     Istri      Suami    Orang Tua     Lain-lain : ...............-->
<br>
Nama             : <strong><?php echo htmlspecialchars($nm_pasien); ?> &nbsp; (<?php echo htmlspecialchars($jk); ?>)</strong> 
<br>
Umur             : <strong><?php echo htmlspecialchars($umur); ?> </strong>	
<br>
Alamat          : <strong><?php echo htmlspecialchars($alamat); ?> </strong><br>
Identitas        : <strong><?php echo htmlspecialchars($ktp); ?></strong>&nbsp;
NORM        : <strong><?php echo htmlspecialchars($no_rkm_medis); ?></strong>
</td>
  </tr>
  
 <tr>
    <td colspan="6"><p><?php echo htmlspecialchars($kabupaten); ?>, <?php echo formatTanggalIndo($tgl_registrasi); ?> </p>

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
                    <div class="sig-label">Saksi 1</div>
                    <div class="signature" data-slot="saksi1">
                        <canvas class="sig-pad" width="200" height="120" style="border:1px solid #888; background:#fff;"></canvas>
                        <br>
                        <button type="button" data-action="clear">Hapus Tanda Tangan</button>
                        <br>
                        <button type="button" data-action="save">Simpan Tanda Tangan</button>
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
               
           

           <!-- <table class="no-border-table">-->
                <tr>
                <td colspan="6">Biaya adalah perkiraan biaya yang  harus dibayarkan oleh pihak pasien berdasarkan perkiraan dalam kasus-kasus sewajarnya dan tidak mengikat kedua belah pihak apabila ada perluasan 
<!--* isilah kolom dan ……  diatas dengan benar, berilah tanda ceklis (√) pada jawaban yang benar dan sesuai. Bubuhkan tandatangan dan nama jelas pada kolom yang disediakan.-->
</td>
                </tr> 
            </table>

<!--             <table class="no-border-table">
                <tr><td width="30%">Nama Pasien</td><td width="5%">:</td><td width="65%"><strong><?php echo htmlspecialchars($nm_pasien); ?></strong></td></tr>
                <tr><td>Nomor Rekam Medis</td><td>:</td><td><strong><?php echo htmlspecialchars($no_rkm_medis); ?></strong></td></tr>
                <tr><td>Tanggal Lahir</td><td>:</td><td><strong><?php echo htmlspecialchars($tgl_lahir); ?></strong></td></tr>
                <tr><td>Jenis Kelamin</td><td>:</td><td><strong><?php echo htmlspecialchars($jk); ?></strong></td></tr>
                <tr><td>Pekerjaan</td><td>:</td><td><strong><?php echo htmlspecialchars($pekerjaan); ?></strong></td></tr>
                <tr><td>Status</td><td>:</td><td><strong><?php echo htmlspecialchars($stts_nikah); ?></strong></td></tr>
                <tr><td>Alamat</td><td>:</td><td><strong><?php echo htmlspecialchars($alamat); ?></strong></td></tr>
                <tr><td>Nama Keluarga</td><td>:</td><td><strong><?php echo htmlspecialchars($namakeluarga); ?></strong></td></tr>
                <tr><td>Hubungan Keluarga</td><td>:</td><td><strong><?php echo htmlspecialchars($keluarga); ?></strong></td></tr>
                <tr><td>Penanggung Jawab</td><td>:</td><td><strong><?php echo htmlspecialchars($p_jawab); ?></strong></td></tr>
                <tr><td>Hubungan dengan Pasien</td><td>:</td><td><strong><?php echo htmlspecialchars($hubunganpj); ?></strong></td></tr>
                <tr><td>Tempat Pemeriksaan</td><td>:</td><td><strong>RS Mata LEC</strong></td></tr>
                <tr><td>Tanggal Registrasi</td><td>:</td><td><strong><?php echo htmlspecialchars($tgl_registrasi); ?></strong></td></tr>
            </table>

            <table class="no-border-table">
                <tr>
                    <td>Demikian Surat Keterangan ini dibuat dengan sebenarnya, agar dapat dipergunakan sebagaimana mestinya</td>
                </tr>
            </table>
 -->
            <?php
            function formatTanggalIndo($tanggal) {
                if (empty($tanggal)) return '';
                $bulanIndo = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                // Terima format Y-m-d, d-m-Y, atau yyyymmdd
                $t = trim($tanggal);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) {
                    [$y,$m,$d] = explode('-', $t);
                } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $t)) {
                    [$d,$m,$y] = explode('-', $t);
                } elseif (preg_match('/^\d{8}$/', $t)) {
                    $y = substr($t,0,4); $m = substr($t,4,2); $d = substr($t,6,2);
                } else {
                    return $tanggal;
                }
                $mi = intval($m); $di = intval($d);
                return $di.' '.($bulanIndo[$mi] ?? $m).' '.$y;
            }
            ?>            
<!--
            <div class="signature">
                <p><?php echo htmlspecialchars($kabupaten); ?>, <?php echo formatTanggalIndo($tgl_registrasi); ?> </p>
                <p>Pasien/ Keluarga/ Penanggung Jawab Pasien</p>
                <br><br>
                <canvas id="signature-pad" width="350" height="120" style="border:1px solid #888; background:#fff;"></canvas>
                <br>
                <button type="button" onclick="signaturePad.clear()">Hapus Tanda Tangan</button>
                <button type="button" onclick="saveSignature()">Simpan Tanda Tangan</button>
                <br><br>
                <p><strong><u id="signed-name"><?php echo htmlspecialchars($p_jawab); ?></u></strong></p>                               
            </div>
--> 
            
        </div>       
    </div>    

    <script>
        // Preview Cetak: simpan PDF server-side terlebih dahulu
        document.getElementById('btnPrint').addEventListener('click', function () {
            const noRawat = (document.querySelector('input[name="no_rawat"]').value || '').trim();
            if (!noRawat) { window.print(); return; }
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'simpan_informedconsent_pdf.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    try {
                        const res = JSON.parse(xhr.responseText || '{}');
                        if (xhr.status === 200 && res.ok) {
                            alert('PDF tersimpan: ' + (res.file || ''));
                        } else {
                            alert('Gagal menyimpan PDF di server: ' + (res.msg || '')); 
                        }
                    } catch (e) {
                        // Bisa jadi backend belum terpasang mPDF; tetap lanjut print
                    }
                    window.print();
                }
            };
            xhr.send('no_rawat=' + encodeURIComponent(noRawat));
        });

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