<?php
require_once 'koneksi.php';

$message = '';
$messageType = '';
$importStats = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_inacbg'])) {
    $file = $_FILES['file_inacbg'];
    
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
            
            $delimiter = "\t"; // Default tab
            if (strpos($firstLine, "\t") !== false) {
                $delimiter = "\t";
            } elseif (strpos($firstLine, ";") !== false) {
                $delimiter = ";";
            } elseif (strpos($firstLine, ",") !== false) {
                $delimiter = ",";
            }
            
            $header = fgetcsv($handle, 0, $delimiter);
            
            if (!$header || count($header) < 5) {
                $message = "Format file tidak valid atau delimiter tidak terdeteksi dengan benar.";
                $messageType = "danger";
                fclose($handle);
            } else {
                // Trim header names
                $cleanHeader = array_map(function($item) {
                    return trim($item);
                }, $header);
                
                // Build dynamic INSERT SQL using table column names
                $colsSql = "`" . implode("`, `", $cleanHeader) . "`";
                $placeholders = implode(", ", array_fill(0, count($cleanHeader), "?"));
                
                $sqlInsert = "REPLACE INTO `inacbg_unencrypted` ($colsSql) VALUES ($placeholders)";
                $stmt = mysqli_prepare($koneksi, $sqlInsert);
                
                if (!$stmt) {
                    $message = "Gagal menyiapkan query database: " . mysqli_error($koneksi);
                    $messageType = "danger";
                    fclose($handle);
                } else {
                    $startTime = microtime(true);
                    $types = str_repeat("s", count($cleanHeader));
                    
                    $success = 0;
                    $failed = 0;
                    $batchSize = 500;
                    $rowCount = 0;
                    
                    mysqli_begin_transaction($koneksi);
                    
                    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                        // Ignore empty lines
                        if (count($row) === 1 && trim($row[0]) === '') {
                            continue;
                        }
                        
                        // Pad row to match header length
                        while (count($row) < count($cleanHeader)) {
                            $row[] = '';
                        }
                        // Truncate row if exceeds header
                        if (count($row) > count($cleanHeader)) {
                            $row = array_slice($row, 0, count($cleanHeader));
                        }
                        
                        $bindParams = [$stmt, $types];
                        for ($i = 0; $i < count($cleanHeader); $i++) {
                            $bindParams[] = &$row[$i];
                        }
                        
                        call_user_func_array('mysqli_stmt_bind_param', $bindParams);
                        
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
                        'duration' => $duration
                    ];
                }
            }
        }
    }
}

// Fetch stats & sample rows from database
$totalRecords = 0;
$resCount = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM `inacbg_unencrypted`");
if ($resCount) {
    $totalRecords = mysqli_fetch_assoc($resCount)['cnt'];
}

$sampleData = [];
$resSample = mysqli_query($koneksi, "SELECT SEP, NAMA_PASIEN, MRN, ADMISSION_DATE, DISCHARGE_DATE, INACBG, DESKRIPSI_INACBG, TOTAL_TARIF, TARIF_INACBG FROM `inacbg_unencrypted` ORDER BY ADMISSION_DATE DESC LIMIT 10");
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
    <title>Upload Data INACBG Unencrypted - SIMRS</title>
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
            --accent-color: #38bdf8;
            --accent-hover: #0284c7;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-bottom: 3rem;
        }

        .header-title {
            background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
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
            background: rgba(56, 189, 248, 0.05);
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
            background: rgba(56, 189, 248, 0.1);
            color: var(--accent-color);
            border: 1px solid rgba(56, 189, 248, 0.2);
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
                <h2 class="header-title mb-1">Upload Data INACBG Unencrypted</h2>
                <p class="text-secondary mb-0">Impor data file TXT / CSV ke tabel MySQL <code>inacbg_unencrypted</code> (78 Field)</p>
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
                <h5 class="text-info mb-3"><i class="bi bi-bar-chart-fill me-2"></i>Hasil Pengunggahan:</h5>
                <div class="row text-center g-3">
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-dark">
                            <span class="text-secondary d-block">Total Baris</span>
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
                            <span class="text-secondary d-block">Gagal</span>
                            <span class="fs-4 fw-bold text-danger"><?php echo number_format($importStats['failed']); ?></span>
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
                <div class="upload-area mb-3" onclick="document.getElementById('file_inacbg').click()">
                    <i class="bi bi-cloud-arrow-up display-3 text-info mb-3 d-block"></i>
                    <h5 class="fw-semibold">Pilih atau Tarik File TXT / CSV INACBG</h5>
                    <p class="text-secondary mb-2">Mendukung file TXT berpemisah Tab (`\t`), Semicolon (`;`), atau Koma (`,`)</p>
                    <span id="selected-file-name" class="badge bg-primary fs-6 py-2 px-3 d-none"></span>
                    <input type="file" name="file_inacbg" id="file_inacbg" class="d-none" accept=".txt,.csv" required onchange="displayFileName(this)">
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-secondary">
                        <i class="bi bi-info-circle me-1"></i> Data diidentifikasi berdasarkan <strong>Primary Key (`SEP`)</strong>. Data lama dengan SEP sama akan diperbarui otomatis.
                    </small>
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-upload me-2"></i>Mulai Unggah Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview Data -->
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-table me-2 text-info"></i>Pratinjau Data Terakhir (Max 10 Record)</h5>
                <span class="text-secondary fs-7">Urut berdasarkan Tgl Masuk</span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th>No. SEP</th>
                            <th>Nama Pasien</th>
                            <th>No. RM</th>
                            <th>Tgl Masuk</th>
                            <th>Tgl Keluar</th>
                            <th>Kode INACBG</th>
                            <th>Deskripsi INACBG</th>
                            <th class="text-end">Tarif RS</th>
                            <th class="text-end">Tarif INACBG</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sampleData)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-secondary py-4">Belum ada data dalam tabel.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sampleData as $row): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($row['SEP']); ?></code></td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($row['NAMA_PASIEN']); ?></td>
                                    <td><?php echo htmlspecialchars($row['MRN']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ADMISSION_DATE']); ?></td>
                                    <td><?php echo htmlspecialchars($row['DISCHARGE_DATE']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['INACBG']); ?></span></td>
                                    <td class="small text-secondary"><?php echo htmlspecialchars($row['DESKRIPSI_INACBG']); ?></td>
                                    <td class="text-end fw-semibold">Rp <?php echo number_format($row['TOTAL_TARIF'], 0, ',', '.'); ?></td>
                                    <td class="text-end fw-semibold text-success">Rp <?php echo number_format($row['TARIF_INACBG'], 0, ',', '.'); ?></td>
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
            const fileInput = document.getElementById('file_inacbg');
            if (files.length > 0) {
                fileInput.files = files;
                displayFileName(fileInput);
            }
        });
    </script>
</body>
</html>
