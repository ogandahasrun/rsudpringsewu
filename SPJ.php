<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ e-katalog</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Panggil file header.php -->
        <?php include 'header.php'; ?>

        <!-- Form Pencarian -->
        <div class="search-form">
            <form method="POST">
                Cari Berdasarkan Nomor Faktur : 
                <input type="text" name="no_faktur" required value="<?php echo isset($no_faktur) ? $no_faktur : ''; ?>">
                <button type="submit" name="filter">Cari</button>
            </form>
        </div>

        <!-- Konten Surat -->
        <div class="content">
            <h2 class="center-text">PERMOHONAN BELANJA BARANG/JASA (PPBJ)</h2>
            <style>
                .no-border-table {
                    border-collapse: collapse;
                    border: none;
                }
                .no-border-table td {
                    border: none;
                }
            </style>

            <table class="no-border-table">
                <tr><td>Ditujukan kepada Yth</td><td>:</td><td><strong>Kuasa Pengguna Anggaran (KPA) RSUD Pringsewu</strong></td></tr>
                <tr><td>Dari</td><td>:</td><td><strong>Pejabat Pelaksana Teknis Kegiatan</strong></td></tr>
                <tr><td>Tanggal</td><td>:</td><td>.........</td></tr>
                <tr><td>Nomor</td><td>:</td><td><strong>445 / ..........01/ PPBJ / LL.04 /...../ 2025</strong></td></tr>
                <tr><td>Program</td><td>:</td><td><strong>Peningkatan Mutu Pelayanan Kesehatan RSUD</strong></td></tr>
                <tr><td>Kegiatan</td><td>:</td><td><strong>Belanja Operasional BLUD</strong></td></tr>
                <tr><td>Kode Rekening</td><td>:</td><td><strong>5.1.02.99.99.9999</strong></td></tr>
            </table>

            <?php
            include 'koneksi.php';
            include 'functions.php';

            // Inisialisasi variabel nomor faktur
            $no_faktur = "";

            // Proses filter jika tombol "Filter" diklik
            if (isset($_POST['filter'])) {
                $no_faktur = $_POST['no_faktur'];
                
                // Validasi nomor faktur
                if (empty($no_faktur)) {
                    echo "<p style='color: red;'>Masukkan nomor faktur</p>";
                } else {
                    // Query dengan filter nomor faktur 
                    $query = "SELECT
                                pemesanan.no_order,
                                pemesanan.tgl_pesan,
                                pemesanan.kode_suplier,
                                pemesanan.no_faktur,
                                pemesanan.tgl_faktur,
                                pemesanan.total2,
                                pemesanan.ppn,
                                pemesanan.tagihan,                                
                                databarang.nama_brng AS nama_brng,
                                detailpesan.jumlah AS jumlah,
                                kodesatuan.satuan AS satuan,
                                detailpesan.h_pesan AS h_pesan,
                                detailpesan.total AS total,
                                datasuplier.nama_suplier,
                                datasuplier.direktur,
                                datasuplier.alamat,
                                datasuplier.kota,
                                datasuplier.jabatan,
                                datasuplier.NPWP
                            FROM
                                pemesanan
                                JOIN detailpesan ON ((detailpesan.no_faktur = pemesanan.no_faktur))
                                JOIN databarang ON ((detailpesan.kode_brng = databarang.kode_brng))
                                JOIN datasuplier ON ((pemesanan.kode_suplier = datasuplier.kode_suplier))
                                JOIN kodesatuan ON (((detailpesan.kode_sat = kodesatuan.kode_sat) AND (databarang.kode_sat = kodesatuan.kode_sat)))                                
                            WHERE
                                pemesanan.no_faktur = ?";
                    
                    $stmt = mysqli_prepare($koneksi, $query);
                    mysqli_stmt_bind_param($stmt, "s", $no_faktur);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if (!$result) {
                        die("Query error: " . mysqli_error($koneksi));
                    }

                    // Ambil data suplier
                    $query_suplier = "SELECT * FROM datasuplier WHERE kode_suplier = (SELECT kode_suplier FROM pemesanan WHERE no_faktur = ?)";
                    $stmt_suplier = mysqli_prepare($koneksi, $query_suplier);
                    mysqli_stmt_bind_param($stmt_suplier, "s", $no_faktur);
                    mysqli_stmt_execute($stmt_suplier);
                    $result_suplier = mysqli_stmt_get_result($stmt_suplier);
                    $datasuplier = mysqli_fetch_assoc($result_suplier);
                }
            }
            ?>

            <!-- Tabel Daftar Barang -->
            <table>
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Nama Barang</th>
                        <th>Volume</th>
                        <th>Satuan</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php include 'table_body.php'; ?>
                </tbody>
            </table>

            <?php
            // Menampilkan terbilang dari total akhir
            if (isset($total_akhir)) {
                $terbilang_total = terbilang($total_akhir);
                echo "<p>Terbilang: <strong><em>" . ucfirst($terbilang_total) . " rupiah</em></strong></p>";
            }
            ?>
            
            <table class="no-border-table">
                <tr><td>Demikian kami sampaikan mohon segera ditindaklanjuti, atas perhatian dan kerjasamanya diucapkan terima kasih</td></tr>    
            </table>

            <div class="signature" style="text-align: center;">
                <p>Pejabat Pelaksana Teknis Kegiatan</p>
                <p>Belanja bahan Habis Pakai (BAHP) Rumah Sakit</p>
                <br>
                <br>
                <p><strong><u>dr. Triyani Rositasari</u></strong></p>
                <p>NIP. 19830619 201101 2 005</p>
            </div>
        </div>

        <!-- Tombol untuk preview cetak -->
        <div class="print-button">
            <button onclick="window.print()">Preview Cetak</button>
        </div>
    </div>

    <table class="half-width-table">
        <thead>
            <tr><th colspan="2">Paraf Koordinasi</th></tr>
        </thead>
        <tbody>
            <tr><td>Ka. Bid. Perencanaan & Keuangan</td><td class="signature-space"></td></tr>
            <tr><td>Ka. Sie. Perencanaan & Pengembangan</td><td class="signature-space"></td></tr>
            <tr><td>Ka. Sie. Keuangan</td><td class="signature-space"></td></tr>
        </tbody>
    </table>
</body>
</html>

<!------------------------------ BATAS HALAMAN 1  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 2</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .page-break {
            page-break-before: always;
        }
        .no-border-table {
            border-collapse: collapse;
            border: none;
        }
        .no-border-table td {
            border: none;
        }        
    </style>
</head>
<body>
    <div class="container">
        <!-- Halaman Kedua -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <style>
                .no-border-table {
                    border-collapse: collapse;
                    border: none;
                }
                .no-border-table td {
                    border: none;
                }
            </style>

            <table class="no-border-table">
                <tr><td>Ditujukan kepada Yth</td><td>:</td><td><strong>Pejabat Pengadaan Obat/BMHP E-Katalog/Non Katalog</strong></td></tr>
                <tr><td>Dari</td><td>:</td><td><strong>Pejabat Pembuat Komitmen</strong></td></tr>
                <tr><td>Tanggal</td><td>:</td><td>.........</td></tr>
                <tr><td>Nomor</td><td>:</td><td><strong>445 / ..........01/ PPBJ / LL.04 /...../ 2025</strong></td></tr>
                <tr><td>Perihal</td><td>:</td><td><strong>Pengadaan e-Purchasing</strong></td></tr>    
            </table>

            <table class="no-border-table">
                <tr><td>Dengan hormat,</td></tr>    
            </table>

            <table class="no-border-table">
                <tr><td>A.</td><td>Dasar</td></tr>
                <tr><td> </td><td>1. Peraturan Presiden Nomor 12 Tahun 2021 tentang Pengadaan Barang/Jasa Pemerintah</td></tr>
                <tr><td> </td><td>2. Peraturan Bupati Nomor 17 Tahun 2018 tentang Jenjang Nilai Pengadaan Barang dan Jasa pada Unit
                Pelayanan Umum Daerah Rumah Sakit Umum Daerah Pringsewu.</td></tr>
                <tr><td> </td><td>3. Surat permintaan Pengadaan Barang/Jasa Nomor : 445/ .01/LL.04/2024 tanggal ... </td></tr>
                <tr><td> </td></tr>
                <tr><td>B.</td><td>Menugaskan</td></tr>
                <tr><td> </td><td>Nama : Wisnetty, S.Si., Apt., M. Kes</td></tr>
                <tr><td> </td><td>NIP : 19701020 200002 2002</td></tr>
                <tr><td> </td><td>Jabatan : Pejabat Pengadaan Barang dan Jasa</td></tr>
            </table>

            <table class="no-border-table">
                <tr><td>Untuk melaksanakan kegiatan berikut dengan metode Pengadaan e-Purchasing, Spesifikasi Teknis dan HPS sebagai berikut :</td></tr>    
            </table>

            <!-- Tabel Detail Barang -->
            <table>
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Nama Barang</th>
                        <th>Volume</th>
                        <th>Satuan</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php include 'table_body.php'; ?>
                </tbody>
            </table>

            <?php
            // Menampilkan terbilang dari total akhir
            if (isset($total_akhir)) {
                $terbilang_total = terbilang($total_akhir);
                echo "<p>Terbilang: <strong><em>" . ucfirst($terbilang_total) . " rupiah</em></strong></p>";
            }
            ?>

            <!-- Tanda Tangan -->
            <div class="signature" style="text-align: center;">
                <p>Pejabat Pembuat Komitmen</p>                
                <br>
                <br>
                <p><strong><u>dr. ANDI ARMAN, Sp.PD</u></strong></p>
                <p>NIP. 19780801 200501 1 009</p>
            </div>   
        </div>
    </div>
</body>
</html>

<!------------------------------ BATAS HALAMAN 2  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 3</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .page-break {
            page-break-before: always;
        }
        .no-border-table {
            border-collapse: collapse;
            border: none;
        }
        .no-border-table td {
            border: none;
        }        
    </style>
</head>
<body>
    <div class="container">
        <!-- Halaman Ketiga -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <style>
                .no-border-table {
                    border-collapse: collapse;
                    border: none;
                }
                .no-border-table td {
                    border: none;
                }
            </style>

            <table class="no-border-table">
                <tr><td>Ditujukan kepada Yth</td><td>:</td><td><strong>Pejabat Pembuat Komitmen</strong></td></tr>
                <tr><td>Dari</td><td>:</td><td><strong>Pejabat Pengadaan Obat/BMHP E-Katalog/Non Katalog</strong></td></tr>
                <tr><td>Tanggal</td><td>:</td><td>.........</td></tr>
                <tr><td>Nomor</td><td>:</td><td><strong>445 / ..........01/ PPBJ / LL.04 /...../ 2025</strong></td></tr>
                <tr><td>Perihal</td><td>:</td><td><strong>Penyampaian Hasil Pengadaan e-Purchasing</strong></td></tr>    
            </table>

            <table class="no-border-table">
                <tr><td>Bersama ini kami sampaikan hasil Penggadaan e-Purchasing Paket Pekerjaan Belanja Bahan habis pakai sebagai dasar
                penerbitan Surat Permintaan dengan rincian sebagai berikut :</td></tr>    
            </table>

            <table class="no-border-table">
                <tr><td>A.</td><td>Dasar</td></tr>
                <tr><td> </td><td>1. Peraturan Presiden Nomor 12 Tahun 2021 tentang Pengadaan Barang/Jasa Pemerintah</td></tr>
                <tr><td> </td><td>2. Peraturan Bupati Nomor 17 Tahun 2018 tentang Jenjang Nilai Pengadaan Barang dan Jasa pada Unit
                Pelayanan Umum Daerah Rumah Sakit Umum Daerah Pringsewu.</td></tr>
                <tr><td> </td><td>3. Surat permintaan Pengadaan Barang/Jasa Nomor : 445/      .01/LL.04/2024 tanggal ... </td></tr>
                <tr><td> </td></tr>
                <tr><td>B.</td><td>Penyedia</td></tr>
                <tr><td> </td><td>1. Nama Paket Pekerjaan : Belanja bahan habis Pakai (BAHP) RSUD</td></tr>
                <tr><td> </td><td>2. Nama Penyedia : <?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></td></tr>
                <tr><td> </td><td>3. Direktur : <?php echo isset($datasuplier['direktur']) ? $datasuplier['direktur'] : ''; ?></td></tr>
                <tr><td> </td><td>4. Alamat Penyedia : <?php echo isset($datasuplier['alamat']) ? $datasuplier['alamat'] : ''; ?></td></tr>
                <tr><td> </td><td>5. NPWP : <?php echo isset($datasuplier['NPWP']) ? $datasuplier['NPWP'] : ''; ?></td></tr>
                <tr><td>C.</td><td>Rincian sebagai berikut :</td></tr>
            </table>

            <!-- Tabel Detail Barang -->
            <table>
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Nama Barang</th>
                        <th>Volume</th>
                        <th>Satuan</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php include 'table_body.php'; ?>
                </tbody>
            </table>

            <?php
            // Menampilkan terbilang dari total akhir
            if (isset($total_akhir)) {
                $terbilang_total = terbilang($total_akhir);
                echo "<p>Terbilang: <strong><em>" . ucfirst($terbilang_total) . " rupiah</em></strong></p>";
            }
            ?>

            <table class="no-border-table">
                <tr><td>Demikian atas perhatian dan kerjasamanya diucapkan terima kasih</td></tr>    
            </table>

            <!-- Tanda Tangan -->
            <div class="signature" style="text-align: center;">
                <p>Pejabat Pengadaan Obat/ BMHP E-Katalog/Non E-Katalog</p>                
                <br>
                <br>
                <p><strong><u>Wisnetty, S.Si., Apt., M. Kes</u></strong></p>
                <p>NIP. 19701020 200002 2002</p>
            </div>   
        </div>
    </div>
</body>
</html>

<!------------------------------ BATAS HALAMAN 3  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 3</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .page-break {
            page-break-before: always;
        }
        .no-border-table {
            border-collapse: collapse;
            border: none;
        }
        .no-border-table td {
            border: none;
        }        
    </style>
</head>
<body>
    <div class="container">
        <!-- Halaman Ketiga -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <h2 class="center-text">SURAT PESANAN</h2>
            <h4 class="center-nomorsurat">Nomor Surat : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/SP/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/LL.04/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025</h4>

            <style>
                .no-border-table {
                    border-collapse: collapse;
                    border: none;
                }
                .no-border-table td {
                    border: none;
                }
            </style>

            <table class="no-border-table">
                <tr><td>yang bertanda tangan di bawah ini :</td></tr>
                <tr><td>Nama : dr. Andi Arman, Sp.PD</td></tr>
                <tr><td>NIP : 19780801 200501 1 009</td></tr>
                <tr><td>Jabatan : Pejabat Pembuat Komitmen BLUD Pringsewu</td></tr>
                <tr><td>&nbsp;</td></tr>
                <tr><td>Dalam hal ini bertindak untuk dan atas nama RSUD Pringsewu, yang Selanjutnya disebut sebagai Pejabat Pembuat Komitmen (PPK);</td></tr>
                <tr><td>Berdasarkan Laporan Hasil Pengadaan e Purchasing</td></tr>
                <tr><td>Nomor : 445 / &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;01/ PPBJ / LL.04 /&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/ 2025</td></tr>
                <tr><td>Tanggal</td><td>:</td><td></td></tr>    
                <tr><td>bersama ini memerintahkan kepada :</td></tr>    
            </table>

            <table class="no-border-table">        
                <tr><td>Penyedia</td></tr>
                <tr><td>Nama Paket Pekerjaan : Belanja bahan habis Pakai (BAHP) RSUD</td></tr>
                <tr><td>Nama Penyedia : <?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></td></tr>
                <tr><td>Direktur : <?php echo isset($datasuplier['direktur']) ? $datasuplier['direktur'] : ''; ?></td></tr>
                <tr><td>Alamat Penyedia : <?php echo isset($datasuplier['alamat']) ? $datasuplier['alamat'] : ''; ?></td></tr>
                <tr><td>NPWP : <?php echo isset($datasuplier['NPWP']) ? $datasuplier['NPWP'] : ''; ?></td></tr>
                <tr><td>Nomor e Purchasing : </td></tr>
                <tr><td>Untuk mengirimkan barang/jasa dengan ketentuan-ketentuan sebagai berikut :</td></tr>
                <tr><td>1. Rincian Barang</td></tr>
            </table>

            <!-- Tabel Detail Barang -->
            <table>
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Nama Barang</th>
                        <th>Volume</th>
                        <th>Satuan</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php include 'table_body.php'; ?>
                </tbody>
            </table>

            <?php
            // Menampilkan terbilang dari total akhir
            if (isset($total_akhir)) {
                $terbilang_total = terbilang($total_akhir);
                echo "<p>Terbilang: <strong><em>" . ucfirst($terbilang_total) . " rupiah</em></strong></p>";
            }
            ?>

            <table class="no-border-table">
                <tr><td>2. Waktu penyelesaian pekerjaan</td><td>: 28 (dua puluh delapan) hari kalender terhitung mulai tanggal Surat
                Perintah Kerja ini</td></tr>
                <tr><td>3. Alamat pelaksanaan pekerjaan</td><td>: Jl. Lintas Barat Pekon Fajar Agung Barat Kecamatan Pringsewu</td></tr>
                <tr><td>4. Pembayaran</td><td>: Pembayaran dilakukan dengan cara transfer</td></tr>
                <tr><td>5. Denda</td><td>: Terhadap setiap hari keterlambatan penyelesaian pekerjaan Penyedia
                Barang/Jasa akan dikenakan Denda Keterlambatan sebesar 1/1000</td></tr>
            </table>

            <!-- Tanda Tangan -->
            <div class="signature" style="text-align: center;">
                <p>Pejabat Pengadaan Obat/ BMHP E-Katalog/Non E-Katalog</p>                
                <br>
                <br>
                <p><strong><u>Wisnetty, S.Si., Apt., M. Kes</u></strong></p>
                <p>NIP. 19701020 200002 2002</p>
            </div>   
        </div>
    </div>
</body>
</html>

<!------------------------------ BATAS HALAMAN 4  ------------------------------->

