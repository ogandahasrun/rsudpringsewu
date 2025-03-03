<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Permohonan Belanja Barang/Jasa</title>
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
    <tr><td> </td><td>1. Peraturan Presiden Nomor 12 Tahun 2021 tentang Pengadaan Brang/Jasa Pemerintah</td></tr>
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
                }
            }
            ?>

            <!-- Tabel Daftar Barang -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">Nomor</th>
                        <th>Nama Obat</th>
                        <th>Volume</th>
                        <th>Satuan</th>
                        <th>Harga</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Tampilkan tabel hanya jika hasil query ada
                    if (isset($result) && mysqli_num_rows($result) > 0) {
                        $nomor_urut = 1; // Inisialisasi nomor urut
                        $total_keseluruhan = 0; // Inisialisasi total keseluruhan

                        while ($row = mysqli_fetch_assoc($result)) {
                            $total = $row['jumlah'] * $row['h_pesan']; // Hitung total per baris
                            $total_keseluruhan += $total; // Akumulasi total keseluruhan

                            echo "<tr>";
                            echo "<td class='text-right'>" . $nomor_urut . "</td>"; // Nomor urut
                            echo "<td>" . $row['nama_brng'] . "</td>";
                            echo "<td class='text-right'>" . number_format($row['jumlah'], 0, ',', '.') . "</td>"; // Volume rata kanan
                            echo "<td>" . $row['satuan'] . "</td>";
                            echo "<td class='text-right'>" . number_format($row['h_pesan'], 0, ',', '.') . "</td>"; // Harga rata kanan
                            echo "<td class='text-right'>" . number_format($total, 0, ',', '.') . "</td>"; // Total rata kanan            
                            echo "</tr>";

                            $nomor_urut++; // Increment nomor urut
                        }

                        // Menampilkan total keseluruhan
                        echo "<tr>";
                        echo "<td colspan='5'><strong>Total Keseluruhan</strong></td>";
                        echo "<td class='text-right'><strong>" . number_format($total_keseluruhan, 0, ',', '.') . "</strong></td>";
                        echo "</tr>";

                        // Menghitung PPN 11%
                        $ppn = $total_keseluruhan * 0.11;

                        // Menampilkan PPN
                        echo "<tr>";
                        echo "<td colspan='5'><strong>PPN (11%)</strong></td>";
                        echo "<td class='text-right'><strong>" . number_format($ppn, 0, ',', '.') . "</strong></td>";
                        echo "</tr>";

                        // Menghitung total akhir (total_keseluruhan + PPN)
                        $total_akhir = $total_keseluruhan + $ppn;

                        // Menampilkan total akhir
                        echo "<tr>";
                        echo "<td colspan='5'><strong>Total</strong></td>";
                        echo "<td class='text-right'><strong>" . number_format($total_akhir, 0, ',', '.') . "</strong></td>";
                        echo "</tr>";
                    } else {
                        if (isset($_POST['filter'])) {
                            echo "<tr><td colspan='6'>Tidak ada data yang ditemukan untuk nomor faktur: $no_faktur</td></tr>";
                        }
                    }
                    
                    mysqli_close($koneksi);
                    ?>
                </tbody>
            </table>

            <?php
            // Menampilkan terbilang dari total akhir
            if (isset($total_akhir)) {
                $terbilang_total = terbilang($total_akhir);
                echo "<p>Terbilang: <strong><em>" . ucfirst($terbilang_total) . " rupiah</em></strong></p>";
            }
            ?>
            
            <div class="signature" style="text-align: center;">
                <p>Pejabat Pembuat Komitmen</p>                
                <br>
                <br>
                <p><strong><u>dr. ANDI ARMAN, Sp.PD</u></strong></p>
                <p>NIP. 19780801 200501 1 009</p>
            </div>
        </div>

        <!-- Tombol untuk preview cetak -->
        <div class="print-button">
            <button onclick="window.print()">Preview Cetak</button>
        </div>
    </div>
    
</body>
</html>