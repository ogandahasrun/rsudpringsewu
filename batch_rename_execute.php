<?php
/**
 * Script Eksekusi Batch Rename
 * Melakukan actual rename dan generate report
 */

// ============ PROCESS RENAME ============

$renameResults = [];
$sourceFolder = '';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request");
    }
    
    if (!isset($_POST['mapping_data']) || !isset($_POST['source_folder'])) {
        throw new Exception("Data tidak lengkap");
    }
    
    $mappingData = json_decode($_POST['mapping_data'], true);
    $sourceFolder = $_POST['source_folder'];
    
    if (!is_dir($sourceFolder)) {
        throw new Exception("Folder sumber tidak valid");
    }
    
    // Proses rename
    $successCount = 0;
    $failCount = 0;
    $startTime = microtime(true);
    
    foreach ($mappingData as $idx => $mapping) {
        $oldPath = $sourceFolder . DIRECTORY_SEPARATOR . $mapping['old_name'];
        $newPath = $sourceFolder . DIRECTORY_SEPARATOR . $mapping['new_name'];
        
        $result = [
            'row' => $mapping['row'],
            'old_name' => $mapping['old_name'],
            'new_name' => $mapping['new_name'],
            'status' => 'success',
            'message' => ''
        ];
        
        // Cek file lama ada
        if (!file_exists($oldPath)) {
            $result['status'] = 'error';
            $result['message'] = 'File lama tidak ditemukan';
            $failCount++;
        } 
        // Cek nama baru sudah ada
        elseif (file_exists($newPath)) {
            $result['status'] = 'error';
            $result['message'] = 'Nama baru sudah ada atau file tidak dapat diakses';
            $failCount++;
        } 
        // Lakukan rename
        else {
            if (@rename($oldPath, $newPath)) {
                $result['status'] = 'success';
                $result['message'] = 'Berhasil direname';
                $successCount++;
            } else {
                $result['status'] = 'error';
                $result['message'] = 'Gagal melakukan rename (permission denied)';
                $failCount++;
            }
        }
        
        $renameResults[] = $result;
        
        // Untuk preview di saat rename (tanpa output buffering)
        flush();
    }
    
    $endTime = microtime(true);
    $totalTime = round($endTime - $startTime, 2);
    
} catch (Exception $e) {
    header("Location: batch_rename_form.php?status=error&message=" . urlencode($e->getMessage()));
    exit;
}

// ============ GENERATE REPORT ============

$reportTime = date('Y-m-d H:i:s');
$logFile = __DIR__ . '/batch_rename_reports/report_' . date('Ymd_His') . '.txt';
$logDir = dirname($logFile);

// Buat folder reports jika belum ada
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Generate text log
$logContent = "=====================================\n";
$logContent .= "BATCH RENAME REPORT\n";
$logContent .= "=====================================\n";
$logContent .= "Waktu: $reportTime\n";
$logContent .= "Durasi: {$totalTime}s\n";
$logContent .= "Folder: " . $sourceFolder . "\n\n";
$logContent .= "SUMMARY:\n";
$logContent .= "- Total File: " . count($renameResults) . "\n";
$logContent .= "- Berhasil: $successCount\n";
$logContent .= "- Gagal: $failCount\n";
$logContent .= "- Success Rate: " . round(($successCount / count($renameResults)) * 100, 2) . "%\n";
$logContent .= "\n=====================================\n";
$logContent .= "DETAIL HASIL:\n";
$logContent .= "=====================================\n\n";

foreach ($renameResults as $result) {
    $status = strtoupper($result['status']);
    $logContent .= "Row #{$result['row']}\n";
    $logContent .= "  Status: [$status]\n";
    $logContent .= "  Dari: {$result['old_name']}\n";
    $logContent .= "  Ke:  {$result['new_name']}\n";
    if (!empty($result['message'])) {
        $logContent .= "  Info: {$result['message']}\n";
    }
    $logContent .= "\n";
}

// Simpan log file
file_put_contents($logFile, $logContent);

// ============ RENDER HTML REPORT ============
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Batch Rename - APOL Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .content {
            padding: 30px;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid;
            text-align: center;
        }
        .summary-box.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .summary-box.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .summary-box.info {
            border-left-color: #667eea;
            background: #e7f3ff;
        }
        .summary-number {
            font-size: 36px;
            font-weight: 700;
            color: inherit;
        }
        .summary-label {
            font-size: 12px;
            margin-top: 8px;
            text-transform: uppercase;
            color: inherit;
        }
        .table-wrapper {
            border-radius: 6px;
            border: 1px solid #ddd;
            overflow-x: auto;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            word-break: break-word;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        .button-group {
            display: flex;
            gap: 10px;
        }
        button, a {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            flex: 1;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-buttons button {
            flex: 0 1 auto;
            padding: 8px 16px;
            background: #f0f0f0;
            color: #333;
        }
        .filter-buttons button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        h3 {
            margin-bottom: 15px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Report Batch Rename Selesai</h1>
            <p>Waktu: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <div class="content">
            <!-- Summary Stats -->
            <div class="summary">
                <div class="summary-box info">
                    <div class="summary-number"><?php echo count($renameResults); ?></div>
                    <div class="summary-label">Total File</div>
                </div>
                <div class="summary-box success">
                    <div class="summary-number"><?php echo $successCount; ?></div>
                    <div class="summary-label">Berhasil</div>
                </div>
                <div class="summary-box error">
                    <div class="summary-number"><?php echo $failCount; ?></div>
                    <div class="summary-label">Gagal</div>
                </div>
                <div class="summary-box info">
                    <div class="summary-number"><?php echo round(($successCount / count($renameResults)) * 100, 1); ?>%</div>
                    <div class="summary-label">Success Rate</div>
                </div>
            </div>

            <!-- Detail Tabel -->
            <h3>📋 Detail Hasil (<?php echo count($renameResults); ?> file)</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30px;">No</th>
                            <th>Nama Lama</th>
                            <th>Nama Baru</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($renameResults as $result): ?>
                        <tr>
                            <td><?php echo $result['row']; ?></td>
                            <td><?php echo htmlspecialchars($result['old_name']); ?></td>
                            <td><?php echo htmlspecialchars($result['new_name']); ?></td>
                            <td>
                                <?php 
                                if ($result['status'] === 'success') {
                                    echo '<span class="badge badge-success">✓ Berhasil</span>';
                                } else {
                                    echo '<span class="badge badge-error">✗ Gagal</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($result['message']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Actions -->
            <h3>📥 Download Report</h3>
            <div class="button-group">
                <a href="batch_rename_download_log.php?file=<?php echo urlencode(basename($logFile)); ?>" class="btn-primary">📄 Download Log Text (.txt)</a>
                <a href="batch_rename_form.php" class="btn-secondary">↩ Rename File Lagi</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-reload summary data di background (opsional)
        console.log('Batch rename selesai. Success: <?php echo $successCount; ?>, Failed: <?php echo $failCount; ?>');
    </script>
</body>
</html>
