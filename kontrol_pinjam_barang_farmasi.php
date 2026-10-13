<?php
/**
 * Kontrol Peminjaman Barang Farmasi (Hibah & Pengembalian)
 * RSUD Pringsewu
 * File: kontrol_pinjam_barang_farmasi.php
 */

include 'koneksi.php';

// Ambil info instansi untuk kop / header
$nama_instansi    = "RSUD PRINGSEWU";
$alamat_instansi  = "JL. Lintas Barat Pekon Fajar Agung Barat";
$kabupaten        = "Pringsewu";
$propinsi         = "Lampung";

$q_setting = mysqli_query($koneksi, "SELECT nama_instansi, alamat_instansi, kabupaten, propinsi FROM setting LIMIT 1");
if ($q_setting && $r_setting = mysqli_fetch_assoc($q_setting)) {
    if (!empty($r_setting['nama_instansi']))   $nama_instansi   = $r_setting['nama_instansi'];
    if (!empty($r_setting['alamat_instansi'])) $alamat_instansi = $r_setting['alamat_instansi'];
    if (!empty($r_setting['kabupaten']))       $kabupaten       = $r_setting['kabupaten'];
    if (!empty($r_setting['propinsi']))        $propinsi        = $r_setting['propinsi'];
}

// Parameter Filter (Mendukung GET dan POST)
$tgl_awal       = isset($_REQUEST['tgl_awal']) && !empty($_REQUEST['tgl_awal']) ? $_REQUEST['tgl_awal'] : date('Y-m-01');
$tgl_akhir      = isset($_REQUEST['tgl_akhir']) && !empty($_REQUEST['tgl_akhir']) ? $_REQUEST['tgl_akhir'] : date('Y-m-d');
$status_filter  = isset($_REQUEST['status_filter']) ? $_REQUEST['status_filter'] : 'semua';
$pemberi_filter = isset($_REQUEST['pemberi_filter']) ? $_REQUEST['pemberi_filter'] : 'semua';
$bangsal_filter = isset($_REQUEST['bangsal_filter']) ? $_REQUEST['bangsal_filter'] : 'semua';
$action         = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

$tgl_awal_esc       = mysqli_real_escape_string($koneksi, $tgl_awal);
$tgl_akhir_esc      = mysqli_real_escape_string($koneksi, $tgl_akhir);
$status_filter_esc  = mysqli_real_escape_string($koneksi, $status_filter);
$pemberi_filter_esc = mysqli_real_escape_string($koneksi, $pemberi_filter);
$bangsal_filter_esc = mysqli_real_escape_string($koneksi, $bangsal_filter);

// 1. Ambil list dropdown pemberi hibah & bangsal untuk opsi filter
$opt_pemberi = [];
$q_opt_pemberi = mysqli_query($koneksi, "
    SELECT DISTINCT h.kode_pemberi, COALESCE(p.nama_pemberi, h.kode_pemberi) AS nama_pemberi 
    FROM hibah_obat_bhp h 
    LEFT JOIN pemberihibah p ON h.kode_pemberi = p.kode_pemberi 
    ORDER BY nama_pemberi ASC
");
if ($q_opt_pemberi) {
    while ($row = mysqli_fetch_assoc($q_opt_pemberi)) {
        if (!empty($row['kode_pemberi'])) {
            $opt_pemberi[$row['kode_pemberi']] = $row['nama_pemberi'];
        }
    }
}

$opt_bangsal = [];
$q_opt_bangsal = mysqli_query($koneksi, "
    SELECT DISTINCT h.kd_bangsal, COALESCE(b.nm_bangsal, h.kd_bangsal) AS nm_bangsal 
    FROM hibah_obat_bhp h 
    LEFT JOIN bangsal b ON h.kd_bangsal = b.kd_bangsal 
    ORDER BY nm_bangsal ASC
");
if ($q_opt_bangsal) {
    while ($row = mysqli_fetch_assoc($q_opt_bangsal)) {
        if (!empty($row['kd_bangsal'])) {
            $opt_bangsal[$row['kd_bangsal']] = $row['nm_bangsal'];
        }
    }
}

// 2. Query Data Peminjaman (Hibah Obat / BHP)
$sql_pinjam = "
    SELECT
        hibah_obat_bhp.tgl_hibah,
        hibah_obat_bhp.no_hibah,
        hibah_obat_bhp.kode_pemberi,
        COALESCE(pemberihibah.nama_pemberi, hibah_obat_bhp.kode_pemberi, '-') AS nama_pemberi,
        hibah_obat_bhp.kd_bangsal,
        COALESCE(bangsal.nm_bangsal, hibah_obat_bhp.kd_bangsal, '-') AS nm_bangsal,
        COALESCE(petugas.nama, hibah_obat_bhp.nip, '-') AS nama_petugas_pinjam,
        detailhibah_obat_bhp.kode_brng,
        COALESCE(databarang.nama_brng, detailhibah_obat_bhp.kode_brng) AS nama_brng,
        detailhibah_obat_bhp.kode_sat,
        SUM(detailhibah_obat_bhp.jumlah) AS jumlah
    FROM hibah_obat_bhp
    INNER JOIN detailhibah_obat_bhp ON detailhibah_obat_bhp.no_hibah = hibah_obat_bhp.no_hibah
    LEFT JOIN databarang ON detailhibah_obat_bhp.kode_brng = databarang.kode_brng
    LEFT JOIN pemberihibah ON hibah_obat_bhp.kode_pemberi = pemberihibah.kode_pemberi
    LEFT JOIN bangsal ON hibah_obat_bhp.kd_bangsal = bangsal.kd_bangsal
    LEFT JOIN petugas ON hibah_obat_bhp.nip = petugas.nip
    WHERE hibah_obat_bhp.tgl_hibah BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'
";

if ($pemberi_filter !== 'semua' && !empty($pemberi_filter)) {
    $sql_pinjam .= " AND hibah_obat_bhp.kode_pemberi = '$pemberi_filter_esc'";
}
if ($bangsal_filter !== 'semua' && !empty($bangsal_filter)) {
    $sql_pinjam .= " AND hibah_obat_bhp.kd_bangsal = '$bangsal_filter_esc'";
}

$sql_pinjam .= "
    GROUP BY 
        hibah_obat_bhp.tgl_hibah,
        hibah_obat_bhp.no_hibah,
        hibah_obat_bhp.kode_pemberi,
        pemberihibah.nama_pemberi,
        hibah_obat_bhp.kd_bangsal,
        bangsal.nm_bangsal,
        petugas.nama,
        hibah_obat_bhp.nip,
        detailhibah_obat_bhp.kode_brng,
        databarang.nama_brng,
        detailhibah_obat_bhp.kode_sat
    ORDER BY hibah_obat_bhp.tgl_hibah DESC, hibah_obat_bhp.no_hibah DESC, databarang.nama_brng ASC
";

$res_pinjam = mysqli_query($koneksi, $sql_pinjam);
$raw_pinjam = [];
$no_hibah_list = [];

if ($res_pinjam) {
    while ($row = mysqli_fetch_assoc($res_pinjam)) {
        $raw_pinjam[] = $row;
        $no_hibah_list[$row['no_hibah']] = true;
    }
}

// 3. Query Data Pengembalian (Pengeluaran Obat / BHP)
// Catatan: 'hibah_obat_bhp.no_hibah' dan 'pengeluaran_obat_bhp.no_keluar' harus sama
$kembali_map = [];

if (!empty($no_hibah_list)) {
    $escaped_nos = [];
    foreach (array_keys($no_hibah_list) as $no_h) {
        $escaped_nos[] = "'" . mysqli_real_escape_string($koneksi, $no_h) . "'";
    }
    $in_clause = implode(',', $escaped_nos);

    $sql_kembali = "
        SELECT
            pengeluaran_obat_bhp.tanggal,
            pengeluaran_obat_bhp.no_keluar,
            pengeluaran_obat_bhp.keterangan,
            COALESCE(petugas.nama, pengeluaran_obat_bhp.nip, '-') AS nama_petugas_kembali,
            detail_pengeluaran_obat_bhp.kode_brng,
            detail_pengeluaran_obat_bhp.kode_sat,
            SUM(detail_pengeluaran_obat_bhp.jumlah) AS jumlah
        FROM pengeluaran_obat_bhp
        INNER JOIN detail_pengeluaran_obat_bhp ON detail_pengeluaran_obat_bhp.no_keluar = pengeluaran_obat_bhp.no_keluar
        LEFT JOIN petugas ON pengeluaran_obat_bhp.nip = petugas.nip
        WHERE detail_pengeluaran_obat_bhp.no_keluar IN ($in_clause)
        GROUP BY 
            pengeluaran_obat_bhp.tanggal,
            pengeluaran_obat_bhp.no_keluar,
            pengeluaran_obat_bhp.keterangan,
            petugas.nama,
            pengeluaran_obat_bhp.nip,
            detail_pengeluaran_obat_bhp.kode_brng,
            detail_pengeluaran_obat_bhp.kode_sat
    ";

    $res_kembali = mysqli_query($koneksi, $sql_kembali);
    if ($res_kembali) {
        while ($row = mysqli_fetch_assoc($res_kembali)) {
            $nk = $row['no_keluar'];
            $kb = $row['kode_brng'];
            if (!isset($kembali_map[$nk][$kb])) {
                $kembali_map[$nk][$kb] = [
                    'jumlah'               => 0,
                    'tanggal'              => $row['tanggal'],
                    'keterangan'           => $row['keterangan'],
                    'nama_petugas_kembali' => $row['nama_petugas_kembali']
                ];
            }
            $kembali_map[$nk][$kb]['jumlah'] += (float)$row['jumlah'];
            // Simpan tanggal pengembalian terbaru jika ada multiple tanggal
            if ($row['tanggal'] > $kembali_map[$nk][$kb]['tanggal']) {
                $kembali_map[$nk][$kb]['tanggal'] = $row['tanggal'];
            }
            if (!empty($row['keterangan']) && $row['keterangan'] !== $kembali_map[$nk][$kb]['keterangan']) {
                $kembali_map[$nk][$kb]['keterangan'] .= '; ' . $row['keterangan'];
            }
        }
    }
}

// 4. Penggabungan Data & Analisis Status
$data_detail = [];
$data_dokumen = [];

// Counter statistik
$total_transaksi     = 0;
$total_item_barang   = 0;
$total_qty_pinjam    = 0;
$total_qty_kembali   = 0;
$total_qty_sisa      = 0;

$count_status_sudah    = 0;
$count_status_sebagian = 0;
$count_status_belum    = 0;

foreach ($raw_pinjam as $item) {
    $no_hibah   = $item['no_hibah'];
    $kode_brng  = $item['kode_brng'];
    $jml_pinjam = (float)$item['jumlah'];
    
    $has_kembali = isset($kembali_map[$no_hibah][$kode_brng]);
    $jml_kembali = $has_kembali ? (float)$kembali_map[$no_hibah][$kode_brng]['jumlah'] : 0;
    $tgl_kembali = $has_kembali ? $kembali_map[$no_hibah][$kode_brng]['tanggal'] : null;
    $ket_kembali = $has_kembali ? $kembali_map[$no_hibah][$kode_brng]['keterangan'] : null;
    $pet_kembali = $has_kembali ? $kembali_map[$no_hibah][$kode_brng]['nama_petugas_kembali'] : null;

    $sisa = max(0, $jml_pinjam - $jml_kembali);

    // Penentuan Status
    if ($jml_kembali >= $jml_pinjam && $jml_pinjam > 0) {
        $status_code = 'sudah';
        $status_text = 'Sudah Dikembalikan';
        $status_badge = 'badge-success';
        $status_icon  = 'fa-check-circle';
    } elseif ($jml_kembali > 0) {
        $status_code = 'sebagian';
        $status_text = 'Dikembalikan Sebagian';
        $status_badge = 'badge-warning';
        $status_icon  = 'fa-clock';
    } else {
        $status_code = 'belum';
        $status_text = 'Belum Dikembalikan';
        $status_badge = 'badge-danger';
        $status_icon  = 'fa-times-circle';
    }

    // Filter Status jika dipilih selain 'semua'
    if ($status_filter !== 'semua' && $status_filter !== $status_code) {
        continue;
    }

    $persentase = $jml_pinjam > 0 ? min(100, round(($jml_kembali / $jml_pinjam) * 100, 1)) : 0;

    $row_processed = [
        'tgl_hibah'            => $item['tgl_hibah'],
        'no_hibah'             => $no_hibah,
        'kode_pemberi'         => $item['kode_pemberi'],
        'nama_pemberi'         => $item['nama_pemberi'],
        'kd_bangsal'           => $item['kd_bangsal'],
        'nm_bangsal'           => $item['nm_bangsal'],
        'nama_petugas_pinjam'  => $item['nama_petugas_pinjam'],
        'kode_brng'            => $kode_brng,
        'nama_brng'            => $item['nama_brng'],
        'kode_sat'             => $item['kode_sat'],
        'jml_pinjam'           => $jml_pinjam,
        'jml_kembali'          => $jml_kembali,
        'sisa'                 => $sisa,
        'persentase'           => $persentase,
        'status_code'          => $status_code,
        'status_text'          => $status_text,
        'status_badge'         => $status_badge,
        'status_icon'          => $status_icon,
        'tgl_kembali'          => $tgl_kembali,
        'ket_kembali'          => $ket_kembali,
        'nama_petugas_kembali' => $pet_kembali
    ];

    $data_detail[] = $row_processed;

    // Update Counter
    $total_item_barang++;
    $total_qty_pinjam  += $jml_pinjam;
    $total_qty_kembali += $jml_kembali;
    $total_qty_sisa    += $sisa;

    if ($status_code === 'sudah')    $count_status_sudah++;
    if ($status_code === 'sebagian') $count_status_sebagian++;
    if ($status_code === 'belum')    $count_status_belum++;

    // Rekap Grouping Dokumen
    if (!isset($data_dokumen[$no_hibah])) {
        $data_dokumen[$no_hibah] = [
            'no_hibah'            => $no_hibah,
            'tgl_hibah'           => $item['tgl_hibah'],
            'kode_pemberi'        => $item['kode_pemberi'],
            'nama_pemberi'        => $item['nama_pemberi'],
            'kd_bangsal'          => $item['kd_bangsal'],
            'nm_bangsal'          => $item['nm_bangsal'],
            'nama_petugas_pinjam' => $item['nama_petugas_pinjam'],
            'total_item'          => 0,
            'total_pinjam'        => 0,
            'total_kembali'       => 0,
            'total_sisa'          => 0,
            'tgl_kembali_terakhir'=> null,
            'items'               => []
        ];
    }
    $data_dokumen[$no_hibah]['total_item']++;
    $data_dokumen[$no_hibah]['total_pinjam']  += $jml_pinjam;
    $data_dokumen[$no_hibah]['total_kembali'] += $jml_kembali;
    $data_dokumen[$no_hibah]['total_sisa']    += $sisa;
    if ($tgl_kembali && (!$data_dokumen[$no_hibah]['tgl_kembali_terakhir'] || $tgl_kembali > $data_dokumen[$no_hibah]['tgl_kembali_terakhir'])) {
        $data_dokumen[$no_hibah]['tgl_kembali_terakhir'] = $tgl_kembali;
    }
    $data_dokumen[$no_hibah]['items'][] = $row_processed;
}

$total_transaksi = count($data_dokumen);
$total_persen_kembali = $total_qty_pinjam > 0 ? round(($total_qty_kembali / $total_qty_pinjam) * 100, 1) : 0;

// Export ke Excel (Format .xls Spreadsheet HTML Table)
if ($action === 'export_excel') {
    $filename = "Kontrol_Pinjam_Barang_Farmasi_" . date('Ymd_His', strtotime($tgl_awal)) . "_sd_" . date('Ymd', strtotime($tgl_akhir)) . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
            th { background-color: #0f766e; color: #ffffff; font-weight: bold; padding: 8px; border: 1px solid #333333; text-align: center; }
            td { padding: 6px 8px; border: 1px solid #cccccc; vertical-align: middle; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .bold { font-weight: bold; }
            .status-sudah { background-color: #d1fae5; color: #065f46; font-weight: bold; text-align: center; }
            .status-sebagian { background-color: #fef3c7; color: #92400e; font-weight: bold; text-align: center; }
            .status-belum { background-color: #fee2e2; color: #991b1b; font-weight: bold; text-align: center; }
        </style>
    </head>
    <body>
        <h2><?php echo htmlspecialchars($nama_instansi); ?></h2>
        <h3>LAPORAN KONTROL PEMINJAMAN & PENGEMBALIAN BARANG FARMASI</h3>
        <p>
            Periode: <b><?php echo htmlspecialchars($tgl_awal); ?></b> s/d <b><?php echo htmlspecialchars($tgl_akhir); ?></b><br>
            Status: <b><?php echo ucfirst(htmlspecialchars($status_filter)); ?></b> | 
            Asal Peminjaman: <b><?php echo $pemberi_filter !== 'semua' && isset($opt_pemberi[$pemberi_filter]) ? htmlspecialchars($opt_pemberi[$pemberi_filter]) : 'Semua Asal'; ?></b><br>
            Dicetak pada: <?php echo date('d-m-Y H:i:s'); ?>
        </p>
        <br>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Pinjam (Hibah)</th>
                    <th>Tgl Pinjam</th>
                    <th>Asal Peminjaman</th>
                    <th>Depo / Bangsal</th>
                    <th>Petugas Pinjam</th>
                    <th>Kode Obat</th>
                    <th>Nama Obat / Barang Farmasi</th>
                    <th>Satuan</th>
                    <th>Jml Pinjam</th>
                    <th>Jml Kembali</th>
                    <th>Sisa</th>
                    <th>% Kembali</th>
                    <th>Status</th>
                    <th>Tgl Kembali</th>
                    <th>Keterangan Pengembalian</th>
                    <th>Petugas Pengembalian</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($data_detail)) {
                    $no = 1;
                    foreach ($data_detail as $r) {
                        $cls = 'status-' . $r['status_code'];
                        echo "<tr>";
                        echo "<td class='text-center'>{$no}</td>";
                        echo "<td class='text-center'>{$r['no_hibah']}</td>";
                        echo "<td class='text-center'>{$r['tgl_hibah']}</td>";
                        echo "<td>" . htmlspecialchars($r['nama_pemberi']) . "</td>";
                        echo "<td>" . htmlspecialchars($r['nm_bangsal']) . "</td>";
                        echo "<td>" . htmlspecialchars($r['nama_petugas_pinjam']) . "</td>";
                        echo "<td class='text-center'>{$r['kode_brng']}</td>";
                        echo "<td>" . htmlspecialchars($r['nama_brng']) . "</td>";
                        echo "<td class='text-center'>{$r['kode_sat']}</td>";
                        echo "<td class='text-right'>" . number_format($r['jml_pinjam'], 0, ',', '.') . "</td>";
                        echo "<td class='text-right'>" . number_format($r['jml_kembali'], 0, ',', '.') . "</td>";
                        echo "<td class='text-right'>" . number_format($r['sisa'], 0, ',', '.') . "</td>";
                        echo "<td class='text-center'>{$r['persentase']}%</td>";
                        echo "<td class='{$cls}'>" . htmlspecialchars($r['status_text']) . "</td>";
                        echo "<td class='text-center'>" . ($r['tgl_kembali'] ? htmlspecialchars($r['tgl_kembali']) : '-') . "</td>";
                        echo "<td>" . ($r['ket_kembali'] ? htmlspecialchars($r['ket_kembali']) : '-') . "</td>";
                        echo "<td>" . ($r['nama_petugas_kembali'] ? htmlspecialchars($r['nama_petugas_kembali']) : '-') . "</td>";
                        echo "</tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='17' class='text-center'>Tidak ada data peminjaman barang farmasi pada periode ini.</td></tr>";
                }
                ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="9" class="text-right">TOTAL:</td>
                    <td class="text-right"><?php echo number_format($total_qty_pinjam, 0, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($total_qty_kembali, 0, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($total_qty_sisa, 0, ',', '.'); ?></td>
                    <td class="text-center"><?php echo $total_persen_kembali; ?>%</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
    </body>
    </html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Pinjam Barang Farmasi - <?php echo htmlspecialchars($nama_instansi); ?></title>
    
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    
    <style>
        :root {
            --primary: #0f766e;
            --primary-hover: #0d5c55;
            --primary-light: #ccfbf1;
            --primary-dark: #115e59;
            --secondary: #0284c7;
            --accent: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(15, 23, 42, 0.08);
            --shadow-lg: 0 10px 25px rgba(15, 23, 42, 0.12);
            --radius-md: 10px;
            --radius-lg: 14px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            line-height: 1.5;
            padding-bottom: 50px;
        }

        /* HEADER BANNER */
        .page-header {
            background: linear-gradient(135deg, #094a45 0%, #0f766e 55%, #14b8a6 100%);
            color: white;
            padding: 26px 24px 22px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(15, 118, 110, 0.25);
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50px; right: -50px;
            width: 220px; height: 220px;
            background: rgba(255, 255, 255, 0.07);
            border-radius: 50%;
            pointer-events: none;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: -60px; left: 10%;
            width: 180px; height: 180px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-container {
            max-width: 1350px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            position: relative;
            z-index: 1;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon-box {
            width: 52px;
            height: 52px;
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .header-titles h1 {
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-titles p {
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.88);
            margin-top: 3px;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.76rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-header {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-header:hover {
            background: rgba(255, 255, 255, 0.28);
            transform: translateY(-1px);
        }

        /* MAIN CONTENT */
        .main-container {
            max-width: 1350px;
            margin: -18px auto 0;
            padding: 0 16px;
            position: relative;
            z-index: 2;
        }

        /* FILTER CARD */
        .filter-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 20px 22px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 22px;
        }

        .filter-title {
            font-size: 0.94rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .form-control {
            height: 38px;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-family: inherit;
            border: 1px solid var(--border-color);
            border-radius: 7px;
            background-color: #ffffff;
            color: var(--text-dark);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
        }

        .filter-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn {
            height: 38px;
            padding: 0 16px;
            font-size: 0.84rem;
            font-weight: 600;
            font-family: inherit;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(15, 118, 110, 0.25);
        }

        .btn-secondary {
            background-color: #f1f5f9;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        .btn-success {
            background-color: #059669;
            color: white;
        }

        .btn-success:hover {
            background-color: #047857;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(5, 150, 105, 0.25);
        }

        /* PRESET PERIOD BUTTONS */
        .presets-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px dashed var(--border-color);
        }

        .preset-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .btn-preset {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 3px 10px;
            border-radius: 16px;
            font-size: 0.74rem;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-preset:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary-dark);
        }

        /* KPI / STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .icon-blue   { background-color: #e0f2fe; color: #0284c7; }
        .icon-teal   { background-color: #ccfbf1; color: #0f766e; }
        .icon-green  { background-color: #d1fae5; color: #059669; }
        .icon-amber  { background-color: #fef3c7; color: #d97706; }
        .icon-red    { background-color: #fee2e2; color: #dc2626; }
        .icon-purple { background-color: #ede9fe; color: #7c3aed; }

        .stat-info {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .stat-value {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--text-dark);
        }

        .stat-title {
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin-top: 2px;
        }

        .stat-sub {
            font-size: 0.70rem;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* STATUS PILLS SUMMARY BAR */
        .status-pills-bar {
            background: white;
            border-radius: var(--radius-md);
            padding: 12px 18px;
            border: 1px solid var(--border-color);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: var(--shadow-sm);
        }

        .pills-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pill-item {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .pill-all     { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .pill-sudah   { background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .pill-sebagian{ background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .pill-belum   { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .pill-item:hover, .pill-item.active {
            transform: scale(1.03);
            filter: brightness(0.95);
        }

        .pill-item.active {
            box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.4);
        }

        /* PROGRESS OVERVIEW */
        .progress-container {
            width: 140px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.70rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .progress-bar-bg {
            height: 8px;
            background-color: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* CONTENT CARD & TABS */
        .content-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .card-header-bar {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: #fafafa;
        }

        .tab-buttons {
            display: flex;
            gap: 6px;
        }

        .tab-btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-muted);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .tab-btn:hover {
            color: var(--primary);
            background: rgba(15, 118, 110, 0.05);
        }

        .tab-btn.active {
            background: white;
            color: var(--primary);
            border-color: var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .table-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-pane {
            display: none;
            padding: 20px;
        }

        .tab-pane.active {
            display: block;
        }

        /* TABLE STYLING */
        table.dataTable {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 0.82rem;
        }

        table.dataTable thead th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 700;
            padding: 10px 12px !important;
            border-bottom: 2px solid #cbd5e1 !important;
            font-size: 0.77rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            padding: 10px 12px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            vertical-align: middle;
        }

        table.dataTable tbody tr:hover {
            background-color: #f0fdfa !important;
        }

        .text-center { text-align: center !important; }
        .text-right  { text-align: right !important; }
        .text-left   { text-align: left !important; }

        /* BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-success {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .badge-warning {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-danger {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .badge-info {
            background-color: #f0f9ff;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .badge-gray {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        /* DETAIL / SUBTEXT */
        .sub-text {
            font-size: 0.70rem;
            color: var(--text-muted);
            display: block;
            margin-top: 2px;
        }

        .no-transaksi {
            font-weight: 700;
            color: var(--primary);
            font-family: monospace;
            font-size: 0.85rem;
        }

        .brng-kode {
            font-size: 0.72rem;
            font-family: monospace;
            color: #64748b;
        }

        .brng-nama {
            font-weight: 600;
            color: #1e293b;
        }

        .qty-box {
            font-weight: 700;
            font-size: 0.85rem;
        }

        .qty-pinjam  { color: #0284c7; }
        .qty-kembali { color: #059669; }
        .qty-sisa    { color: #dc2626; }

        /* MINI PROGRESS IN ROW */
        .row-progress {
            width: 70px;
            height: 6px;
            background-color: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-right: 6px;
        }

        .row-progress-fill {
            height: 100%;
            border-radius: 3px;
        }

        /* MODAL STYLES */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-dialog {
            background: white;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 850px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: modalFadeIn 0.25s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(15px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            padding: 16px 20px;
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 1.05rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            transition: background-color 0.15s;
        }

        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            background-color: #fafafa;
        }

        .doc-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            background: #f8fafc;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            border: 1px solid var(--border-color);
            font-size: 0.80rem;
        }

        .doc-info-item span {
            display: block;
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .doc-info-item strong {
            color: var(--text-dark);
            font-size: 0.86rem;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
                font-size: 9pt !important;
            }

            .page-header, .filter-card, .table-toolbar, .dataTables_filter, 
            .dataTables_length, .dataTables_paginate, .dataTables_info,
            .btn, .btn-header, .tab-buttons, .status-pills-bar, .modal-overlay {
                display: none !important;
            }

            .main-container {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }

            .content-card {
                border: none !important;
                box-shadow: none !important;
            }

            .print-kop {
                display: block !important;
                text-align: center;
                border-bottom: 2px solid black;
                padding-bottom: 10px;
                margin-bottom: 15px;
            }

            .print-kop h2 {
                font-size: 14pt;
                margin: 0;
            }

            .print-kop p {
                font-size: 9pt;
                margin: 2px 0;
            }

            .tab-pane {
                display: block !important;
                padding: 0 !important;
            }

            #pane-dokumen {
                display: none !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            table th, table td {
                border: 1px solid #333 !important;
                padding: 5px !important;
                font-size: 8.5pt !important;
            }

            table th {
                background-color: #f2f2f2 !important;
                color: black !important;
            }

            .badge {
                border: none !important;
                padding: 0 !important;
                font-weight: bold !important;
            }
        }

        .print-kop {
            display: none;
        }
    </style>
</head>
<body>

    <!-- PRINT KOP SURAT (Hanya tampil saat dicetak) -->
    <div class="print-kop">
        <h2><?php echo htmlspecialchars($nama_instansi); ?></h2>
        <p><?php echo htmlspecialchars($alamat_instansi); ?>, <?php echo htmlspecialchars($kabupaten); ?> - <?php echo htmlspecialchars($propinsi); ?></p>
        <p><strong>LAPORAN KONTROL PEMINJAMAN & PENGEMBALIAN BARANG FARMASI</strong></p>
        <p>Periode: <?php echo htmlspecialchars($tgl_awal); ?> s/d <?php echo htmlspecialchars($tgl_akhir); ?> | Dicetak: <?php echo date('d-m-Y H:i:s'); ?></p>
    </div>

    <!-- PAGE HEADER BANNER -->
    <header class="page-header">
        <div class="header-container">
            <div class="header-brand">
                <div class="header-icon-box">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
                <div class="header-titles">
                    <h1>
                        Kontrol Peminjaman Barang Farmasi
                        <span class="header-badge">
                            <i class="fa-solid fa-hospital"></i> <?php echo htmlspecialchars($nama_instansi); ?>
                        </span>
                    </h1>
                    <p>Monitoring & verifikasi pengembalian barang farmasi yang dipinjam (Hibah & Pengeluaran)</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="?action=export_excel&tgl_awal=<?php echo urlencode($tgl_awal); ?>&tgl_akhir=<?php echo urlencode($tgl_akhir); ?>&status_filter=<?php echo urlencode($status_filter); ?>&pemberi_filter=<?php echo urlencode($pemberi_filter); ?>" 
                   class="btn-header" title="Download data dalam format Excel">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
                <button type="button" class="btn-header" onclick="window.print()" title="Cetak laporan">
                    <i class="fa-solid fa-print"></i> Cetak Laporan
                </button>
            </div>
        </div>
    </header>

    <!-- MAIN CONTAINER -->
    <main class="main-container">

        <!-- FILTER CARD -->
        <div class="filter-card">
            <div class="filter-title">
                <span><i class="fa-solid fa-filter text-primary"></i> Filter Periode & Parameter Peminjaman</span>
                <span class="sub-text">Default: 1 Bulan Berjalan</span>
            </div>
            <form method="GET" action="" class="filter-form" id="filterForm">
                <div class="form-group">
                    <label for="tgl_awal">Tanggal Awal</label>
                    <input type="date" id="tgl_awal" name="tgl_awal" class="form-control" value="<?php echo htmlspecialchars($tgl_awal); ?>" required>
                </div>
                <div class="form-group">
                    <label for="tgl_akhir">Tanggal Akhir</label>
                    <input type="date" id="tgl_akhir" name="tgl_akhir" class="form-control" value="<?php echo htmlspecialchars($tgl_akhir); ?>" required>
                </div>
                <div class="form-group">
                    <label for="status_filter">Status Pengembalian</label>
                    <select id="status_filter" name="status_filter" class="form-control">
                        <option value="semua" <?php echo $status_filter === 'semua' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="belum" <?php echo $status_filter === 'belum' ? 'selected' : ''; ?>>Belum Dikembalikan</option>
                        <option value="sebagian" <?php echo $status_filter === 'sebagian' ? 'selected' : ''; ?>>Dikembalikan Sebagian</option>
                        <option value="sudah" <?php echo $status_filter === 'sudah' ? 'selected' : ''; ?>>Sudah Dikembalikan (Lunas)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pemberi_filter">Asal Pinjaman (Pemberi)</label>
                    <select id="pemberi_filter" name="pemberi_filter" class="form-control">
                        <option value="semua">Semua Asal Peminjaman</option>
                        <?php foreach ($opt_pemberi as $kode => $nama): ?>
                            <option value="<?php echo htmlspecialchars($kode); ?>" <?php echo $pemberi_filter === $kode ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nama); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bangsal_filter">Depo / Bangsal Peminjam</label>
                    <select id="bangsal_filter" name="bangsal_filter" class="form-control">
                        <option value="semua">Semua Depo / Bangsal</option>
                        <?php foreach ($opt_bangsal as $kode => $nama): ?>
                            <option value="<?php echo htmlspecialchars($kode); ?>" <?php echo $bangsal_filter === $kode ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nama); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary" title="Tampilkan Data">
                        <i class="fa-solid fa-magnifying-glass"></i> Tampilkan
                    </button>
                    <a href="kontrol_pinjam_barang_farmasi.php" class="btn btn-secondary" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </div>
            </form>

            <!-- PRESET TOMBOL PERIODE CEPAT -->
            <div class="presets-row">
                <span class="preset-label"><i class="fa-regular fa-calendar-check"></i> Periode Cepat:</span>
                <button type="button" class="btn-preset" onclick="setPeriode('hari_ini')">Hari Ini</button>
                <button type="button" class="btn-preset" onclick="setPeriode('7_hari')">7 Hari Terakhir</button>
                <button type="button" class="btn-preset" onclick="setPeriode('bulan_ini')">Bulan Ini</button>
                <button type="button" class="btn-preset" onclick="setPeriode('bulan_lalu')">Bulan Lalu</button>
                <button type="button" class="btn-preset" onclick="setPeriode('tahun_ini')">Tahun Ini (2026)</button>
            </div>
        </div>

        <!-- STATS & KPI CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($total_transaksi, 0, ',', '.'); ?></div>
                    <div class="stat-title">Transaksi Pinjam</div>
                    <div class="stat-sub">Nomor Dokumen Hibah</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-teal">
                    <i class="fa-solid fa-pills"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($total_item_barang, 0, ',', '.'); ?></div>
                    <div class="stat-title">Total Macam Item</div>
                    <div class="stat-sub">Obat / BHP Dipinjam</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-amber">
                    <i class="fa-solid fa-hand-holding-medical"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($total_qty_pinjam, 0, ',', '.'); ?></div>
                    <div class="stat-title">Total Qty Pinjam</div>
                    <div class="stat-sub">Volume barang dipinjam</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($total_qty_kembali, 0, ',', '.'); ?></div>
                    <div class="stat-title">Qty Dikembalikan</div>
                    <div class="stat-sub">Volume barang kembali</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-red">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($total_qty_sisa, 0, ',', '.'); ?></div>
                    <div class="stat-title">Sisa Belum Kembali</div>
                    <div class="stat-sub">Masih dalam peminjaman</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-purple">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $total_persen_kembali; ?>%</div>
                    <div class="stat-title">Rasio Pengembalian</div>
                    <div class="stat-sub">Persentase pelunasan</div>
                </div>
            </div>
        </div>

        <!-- QUICK STATUS FILTER BAR -->
        <div class="status-pills-bar">
            <div class="pills-group">
                <span class="sub-text bold" style="font-weight:700;">Filter Cepat Status:</span>
                <a href="?tgl_awal=<?php echo urlencode($tgl_awal); ?>&tgl_akhir=<?php echo urlencode($tgl_akhir); ?>&pemberi_filter=<?php echo urlencode($pemberi_filter); ?>&status_filter=semua" 
                   class="pill-item pill-all <?php echo $status_filter === 'semua' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-list-ul"></i> Semua Status (<?php echo count($raw_pinjam); ?>)
                </a>
                <a href="?tgl_awal=<?php echo urlencode($tgl_awal); ?>&tgl_akhir=<?php echo urlencode($tgl_akhir); ?>&pemberi_filter=<?php echo urlencode($pemberi_filter); ?>&status_filter=sudah" 
                   class="pill-item pill-sudah <?php echo $status_filter === 'sudah' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-check-circle"></i> Sudah Dikembalikan (<?php echo $count_status_sudah; ?>)
                </a>
                <a href="?tgl_awal=<?php echo urlencode($tgl_awal); ?>&tgl_akhir=<?php echo urlencode($tgl_akhir); ?>&pemberi_filter=<?php echo urlencode($pemberi_filter); ?>&status_filter=sebagian" 
                   class="pill-item pill-sebagian <?php echo $status_filter === 'sebagian' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-clock"></i> Dikembalikan Sebagian (<?php echo $count_status_sebagian; ?>)
                </a>
                <a href="?tgl_awal=<?php echo urlencode($tgl_awal); ?>&tgl_akhir=<?php echo urlencode($tgl_akhir); ?>&pemberi_filter=<?php echo urlencode($pemberi_filter); ?>&status_filter=belum" 
                   class="pill-item pill-belum <?php echo $status_filter === 'belum' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-times-circle"></i> Belum Dikembalikan (<?php echo $count_status_belum; ?>)
                </a>
            </div>
            <div class="progress-container">
                <div class="progress-label">
                    <span>Pengembalian</span>
                    <span><?php echo $total_persen_kembali; ?>%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: <?php echo min(100, $total_persen_kembali); ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- MAIN DATA TABLE CARD -->
        <div class="content-card">
            <div class="card-header-bar">
                <div class="tab-buttons">
                    <button type="button" class="tab-btn active" onclick="switchTab('detail')">
                        <i class="fa-solid fa-table-list"></i> Detail Per Item Barang (<?php echo count($data_detail); ?>)
                    </button>
                    <button type="button" class="tab-btn" onclick="switchTab('dokumen')">
                        <i class="fa-solid fa-folder-tree"></i> Rekap Per No. Pinjam (<?php echo count($data_dokumen); ?>)
                    </button>
                </div>
                <div class="table-toolbar">
                    <span class="sub-text">
                        <i class="fa-solid fa-info-circle"></i> Cocokkan otomatis No. Hibah = No. Keluar
                    </span>
                </div>
            </div>

            <!-- TAB 1: DETAIL PER ITEM BARANG -->
            <div id="pane-detail" class="tab-pane active">
                <?php if (!empty($data_detail)): ?>
                    <div class="table-responsive">
                        <table id="tableDetail" class="dataTable display responsive nowrap">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 35px;">No</th>
                                    <th class="text-center">No. Pinjam (Hibah)</th>
                                    <th class="text-center">Tgl Pinjam</th>
                                    <th>Asal Peminjaman</th>
                                    <th>Depo / Bangsal</th>
                                    <th>Kode & Nama Obat / BHP</th>
                                    <th class="text-center">Satuan</th>
                                    <th class="text-right">Jml Pinjam</th>
                                    <th class="text-right">Jml Kembali</th>
                                    <th class="text-right">Sisa</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Tgl Kembali</th>
                                    <th>Keterangan / Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $nomor = 1;
                                foreach ($data_detail as $row):
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $nomor++; ?></td>
                                        <td class="text-center">
                                            <span class="no-transaksi"><?php echo htmlspecialchars($row['no_hibah']); ?></span>
                                            <span class="sub-text"><?php echo htmlspecialchars($row['nama_petugas_pinjam']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <strong><?php echo date('d/m/Y', strtotime($row['tgl_hibah'])); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['nama_pemberi']); ?></strong>
                                            <span class="sub-text"><?php echo htmlspecialchars($row['kode_pemberi']); ?></span>
                                        </td>
                                        <td>
                                            <span><?php echo htmlspecialchars($row['nm_bangsal']); ?></span>
                                        </td>
                                        <td>
                                            <span class="brng-kode"><?php echo htmlspecialchars($row['kode_brng']); ?></span><br>
                                            <span class="brng-nama"><?php echo htmlspecialchars($row['nama_brng']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-gray"><?php echo htmlspecialchars($row['kode_sat']); ?></span>
                                        </td>
                                        <td class="text-right">
                                            <span class="qty-box qty-pinjam"><?php echo number_format($row['jml_pinjam'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td class="text-right">
                                            <span class="qty-box qty-kembali"><?php echo number_format($row['jml_kembali'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td class="text-right">
                                            <span class="qty-box qty-sisa"><?php echo number_format($row['sisa'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?php echo $row['status_badge']; ?>">
                                                <i class="fa-solid <?php echo $row['status_icon']; ?>"></i>
                                                <?php echo htmlspecialchars($row['status_text']); ?>
                                            </span>
                                            <?php if ($row['jml_pinjam'] > 0): ?>
                                                <div style="margin-top: 3px;">
                                                    <span class="row-progress" title="Pelunasan: <?php echo $row['persentase']; ?>%">
                                                        <span class="row-progress-fill" style="width: <?php echo min(100, $row['persentase']); ?>%; background-color: <?php echo $row['persentase'] >= 100 ? '#10b981' : ($row['persentase'] > 0 ? '#f59e0b' : '#ef4444'); ?>;"></span>
                                                    </span>
                                                    <span style="font-size:0.68rem; font-weight:600; color:#64748b;"><?php echo $row['persentase']; ?>%</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['tgl_kembali']): ?>
                                                <strong style="color: #047857;"><?php echo date('d/m/Y', strtotime($row['tgl_kembali'])); ?></strong>
                                            <?php else: ?>
                                                <span class="sub-text">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['ket_kembali']): ?>
                                                <span><?php echo htmlspecialchars($row['ket_kembali']); ?></span><br>
                                            <?php endif; ?>
                                            <?php if ($row['nama_petugas_kembali']): ?>
                                                <span class="sub-text"><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($row['nama_petugas_kembali']); ?></span>
                                            <?php else: ?>
                                                <span class="sub-text">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: bold;">
                                    <th colspan="7" class="text-right">TOTAL:</th>
                                    <th class="text-right"><?php echo number_format($total_qty_pinjam, 0, ',', '.'); ?></th>
                                    <th class="text-right"><?php echo number_format($total_qty_kembali, 0, ',', '.'); ?></th>
                                    <th class="text-right"><?php echo number_format($total_qty_sisa, 0, ',', '.'); ?></th>
                                    <th class="text-center"><?php echo $total_persen_kembali; ?>%</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-folder-open"></i></div>
                        <h3>Tidak Ada Data Peminjaman</h3>
                        <p>Tidak ditemukan data barang farmasi yang dipinjam pada periode <?php echo htmlspecialchars($tgl_awal); ?> s/d <?php echo htmlspecialchars($tgl_akhir); ?>.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: REKAP PER NOMOR DOKUMEN -->
            <div id="pane-dokumen" class="tab-pane">
                <?php if (!empty($data_dokumen)): ?>
                    <div class="table-responsive">
                        <table id="tableDokumen" class="dataTable display responsive nowrap">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 35px;">No</th>
                                    <th class="text-center">No. Pinjam (Hibah)</th>
                                    <th class="text-center">Tgl Pinjam</th>
                                    <th>Asal Peminjaman</th>
                                    <th>Depo / Bangsal</th>
                                    <th>Petugas Pencatat</th>
                                    <th class="text-center">Jml Macam Obat</th>
                                    <th class="text-right">Total Pinjam</th>
                                    <th class="text-right">Total Kembali</th>
                                    <th class="text-right">Sisa Belum</th>
                                    <th class="text-center">Status Dokumen</th>
                                    <th class="text-center">Tgl Kembali Akhir</th>
                                    <th class="text-center" style="width: 70px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no_doc = 1;
                                foreach ($data_dokumen as $doc_no => $doc):
                                    $doc_pinjam  = $doc['total_pinjam'];
                                    $doc_kembali = $doc['total_kembali'];
                                    $doc_sisa    = $doc['total_sisa'];
                                    $doc_persen  = $doc_pinjam > 0 ? min(100, round(($doc_kembali / $doc_pinjam) * 100)) : 0;

                                    if ($doc_kembali >= $doc_pinjam && $doc_pinjam > 0) {
                                        $d_badge = 'badge-success';
                                        $d_icon  = 'fa-check-circle';
                                        $d_text  = 'Lunas / Lengkap';
                                    } elseif ($doc_kembali > 0) {
                                        $d_badge = 'badge-warning';
                                        $d_icon  = 'fa-clock';
                                        $d_text  = 'Sebagian Kembali';
                                    } else {
                                        $d_badge = 'badge-danger';
                                        $d_icon  = 'fa-times-circle';
                                        $d_text  = 'Belum Kembali';
                                    }
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no_doc++; ?></td>
                                        <td class="text-center">
                                            <span class="no-transaksi"><?php echo htmlspecialchars($doc['no_hibah']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <strong><?php echo date('d/m/Y', strtotime($doc['tgl_hibah'])); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($doc['nama_pemberi']); ?></strong>
                                            <span class="sub-text"><?php echo htmlspecialchars($doc['kode_pemberi']); ?></span>
                                        </td>
                                        <td>
                                            <span><?php echo htmlspecialchars($doc['nm_bangsal']); ?></span>
                                        </td>
                                        <td>
                                            <span><?php echo htmlspecialchars($doc['nama_petugas_pinjam']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info"><?php echo $doc['total_item']; ?> Macam</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="qty-box qty-pinjam"><?php echo number_format($doc_pinjam, 0, ',', '.'); ?></span>
                                        </td>
                                        <td class="text-right">
                                            <span class="qty-box qty-kembali"><?php echo number_format($doc_kembali, 0, ',', '.'); ?></span>
                                        </td>
                                        <td class="text-right">
                                            <span class="qty-box qty-sisa"><?php echo number_format($doc_sisa, 0, ',', '.'); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?php echo $d_badge; ?>">
                                                <i class="fa-solid <?php echo $d_icon; ?>"></i> <?php echo $d_text; ?>
                                            </span>
                                            <div style="margin-top: 3px;">
                                                <span class="row-progress" title="Pelunasan: <?php echo $doc_persen; ?>%">
                                                    <span class="row-progress-fill" style="width: <?php echo $doc_persen; ?>%; background-color: <?php echo $doc_persen >= 100 ? '#10b981' : ($doc_persen > 0 ? '#f59e0b' : '#ef4444'); ?>;"></span>
                                                </span>
                                                <span style="font-size:0.68rem; font-weight:600; color:#64748b;"><?php echo $doc_persen; ?>%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($doc['tgl_kembali_terakhir']): ?>
                                                <strong style="color: #047857;"><?php echo date('d/m/Y', strtotime($doc['tgl_kembali_terakhir'])); ?></strong>
                                            <?php else: ?>
                                                <span class="sub-text">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-secondary" style="height:30px; padding:0 8px; font-size:0.75rem;" 
                                                    onclick='showDocDetail(<?php echo json_encode($doc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                                <i class="fa-solid fa-eye"></i> Rincian
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-folder-open"></i></div>
                        <h3>Tidak Ada Dokumen Peminjaman</h3>
                        <p>Tidak ditemukan dokumen hibah peminjaman obat pada periode ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <!-- MODAL DETAIL DOKUMEN -->
    <div id="modalDocDetail" class="modal-overlay">
        <div class="modal-dialog">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-file-invoice"></i> Rincian Dokumen Peminjaman: <span id="modalNoHibah"></span>
                </div>
                <button type="button" class="modal-close" onclick="closeDocDetail()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="doc-info-grid">
                    <div class="doc-info-item">
                        <span>Tanggal Pinjam:</span>
                        <strong id="modalTglPinjam">-</strong>
                    </div>
                    <div class="doc-info-item">
                        <span>Asal Peminjaman:</span>
                        <strong id="modalAsal">-</strong>
                    </div>
                    <div class="doc-info-item">
                        <span>Depo / Bangsal:</span>
                        <strong id="modalBangsal">-</strong>
                    </div>
                    <div class="doc-info-item">
                        <span>Petugas Peminjam:</span>
                        <strong id="modalPetugas">-</strong>
                    </div>
                </div>

                <h4 style="font-size:0.88rem; font-weight:700; margin-bottom:10px; color:#1e293b;">Daftar Obat & Status Pengembalian:</h4>
                <div class="table-responsive">
                    <table style="width:100%; font-size:0.80rem; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f1f5f9; text-align:left; border-bottom:2px solid #cbd5e1;">
                                <th style="padding:8px;">Kode</th>
                                <th style="padding:8px;">Nama Obat / BHP</th>
                                <th style="padding:8px; text-align:center;">Satuan</th>
                                <th style="padding:8px; text-align:right;">Pinjam</th>
                                <th style="padding:8px; text-align:right;">Kembali</th>
                                <th style="padding:8px; text-align:right;">Sisa</th>
                                <th style="padding:8px; text-align:center;">Status</th>
                                <th style="padding:8px;">Tgl & Ket. Kembali</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsBody">
                            <!-- Populated via Javascript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDocDetail()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables untuk Detail Per Item
            $('#tableDetail').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                language: {
                    search: "Cari Cepat:",
                    lengthMenu: "Tampilkan _MENU_ baris",
                    info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    zeroRecords: "Tidak ditemukan data yang sesuai",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "Lanjut",
                        previous: "Kembali"
                    }
                },
                order: [[1, 'desc']], // Urutkan berdasarkan No. Pinjam
                responsive: true
            });

            // Inisialisasi DataTables untuk Rekap Dokumen
            $('#tableDokumen').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                language: {
                    search: "Cari Dokumen:",
                    lengthMenu: "Tampilkan _MENU_ dokumen",
                    info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ dokumen",
                    infoEmpty: "Menampilkan 0 dokumen",
                    infoFiltered: "(disaring dari _MAX_ total dokumen)",
                    zeroRecords: "Tidak ditemukan dokumen yang cocok",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "Lanjut",
                        previous: "Kembali"
                    }
                },
                order: [[1, 'desc']],
                responsive: true
            });
        });

        // Tab Switcher
        function switchTab(tabId) {
            $('.tab-btn').removeClass('active');
            $('.tab-pane').removeClass('active');

            if (tabId === 'detail') {
                $('.tab-btn:first-child').addClass('active');
                $('#pane-detail').addClass('active');
            } else {
                $('.tab-btn:last-child').addClass('active');
                $('#pane-dokumen').addClass('active');
            }

            // Adjust table columns width after tab toggle
            setTimeout(function() {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
            }, 100);
        }

        // Quick Period Buttons
        function setPeriode(p) {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const todayStr = `${yyyy}-${mm}-${dd}`;

            let tglAwal = '';
            let tglAkhir = todayStr;

            if (p === 'hari_ini') {
                tglAwal = todayStr;
                tglAkhir = todayStr;
            } else if (p === '7_hari') {
                const past7 = new Date();
                past7.setDate(now.getDate() - 7);
                tglAwal = past7.toISOString().split('T')[0];
            } else if (p === 'bulan_ini') {
                tglAwal = `${yyyy}-${mm}-01`;
                tglAkhir = todayStr;
            } else if (p === 'bulan_lalu') {
                const prevMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const prevYear = prevMonth.getFullYear();
                const prevMm = String(prevMonth.getMonth() + 1).padStart(2, '0');
                const lastDayPrev = new Date(now.getFullYear(), now.getMonth(), 0).getDate();
                tglAwal = `${prevYear}-${prevMm}-01`;
                tglAkhir = `${prevYear}-${prevMm}-${String(lastDayPrev).padStart(2, '0')}`;
            } else if (p === 'tahun_ini') {
                tglAwal = `${yyyy}-01-01`;
                tglAkhir = todayStr;
            }

            document.getElementById('tgl_awal').value = tglAwal;
            document.getElementById('tgl_akhir').value = tglAkhir;
            document.getElementById('filterForm').submit();
        }

        // Show Document Detail Modal
        function showDocDetail(doc) {
            $('#modalNoHibah').text(doc.no_hibah);
            $('#modalTglPinjam').text(doc.tgl_hibah);
            $('#modalAsal').text(doc.nama_pemberi);
            $('#modalBangsal').text(doc.nm_bangsal);
            $('#modalPetugas').text(doc.nama_petugas_pinjam);

            let tbody = '';
            if (doc.items && doc.items.length > 0) {
                doc.items.forEach(function(item) {
                    let ketText = item.tgl_kembali ? item.tgl_kembali : '-';
                    if (item.ket_kembali) {
                        ketText += ' (' + item.ket_kembali + ')';
                    }

                    tbody += `
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding:8px; font-family:monospace;">${escapeHtml(item.kode_brng)}</td>
                            <td style="padding:8px; font-weight:600;">${escapeHtml(item.nama_brng)}</td>
                            <td style="padding:8px; text-align:center;"><span class="badge badge-gray">${escapeHtml(item.kode_sat)}</span></td>
                            <td style="padding:8px; text-align:right; font-weight:700; color:#0284c7;">${formatNumber(item.jml_pinjam)}</td>
                            <td style="padding:8px; text-align:right; font-weight:700; color:#059669;">${formatNumber(item.jml_kembali)}</td>
                            <td style="padding:8px; text-align:right; font-weight:700; color:#dc2626;">${formatNumber(item.sisa)}</td>
                            <td style="padding:8px; text-align:center;">
                                <span class="badge ${item.status_badge}">
                                    <i class="fa-solid ${item.status_icon}"></i> ${escapeHtml(item.status_text)}
                                </span>
                            </td>
                            <td style="padding:8px; font-size:0.75rem; color:#475569;">${escapeHtml(ketText)}</td>
                        </tr>
                    `;
                });
            } else {
                tbody = `<tr><td colspan="8" style="padding:15px; text-align:center; color:#94a3b8;">Tidak ada data item barang.</td></tr>`;
            }

            $('#modalItemsBody').html(tbody);
            $('#modalDocDetail').addClass('active');
        }

        function closeDocDetail() {
            $('#modalDocDetail').removeClass('active');
        }

        // Close modal when clicked outside
        $(window).on('click', function(e) {
            if ($(e.target).is('#modalDocDetail')) {
                closeDocDetail();
            }
        });

        // Helper escape html
        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatNumber(num) {
            return Number(num).toLocaleString('id-ID');
        }
    </script>
</body>
</html>
