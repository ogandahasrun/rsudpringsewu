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

        <!-- Tombol untuk preview cetak -->
        <div class="print-button">
            <button onclick="window.print()">Preview Cetak</button>
        </div>

        <!-- Konten Surat -->
        <div class="content">
            <h4 class="center-text">PERMOHONAN BELANJA BARANG/JASA (PPBJ)</h4>

            <table class="no-border-table">
                <tr><td>Ditujukan kepada Yth</td><td>:</td><td>Kuasa Pengguna Anggaran (KPA) RSUD Pringsewu</td></tr>
                <tr><td>Dari</td><td>:</td><td>Pejabat Pelaksana Teknis Kegiatan</td></tr>
                <tr><td>Tanggal</td><td>:</td><td>.........</td></tr>
                <tr><td>Nomor</td><td>:</td><td>445 / ..........01/ PPBJ / LL.04 /...../ 2025</td></tr>
                <tr><td>Program</td><td>:</td><td>Peningkatan Mutu Pelayanan Kesehatan RSUD</td></tr>
                <tr><td>Kegiatan</td><td>:</td><td>Belanja Operasional BLUD</td></tr>
                <tr><td>Kode Rekening</td><td>:</td><td>5.1.02.99.99.9999</td></tr>
            </table>

            <table class="no-border-table">
                <tr><td> Dengan hormat,</td></tr>
                <tr><td>Berikut kami sampaikan permintaan pengadaan Belanja Bahan Habis Pakai dari Pengguna atau user untuk Operasional
                Pelayanan Rumah Sakit</td></tr>    
            </table>

            <?php
            include 'koneksi.php';
            include 'functions.php';

            // Inisialisasi variabel nomor faktur
            $no_faktur = "";
            $pemesanan = [];
            $datasuplier = [];

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

                    // Ambil data pemesanan
                    $pemesanan = mysqli_fetch_assoc($result);

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
        </div>       
    </div>    
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
    
</head>
<body>
    <div class="container">
        <!-- Halaman Kedua -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <table class="no-border-table">
                <tr><td>Ditujukan kepada Yth</td><td>:</td><td>Pejabat Pengadaan Obat/BMHP E-Katalog/Non Katalog</td></tr>
                <tr><td>Dari</td><td>:</td><td>Pejabat Pembuat Komitmen</td></tr>
                <tr><td>Tanggal</td><td>:</td><td>.........</td></tr>
                <tr><td>Nomor</td><td>:</td><td>445 /<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/ PPBJ / LL.04 /...../ 2025</td></tr>
                <tr><td>Perihal</td><td>:</td><td>Pengadaan Langsung</td></tr>    
            </table>

            <table class="no-border-table">
                <tr><td>Dengan hormat,</td></tr>    
            </table>
            <table class="no-border-table">
                <tr><td>A.</td><td>Dasar</td></tr>
                <tr><td> </td><td>1. Peraturan Presiden Nomor 12 Tahun 2021 tentang Pengadaan Barang/Jasa Pemerintah</td></tr>
                <tr><td> </td><td>2. Peraturan Bupati Nomor 17 Tahun 2018 tentang Jenjang Nilai Pengadaan Barang dan Jasa pada Unit Pelayanan Umum Daerah </td></tr>
                <tr><td> </td><td>&nbsp;&nbsp;&nbsp;&nbsp;Rumah Sakit Umum Daerah Pringsewu.</td></tr>
                <tr><td> </td><td>3. Surat permintaan Pengadaan Barang/Jasa Nomor : 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/LL.04/2025 tanggal ... </td></tr>
            </table>
            <table class="no-border-table">
                <tr><td>B.</td><td>Menugaskan</td><td>:</td><td></td></tr>
                <tr><td> </td><td>Nama</td><td>:</td><td>Wisnetty, S.Si., Apt., M. Kes</td></tr>
                <tr><td> </td><td>NIP</td><td>:</td><td>19701020 200002 2002</td></tr>
                <tr><td> </td><td>Jabatan</td><td>:</td><td>Pejabat Pengadaan Barang dan Jasa</td></tr>
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
                <p><strong><u>dr. Herman Syahrial</u></strong></p>
                <p>NIP. 19690927 200212 1 003</p>
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
    <title>SPJ Halaman 2a</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <!-- Halaman Kesepuluh -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <table class="no-border-table">
                <tr><td>Nomor</td><td>:</td><td>445 /<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/PIBH/LL.04/...../2025</td></tr>
                <tr><td>Lampiran</td><td>:</td><td>-</td></tr>
                <tr><td>Perihal</td><td>:</td><td> Permohonan Informasi Barang dan Harga</td></tr>    
            </table>

            <table class="no-border-table">        
                <tr><td>Kepada Yth</td></tr>
                <tr><td><?php echo isset($datasuplier['jabatan']) ? $datasuplier['jabatan'] : ''; ?>
                <?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?>
                </td></tr>
            </table>



            <table class="no-border-table">        
                <tr><td> Dengan ini kami memohon informasi harga barang untuk paket pekerjaan sebagai berikut :</td></tr>
            </table>
            <table class="no-border-table">        
                <tr><td>1.</td><td>Paket Pekerjaan</td><td>: Belanja Bahan Alat Habis Pakai (BAHP) Rumah Sakit</td></tr>
                <tr><td></td><td>Nama Paket Pekerjaan</td><td>: Belanja Bahan Alat Habis Pakai (BAHP) Rumah Sakit</td></tr>
                <tr><td></td><td>Sumber Pendanaan</td><td>: DPA-BLUD Tahun Anggaran 2025</td></tr>
                <tr><td>2.</td><td>Rincian Barang</td><td>: </td></tr>
            </table>            

            <!-- Tabel Detail Barang -->
            <table>
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Nama Barang</th>
                        <th>Volume</th>
                        <th>Satuan</th>
                <!--    <th>Lengkap</th>
                        <th>Tidak Lengkap</th>
                        <th>Ket</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if (isset($result) && mysqli_num_rows($result) > 0) {
                            $nomor_urut = 1;
                            $total_keseluruhan = 0;

                            mysqli_data_seek($result, 0); // Reset pointer result set ke awal

                            while ($row = mysqli_fetch_assoc($result)) {
                                $total = $row['jumlah'] * $row['h_pesan'];
                                $total_keseluruhan += $total;

                                echo "<tr>";
                                echo "<td>" . $nomor_urut . "</td>";
                                echo "<td>" . $row['nama_brng'] . "</td>";
                                echo "<td class='text-right'>" . number_format($row['jumlah'], 0, ',', '.') . "</td>";
                                echo "<td class='center'>" . $row['satuan'] . "</td>";
                                echo "</tr>";

                                $nomor_urut++;
                            }
                        } else {
                            if (isset($_POST['filter'])) {
                                echo "<tr><td colspan='6'>Tidak ada data yang ditemukan untuk nomor faktur: $no_faktur</td></tr>";
                            }
                        }
                    ?>
                </tbody>
            </table>

            <table class="no-border-table">        
                <tr><td>Saudara diminta memberikan informasi barang dan harga selambat-lambatnya 3 (hari) kerja sejak surat ini diterima dan
                dilanjutkan dengan negosiasi untuk mencapai kesepakatan harga.</td></tr>
                <tr><td>Demikian atas kerjasamanya kami ucapkan terima kasih.</td></tr>
            </table>

            <!-- Tanda Tangan -->
            <div class="signature" style="text-align: center;">
                <p>Pringsewu, ...................................... 2025</p>
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


<!------------------------------ BATAS HALAMAN 2a  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 3</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <!-- Halaman Ketiga -->
        <div class="page-break">
            
            <table class="no-border-table">
                <tr><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td></tr>
            </table>

            <table class="no-border-table">
                <tr><td>Kepada Yth,</td></tr>
                <tr><td>Pejabat Pengadaan Obat/BMHP E-Katalog/Non E-Katalog RSUD Pringsewu</td></tr>
                <tr><td>di - </td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;RSUD Pringsewu</td></tr>
            </table>

            <table class="no-border-table">
                <tr><td>Perihal : Informasi Barang dan Harga</td></tr>    
            </table>

            <table class="no-border-table">
                <tr><td>Sehubungan dengan Surat Permintaan dan Informasi Barang dan Harga :</td></tr>    
            </table>

            <table class="no-border-table">
                <tr><td>Nomor</td><td>: 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/PIBH/LL.04/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/ 2025</td></tr>
                <tr><td>Tanggal</td><td>:</td></tr>
                <tr><td>Paket Pekerjaan</td><td>: Belanja Bahan Alat Habis Pakai (BAHP) Rumah Sakit</td></tr>
                <tr><td>Nama Paket Pekerjaan</td><td>: Belanja Bahan Alat Habis Pakai (BAHP) Rumah Sakit</td></tr>
                <tr><td>Lokasi</td><td>: RSUD Pringsewu</td></tr>
                <tr><td>Sumber Dana</td><td>: DPA-BLUD RSUD Pringsewu Tahun Anggaran 2025</td></tr>
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
                <p><?php echo isset($datasuplier['kota']) ? $datasuplier['kota'] : ''; ?>, ....................................... 2025</p>
                <p><?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></p>
                <br>
                <br>
                <br>
                <p><strong><u><?php echo isset($datasuplier['direktur']) ? $datasuplier['direktur'] : ''; ?></u></strong></p>
                <p><?php echo isset($datasuplier['jabatan']) ? $datasuplier['jabatan'] : ''; ?></p>
            </div>   
        </div>
    </div>
</body>
</html>


<!------------------------------ BATAS HALAMAN 2b  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 3</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <!-- Halaman Ketiga -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>
            
            <table class="no-border-table">
                <tr><td>Nomor</td><td>:</td><td>445 /<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/ PPBJ / LL.04 /...../ 2025</td></tr>
                <tr><td>Lampiran</td><td>:</td><td>-</td></tr>    
                <tr><td>Perihal</td><td>:</td><td>Penyampaian Hasil Pengadaan Langsung</td></tr>    
            </table>

            <table class="no-border-table">
                <tr><td>Kepada Yth,</td></tr>
                <tr><td>Pejabat Pembuat Komitmen</td></tr>
                <tr><td>di - </td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;RSUD Pringsewu</td></tr>
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
                <tr><td> </td><td>3. Surat permintaan pengadaan langsung Nomor : 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/LL.04/2025 tanggal ... </td></tr>
                <tr><td> </td></tr>
                <tr><td>B.</td><td>Penyedia</td></tr>
                <tr><td> </td><td>1. Nama Paket Pekerjaan : Belanja bahan habis Pakai (BAHP) RSUD</td></tr>
                <tr><td> </td><td>2. HPS : <?php if (isset($total_akhir)) {echo "Rp. " . number_format($total_akhir, 0, ',', '.') . " ";} ?></td></tr>
                <tr><td> </td><td>3. Nama Penyedia : <?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></td></tr>
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
                <p>Pringsewu, ............................. 2025</p>
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

</head>
<body>
    <div class="container">
        <!-- Halaman Ketiga -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <h4 class="center-text">SURAT PERINTAH KERJA</h4>
            <h4 class="center-nomorsurat">Nomor Surat : <?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>/SP/
                <?php echo isset($pemesanan['kode_suplier']) ? $pemesanan['kode_suplier'] : ''; ?>/LL.04/
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025</h4>

            <table class="no-border-table">        
                <tr><td>Pada hari ini &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
                    bulan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tahun Dua Ribu Dua Puluh Lima (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025),</td></tr>
            </table>            

            <table class="no-border-table">
                <tr><td>yang bertanda tangan di bawah ini :</td></tr>
            </table>
            <table class="no-border-table">        
                <tr><td>Nama</td><td>:</td><td>dr. Herman Syahrial</td></tr>
                <tr><td>NIP</td><td>:</td><td>19690927 200212 1 003</td></tr>
                <tr><td>Jabatan</td><td>:</td><td>Pejabat Pembuat Komitmen BLUD Pringsewu</td></tr>
            </table>
            <table class="no-border-table">        
                <tr><td>Dalam hal ini bertindak untuk dan atas nama RSUD Pringsewu, yang Selanjutnya disebut sebagai Pejabat Pembuat Komitmen (PPK);</td></tr>
                <tr><td>Berdasarkan Laporan Hasil Pengadaan Langsung</td></tr>
                <tr><td>Nomor : 445 / <?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/ PPBJ / LL.04 /&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/ 2025</td></tr>
                <tr><td>Tanggal :</td></tr>    
                <tr><td>bersama ini memerintahkan kepada :</td></tr>    
            </table>
            <table class="no-border-table">        
                <tr><td>Penyedia</td></tr>
                <tr><td>Nama Paket Pekerjaan</td><td>:</td><td>Belanja bahan habis Pakai (BAHP) RSUD</td></tr>
                <tr><td>Nama Penyedia</td><td>:</td><td><?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></td></tr>
                <tr><td>Yang diwakili oleh</td><td>:</td><td><?php echo isset($datasuplier['direktur']) ? $datasuplier['direktur'] : ''; ?></td></tr>
                <tr><td>Jabatan</td><td>:</td><td><?php echo isset($datasuplier['jabatan']) ? $datasuplier['jabatan'] : ''; ?></td></tr>
                <tr><td>Alamat Penyedia</td><td>:</td><td><?php echo isset($datasuplier['alamat']) ? $datasuplier['alamat'] : ''; ?></td></tr>
                <tr><td>NPWP</td><td>:</td><td><?php echo isset($datasuplier['NPWP']) ? $datasuplier['NPWP'] : ''; ?></td></tr>
                <tr><td>Nomor e Purchasing</td><td>:</td><td></td></tr>
            </table>
            <table class="no-border-table">        
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

            <table class="no-border-table" style="text-align: center;">
                <tr><td></td><td>Untuk dan atas nama</td><td></td><td>Untuk dan atas nama</td></tr>
                <tr><td></td><td>Pejabat Pembuat Komitmen</td><td></td><td>Penyedia barang</td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td></td><td><strong><u>dr. Herman Syahrial</u></strong></td><td></td><td><strong><u><?php echo isset($datasuplier['direktur']) ? $datasuplier['direktur'] : ''; ?></u></strong></td></tr>
                <tr><td></td><td>NIP. 19690927 200212 1 003</td><td></td><td><?php echo isset($datasuplier['jabatan']) ? $datasuplier['jabatan'] : ''; ?></td></tr>
            </table>
        </div>
    </div>
</body>
</html>

<!------------------------------ BATAS HALAMAN 4  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 3</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <!-- Halaman Kelima -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <h2 class="center-text">BERITA ACARA SERAH TERIMA PEKERJAAN</h2>
            <h4 class="center-nomorsurat">Nomor Surat : 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/BASTP/LL.04/
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025</h4>

            <table class="no-border-table">        
                <tr><td>Pada hari ini &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
                    bulan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tahun Dua Ribu Dua Puluh Lima (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025),</td></tr>
            </table>            

            <table class="no-border-table">        
                <tr><td></td><td>Yang bertanda tangan di bawah ini </td><td>:</td></tr>
                <tr><td>1&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Nama Penyedia </td><td>: <?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></td></tr>
                <tr><td></td><td>Alamat Penyedia </td><td>: <?php echo isset($datasuplier['alamat']) ? $datasuplier['alamat'] : ''; ?></td></tr>
                <tr><td></td><td>yang diwakili oleh </td><td>: </td></tr>
                <tr><td></td><td>Nama </td><td>: <?php echo isset($datasuplier['direktur']) ? $datasuplier['direktur'] : ''; ?></td></tr>
                <tr><td></td><td>Jabatan </td><td>: <?php echo isset($datasuplier['jabatan']) ? $datasuplier['jabatan'] : ''; ?></td></tr>
                <tr><td></td><td>Dalam hal ini bertindak untuk dan atas nama </td><td>: <?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></td></tr>
                <tr><td></td><td>Selanjutnya disebut sebagai Penyedia Barang/Jasa<td>:</td></td></tr>
                <tr><td></td></tr>
                <tr><td>2</td><td>Nama</td><td>: dr. Herman Syahrial</td></tr>
                <tr><td></td><td>NIP</td><td>: 19690927 200212 1 003</td></tr>
                <tr><td></td><td>Jabatan</td><td>: Kuasa Pengguna Anggaran</td></tr>
                <tr><td></td><td>Dalam hal ini bertindak untuk dan atas nama RSUD Pringsewu, </td><td></td></tr>
                <tr><td></td><td>yang Selanjutnya disebut sebagai Pejabat Pembuat Komitmen (PPK);</td><td></td></tr>
            </table>            

            <?php echo ""; ?>

            <table class="no-border-table">        
                <tr><td>Berdasarkan Surat Pesanan Barang dan Jasa Nomor : <?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/SP/ /LL.04/ /2025 untuk paket pekerjaan 
                        belanja Bahan Habis Pakai dengan ini menerangkan bahwa :</td></tr>
            </table>   


            <table class="no-border-table">        
                <tr><td>1&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Penyedia Barang/Jasa telah menyelesaikan dan telah menyerahkan hasil pekerjaan tersebut sesuai dengan ketentuan
                Surat Perintah Kerja (SPK) kepada PPK</td></tr>
                <tr><td>2</td><td>PPK telah menerima hasil pekerjaan tersebut dan menyatakan hasil pekerjaan tersebut telah sesuai dengan
                ketentuan Surat Perintah Kerja (SPK) Barang dan Jasa, dengan rincian sebagai berikut :</td></tr>
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

<?php echo "Demikian Berita Acara ini dibuat dalam rangkap 5 (lima) untuk dapat dipergunakan sebagaimana mestinya"; ?>


            <table class="no-border-table" style="text-align: center;">
                <tr><td></td><td>Pejabat Pembuat Komitmen</td><td></td><td>Penyedia barang</td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td></td><td><strong><u>dr. Herman Syahrial</u></strong></td><td></td><td><strong><u><?php echo isset($datasuplier['direktur']) ? $datasuplier['direktur'] : ''; ?></u></strong></td></tr>
                <tr><td></td><td>NIP. 19690927 200212 1 003</td><td></td><td><?php echo isset($datasuplier['jabatan']) ? $datasuplier['jabatan'] : ''; ?></td></tr>
            </table>
        </div>
    </div>
</body>
</html>

<!------------------------------ BATAS HALAMAN 5  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 6</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <!-- Halaman Keenam -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <h2 class="center-text">BERITA ACARA SERAH TERIMA BARANG/JASA</h2>
            <h4 class="center-nomorsurat">Nomor Surat : 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>/BASTB/LL.04/
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025</h4>

            <table class="no-border-table">        
                <tr><td>Pada hari ini &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
                    bulan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tahun Dua Ribu Dua Puluh Lima (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025),</td></tr>
            </table>            

            <table class="no-border-table">        
                <tr><td></td><td>Yang bertanda tangan di bawah ini </td><td>:</td></tr>
                <tr><td>1&nbsp;&nbsp;&nbsp;</td><td>Nama</td><td>: dr. Herman Syahrial</td></tr>
                <tr><td></td><td>NIP</td><td>: 19690927 200212 1 003</td></tr>
                <tr><td></td><td>Jabatan</td><td>: Pejabat Pembuat Komitmen (PPK)</td></tr>
                <tr><td></td><td>yang Selanjutnya disebut sebagai Pihak I;</td></tr>
                <tr><td>2</td><td>Nama  </td><td>: dr. Triyani Rositasari</td></tr>
                <tr><td></td><td>NIP </td><td>: 19830619 201101 2 005</td></tr>
                <tr><td></td><td>Jabatan </td><td>: Pejabat Pelaksana Teknis Kegiatan (PPTK)</td></tr>
                <tr><td></td><td>yang Selanjutnya disebut sebagai Pihak II;</td><td></td></tr>
            </table>            

            <table class="no-border-table">        
                <tr><td>Dengan ini menerangkan bahwa :</td></tr>
            </table>   

            <table class="no-border-table">        
                <tr><td>1&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Pihak I telah menyerahkan barang dan jasa sesuai dengan Permohonan Pengadaan Barang dan Jasa (PPBJ) </td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Nomor : 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/PPBJ/LL.04/ /2025 Tanggal , dengan rincian sebagai berikut :</td></tr>
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
                <tr><td>2&nbsp;&nbsp;&nbsp;</td><td>Untuk pendistribusian dan penggunaan barang dan jasa, Pihak II agar berkoordinasi dengan Pengurus Barang
                pembantu.</td></tr>
            </table>   


<?php echo "Demikian Berita Serah Terima ini dibuat dengan sebenarnya dalam rangkap 5 (Lima) untuk dipergunakan sebagaimana mestinya."; ?>

            <table class="no-border-table" style="text-align: center;">
                <tr><td></td><td style="text-align: center;">Pejabat Pelaksana Teknis Kegiatan</td><td></td><td style="text-align: center;">Pejabat Pembuat Komitmen</td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td></td><td style="text-align: center;"><strong><u>dr. Triyani Rositasari</u></strong></td><td></td><td style="text-align: center;"><strong><u>dr. Herman Syahrial</u></strong></td></tr>
                <tr><td></td><td style="text-align: center;">NIP. 19830619 201101 2 005</td><td></td><td style="text-align: center;">NIP. 19690927 200212 1 003</td></tr>
            </table>
        </div>
    </div>
</body>
</html>

<!------------------------------ BATAS HALAMAN 6  ------------------------------->


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 7</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <!-- Halaman Ketujuh -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <h2 class="center-text">SURAT PERINTAH PENCATATAN ASET</h2>
            <h4 class="center-nomorsurat">Nomor Surat : 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/SPPA.1/<?php echo isset($pemesanan['kode_suplier']) ? $pemesanan['kode_suplier'] : ''; ?>/LL.04/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025</h4>

            <table class="no-border-table">        
                <tr><td>Pada hari ini &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
                    bulan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tahun Dua Ribu Dua Puluh Lima (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025),</td></tr>
            </table>            

            <table class="no-border-table">        
            <tr><td></td><td>Yang bertanda tangan di bawah ini </td><td>:</td></tr>
                <tr><td>1</td><td>Nama</td><td>: dr. Herman Syahrial</td></tr>
                <tr><td></td><td>NIP</td><td>: 19690927 200212 1 003</td></tr>
                <tr><td></td><td>Jabatan</td><td>: Direktur RSUD Pringsewu</td></tr>
            </table>            
            <table class="no-border-table">        
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Dalam hal ini bertindak untuk dan atas nama RSUD Pringsewu, 
                    yang selanjutnya disebut sebagai Kuasa Pengguna Barang (KPB);</td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Memerintahkan kepada :</td></tr>
            </table>            
            <table class="no-border-table">        
                <tr><td>2</td><td>Nama  </td><td>: Aris Mulato, SKM</td></tr>
                <tr><td></td><td>NIP </td><td>: 19760308 201407 1 003</td></tr>
                <tr><td></td><td>Jabatan </td><td>: Pengurus Barang Pembantu</td></tr>
            </table>   

            <table class="no-border-table">        
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Dalam hal ini bertindak untuk dan atas nama RSUD Pringsewu, 
                    yang ditunjuk berdasarkan Surat Keputusan (SK) yang ditandatangani Kepala Daerah Kabupaten Pringsewu, 
                    Nomor : B/52 /KPTS/B.02/2023 Tanggal 2 januari 2025 yang selanjutnya disebut sebagai Pengurus Barang Pembantu I;</td></tr>
            </table>   

            <table class="no-border-table">        
                <tr><td>Untuk dapat melakukan pencatatan dan penatausahaan barang-barang berikut sesuai dengan ketentuan Peraturan Menteri
                Dalam negeri Nomor 19 Tahun 2016.</td></tr>
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
                <tr><td>Demikian Berita Serah Terima ini dibuat dalam rangkap 3 (Tiga) untuk dipergunakan sebagaimana mestinya.</td></tr>
            </table>   

            <table class="no-border-table" style="text-align: center;">
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                <td style="text-align: center;">Pengurus Barang Pembantu I</td><td></td><td style="text-align: center;">Pejabat Pembuat Komitmen</td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                <td></td><td></td><td></td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                <td></td><td></td><td></td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                <td style="text-align: center;"><strong><u>Aris Mulato, SKM</u></strong></td><td></td><td style="text-align: center;"><strong><u>dr. Herman Syahrial</u></strong></td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                <td style="text-align: center;">NIP 19760308 201407 1 003</td><td></td><td style="text-align: center;">NIP. 19690927 200212 1 003</td></tr>
            </table>
        </div>
    </div>
</body>
</html>

<!------------------------------ BATAS HALAMAN 7  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 8</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <!-- Halaman Kedelapan -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <h2 class="center-text">SURAT PERMOHONAN PEMBAYARAN</h2>
            <h4 class="center-nomorsurat">Nomor Surat : 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>/SPP.1/<?php echo isset($pemesanan['kode_suplier']) ? $pemesanan['kode_suplier'] : ''; ?>/LL.04/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025</h4>

            <table class="no-border-table">        
                <tr><td>Kepada Yth :</td></tr>
                <tr><td>Kuasa Pengguna Anggaran (KPA)</td></tr>
                <tr><td>BLUD RSUD Pringsewu</td></tr>
                <tr><td>di -</td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pringsewu</td></tr>
            </table>            

            <table class="no-border-table">        
                <tr><td>Dengan hormat,</td></tr>
                <tr><td>Dengan ini kami mengajukan Permintaan Pembayaran untuk kegiatan :</td></tr>
            </table>            


            <table class="no-border-table">        
                <tr><td>1</td><td>Program</td><td>: Operasional Pelayanan Rumah Sakit</td></tr>
                <tr><td>2</td><td>Kegiatan</td><td>: Belanja Barang dan Jasa BLUD</td></tr>
                <tr><td>3</td><td>Pekerjaan</td><td>: Belanja Bahan Habis Pakai</td></tr>
                <tr><td>4</td><td>Nomor PPBJ</td><td>: 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>.01/PPBJ.1/<?php echo isset($pemesanan['kode_suplier']) ? $pemesanan['kode_suplier'] : ''; ?>/LL.04/  /2025</td></tr>
                <tr><td></td><td>Nilai</td><td>: <?php if (isset($total_akhir)) {echo "Rp. " . number_format($total_akhir, 0, ',', '.') . " ";} ?></td></tr>
                <tr><td></td><td></td><td>: <?php if (isset($total_akhir)) {$terbilang_total = terbilang($total_akhir);
                echo "" . ucfirst($terbilang_total) . " rupiah";}?> </td></tr>
                <tr><td>5</td><td>Nomor Surat Pesanan</td><td>: <?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>/SP/<?php echo isset($pemesanan['kode_suplier']) ? $pemesanan['kode_suplier'] : ''; ?>/LL.04/  /2025</td></tr>
                <tr><td></td><td>Nilai</td><td>: <?php if (isset($total_akhir)) {echo "Rp. " . number_format($total_akhir, 0, ',', '.') . " ";} ?></td></tr>
                <tr><td></td><td></td><td>: <?php if (isset($total_akhir)) {$terbilang_total = terbilang($total_akhir);
                echo "" . ucfirst($terbilang_total) . " rupiah";}?></td></tr>
                <tr><td>6</td><td>Pelaksana Pekerjaan</td><td></td></tr>
                <tr><td></td><td>Nama</td><td>: <?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></td></tr>
                <tr><td></td><td>Alamat</td><td>: <?php echo isset($datasuplier['alamat']) ? $datasuplier['alamat'] : ''; ?></td></tr>
                <tr><td></td><td>NPWP</td><td>: <?php echo isset($datasuplier['NPWP']) ? $datasuplier['NPWP'] : ''; ?></td></tr>
            </table>
            
            <table class="no-border-table">        
                <tr><td>Demikian Permohonan ini kami sampaikan, atas perhatian dan kerjasamanya diucapkan terima kasih</td></tr>
            </table>            

            <!-- Tabel Detail Barang 
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
            </table> -->

            <!--
            <?php
            // Menampilkan terbilang dari total akhir
            if (isset($total_akhir)) {
                $terbilang_total = terbilang($total_akhir);
                echo "<p>Terbilang: <strong><em>" . ucfirst($terbilang_total) . " rupiah</em></strong></p>";
            }
            ?> -->

            <div class="signature" style="text-align: center;">
                <p>Pringsewu, ................................................2025</p>
                <p>Pejabat Pelaksana Teknis Kegiatan</p>
                <p>Belanja bahan Habis Pakai (BAHP) Rumah Sakit</p>
                <br>
                <br>
                <p><strong><u>dr. Triyani Rositasari</u></strong></p>
                <p>NIP. 19830619 201101 2 005</p>
            </div>
        </div>
    </div>
</body>
</html>


<!------------------------------ BATAS HALAMAN 8  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 9</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <!-- Halaman Kesembilan -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <h2 class="center-text">BERITA ACARA SERAH TERIMA BARANG/JASA</h2>
            <h4 class="center-nomorsurat">Nomor Surat : 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>/BASTB.IF/<?php echo isset($pemesanan['kode_suplier']) ? $pemesanan['kode_suplier'] : ''; ?>/LL.04/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025</h4>

                <table class="no-border-table">        
                <tr><td>Pada hari ini &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
                    bulan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tahun Dua Ribu Dua Puluh Lima (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025),</td></tr>
            </table>            

            <table class="no-border-table">        
                <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Yang bertanda tangan di bawah ini </td><td>:</td></tr>
                <tr><td>1&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Nama</td><td>: Aris Mulato, SKM</td></tr>
                <tr><td></td><td>NIP</td><td>: 197603082014071003</td></tr>
                <tr><td></td><td>Jabatan</td><td>: Pengurus Barang Pembantu</td></tr>
                <tr><td></td><td></td><td>&nbsp;&nbsp;yang Selanjutnya disebut sebagai Pengurus Barang I ;</td></tr>
                <tr><td>2</td><td>Nama  </td><td>: Wisnetty, S.Si., Apt., M. Kes</td></tr>
                <tr><td></td><td>NIP </td><td>: 197010202000032002</td></tr>
                <tr><td></td><td>Jabatan </td><td>: Kepala Instalasi Farmasi</td></tr>
                <tr><td></td><td></td><td>&nbsp;&nbsp;yang Selanjutnya disebut sebagai Kepala Instalasi Farmasi ;</td></tr>
            </table>            

            <table class="no-border-table">        
                <tr><td>3&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Dengan ini Pengurus Barang I telah menyerahkan barang kepada Kepala Instalasi Farmasi sebagaimana di bawah ini</td></tr>
            </table>   

            <table>
                <tr>
                <th>Nomor</th>
                <th>Tanggal Pesan</th>
                <th>Nomor Pesan</th>
                <th>Tanggal Faktur</th>
                <th>Nomor Faktur</th>
                <th>PBF/PAK</th>
                </tr>

                    <tr>
                    <td>1</td>
                    <td>.</td>
                    <td><div class="supplier-info"style="text-align: center;"><?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>/SP/<?php echo isset($pemesanan['kode_suplier']) ? $pemesanan['kode_suplier'] : ''; ?>/LL.04/  /2025</td>
                    <td><div class="supplier-info"style="text-align: center;"><?php echo "<p>" . htmlspecialchars($pemesanan['tgl_faktur']) . "</p>"; ?> </div></td>
                    <td><div class="supplier-info"style="text-align: center;"><?php echo "<p>" . htmlspecialchars($no_faktur) . "</p>"; ?> </div></td>
                    <td><div class="supplier-info"style="text-align: center;"><?php echo "<p>" . htmlspecialchars($datasuplier['nama_suplier']) . "</p>"; ?> </div></td>
                    </tr>
            </table>
                
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
                <tr><td>2</td><td>Untuk pendistribusian dan penggunaan barang dan jasa, Pihak II agar berkoordinasi dengan Pengurus Barang
                pembantu.</td></tr>
            </table>   


<?php echo "Demikian Berita Serah Terima ini dibuat dengan sebenarnya dalam rangkap 5 (Lima) untuk dipergunakan sebagaimana mestinya."; ?>

            <table class="no-border-table" style="text-align: center;">
                <tr><td></td><td style="text-align: center;">Kepala Instalasi Farmasi</td><td></td><td style="text-align: center;">Pengurus Barang Pembantu</td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td></td><td style="text-align: center;"><strong><u>Wisnetty, S.Si., Apt., M. Kes</u></strong></td><td></td><td style="text-align: center;"><strong><u>Aris Mulato, SKM</u></strong></td></tr>
                <tr><td></td><td style="text-align: center;">NIP. 197010202000032002</td><td></td><td style="text-align: center;">NIP. 197603082014071003</td></tr>
            </table>
        </div>
    </div>
</body>
</html>


<!------------------------------ BATAS HALAMAN 9  ------------------------------->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPJ Halaman 10</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <!-- Halaman Kesepuluh -->
        <div class="page-break">
            <!-- Panggil file header.php -->
            <?php include 'header.php'; ?>

            <h3 class="center-text">BERITA ACARA PEMERIKSAAN BARANG</h3>
            <h4 class="center-nomorsurat">Nomor : 445/<?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>/LL.04/2025</h4>
            <br></br>
            <table class="no-border-table">        
                <tr><td>Pada hari ini &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
                    bulan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    tahun Dua Ribu Dua Puluh Lima (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/2025),
                    telah mengadakan pemeriksaan dan uji fungsi untuk :</td></tr>
            </table>            
            <table class="no-border-table">        
                <tr><td>Kegiatan</td><td>:</td><td>Belanja Barang dan Jasa BLUD</td></tr>
                <tr><td>Pekerjaan</td><td>:</td><td>Belanja Bahan Habis Pakai</td></tr>
                <tr><td>No. Surat Pesanan</td><td>:</td><td><?php echo isset($pemesanan['no_order']) ? $pemesanan['no_order'] : ''; ?>/SP/<?php echo isset($pemesanan['kode_suplier']) ? $pemesanan['kode_suplier'] : ''; ?>/LL.04/  /2025</td></tr>
                <tr><td>Pelaksana Pekerjaan</td><td>:</td><td></td></tr>
                <tr><td>Nama Perusahaan</td><td>:</td><td><?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></td></tr>
                <tr><td>Alamat Perusahaan</td><td>:</td><td><?php echo isset($datasuplier['alamat']) ? $datasuplier['alamat'] : ''; ?></td></tr>
                <tr><td>Daftar Pesanan Produk</td><td>:</td></tr>
            </table>            

            <!-- Tabel Detail Barang -->
            <table>
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Nama Barang</th>
                        <th>Volume</th>
                        <th>Satuan</th>
                        <th>Lengkap</th>
                        <th>Tidak Lengkap</th>
                        <th>Ket</th>
                    </tr>
                </thead>
                <tbody>
                    <?php include 'table_body_kelengkapan.php'; ?>
                </tbody>
            </table>

            <table class="no-border-table">        
                <tr><td>Dari hasil Pemeriksaan Pengadaan Barang/Jasa untuk kebutuhan pekerjaan tersebut diatas dengan Kuantitas dan
                        Jenis Barang/Material pada Surat Pesanan (SP) yang ada, maka kami berpendapat Barang tersebut sudah memenuhi
                        syarat, cukup dan dapat berfungsi dengan baik</td></tr>
                <tr><td>Demikian Berita Acara ini dibuat dalam rangkap 5 (lima) untuk dapat dipergunakan sebagaimana mestinya</td></tr>
            </table>

            <table class="no-border-table" style="text-align: center;">
                <tr><td>1</td><td><?php echo isset($datasuplier['nama_suplier']) ? $datasuplier['nama_suplier'] : ''; ?></td>
                <td><?php echo isset($datasuplier['direktur']) ? $datasuplier['direktur'] : ''; ?></td><td>..................</td></tr>
                <tr><td></td><td>Penyedia Barang/Jasa</td><td><?php echo isset($datasuplier['jabatan']) ? $datasuplier['jabatan'] : ''; ?></td><td></td></tr>
                <tr><td>2</td><td>Pejabat Pelaksana Teknis Kegiatan (PPTK)</td><td>dr. Triyani Rositasari</td><td>..................</td></tr>
                <tr><td></td><td>Rumah Sakit Umum Daerah Pringsewu</td><td>NIP. 19830619 201101 2 005 </td><td></td></tr>
                <tr><td>3</td><td>Tim Teknis Pengadaan Barang dan jasa</td><td>Sulaksono, SKM., M.Kes</td><td>..................</td></tr>
                <tr><td></td><td>Rumah Sakit Umum Daerah Pringsewu</td><td>NIP. 19750523 199703 1 004</td><td></td></tr>
                <tr><td>4</td><td>Tim Teknis Pengadaan Barang dan jasa</td><td>Edi Irawan, Am.TE</td><td>..................</td></tr>
                <tr><td></td><td>Rumah Sakit Umum Daerah Pringsewu</td><td>NIP. 19850806 201101 1 006</td><td></td></tr>
                <tr><td>5</td><td>Tim Teknis Pengadaan Barang dan jasa</td><td>Emmy Saragih, Amd.Gz</td><td>..................</td></tr>
                <tr><td></td><td>Rumah Sakit Umum Daerah Pringsewu</td><td>NIP. 19690118 199203 2 006</td><td></td></tr>
                <tr><td>6</td><td>Tim Teknis Pengadaan Barang dan jasa</td><td>Aini Siswanto</td><td>..................</td></tr>
                <tr><td></td><td>Rumah Sakit Umum Daerah Pringsewu</td><td>NIP. 19680622 200801 1 010</td><td></td></tr>
                <tr><td>7</td><td>Tim Teknis Pengadaan Barang dan jasa</td><td>Aida Septiana, Amd. Farm</td><td>..................</td></tr>
                <tr><td></td><td>Rumah Sakit Umum Daerah Pringsewu</td><td>NIP. 19820927 200604 2 015</td><td></td></tr>
            </table>
        </div>
    </div>
</body>
</html>

<!------------------------------ BATAS HALAMAN 10  ------------------------------->
