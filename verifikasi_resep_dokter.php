<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// ============================================================
// Query Nama Instansi & Logo
// ============================================================
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU";
$logo_src = "images/logo.png";
if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    if (!empty($row_instansi['logo'])) {
        $logo_src = "data:image/png;base64," . base64_encode($row_instansi['logo']);
    }
}

// ============================================================
// Filter Parameters
// ============================================================
$tgl1         = $_GET['tgl1'] ?? date('Y-m-d');
$tgl2         = $_GET['tgl2'] ?? date('Y-m-d');
$cari         = trim($_GET['cari'] ?? '');
$filter_status = $_GET['filter_status'] ?? 'semua'; // 'semua', 'beda', 'cocok'
$limit        = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
if ($limit <= 0 || $limit > 200) $limit = 30;
$page         = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset       = ($page - 1) * $limit;

// ============================================================
// Helper Functions
// ============================================================
function normalizeAturan($str) {
    $clean = trim((string)$str);
    if ($clean === '.' || $clean === '-') return '';
    return strtolower($clean);
}

function compareResepItems($resep_items, $farmasi_items) {
    $merged = [];
    $farm_map = [];
    foreach ($farmasi_items as $f) {
        $farm_map[$f['kode_brng']] = $f;
    }
    $used_farm = [];

    foreach ($resep_items as $r) {
        $f = $farm_map[$r['kode_brng']] ?? null;
        if ($f) $used_farm[$r['kode_brng']] = true;

        $status = 'match';
        if (!$f) {
            $status = 'missing'; // Ada di resep dokter tapi belum diberikan farmasi
        } else {
            $jml_diff = (float)$r['jml'] !== (float)$f['jml'];
            $r_atur   = normalizeAturan($r['aturan_pakai'] ?? '');
            $f_atur   = normalizeAturan($f['aturan_pakai'] ?? '');
            $atur_diff = ($r_atur !== $f_atur);
            if ($jml_diff || $atur_diff) {
                $status = 'mismatch'; // Ada perbedaan jumlah atau aturan pakai
            }
        }
        $merged[] = [
            'kode_brng' => $r['kode_brng'],
            'nama_brng' => $r['nama_brng'],
            'kode_sat'  => $r['kode_sat'],
            'resep'     => $r,
            'farmasi'   => $f,
            'status'    => $status
        ];
    }

    foreach ($farmasi_items as $f) {
        if (!isset($used_farm[$f['kode_brng']])) {
            $merged[] = [
                'kode_brng' => $f['kode_brng'],
                'nama_brng' => $f['nama_brng'],
                'kode_sat'  => $f['kode_sat'],
                'resep'     => null,
                'farmasi'   => $f,
                'status'    => 'extra' // Diberikan farmasi tapi tidak ada di resep dokter
            ];
        }
    }

    return $merged;
}

function compareRacikanItems($resep_racik, $farmasi_racik) {
    $merged = [];
    $farm_map = [];
    foreach ($farmasi_racik as $f) {
        $farm_map[$f['no_racik']] = $f;
    }
    $used_farm = [];

    foreach ($resep_racik as $r) {
        $f = $farm_map[$r['no_racik']] ?? null;
        if ($f) $used_farm[$r['no_racik']] = true;

        $detail_comp = compareResepItems($r['detail'] ?? [], $f ? ($f['detail'] ?? []) : []);

        $status = 'match';
        if (!$f) {
            $status = 'missing';
        } else {
            $jml_diff = (float)$r['jml_dr'] !== (float)$f['jml_dr'];
            $r_atur   = normalizeAturan($r['aturan_pakai'] ?? '');
            $f_atur   = normalizeAturan($f['aturan_pakai'] ?? '');
            $atur_diff = ($r_atur !== $f_atur);

            $has_sub_mismatch = false;
            foreach ($detail_comp as $dc) {
                if ($dc['status'] !== 'match') {
                    $has_sub_mismatch = true;
                    break;
                }
            }
            if ($jml_diff || $atur_diff || $has_sub_mismatch) {
                $status = 'mismatch';
            }
        }

        $merged[] = [
            'resep'       => $r,
            'farmasi'     => $f,
            'status'      => $status,
            'detail_comp' => $detail_comp
        ];
    }

    foreach ($farmasi_racik as $f) {
        if (!isset($used_farm[$f['no_racik']])) {
            $detail_comp = compareResepItems([], $f['detail'] ?? []);
            $merged[] = [
                'resep'       => null,
                'farmasi'     => $f,
                'status'      => 'extra',
                'detail_comp' => $detail_comp
            ];
        }
    }

    return $merged;
}

// ============================================================
// MAIN QUERY: Daftar Header Resep
// ============================================================
$count_sql = "SELECT COUNT(*) as total
              FROM resep_obat
                INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
                INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                INNER JOIN dokter ON resep_obat.kd_dokter = dokter.kd_dokter
              WHERE resep_obat.tgl_peresepan <> '0000-00-00'
                AND resep_obat.tgl_perawatan <> '0000-00-00'
                AND resep_obat.tgl_peresepan BETWEEN ? AND ?";

$types  = "ss";
$params = [$tgl1, $tgl2];

if ($cari !== '') {
    $count_sql .= " AND (resep_obat.no_resep LIKE ? OR resep_obat.no_rawat LIKE ?
                    OR pasien.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ?
                    OR resep_obat.kd_dokter LIKE ? OR dokter.nm_dokter LIKE ?
                    OR resep_obat.status LIKE ?)";
    $like = "%{$cari}%";
    $types  .= "sssssss";
    $params  = array_merge($params, array_fill(0, 7, $like));
}

$stmt_cnt = mysqli_prepare($koneksi, $count_sql);
mysqli_stmt_bind_param($stmt_cnt, $types, ...$params);
mysqli_stmt_execute($stmt_cnt);
$cnt_res = mysqli_stmt_get_result($stmt_cnt);
$total_rows = mysqli_fetch_assoc($cnt_res)['total'] ?? 0;
mysqli_stmt_close($stmt_cnt);

$total_pages = ceil($total_rows / $limit);
if ($total_pages < 1) $total_pages = 1;

// Fetch page records
$sql = "SELECT resep_obat.no_resep, resep_obat.tgl_peresepan, resep_obat.jam_peresepan,
               resep_obat.no_rawat, pasien.no_rkm_medis, pasien.nm_pasien,
               resep_obat.kd_dokter, dokter.nm_dokter, resep_obat.status,
               resep_obat.tgl_perawatan, resep_obat.jam
        FROM resep_obat
          INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
          INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
          INNER JOIN dokter ON resep_obat.kd_dokter = dokter.kd_dokter
        WHERE resep_obat.tgl_peresepan <> '0000-00-00'
          AND resep_obat.tgl_perawatan <> '0000-00-00'
          AND resep_obat.tgl_peresepan BETWEEN ? AND ?";

if ($cari !== '') {
    $sql .= " AND (resep_obat.no_resep LIKE ? OR resep_obat.no_rawat LIKE ?
              OR pasien.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ?
              OR resep_obat.kd_dokter LIKE ? OR dokter.nm_dokter LIKE ?
              OR resep_obat.status LIKE ?)";
}

$sql .= " ORDER BY resep_obat.tgl_perawatan DESC, resep_obat.jam DESC LIMIT ? OFFSET ?";
$types_limit = $types . "ii";
$params_limit = array_merge($params, [$limit, $offset]);

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, $types_limit, ...$params_limit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$resep_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $resep_list[] = $row;
}
mysqli_stmt_close($stmt);

// ============================================================
// Load Obat Details for all resep in this page
// ============================================================
$verified_reseps = [];
$stat_total_items = 0;
$stat_match_items = 0;
$stat_diff_items  = 0;
$stat_resep_cocok = 0;
$stat_resep_beda  = 0;

foreach ($resep_list as $r) {
    $no_resep = $r['no_resep'];
    $no_rawat = $r['no_rawat'];
    $tgl_per  = $r['tgl_perawatan'];
    $jam_per  = $r['jam'];

    // 2a. Resep dokter non-racikan
    $q2a = mysqli_prepare($koneksi, "SELECT databarang.kode_brng, databarang.nama_brng, resep_dokter.jml,
                                            databarang.kode_sat, resep_dokter.aturan_pakai
                                     FROM resep_dokter
                                       INNER JOIN databarang ON resep_dokter.kode_brng = databarang.kode_brng
                                     WHERE resep_dokter.no_resep = ?
                                     ORDER BY databarang.kode_brng");
    mysqli_stmt_bind_param($q2a, "s", $no_resep);
    mysqli_stmt_execute($q2a);
    $res2a = mysqli_stmt_get_result($q2a);
    $r_non = [];
    while ($row = mysqli_fetch_assoc($res2a)) $r_non[] = $row;
    mysqli_stmt_close($q2a);

    // 2b & 2c. Resep dokter racikan
    $q2b = mysqli_prepare($koneksi, "SELECT resep_dokter_racikan.no_racik, resep_dokter_racikan.nama_racik,
                                            resep_dokter_racikan.kd_racik, metode_racik.nm_racik AS metode,
                                            resep_dokter_racikan.jml_dr, resep_dokter_racikan.aturan_pakai,
                                            resep_dokter_racikan.keterangan
                                     FROM resep_dokter_racikan
                                       INNER JOIN metode_racik ON resep_dokter_racikan.kd_racik = metode_racik.kd_racik
                                     WHERE resep_dokter_racikan.no_resep = ?");
    mysqli_stmt_bind_param($q2b, "s", $no_resep);
    mysqli_stmt_execute($q2b);
    $res2b = mysqli_stmt_get_result($q2b);
    $r_rac = [];
    while ($rh = mysqli_fetch_assoc($res2b)) {
        $q2c = mysqli_prepare($koneksi, "SELECT databarang.kode_brng, databarang.nama_brng,
                                                resep_dokter_racikan_detail.jml, databarang.kode_sat
                                         FROM resep_dokter_racikan_detail
                                           INNER JOIN databarang ON resep_dokter_racikan_detail.kode_brng = databarang.kode_brng
                                         WHERE resep_dokter_racikan_detail.no_resep = ?
                                           AND resep_dokter_racikan_detail.no_racik = ?
                                         ORDER BY databarang.kode_brng");
        mysqli_stmt_bind_param($q2c, "ss", $no_resep, $rh['no_racik']);
        mysqli_stmt_execute($q2c);
        $res2c = mysqli_stmt_get_result($q2c);
        $rh['detail'] = [];
        while ($rd = mysqli_fetch_assoc($res2c)) $rh['detail'][] = $rd;
        mysqli_stmt_close($q2c);
        $r_rac[] = $rh;
    }
    mysqli_stmt_close($q2b);

    // 3a. Farmasi non-racikan
    $q3a = mysqli_prepare($koneksi, "SELECT databarang.kode_brng, databarang.nama_brng,
                                            detail_pemberian_obat.jml, databarang.kode_sat,
                                            detail_pemberian_obat.biaya_obat, detail_pemberian_obat.embalase,
                                            detail_pemberian_obat.tuslah, detail_pemberian_obat.total
                                     FROM detail_pemberian_obat
                                       INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
                                     WHERE detail_pemberian_obat.tgl_perawatan = ?
                                       AND detail_pemberian_obat.jam = ?
                                       AND detail_pemberian_obat.no_rawat = ?
                                       AND databarang.kode_brng NOT IN (
                                         SELECT detail_obat_racikan.kode_brng
                                         FROM detail_obat_racikan
                                         WHERE detail_obat_racikan.tgl_perawatan = ?
                                           AND detail_obat_racikan.jam = ?
                                           AND detail_obat_racikan.no_rawat = ?
                                       )
                                     ORDER BY databarang.kode_brng");
    mysqli_stmt_bind_param($q3a, "ssssss", $tgl_per, $jam_per, $no_rawat, $tgl_per, $jam_per, $no_rawat);
    mysqli_stmt_execute($q3a);
    $res3a = mysqli_stmt_get_result($q3a);
    $f_non_raw = [];
    while ($row = mysqli_fetch_assoc($res3a)) $f_non_raw[] = $row;
    mysqli_stmt_close($q3a);

    // Agregasi farmasi non-racikan per kode barang
    $f_non = [];
    foreach ($f_non_raw as $fr) {
        $kd = $fr['kode_brng'];
        if (!isset($f_non[$kd])) {
            $f_non[$kd] = $fr;
        } else {
            $f_non[$kd]['jml']        += $fr['jml'];
            $f_non[$kd]['biaya_obat'] += $fr['biaya_obat'];
            $f_non[$kd]['total']      += $fr['total'];
        }
    }

    // Aturan pakai farmasi non-racikan
    $qat = mysqli_prepare($koneksi, "SELECT kode_brng, aturan
                                     FROM aturan_pakai
                                     WHERE tgl_perawatan = ?
                                       AND jam = ?
                                       AND no_rawat = ?");
    mysqli_stmt_bind_param($qat, "sss", $tgl_per, $jam_per, $no_rawat);
    mysqli_stmt_execute($qat);
    $resat = mysqli_stmt_get_result($qat);
    $at_map = [];
    while ($row = mysqli_fetch_assoc($resat)) $at_map[$row['kode_brng']] = $row['aturan'];
    mysqli_stmt_close($qat);

    foreach ($f_non as &$fn) {
        $fn['aturan_pakai'] = $at_map[$fn['kode_brng']] ?? '';
    }
    unset($fn);
    $f_non = array_values($f_non);

    // 3b & 3c. Farmasi racikan
    $q3b = mysqli_prepare($koneksi, "SELECT obat_racikan.no_racik, obat_racikan.nama_racik,
                                            obat_racikan.kd_racik, metode_racik.nm_racik AS metode,
                                            obat_racikan.jml_dr, obat_racikan.aturan_pakai,
                                            obat_racikan.keterangan
                                     FROM obat_racikan
                                       INNER JOIN metode_racik ON obat_racikan.kd_racik = metode_racik.kd_racik
                                     WHERE obat_racikan.tgl_perawatan = ?
                                       AND obat_racikan.jam = ?
                                       AND obat_racikan.no_rawat = ?");
    mysqli_stmt_bind_param($q3b, "sss", $tgl_per, $jam_per, $no_rawat);
    mysqli_stmt_execute($q3b);
    $res3b = mysqli_stmt_get_result($q3b);
    $f_rac = [];
    while ($fh = mysqli_fetch_assoc($res3b)) {
        $q3c = mysqli_prepare($koneksi, "SELECT databarang.kode_brng, databarang.nama_brng,
                                                detail_pemberian_obat.jml, databarang.kode_sat,
                                                detail_pemberian_obat.biaya_obat, detail_pemberian_obat.embalase,
                                                detail_pemberian_obat.tuslah, detail_pemberian_obat.total
                                         FROM detail_pemberian_obat
                                           INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
                                           INNER JOIN detail_obat_racikan
                                             ON detail_pemberian_obat.kode_brng = detail_obat_racikan.kode_brng
                                             AND detail_pemberian_obat.tgl_perawatan = detail_obat_racikan.tgl_perawatan
                                             AND detail_pemberian_obat.jam = detail_obat_racikan.jam
                                             AND detail_pemberian_obat.no_rawat = detail_obat_racikan.no_rawat
                                         WHERE detail_pemberian_obat.tgl_perawatan = ?
                                           AND detail_pemberian_obat.jam = ?
                                           AND detail_pemberian_obat.no_rawat = ?
                                           AND detail_obat_racikan.no_racik = ?
                                         ORDER BY databarang.kode_brng");
        mysqli_stmt_bind_param($q3c, "ssss", $tgl_per, $jam_per, $no_rawat, $fh['no_racik']);
        mysqli_stmt_execute($q3c);
        $res3c = mysqli_stmt_get_result($q3c);
        $fh['detail'] = [];
        while ($fd = mysqli_fetch_assoc($res3c)) $fh['detail'][] = $fd;
        mysqli_stmt_close($q3c);
        $f_rac[] = $fh;
    }
    mysqli_stmt_close($q3b);

    // Bandingkan side-by-side
    $comp_non = compareResepItems($r_non, $f_non);
    $comp_rac = compareRacikanItems($r_rac, $f_rac);

    // Hitung status kesesuaian resep ini
    $resep_status_kesesuaian = 'cocok';
    $count_item_resep = 0;
    $count_diff_resep = 0;

    foreach ($comp_non as $cn) {
        $count_item_resep++;
        $stat_total_items++;
        if ($cn['status'] === 'match') {
            $stat_match_items++;
        } else {
            $stat_diff_items++;
            $count_diff_resep++;
            $resep_status_kesesuaian = 'beda';
        }
    }

    foreach ($comp_rac as $cr) {
        $count_item_resep++;
        $stat_total_items++;
        if ($cr['status'] === 'match') {
            $stat_match_items++;
        } else {
            $stat_diff_items++;
            $count_diff_resep++;
            $resep_status_kesesuaian = 'beda';
        }
        foreach ($cr['detail_comp'] as $crd) {
            $count_item_resep++;
            $stat_total_items++;
            if ($crd['status'] === 'match') {
                $stat_match_items++;
            } else {
                $stat_diff_items++;
                $count_diff_resep++;
                $resep_status_kesesuaian = 'beda';
            }
        }
    }

    if ($resep_status_kesesuaian === 'cocok') {
        $stat_resep_cocok++;
    } else {
        $stat_resep_beda++;
    }

    // Filter status jika dipilih
    if ($filter_status === 'cocok' && $resep_status_kesesuaian !== 'cocok') continue;
    if ($filter_status === 'beda' && $resep_status_kesesuaian !== 'beda') continue;

    $r['comp_non']                = $comp_non;
    $r['comp_rac']                = $comp_rac;
    $r['kesesuaian']             = $resep_status_kesesuaian;
    $r['count_items']            = $count_item_resep;
    $r['count_diff']             = $count_diff_resep;
    $verified_reseps[]           = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💊 Verifikasi Resep Dokter — <?= htmlspecialchars($nama_instansi) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{
            font-family:'Poppins',sans-serif;
            background:linear-gradient(135deg,#eef5f2 0%,#f0f9ff 50%,#f8fafc 100%);
            min-height:100vh;padding:20px;color:#1e293b;
        }
        .page-wrap{max-width:1600px;margin:0 auto;}

        /* Header Navigation */
        .top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;}
        .back-btn{
            display:inline-flex;align-items:center;gap:8px;padding:10px 22px;
            background:linear-gradient(135deg,#475569,#334155);color:#fff;
            text-decoration:none;border-radius:10px;font-weight:600;font-size:14px;
            transition:all .3s;box-shadow:0 4px 14px rgba(71,85,105,.25);
        }
        .back-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(71,85,105,.35);}
        .quick-actions{display:flex;gap:10px;}
        .btn-action{
            padding:9px 16px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;
            font-size:13px;font-weight:600;color:#334155;cursor:pointer;display:inline-flex;
            align-items:center;gap:6px;transition:all .2s;font-family:inherit;
        }
        .btn-action:hover{background:#f1f5f9;border-color:#94a3b8;}

        /* Main Page Header Banner */
        .page-header{
            background:linear-gradient(135deg,#059669 0%,#0d9488 50%,#0284c7 100%);
            color:#fff;padding:26px 30px;border-radius:16px;
            box-shadow:0 10px 30px rgba(13,148,136,.25);margin-bottom:22px;
            display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;
        }
        .header-brand{display:flex;align-items:center;gap:16px;}
        .header-brand img{height:54px;border-radius:8px;background:rgba(255,255,255,.9);padding:3px;}
        .header-title h1{font-size:1.65rem;font-weight:700;letter-spacing:-.3px;display:flex;align-items:center;gap:10px;}
        .header-title p{opacity:.9;margin-top:4px;font-size:.9rem;}

        /* Filter Card */
        .filter-card{
            background:#fff;border-radius:14px;padding:20px 24px;
            box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid #e2e8f0;margin-bottom:20px;
        }
        .filter-row{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;}
        .filter-group{display:flex;flex-direction:column;gap:4px;}
        .filter-group label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;}
        .filter-group input, .filter-group select{
            padding:9px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:13.5px;
            font-family:inherit;transition:all .25s;
        }
        .filter-group input:focus, .filter-group select:focus{
            outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.12);
        }
        .btn-cari{
            padding:9px 24px;background:linear-gradient(135deg,#059669,#0d9488);
            color:#fff;border:none;border-radius:8px;font-weight:600;font-size:13.5px;
            cursor:pointer;transition:all .3s;font-family:inherit;
            box-shadow:0 4px 12px rgba(5,150,105,.25);display:inline-flex;align-items:center;gap:6px;
        }
        .btn-cari:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(5,150,105,.35);}
        .btn-reset{
            padding:9px 16px;background:#f8fafc;color:#475569;border:2px solid #e2e8f0;
            border-radius:8px;font-weight:600;font-size:13.5px;cursor:pointer;
            transition:all .2s;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:6px;
        }
        .btn-reset:hover{background:#e2e8f0;}

        /* Statistics Cards */
        .stats-grid{
            display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px;
        }
        .stat-card{
            background:#fff;border-radius:12px;padding:16px 18px;border:1px solid #e2e8f0;
            display:flex;align-items:center;gap:14px;box-shadow:0 2px 8px rgba(0,0,0,.03);
        }
        .stat-icon{
            width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;
            font-size:20px;
        }
        .stat-icon.blue{background:#eff6ff;color:#2563eb;}
        .stat-icon.green{background:#ecfdf5;color:#059669;}
        .stat-icon.amber{background:#fffbeb;color:#d97706;}
        .stat-icon.purple{background:#f5f3ff;color:#7c3aed;}
        .stat-info .num{font-size:22px;font-weight:700;line-height:1.1;}
        .stat-info .lbl{font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;margin-top:3px;}

        /* Prescription Card (Accordion) */
        .resep-card{
            background:#fff;border-radius:14px;border:1px solid #e2e8f0;margin-bottom:18px;
            box-shadow:0 2px 10px rgba(0,0,0,.04);overflow:hidden;transition:all .25s;
        }
        .resep-card:hover{box-shadow:0 4px 18px rgba(0,0,0,.08);border-color:#cbd5e1;}
        
        .resep-header{
            padding:16px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;
            display:flex;justify-content:space-between;align-items:center;cursor:pointer;
            user-select:none;transition:background .2s;flex-wrap:wrap;gap:10px;
        }
        .resep-header:hover{background:#f1f5f9;}
        .resep-card.status-cocok .resep-header{border-left:5px solid #10b981;}
        .resep-card.status-beda .resep-header{border-left:5px solid #f59e0b;}

        .resep-main-info{display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
        .resep-no{
            font-size:15px;font-weight:700;color:#0f172a;display:inline-flex;align-items:center;gap:6px;
        }
        .resep-meta{font-size:13px;color:#475569;display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
        .resep-meta b{color:#1e293b;}
        .resep-badges{display:flex;align-items:center;gap:8px;}

        .badge{
            display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;
            font-size:11px;font-weight:600;text-transform:capitalize;
        }
        .badge-ralan{background:#dbeafe;color:#1d4ed8;}
        .badge-ranap{background:#fce7f3;color:#be185d;}
        .badge-cocok{background:#dcfce7;color:#15803d;}
        .badge-beda{background:#fef3c7;color:#b45309;}

        .resep-toggle-icon{
            font-size:15px;color:#64748b;transition:transform .3s;margin-left:8px;
        }
        .resep-card.collapsed .resep-toggle-icon{transform:rotate(-90deg);}
        .resep-card.collapsed .resep-body{display:none;}

        /* Resep Body — Side-by-Side Comparison */
        .resep-body{padding:18px 20px;}

        /* Detail Table */
        .comp-table-wrap{
            border:1px solid #e2e8f0;border-radius:10px;overflow-x:auto;margin-bottom:12px;
        }
        .comp-table{width:100%;border-collapse:collapse;font-size:13px;}
        
        /* Master Table Headers */
        .comp-table thead tr:first-child th{
            padding:10px 12px;font-size:12px;font-weight:700;text-transform:uppercase;
            letter-spacing:.4px;
        }
        .comp-table thead tr:nth-child(2) th{
            padding:7px 10px;font-size:11px;font-weight:600;text-transform:uppercase;
            border-bottom:2px solid #cbd5e1;
        }
        
        .th-side-resep{background:#ecfdf5;color:#065f46;border-right:1px solid #cbd5e1;text-align:center;}
        .th-side-status{background:#f8fafc;color:#475569;border-right:1px solid #cbd5e1;text-align:center;width:60px;}
        .th-side-farmasi{background:#eff6ff;color:#1e40af;text-align:center;}

        .th-sub-resep{background:#f0fdf4;color:#047857;}
        .th-sub-status{background:#f8fafc;color:#475569;}
        .th-sub-farmasi{background:#f0f7ff;color:#1d4ed8;}

        .comp-table td{
            padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:12.5px;
        }
        .comp-table tbody tr:hover{background:#fafaf9;}

        /* Status Rows */
        .tr-match{background:#f9fefb;}
        .tr-mismatch{background:#fffdf5;}
        .tr-missing{background:#fff8f8;}
        .tr-extra{background:#fff8f8;}

        .diff-tag{
            display:inline-block;font-weight:700;color:#b45309;
            background:#fef3c7;padding:1px 6px;border-radius:4px;
        }

        /* Status Icon Pill */
        .pill-status{
            display:inline-flex;align-items:center;justify-content:center;
            width:28px;height:28px;border-radius:50%;font-size:13px;
        }
        .pill-match{background:#dcfce7;color:#16a34a;}
        .pill-mismatch{background:#fef3c7;color:#d97706;}
        .pill-missing{background:#fee2e2;color:#dc2626;}

        /* Racikan Section */
        .racik-box{
            background:#fcfcfd;border:1px solid #e2e8f0;border-radius:10px;
            padding:14px 16px;margin-bottom:12px;
        }
        .racik-top{
            display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;
            padding-bottom:8px;border-bottom:1px dashed #cbd5e1;flex-wrap:wrap;gap:8px;
        }
        .racik-name{font-size:13.5px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:6px;}
        .racik-meta-grid{
            display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:10px;font-size:12px;
        }
        .racik-meta-side{padding:8px 12px;border-radius:6px;}
        .meta-side-resep{background:#ecfdf5;border:1px solid #bbf7d0;}
        .meta-side-farmasi{background:#eff6ff;border:1px solid #bfdbfe;}

        /* Pagination */
        .pagination{
            display:flex;justify-content:center;align-items:center;gap:6px;margin:24px 0;flex-wrap:wrap;
        }
        .page-link{
            padding:8px 14px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;
            color:#334155;text-decoration:none;font-weight:600;font-size:13px;transition:all .2s;
        }
        .page-link:hover{background:#f1f5f9;border-color:#94a3b8;}
        .page-link.active{background:#0d9488;color:#fff;border-color:#0d9488;}
        .page-link.disabled{opacity:.4;pointer-events:none;}

        /* Empty state */
        .empty-state{
            background:#fff;border-radius:14px;padding:50px 20px;text-align:center;
            color:#64748b;border:1px solid #e2e8f0;
        }
        .empty-state i{font-size:44px;color:#cbd5e1;margin-bottom:14px;}

        /* Print Style */
        @media print{
            body{background:#fff;padding:0;}
            .top-bar,.filter-card,.pagination,.quick-actions,.resep-toggle-icon{display:none!important;}
            .page-header{background:#059669!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;padding:16px;}
            .resep-card{box-shadow:none;border:1px solid #94a3b8;margin-bottom:14px;page-break-inside:avoid;}
            .resep-body{display:block!important;}
        }
    </style>
</head>
<body>
<div class="page-wrap">

    <div class="top-bar">
        <a href="farmasi.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Menu Farmasi</a>
        <div class="quick-actions">
            <button class="btn-action" onclick="toggleAllCards(false)"><i class="fas fa-expand-alt"></i> Buka Semua</button>
            <button class="btn-action" onclick="toggleAllCards(true)"><i class="fas fa-compress-alt"></i> Tutup Semua</button>
            <button class="btn-action" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
        </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-brand">
            <img src="<?= htmlspecialchars($logo_src) ?>" alt="Logo">
            <div class="header-title">
                <h1><i class="fas fa-check-double"></i> Verifikasi Resep Dokter</h1>
                <p>Komparasi langsung per obat: Sisi Kiri (Resep Dokter) vs Sisi Kanan (Validasi Farmasi)</p>
            </div>
        </div>
        <div>
            <span style="background:rgba(255,255,255,.2);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;">
                <i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($tgl1)) ?> s/d <?= date('d M Y', strtotime($tgl2)) ?>
            </span>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="filter-card">
        <form method="GET" class="filter-row">
            <div class="filter-group">
                <label>Tanggal Resep Dari</label>
                <input type="date" name="tgl1" value="<?= htmlspecialchars($tgl1) ?>">
            </div>
            <div class="filter-group">
                <label>Tanggal Resep Sampai</label>
                <input type="date" name="tgl2" value="<?= htmlspecialchars($tgl2) ?>">
            </div>
            <div class="filter-group">
                <label>Status Kesesuaian</label>
                <select name="filter_status">
                    <option value="semua" <?= $filter_status==='semua'?'selected':'' ?>>Semua Resep</option>
                    <option value="beda" <?= $filter_status==='beda'?'selected':'' ?>>⚠️ Ada Perbedaan / Selisih</option>
                    <option value="cocok" <?= $filter_status==='cocok'?'selected':'' ?>>✅ 100% Sesuai Saja</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Pencarian</label>
                <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                       placeholder="No.Resep / RM / Pasien / Dokter" style="min-width:260px;">
            </div>
            <div class="filter-group">
                <label>Tampilkan per Hal</label>
                <select name="limit">
                    <option value="20" <?= $limit==20?'selected':'' ?>>20 Resep</option>
                    <option value="30" <?= $limit==30?'selected':'' ?>>30 Resep</option>
                    <option value="50" <?= $limit==50?'selected':'' ?>>50 Resep</option>
                    <option value="100" <?= $limit==100?'selected':'' ?>>100 Resep</option>
                </select>
            </div>
            <button type="submit" class="btn-cari"><i class="fas fa-search"></i> Tampilkan</button>
            <a href="verifikasi_resep_dokter.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
        </form>
    </div>

    <!-- Stat Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-receipt"></i></div>
            <div class="stat-info">
                <div class="num"><?= number_format($total_rows) ?></div>
                <div class="lbl">Total Resep Terproses</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="num"><?= number_format($stat_resep_cocok) ?></div>
                <div class="lbl">Resep Sesuai Sempurna</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <div class="num"><?= number_format($stat_resep_beda) ?></div>
                <div class="lbl">Resep Ada Perbedaan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-pills"></i></div>
            <div class="stat-info">
                <div class="num"><?= number_format($stat_total_items) ?></div>
                <div class="lbl">Obat Diperiksa (Hal ini)</div>
            </div>
        </div>
    </div>

    <!-- Daftar Resep Lengkap dengan Data Obat Side-by-Side -->
    <?php if (empty($verified_reseps)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>Tidak Ada Data Resep Ditemukan</h3>
            <p style="margin-top:6px;font-size:13.5px;">Silakan sesuaikan filter tanggal atau kata kunci pencarian Anda.</p>
        </div>
    <?php else: ?>
        <?php foreach ($verified_reseps as $idx => $r):
            $is_cocok = ($r['kesesuaian'] === 'cocok');
            $statusRawatClass = ($r['status'] === 'ralan') ? 'badge-ralan' : 'badge-ranap';
            $statusRawatLabel = ucfirst($r['status']);
        ?>
        <div class="resep-card status-<?= $r['kesesuaian'] ?>" id="card-<?= htmlspecialchars($r['no_resep']) ?>">
            
            <!-- Prescription Header Banner -->
            <div class="resep-header" onclick="toggleCard('card-<?= htmlspecialchars($r['no_resep']) ?>')">
                <div class="resep-main-info">
                    <span class="resep-no">
                        <i class="fas fa-prescription" style="color:#0d9488;"></i> <?= htmlspecialchars($r['no_resep']) ?>
                    </span>
                    <span class="badge <?= $statusRawatClass ?>"><?= $statusRawatLabel ?></span>
                    <div class="resep-meta">
                        <span><i class="far fa-user"></i> <b><?= htmlspecialchars($r['nm_pasien']) ?></b> (No.RM: <?= htmlspecialchars($r['no_rkm_medis']) ?>)</span>
                        <span><i class="fas fa-user-md"></i> Dokter: <b><?= htmlspecialchars($r['nm_dokter']) ?></b></span>
                        <span><i class="far fa-clock"></i> Resep: <?= htmlspecialchars($r['tgl_peresepan']) ?> <?= htmlspecialchars($r['jam_peresepan']) ?></span>
                        <span><i class="fas fa-check"></i> Validasi Farmasi: <?= htmlspecialchars($r['tgl_perawatan']) ?> <?= htmlspecialchars($r['jam']) ?></span>
                    </div>
                </div>

                <div class="resep-badges">
                    <?php if ($is_cocok): ?>
                        <span class="badge badge-cocok"><i class="fas fa-check-circle"></i> Sesuai Sempurna</span>
                    <?php else: ?>
                        <span class="badge badge-beda"><i class="fas fa-exclamation-triangle"></i> Ada Perbedaan (<?= $r['count_diff'] ?>)</span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down resep-toggle-icon"></i>
                </div>
            </div>

            <!-- Body: Obat Side-by-Side Comparison -->
            <div class="resep-body">

                <!-- 1. Obat Non-Racikan Table -->
                <?php if (!empty($r['comp_non'])): ?>
                <div style="font-size:12.5px;font-weight:700;color:#334155;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-pills" style="color:#0d9488;"></i> Obat Non-Racikan
                </div>
                <div class="comp-table-wrap">
                    <table class="comp-table">
                        <thead>
                            <tr>
                                <th colspan="4" class="th-side-resep"><i class="fas fa-file-prescription"></i> Resep Dokter</th>
                                <th rowspan="2" class="th-side-status"><i class="fas fa-exchange-alt"></i></th>
                                <th colspan="3" class="th-side-farmasi"><i class="fas fa-prescription-bottle-alt"></i> Validasi Farmasi</th>
                            </tr>
                            <tr>
                                <th class="th-sub-resep" style="text-align:left;">Kode / Nama Obat</th>
                                <th class="th-sub-resep" style="text-align:center;width:70px;">Satuan</th>
                                <th class="th-sub-resep" style="text-align:center;width:80px;">Jml Resep</th>
                                <th class="th-sub-resep" style="text-align:left;">Aturan Pakai Resep</th>
                                <th class="th-sub-farmasi" style="text-align:center;width:80px;">Jml Diberikan</th>
                                <th class="th-sub-farmasi" style="text-align:left;">Aturan Pakai Farmasi</th>
                                <th class="th-sub-farmasi" style="text-align:right;width:100px;">Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($r['comp_non'] as $item):
                                $res_item = $item['resep'];
                                $far_item = $item['farmasi'];
                                $status   = $item['status'];
                                
                                $jml_beda  = ($res_item && $far_item && (float)$res_item['jml'] !== (float)$far_item['jml']);
                                $atur_beda = ($res_item && $far_item && normalizeAturan($res_item['aturan_pakai']??'') !== normalizeAturan($far_item['aturan_pakai']??''));
                            ?>
                            <tr class="tr-<?= $status ?>">
                                <td style="text-align:left;">
                                    <b><?= htmlspecialchars($item['kode_brng']) ?></b> — <?= htmlspecialchars($item['nama_brng']) ?>
                                </td>
                                <td style="text-align:center;"><?= htmlspecialchars($item['kode_sat'] ?? '-') ?></td>
                                <td style="text-align:center;">
                                    <?php if ($res_item): ?>
                                        <span class="<?= $jml_beda ? 'diff-tag' : '' ?>">
                                            <?= (float)$res_item['jml'] == (int)$res_item['jml'] ? (int)$res_item['jml'] : $res_item['jml'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;font-style:italic;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:left;">
                                    <?php if ($res_item): ?>
                                        <span class="<?= $atur_beda ? 'diff-tag' : '' ?>">
                                            <?= htmlspecialchars(!empty($res_item['aturan_pakai']) ? $res_item['aturan_pakai'] : '-') ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;font-style:italic;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php if ($status === 'match'): ?>
                                        <span class="pill-status pill-match" title="Sesuai Sempurna"><i class="fas fa-check"></i></span>
                                    <?php elseif ($status === 'mismatch'): ?>
                                        <span class="pill-status pill-mismatch" title="Ada Perbedaan Jumlah/Aturan"><i class="fas fa-exclamation"></i></span>
                                    <?php elseif ($status === 'missing'): ?>
                                        <span class="pill-status pill-missing" title="Ada di Resep tapi Belum Diberikan Farmasi"><i class="fas fa-arrow-left"></i></span>
                                    <?php else: ?>
                                        <span class="pill-status pill-missing" title="Diberikan Farmasi tapi Tidak di Resep Dokter"><i class="fas fa-arrow-right"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php if ($far_item): ?>
                                        <span class="<?= $jml_beda ? 'diff-tag' : '' ?>">
                                            <?= (float)$far_item['jml'] == (int)$far_item['jml'] ? (int)$far_item['jml'] : $far_item['jml'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#ef4444;font-style:italic;font-weight:600;">Belum diberikan</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:left;">
                                    <?php if ($far_item): ?>
                                        <span class="<?= $atur_beda ? 'diff-tag' : '' ?>">
                                            <?= htmlspecialchars(!empty($far_item['aturan_pakai']) ? $far_item['aturan_pakai'] : '-') ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;font-style:italic;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($far_item && isset($far_item['total'])): ?>
                                        Rp <?= number_format($far_item['total'], 0, ',', '.') ?>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- 2. Obat Racikan Section -->
                <?php if (!empty($r['comp_rac'])): ?>
                <div style="font-size:12.5px;font-weight:700;color:#334155;margin-top:14px;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-mortar-pestle" style="color:#0284c7;"></i> Obat Racikan
                </div>
                <?php foreach ($r['comp_rac'] as $rac):
                    $r_rac = $rac['resep'];
                    $f_rac = $rac['farmasi'];
                    $status_rac = $rac['status'];
                ?>
                <div class="racik-box">
                    <div class="racik-top">
                        <span class="racik-name">
                            <i class="fas fa-blender" style="color:#0284c7;"></i> 
                            <?= htmlspecialchars($r_rac['nama_racik'] ?? $f_rac['nama_racik'] ?? 'Racikan') ?>
                            (No.Racik: <?= htmlspecialchars($r_rac['no_racik'] ?? $f_rac['no_racik'] ?? '-') ?>)
                        </span>
                        <div>
                            <?php if ($status_rac === 'match'): ?>
                                <span class="badge badge-cocok"><i class="fas fa-check-circle"></i> Racikan Cocok</span>
                            <?php else: ?>
                                <span class="badge badge-beda"><i class="fas fa-exclamation-triangle"></i> Ada Perbedaan Racikan</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Side-by-side header racikan info -->
                    <div class="racik-meta-grid">
                        <div class="racik-meta-side meta-side-resep">
                            <div style="font-weight:700;color:#065f46;margin-bottom:4px;"><i class="fas fa-file-prescription"></i> Header Resep Dokter:</div>
                            <?php if ($r_rac): ?>
                                <div><b>Metode:</b> <?= htmlspecialchars($r_rac['metode'] ?? '-') ?> | <b>Jml:</b> <?= htmlspecialchars($r_rac['jml_dr'] ?? '0') ?></div>
                                <div><b>Aturan:</b> <?= htmlspecialchars($r_rac['aturan_pakai'] ?? '-') ?> | <b>Ket:</b> <?= htmlspecialchars($r_rac['keterangan'] ?? '-') ?></div>
                            <?php else: ?>
                                <em style="color:#94a3b8;">Tidak ada dalam resep dokter</em>
                            <?php endif; ?>
                        </div>
                        <div class="racik-meta-side meta-side-farmasi">
                            <div style="font-weight:700;color:#1e40af;margin-bottom:4px;"><i class="fas fa-prescription-bottle-alt"></i> Header Validasi Farmasi:</div>
                            <?php if ($f_rac): ?>
                                <div><b>Metode:</b> <?= htmlspecialchars($f_rac['metode'] ?? '-') ?> | <b>Jml:</b> <?= htmlspecialchars($f_rac['jml_dr'] ?? '0') ?></div>
                                <div><b>Aturan:</b> <?= htmlspecialchars($f_rac['aturan_pakai'] ?? '-') ?> | <b>Ket:</b> <?= htmlspecialchars($f_rac['keterangan'] ?? '-') ?></div>
                            <?php else: ?>
                                <em style="color:#ef4444;font-weight:600;">Belum diberikan farmasi</em>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Detail rincian obat dalam racikan -->
                    <?php if (!empty($rac['detail_comp'])): ?>
                    <div class="comp-table-wrap">
                        <table class="comp-table">
                            <thead>
                                <tr>
                                    <th colspan="3" class="th-side-resep"><i class="fas fa-mortar-pestle"></i> Komposisi Resep Dokter</th>
                                    <th rowspan="2" class="th-side-status"><i class="fas fa-exchange-alt"></i></th>
                                    <th colspan="3" class="th-side-farmasi"><i class="fas fa-mortar-pestle"></i> Komposisi Diberikan Farmasi</th>
                                </tr>
                                <tr>
                                    <th class="th-sub-resep" style="text-align:left;">Nama Bahan Obat</th>
                                    <th class="th-sub-resep" style="text-align:center;width:70px;">Satuan</th>
                                    <th class="th-sub-resep" style="text-align:center;width:80px;">Jml Resep</th>
                                    <th class="th-sub-farmasi" style="text-align:center;width:80px;">Jml Diberikan</th>
                                    <th class="th-sub-farmasi" style="text-align:center;width:70px;">Satuan</th>
                                    <th class="th-sub-farmasi" style="text-align:right;width:100px;">Total Biaya</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rac['detail_comp'] as $dc):
                                    $dr = $dc['resep'];
                                    $df = $dc['farmasi'];
                                    $st = $dc['status'];
                                    $jml_sub_diff = ($dr && $df && (float)$dr['jml'] !== (float)$df['jml']);
                                ?>
                                <tr class="tr-<?= $st ?>">
                                    <td style="text-align:left;">
                                        <b><?= htmlspecialchars($dc['kode_brng']) ?></b> — <?= htmlspecialchars($dc['nama_brng']) ?>
                                    </td>
                                    <td style="text-align:center;"><?= htmlspecialchars($dc['kode_sat'] ?? '-') ?></td>
                                    <td style="text-align:center;">
                                        <?php if ($dr): ?>
                                            <span class="<?= $jml_sub_diff ? 'diff-tag' : '' ?>">
                                                <?= (float)$dr['jml'] == (int)$dr['jml'] ? (int)$dr['jml'] : $dr['jml'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#cbd5e1;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($st === 'match'): ?>
                                            <span class="pill-status pill-match"><i class="fas fa-check"></i></span>
                                        <?php elseif ($st === 'mismatch'): ?>
                                            <span class="pill-status pill-mismatch"><i class="fas fa-exclamation"></i></span>
                                        <?php else: ?>
                                            <span class="pill-status pill-missing"><i class="fas fa-times"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($df): ?>
                                            <span class="<?= $jml_sub_diff ? 'diff-tag' : '' ?>">
                                                <?= (float)$df['jml'] == (int)$df['jml'] ? (int)$df['jml'] : $df['jml'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#ef4444;font-style:italic;">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;"><?= htmlspecialchars($df['kode_sat'] ?? ($dr['kode_sat'] ?? '-')) ?></td>
                                    <td style="text-align:right;">
                                        <?php if ($df && isset($df['total'])): ?>
                                            Rp <?= number_format($df['total'], 0, ',', '.') ?>
                                        <?php else: ?>
                                            <span style="color:#cbd5e1;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if (empty($r['comp_non']) && empty($r['comp_rac'])): ?>
                    <div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">
                        <i class="fas fa-info-circle"></i> Tidak ada rincian obat ditemukan untuk nomor resep ini.
                    </div>
                <?php endif; ?>

            </div>

        </div>
        <?php endforeach; ?>

        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            $query_params = $_GET;
            ?>
            <a href="?<?= http_build_query(array_merge($query_params, ['page' => max(1, $page - 1)])) ?>"
               class="page-link <?= $page <= 1 ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i> Sebelumnya</a>

            <?php
            $start_p = max(1, $page - 2);
            $end_p   = min($total_pages, $page + 2);
            for ($p = $start_p; $p <= $end_p; $p++):
            ?>
                <a href="?<?= http_build_query(array_merge($query_params, ['page' => $p])) ?>"
                   class="page-link <?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>

            <a href="?<?= http_build_query(array_merge($query_params, ['page' => min($total_pages, $page + 1)])) ?>"
               class="page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">Selanjutnya <i class="fas fa-chevron-right"></i></a>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<script>
function toggleCard(cardId) {
    const card = document.getElementById(cardId);
    if (card) {
        card.classList.toggle('collapsed');
    }
}

function toggleAllCards(collapse) {
    const cards = document.querySelectorAll('.resep-card');
    cards.forEach(card => {
        if (collapse) {
            card.classList.add('collapsed');
        } else {
            card.classList.remove('collapsed');
        }
    });
}
</script>
</body>
</html>
