<?php
/**
 * Script Preview Mapping dari Excel/CSV
 */

function readCsvOnly($filePath) {
    return readCsvFile($filePath);
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
    
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
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
    
    $file = $_FILES['csv_file'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];

    // Validasi ukuran
    if ($fileSize > 10 * 1024 * 1024) { // 10MB
        throw new Exception("File terlalu besar (maksimal 10MB)");
    }

    // Baca file CSV
    $mappingData = readCsvOnly($fileTmp);

    if (empty($mappingData)) {
        throw new Exception("Tidak ada data ditemukan di file");
    }
    
} catch (Exception $e) {
    header("Location: batch_rename_form.php?status=error&message=" . urlencode($e->getMessage()));
    exit;
}

// ============ VALIDASI MAPPING ============

function validateFileExists($folderPath, $fileName) {
    $fullPath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

    // Cek eksistensi file
    if (file_exists($fullPath)) {
        return ['exists' => true, 'message' => ''];
    }

    // Jika tidak ditemukan, coba analisis lebih detail
    $issues = [];

    // 1. Cek case sensitivity - list semua file di folder
    $filesInFolder = scandir($folderPath);
    $filesInFolder = array_diff($filesInFolder, ['.', '..']);

    // Cari file dengan case insensitive
    $fileNameLower = strtolower($fileName);
    $similarFiles = [];
    foreach ($filesInFolder as $existingFile) {
        if (strtolower($existingFile) === $fileNameLower) {
            $similarFiles[] = $existingFile;
        }
    }

    if (!empty($similarFiles)) {
        $issues[] = 'Case mismatch. File ditemukan: "' . implode('", "', $similarFiles) . '"';
    }

    // 2. Cek karakter tersembunyi (non-printable characters)
    $cleanFileName = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $fileName);
    if ($cleanFileName !== $fileName) {
        $issues[] = 'Ada karakter tersembunyi/non-printable di nama file';
    }

    // 3. Cek spasi di awal/akhir
    $trimmedFileName = trim($fileName);
    if ($trimmedFileName !== $fileName) {
        $issues[] = 'Ada spasi di awal/akhir nama file';
    }

    // 4. Cek encoding issues
    if (!mb_check_encoding($fileName, 'UTF-8')) {
        $issues[] = 'Encoding karakter tidak valid (bukan UTF-8)';
    }

    // 5. Cek ekstensi file
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (empty($ext)) {
        $issues[] = 'Nama file tidak memiliki ekstensi';
    }

    // 6. Cari file dengan ekstensi berbeda
    $nameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
    $possibleFiles = [];
    foreach ($filesInFolder as $existingFile) {
        $existingNameWithoutExt = pathinfo($existingFile, PATHINFO_FILENAME);
        if (strtolower($existingNameWithoutExt) === strtolower($nameWithoutExt)) {
            $possibleFiles[] = $existingFile;
        }
    }

    if (!empty($possibleFiles) && empty($similarFiles)) {
        $issues[] = 'File dengan nama mirip ditemukan: "' . implode('", "', $possibleFiles) . '"';
    }

    $message = empty($issues) ? 'File tidak ditemukan di folder' : implode('; ', $issues);

    return ['exists' => false, 'message' => $message];
}

$validationResults = [];
$totalFiles = count($mappingData);
$existingFiles = 0;
$missingFiles = 0;
$duplicateNewNames = [];

foreach ($mappingData as $idx => $mapping) {
    $oldName = $mapping['old_name'];
    $newName = $mapping['new_name'];

    // Validasi file lama
    $fileCheck = validateFileExists($sourceFolder, $oldName);
    $oldPath = $sourceFolder . DIRECTORY_SEPARATOR . $oldName;

    $status = $fileCheck['exists'] ? 'valid' : 'error';
    $message = $fileCheck['message'];

    if ($fileCheck['exists']) {
        $existingFiles++;

        // Cek nama baru tidak ada duplikat dalam list
        if (isset($duplicateNewNames[$newName])) {
            $status = 'warning';
            $message = 'Nama baru duplikat dengan row ' . $duplicateNewNames[$newName];
        } else {
            $duplicateNewNames[$newName] = ($idx + 1);
        }
    } else {
        $missingFiles++;
    }

    $validationResults[] = [
        'row' => $idx + 1,
        'old_name' => $oldName,
        'new_name' => $newName,
        'old_path' => $oldPath,
        'exists' => $fileCheck['exists'],
        'status' => $status,
        'message' => $message
    ];
}

// Fungsi untuk menampilkan karakter tersembunyi secara visual
function visualizeHiddenChars($str) {
    $out = '';
    $len = mb_strlen($str, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $char = mb_substr($str, $i, 1, 'UTF-8');
        $ord = ord($char);
        if ($char === ' ') {
            $out .= '<span style="background:#ffe082;color:#333;">␣</span>';
        } elseif ($ord < 32 || ($ord >= 127 && $ord <= 159)) {
            $out .= '<span style="background:#ffcccb;color:#721c24;">␀</span>';
        } else {
            $out .= htmlspecialchars($char);
        }
    }
    return $out;
}

// ============ RENDER HTML ============
?>
        <div class="container">
            <div class="header">
                <h1>📋 Preview Mapping Rename</h1>
                <p>Review hasil mapping sebelum melakukan proses rename file.</p>
            </div>
            <div class="content" style="padding:30px;">
                <div class="stats">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $totalFiles; ?></div>
                        <div class="stat-label">Total Mapping</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $existingFiles; ?></div>
                        <div class="stat-label">File Ditemukan</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $missingFiles; ?></div>
                        <div class="stat-label">File Tidak Ditemukan</div>
                    </div>
                </div>
                <div class="table-wrapper" style="margin-top:30px;">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama File Lama</th>
                                <th>Visual Karakter Tersembunyi</th>
                                <th>Nama File Baru</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($validationResults as $row): ?>
                            <tr>
                                <td><?php echo $row['row']; ?></td>
                                <td><?php echo htmlspecialchars($row['old_name']); ?></td>
                                <td><?php echo visualizeHiddenChars($row['old_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['new_name']); ?></td>
                                <td>
                                    <?php if ($row['status'] === 'valid'): ?>
                                        <span class="badge badge-success">OK</span>
                                    <?php elseif ($row['status'] === 'warning'): ?>
                                        <span class="badge badge-warning">Warning</span>
                                    <?php else: ?>
                                        <span class="badge badge-error">Error</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
                <h4>⚠️ <strong><?php echo $missingFiles; ?> file tidak ditemukan</strong> di folder "<strong><?php echo htmlspecialchars($sourceFolder); ?></strong>"</h4>
                <p>Halaman ini masih akan menampilkan data, tetapi file yang hilang tidak akan direname.</p>

                <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.1); border-radius: 4px;">
                    <strong>🔍 Kemungkinan Penyebab & Solusi:</strong>
                    <ul style="margin: 8px 0; padding-left: 20px; line-height: 1.6;">
                        <li><strong>Case Sensitivity:</strong> RESEP2-2026010001.pdf ≠ resep2-2026010001.pdf<br/>
                            → <em>Solusi: Copy nama file langsung dari File Explorer (klik kanan file → Properties → nama file)</em></li>
                        <li><strong>Spasi tersembunyi:</strong> Ada spasi di awal/akhir nama file<br/>
                            → <em>Solusi: Gunakan TRIM() di Excel atau paste ulang nama file</em></li>
                        <li><strong>Karakter tersembunyi:</strong> Copy-paste dari sumber lain<br/>
                            → <em>Solusi: Ketik ulang nama file manual di Excel</em></li>
                        <li><strong>Encoding karakter:</strong> Karakter non-ASCII<br/>
                            → <em>Solusi: Pastikan Excel menggunakan UTF-8 encoding</em></li>
                        <li><strong>Path folder salah:</strong> Folder tidak sesuai<br/>
                            → <em>Solusi: Double-check path folder (contoh: D:\APOL bukan D:\APOL\)</em></li>
                    </ul>

                    <p style="margin-top: 10px;"><strong>💡 Tips:</strong> Buka folder di File Explorer, copy nama file dari situ, paste ke Excel. Jangan ketik manual!</p>
                </div>
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
