<?php
/**
 * Halaman Buat SEP (Surat Eligibilitas Peserta) BPJS VClaim 2.0
 * RSUD Pringsewu
 */

require_once 'koneksi.php';
require_once 'vendor/autoload.php';

// Ambil logo instansi
$query_instansi = "SELECT nama_instansi, logo, alamat_instansi, kabupaten, propinsi FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU";
$alamat_instansi = "Jl. Lintas Barat Pekon Fajar Agung Barat, Pringsewu";
$logo_src = "images/logo.png";

if ($result_instansi && $row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'] ?? $nama_instansi;
    $alamat_instansi = $row_instansi['alamat_instansi'] ?? $alamat_instansi;
    if (!empty($row_instansi['logo'])) {
        $logo_src = "data:image/png;base64," . base64_encode($row_instansi['logo']);
    }
}

// Handler AJAX Endpoint
if (isset($_REQUEST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_REQUEST['ajax_action'];
    $mode = $_REQUEST['api_mode'] ?? 'prod'; // 'prod' atau 'dev'

    // Tentukan Kredensial dari koneksi.php
    $vclaim_url = $URLVCLAIM ?? '';
    $vclaim_consid = $CONSIDVCLAIM ?? '';
    $vclaim_secret = $SECRETKEYVCLAIM ?? '';
    $vclaim_userkey = $USERKEYVCLAIM ?? '';
    $ppk_pelayanan = $KODEPPKAPLICARE ?? '0807R006';

    // Helper fungsi kirim request VClaim
    $callVClaim = function($endpoint, $method = 'GET', $payload = null) use ($vclaim_url, $vclaim_consid, $vclaim_secret, $vclaim_userkey) {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $signature = base64_encode(hash_hmac('sha256', $vclaim_consid . '&' . $tStamp, $vclaim_secret, true));

        $headers = [
            'X-cons-id: ' . $vclaim_consid,
            'X-timestamp: ' . $tStamp,
            'X-signature: ' . $signature,
            'user_key: ' . $vclaim_userkey,
            'Content-Type: application/json'
        ];

        $targetUrl = rtrim($vclaim_url, '/') . '/' . ltrim($endpoint, '/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($payload) ? json_encode($payload) : $payload);
        }

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['status' => false, 'message' => 'Koneksi cURL Error: ' . $curlError];
        }

        $json = json_decode($rawResponse, true);
        $meta = $json['metaData'] ?? null;
        $metaCode = (string)($meta['code'] ?? '');
        $metaMsg = $meta['message'] ?? 'Respon tidak dikenal';

        $decryptedData = null;
        if (isset($json['response']) && is_string($json['response'])) {
            try {
                $key = $vclaim_consid . $vclaim_secret . $tStamp;
                $keyHash = hex2bin(hash('sha256', $key));
                $iv = substr($keyHash, 0, 16);
                $aes = openssl_decrypt(base64_decode($json['response']), 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, $iv);
                if ($aes !== false) {
                    $decomp = \LZCompressor\LZString::decompressFromEncodedURIComponent($aes);
                    $decryptedData = json_decode($decomp, true) ?? $decomp;
                }
            } catch (\Exception $e) {
                $decryptedData = null;
            }
        } elseif (isset($json['response'])) {
            $decryptedData = $json['response'];
        }

        return [
            'status' => ($metaCode === '200' || $metaCode === '1'),
            'code' => $metaCode,
            'message' => $metaMsg,
            'data' => $decryptedData,
            'raw' => $rawResponse
        ];
    };

    // 1. CARI PASIEN DARI SIMRS
    if ($action === 'cari_pasien') {
        $q = mysqli_real_escape_string($koneksi, trim($_GET['q'] ?? ''));
        if (empty($q)) {
            echo json_encode(['status' => false, 'message' => 'Kata kunci pencarian kosong!']);
            exit;
        }

        $sql = "SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_peserta, 
                       pasien.no_ktp, pasien.jk, pasien.tgl_lahir, pasien.no_tlp, reg_periksa.tgl_registrasi,
                       reg_periksa.status_lanjut, reg_periksa.kd_poli, poliklinik.nm_poli,
                       reg_periksa.kd_dokter, dokter.nm_dokter,
                       maping_poli_bpjs.kd_poli_bpjs, maping_poli_bpjs.nm_poli_bpjs,
                       maping_dokter_dpjpvclaim.kd_dokter_bpjs, maping_dokter_dpjpvclaim.nm_dokter_bpjs
                FROM reg_periksa 
                INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                LEFT JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                LEFT JOIN maping_poli_bpjs ON reg_periksa.kd_poli = maping_poli_bpjs.kd_poli_rs
                LEFT JOIN maping_dokter_dpjpvclaim ON reg_periksa.kd_dokter = maping_dokter_dpjpvclaim.kd_dokter
                WHERE reg_periksa.no_rawat LIKE '%$q%' 
                   OR reg_periksa.no_rkm_medis LIKE '%$q%' 
                   OR pasien.nm_pasien LIKE '%$q%'
                   OR pasien.no_peserta LIKE '%$q%'
                   OR pasien.no_ktp LIKE '%$q%'
                ORDER BY reg_periksa.tgl_registrasi DESC, reg_periksa.jam_reg DESC 
                LIMIT 15";

        $res = mysqli_query($koneksi, $sql);
        $list = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $list[] = $row;
        }

        echo json_encode(['status' => true, 'data' => $list]);
        exit;
    }

    // 2. CEK RUJUKAN DARI BPJS (FKTP & RS)
    if ($action === 'cari_rujukan') {
        $noKartu = trim($_GET['no_kartu'] ?? '');
        if (empty($noKartu)) {
            echo json_encode(['status' => false, 'message' => 'Nomor kartu BPJS kosong!']);
            exit;
        }

        // Coba ambil list rujukan FKTP terlebih dahulu
        $respFaskes1 = $callVClaim("Rujukan/List/Peserta/{$noKartu}");
        $rujukanList = [];

        if ($respFaskes1['status'] && isset($respFaskes1['data']['rujukan'])) {
            foreach ($respFaskes1['data']['rujukan'] as $r) {
                $r['asalFaskes'] = '1';
                $r['asalFaskesText'] = 'Faskes Tingkat 1 (Puskesmas/Klinik/Dokter)';
                $rujukanList[] = $r;
            }
        } elseif (isset($respFaskes1['data']['rujukan']['noKunjungan'])) {
            $r = $respFaskes1['data']['rujukan'];
            $r['asalFaskes'] = '1';
            $r['asalFaskesText'] = 'Faskes Tingkat 1';
            $rujukanList[] = $r;
        }

        // Coba ambil rujukan antar RS (Faskes 2)
        $respFaskes2 = $callVClaim("Rujukan/RS/List/Peserta/{$noKartu}");
        if ($respFaskes2['status'] && isset($respFaskes2['data']['rujukan'])) {
            foreach ($respFaskes2['data']['rujukan'] as $r) {
                $r['asalFaskes'] = '2';
                $r['asalFaskesText'] = 'Faskes Tingkat 2 (Rumah Sakit)';
                $rujukanList[] = $r;
            }
        } elseif (isset($respFaskes2['data']['rujukan']['noKunjungan'])) {
            $r = $respFaskes2['data']['rujukan'];
            $r['asalFaskes'] = '2';
            $r['asalFaskesText'] = 'Faskes Tingkat 2 (RS)';
            $rujukanList[] = $r;
        }

        // Ambil data kepesertaan peserta
        $today = date('Y-m-d');
        $pesertaData = $callVClaim("Peserta/nokartu/{$noKartu}/tglSEP/{$today}");

        echo json_encode([
            'status' => true,
            'rujukan_count' => count($rujukanList),
            'rujukan' => $rujukanList,
            'peserta' => $pesertaData['data']['peserta'] ?? null,
            'faskes1_meta' => $respFaskes1['message'],
            'faskes2_meta' => $respFaskes2['message']
        ]);
        exit;
    }

    // 3. CARI REFERENSI DIAGNOSA ICD-10
    if ($action === 'cari_diagnosa') {
        $diag = urlencode(trim($_GET['q'] ?? ''));
        if (strlen($diag) < 2) {
            echo json_encode(['status' => false, 'message' => 'Masukkan minimal 2 karakter']);
            exit;
        }
        $resp = $callVClaim("referensi/diagnosa/{$diag}");
        echo json_encode($resp);
        exit;
    }

    // 4. CARI REFERENSI POLI BPJS
    if ($action === 'cari_poli') {
        $poli = urlencode(trim($_GET['q'] ?? ''));
        $resp = $callVClaim("referensi/poli/{$poli}");
        echo json_encode($resp);
        exit;
    }

    // 5. CARI REFERENSI DOKTER DPJP BPJS
    if ($action === 'cari_dpjp') {
        $jnsPelayanan = $_GET['jns_pelayanan'] ?? '2';
        $tgl = $_GET['tgl_sep'] ?? date('Y-m-d');
        $kdPoli = $_GET['kd_poli'] ?? '';
        if (empty($kdPoli)) {
            echo json_encode(['status' => false, 'message' => 'Pilih Poli terlebih dahulu!']);
            exit;
        }
        $resp = $callVClaim("referensi/dokter/pelayanan/{$jnsPelayanan}/tglPelayanan/{$tgl}/Spesialis/{$kdPoli}");
        echo json_encode($resp);
        exit;
    }

    // 6. PROSES SUBMIT / BUAT SEP KE VCLAIM 2.0
    if ($action === 'submit_sep') {
        // Ambil payload dari form POST
        $noKartu = trim($_POST['no_kartu'] ?? '');
        $noMr = trim($_POST['no_mr'] ?? '');
        $noRawat = trim($_POST['no_rawat'] ?? '');
        $tglSep = trim($_POST['tgl_sep'] ?? date('Y-m-d'));
        $jnsPelayanan = trim($_POST['jns_pelayanan'] ?? '2'); // 1 = Ranap, 2 = Ralan
        $klsRawatHak = trim($_POST['kls_rawat_hak'] ?? '3');
        $asalRujukan = trim($_POST['asal_rujukan'] ?? '1'); // 1 = Faskes 1, 2 = Faskes 2
        $tglRujukan = trim($_POST['tgl_rujukan'] ?? $tglSep);
        $noRujukan = trim($_POST['no_rujukan'] ?? '');
        $ppkRujukan = trim($_POST['ppk_rujukan'] ?? '');
        $nmppkRujukan = trim($_POST['nm_ppk_rujukan'] ?? '');
        $catatan = trim($_POST['catatan'] ?? '-');
        $diagAwal = trim($_POST['diag_awal'] ?? '');
        $nmdiagAwal = trim($_POST['nm_diag_awal'] ?? '');
        $poliTujuan = trim($_POST['poli_tujuan'] ?? '');
        $nmpoliTujuan = trim($_POST['nm_poli_tujuan'] ?? '');
        $eksekutif = trim($_POST['eksekutif'] ?? '0');
        $cob = trim($_POST['cob'] ?? '0');
        $katarak = trim($_POST['katarak'] ?? '0');
        $lakaLantas = trim($_POST['laka_lantas'] ?? '0');
        $noSurat = trim($_POST['no_surat'] ?? '');
        $kodeDpjpSurat = trim($_POST['kode_dpjp_surat'] ?? '');
        $dpjpLayan = trim($_POST['dpjp_layan'] ?? '');
        $nmdpjpLayan = trim($_POST['nm_dpjp_layan'] ?? '');
        $noTelp = trim($_POST['no_telp'] ?? '08123456789');
        $tujuanKunj = trim($_POST['tujuan_kunj'] ?? '0');
        $flagProcedure = trim($_POST['flag_procedure'] ?? '');
        $kdPenunjang = trim($_POST['kd_penunjang'] ?? '');
        $assesmentPel = trim($_POST['assesment_pel'] ?? '');
        $userLogin = 'Petugas RS';

        // Validasi data penting
        if (empty($noKartu) || empty($noMr) || empty($diagAwal) || empty($poliTujuan) || empty($dpjpLayan)) {
            echo json_encode(['status' => false, 'message' => 'Lengkapi No Kartu, No RM, Diagnosa, Poli, dan Dokter DPJP!']);
            exit;
        }

        // Susun payload standar VClaim 2.0
        $sepPayload = [
            'request' => [
                't_sep' => [
                    'noKartu' => $noKartu,
                    'tglSep' => $tglSep,
                    'ppkPelayanan' => $ppk_pelayanan,
                    'jnsPelayanan' => $jnsPelayanan,
                    'klsRawat' => [
                        'klsRawatHak' => $klsRawatHak,
                        'klsRawatNaik' => '',
                        'pembiayaan' => '',
                        'penanggungJawab' => ''
                    ],
                    'noMR' => $noMr,
                    'rujukan' => [
                        'asalRujukan' => $asalRujukan,
                        'tglRujukan' => $tglRujukan,
                        'noRujukan' => $noRujukan,
                        'ppkRujukan' => $ppkRujukan
                    ],
                    'catatan' => $catatan ?: '-',
                    'diagAwal' => $diagAwal,
                    'poli' => [
                        'tujuan' => $poliTujuan,
                        'eksekutif' => $eksekutif
                    ],
                    'cob' => [
                        'cob' => $cob
                    ],
                    'katarak' => [
                        'katarak' => $katarak
                    ],
                    'jaminan' => [
                        'lakaLantas' => $lakaLantas,
                        'noLP' => '',
                        'penjamin' => [
                            'tglKejadian' => '',
                            'keterangan' => '',
                            'suplesi' => [
                                'suplesi' => '0',
                                'noSepSuplesi' => '',
                                'lokasiLaka' => [
                                    'kdPropinsi' => '',
                                    'kdKabupaten' => '',
                                    'kdKecamatan' => ''
                                ]
                            ]
                        ]
                    ],
                    'tujuanKunj' => $tujuanKunj,
                    'flagProcedure' => $flagProcedure,
                    'kdPenunjang' => $kdPenunjang,
                    'assesmentPel' => $assesmentPel,
                    'skdp' => [
                        'noSurat' => $noSurat,
                        'kodeDPJP' => $kodeDpjpSurat
                    ],
                    'dpjpLayan' => $dpjpLayan,
                    'noTelp' => $noTelp,
                    'user' => $userLogin
                ]
            ]
        ];

        // Kirim request ke VClaim
        $vclaimResult = $callVClaim('SEP/2.0/insert', 'POST', $sepPayload);

        if (!$vclaimResult['status']) {
            echo json_encode([
                'status' => false,
                'message' => "Gagal membuat SEP [{$vclaimResult['code']}]: {$vclaimResult['message']}",
                'details' => $vclaimResult
            ]);
            exit;
        }

        // Respon berhasil
        $sepData = $vclaimResult['data']['sep'] ?? null;
        $noSep = $sepData['noSep'] ?? '';

        if (empty($noSep)) {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mendapatkan Nomor SEP dari respon BPJS.',
                'raw' => $vclaimResult
            ]);
            exit;
        }

        // Simpan ke database SIMRS `bridging_sep`
        $namaPasien = mysqli_real_escape_string($koneksi, $sepData['peserta']['nama'] ?? $_POST['nama_pasien'] ?? '');
        $jkel = ($sepData['peserta']['jkel'] ?? 'P') === 'Laki-laki' || ($sepData['peserta']['jkel'] ?? '') === 'L' ? 'L' : 'P';
        $tglLahir = $sepData['peserta']['tglLahir'] ?? $_POST['tgl_lahir'] ?? '1970-01-01';
        $jenisPeserta = mysqli_real_escape_string($koneksi, $sepData['peserta']['jnsPeserta'] ?? 'BPJS KESEHATAN');
        $asalRujukanText = ($asalRujukan === '1') ? '1. Faskes 1' : '2. Faskes 2(RS)';
        $eksekutifText = ($eksekutif === '1') ? '1.Ya' : '0. Tidak';
        $cobText = ($cob === '1') ? '1.Ya' : '0. Tidak';
        $katarakText = ($katarak === '1') ? '1.Ya' : '0. Tidak';
        $nmppkPelayanan = mysqli_real_escape_string($koneksi, $nama_instansi);

        $insertSql = "INSERT INTO bridging_sep (
            no_sep, no_rawat, tglsep, tglrujukan, no_rujukan, kdppkrujukan, nmppkrujukan,
            kdppkpelayanan, nmppkpelayanan, jnspelayanan, catatan, diagawal, nmdiagnosaawal,
            kdpolitujuan, nmpolitujuan, klsrawat, lakalantas, user, nomr, nama_pasien,
            tanggal_lahir, peserta, jkel, no_kartu, tglpulang, asal_rujukan, eksekutif,
            cob, notelep, katarak, noskdp, kddpjp, nmdpdjp, tujuankunjungan, flagprosedur,
            penunjang, asesmenpelayanan, kddpjplayanan, nmdpjplayanan
        ) VALUES (
            '$noSep', '$noRawat', '$tglSep', '$tglRujukan', '$noRujukan', '$ppkRujukan', '$nmppkRujukan',
            '$ppk_pelayanan', '$nmppkPelayanan', '$jnsPelayanan', '$catatan', '$diagAwal', '$nmdiagAwal',
            '$poliTujuan', '$nmpoliTujuan', '$klsRawatHak', '$lakaLantas', '$userLogin', '$noMr', '$namaPasien',
            '$tglLahir', '$jenisPeserta', '$jkel', '$noKartu', '$tglSep 00:00:00', '$asalRujukanText', '$eksekutifText',
            '$cobText', '$noTelp', '$katarakText', '$noSurat', '$dpjpLayan', '$nmdpjpLayan', '$tujuanKunj', '$flagProcedure',
            '$kdPenunjang', '$assesmentPel', '$dpjpLayan', '$nmdpjpLayan'
        ) ON DUPLICATE KEY UPDATE 
            no_sep = '$noSep', tglsep = '$tglSep', diagawal = '$diagAwal', kdpolitujuan = '$poliTujuan'";

        $dbSave = mysqli_query($koneksi, $insertSql);
        $dbError = $dbSave ? '' : mysqli_error($koneksi);

        echo json_encode([
            'status' => true,
            'message' => 'SEP Berhasil Dibuat!',
            'no_sep' => $noSep,
            'sep' => $sepData,
            'db_saved' => $dbSave,
            'db_error' => $dbError
        ]);
        exit;
    }

    echo json_encode(['status' => false, 'message' => 'Aksi AJAX tidak dikenali.']);
    exit;
}

// Ambil Master Mapping Poli & Dokter dari database untuk dropdown
$poliOptions = [];
$resPoli = mysqli_query($koneksi, "SELECT kd_poli_rs, kd_poli_bpjs, nm_poli_bpjs FROM maping_poli_bpjs ORDER BY nm_poli_bpjs ASC");
while ($r = mysqli_fetch_assoc($resPoli)) {
    $poliOptions[] = $r;
}

$dokterOptions = [];
$resDokter = mysqli_query($koneksi, "SELECT kd_dokter, kd_dokter_bpjs, nm_dokter_bpjs FROM maping_dokter_dpjpvclaim ORDER BY nm_dokter_bpjs ASC");
while ($r = mysqli_fetch_assoc($resDokter)) {
    $dokterOptions[] = $r;
}

// Ambil 5 pasien terbaru hari ini untuk quick pick
$today = date('Y-m-d');
$quickPatients = [];
$resQuick = mysqli_query($koneksi, "SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_peserta, poliklinik.nm_poli, dokter.nm_dokter 
FROM reg_periksa 
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
LEFT JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter 
WHERE reg_periksa.tgl_registrasi = '$today' 
ORDER BY reg_periksa.jam_reg DESC LIMIT 5");
if ($resQuick) {
    while ($r = mysqli_fetch_assoc($resQuick)) {
        $quickPatients[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat SEP BPJS - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --bg-page: #eef2f7;
            --border: #d1d5db;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-page);
            color: #333;
            padding: 20px;
            font-size: 14px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: #fff;
            padding: 22px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title p {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 4px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-top {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-top:hover {
            background: rgba(255, 255, 255, 0.35);
            color: #fff;
        }

        /* Mode Switcher */
        .mode-box {
            background: rgba(255, 255, 255, 0.25);
            padding: 4px 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .mode-box select {
            background: #fff;
            color: #333;
            border: none;
            padding: 5px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
        }

        /* Body Layout */
        .content {
            padding: 25px 30px;
        }

        /* Step Card */
        .step-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 22px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .step-header {
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .step-header i {
            color: var(--primary);
            margin-right: 8px;
        }

        .step-body {
            padding: 18px 20px;
        }

        /* Form Grid */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
        }

        .form-group label span.req {
            color: var(--danger);
        }

        .input-group {
            display: flex;
            gap: 6px;
        }

        input[type="text"], input[type="date"], input[type="number"], select, textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 13.5px;
            font-family: inherit;
            transition: all 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        .btn-action {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 9px 14px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
            font-size: 13px;
        }

        .btn-action:hover {
            background: var(--primary-dark);
        }

        .btn-success {
            background: var(--success);
        }

        .btn-success:hover {
            background: #218838;
        }

        /* Patient Badge Info */
        .patient-info-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 14px 18px;
            margin-top: 12px;
            display: none;
        }

        .patient-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            font-size: 13px;
        }

        .patient-grid div strong {
            color: #166534;
        }

        /* Rujukan Cards */
        .rujukan-item {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 10px;
            cursor: pointer;
            transition: 0.2s;
        }

        .rujukan-item:hover {
            background: #dbeafe;
            border-color: #3b82f6;
        }

        .rujukan-item.active {
            background: #dbeafe;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary);
        }

        /* Submit Button Container */
        .submit-container {
            margin-top: 25px;
            text-align: right;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }

        .btn-submit-sep {
            background: linear-gradient(135deg, #198754, #20c997);
            color: #fff;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(25, 135, 84, 0.3);
            transition: 0.2s;
        }

        .btn-submit-sep:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(25, 135, 84, 0.4);
        }

        /* Modal Cetak SEP */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            padding: 15px;
        }

        .modal-content {
            background: #fff;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: #f8fafc;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 24px;
        }

        /* Cetak SEP Paper Format */
        .sep-print-sheet {
            border: 1px solid #ccc;
            padding: 20px 25px;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            line-height: 1.4;
            background: #fff;
        }

        .sep-header-tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .sep-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .sep-sub {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
        }

        .sep-info-table {
            width: 100%;
            font-size: 12px;
            border-collapse: collapse;
        }

        .sep-info-table td {
            padding: 3px 6px;
            vertical-align: top;
        }

        .sep-info-table td.col-label {
            width: 18%;
            font-weight: 500;
        }

        .sep-info-table td.col-sep {
            width: 2%;
        }

        .sep-info-table td.col-val {
            width: 30%;
        }

        .sep-footer-tbl {
            width: 100%;
            margin-top: 20px;
            font-size: 11.5px;
            border-collapse: collapse;
        }

        .barcode-box {
            font-family: 'Consolas', monospace;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .sep-print-sheet, .sep-print-sheet * {
                visibility: visible;
            }
            .sep-print-sheet {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-title">
            <h1><i class="fas fa-file-invoice-medical"></i> Pembuatan SEP BPJS (VClaim 2.0)</h1>
            <p><?php echo htmlspecialchars($nama_instansi); ?> - Integrasi Web Service Bridging VClaim & SIMRS</p>
        </div>
        <div class="header-actions">
            <div class="mode-box">
                <i class="fas fa-server"></i>
                <label>Mode API:</label>
                <select id="api_mode" onchange="switchApiMode(this.value)">
                    <option value="prod" selected>Live (koneksi.php)</option>
                    <option value="dev">Tester VClaim (Dev)</option>
                </select>
            </div>
            <a href="bpjs.php" class="btn-top">
                <i class="fas fa-arrow-left"></i> Menu BPJS
            </a>
        </div>
    </div>

    <div class="content">
        <!-- STEP 1: CARI PASIEN DARI SIMRS / NO KARTU -->
        <div class="step-card">
            <div class="step-header">
                <div><i class="fas fa-user-search"></i> Langkah 1: Cari Pasien & Registrasi RS</div>
                <span style="font-size: 12px; color: #64748b; font-weight: normal;">Cari berdasarkan No Rawat, No RM, atau No Kartu</span>
            </div>
            <div class="step-body">
                <div class="input-group">
                    <input type="text" id="cari_keyword" placeholder="Ketik No Rawat (contoh: 2026/09/21/000001), No RM, Nama Pasien, atau No Kartu BPJS..." onkeypress="if(event.key==='Enter') cariPasien()">
                    <button type="button" class="btn-action" onclick="cariPasien()">
                        <i class="fas fa-search"></i> Cari Pasien
                    </button>
                </div>

                <!-- Dropdown Hasil Pencarian Pasien -->
                <div id="hasil_cari_pasien" style="margin-top: 10px; display: none;">
                    <label style="font-size: 12px; font-weight: 600; color: #475569;">Pilih Pasien dari Hasil Pencarian:</label>
                    <select id="select_pasien" size="4" style="margin-top: 4px;" onchange="pilihPasien(this.value)">
                    </select>
                </div>

                <!-- Box Detail Pasien Terpilih -->
                <div id="box_patient_info" class="patient-info-box">
                    <div class="patient-grid">
                        <div><strong>Nama Pasien:</strong> <span id="info_nama">-</span></div>
                        <div><strong>No RM:</strong> <span id="info_norm">-</span></div>
                        <div><strong>No Rawat:</strong> <span id="info_norawat">-</span></div>
                        <div><strong>No Kartu BPJS:</strong> <span id="info_nokartu" style="font-weight: bold; color: #0d6efd;">-</span></div>
                        <div><strong>NIK:</strong> <span id="info_nik">-</span></div>
                        <div><strong>Tgl Lahir / JK:</strong> <span id="info_lahir">-</span></div>
                        <div><strong>Poli RS:</strong> <span id="info_poli_rs">-</span></div>
                        <div><strong>Dokter RS:</strong> <span id="info_dokter_rs">-</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: CEK RUJUKAN BPJS (OTOMATIS / MANUAL) -->
        <div class="step-card">
            <div class="step-header">
                <div><i class="fas fa-hospital-user"></i> Langkah 2: Tarik Data Rujukan dari BPJS</div>
                <button type="button" class="btn-action btn-success" style="padding: 5px 12px; font-size: 12px;" onclick="tarikRujukanBPJS()">
                    <i class="fas fa-cloud-arrow-down"></i> Tarik Rujukan Online BPJS
                </button>
            </div>
            <div class="step-body">
                <div id="rujukan_status_msg" style="font-size: 13px; color: #64748b;">
                    Klik tombol <strong>"Tarik Rujukan Online BPJS"</strong> di atas setelah memilih pasien untuk memeriksa rujukan aktif dari faskes tingkat 1 atau RS.
                </div>
                <!-- List Rujukan yang Ditemukan -->
                <div id="list_rujukan_container" style="display: none; margin-top: 10px;">
                    <label style="font-size: 12.5px; font-weight: 600; color: #1e293b;">Pilih Rujukan Aktif yang Akan Digunakan:</label>
                    <div id="rujukan_cards"></div>
                </div>
            </div>
        </div>

        <!-- STEP 3: FORM PARAMETER PEMBUATAN SEP -->
        <form id="formSEP" onsubmit="event.preventDefault(); submitSEP();">
            <div class="step-card">
                <div class="step-header">
                    <div><i class="fas fa-list-check"></i> Langkah 3: Form Parameter Pembuatan SEP (Standar VClaim 2.0)</div>
                </div>
                <div class="step-body">
                    <!-- Hidden Identitas -->
                    <input type="hidden" id="nama_pasien" name="nama_pasien">
                    <input type="hidden" id="tgl_lahir" name="tgl_lahir">
                    <input type="hidden" id="jkel" name="jkel">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="no_kartu">Nomor Kartu BPJS <span class="req">*</span></label>
                            <input type="text" id="no_kartu" name="no_kartu" required placeholder="0001234567890">
                        </div>
                        <div class="form-group">
                            <label for="no_mr">Nomor Rekam Medis (No RM) <span class="req">*</span></label>
                            <input type="text" id="no_mr" name="no_mr" required placeholder="123456">
                        </div>
                        <div class="form-group">
                            <label for="no_rawat">Nomor Rawat SIMRS <span class="req">*</span></label>
                            <input type="text" id="no_rawat" name="no_rawat" required placeholder="2026/09/21/000001">
                        </div>
                        <div class="form-group">
                            <label for="tgl_sep">Tanggal SEP <span class="req">*</span></label>
                            <input type="date" id="tgl_sep" name="tgl_sep" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="jns_pelayanan">Jenis Pelayanan <span class="req">*</span></label>
                            <select id="jns_pelayanan" name="jns_pelayanan" required onchange="onJnsPelayananChange()">
                                <option value="2" selected>2. Rawat Jalan</option>
                                <option value="1">1. Rawat Inap</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="kls_rawat_hak">Hak Kelas Rawat <span class="req">*</span></label>
                            <select id="kls_rawat_hak" name="kls_rawat_hak" required>
                                <option value="3" selected>Kelas 3</option>
                                <option value="2">Kelas 2</option>
                                <option value="1">Kelas 1</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="asal_rujukan">Asal Rujukan <span class="req">*</span></label>
                            <select id="asal_rujukan" name="asal_rujukan" required>
                                <option value="1" selected>1. Faskes 1 (Puskesmas/Klinik/Dokter)</option>
                                <option value="2">2. Faskes 2 (Rumah Sakit)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tgl_rujukan">Tanggal Rujukan <span class="req">*</span></label>
                            <input type="date" id="tgl_rujukan" name="tgl_rujukan" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="no_rujukan">Nomor Rujukan</label>
                            <input type="text" id="no_rujukan" name="no_rujukan" placeholder="Masukkan nomor rujukan">
                        </div>
                        <div class="form-group">
                            <label for="ppk_rujukan">Kode Faskes Perujuk</label>
                            <input type="text" id="ppk_rujukan" name="ppk_rujukan" placeholder="Kode PPK perujuk (misal 00010001)">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="nm_ppk_rujukan">Nama Faskes Perujuk</label>
                            <input type="text" id="nm_ppk_rujukan" name="nm_ppk_rujukan" placeholder="Nama Puskesmas / RS Perujuk">
                        </div>
                    </div>

                    <!-- Diagnosa & Poli -->
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="diag_awal">Diagnosa Awal ICD-10 <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="text" id="diag_awal" name="diag_awal" required placeholder="Kode ICD-10 (misal: M51, A00, E11.9)" style="max-width: 130px;">
                                <input type="text" id="nm_diag_awal" name="nm_diag_awal" placeholder="Nama Diagnosa..." readonly>
                                <button type="button" class="btn-action" onclick="cariDiagnosaOnline()">
                                    <i class="fas fa-search"></i> Cari ICD-10
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="poli_tujuan">Poli Tujuan BPJS <span class="req">*</span></label>
                            <select id="poli_tujuan" name="poli_tujuan" required onchange="onPoliChange()">
                                <option value="">-- Pilih Poli BPJS --</option>
                                <?php foreach ($poliOptions as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['kd_poli_bpjs']); ?>" data-rs="<?php echo htmlspecialchars($p['kd_poli_rs']); ?>" data-nama="<?php echo htmlspecialchars($p['nm_poli_bpjs']); ?>">
                                        <?php echo htmlspecialchars($p['nm_poli_bpjs'] . " (" . $p['kd_poli_bpjs'] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="nm_poli_tujuan" name="nm_poli_tujuan">
                        </div>

                        <div class="form-group">
                            <label for="dpjp_layan">Dokter DPJP Melayani <span class="req">*</span></label>
                            <select id="dpjp_layan" name="dpjp_layan" required onchange="onDokterChange()">
                                <option value="">-- Pilih Dokter DPJP --</option>
                                <?php foreach ($dokterOptions as $d): ?>
                                    <option value="<?php echo htmlspecialchars($d['kd_dokter_bpjs']); ?>" data-rs="<?php echo htmlspecialchars($d['kd_dokter']); ?>" data-nama="<?php echo htmlspecialchars($d['nm_dokter_bpjs']); ?>">
                                        <?php echo htmlspecialchars($d['nm_dokter_bpjs'] . " (" . $d['kd_dokter_bpjs'] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="nm_dpjp_layan" name="nm_dpjp_layan">
                        </div>
                    </div>

                    <!-- Parameter Kunjungan & Surat Kontrol -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tujuan_kunj">Tujuan Kunjungan</label>
                            <select id="tujuan_kunj" name="tujuan_kunj">
                                <option value="0" selected>0. Normal</option>
                                <option value="1">1. Prosedur</option>
                                <option value="2">2. Konsul Dokter</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="assesment_pel">Asesmen Pelayanan</label>
                            <select id="assesment_pel" name="assesment_pel">
                                <option value="">- Tidak Ada -</option>
                                <option value="1">1. Poli spesialis tidak tersedia pada hari sebelumnya</option>
                                <option value="2">2. Jam Poli telah berakhir pada hari sebelumnya</option>
                                <option value="3">3. Dokter Spesialis Berhalangan hadir pada hari sebelumnya</option>
                                <option value="4">4. Atas Instruksi RS</option>
                                <option value="5">5. Tujuan Kontrol</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="no_surat">No. Surat Kontrol / SKDP</label>
                            <input type="text" id="no_surat" name="no_surat" placeholder="No Surat Kontrol jika kontrol">
                        </div>
                        <div class="form-group">
                            <label for="kode_dpjp_surat">Kode DPJP Pemberi Surat</label>
                            <input type="text" id="kode_dpjp_surat" name="kode_dpjp_surat" placeholder="Kode DPJP BPJS">
                        </div>
                    </div>

                    <!-- Jaminan, Telepon, Catatan -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="laka_lantas">Penjaminan / Laka Lantas</label>
                            <select id="laka_lantas" name="laka_lantas">
                                <option value="0" selected>0. Bukan Kecelakaan Lalu Lintas [BKLL]</option>
                                <option value="1">1. KLL & Bukan Kecelakaan Kerja [BKK]</option>
                                <option value="2">2. KLL & KK</option>
                                <option value="3">3. Kecelakaan Kerja</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="no_telp">Nomor Telepon / HP Pasien</label>
                            <input type="text" id="no_telp" name="no_telp" value="08123456789" required>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="catatan">Catatan Petugas</label>
                            <input type="text" id="catatan" name="catatan" value="-">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="submit-container">
                        <button type="submit" id="btn_submit_sep" class="btn-submit-sep">
                            <i class="fas fa-check-circle"></i> Terbitkan SEP ke BPJS Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pencarian Diagnosa ICD-10 -->
<div id="modalDiagnosa" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 style="font-size: 15px;"><i class="fas fa-search"></i> Cari Master Diagnosa ICD-10 (BPJS)</h3>
            <button type="button" onclick="closeModal('modalDiagnosa')" style="border: none; background: none; font-size: 18px; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <div class="input-group">
                <input type="text" id="diag_search_input" placeholder="Ketik kode/nama diagnosa (misal: typh, cholera, demam)..." onkeypress="if(event.key==='Enter') executeDiagnosaSearch()">
                <button type="button" class="btn-action" onclick="executeDiagnosaSearch()">Cari</button>
            </div>
            <div id="diag_search_results" style="margin-top: 15px; max-height: 300px; overflow-y: auto;">
                <p style="color: #64748b; font-size: 13px;">Masukkan kata kunci dan klik Cari.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tampilan & Cetak SEP -->
<div id="modalCetakSEP" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header no-print">
            <h3 style="font-size: 16px; color: #198754;"><i class="fas fa-check-circle"></i> SEP Berhasil Diterbitkan!</h3>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-action btn-success" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak SEP
                </button>
                <button type="button" onclick="closeModal('modalCetakSEP')" style="border: none; background: none; font-size: 20px; cursor: pointer; padding: 0 6px;">&times;</button>
            </div>
        </div>
        <div class="modal-body">
            <!-- Sheet Lembar Cetak SEP -->
            <div class="sep-print-sheet" id="sep_print_area">
                <table class="sep-header-tbl">
                    <tr>
                        <td style="width: 15%; text-align: left;">
                            <img src="<?php echo $logo_src; ?>" alt="Logo RS" style="height: 55px; max-width: 80px;">
                        </td>
                        <td style="text-align: center;">
                            <div class="sep-title">SURAT ELIGIBILITAS PESERTA</div>
                            <div class="sep-sub"><?php echo htmlspecialchars($nama_instansi); ?></div>
                        </td>
                        <td style="width: 15%; text-align: right;">
                            <div style="font-size: 20px; font-weight: bold; color: #007bff;">BPJS</div>
                            <div style="font-size: 10px; color: #555;">Kesehatan</div>
                        </td>
                    </tr>
                </table>

                <table class="sep-info-table">
                    <tr>
                        <td class="col-label">No. SEP</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" style="font-weight: bold; font-size: 14px;" id="cetak_no_sep">-</td>
                        <td class="col-label">Prb</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_prb">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">Tgl. SEP</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_tgl_sep">-</td>
                        <td class="col-label">Peserta</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_peserta">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">No. Kartu</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_no_kartu">-</td>
                        <td class="col-label">Jns. Rawat</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_jns_rawat">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">Nama Peserta</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_nama_peserta" style="font-weight: bold;">-</td>
                        <td class="col-label">Jns. Kunjungan</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_jns_kunjungan">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">Tgl. Lahir</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_tgl_lahir">-</td>
                        <td class="col-label">Poli Perujuk</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_poli_perujuk">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">No. Telepon</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_no_telepon">-</td>
                        <td class="col-label">Kls. Hak</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_kls_hak">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">Sub/Spesialis</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_sub_spesialis">-</td>
                        <td class="col-label">Kls. Rawat</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_kls_rawat">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">Dokter DPJP</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_dpjp">-</td>
                        <td class="col-label">Penjamin</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_penjamin">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">Faskes Perujuk</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_faskes_perujuk">-</td>
                        <td class="col-label">No. MR</td>
                        <td class="col-sep">:</td>
                        <td class="col-val" id="cetak_nomr">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">Diagnosa Awal</td>
                        <td class="col-sep">:</td>
                        <td colspan="4" id="cetak_diag_awal">-</td>
                    </tr>
                    <tr>
                        <td class="col-label">Catatan</td>
                        <td class="col-sep">:</td>
                        <td colspan="4" id="cetak_catatan">-</td>
                    </tr>
                </table>

                <div style="font-size: 10px; color: #555; margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 6px;">
                    *Saya Menyetujui BPJS Kesehatan menggunakan Informasi Medis Pasien jika diperlukan.<br>
                    *SEP bukan sebagai bukti penjaminan peserta.
                </div>

                <table class="sep-footer-tbl">
                    <tr>
                        <td style="width: 50%; text-align: center;">
                            Pasien / Keluarga Pasien<br><br><br><br>
                            (_______________________)
                        </td>
                        <td style="width: 50%; text-align: center;">
                            Petugas BPJS Kesehatan / RS<br><br><br><br>
                            (_______________________)
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentPatients = [];
let selectedPatient = null;

function switchApiMode(mode) {
    const text = mode === 'dev' ? 'Mode Pengembangan (Tester VClaim Dev)' : 'Mode Live / Production';
    alert('Beralih ke: ' + text);
}

// 1. CARI PASIEN SIMRS
function cariPasien() {
    const keyword = document.getElementById('cari_keyword').value.trim();
    if (!keyword) {
        alert('Masukkan No Rawat, No RM, atau No Kartu!');
        return;
    }

    const mode = document.getElementById('api_mode').value;
    const btn = event.target;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';

    fetch(`buat_sep.php?ajax_action=cari_pasien&api_mode=${mode}&q=${encodeURIComponent(keyword)}`)
        .then(res => res.json())
        .then(res => {
            btn.innerHTML = '<i class="fas fa-search"></i> Cari Pasien';
            if (res.status && res.data.length > 0) {
                currentPatients = res.data;
                const select = document.getElementById('select_pasien');
                select.innerHTML = '';
                res.data.forEach((p, idx) => {
                    const opt = document.createElement('option');
                    opt.value = idx;
                    opt.textContent = `[${p.no_rawat}] RM: ${p.no_rkm_medis} - ${p.nm_pasien} (BPJS: ${p.no_peserta || '-'}) - Poli: ${p.nm_poli || '-'}`;
                    select.appendChild(opt);
                });
                document.getElementById('hasil_cari_pasien').style.display = 'block';
                // Auto pilih baris pertama
                select.selectedIndex = 0;
                pilihPasien(0);
            } else {
                alert('Data pasien tidak ditemukan di SIMRS!');
                document.getElementById('hasil_cari_pasien').style.display = 'none';
            }
        })
        .catch(err => {
            btn.innerHTML = '<i class="fas fa-search"></i> Cari Pasien';
            alert('Gagal mencari pasien: ' + err);
        });
}

// 2. PILIH PASIEN
function pilihPasien(idx) {
    const p = currentPatients[idx];
    if (!p) return;
    selectedPatient = p;

    // Tampilkan box info pasien
    document.getElementById('info_nama').textContent = p.nm_pasien;
    document.getElementById('info_norm').textContent = p.no_rkm_medis;
    document.getElementById('info_norawat').textContent = p.no_rawat;
    document.getElementById('info_nokartu').textContent = p.no_peserta || '(Belum ada di data pasien)';
    document.getElementById('info_nik').textContent = p.no_ktp || '-';
    document.getElementById('info_lahir').textContent = `${p.tgl_lahir} / ${p.jk === 'L' ? 'Laki-laki' : 'Perempuan'}`;
    document.getElementById('info_poli_rs').textContent = p.nm_poli || '-';
    document.getElementById('info_dokter_rs').textContent = p.nm_dokter || '-';
    document.getElementById('box_patient_info').style.display = 'block';

    // Auto-fill ke Form SEP
    document.getElementById('nama_pasien').value = p.nm_pasien;
    document.getElementById('tgl_lahir').value = p.tgl_lahir;
    document.getElementById('jkel').value = p.jk;
    document.getElementById('no_rawat').value = p.no_rawat;
    document.getElementById('no_mr').value = p.no_rkm_medis;
    if (p.no_peserta) {
        document.getElementById('no_kartu').value = p.no_peserta;
    }
    if (p.no_tlp) {
        document.getElementById('no_telp').value = p.no_tlp;
    }

    // Auto-select Poli BPJS jika ada di mapping
    if (p.kd_poli_bpjs) {
        document.getElementById('poli_tujuan').value = p.kd_poli_bpjs;
        onPoliChange();
    }

    // Auto-select DPJP BPJS jika ada di mapping
    if (p.kd_dokter_bpjs) {
        document.getElementById('dpjp_layan').value = p.kd_dokter_bpjs;
        onDokterChange();
    }

    // Otomatis trigger tarik rujukan BPJS jika no kartu tersedia
    if (p.no_peserta) {
        tarikRujukanBPJS();
    }
}

// 3. TARIK DATA RUJUKAN DARI BPJS
function tarikRujukanBPJS() {
    const noKartu = document.getElementById('no_kartu').value.trim();
    if (!noKartu) {
        alert('Isi No Kartu BPJS terlebih dahulu!');
        return;
    }

    const mode = document.getElementById('api_mode').value;
    const msg = document.getElementById('rujukan_status_msg');
    msg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghubungi server BPJS VClaim...';

    fetch(`buat_sep.php?ajax_action=cari_rujukan&api_mode=${mode}&no_kartu=${encodeURIComponent(noKartu)}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.rujukan && res.rujukan.length > 0) {
                msg.innerHTML = `<span style="color: #166534; font-weight: bold;"><i class="fas fa-check-circle"></i> Ditemukan ${res.rujukan.length} data rujukan aktif di BPJS.</span>`;
                const container = document.getElementById('rujukan_cards');
                container.innerHTML = '';

                res.rujukan.forEach((r, i) => {
                    const card = document.createElement('div');
                    card.className = 'rujukan-item' + (i === 0 ? ' active' : '');
                    card.innerHTML = `
                        <div style="font-weight: bold; color: #1e3a8a;">
                            <i class="fas fa-ticket"></i> No. Rujukan: ${r.noKunjungan} (${r.asalFaskesText})
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 6px; margin-top: 6px; font-size: 12.5px;">
                            <div><strong>Tgl Rujukan:</strong> ${r.tglKunjungan}</div>
                            <div><strong>Faskes Perujuk:</strong> ${r.provPerujuk.nama} (${r.provPerujuk.kode})</div>
                            <div><strong>Diagnosa:</strong> ${r.diagnosa.kode} - ${r.diagnosa.nama}</div>
                            <div><strong>Poli Rujukan:</strong> ${r.poliRujukan.nama} (${r.poliRujukan.kode})</div>
                        </div>
                    `;
                    card.onclick = () => {
                        document.querySelectorAll('.rujukan-item').forEach(c => c.classList.remove('active'));
                        card.classList.add('active');
                        terapkanRujukan(r);
                    };
                    container.appendChild(card);
                });

                document.getElementById('list_rujukan_container').style.display = 'block';
                // Terapkan rujukan pertama
                terapkanRujukan(res.rujukan[0]);
            } else {
                msg.innerHTML = `<span style="color: #b91c1c;"><i class="fas fa-exclamation-triangle"></i> Tidak ada rujukan aktif untuk nomor kartu ini (Respon BPJS: ${res.faskes1_meta || 'Tidak ada data'}). Jika pasien kontrol pasca rawat / IGD, silakan isi nomor rujukan/surat kontrol secara manual.</span>`;
                document.getElementById('list_rujukan_container').style.display = 'none';
            }

            // Update Hak Kelas dari BPJS jika ada
            if (res.peserta && res.peserta.hakKelas && res.peserta.hakKelas.kode) {
                document.getElementById('kls_rawat_hak').value = res.peserta.hakKelas.kode;
            }
        })
        .catch(err => {
            msg.innerHTML = `<span style="color: #b91c1c;">Gagal menghubungi BPJS: ${err}</span>`;
        });
}

// 4. TERAPKAN RUJUKAN KE FORM
function terapkanRujukan(r) {
    document.getElementById('asal_rujukan').value = r.asalFaskes || '1';
    document.getElementById('no_rujukan').value = r.noKunjungan || '';
    document.getElementById('tgl_rujukan').value = r.tglKunjungan || '';
    document.getElementById('ppk_rujukan').value = r.provPerujuk.kode || '';
    document.getElementById('nm_ppk_rujukan').value = r.provPerujuk.nama || '';

    if (r.diagnosa) {
        document.getElementById('diag_awal').value = r.diagnosa.kode || '';
        document.getElementById('nm_diag_awal').value = r.diagnosa.nama || '';
    }

    if (r.poliRujukan && r.poliRujukan.kode) {
        const poliSelect = document.getElementById('poli_tujuan');
        for (let opt of poliSelect.options) {
            if (opt.value === r.poliRujukan.kode) {
                poliSelect.value = r.poliRujukan.kode;
                onPoliChange();
                break;
            }
        }
    }
}

// 5. EVENT HANDLERS FORM
function onPoliChange() {
    const select = document.getElementById('poli_tujuan');
    const opt = select.options[select.selectedIndex];
    document.getElementById('nm_poli_tujuan').value = opt ? (opt.getAttribute('data-nama') || '') : '';
}

function onDokterChange() {
    const select = document.getElementById('dpjp_layan');
    const opt = select.options[select.selectedIndex];
    document.getElementById('nm_dpjp_layan').value = opt ? (opt.getAttribute('data-nama') || '') : '';
}

function onJnsPelayananChange() {
    const val = document.getElementById('jns_pelayanan').value;
    if (val === '1') { // Ranap
        document.getElementById('poli_tujuan').value = '';
        document.getElementById('poli_tujuan').required = false;
    } else {
        document.getElementById('poli_tujuan').required = true;
    }
}

// 6. MODAL PENCARIAN ICD-10
function cariDiagnosaOnline() {
    document.getElementById('modalDiagnosa').style.display = 'flex';
    document.getElementById('diag_search_input').focus();
}

function executeDiagnosaSearch() {
    const q = document.getElementById('diag_search_input').value.trim();
    if (q.length < 2) {
        alert('Ketik minimal 2 karakter!');
        return;
    }
    const mode = document.getElementById('api_mode').value;
    const box = document.getElementById('diag_search_results');
    box.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Mencari diagnosa di BPJS...</p>';

    fetch(`buat_sep.php?ajax_action=cari_diagnosa&api_mode=${mode}&q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.data && res.data.diagnosa) {
                const list = res.data.diagnosa;
                let html = '<table style="width:100%; border-collapse: collapse; font-size: 13px;">';
                html += '<tr style="background:#f1f5f9; text-align:left;"><th style="padding:6px;">Kode</th><th style="padding:6px;">Nama Diagnosa</th><th style="padding:6px;">Aksi</th></tr>';
                list.forEach(d => {
                    html += `<tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding:6px; font-weight:bold;">${d.kode}</td>
                        <td style="padding:6px;">${d.nama}</td>
                        <td style="padding:6px;">
                            <button type="button" class="btn-action" style="padding:4px 8px; font-size:11px;" onclick="pilihDiagnosa('${d.kode}', '${d.nama.replace(/'/g, "\\'")}')">Pilih</button>
                        </td>
                    </tr>`;
                });
                html += '</table>';
                box.innerHTML = html;
            } else {
                box.innerHTML = '<p style="color:#b91c1c;">Diagnosa tidak ditemukan di database BPJS.</p>';
            }
        })
        .catch(err => {
            box.innerHTML = '<p style="color:#b91c1c;">Gagal mencari diagnosa: ' + err + '</p>';
        });
}

function pilihDiagnosa(kode, nama) {
    document.getElementById('diag_awal').value = kode;
    document.getElementById('nm_diag_awal').value = nama;
    closeModal('modalDiagnosa');
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// 7. SUBMIT PEMBUATAN SEP
function submitSEP() {
    const btn = document.getElementById('btn_submit_sep');
    const form = document.getElementById('formSEP');
    const formData = new FormData(form);
    const mode = document.getElementById('api_mode').value;

    if (!confirm('Apakah Anda yakin data SEP sudah benar dan ingin menerbitkan SEP ke BPJS?')) {
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghubungi BPJS VClaim...';

    fetch(`buat_sep.php?ajax_action=submit_sep&api_mode=${mode}`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Terbitkan SEP ke BPJS Sekarang';

        if (res.status && res.sep) {
            const sep = res.sep;
            // Tampilkan Modal Cetak
            document.getElementById('cetak_no_sep').textContent = sep.noSep || res.no_sep;
            document.getElementById('cetak_tgl_sep').textContent = sep.tglSep || document.getElementById('tgl_sep').value;
            document.getElementById('cetak_no_kartu').textContent = sep.peserta ? sep.peserta.noKartu : document.getElementById('no_kartu').value;
            document.getElementById('cetak_nama_peserta').textContent = sep.peserta ? sep.peserta.nama : document.getElementById('nama_pasien').value;
            document.getElementById('cetak_tgl_lahir').textContent = sep.peserta ? sep.peserta.tglLahir : document.getElementById('tgl_lahir').value;
            document.getElementById('cetak_no_telepon').textContent = document.getElementById('no_telp').value;
            document.getElementById('cetak_sub_spesialis').textContent = sep.poli || document.getElementById('nm_poli_tujuan').value;
            document.getElementById('cetak_dpjp').textContent = document.getElementById('nm_dpjp_layan').value;
            document.getElementById('cetak_faskes_perujuk').textContent = document.getElementById('nm_ppk_rujukan').value || '-';
            document.getElementById('cetak_diag_awal').textContent = sep.diagnosa || `${document.getElementById('diag_awal').value} - ${document.getElementById('nm_diag_awal').value}`;
            document.getElementById('cetak_catatan').textContent = sep.catatan || document.getElementById('catatan').value;
            document.getElementById('cetak_peserta').textContent = sep.peserta ? sep.peserta.jnsPeserta : 'BPJS Kesehatan';
            document.getElementById('cetak_jns_rawat').textContent = sep.jnsPelayanan || (document.getElementById('jns_pelayanan').value === '1' ? 'Rawat Inap' : 'Rawat Jalan');
            document.getElementById('cetak_jns_kunjungan').textContent = 'Normal';
            document.getElementById('cetak_poli_perujuk').textContent = '-';
            document.getElementById('cetak_kls_hak').textContent = sep.kelasRawat || 'Kelas ' + document.getElementById('kls_rawat_hak').value;
            document.getElementById('cetak_kls_rawat').textContent = sep.kelasRawat || '-';
            document.getElementById('cetak_penjamin').textContent = sep.penjamin || '-';
            document.getElementById('cetak_nomr').textContent = sep.peserta ? sep.peserta.noMr : document.getElementById('no_mr').value;
            document.getElementById('cetak_prb').textContent = '-';

            document.getElementById('modalCetakSEP').style.display = 'flex';
        } else {
            alert('GAGAL MENERBITKAN SEP:\n' + res.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Terbitkan SEP ke BPJS Sekarang';
        alert('Terjadi kesalahan sistem: ' + err);
    });
}
</script>

</body>
</html>
