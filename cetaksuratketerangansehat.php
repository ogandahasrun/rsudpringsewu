<?php
// cetaksuratketerangansehat.php
include 'koneksi.php';
$no_surat = isset($_GET['no_surat']) ? trim($_GET['no_surat']) : '';
$data = null;
if ($no_surat !== '') {
    $query = "SELECT
        surat_keterangan_sehat.no_surat,
        surat_keterangan_sehat.no_rawat,
        pasien.nm_pasien,
        pasien.no_rkm_medis,
        pasien.pekerjaan,
        pasien.stts_nikah,
        pasien.alamat,
        kelurahan.nm_kel,
        kecamatan.nm_kec,
        kabupaten.nm_kab,
        surat_keterangan_sehat.tinggi,
        surat_keterangan_sehat.berat,
        surat_keterangan_sehat.kesimpulan,
        surat_keterangan_sehat.keperluan,
        propinsi.nm_prop,
        reg_periksa.umurdaftar,
        reg_periksa.sttsumur,
        surat_keterangan_sehat.tanggalsurat,
        dokter.nm_dokter
    FROM
        surat_keterangan_sehat
    INNER JOIN reg_periksa ON surat_keterangan_sehat.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
    INNER JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
    INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
    INNER JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
    INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
    WHERE surat_keterangan_sehat.no_surat = '" . mysqli_real_escape_string($koneksi, $no_surat) . "'";
    $result = mysqli_query($koneksi, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        // QR Barcode dummy (replace with actual QR code logic if needed)
        $qr_text = 'dikeluarkan di ' . (isset($nama_instansi) ? $nama_instansi : 'RSUD Pringsewu') . ' pada tanggal ' . $data['tanggalsurat'] . ' oleh ' . $data['nm_dokter'];
        $data['qr_barcode'] = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($qr_text);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Surat Keterangan Sehat</title>
    <style>
        .header-container {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 0;
        }
        .logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .header-content {
            flex: 1;
            text-align: center;
        }
        .header-content h1 {
            margin: 0;
            font-size: 1.3em;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header-content p {
            margin: 2px 0 0 0;
            font-size: 1em;
        }
        .garis-pembatas {
            border-bottom: 3px double #222;
            margin: 10px 0 20px 0;
        }

        body {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 40px 30px;
        }
        .kop {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .judul {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        .nomor {
            text-align: center;
            margin-bottom: 20px;
        }
        .isi {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .ttd {
            margin-top: 40px;
            text-align: right;
            font-size: 16px;
        }
        .qr {
            margin: 20px 0 10px 0;
            text-align: left;
        }
        .form-cari {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            justify-content: center;
        }
        @media print {
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .container {
                background: #fff !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 20px !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .form-cari { display: none !important; }
            .notfound { display: none !important; }
            .isi, .ttd, .judul, .nomor, .header-content, .header-container {
                font-size: 13pt !important;
            }
        }
        .form-cari input[type=text] {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            font-size: 15px;
            width: 220px;
        }
        .form-cari button {
            padding: 10px 25px;
            border-radius: 8px;
            border: none;
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .form-cari button:hover {
            background: linear-gradient(45deg, #0056b3, #007bff);
        }
        .notfound {
            color: #c00;
            text-align: center;
            font-style: italic;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <form class="form-cari" method="get" id="formCariSurat">
            <label for="no_surat"><b>Nomor Surat:</b></label>
            <input type="text" id="no_surat" name="no_surat" value="<?php echo htmlspecialchars($no_surat); ?>" required autofocus>
            <button type="submit">Cari</button>
            <?php if ($data): ?>
                <button type="button" onclick="window.print();" style="background:linear-gradient(45deg,#28a745,#20c997);margin-left:10px;">Cetak</button>
            <?php endif; ?>
        </form>
        <?php if ($no_surat !== '' && !$data): ?>
            <div class="notfound">❌ Data surat tidak ditemukan.</div>
        <?php elseif ($data): ?>
            <div style="margin-bottom:30px;">
                <?php include 'header.php'; ?>
            </div>
            <div class="judul">SURAT KETERANGAN SEHAT</div>
            <div class="nomor">Nomor : <?php echo htmlspecialchars($data['no_surat']); ?></div>
            <div class="isi">
                Yang bertanda tangan di bawah ini, Dokter <?php echo isset($nama_instansi) ? htmlspecialchars($nama_instansi) : 'RSUD Pringsewu'; ?> dengan ini menerangkan bahwa :<br><br>
                <table style="border-collapse:collapse; font-size:16px; margin-bottom:10px;">
                    <tr>
                        <td style="vertical-align:top; min-width:120px;">Nama</td>
                        <td style="vertical-align:top; width:10px;">:</td>
                        <td style="vertical-align:top;"><?php echo htmlspecialchars($data['nm_pasien']); ?></td>
                    </tr>
                    <tr>
                        <td>Umur</td>
                        <td>:</td>
                        <td><?php echo htmlspecialchars($data['umurdaftar'] . ' ' . $data['sttsumur']); ?></td>
                    </tr>
                    <tr>
                        <td>Pekerjaan</td>
                        <td>:</td>
                        <td><?php echo htmlspecialchars($data['pekerjaan']); ?></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>:</td>
                        <td><?php echo htmlspecialchars($data['stts_nikah']); ?></td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>:</td>
                        <td><?php echo htmlspecialchars($data['alamat'] . ', ' . $data['nm_kel'] . ', ' . $data['nm_kec'] . ', ' . $data['nm_kab'] . ', ' . $data['nm_prop']); ?></td>
                    </tr>
                    <tr>
                        <td>Tinggi / Berat</td>
                        <td>:</td>
                        <td><?php echo htmlspecialchars($data['tinggi']); ?> cm / <?php echo htmlspecialchars($data['berat']); ?> Kg</td>
                    </tr>
                </table>
                Dengan Hasil Pemeriksaan : <?php echo htmlspecialchars($data['kesimpulan']); ?><br>
                Surat Keterangan Ini Untuk Keperluan : <?php echo htmlspecialchars($data['keperluan']); ?><br><br>
                Demikianlah Surat Keterangan ini dibuat dengan sebenarnya
            </div>
            <div class="ttd">
                Dikeluarkan di : Pringsewu<br>
                Pada tanggal : <?php echo htmlspecialchars($data['tanggalsurat']); ?><br><br>
                <div style="width:100%; display:flex; justify-content: flex-end;">
                    <div class="qr" style="float:right; margin-bottom:10px; margin-left:20px;">
                        <img src="<?php echo $data['qr_barcode']; ?>" alt="QR Barcode">
                    </div>
                </div>
                <br>
                <?php echo htmlspecialchars($data['nm_dokter']); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
