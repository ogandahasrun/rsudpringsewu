<?php
require_once 'koneksi.php';
require_once 'bpjssignature.php';

$configuredKodeppk = isset($KODEPPKAPLICARE) ? trim((string) $KODEPPKAPLICARE) : '';
$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

$error = null;
$result = null;
$httpCode = null;
$requestUrl = null;

$start = isset($_GET['start']) ? trim((string) $_GET['start']) : '1';
$limit = isset($_GET['limit']) ? trim((string) $_GET['limit']) : '10';

function isPositiveInteger($value) {
    return preg_match('/^[1-9]\d*$/', (string) $value) === 1;
}

function sendAplicareReadRequest($requestUrl, $headers) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $requestUrl);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
            'http_code' => $httpCode,
            'error' => 'Error cURL: ' . $curlError,
            'response' => $response
        );
    }

    $decodedResponse = json_decode($response, true);
    $finalResponse = json_last_error() === JSON_ERROR_NONE ? $decodedResponse : $response;

    return array(
        'http_code' => $httpCode,
        'error' => ($httpCode >= 200 && $httpCode < 300) ? null : 'HTTP Error: ' . $httpCode,
        'response' => $finalResponse
    );
}

function extractAplicareRows($payload) {
    if (!is_array($payload)) {
        return array();
    }

    $candidates = array();

    if (isset($payload['response']) && is_array($payload['response'])) {
        $response = $payload['response'];
        if (array_keys($response) === range(0, count($response) - 1)) {
            $candidates[] = $response;
        }

        foreach (array('list', 'data', 'kamar', 'ruang', 'bed') as $key) {
            if (isset($response[$key]) && is_array($response[$key])) {
                $candidates[] = $response[$key];
            }
        }
    }

    foreach (array('response', 'list', 'data') as $topLevelKey) {
        if (isset($payload[$topLevelKey]) && is_array($payload[$topLevelKey])) {
            $candidates[] = $payload[$topLevelKey];
        }
    }

    foreach ($candidates as $candidate) {
        if (!is_array($candidate) || empty($candidate)) {
            continue;
        }

        $first = reset($candidate);
        if (is_array($first)) {
            return $candidate;
        }
    }

    return array();
}

if ($configuredKodeppk === '') {
    $error = 'Konfigurasi KODEPPKAPLICARE di koneksi.php belum diisi.';
} elseif (!isPositiveInteger($start)) {
    $error = 'Parameter start harus berupa angka bulat positif dan dimulai dari 1.';
} elseif (!isPositiveInteger($limit)) {
    $error = 'Parameter limit harus berupa angka bulat positif.';
} else {
    $requestUrl = rtrim($URLAPLICARE, '/') . '/rest/bed/read/' . rawurlencode($configuredKodeppk) . '/' . rawurlencode($start) . '/' . rawurlencode($limit);
    $headers = getAplicareHeaders();
    $apiResult = sendAplicareReadRequest($requestUrl, $headers);
    $httpCode = $apiResult['http_code'];
    $result = $apiResult['response'];

    if ($apiResult['error'] !== null) {
        $error = $apiResult['error'];
    }
}

$rows = extractAplicareRows($result);
$displayColumns = array();
if (!empty($rows)) {
    $displayColumns = array_keys((array) reset($rows));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ketersediaan Kamar Aplicare</title>
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
            --border: #d7e4ef;
            --shadow: 0 22px 60px rgba(18, 52, 86, 0.14);
        }

        * { box-sizing: border-box; }

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
            background: rgba(255, 255, 255, 0.88);
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
        }

        .hero p {
            margin: 0;
            max-width: 820px;
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

        .form-grid {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            align-items: end;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-weight: 700;
            font-size: 0.95rem;
        }

        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 13px 14px;
            font-size: 0.98rem;
            color: var(--text);
            background: #fff;
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.13);
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
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

        .table-wrap {
            margin-top: 18px;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 780px;
        }

        th, td {
            padding: 13px 12px;
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

        tr:last-child td { border-bottom: 0; }

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

        @media (max-width: 768px) {
            .hero,
            .content,
            .card {
                padding: 20px;
            }

            .meta-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>Ketersediaan Kamar Aplicare</h1>
            <p>Halaman ini membaca data ketersediaan kamar langsung dari API Aplicare BPJS melalui endpoint <strong>read</strong>, sehingga bisa dipakai untuk memverifikasi hasil update yang sudah dikirim.</p>
        </div>

        <div class="content">
            <div class="card">
                <h2>Parameter Pembacaan</h2>
                <p class="subtle">BPJS memakai endpoint `read/{kodeppk}/{start}/{limit}`. Nilai `start` dimulai dari 1, dan `limit` menentukan jumlah data yang diambil pada request tersebut.</p>

                <div class="meta-grid">
                    <div class="meta-box">
                        <strong>Kode PPK Aplicare</strong>
                        <div><?php echo htmlspecialchars($configuredKodeppk !== '' ? $configuredKodeppk : 'Belum dikonfigurasi'); ?></div>
                    </div>
                    <div class="meta-box">
                        <strong>Start</strong>
                        <div><?php echo htmlspecialchars($start); ?></div>
                    </div>
                    <div class="meta-box">
                        <strong>Limit</strong>
                        <div><?php echo htmlspecialchars($limit); ?></div>
                    </div>
                </div>

                <form method="GET" action="">
                    <div class="form-grid">
                        <div class="field">
                            <label for="start">Start</label>
                            <input type="number" id="start" name="start" min="1" step="1" value="<?php echo htmlspecialchars($start); ?>" required>
                        </div>

                        <div class="field">
                            <label for="limit">Limit</label>
                            <input type="number" id="limit" name="limit" min="1" step="1" value="<?php echo htmlspecialchars($limit); ?>" required>
                        </div>

                        <div class="actions">
                            <button type="submit" class="btn btn-primary">Ambil Data</button>
                            <a href="ketersediaan_kamar_aplicare.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($error !== null): ?>
                <div class="status error">
                    <strong>Permintaan gagal.</strong><br>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif ($result !== null): ?>
                <div class="status success">
                    <strong>Permintaan ke Aplicare berhasil dijalankan.</strong><br>
                    HTTP Status: <?php echo htmlspecialchars((string) $httpCode); ?>
                </div>
            <?php endif; ?>

            <?php if ($requestUrl !== null): ?>
                <div class="card">
                    <h2>Request API</h2>
                    <div class="meta-grid">
                        <div class="meta-box">
                            <strong>Method</strong>
                            <div>GET</div>
                        </div>
                        <div class="meta-box">
                            <strong>HTTP Status</strong>
                            <div><?php echo htmlspecialchars((string) $httpCode); ?></div>
                        </div>
                        <div class="meta-box">
                            <strong>URL</strong>
                            <div><?php echo htmlspecialchars($requestUrl); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($rows)): ?>
                <div class="card">
                    <h2>Hasil Ketersediaan Kamar</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach ($displayColumns as $column): ?>
                                        <th><?php echo htmlspecialchars($column); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <?php foreach ($displayColumns as $column): ?>
                                            <td><?php echo htmlspecialchars(isset($row[$column]) ? (string) $row[$column] : ''); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($result !== null): ?>
                <div class="card">
                    <h2>Interpretasi Tabel</h2>
                    <p class="subtle">Response berhasil diterima, tetapi struktur array data kamar tidak dikenali secara otomatis. Data mentah tetap ditampilkan di bawah untuk pemeriksaan manual.</p>
                </div>
            <?php endif; ?>

            <?php if ($result !== null): ?>
                <div class="card">
                    <h2>Raw JSON Response</h2>
                    <pre><?php echo htmlspecialchars(is_array($result) ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $result); ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>