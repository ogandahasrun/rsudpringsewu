<?php
require_once 'koneksi.php';
require_once 'bpjssignature.php';

$configuredKodeppk = isset($KODEPPKAPLICARE) ? trim((string) $KODEPPKAPLICARE) : '';

// ============================================================
// AJAX ENDPOINT — called by JS auto-sync (no HTML output)
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'sync') {
    header('Content-Type: application/json; charset=utf-8');

    $result = array('success' => false, 'timestamp' => date('Y-m-d H:i:s'), 'details' => array(), 'error' => null);

    if ($configuredKodeppk === '') {
        $result['error'] = 'Konfigurasi KODEPPKAPLICARE di koneksi.php belum diisi.';
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $mappingFetch = fetchAplicareMappings($koneksi);
    if ($mappingFetch['error'] !== null) {
        $result['error'] = 'Gagal membaca tabel mapping Aplicare: ' . $mappingFetch['error'];
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $mappings = $mappingFetch['rows'];
    if (empty($mappings)) {
        $result['error'] = 'Tabel aplicare_ketersediaan_kamar belum memiliki data mapping.';
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $headers = getAplicareHeaders();
    $requestUrl = rtrim($URLAPLICARE, '/') . '/rest/bed/update/' . rawurlencode($configuredKodeppk);

    $successCount = 0;
    $failedCount = 0;

    foreach ($mappings as $target) {
        $payload = buildAplicarePayload($target);
        $apiResult = sendAplicareUpdate($requestUrl, $headers, $payload);

        $detail = array(
            'kd_bangsal' => $target['kd_bangsal'],
            'nm_bangsal' => $target['nm_bangsal'],
            'kodekelas' => $target['kode_kelas_aplicare'],
            'kapasitas' => $payload['kapasitas'],
            'tersedia' => $payload['tersedia'],
            'http_code' => $apiResult['http_code'],
            'success' => $apiResult['success'],
            'response' => $apiResult['response'],
            'error' => $apiResult['error']
        );
        $result['details'][] = $detail;

        if ($apiResult['success']) {
            $successCount++;
        } else {
            $failedCount++;
        }
    }

    $result['success'] = ($failedCount === 0);
    $result['summary'] = "Berhasil: {$successCount}, Gagal: {$failedCount}, Total: " . count($mappings);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// PHP FUNCTIONS
// ============================================================
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

// ============================================================
// Fetch mapping data for the HTML table display
// ============================================================
$mappingFetch = fetchAplicareMappings($koneksi);
$mappings = $mappingFetch['rows'];
$mappingError = $mappingFetch['error'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Sync Kamar Aplicare</title>
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
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.08), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
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

        /* ===== SYNC CONTROL PANEL ===== */
        .sync-panel {
            margin: 0;
            padding: 24px 32px;
            background: linear-gradient(180deg, #f0f7f5 0%, #ffffff 100%);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .sync-status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.05rem;
        }

        .pulse-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #94a3b8;
            position: relative;
            flex-shrink: 0;
        }

        .pulse-dot.running {
            background: #22c55e;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5);
            animation: pulse-ring 1.8s ease-out infinite;
        }

        .pulse-dot.syncing {
            background: #f59e0b;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.5);
            animation: pulse-ring 0.8s ease-out infinite;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .sync-timer {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.92rem;
            color: var(--muted);
        }

        .sync-timer strong {
            font-family: 'Consolas', monospace;
            font-size: 1.1rem;
            color: var(--text);
            min-width: 48px;
            text-align: center;
        }

        .sync-controls {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .btn {
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-start {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #fff;
        }

        .btn-stop {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff;
        }

        .btn-sync-now {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
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

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* ===== CONTENT ===== */
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

        /* ===== LOG PANEL ===== */
        .log-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
        }

        .log-header {
            padding: 18px 24px;
            background: #f8fbfd;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .log-header h2 {
            margin: 0;
            font-size: 1.1rem;
        }

        .log-count {
            background: var(--accent);
            color: #fff;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .log-body {
            max-height: 500px;
            overflow-y: auto;
            padding: 4px 0;
        }

        .log-entry {
            padding: 14px 24px;
            border-bottom: 1px solid #f0f4f8;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: logFadeIn 0.3s ease;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        @keyframes logFadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .log-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .log-icon.success {
            background: var(--success-bg);
            color: var(--success);
        }

        .log-icon.error {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .log-icon.info {
            background: #eef2ff;
            color: #4f46e5;
        }

        .log-icon.sync {
            background: #fff7ed;
            color: #ea580c;
        }

        .log-text {
            flex: 1;
            min-width: 0;
        }

        .log-text .log-title {
            font-weight: 700;
            font-size: 0.92rem;
        }

        .log-text .log-detail {
            color: var(--muted);
            font-size: 0.85rem;
            margin-top: 3px;
            word-break: break-word;
        }

        .log-time {
            color: var(--muted);
            font-size: 0.78rem;
            font-family: 'Consolas', monospace;
            white-space: nowrap;
            flex-shrink: 0;
            margin-top: 4px;
        }

        .log-empty {
            padding: 40px 24px;
            text-align: center;
            color: var(--muted);
        }

        /* ===== TABLE ===== */
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

        /* ===== PROGRESS BAR ===== */
        .progress-bar-container {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.3);
            overflow: hidden;
        }

        .progress-bar-container.active {
            background: #e2e8f0;
        }

        .progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #22c55e, #16a34a);
            transition: width 1s linear;
            border-radius: 0 4px 4px 0;
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

        /* ===== DETAIL TOGGLE ===== */
        .detail-toggle {
            cursor: pointer;
            color: var(--accent);
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: underline;
            background: none;
            border: none;
            padding: 0;
            margin-top: 4px;
        }

        .detail-content {
            display: none;
            margin-top: 8px;
            padding: 12px;
            background: #f8fafb;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 0.82rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .detail-content.show {
            display: block;
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

            .sync-panel {
                flex-direction: column;
                align-items: stretch;
            }

            .sync-controls {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>⚙ Auto Sync Kamar Aplicare</h1>
            <p>Sinkronisasi otomatis ketersediaan tempat tidur ke BPJS Aplicare. Halaman ini akan auto-run saat dibuka dan melakukan sync ulang setiap 10 menit.</p>
        </div>

        <!-- Progress bar -->
        <div class="progress-bar-container" id="progressContainer">
            <div class="progress-bar" id="progressBar"></div>
        </div>

        <!-- Sync Control Panel -->
        <div class="sync-panel">
            <div class="sync-status-indicator">
                <div class="pulse-dot" id="statusDot"></div>
                <span id="statusLabel">Mempersiapkan...</span>
            </div>

            <div class="sync-timer">
                <span>Sync berikutnya:</span>
                <strong id="countdownTimer">--:--</strong>
            </div>

            <div class="sync-controls">
                <button class="btn btn-sync-now btn-small" id="btnSyncNow" onclick="doSyncNow()">
                    ▶ Sync Sekarang
                </button>
                <button class="btn btn-stop btn-small" id="btnStop" onclick="stopAutoSync()" style="display:none;">
                    ⏹ Stop Auto Sync
                </button>
                <button class="btn btn-start btn-small" id="btnStart" onclick="startAutoSync()" style="display:none;">
                    ▶ Start Auto Sync
                </button>
            </div>
        </div>

        <div class="content">
            <!-- Info Card -->
            <div class="card">
                <h2>Ringkasan Konfigurasi</h2>
                <p class="subtle">Kapasitas dihitung dari jumlah baris pada tabel kamar per kd_bangsal. Tempat tidur tersedia dihitung dari kamar dengan status <code>kosong</code>. Auto sync berjalan setiap <strong>10 menit</strong>.</p>

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
            </div>

            <!-- Activity Log -->
            <div class="log-card">
                <div class="log-header">
                    <h2>📋 Log Aktivitas Sinkronisasi</h2>
                    <span class="log-count" id="logCount">0</span>
                </div>
                <div class="log-body" id="logBody">
                    <div class="log-empty" id="logEmpty">
                        Menunggu sinkronisasi pertama...
                    </div>
                </div>
            </div>

            <!-- API Rule -->
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mappings as $mapping): ?>
                                    <?php
                                    $payloadPreview = buildAplicarePayload($mapping);
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    (function() {
        // ===== CONFIGURATION =====
        const SYNC_INTERVAL_MS = 10 * 60 * 1000; // 10 minutes
        const SYNC_URL = window.location.pathname + '?ajax=sync';

        // ===== STATE =====
        let autoSyncTimer = null;
        let countdownInterval = null;
        let nextSyncTime = null;
        let isSyncing = false;
        let isAutoRunning = false;
        let logEntries = 0;

        // ===== DOM REFS =====
        const statusDot = document.getElementById('statusDot');
        const statusLabel = document.getElementById('statusLabel');
        const countdownTimer = document.getElementById('countdownTimer');
        const btnSyncNow = document.getElementById('btnSyncNow');
        const btnStop = document.getElementById('btnStop');
        const btnStart = document.getElementById('btnStart');
        const logBody = document.getElementById('logBody');
        const logEmpty = document.getElementById('logEmpty');
        const logCount = document.getElementById('logCount');
        const progressBar = document.getElementById('progressBar');
        const progressContainer = document.getElementById('progressContainer');

        // ===== LOG FUNCTIONS =====
        function addLog(type, title, detail) {
            if (logEmpty) logEmpty.style.display = 'none';

            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            const iconMap = {
                success: '✓',
                error: '✗',
                info: 'ℹ',
                sync: '⟳'
            };

            const entry = document.createElement('div');
            entry.className = 'log-entry';

            let detailHtml = '';
            if (detail) {
                const detailId = 'detail-' + Date.now();
                detailHtml = `
                    <button class="detail-toggle" onclick="var el=document.getElementById('${detailId}'); el.classList.toggle('show'); this.textContent = el.classList.contains('show') ? 'Sembunyikan detail ▲' : 'Lihat detail ▼';">Lihat detail ▼</button>
                    <div class="detail-content" id="${detailId}"><pre style="margin:0; padding:8px; font-size:0.8rem; border-radius:8px;">${escapeHtml(detail)}</pre></div>
                `;
            }

            entry.innerHTML = `
                <div class="log-icon ${type}">${iconMap[type] || '•'}</div>
                <div class="log-text">
                    <div class="log-title">${escapeHtml(title)}</div>
                    ${detailHtml}
                </div>
                <div class="log-time">${timeStr}</div>
            `;

            logBody.insertBefore(entry, logBody.firstChild);
            logEntries++;
            logCount.textContent = logEntries;

            // Keep max 100 entries
            while (logBody.children.length > 101) {
                logBody.removeChild(logBody.lastChild);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ===== SYNC FUNCTION =====
        async function performSync(source) {
            if (isSyncing) {
                addLog('info', 'Sync dilewati', 'Proses sync sebelumnya masih berjalan.');
                return;
            }

            isSyncing = true;
            updateUI('syncing', 'Sedang mengirim data ke Aplicare...');
            progressContainer.classList.add('active');
            progressBar.style.width = '30%';

            addLog('sync', 'Memulai sinkronisasi (' + source + ')', null);

            try {
                const response = await fetch(SYNC_URL, {
                    method: 'GET',
                    cache: 'no-cache'
                });

                progressBar.style.width = '70%';

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ' ' + response.statusText);
                }

                const data = await response.json();
                progressBar.style.width = '100%';

                if (data.success) {
                    addLog('success', 'Sinkronisasi berhasil — ' + data.summary, JSON.stringify(data.details, null, 2));
                } else if (data.error) {
                    addLog('error', 'Gagal: ' + data.error, data.details ? JSON.stringify(data.details, null, 2) : null);
                } else {
                    // Partial success
                    addLog('error', 'Sinkronisasi selesai dengan error — ' + data.summary, JSON.stringify(data.details, null, 2));
                }

            } catch (err) {
                progressBar.style.width = '100%';
                addLog('error', 'Error sinkronisasi: ' + err.message, null);
            }

            setTimeout(function() {
                progressBar.style.width = '0%';
                progressContainer.classList.remove('active');
            }, 800);

            isSyncing = false;

            if (isAutoRunning) {
                updateUI('running', 'Auto Sync aktif');
                scheduleNextSync();
            } else {
                updateUI('idle', 'Auto Sync dihentikan');
            }
        }

        // ===== COUNTDOWN & SCHEDULING =====
        function scheduleNextSync() {
            clearTimeout(autoSyncTimer);
            clearInterval(countdownInterval);

            nextSyncTime = Date.now() + SYNC_INTERVAL_MS;

            countdownInterval = setInterval(function() {
                const remaining = Math.max(0, nextSyncTime - Date.now());
                const mins = Math.floor(remaining / 60000);
                const secs = Math.floor((remaining % 60000) / 1000);
                countdownTimer.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');

                if (remaining <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);

            autoSyncTimer = setTimeout(function() {
                if (isAutoRunning) {
                    performSync('Auto Sync 10 Menit');
                }
            }, SYNC_INTERVAL_MS);
        }

        // ===== UI UPDATES =====
        function updateUI(state, label) {
            statusDot.className = 'pulse-dot';
            statusLabel.textContent = label;

            if (state === 'running') {
                statusDot.classList.add('running');
                btnStop.style.display = '';
                btnStart.style.display = 'none';
            } else if (state === 'syncing') {
                statusDot.classList.add('syncing');
            } else if (state === 'idle') {
                btnStop.style.display = 'none';
                btnStart.style.display = '';
                countdownTimer.textContent = '--:--';
            }
        }

        // ===== PUBLIC FUNCTIONS =====
        window.startAutoSync = function() {
            isAutoRunning = true;
            addLog('info', 'Auto Sync diaktifkan', 'Interval: 10 menit');
            updateUI('running', 'Auto Sync aktif');
            performSync('Manual Start');
        };

        window.stopAutoSync = function() {
            isAutoRunning = false;
            clearTimeout(autoSyncTimer);
            clearInterval(countdownInterval);
            autoSyncTimer = null;
            addLog('info', 'Auto Sync dihentikan', null);
            updateUI('idle', 'Auto Sync dihentikan');
        };

        window.doSyncNow = function() {
            if (isAutoRunning) {
                // Reset the timer
                clearTimeout(autoSyncTimer);
                clearInterval(countdownInterval);
            }
            performSync('Sync Manual');
        };

        // ===== AUTO-START ON PAGE LOAD =====
        addLog('info', 'Halaman dibuka, memulai Auto Sync...', 'Sync pertama akan berjalan otomatis.');

        // Short delay to let the page render before starting
        setTimeout(function() {
            isAutoRunning = true;
            updateUI('running', 'Auto Sync aktif');
            performSync('Autorun saat halaman dibuka');
        }, 1500);

    })();
    </script>
</body>
</html>