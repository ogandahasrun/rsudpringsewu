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
        <?php include 'header.php'; ?>

        <div class="search-form">
            <h2>Filter Pencarian</h2>
            <form method="GET">
                <label for="nopgdn">No PGDN:</label>
                <input type="text" name="nopgdn" id="nopgdn" value="<?php echo htmlspecialchars($nopgdn); ?>">
                <button type="submit">Cari</button>
            </form>
        </div>

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

        $nopgdn = isset($_GET['nopgdn']) ? $_GET['nopgdn'] : '';
        $data = [];
        $total_summary = 0;
        $pemesanan = [];
        $datasuplier = [];

        if (!empty($nopgdn)) {
            $stmt = $koneksi->prepare("SELECT databarang.nama_brng, SUM(detailpesan.jumlah) AS jumlah, 
                detailpesan.kode_sat AS satuan, AVG(detailpesan.h_pesan) AS harga, SUM(detailpesan.subtotal) AS total
                FROM pemesananspjgabungan
                JOIN pemesanan ON pemesananspjgabungan.no_faktur = pemesanan.no_faktur
                JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
                JOIN databarang ON detailpesan.kode_brng = databarang.kode_brng
                WHERE pemesananspjgabungan.nopgdn = ?
                GROUP BY databarang.nama_brng");
            $stmt->bind_param("s", $nopgdn);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
                $total_summary += $row['total'];
            }
            $stmt->close();

            // Ambil salah satu no_faktur dari hasil pencarian sebelumnya
            $stmt_no_faktur = $koneksi->prepare("SELECT pemesanan.no_faktur 
                FROM pemesananspjgabungan 
                JOIN pemesanan ON pemesananspjgabungan.no_faktur = pemesanan.no_faktur 
                WHERE pemesananspjgabungan.nopgdn = ? LIMIT 1");
            $stmt_no_faktur->bind_param("s", $nopgdn);
            $stmt_no_faktur->execute();
            $result_no_faktur = $stmt_no_faktur->get_result();

            if ($row_faktur = $result_no_faktur->fetch_assoc()) {
                $no_faktur = $row_faktur['no_faktur'];

                // Ambil data suplier berdasarkan no_faktur
                $query_suplier = "SELECT * FROM datasuplier 
                    WHERE kode_suplier = (SELECT kode_suplier FROM pemesanan WHERE no_faktur = ? LIMIT 1)";
                $stmt_suplier = $koneksi->prepare($query_suplier);
                $stmt_suplier->bind_param("s", $no_faktur);
                $stmt_suplier->execute();
                $result_suplier = $stmt_suplier->get_result();
                $datasuplier = $result_suplier->fetch_assoc();

                $stmt_suplier->close();
            }
            $stmt_no_faktur->close();

            $ppn = $total_summary * 0.11;
            $total_with_ppn = $total_summary + $ppn;
            $terbilang = terbilang($total_with_ppn);
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
        ?>

        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th class="currency" style="text-align: center;">Harga</th>
                    <th class="currency" style="text-align: center;">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php include 'table_body_gabungan.php'; ?>
            </tbody>
        </table>

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
            <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th class="currency" style="text-align: center;">Harga</th>
                    <th class="currency" style="text-align: center;">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php include 'table_body_gabungan.php'; ?>
            </tbody>
        </table>

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
                <tr><td>Nomor</td><td>:</td><td>445 /&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;.01/PIBH/LL.04/...../2025</td></tr>
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
            <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th class="currency" style="text-align: center;">Harga</th>
                    <th class="currency" style="text-align: center;">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php include 'table_body_gabungan.php'; ?>
            </tbody>
        </table>

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

<!------------------------------ BATAS HALAMAN 3  ------------------------------->
