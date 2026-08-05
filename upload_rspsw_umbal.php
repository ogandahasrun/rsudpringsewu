<?php
require_once 'koneksi.php';

$message = '';
$messageType = '';
$importStats = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_umbal'])) {
    $file = $_FILES['file_umbal'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Gagal mengunggah file. Kode error: " . $file['error'];
        $messageType = "danger";
    } else {
        $filePath = $file['tmp_name'];
        $fileName = htmlspecialchars($file['name']);
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $message = "Tidak dapat membaca file yang diunggah.";
            $messageType = "danger";
        } else {
            // Read first line to detect delimiter and header
            $firstLine = fgets($handle);
            rewind($handle);
            
            $delimiter = ";"; // Default semicolon for CSV
            if (strpos($firstLine, ";") !== false) {
                $delimiter = ";";
            } elseif (strpos($firstLine, ",") !== false) {
                $delimiter = ",";
            } elseif (strpos($firstLine, "\t") !== false) {
                $delimiter = "\t";
            }
            
            $header = fgetcsv($handle, 0, $delimiter);
            
            if (!$header || count($header) < 3) {
                $message = "Format file CSV tidak valid. Kolom minimal: no_sep, no_rawat, bulanklaim, diajukan, disetujui.";
                $messageType = "danger";
                fclose($handle);
            } else {
                // Normalize header names to lowercase & strip BOM/special characters
                $cleanHeader = array_map(function($item) {
                    $item = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $item);
                    return strtolower(trim($item));
                }, $header);
                
                // Map column indices
                $idxSep = array_search('no_sep', $cleanHeader);
                $idxRawat = array_search('no_rawat', $cleanHeader);
                $idxBulan = array_search('bulanklaim', $cleanHeader);
                $idxDiajukan = array_search('diajukan', $cleanHeader);
                $idxDisetujui = array_search('disetujui', $cleanHeader);
                
                if ($idxSep === false || $idxRawat === false) {
                    $message = "Header file CSV harus memiliki minimal kolom 'no_sep' dan 'no_rawat'. Header terdeteksi: " . implode(", ", $cleanHeader);
                    $messageType = "danger";
                    fclose($handle);
                } else {
                    $startTime = microtime(true);
                    
                    // Disable Foreign Key checks for bulk import flexibility
                    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS=0");
                    
                    $sqlInsert = "REPLACE INTO `rspsw_umbal` (`no_sep`, `no_rawat`, `bulanklaim`, `diajukan`, `disetujui`) VALUES (?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($koneksi, $sqlInsert);
                    
                    if (!$stmt) {
                        $message = "Gagal menyiapkan query database: " . mysqli_error($koneksi);
                        $messageType = "danger";
                        mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS=1");
                        fclose($handle);
                    } else {
                        $success = 0;
                        $failed = 0;
                        $skipped = 0;
                        $batchSize = 500;
                        $rowCount = 0;
                        
                        mysqli_begin_transaction($koneksi);
                        
                        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                            // Extract values safely
                            $noSep = ($idxSep !== false && isset($row[$idxSep])) ? trim($row[$idxSep]) : '';
                            $noRawat = ($idxRawat !== false && isset($row[$idxRawat])) ? trim($row[$idxRawat]) : '';
                            $bulanKlaim = ($idxBulan !== false && isset($row[$idxBulan])) ? trim($row[$idxBulan]) : '';
                            $diajukanRaw = ($idxDiajukan !== false && isset($row[$idxDiajukan])) ? trim($row[$idxDiajukan]) : '0';
                            $disetujuiRaw = ($idxDisetujui !== false && isset($row[$idxDisetujui])) ? trim($row[$idxDisetujui]) : '0';
                            
                            // Skip empty rows
                            if ($noSep === '' && $noRawat === '' && $bulanKlaim === '') {
                                $skipped++;
                                continue;
                            }
                            
                            // Sanitize numeric fields
                            $diajukan = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $diajukanRaw)));
                            $disetujui = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $disetujuiRaw)));
                            
                            mysqli_stmt_bind_param($stmt, "sssdd", $noSep, $noRawat, $bulanKlaim, $diajukan, $disetujui);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                $success++;
                            } else {
                                $failed++;
                            }
                            
                            $rowCount++;
                            if ($rowCount % $batchSize === 0) {
                                mysqli_commit($koneksi);
                                mysqli_begin_transaction($koneksi);
                            }
                        }
                        
                        mysqli_commit($koneksi);
                        mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS=1");
                        fclose($handle);
                        mysqli_stmt_close($stmt);
                        
                        $duration = round(microtime(true) - $startTime, 2);
                        
                        $message = "File <strong>$fileName</strong> berhasil diproses dalam $duration detik!";
                        $messageType = "success";
                        
                        $importStats = [
                            'filename' => $fileName,
                            'total' => $rowCount,
                            'success' => $success,
                            'failed' => $failed,
                            'skipped' => $skipped,
                            'duration' => $duration
                        ];
                    }
                }
            }
        }
    }
}

// Fetch stats & sample rows from database
$totalRecords = 0;
$resCount = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM `rspsw_umbal`");
if ($resCount) {
    $totalRecords = mysqli_fetch_assoc($resCount)['cnt'];
}

$sampleData = [];
$resSample = mysqli_query($koneksi, "SELECT no_sep, no_rawat, bulanklaim, diajukan, disetujui FROM `rspsw_umbal` ORDER BY bulanklaim DESC, no_rawat DESC LIMIT 10");
if ($resSample) {
    while ($r = mysqli_fetch_assoc($resSample)) {
        $sampleData[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Data UMBAL RSPSW - SIMRS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-color: #10b981;
            --accent-hover: #059669;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-bottom: 3rem;
        }

        .header-title {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .upload-area {
            border: 2px dashed #334155;
            border-radius: 0.75rem;
            padding: 2.5rem;
            text-align: center;
            background: rgba(15, 23, 42, 0.4);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover, .upload-area.dragover {
            border-color: var(--accent-color);
            background: rgba(16, 185, 129, 0.05);
        }

        .table-dark-custom {
            --bs-table-bg: transparent;
            --bs-table-color: #e2e8f0;
            --bs-table-border-color: rgba(255, 255, 255, 0.08);
        }

        .btn-accent {
            background-color: var(--accent-color);
            color: #0f172a;
            font-weight: 600;
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-accent:hover {
            background-color: var(--accent-hover);
            color: #ffffff;
            transform: translateY(-2px);
        }

        .stat-badge {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="header-title mb-1">Upload Data UMBAL RSPSW</h2>
                <p class="text-secondary mb-0">Impor data file CSV ke tabel MySQL <code>rspsw_umbal</code></p>
            </div>
            <div class="stat-badge">
                <i class="bi bi-database-fill"></i>
                Total Data: <?php echo number_format($totalRecords, 0, ',', '.'); ?> Baris
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show glass-card border-0 mb-4" role="alert">
                <i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($importStats): ?>
            <div class="glass-card p-4 mb-4">
                <h5 class="text-success mb-3"><i class="bi bi-bar-chart-fill me-2"></i>Hasil Pengunggahan:</h5>
                <div class="row text-center g-3">
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-dark">
                            <span class="text-secondary d-block">Total Baris Valid</span>
                            <span class="fs-4 fw-bold text-white"><?php echo number_format($importStats['total']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-dark">
                            <span class="text-secondary d-block">Berhasil (Insert/Replace)</span>
                            <span class="fs-4 fw-bold text-success"><?php echo number_format($importStats['success']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-dark">
                            <span class="text-secondary d-block">Baris Kosong Dilewati</span>
                            <span class="fs-4 fw-bold text-warning"><?php echo number_format($importStats['skipped']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-dark">
                            <span class="text-secondary d-block">Waktu Pemrosesan</span>
                            <span class="fs-4 fw-bold text-info"><?php echo $importStats['duration']; ?> dtk</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Upload -->
        <div class="glass-card p-4 mb-5">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="upload-area mb-3" onclick="document.getElementById('file_umbal').click()">
                    <i class="bi bi-file-earmark-spreadsheet display-3 text-success mb-3 d-block"></i>
                    <h5 class="fw-semibold">Pilih atau Tarik File CSV UMBAL</h5>
                    <p class="text-secondary mb-2">Format kolom: <code>no_sep; no_rawat; bulanklaim; diajukan; disetujui</code></p>
                    <span id="selected-file-name" class="badge bg-success fs-6 py-2 px-3 d-none"></span>
                    <input type="file" name="file_umbal" id="file_umbal" class="d-none" accept=".csv,.txt" required onchange="displayFileName(this)">
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-secondary">
                        <i class="bi bi-info-circle me-1"></i> Menggunakan <strong>`no_rawat`</strong> sebagai Primary Key. Data lama dengan `no_rawat` sama akan diperbarui otomatis.
                    </small>
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-upload me-2"></i>Mulai Unggah CSV
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview Data -->
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-table me-2 text-success"></i>Pratinjau Data Terakhir (Max 10 Record)</h5>
                <span class="text-secondary fs-7">Urut berdasarkan Bulan Klaim & No. Rawat</span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th>No. SEP</th>
                            <th>No. Rawat</th>
                            <th>Bulan Klaim</th>
                            <th class="text-end">Biaya Diajukan</th>
                            <th class="text-end">Biaya Disetujui</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sampleData)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">Belum ada data dalam tabel.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sampleData as $row): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($row['no_sep']); ?></code></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['no_rawat']); ?></span></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['bulanklaim']); ?></span></td>
                                    <td class="text-end fw-semibold">Rp <?php echo number_format($row['diajukan'], 0, ',', '.'); ?></td>
                                    <td class="text-end fw-semibold text-success">Rp <?php echo number_format($row['disetujui'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function displayFileName(input) {
            const fileNameBadge = document.getElementById('selected-file-name');
            if (input.files && input.files[0]) {
                fileNameBadge.textContent = 'File Terpilih: ' + input.files[0].name;
                fileNameBadge.classList.remove('d-none');
            } else {
                fileNameBadge.classList.add('d-none');
            }
        }

        // Drag and drop handling
        const uploadArea = document.querySelector('.upload-area');
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            }, false);
        });

        uploadArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = document.getElementById('file_umbal');
            if (files.length > 0) {
                fileInput.files = files;
                displayFileName(fileInput);
            }
        });
    </script>
</body>
</html>
