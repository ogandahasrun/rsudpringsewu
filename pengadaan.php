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
        <!-- Header dengan Logo dan Konten -->
        <div class="header-container">
            <!-- Logo dari URL eksternal -->
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2e/Lambang_Kabupaten_Pringsewu.png/640px-Lambang_Kabupaten_Pringsewu.png" alt="Logo RSUD Pringsewu" class="logo">

            <!-- Konten -->
            <div class="header-content">
                <h1>PEMERINTAH KABUPATEN PRINGSEWU</h1>
                <h1><strong>RSUD PRINGSEWU</strong></h1>
                <p>Jl. Lintas Barat Pekon Fajar Agung Barat, Kec. Pringsewu, Kode Pos 35373</p>
                <p>Phone: (0729) 23582 | Email: rsud@pringsewukab.go.id | Website: rsud.pringsewukab.go.id</p>
            </div>
        </div>

        <!-- Garis Pembatas -->
        <div class="garis-pembatas"></div>

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
    <tr><td>Nomor</td><td>:</td><td><strong>445 /     .01/ PPBJ / LL.04 / / 2025</strong></td></tr>
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
                }
            }
            ?>

            <!-- Tabel Daftar Barang -->
            <table>
                <thead>
                    <tr>
                        <th>Nomor</th>
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
                            echo "<td>" . $nomor_urut . "</td>"; // Nomor urut
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