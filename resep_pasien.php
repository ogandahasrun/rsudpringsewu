<?php
require_once 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// =========================================================================
// MODE CETAK RESEP (?action=cetak&no_resep=...)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'cetak' && !empty($_GET['no_resep'])) {
    $no_resep_cetak = $_GET['no_resep'];

    // 1. Query Header (setting)
    $sqlHeader = "SELECT
                    setting.nama_instansi,
                    setting.alamat_instansi,
                    setting.kabupaten,
                    setting.propinsi,
                    setting.kontak,
                    setting.email,
                    setting.logo
                  FROM
                    setting
                  LIMIT 1";
    $resHeader = mysqli_query($koneksi, $sqlHeader);
    $header = mysqli_fetch_assoc($resHeader);

    $logo_src = "images/logo.png";
    if ($header && !empty($header['logo'])) {
        $logo_src = "data:image/png;base64," . base64_encode($header['logo']);
    }

    // 2. Query Isi Resep
    $sqlIsiResep = "SELECT
                        resep_obat.no_resep,
                        resep_obat.no_rawat,
                        resep_obat.tgl_peresepan,
                        resep_obat.jam_peresepan,
                        resep_dokter.kode_brng,
                        databarang.nama_brng,
                        resep_dokter.jml,
                        databarang.kode_sat,
                        resep_dokter.aturan_pakai,
                        dokter.nm_dokter,
                        databarang.kode_golongan
                    FROM
                        resep_obat
                    INNER JOIN resep_dokter ON resep_dokter.no_resep = resep_obat.no_resep
                    INNER JOIN databarang ON resep_dokter.kode_brng = databarang.kode_brng
                    INNER JOIN dokter ON resep_obat.kd_dokter = dokter.kd_dokter
                    WHERE resep_obat.no_resep = ?";
    $stmtIsi = mysqli_prepare($koneksi, $sqlIsiResep);
    mysqli_stmt_bind_param($stmtIsi, 's', $no_resep_cetak);
    mysqli_stmt_execute($stmtIsi);
    $resIsi = mysqli_stmt_get_result($stmtIsi);

    $itemsResep = [];
    $no_rawat_resep = '';
    $tgl_peresepan = '';
    $jam_peresepan = '';
    $nm_dokter_resep = '';

    while ($row = mysqli_fetch_assoc($resIsi)) {
        if ($no_rawat_resep === '') {
            $no_rawat_resep  = $row['no_rawat'];
            $tgl_peresepan   = $row['tgl_peresepan'];
            $jam_peresepan   = $row['jam_peresepan'];
            $nm_dokter_resep = $row['nm_dokter'];
        }
        $itemsResep[] = $row;
    }

    // 3. Query Data Pasien
    $pasienData = null;
    if ($no_rawat_resep !== '') {
        $sqlPasien = "SELECT
                        reg_periksa.no_rawat,
                        pasien.no_rkm_medis,
                        pasien.nm_pasien,
                        reg_periksa.umurdaftar,
                        reg_periksa.sttsumur,
                        penjab.png_jawab
                      FROM
                        reg_periksa
                      INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                      INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                      WHERE reg_periksa.no_rawat = ?
                      LIMIT 1";
        $stmtPasien = mysqli_prepare($koneksi, $sqlPasien);
        mysqli_stmt_bind_param($stmtPasien, 's', $no_rawat_resep);
        mysqli_stmt_execute($stmtPasien);
        $resPasien = mysqli_stmt_get_result($stmtPasien);
        $pasienData = mysqli_fetch_assoc($resPasien);
    }

    // Fallback data peresepan jika tidak ada item resep
    if ($no_rawat_resep === '') {
        $sqlResepFallback = "SELECT resep_obat.no_rawat, resep_obat.tgl_peresepan, resep_obat.jam_peresepan, dokter.nm_dokter
                             FROM resep_obat
                             LEFT JOIN dokter ON resep_obat.kd_dokter = dokter.kd_dokter
                             WHERE resep_obat.no_resep = ? LIMIT 1";
        $stmtFb = mysqli_prepare($koneksi, $sqlResepFallback);
        mysqli_stmt_bind_param($stmtFb, 's', $no_resep_cetak);
        mysqli_stmt_execute($stmtFb);
        $resFb = mysqli_stmt_get_result($stmtFb);
        if ($rowFb = mysqli_fetch_assoc($resFb)) {
            $no_rawat_resep  = $rowFb['no_rawat'];
            $tgl_peresepan   = $rowFb['tgl_peresepan'];
            $jam_peresepan   = $rowFb['jam_peresepan'];
            $nm_dokter_resep = $rowFb['nm_dokter'] ?? '';
        }
    }

    // 4. Generate QR Code Tanda Tangan Dokter (Hanya di memori / base64, tanpa simpan file)
    $nama_instansi_qr = $header['nama_instansi'] ?? '';
    $kabupaten_qr     = $header['kabupaten'] ?? '';
    $dokter_qr        = $nm_dokter_resep ?? '';
    $tgl_resep_qr     = $tgl_peresepan ?? '';

    $qr_text = "Dikeluarkan di " . $nama_instansi_qr . ", Kabupaten/Kota " . $kabupaten_qr . "\n"
             . "Ditandatangani secara elektronik oleh " . $dokter_qr . "\n"
             . "pada tanggal " . $tgl_resep_qr;

    $qr_src = '';
    if (file_exists('phpqrcode/qrlib.php')) {
        require_once 'phpqrcode/qrlib.php';
        ob_start();
        // Menggunakan stream php://output agar tidak memicu Header("Content-type: image/png") dari phpqrcode
        QRcode::png($qr_text, 'php://output', QR_ECLEVEL_M, 3, 2);
        $qr_image_data = ob_get_clean();
        header('Content-Type: text/html; charset=utf-8');
        if (!empty($qr_image_data)) {
            $qr_src = "data:image/png;base64," . base64_encode($qr_image_data);
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Cetak Resep - <?php echo htmlspecialchars($no_resep_cetak); ?></title>
        <style>
            * {
                box-sizing: border-box;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background: #f1f5f9;
                color: #111;
            }
            .print-toolbar {
                max-width: 148mm;
                margin: 0 auto 15px auto;
                display: flex;
                justify-content: space-between;
                gap: 10px;
            }
            .btn-action {
                padding: 8px 16px;
                border: none;
                border-radius: 6px;
                font-size: 13px;
                font-weight: bold;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .btn-print { background: #007bff; color: #fff; }
            .btn-close { background: #6c757d; color: #fff; }
            .resep-sheet {
                max-width: 148mm;
                min-height: 210mm;
                margin: 0 auto;
                background: #fff;
                padding: 16px 20px;
                border: 1px solid #ccc;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            /* Kop Instansi */
            .kop-header {
                display: flex;
                align-items: center;
                gap: 12px;
                border-bottom: 3px double #000;
                padding-bottom: 10px;
                margin-bottom: 12px;
            }
            .kop-logo {
                width: 55px;
                height: 65px;
                object-fit: contain;
            }
            .kop-text {
                flex: 1;
                text-align: center;
            }
            .kop-text h2 {
                margin: 0;
                font-size: 16px;
                text-transform: uppercase;
            }
            .kop-text p {
                margin: 2px 0 0 0;
                font-size: 11px;
                color: #333;
            }
            .judul-resep {
                text-align: center;
                font-weight: bold;
                font-size: 14px;
                letter-spacing: 1px;
                text-decoration: underline;
                margin-bottom: 12px;
            }
            /* Info Pasien & Resep */
            .info-table {
                width: 100%;
                font-size: 12px;
                margin-bottom: 12px;
                border-collapse: collapse;
            }
            .info-table td {
                padding: 3px 4px;
                vertical-align: top;
            }
            .info-table td.label {
                width: 110px;
                font-weight: bold;
            }
            .info-table td.sep {
                width: 10px;
            }
            .resep-divider {
                border-top: 1px solid #333;
                margin: 8px 0 12px 0;
            }
            /* Daftar Obat R/ */
            .obat-list {
                width: 100%;
                border-collapse: collapse;
                font-size: 12.5px;
                margin-bottom: 20px;
            }
            .obat-list th {
                border-bottom: 1px solid #333;
                padding: 6px 4px;
                text-align: left;
                font-size: 11.5px;
            }
            .obat-list td {
                padding: 7px 4px;
                border-bottom: 1px dashed #ccc;
                vertical-align: top;
            }
            .r-symbol {
                font-weight: bold;
                font-style: italic;
                font-size: 14px;
                width: 28px;
            }
            /* Warna Merah untuk Narkotik (NK) */
            tr.obat-nk td {
                background-color: #fee2e2 !important;
                color: #b91c1c !important;
                font-weight: bold;
            }
            tr.obat-nk .nama-obat-text {
                text-decoration: underline;
                text-decoration-color: #dc2626;
                text-decoration-thickness: 2px;
            }
            .badge-nk {
                display: inline-block;
                background: #dc2626 !important;
                color: #fff !important;
                font-size: 10px;
                padding: 1px 6px;
                border-radius: 4px;
                margin-left: 6px;
            }
            /* Warna Biru untuk Psikotropik (PSI) */
            tr.obat-psi td {
                background-color: #dbeafe !important;
                color: #1d4ed8 !important;
                font-weight: bold;
            }
            tr.obat-psi .nama-obat-text {
                text-decoration: underline;
                text-decoration-color: #2563eb;
                text-decoration-thickness: 2px;
            }
            .badge-psi {
                display: inline-block;
                background: #2563eb !important;
                color: #fff !important;
                font-size: 10px;
                padding: 1px 6px;
                border-radius: 4px;
                margin-left: 6px;
            }
            .aturan-pakai {
                font-size: 11.5px;
                margin-top: 3px;
                font-style: italic;
            }
            .legend-print {
                font-size: 10.5px;
                margin-top: 8px;
                display: flex;
                gap: 14px;
                color: #444;
            }
            .footer-sig {
                margin-top: 24px;
                display: flex;
                justify-content: flex-end;
                font-size: 12px;
            }
            .sig-box {
                text-align: center;
                min-width: 180px;
            }
            .sig-space {
                height: 55px;
            }
            .sig-qr {
                margin: 5px auto;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .sig-qr img {
                width: 75px;
                height: 75px;
                display: block;
            }
            @media print {
                body {
                    background: #fff;
                    padding: 0;
                }
                .print-toolbar {
                    display: none !important;
                }
                .resep-sheet {
                    border: none;
                    box-shadow: none;
                    max-width: 100%;
                    min-height: auto;
                    padding: 5mm;
                }
            }
        </style>
    </head>
    <body onload="window.print()">
        <div class="print-toolbar">
            <button type="button" class="btn-action btn-print" onclick="window.print()">🖨️ Cetak Resep</button>
            <button type="button" class="btn-action btn-close" onclick="window.close()">✖ Tutup</button>
        </div>

        <div class="resep-sheet">
            <div>
                <!-- Header Instansi -->
                <div class="kop-header">
                    <img src="<?php echo $logo_src; ?>" alt="Logo" class="kop-logo">
                    <div class="kop-text">
                        <h2><?php echo htmlspecialchars($header['nama_instansi'] ?? 'RSUD PRINGSEWU'); ?></h2>
                        <p>
                            <?php echo htmlspecialchars($header['alamat_instansi'] ?? ''); ?>
                            <?php if (!empty($header['kabupaten'])) echo ', ' . htmlspecialchars($header['kabupaten']); ?>
                            <?php if (!empty($header['propinsi'])) echo ' - ' . htmlspecialchars($header['propinsi']); ?>
                        </p>
                        <p>
                            Telp/Kontak: <?php echo htmlspecialchars($header['kontak'] ?? '-'); ?> |
                            Email: <?php echo htmlspecialchars($header['email'] ?? '-'); ?>
                        </p>
                    </div>
                </div>

                <div class="judul-resep">LEMBAR RESEP DOKTER</div>

                <!-- Data Pasien & Resep -->
                <table class="info-table">
                    <tr>
                        <td class="label">No. Resep</td>
                        <td class="sep">:</td>
                        <td><strong><?php echo htmlspecialchars($no_resep_cetak); ?></strong></td>
                        <td class="label">Tanggal Resep</td>
                        <td class="sep">:</td>
                        <td><?php echo htmlspecialchars($tgl_peresepan . ($jam_peresepan ? ' ' . $jam_peresepan : '')); ?></td>
                    </tr>
                    <tr>
                        <td class="label">No. Rawat</td>
                        <td class="sep">:</td>
                        <td><?php echo htmlspecialchars($pasienData['no_rawat'] ?? $no_rawat_resep); ?></td>
                        <td class="label">No. RM</td>
                        <td class="sep">:</td>
                        <td><strong><?php echo htmlspecialchars($pasienData['no_rkm_medis'] ?? '-'); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label">Nama Pasien</td>
                        <td class="sep">:</td>
                        <td><strong><?php echo htmlspecialchars($pasienData['nm_pasien'] ?? '-'); ?></strong></td>
                        <td class="label">Umur</td>
                        <td class="sep">:</td>
                        <td>
                            <?php
                            if ($pasienData) {
                                echo htmlspecialchars($pasienData['umurdaftar'] . ' ' . $pasienData['sttsumur']);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Dokter Penulis</td>
                        <td class="sep">:</td>
                        <td><?php echo htmlspecialchars($nm_dokter_resep); ?></td>
                        <td class="label">Cara Bayar</td>
                        <td class="sep">:</td>
                        <td><?php echo htmlspecialchars($pasienData['png_jawab'] ?? '-'); ?></td>
                    </tr>
                </table>

                <div class="resep-divider"></div>

                <!-- Isi Resep -->
                <table class="obat-list">
                    <thead>
                        <tr>
                            <th>R/</th>
                            <th>Kode</th>
                            <th>Nama Obat &amp; Aturan Pakai</th>
                            <th style="text-align: right;">Jumlah</th>
                            <th>Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($itemsResep)): ?>
                            <?php foreach ($itemsResep as $item):
                                $gol = strtoupper(trim($item['kode_golongan'] ?? ''));
                                $rowClass = '';
                                $badgeHtml = '';
                                if ($gol === 'NK') {
                                    $rowClass = 'obat-nk';
                                    $badgeHtml = '<span class="badge-nk">NARKOTIK (NK)</span>';
                                } elseif ($gol === 'PSI') {
                                    $rowClass = 'obat-psi';
                                    $badgeHtml = '<span class="badge-psi">PSIKOTROPIK (PSI)</span>';
                                }
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td class="r-symbol">R/</td>
                                <td><?php echo htmlspecialchars($item['kode_brng']); ?></td>
                                <td>
                                    <span class="nama-obat-text"><?php echo htmlspecialchars($item['nama_brng']); ?></span>
                                    <?php echo $badgeHtml; ?>
                                    <div class="aturan-pakai">
                                        ∫ <?php echo htmlspecialchars($item['aturan_pakai'] ?: '-'); ?>
                                    </div>
                                </td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo htmlspecialchars($item['jml']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['kode_sat']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">Data obat tidak ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="legend-print">
                    <span><strong style="color:#dc2626;">■ Merah (NK):</strong> Obat Narkotik</span>
                    <span><strong style="color:#2563eb;">■ Biru (PSI):</strong> Obat Psikotropik</span>
                </div>
            </div>

            <!-- Tanda Tangan Dokter -->
            <div class="footer-sig">
                <div class="sig-box">
                    <div><?php echo htmlspecialchars($header['kabupaten'] ?? 'Pringsewu'); ?>, <?php echo htmlspecialchars($tgl_peresepan ?: date('Y-m-d')); ?></div>
                    <div>Dokter Penulis Resep,</div>
                    <?php if (!empty($qr_src)): ?>
                        <div class="sig-qr">
                            <img src="<?php echo $qr_src; ?>" alt="QR Code Tanda Tangan Dokter">
                        </div>
                    <?php else: ?>
                        <div class="sig-space"></div>
                    <?php endif; ?>
                    <div><strong>( <?php echo htmlspecialchars($nm_dokter_resep ?: '....................................'); ?> )</strong></div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// =========================================================================
// HALAMAN UTAMA DAFTAR RESEP PASIEN
// =========================================================================
$tanggal_awal  = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$kd_dokter     = isset($_GET['kd_dokter']) ? $_GET['kd_dokter'] : '';
$filter_gol    = isset($_GET['filter_gol']) ? $_GET['filter_gol'] : '';

// Ambil daftar dokter untuk filter dropdown
$dokterList = [];
$resDokter = mysqli_query($koneksi, "SELECT kd_dokter, nm_dokter FROM dokter WHERE status = '1' ORDER BY nm_dokter ASC");
if ($resDokter) {
    while ($dk = mysqli_fetch_assoc($resDokter)) {
        $dokterList[] = $dk;
    }
}

// Bangun klausa WHERE
$whereClauses = ["resep_obat.tgl_peresepan BETWEEN ? AND ?"];
$bindTypes = "ss";
$bindParams = [$tanggal_awal, $tanggal_akhir];

if ($kd_dokter !== '') {
    $whereClauses[] = "resep_obat.kd_dokter = ?";
    $bindTypes .= "s";
    $bindParams[] = $kd_dokter;
}

if ($filter_gol === 'NK') {
    $whereClauses[] = "TRIM(databarang.kode_golongan) = 'NK'";
} elseif ($filter_gol === 'PSI') {
    $whereClauses[] = "TRIM(databarang.kode_golongan) = 'PSI'";
} elseif ($filter_gol === 'NK_PSI') {
    $whereClauses[] = "TRIM(databarang.kode_golongan) IN ('NK', 'PSI')";
}

$whereSql = implode(" AND ", $whereClauses);

// Query utama sesuai spesifikasi ditambah kode_golongan untuk penanda warna NK & PSI
$query = "SELECT
            resep_obat.tgl_peresepan,
            resep_obat.no_resep,
            reg_periksa.no_rawat,
            pasien.no_rkm_medis,
            pasien.nm_pasien,
            resep_dokter.kode_brng,
            databarang.nama_brng,
            resep_dokter.jml,
            databarang.kode_sat,
            resep_dokter.aturan_pakai,
            dokter.nm_dokter,
            penjab.png_jawab,
            databarang.kode_golongan
          FROM
            resep_obat
          INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
          INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
          INNER JOIN resep_dokter ON resep_dokter.no_resep = resep_obat.no_resep
          INNER JOIN databarang ON resep_dokter.kode_brng = databarang.kode_brng
          INNER JOIN dokter ON resep_obat.kd_dokter = dokter.kd_dokter
          INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
          WHERE $whereSql
          ORDER BY resep_obat.tgl_peresepan DESC, resep_obat.no_resep DESC";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Kelompokkan per no_resep agar tombol Cetak Resep di sisi kanan rapi per resep
$resepGrouped = [];
$totalItemObat = 0;
$totalItemNK = 0;
$totalItemPSI = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $no_resep = $row['no_resep'];
        $gol = strtoupper(trim($row['kode_golongan'] ?? ''));

        if ($gol === 'NK') $totalItemNK++;
        if ($gol === 'PSI') $totalItemPSI++;
        $totalItemObat++;

        if (!isset($resepGrouped[$no_resep])) {
            $resepGrouped[$no_resep] = [
                'tgl_peresepan' => $row['tgl_peresepan'],
                'no_resep'      => $row['no_resep'],
                'no_rawat'      => $row['no_rawat'],
                'no_rkm_medis'  => $row['no_rkm_medis'],
                'nm_pasien'     => $row['nm_pasien'],
                'nm_dokter'     => $row['nm_dokter'],
                'png_jawab'     => $row['png_jawab'],
                'items'         => []
            ];
        }

        $resepGrouped[$no_resep]['items'][] = [
            'kode_brng'     => $row['kode_brng'],
            'nama_brng'     => $row['nama_brng'],
            'jml'           => $row['jml'],
            'kode_sat'      => $row['kode_sat'],
            'aturan_pakai'  => $row['aturan_pakai'],
            'kode_golongan' => $gol
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Resep Pasien - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            margin: 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 22px 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.75em;
            font-weight: bold;
        }
        .content {
            padding: 25px;
        }
        .back-button {
            margin-bottom: 18px;
        }
        .back-button a {
            display: inline-block;
            padding: 9px 18px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        .back-button a:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        .filter-form {
            background: #f8f9fa;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        .filter-title {
            font-size: 17px;
            font-weight: bold;
            color: #333;
            margin-bottom: 16px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .filter-group label {
            font-weight: bold;
            color: #495057;
            font-size: 13px;
        }
        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 13.5px;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
        }
        .filter-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-1px); }
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-print-row {
            background: #0d6efd;
            color: #fff;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(13, 110, 253, 0.25);
        }
        .btn-print-row:hover {
            background: #0b5ed7;
            transform: translateY(-1px);
        }

        /* Legend & Info Bar */
        .info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 14px;
            padding: 12px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .legend-group {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 13px;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: bold;
        }
        .swatch-nk {
            width: 16px;
            height: 16px;
            background: #fee2e2;
            border: 2px solid #dc2626;
            border-radius: 4px;
            display: inline-block;
        }
        .swatch-psi {
            width: 16px;
            height: 16px;
            background: #dbeafe;
            border: 2px solid #2563eb;
            border-radius: 4px;
            display: inline-block;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 1150px;
        }
        th {
            background: linear-gradient(45deg, #343a40, #495057);
            color: white;
            padding: 13px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 12.5px;
            white-space: nowrap;
        }
        td {
            padding: 10px;
            border: 1px solid #e9ecef;
            font-size: 12.5px;
            vertical-align: top;
        }

        /* Tanda Warna Merah untuk Obat Narkotik (kode_golongan = NK) */
        td.cell-nk {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
            font-weight: bold;
            border-top: 1px solid #fca5a5;
            border-bottom: 1px solid #fca5a5;
        }
        .badge-gol-nk {
            display: inline-block;
            background: #dc2626;
            color: #fff;
            font-size: 10.5px;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 999px;
            margin-left: 6px;
        }

        /* Tanda Warna Biru untuk Obat Psikotropik (kode_golongan = PSI) */
        td.cell-psi {
            background-color: #dbeafe !important;
            color: #1d4ed8 !important;
            font-weight: bold;
            border-top: 1px solid #93c5fd;
            border-bottom: 1px solid #93c5fd;
        }
        .badge-gol-psi {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            font-size: 10.5px;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 999px;
            margin-left: 6px;
        }

        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
    <script>
        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('kd_dokter').value = '';
            document.getElementById('filter_gol').value = '';
        }

        function copyTableData() {
            const table = document.getElementById('resep-table');
            if (!table) return;
            let range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            try {
                document.execCommand('copy');
                alert('✅ Tabel berhasil disalin ke clipboard!');
            } catch (err) {
                alert('❌ Gagal menyalin tabel');
            }
            window.getSelection().removeAllRanges();
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💊 Data Resep Pasien</h1>
        </div>

        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
            </div>

            <!-- Form Filter Periode & Dokter -->
            <form method="GET" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Periode &amp; Dokter Peresep
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Peresepan Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Peresepan Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="kd_dokter">👨‍⚕️ Dokter</label>
                        <select id="kd_dokter" name="kd_dokter">
                            <option value="">-- Semua Dokter --</option>
                            <?php foreach ($dokterList as $dk): ?>
                                <option value="<?php echo htmlspecialchars($dk['kd_dokter']); ?>" <?php if ($kd_dokter === $dk['kd_dokter']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($dk['nm_dokter']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_gol">🏷️ Filter Golongan Obat</label>
                        <select id="filter_gol" name="filter_gol">
                            <option value="" <?php if ($filter_gol === '') echo 'selected'; ?>>-- Semua Golongan Obat --</option>
                            <option value="NK_PSI" <?php if ($filter_gol === 'NK_PSI') echo 'selected'; ?>>Narkotik (NK) &amp; Psikotropik (PSI)</option>
                            <option value="NK" <?php if ($filter_gol === 'NK') echo 'selected'; ?>>Hanya Narkotik (NK - Merah)</option>
                            <option value="PSI" <?php if ($filter_gol === 'PSI') echo 'selected'; ?>>Hanya Psikotropik (PSI - Biru)</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">📊 Tampilkan Data</button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">🔄 Reset Filter</button>
                    <?php if (!empty($resepGrouped)): ?>
                        <button type="button" onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Info & Keterangan Warna Golongan Obat -->
            <div class="info-bar">
                <div style="font-size: 13.5px; font-weight: bold; color: #334155;">
                    📊 Total Resep: <span style="color:#007bff;"><?php echo number_format(count($resepGrouped), 0, ',', '.'); ?></span>
                    | Total Item Obat: <span style="color:#007bff;"><?php echo number_format($totalItemObat, 0, ',', '.'); ?></span>
                </div>
                <div class="legend-group">
                    <span class="legend-item" style="color: #dc2626;">
                        <span class="swatch-nk"></span> Narkotik (kode_golongan = NK): <?php echo $totalItemNK; ?> item
                    </span>
                    <span class="legend-item" style="color: #2563eb;">
                        <span class="swatch-psi"></span> Psikotropik (kode_golongan = PSI): <?php echo $totalItemPSI; ?> item
                    </span>
                </div>
            </div>

            <?php if (!empty($resepGrouped)): ?>
            <div class="table-responsive">
                <table id="resep-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tgl. Peresepan</th>
                            <th>No. Resep</th>
                            <th>No. Rawat</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th style="text-align: right;">Jml</th>
                            <th>Satuan</th>
                            <th>Aturan Pakai</th>
                            <th>Dokter</th>
                            <th>Cara Bayar</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($resepGrouped as $no_resep => $resep):
                            $rowspan = count($resep['items']);
                            $first = true;
                            foreach ($resep['items'] as $obat):
                                $gol = $obat['kode_golongan'];
                                $cellClass = '';
                                $badgeHtml = '';
                                if ($gol === 'NK') {
                                    $cellClass = 'cell-nk';
                                    $badgeHtml = '<span class="badge-gol-nk">NK - Narkotik</span>';
                                } elseif ($gol === 'PSI') {
                                    $cellClass = 'cell-psi';
                                    $badgeHtml = '<span class="badge-gol-psi">PSI - Psikotropik</span>';
                                }
                        ?>
                        <tr>
                            <?php if ($first): ?>
                                <td rowspan="<?php echo $rowspan; ?>"><?php echo $no; ?></td>
                                <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($resep['tgl_peresepan']); ?></td>
                                <td rowspan="<?php echo $rowspan; ?>"><strong><?php echo htmlspecialchars($resep['no_resep']); ?></strong></td>
                                <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($resep['no_rawat']); ?></td>
                                <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($resep['no_rkm_medis']); ?></td>
                                <td rowspan="<?php echo $rowspan; ?>"><strong><?php echo htmlspecialchars($resep['nm_pasien']); ?></strong></td>
                            <?php endif; ?>

                            <td class="<?php echo $cellClass; ?>"><?php echo htmlspecialchars($obat['kode_brng']); ?></td>
                            <td class="<?php echo $cellClass; ?>">
                                <?php echo htmlspecialchars($obat['nama_brng']); ?>
                                <?php echo $badgeHtml; ?>
                            </td>
                            <td class="<?php echo $cellClass; ?>" style="text-align: right;"><?php echo htmlspecialchars($obat['jml']); ?></td>
                            <td class="<?php echo $cellClass; ?>"><?php echo htmlspecialchars($obat['kode_sat']); ?></td>
                            <td class="<?php echo $cellClass; ?>"><?php echo htmlspecialchars($obat['aturan_pakai']); ?></td>

                            <?php if ($first): ?>
                                <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($resep['nm_dokter']); ?></td>
                                <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($resep['png_jawab']); ?></td>
                                <td rowspan="<?php echo $rowspan; ?>" style="text-align: center; vertical-align: middle;">
                                    <a href="resep_pasien.php?action=cetak&no_resep=<?php echo urlencode($resep['no_resep']); ?>"
                                       target="_blank"
                                       class="btn-print-row"
                                       title="Cetak Resep <?php echo htmlspecialchars($resep['no_resep']); ?>">
                                        🖨️ Cetak Resep
                                    </a>
                                </td>
                            <?php
                                $first = false;
                                $no++;
                            endif;
                            ?>
                        </tr>
                        <?php
                            endforeach;
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="no-data">📋 Tidak ada data resep pada filter periode dan dokter yang dipilih.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

