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
            <form method="POST">
                Cari Berdasarkan Nomor Pengeluaran : 
                <input type="text" name="no_keluar" required value="<?php echo isset($_POST['no_keluar']) ? htmlspecialchars($_POST['no_keluar']) : ''; ?>">
                <button type="submit" name="filter">Cari</button>
            </form>
        </div>

        <div class="print-button">
            <button onclick="window.print()">Preview Cetak</button>
        </div>

        <div class="content">

            <h4 class="center-text">BERITA ACARA SERAH TERIMA BARANG</h4>
            <?php
            // Fungsi untuk mengubah angka ke teks Indonesia
            function terbilang($angka) {
                $angka = (int)$angka;
                $bilangan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
                if ($angka < 12) {
                    return $bilangan[$angka];
                } elseif ($angka < 20) {
                    return terbilang($angka - 10) . " Belas";
                } elseif ($angka < 100) {
                    return terbilang($angka / 10) . " Puluh " . terbilang($angka % 10);
                } elseif ($angka < 200) {
                    return "Seratus " . terbilang($angka - 100);
                } elseif ($angka < 1000) {
                    return terbilang($angka / 100) . " Ratus " . terbilang($angka % 100);
                } elseif ($angka < 2000) {
                    return "Seribu " . terbilang($angka - 1000);
                } elseif ($angka < 1000000) {
                    return terbilang($angka / 1000) . " Ribu " . terbilang($angka % 1000);
                } elseif ($angka < 1000000000) {
                    return terbilang($angka / 1000000) . " Juta " . terbilang($angka % 1000000);
                } else {
                    return "Angka terlalu besar";
                }
            }

            function hariIndo($tanggal) {
                $hari = [
                    'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
                ];
                $dayIndex = date('w', strtotime($tanggal));
                return $hari[$dayIndex];
            }

            function bulanIndo($bulan) {
                $bulanList = [
                    1 => 'Januari',
                    2 => 'Februari',
                    3 => 'Maret',
                    4 => 'April',
                    5 => 'Mei',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'Agustus',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Desember'
                ];
                return $bulanList[(int)$bulan];
            }

            // Ambil $no_keluar dari POST jika ada
            $no_keluar = isset($_POST['no_keluar']) ? $_POST['no_keluar'] : '';
            $tanggal_lengkap = '';
            if (isset($_POST['filter']) && !empty($no_keluar)) {
                include 'koneksi.php';
                $query_tgl = "SELECT tanggal FROM ipsrspengeluaran WHERE no_keluar = ? LIMIT 1";
                $stmt_tgl = mysqli_prepare($koneksi, $query_tgl);
                mysqli_stmt_bind_param($stmt_tgl, "s", $no_keluar);
                mysqli_stmt_execute($stmt_tgl);
                $result_tgl = mysqli_stmt_get_result($stmt_tgl);
                if ($row_tgl = mysqli_fetch_assoc($result_tgl)) {
                    $tanggal_db = $row_tgl['tanggal'];
                    $hari = hariIndo($tanggal_db);
                    $tgl = date('j', strtotime($tanggal_db));
                    $tgl_angka = date('d', strtotime($tanggal_db));
                    $bulan = date('n', strtotime($tanggal_db));
                    $tahun = date('Y', strtotime($tanggal_db));
                    $tahun_angka = date('Y', strtotime($tanggal_db));
                    $tahun_terbilang = strtolower(terbilang($tahun));
                    $tanggal_lengkap = "Pada hari ini, $hari tanggal " . terbilang($tgl) . " Bulan " . bulanIndo($bulan) . " Tahun " . ucfirst($tahun_terbilang) . " ($tgl_angka-" . str_pad($bulan,2,'0',STR_PAD_LEFT) . "-$tahun_angka), Kami yang bertanda tangan di bawah ini :";
                }
            }
            ?>

            <table class="no-border-table">
                <tr><td>
                    <?php echo $tanggal_lengkap ? $tanggal_lengkap : 'Pada hari ini. .............. Tanggal ................. bulan ..................... tahun Dua Ribu Dua Puluh Lima, Kami yang bertanda tangan di bawah ini :'; ?>
                </td></tr>
            </table>

            <table class="no-border-table" border="1">
                <colgroup>
                    <col style="width: 5%">
                    <col style="width: 30%">
                    <col style="width: 5%">
                    <col style="width: 60%">
                </colgroup>
                <tr><td>A.</td><td>Nama</td><td>:</td><td>RIYADI, SE</td></tr>
                <tr><td></td><td>NIP</td><td>:</td><td>19731024 201001 1 001</td></tr>
                <tr><td></td><td>Jabatan</td><td>:</td><td>Pengurus Barang</td></tr>
                <tr><td></td><td>Selanjutnya disebut PIHAK PERTAMA</td><td colspan="2"></td></tr>
                <tr><td>B.</td><td>Nama</td><td>:</td><td></td></tr>
                <tr><td></td><td>NIP</td><td>:</td><td></td></tr>
                <tr><td></td><td>Jabatan</td><td>:</td><td></td></tr>
                <tr><td></td><td>Selanjutnya disebut PIHAK KEDUA</td><td colspan="2"></td></tr>
            </table>

            <table class="no-border-table">
                <tr><td>Pihak pertama</td></tr>
                <tr><td>Berikut kami sampaikan permintaan pengadaan Belanja Bahan Habis Pakai dari Pengguna atau user untuk Operasional Pelayanan Rumah Sakit</td></tr>    
            </table>

            <?php
            include 'koneksi.php';
            $no_keluar = "";
            $result = null;

            if (isset($_POST['filter'])) {
                $no_keluar = $_POST['no_keluar'];

                if (!empty($no_keluar)) {
                    $query = "SELECT
                                ipsrspengeluaran.no_keluar,
                                ipsrspengeluaran.tanggal,
                                ipsrsbarang.nama_brng,
                                ipsrsdetailpengeluaran.jumlah,
                                kodesatuan.satuan
                            FROM
                                ipsrspengeluaran
                            INNER JOIN ipsrsdetailpengeluaran ON ipsrsdetailpengeluaran.no_keluar = ipsrspengeluaran.no_keluar
                            INNER JOIN ipsrsbarang ON ipsrsdetailpengeluaran.kode_brng = ipsrsbarang.kode_brng
                            INNER JOIN kodesatuan ON ipsrsdetailpengeluaran.kode_sat = kodesatuan.kode_sat
                            WHERE
                                ipsrspengeluaran.no_keluar = ?
                            ORDER BY
                                ipsrsbarang.nama_brng ASC";

                    $stmt = mysqli_prepare($koneksi, $query);
                    mysqli_stmt_bind_param($stmt, "s", $no_keluar);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                } else {
                    echo "<p style='color: red;'>Masukkan nomor keluar</p>";
                }
            }
            ?>

            <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <table border="1" cellspacing="0" cellpadding="5">
                <colgroup>
                    <col style="width: 5%;">   <!-- Nomor -->
                    <col style="width: 55%;">   <!-- Nama Barang -->
                    <col style="width: 20%;">   <!-- Jumlah -->
                    <col style="width: 20%;">   <!-- Satuan -->
                </colgroup>
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_brng']); ?></td>
                            <td><?php echo $row['jumlah']; ?></td>
                            <td><?php echo $row['satuan']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php elseif (isset($_POST['filter'])): ?>
                <p style="color:red;">Data tidak ditemukan untuk nomor keluar: <?php echo htmlspecialchars($no_keluar); ?></p>
            <?php endif; ?>

            <br>

            <table class="no-border-table">
                <tr><td>Demikian berita acara serah terima ini dibuat oleh kedua belah pihak, 
                    adapun barang-barang tersebut dalam keadaan baik dan cukup sejak penandatanganan
                    berita acara ini, maka barang tersebut menjadi tanggung jawab PIHAK KEDUA,
                    memelihara/merawat dengan baik</td></tr>    
            </table>

            <table class="no-border-table" style="text-align: center;">
                <tr><td></td><td style="text-align: center;">Yang Menyerahkan</td><td></td><td style="text-align: center;">Yang Menerima</td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td></td><td style="text-align: center;"><strong><u>RIYADI, SE</u></strong></td><td></td><td style="text-align: center;"><strong><u>..........................</u></strong></td></tr>
                <tr><td></td><td style="text-align: center;">NIP. 19731024 201001 1 001</td><td></td><td style="text-align: center;">NIP. ..............................</td></tr>
                <tr><td></td><td></td><td style="text-align: center;">Mengetahui,</td><td></td></tr>
                <tr><td></td><td></td><td style="text-align: center;">Direktur RSUD Pringsewu</td><td></td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td><br></td><td></td><td></td><td></td></tr>
                <tr><td></td><td></td><td style="text-align: center;"><strong><u>dr. HERMAN SYAHRIAL</u></strong></td><td></td></tr>
                <tr><td></td><td></td><td style="text-align: center;">NIP. 19690927 200202 1 003</td><td></td></tr>
            </table>
        </div>
    </div>
</body>
</html>
