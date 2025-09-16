<?php
include 'koneksi.php';
$query_instansi = "SELECT nama_instansi, alamat_instansi, kabupaten, propinsi, kontak, email, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    $alamat = $row_instansi['alamat_instansi'];
    $kabupaten = $row_instansi['kabupaten'];
    $propinsi = $row_instansi['propinsi'];
    $kontak = $row_instansi['kontak'];
    $email = $row_instansi['email'];
    if (!empty($row_instansi['logo'])) {
        $logo_blob = $row_instansi['logo'];
        $logo_base64 = base64_encode($logo_blob);
        $logo_src = "data:image/png;base64," . $logo_base64;
    } else {
        $logo_src = "images/logo.png";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pesanan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-container">
        <img src="<?php echo $logo_src; ?>" alt="Logo" width="80" height="100">
        <div class="header-content">
            <h1><?php echo htmlspecialchars($nama_instansi); ?></h1>
            <p><?php echo htmlspecialchars($alamat . ', ' . $kabupaten . ', ' . $propinsi); ?></p>
            <p><?php echo htmlspecialchars($kontak . ' | ' . $email); ?></p>
        </div>
        </div>
        <div class="garis-pembatas"></div>
        <div class="search-form">
            <form method="POST">
                Cari Berdasarkan Nomor Surat Pemesanan : 
                <input type="text" name="no_pemesanan" required value="<?php echo isset($_POST['no_pemesanan']) ? htmlspecialchars($_POST['no_pemesanan']) : ''; ?>">
                <button type="submit" name="filter">Cari</button>
            </form>
        </div>
        <div class="print-button">
            <button onclick="window.print()">Preview Cetak</button>
        </div>
        <div class="content">
            <h4 class="center-text">SURAT PESANAN</h4>
            <?php
            include 'koneksi.php';

            // Include library QRCode
            require_once 'phpqrcode/qrlib.php'; // Pastikan path sesuai

            if (isset($_POST['filter'])) {
                $no_pemesanan = mysqli_real_escape_string($koneksi, $_POST['no_pemesanan']);

                $query = "SELECT
                    surat_pemesanan_medis.no_pemesanan,
                    surat_pemesanan_medis.tanggal,
                    datasuplier.nama_suplier,
                    detail_surat_pemesanan_medis.kode_brng,
                    databarang.nama_brng,
                    detail_surat_pemesanan_medis.jumlah,
                    databarang.kode_sat,
                    pegawai.nama
                FROM
                    surat_pemesanan_medis
                INNER JOIN datasuplier ON surat_pemesanan_medis.kode_suplier = datasuplier.kode_suplier
                INNER JOIN detail_surat_pemesanan_medis ON detail_surat_pemesanan_medis.no_pemesanan = surat_pemesanan_medis.no_pemesanan
                INNER JOIN databarang ON detail_surat_pemesanan_medis.kode_brng = databarang.kode_brng
                INNER JOIN pegawai ON surat_pemesanan_medis.nip = pegawai.nik
                WHERE
                    surat_pemesanan_medis.no_pemesanan = '$no_pemesanan'
                ";

                $result = mysqli_query($koneksi, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                    $row_first = mysqli_fetch_assoc($result);
                    echo "<p><strong>No. Pemesanan:</strong> " . htmlspecialchars($row_first['no_pemesanan']) . "</p>";
                    echo "<p><strong>Nama Suplier:</strong> " . htmlspecialchars($row_first['nama_suplier']) . "</p>";
                    $tanggal_pesanan = $row_first['tanggal'];
                    $nama = $row_first['nama'];
                    mysqli_data_seek($result, 0);

                    echo "<table border='1' cellpadding='6' cellspacing='0' style='width:100%;margin-top:12px;'>";
                    echo "<tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                        </tr>";
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                                <td>" . $no++ . "</td>
                                <td>" . htmlspecialchars($row['nama_brng']) . "</td>
                                <td>" . htmlspecialchars($row['jumlah']) . "</td>
                                <td>" . htmlspecialchars($row['kode_sat']) . "</td>
                            </tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='color:red;'>Data tidak ditemukan.</p>";
                }
            }
            ?>
            <div class="signature">
                <p><?php echo htmlspecialchars($kabupaten . ', ' . $tanggal_pesanan); ?></p>
                <p>Hormat kami,</p>
                <!-- QR Code Offline -->
                <?php
                // QR Code data
                date_default_timezone_set('Asia/Jakarta');
                $tanggal = date('d-m-Y');
                $jam = date('H:i:s');
                $qrdata = "Surat ini ditandatangani oleh $nama dengan SIPA 503.440/030/SIPA/429.111/2024 di $nama_instansi pada tanggal $tanggal dan pukul $jam";
                $qrfile = "image/qrcode_pesanan.png";
                // Buat QR Code jika belum ada atau ingin selalu update
                QRcode::png($qrdata, $qrfile, QR_ECLEVEL_L, 2);
                ?>
                <img src="<?php echo $qrfile; ?>" alt="QR Code Tanda Tangan">
                <p><strong><u><?php echo htmlspecialchars($nama); ?></u></strong></p>
                <p>SIPA 503.440/030/SIPA/429.111/2024</p>
                <br>                                
            </div>
        </div>       
    </div>    
</body>
</html>