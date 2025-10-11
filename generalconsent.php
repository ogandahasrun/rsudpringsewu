<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Concern</title>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .signature {
            text-align: left;
            margin-top: 30px;
            width: 370px;
            padding: 10px 20px;
            background: #fff;
            border-radius: 8px;
            border: none;
        }
        .signature img {
            display: block;
            margin: 10px 0 10px 0;
        }
        .signature p {
            margin: 4px 0;
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
        .form-section td {
            padding: 8px;
            vertical-align: top;
            border: 1px solid #ddd;
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

        <!-- Tombol untuk preview cetak dan simpan PDF -->
        <div class="print-button">
            <button onclick="window.print()" type="button">Preview Cetak</button>
            <button id="btnSavePdf" type="button">Simpan PDF</button>
        </div>

        <!-- Konten Surat -->
        <div class="content">

            <h3 class="center-text">PERSETUJUAN UMUM (GENERAL CONSENT)</h3>

            <?php
            include 'koneksi.php';

            // Inisialisasi variabel dengan nilai default
            $nm_pasien = $no_rkm_medis = $tgl_lahir = $pekerjaan = $stts_nikah = $alamat = "";
            $tgl_registrasi = $p_jawab = $keluarga = $hubunganpj = $jk = $namakeluarga = "";
            $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : (isset($_GET['no_rawat']) ? $_GET['no_rawat'] : '');
            $kabupaten = "Pringsewu";
            $signature_file = '';

            // Proses filter jika tombol "Filter" diklik
            if (isset($_POST['filter']) || isset($_GET['filter'])) {
                // Validasi nomor rawat
                if (empty($no_rawat)) {
                    echo "<p style='color: red;'>Masukkan nomor rawat</p>";
                } else {
                    // Query dengan filter nomor rawat 
                    $query = "SELECT
                                reg_periksa.tgl_registrasi,
                                reg_periksa.no_rawat,
                                pasien.no_rkm_medis,
                                pasien.nm_pasien,
                                pasien.tgl_lahir,
                                pasien.jk,
                                pasien.namakeluarga,
                                pasien.keluarga,
                                reg_periksa.p_jawab,
                                reg_periksa.hubunganpj,
                                pasien.pekerjaan,
                                pasien.stts_nikah,
                                pasien.alamat
                            FROM
                                reg_periksa
                            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                            WHERE
                                reg_periksa.no_rawat = ? ";

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
                        $pekerjaan      = $row['pekerjaan'] ?? '';
                        $stts_nikah     = $row['stts_nikah'] ?? '';
                        $alamat         = $row['alamat'] ?? '';
                        $tgl_registrasi = $row['tgl_registrasi'] ?? '';
                        $p_jawab        = $row['p_jawab'] ?? '';
                        $keluarga       = $row['keluarga'] ?? '';
                        $hubunganpj     = $row['hubunganpj'] ?? '';
                        $jk             = $row['jk'] ?? '';
                        $namakeluarga   = $row['namakeluarga'] ?? '';
                        
                        // Format tanggal
                        if (!empty($tgl_registrasi)) {
                            $tgl_registrasi = date('d-m-Y', strtotime($tgl_registrasi));
                        }
                        if (!empty($tgl_lahir)) {
                            $tgl_lahir = date('d-m-Y', strtotime($tgl_lahir));
                        }
                        
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
                    <td style="width: 90px;"><strong>Nama</strong></td>
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
                    <td><strong><?php echo htmlspecialchars($tgl_lahir); ?></strong></td>
                </tr>
            </table>

            <div class="form-section">
                <table>
                    <tr>
                        <td width="5%">1.</td>
                        <td width="95%">
                            <strong>Hak, kewajiban dan tanggung jawab pasien / keluarga</strong>
                            Dengan menandatangani dokumen ini saya pasien, atau penanggung jawab, wali ataupun keluarga yang menandatangani dokumen ini untuk dan atas nama saya menyatakan bahwa telah memperoleh dan telah menerima semua informasi mengenai hak-hak, kewajiban dan tanggung jawab pasien/keluarga pada saat proses pendaftaran pasien selama mendapatkan pelayanan terhadap pada leaflet tentang hak dan kewajiban pasien.
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">2.</td>
                        <td width="95%">
                            <strong>Akses informasi kesehatan</strong>
                            Saya dengan ini memberikan persetujuan kepada Rumah Sakit Mata LEC untuk memberikan informasi tentang halhal berkaitan dengan kesehatan saya kepada penanggung jawab, keluarga, atau pihak yang menjamin biaya perawatan
                            saya di rumah sakit, jika ada hal-hal yang tidak boleh diinformasikan maka akan dibuat pernyataan tersendiri.
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">3.</td>
                        <td width="95%">
                            <strong>Kerahasiaan informasi medis/ kedokteran dan pelepasan informasi</strong>
                            Saya memberi wewenang kepada Rumah Sakit untuk memberikan kewenangan untuk terlibat dalam pengambilan
                            keputusan mengenai perawatan saya, data dan informasi mengenai diri saya dan keadaan kesehatan saya termasuk
                            dalam situasi tertentu misalnya keadaan kritis, dll kepada keluarga saya : 
                            <strong><?php echo htmlspecialchars($p_jawab); ?></strong>.
                            hubungan dengan pasien : 
                            <strong><?php echo htmlspecialchars($hubunganpj); ?></strong>.
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">4.</td>
                        <td width="95%">
                            <strong>Privasi</strong>
                            Apabila saya memerlukan hal-hal yang menyangkut privasi saya sebagai pasien, saya akan menyampaikan kepada
                            staf Rumah Sakit Mata LEC dengan membuat pernyataan tentang kebutuhan privasi.
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">5.</td>
                        <td width="95%">
                            <strong>Keamanan barang berharga milik pasien</strong><br>
                            1. Bahwa rumah sakit telah menginformasikan agar tidak membawa barang-barang berharga seperti perhiasan, uang berlebihan, elektronik dll untuk menghindari terjadinya kehilangan/keamanan selama di rumah sakit.<br>
                            2. Bahwa rumah sakit mengijinkan keluarga berkunjung diluar jam kunjung sesuai dengan kebutuhannya, harus lapor dan menukarkan identitas dangan kartu identitas dengan kartu tamu (nametag tamu/pengunjung).<br>
                            3. Saya diinformasikan bahwa pasien yang tidak dapat menjaga barang miliknya misalnya pasien gawat darurat, tidak sadar, tindakan bedah rawat sehari, dan pasien yang menyatakan tidak mampu menjaga barang miliknya,
                            pasien tidak ada keluarganya maka dapat menitipkan kepada rumah sakit dengan mengisi formulir penitipan barang milik pasien.<br>
                            4. Pasien dan keluarga diwajibkan menjaga barang miliknya dan tidak meninggalkan barang tanpa ada yang menjaganya, kehilangan barang yang tidak dititipkan kepada rumah sakit adalah tanggung jawab pribadi.<br>
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">6.</td>
                        <td width="95%">
                            <strong>Second Opinion</strong>
                            Saya telah diinformasikan bahwa pasien/keluarga pasien berhak untuk meminta second opinion/ meminta pendapat
                            kedua dalam pelayanan medis terhadap dirinya/keluarga saya.
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">7.</td>
                        <td width="95%">
                            <strong>Informasi pelayanan dan fasilitas rumah sakit</strong>
                            Saya diinformasikan tentang jenis pelayanan di rumah sakit, fasilitas dan cara untuk mendapatkan pelayanan rawat
                            jalan, rawat inap dan pemeriksaan penunjang/ tindakan pengobatan di rumah sakit dapat diakses melalui leaflet/flyer
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">8.</td>
                        <td width="95%">
                            <strong>Pengajuan keluhan</strong><br>
                            a. Saya diinformasikan tata cara pengajuan keluhan dan tindak lanjut bila ada keluhan terkait pelayanan dan
                            pengobatan, keluhan dapat saya sampaikan kepada Unit Pelayanan Pengaduan atau dapat langsung kepada
                            petugas yang ada di pelayanan, melalui web, kotak saran dIl.<br>
                            b. Saya mengerti rumah sakit tidak wajib menindaklanjuti keluhan saya apabila tidak diajukan sesuai dengan
                            prosedur, dan tidak wajib bertanggungjawab atas setiap kerugian dalam bentuk apapun yang timbul dari keluhan
                            yang tidak diajukan sesuai prosedur.<br>
                        </td>   
                    </tr>
                    <tr>
                        <td width="5%">9.</td>
                        <td width="95%">
                            <strong>Pelayanan kerohanian dan nilai-nilai kepercayaan</strong><br>
                            a. Saya telah diinformasi tentang pelayanan kerohanian yang berada di rumah sakit sesuai dengan agama/
                            kepercayaan pasien dan cara pemberian bimbingan kerohanian disesuaikan dengan fasilitas di rumah sakit/
                            kebutuhan pasien.<br>
                            b. Jika saya menginginkan pelayanan sesuai dengan nilai - nilai kepercayaan saya, saya akan informasik an kepada
                            petugas di pelayanan.<br>
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">10.</td>
                        <td width="95%">
                            <strong>Informasi khusus asuransi BPJS</strong><br>
                            a. Saya telah diinformasikan bahwa apabila dalam waktu 3x24 jam/pasien pulang belum menyerahkan kartu
                            BPJS maka seluruh biaya perawatan/tindakan/pengobatan menjadi tanggung jawab pasien/ keluarga.<br>
                            b. Saya telah diiformasikan untuk membayar seluruh biaya perawatan/tindakan yang telah saya terima jika ternyata
                            kartu kepesertaan BPJS saya tidak terdaftar/belum aktif saat pembuatan Surat Eligibilitas Peserta (SEP) BPJS<br>
                            c. Bersedia menggunakan obat-obatan BPJS sesuai ketentuan Formularium Nasional (Fornas)<br>
                            d. Saya dengan ini memberikan kewenangan tanpa dapat dicabut kembali kepada RS Mata LEC untuk memberi
                            tagihan kepada asuransi terkait atas seluruh pelayanan dan tindakan kedokteran yang telah dilakukan<br>
                            e. Apabila di kemudian hari saya tidak lagi ditanggung oleh asuransi, maka saya atau penanggung jawab, wali,
                            ataupun keluarga saya dengan ini setuju untuk secara pribadi bertanggung jawab dalam membayar seluruh biaya
                            pelayanan dan tindakan kedokteran dari RS Mata LEC<br>
                            f. Pasien JKN/BPJS naik kelas atas permintaan sendiri (hanya boleh naik kelas satu tingkat dan selisih biaya
                            dibebankan kepada pasien/ keluarga. Pasien yang sudah naik kelas selama perawatan tidak diperkenankan untuk
                            pindah kelas lagi<br>
                            g. Dokter yang melayani pasien ditentukan rumah sakit, tidak diperkenankan memilih dokter sesuai den gan
                            peraturan rumah sakit.<br>
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">11.</td>
                        <td width="95%">
                            <strong>Kewajiban pembayaran</strong><br>
                            a. Dengan ini saya atau penanggung jawab, wali, ataupun keluarga saya telah dijelaskan dan menyatakan setuju
                            bahwa sesuai dengan pertimbangan pelayanan yang diberikan kepada saya, maka saya wajib untuk melakukan
                            pembayaran atas seluruh biaya pelayanan, yang akan ditentukan berdasarkan acuan biaya dan ketentuan yang
                            ditetapkan oleh RS Mata LEC.<br>
                            b. Apabila asuransi kesehatan swasta atau program pemerintah menanggung pembiayaan atas pelayanan kesehatan
                            terhadap saya, saya dengan ini memberikan kewenangan tanpa dapat dicabut kembali kepada RS Mata LEC
                            untuk memberi tagihan kepada asuransi terkait atas seluruh pelayanan dan tindakan kedokteran yang telah
                            dilakukan. Tanggungan asuransi dari saya mungkin akan menyatakan bahwa sebagian pembayaran tetap menjadi
                            tanggung jawab pribadi dari saya atau tidak ditanggung oleh asuransi tersebut, oleh karenanya RS Mata LEC
                            berhak untuk memberi tagihan atas biaya tagihan yang tidak ditanggung tersebut dan dengan ini saya atau
                            penanggung jawab, wali, ataupun keluarga saya setuju untuk bertanggung jawab membayar biaya tagihan
                            tersebut.<br>
                            c. Saya dengan ini juga memberikan persetujuan kepada RS Mata LEC untuk dapat memberikan rahasia kedokteran
                            atas rekam medis saya kepada perusahaan asuransi terkait sesuai dengan keperluan penagihan tersebut.<br>
                            d. Apabila di kemudian hari saya tidak lagi ditanggung oleh asuransi, maka saya atau penanggung jawab, wali,
                            ataupun keluarga saya dengan ini setuju untuk secara pribadi bertanggung jawab dalam membayar seluruh biaya
                            pelayanan dan tindakan kedokteran dari RS Mata LEС.<br>
                            e Saya telah diinformasikan bahwa penjaminan peserta dari awal sudah dipastikan menggunakan biaya
                            dan tidak dapat berubah menjadi BPJS setelah dilakukan registrasi rawat
                            inap, selama perawatan / tindakan/ setelah pasien pulang<br>
                        </td>
                    </tr>
                    <tr>
                        <td width="5%">12.</td>
                        <td width="95%">
                            <strong>Informasi Biaya</strong><br>
                            Saya telah dijelaskan tentang biaya sesuai dengan perencanaan :<br>
                            <strong>Rawat Jalan</strong><br>
                            a. Biaya rawat jalan Rp..... / konsultasi<br>
                            b. Biaya tindakan pasien umum/pribadi<br>
                            c. Biaya pemeriksaan penunjang, obat-obatan, lain-lain sesuai dengan kebutuhan<br>
                            d. Biaya jaminan BPJS sesuai dengan koding<br>
                            <strong>Rawat Inap</strong><br>
                            a. Kelas perawatan Rp /hari<br>
                            b. Biaya perkiraan tindakan pasien umum/ pribadi<br>
                            c. Jasa sarana klinis pasien umum/pribadi<br>
                            d. Biaya pemeriksaan penunjang, obat-obatan, lain-lain sesuai dengan kebutuhan<br>
                            e. Biaya jaminan BPJS sesuai dengan koding<br>
                            f. Biaya jaminan BPJS yang naik kelas perawatan<br>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="no-border-table">
                <tr>
                    <td><strong> TELAH DIJELASKAN, MEMBACA, MEMAHAMI dan SEPENUHNYA SETUJU </strong> pernyataan tersebut diatas</td>
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
                <tr><td>Tempat Pemeriksaan</td><td>:</td><td><strong>RSUD Pringsewu</strong></td></tr>
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
                $bulanIndo = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                $parts = explode('-', $tanggal);
                if (count($parts) == 3) {
                    // $parts[0] = tahun, $parts[1] = bulan, $parts[2] = hari
                    return intval($parts[2]) . ' ' . $bulanIndo[intval($parts[1])] . ' ' . $parts[0];
                }
                return $tanggal;
            }
            ?>            

            <div class="signature">
                <p><?php echo htmlspecialchars($kabupaten); ?>, <?php echo formatTanggalIndo($tgl_registrasi); ?> </p>
                <p>Penanggung Jawab Pasien</p>
                <br><br>
                <canvas id="signature-pad" width="350" height="120" style="border:1px solid #888; background:#fff;"></canvas>
                <br>
                <button type="button" onclick="signaturePad.clear()">Hapus Tanda Tangan</button>
                <button type="button" onclick="saveSignature()">Simpan Tanda Tangan</button>
                <br><br>
                <p><strong><u id="signed-name"><?php echo htmlspecialchars($p_jawab); ?></u></strong></p>                                
            </div>
        </div>       
    </div>    

    <script>
        // Simpan PDF ke server dengan render versi cetak utuh via Chrome headless
        document.getElementById('btnSavePdf').addEventListener('click', function () {
            const noRawat = (document.querySelector('input[name="no_rawat"]').value || '').trim();
            if (!noRawat) { alert('Masukkan nomor rawat terlebih dahulu.'); return; }
            const doSave = (overwrite) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'print_generalconsent_headless.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4) {
                        try {
                            const res = JSON.parse(xhr.responseText || '{}');
                            if (xhr.status === 200 && res.ok) {
                                alert('PDF tersimpan: ' + (res.file || ''));
                            } else if (res.code === 'exists') {
                                if (confirm('File sudah ada. Apakah Anda ingin mengganti (replace)?')) {
                                    doSave('1');
                                }
                            } else {
                                alert('Gagal menyimpan PDF: ' + (res.msg || ''));
                            }
                        } catch (e) {
                            alert('Gagal menyimpan PDF. Periksa server.');
                        }
                    }
                };
                xhr.send('no_rawat=' + encodeURIComponent(noRawat) + '&overwrite=' + encodeURIComponent(overwrite ? '1' : '0'));
            };
            doSave('0');
        });

        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas);

        // Tampilkan tanda tangan lama di canvas jika ada
        <?php if (!empty($signature_file)): ?>
        var img = new Image();
        img.onload = function() {
            signaturePad.clear();
            canvas.getContext("2d").drawImage(img, 0, 0, canvas.width, canvas.height);
        };
        img.src = "<?php echo $signature_file; ?>";
        <?php endif; ?>

        function saveSignature() {
            if (signaturePad.isEmpty()) {
                alert("Silakan tanda tangan dulu.");
                return;
            }
            var dataURL = signaturePad.toDataURL();
            var noRawat = document.querySelector('input[name="no_rawat"]').value;
            
            if (!noRawat) {
                alert("Silakan cari data pasien terlebih dahulu dengan nomor rawat.");
                return;
            }
            
            var now = new Date();
            var waktu = now.getFullYear().toString() +
                        ("0"+(now.getMonth()+1)).slice(-2) +
                        ("0"+now.getDate()).slice(-2) +
                        ("0"+now.getHours()).slice(-2) +
                        ("0"+now.getMinutes()).slice(-2) +
                        ("0"+now.getSeconds()).slice(-2);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "simpan_ttd.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        alert(xhr.responseText);
                        // Refresh halaman untuk menampilkan tanda tangan yang baru
                        location.reload();
                    } else {
                        alert("Error menyimpan tanda tangan.");
                    }
                }
            };
            xhr.send("img=" + encodeURIComponent(dataURL) + "&no_rawat=" + encodeURIComponent(noRawat) + "&waktu=" + waktu);
        }

        // Auto-resize canvas untuk tampilan yang lebih baik
        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear(); // Clear signature pad when resized
        }

        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();
    </script>
</body>
</html>