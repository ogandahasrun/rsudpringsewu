<?php
/**
 * Aplikasi Batch Rename File
 * Fitur: Upload Excel/CSV, preview mapping, rename file dengan report
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Rename File - APOL Manager</title>
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
            max-width: 1000px;
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
            text-align: center;
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
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        input[type="text"],
        input[type="file"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="file"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
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
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .preview-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }
        .preview-section h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .table-wrapper {
            overflow-x: auto;
            border-radius: 6px;
            border: 1px solid #ddd;
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
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 Batch Rename File</h1>
            <p>Rename hingga 2000 file sekaligus dari file CSV</p>
        </div>
        
        <div class="content">
            <?php
            // Display pesan jika ada
            if (isset($_GET['status'])) {
                if ($_GET['status'] === 'success') {
                    echo '<div class="alert alert-success">✓ Preview berhasil dibuat. Silakan review data sebelum rename.</div>';
                } elseif ($_GET['status'] === 'error') {
                    echo '<div class="alert alert-error">✗ ' . htmlspecialchars($_GET['message'] ?? 'Terjadi kesalahan') . '</div>';
                }
            }
            ?>


            <form method="POST" action="batch_rename_preview.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label>📋 File CSV (Mapping Rename)</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                    <div class="help-text">Format CSV: Kolom 1 = Nama Lama, Kolom 2 = Nama Baru | File maksimal 10MB</div>
                </div>

                <div class="form-group">
                    <label>📂 Folder Sumber File PDF</label>
                    <input type="text" name="source_folder" placeholder="Contoh: D:\APOL atau C:\xampp\htdocs\rsudpringsewu\files" required>
                    <div class="help-text">Path lengkap folder yang berisi file yang akan direname</div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-primary">🔍 Preview Mapping</button>
                    <button type="reset" class="btn-secondary">Clear</button>
                </div>
            </form>

            <div class="preview-section">

                <h3>📌 Panduan Penggunaan</h3>
                <p style="margin-bottom: 15px; color: #666; line-height: 1.6;">
                    <strong>Langkah 1:</strong> Siapkan file <b>CSV</b> dengan 2 kolom:<br/>
                    &nbsp;&nbsp;&nbsp;&nbsp;• Kolom 1: Nama file lama (RESEP2-2026010001.pdf)<br/>
                    &nbsp;&nbsp;&nbsp;&nbsp;• Kolom 2: Nama file baru (001_UMI_0807R006V0126000001.pdf)<br/>
                    &nbsp;&nbsp;&nbsp;&nbsp;<em>→ Contoh: <a href="contoh_mapping_rename.csv" style="color: #667eea; text-decoration: underline;">Download contoh file CSV di sini</a></em><br/><br/>
                    <strong>Langkah 2:</strong> Upload file CSV dan masukkan path folder sumber<br/>
                    <strong>Langkah 3:</strong> Review preview dan pastikan semua mapping benar<br/>
                    <strong>Langkah 4:</strong> Klik tombol "Rename Sekarang" untuk melakukan rename<br/>
                    <strong>Langkah 5:</strong> Download laporan hasil rename
                </p>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 15px;">
                    <h4 style="margin-bottom: 10px; color: #333;">🔧 Troubleshooting "File Tidak Ditemukan":</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #666; line-height: 1.6;">
                        <li><strong>Case sensitivity:</strong> RESEP2-2026010001.pdf ≠ resep2-2026010001.pdf</li>
                        <li><strong>Spasi tersembunyi:</strong> Copy nama file dari File Explorer, jangan ketik manual</li>
                        <li><strong>Karakter tersembunyi:</strong> Paste ulang nama file di Excel</li>
                        <li><strong>Path folder:</strong> Pastikan path benar (D:\APOL)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


<!-- 1. Siapkan Excel dengan 2 kolom (nama lama | nama baru)
   ↓
2. Upload file Excel & masukkan path folder sumber
   ↓
3. Preview mapping (validasi file ada/tidak)
   ↓
4. Klik "Rename Sekarang"
   ↓
5. Lihat report & download log -->