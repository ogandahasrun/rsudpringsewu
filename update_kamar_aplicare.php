<?php
require_once 'koneksi.php';
require_once 'bpjssignature.php';

$configuredKodeppk = isset($KODEPPKAPLICARE) ? trim((string) $KODEPPKAPLICARE) : '';
$error = null;
$mappings = array();
$mappingError = null;
$processResults = array();
$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

function fetchAplicareMappings($koneksi) {
    $query = "
        SELECT
            aplicare_ketersediaan_kamar.kode_kelas_aplicare,
            aplicare_ketersediaan_kamar.kd_bangsal,
            aplicare_ketersediaan_kamar.kelas,
            COALESCE(bangsal.nm_bangsal, aplicare_ketersediaan_kamar.kd_bangsal) AS nm_bangsal,
            COALESCE(kamar_ringkasan.kapasitas, 0) AS kapasitas_real,
            COALESCE(kamar_ringkasan.tersedia, 0) AS tersedia_real
        FROM aplicare_ketersediaan_kamar
        LEFT JOIN bangsal ON bangsal.kd_bangsal = aplicare_ketersediaan_kamar.kd_bangsal
        LEFT JOIN (
            SELECT
                kd_bangsal,
                COUNT(*) AS kapasitas,
                SUM(CASE WHEN LOWER(status) = 'kosong' THEN 1 ELSE 0 END) AS tersedia
            FROM kamar
            GROUP BY kd_bangsal
        ) AS kamar_ringkasan ON kamar_ringkasan.kd_bangsal = aplicare_ketersediaan_kamar.kd_bangsal
        ORDER BY nm_bangsal ASC, aplicare_ketersediaan_kamar.kd_bangsal ASC
    ";

    $result = mysqli_query($koneksi, $query);
    if (!$result) {
        return array('rows' => array(), 'error' => mysqli_error($koneksi));
    }

    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_free_result($result);

    return array('rows' => $rows, 'error' => null);
}

function buildAplicarePayload($mapping) {
    $kapasitas = (int) $mapping['kapasitas_real'];
    $tersedia = (int) $mapping['tersedia_real'];

    if ($tersedia > $kapasitas) {
        $tersedia = $kapasitas;
    }

    return array(
        'kodekelas' => (string) $mapping['kode_kelas_aplicare'],
        'koderuang' => (string) $mapping['kd_bangsal'],
        'namaruang' => (string) $mapping['nm_bangsal'],
        'kapasitas' => (string) $kapasitas,
        'tersedia' => (string) $tersedia,
        'tersediapria' => '0',
        'tersediawanita' => '0',
        'tersediapriawanita' => (string) $tersedia
    );
}

function sendAplicareUpdate($requestUrl, $headers, $payload) {
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($jsonPayload === false) {
        return array(
            'success' => false,
            'http_code' => null,
            'response' => null,
            'error' => 'Gagal membentuk payload JSON: ' . json_last_error_msg()
        );
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $requestUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return array(
            'success' => false,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => 'Error cURL: ' . $curlError
        );
    }

    $decodedResponse = json_decode($response, true);
    $finalResponse = json_last_error() === JSON_ERROR_NONE ? $decodedResponse : $response;

    if ($httpCode < 200 || $httpCode >= 300) {
        return array(
            'success' => false,
            'http_code' => $httpCode,
            'response' => $finalResponse,
            'error' => 'HTTP Error: ' . $httpCode
        );
    }

    return array(
        'success' => true,
        'http_code' => $httpCode,
        'response' => $finalResponse,
        'error' => null
    );
}

$mappingFetch = fetchAplicareMappings($koneksi);
$mappings = $mappingFetch['rows'];
$mappingError = $mappingFetch['error'];

$mappingIndex = array();
foreach ($mappings as $mapping) {
    $mappingKey = $mapping['kode_kelas_aplicare'] . '|' . $mapping['kd_bangsal'];
    $mappingIndex[$mappingKey] = $mapping;
}

if ($requestMethod === 'POST') {
    $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';
    $targets = array();

    if ($configuredKodeppk === '') {
        $error = 'Konfigurasi KODEPPKAPLICARE di koneksi.php belum diisi.';
    } elseif ($mappingError !== null) {
        $error = 'Gagal membaca tabel mapping Aplicare: ' . $mappingError;
    } elseif (empty($mappings)) {
        $error = 'Tabel aplicare_ketersediaan_kamar belum memiliki data mapping.';
    } elseif ($action === 'update_single') {
        $mappingKey = isset($_POST['mapping_key']) ? trim((string) $_POST['mapping_key']) : '';
        if ($mappingKey === '' || !isset($mappingIndex[$mappingKey])) {
            $error = 'Mapping kamar yang dipilih tidak ditemukan.';
        } else {
            $targets[] = $mappingIndex[$mappingKey];
        }
    } elseif ($action === 'update_all') {
        $targets = $mappings;
    } else {
        $error = 'Aksi update tidak dikenali.';
    }

    if ($error === null) {
        $headers = getAplicareHeaders();
        $requestUrl = rtrim($URLAPLICARE, '/') . '/rest/bed/update/' . rawurlencode($configuredKodeppk);

        foreach ($targets as $target) {
            $payload = buildAplicarePayload($target);
            $apiResult = sendAplicareUpdate($requestUrl, $headers, $payload);

            $processResults[] = array(
                'kodekelas' => $target['kode_kelas_aplicare'],
                'kd_bangsal' => $target['kd_bangsal'],
                'nm_bangsal' => $target['nm_bangsal'],
                'payload' => $payload,
                'request_url' => $requestUrl,
                'http_code' => $apiResult['http_code'],
                'success' => $apiResult['success'],
                'response' => $apiResult['response'],
                'error' => $apiResult['error']
            );
        }

        $mappingFetch = fetchAplicareMappings($koneksi);
        $mappings = $mappingFetch['rows'];
        $mappingError = $mappingFetch['error'];
    }
}

$successCount = 0;
$failedCount = 0;
foreach ($processResults as $processResult) {
    if ($processResult['success']) {
        $successCount++;
    } else {
        $failedCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Kamar Aplicare</title>
    <style>
        :root {
            --bg-top: #eef6ff;
            --bg-bottom: #dff2ea;
            --panel: #ffffff;
            --panel-soft: #f5f9fc;
            --text: #17324d;
            --muted: #5f7388;
            --accent: #0f766e;
            --accent-strong: #0b5f59;
            --danger: #b42318;
            --danger-bg: #fff1f0;
            --success: #166534;
            --success-bg: #edfdf3;
            --warning: #915f00;
            --warning-bg: #fff8e8;
            --border: #d7e4ef;
            --shadow: 0 22px 60px rgba(18, 52, 86, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.12), transparent 32%),
                radial-gradient(circle at bottom right, rgba(23, 50, 77, 0.12), transparent 28%),
                linear-gradient(160deg, var(--bg-top), var(--bg-bottom));
            padding: 28px 16px;
        }

        .container {
            max-width: 1240px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.65);
            border-radius: 24px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .hero {
            padding: 32px;
            background: linear-gradient(135deg, #17324d, #0f766e);
            color: #fff;
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: 2rem;
            letter-spacing: 0.02em;
        }

        .hero p {
            margin: 0;
            max-width: 840px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.86);
        }

        .content {
            padding: 32px;
            display: grid;
            gap: 24px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 1.15rem;
        }

        .subtle {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .meta-grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .meta-box {
            background: var(--panel-soft);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
        }

        .meta-box strong {
            display: block;
            margin-bottom: 8px;
        }

        .actions {
            margin-top: 22px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .inline-form {
            margin: 0;
        }

        .btn {
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #fff;
        }

        .btn-secondary {
            background: #edf4f8;
            color: var(--text);
        }

        .btn-small {
            padding: 9px 14px;
            font-size: 0.85rem;
        }

        .status {
            border-radius: 18px;
            padding: 18px 20px;
            line-height: 1.6;
        }

        .status.error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid #f3c3bd;
        }

        .status.success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid #b7ebc7;
        }

        .status.warning {
            background: var(--warning-bg);
            color: var(--warning);
            border: 1px solid #eedbaf;
        }

        .api-rule {
            display: grid;
            gap: 10px;
            margin-top: 18px;
        }

        .api-rule div {
            padding: 14px 16px;
            border-radius: 14px;
            background: var(--panel-soft);
            border: 1px solid var(--border);
        }

        .table-wrap {
            margin-top: 20px;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eff8f6;
            font-size: 0.87rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            background: #edf4f8;
            color: var(--text);
        }

        .badge.success {
            background: var(--success-bg);
            color: var(--success);
        }

        .badge.warning {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .result-grid {
            display: grid;
            gap: 16px;
        }

        .result-card {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: #fff;
            overflow: hidden;
        }

        .result-head {
            padding: 16px 18px;
            background: var(--panel-soft);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .result-body {
            padding: 18px;
            display: grid;
            gap: 14px;
        }

        pre {
            margin: 0;
            padding: 18px;
            border-radius: 16px;
            background: #102033;
            color: #dff5ff;
            overflow-x: auto;
            font-size: 0.9rem;
            line-height: 1.55;
        }

        code {
            font-family: Consolas, monospace;
        }

        @media (max-width: 768px) {
            .hero,
            .content,
            .card {
                padding: 20px;
            }

            .meta-grid {
                grid-template-columns: 1fr;
            }

            .result-head {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>Update Kamar Aplicare</h1>
            <p>Halaman ini membaca mapping dari tabel aplicare_ketersediaan_kamar, menghitung kapasitas dan tempat tidur kosong dari tabel kamar berdasarkan kd_bangsal, lalu mengirim update ke API BPJS Aplicare.</p>
        </div>

        <div class="content">
            <div class="card">
                <h2>Ringkasan Sinkronisasi</h2>
                <p class="subtle">Kapasitas dihitung dari jumlah baris pada tabel kamar per kd_bangsal. Tempat tidur tersedia dihitung dari kamar dengan status <code>kosong</code>. Payload gender saat ini dikirim sebagai <code>0</code>, <code>0</code>, dan <code>tersediapriawanita = tersedia</code>.</p>

                <div class="meta-grid">
                    <div class="meta-box">
                        <strong>Kode PPK Aplicare</strong>
                        <div><?php echo htmlspecialchars($configuredKodeppk !== '' ? $configuredKodeppk : 'Belum dikonfigurasi'); ?></div>
                    </div>
                    <div class="meta-box">
                        <strong>Total Mapping</strong>
                        <div><?php echo htmlspecialchars((string) count($mappings)); ?> ruang</div>
                    </div>
                    <div class="meta-box">
                        <strong>Endpoint</strong>
                        <div><?php echo htmlspecialchars(rtrim($URLAPLICARE, '/')); ?>/rest/bed/update/{kodeppk}</div>
                    </div>
                </div>

                <div class="actions">
                    <form method="POST" class="inline-form">
                        <input type="hidden" name="action" value="update_all">
                        <button type="submit" class="btn btn-primary">Update Semua Mapping</button>
                    </form>
                    <button type="button" class="btn btn-secondary" onclick="window.location.reload()">Refresh Data Kamar</button>
                </div>
            </div>

            <div class="card">
                <h2>Rule API BPJS</h2>
                <div class="api-rule">
                    <div><strong>Method</strong><br>POST</div>
                    <div><strong>Content-Type</strong><br>application/json</div>
                    <div><strong>Field otomatis</strong><br>kodekelas dari mapping, koderuang dari kd_bangsal, namaruang dari bangsal, kapasitas dan tersedia dari tabel kamar</div>
                </div>
            </div>

            <?php if ($mappingError !== null): ?>
                <div class="status error">
                    <strong>Gagal membaca data mapping.</strong><br>
                    <?php echo htmlspecialchars($mappingError); ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== null): ?>
                <div class="status error">
                    <strong>Proses update gagal.</strong><br>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($mappingError === null && empty($mappings)): ?>
                <div class="status warning">
                    <strong>Belum ada data mapping.</strong><br>
                    Isi tabel aplicare_ketersediaan_kamar terlebih dahulu sebelum melakukan update ke Aplicare.
                </div>
            <?php endif; ?>

            <?php if (!empty($mappings)): ?>
                <div class="card">
                    <h2>Daftar Mapping Kamar</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kode Kelas</th>
                                    <th>Kode Ruang</th>
                                    <th>Nama Ruang</th>
                                    <th>Kelas SIMRS</th>
                                    <th>Kapasitas</th>
                                    <th>Tersedia</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mappings as $mapping): ?>
                                    <?php
                                    $payloadPreview = buildAplicarePayload($mapping);
                                    $mappingKey = $mapping['kode_kelas_aplicare'] . '|' . $mapping['kd_bangsal'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($mapping['kode_kelas_aplicare']); ?></td>
                                        <td><?php echo htmlspecialchars($mapping['kd_bangsal']); ?></td>
                                        <td><?php echo htmlspecialchars($mapping['nm_bangsal']); ?></td>
                                        <td><?php echo htmlspecialchars($mapping['kelas']); ?></td>
                                        <td><?php echo htmlspecialchars($payloadPreview['kapasitas']); ?></td>
                                        <td><?php echo htmlspecialchars($payloadPreview['tersedia']); ?></td>
                                        <td>
                                            <?php if ((int) $payloadPreview['kapasitas'] > 0): ?>
                                                <span class="badge success">Siap dikirim</span>
                                            <?php else: ?>
                                                <span class="badge warning">Belum ada kamar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="action" value="update_single">
                                                <input type="hidden" name="mapping_key" value="<?php echo htmlspecialchars($mappingKey); ?>">
                                                <button type="submit" class="btn btn-primary btn-small">Update Ruang Ini</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($processResults)): ?>
                <div class="status <?php echo $failedCount === 0 ? 'success' : 'warning'; ?>">
                    <strong>Proses update selesai.</strong><br>
                    Berhasil: <?php echo htmlspecialchars((string) $successCount); ?> ruang, gagal: <?php echo htmlspecialchars((string) $failedCount); ?> ruang.
                </div>

                <div class="result-grid">
                    <?php foreach ($processResults as $processResult): ?>
                        <div class="result-card">
                            <div class="result-head">
                                <div>
                                    <strong><?php echo htmlspecialchars($processResult['nm_bangsal']); ?></strong><br>
                                    <?php echo htmlspecialchars($processResult['kd_bangsal']); ?> | <?php echo htmlspecialchars($processResult['kodekelas']); ?>
                                </div>
                                <span class="badge <?php echo $processResult['success'] ? 'success' : 'warning'; ?>">
                                    <?php echo $processResult['success'] ? 'Berhasil' : 'Gagal'; ?>
                                    <?php if ($processResult['http_code'] !== null): ?>
                                        (HTTP <?php echo htmlspecialchars((string) $processResult['http_code']); ?>)
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="result-body">
                                <div>
                                    <strong>URL Request</strong>
                                    <pre><?php echo htmlspecialchars($processResult['request_url']); ?></pre>
                                </div>
                                <div>
                                    <strong>Payload JSON</strong>
                                    <pre><?php echo htmlspecialchars(json_encode($processResult['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                </div>
                                <div>
                                    <strong>Response BPJS</strong>
                                    <pre><?php echo htmlspecialchars(is_array($processResult['response']) ? json_encode($processResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $processResult['response']); ?></pre>
                                </div>
                                <?php if ($processResult['error'] !== null): ?>
                                    <div class="status error">
                                        <?php echo htmlspecialchars($processResult['error']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>