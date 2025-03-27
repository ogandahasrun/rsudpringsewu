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
                    <th class="currency">Harga</th>
                    <th class="currency">Total</th>
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