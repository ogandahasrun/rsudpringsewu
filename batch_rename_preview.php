<?php
/**
 * Script Preview Mapping dari Excel/CSV
 */

function readExcelOrCsv($filePath, $sheetName = null) {
    $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($fileExt === 'csv') {
        return readCsvFile($filePath);
    } else {
        // Try to read as XLSX/XLS
        return readXlsxFile($filePath, $sheetName);
    }
}

function readCsvFile($filePath) {
    $data = [];
    if (($handle = fopen($filePath, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (!empty($row[0]) && !empty($row[1])) {
                $data[] = [
                    'old_name' => trim($row[0]),
                    'new_name' => trim($row[1])
                ];
            }
        }
        fclose($handle);
    }
    return $data;
}

function readXlsxFile($filePath, $sheetName = null) {
    // Try using simple XML parsing untuk XLSX (tanpa library eksternal)
    $data = [];
    
    if (!file_exists($filePath)) {
        throw new Exception("File tidak ditemukan: $filePath");
    }
    
    $zip = new ZipArchive();
    if ($zip->open($filePath) === true) {
        $xml = $zip->getFromName('xl/workbook.xml');
        $zip->close();
        
        if ($xml === false) {
            throw new Exception("Format XLSX tidak valid");
        }
        
        // Parse workbook untuk dapatkan sheet ID
        $workbook = simplexml_load_string($xml);
        $sheets = $workbook->sheets->sheet;
        $sheetId = null;
        
        if ($sheetName) {
            foreach ($sheets as $sheet) {
                if ((string)$sheet['name'] === $sheetName) {
                    $sheetId = (string)$sheet['sheetId'];
                    break;
                }
            }
        } else {
            $sheetId = (string)$sheets[0]['sheetId'];
        }
        
        if (!$sheetId) {
            throw new Exception("Sheet tidak ditemukan");
        }
        
        // Baca sheet data
        $zip = new ZipArchive();
        $zip->open($filePath);
        $xmlSheet = $zip->getFromName('xl/worksheets/sheet' . $sheetId . '.xml');
        $zip->close();
        
        if ($xmlSheet === false) {
            throw new Exception("Tidak bisa membaca sheet");
        }
        
        $sheet = simplexml_load_string($xmlSheet);
        $sheetData = $sheet->sheetData;
        
        // Parse setiap row
        foreach ($sheetData->row as $row) {
            $cellA = null;
            $cellB = null;
            
            foreach ($row->c as $cell) {
                $cellRef = (string)$cell['r'];
                $value = (string)$cell->v;
                
                if (strpos($cellRef, 'A') === 0) {
                    $cellA = $value;
                } elseif (strpos($cellRef, 'B') === 0) {
                    $cellB = $value;
                }
            }
            
            if ($cellA && $cellB) {
                $data[] = [
                    'old_name' => trim($cellA),
                    'new_name' => trim($cellB)
                ];
            }
        }
        
        return $data;
    }
    
    throw new Exception("Tidak bisa membuka file XLSX");
}

// ============ MAIN PROCESS ============

$errorMessages = [];
$mappingData = [];
$sourceFolder = '';

try {
    // Validasi form
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request");
    }
    
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File tidak berhasil diupload");
    }
    
    if (!isset($_POST['source_folder']) || empty($_POST['source_folder'])) {
        throw new Exception("Folder sumber harus diisi");
    }
    
    $sourceFolder = $_POST['source_folder'];
    
    // Validasi folder
    if (!is_dir($sourceFolder)) {
        throw new Exception("Folder sumber tidak ditemukan: " . htmlspecialchars($sourceFolder));
    }
    
    $file = $_FILES['excel_file'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    
    // Validasi ukuran
    if ($fileSize > 10 * 1024 * 1024) { // 10MB
        throw new Exception("File terlalu besar (maksimal 10MB)");
    }
    
    // Baca file
    $sheetName = isset($_POST['sheet_name']) && !empty($_POST['sheet_name']) ? $_POST['sheet_name'] : null;
    $mappingData = readExcelOrCsv($fileTmp, $sheetName);
    
    if (empty($mappingData)) {
        throw new Exception("Tidak ada data ditemukan di file");
    }
    
} catch (Exception $e) {
    header("Location: batch_rename_form.php?status=error&message=" . urlencode($e->getMessage()));
    exit;
}

// ============ VALIDASI MAPPING ============

$validationResults = [];
$totalFiles = count($mappingData);
$existingFiles = 0;
$missingFiles = 0;
$duplicateNewNames = [];

foreach ($mappingData as $idx => $mapping) {
    $oldPath = $sourceFolder . DIRECTORY_SEPARATOR . $mapping['old_name'];
    $newName = $mapping['new_name'];
    $status = 'valid';
    $message = '';
    
    // Cek file lama ada atau tidak
    if (!file_exists($oldPath)) {
        $status = 'error';
        $message = 'File tidak ditemukan';
        $missingFiles++;
    } else {
        $existingFiles++;
        
        // Cek nama baru tidak ada duplikat dalam list
        if (isset($duplicateNewNames[$newName])) {
            $status = 'warning';
            $message = 'Nama baru duplikat dengan row ' . $duplicateNewNames[$newName];
        } else {
            $duplicateNewNames[$newName] = ($idx + 1);
        }
    }
    
    $validationResults[] = [
        'row' => $idx + 1,
        'old_name' => $mapping['old_name'],
        'new_name' => $newName,
        'old_path' => $oldPath,
        'exists' => file_exists($oldPath),
        'status' => $status,
        'message' => $message
    ];
}

// ============ RENDER HTML ============
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Batch Rename - APOL Manager</title>
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
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            text-transform: uppercase;
        }
        .stat-box.warning {
            border-left-color: #ffc107;
        }
        .stat-box.warning .stat-number {
            color: #ffc107;
        }
        .stat-box.error {
            border-left-color: #dc3545;
        }
        .stat-box.error .stat-number {
            color: #dc3545;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table-wrapper {
            overflow-x: auto;
            border-radius: 6px;
            border: 1px solid #ddd;
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
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
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
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Preview Mapping File Rename</h1>
            <p>Verifikasi data sebelum melakukan rename massal</p>
        </div>
        
        <div class="content">
            <!-- Statistics -->
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $totalFiles; ?></div>
                    <div class="stat-label">Total File</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $existingFiles; ?></div>
                    <div class="stat-label">File Ada</div>
                </div>
                <div class="stat-box error">
                    <div class="stat-number"><?php echo $missingFiles; ?></div>
                    <div class="stat-label">File Hilang</div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($missingFiles > 0): ?>
            <div class="alert alert-error">
                ⚠️ <strong><?php echo $missingFiles; ?> file tidak ditemukan</strong> di folder "<strong><?php echo htmlspecialchars($sourceFolder); ?></strong>". 
                Halaman ini masih akan menampilkan data, tetapi file yang hilang tidak akan direname. 
                Pastikan path folder sudah benar sebelum melanjutkan.
            </div>
            <?php endif; ?>

            <?php if (count($mappingData) > 1000): ?>
            <div class="alert alert-warning">
                ℹ️ Anda akan rename <strong><?php echo count($mappingData); ?> file</strong>. Proses ini mungkin memakan waktu. 
                Jangan tutup browser hingga selesai.
            </div>
            <?php endif; ?>

            <!-- Table -->
            <h3 style="margin-bottom: 15px;">📋 Detail Mapping (<?php echo count($validationResults); ?> baris)</h3>
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
                        <?php foreach ($validationResults as $result): ?>
                        <tr>
                            <td><?php echo $result['row']; ?></td>
                            <td><?php echo htmlspecialchars($result['old_name']); ?></td>
                            <td><?php echo htmlspecialchars($result['new_name']); ?></td>
                            <td>
                                <?php 
                                $statusBadge = [
                                    'valid' => '<span class="badge badge-success">✓ Valid</span>',
                                    'warning' => '<span class="badge badge-warning">⚠ Warning</span>',
                                    'error' => '<span class="badge badge-error">✗ Error</span>'
                                ];
                                echo $statusBadge[$result['status']] ?? '';
                                ?>
                            </td>
                            <td>
                                <?php echo !empty($result['message']) ? htmlspecialchars($result['message']) : '-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Action Buttons -->
            <div class="button-group">
                <form method="POST" action="batch_rename_execute.php" style="flex: 1;">
                    <input type="hidden" name="mapping_data" value="<?php echo htmlspecialchars(json_encode($validationResults)); ?>">
                    <input type="hidden" name="source_folder" value="<?php echo htmlspecialchars($sourceFolder); ?>">
                    <button type="submit" class="btn-primary" <?php echo ($missingFiles > 0 ? 'onclick="return confirm(\'Beberapa file tidak ditemukan. Lanjutkan?\');"' : ''); ?>>
                        🚀 Rename Sekarang (<?php echo $existingFiles; ?> file)
                    </button>
                </form>
            </div>

            <div class="button-group">
                <a href="batch_rename_form.php" class="btn-secondary" style="flex: 1; text-align: center;">↩ Kembali Upload File</a>
            </div>
        </div>
    </div>
</body>
</html>
