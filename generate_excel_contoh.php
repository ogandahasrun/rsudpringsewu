<?php
/**
 * Script untuk generate file Excel contoh mapping rename
 * Menggunakan format XLSX sederhana (XML-based)
 */

function createSampleExcel() {
    // Data contoh
    $data = [
        ['Nama Lama', 'Nama Baru'],
        ['RESEP2-2026010001.pdf', '001_UMI_0807R006V0126000001.pdf'],
        ['RESEP2-2026010002.pdf', '002_YENI_0807R006V0126000909.pdf'],
        ['RESEP2-2026010003.pdf', '003_BUDI_0807R006V1225000024.pdf'],
        ['RESEP2-2026010004.pdf', '004_SITI_0807R006V0908000012.pdf'],
        ['RESEP2-2026010005.pdf', '005_ANDA_0807R006V0112000056.pdf'],
        ['RESEP2-2026010006.pdf', '006_RINA_0807R006V0133000078.pdf'],
        ['RESEP2-2026010007.pdf', '007_TONO_0807R006V0144000090.pdf'],
        ['RESEP2-2026010008.pdf', '008_LINA_0807R006V0155000012.pdf'],
        ['RESEP2-2026010009.pdf', '009_DIAN_0807R006V0166000034.pdf'],
        ['RESEP2-2026010010.pdf', '010_AGUS_0807R006V0177000056.pdf']
    ];

    // Generate XML untuk XLSX
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
    $xml .= '<sheetData>' . "\n";

    foreach ($data as $rowIndex => $row) {
        $xml .= '<row r="' . ($rowIndex + 1) . '">' . "\n";
        foreach ($row as $colIndex => $cellValue) {
            $colLetter = chr(65 + $colIndex); // A, B, C, ...
            $cellRef = $colLetter . ($rowIndex + 1);

            $xml .= '<c r="' . $cellRef . '" t="str">' . "\n";
            $xml .= '<v>' . htmlspecialchars($cellValue) . '</v>' . "\n";
            $xml .= '</c>' . "\n";
        }
        $xml .= '</row>' . "\n";
    }

    $xml .= '</sheetData>' . "\n";
    $xml .= '</worksheet>';

    return $xml;
}

function createWorkbookXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheets>
        <sheet name="Mapping Rename" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
}

function createContentTypes() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>';
}

function createRels() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
}

function createWorkbookRels() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';
}

// Buat ZIP file (XLSX)
$zip = new ZipArchive();
$excelFile = __DIR__ . '/contoh_mapping_rename.xlsx';

if ($zip->open($excelFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    // Buat struktur folder
    $zip->addEmptyDir('xl');
    $zip->addEmptyDir('xl/worksheets');
    $zip->addEmptyDir('xl/_rels');
    $zip->addEmptyDir('_rels');

    // Tambah file-file XML
    $zip->addFromString('[Content_Types].xml', createContentTypes());
    $zip->addFromString('_rels/.rels', createRels());
    $zip->addFromString('xl/workbook.xml', createWorkbookXml());
    $zip->addFromString('xl/worksheets/sheet1.xml', createSampleExcel());
    $zip->addFromString('xl/_rels/workbook.xml.rels', createWorkbookRels());

    $zip->close();

    echo '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin: 20px 0;">';
    echo '<h3>✅ File Excel Contoh Berhasil Dibuat!</h3>';
    echo '<p><strong>Lokasi file:</strong> ' . htmlspecialchars($excelFile) . '</p>';
    echo '<p><strong>Download:</strong> <a href="contoh_mapping_rename.xlsx" style="color: #155724; text-decoration: underline;">Klik di sini untuk download</a></p>';
    echo '<p><strong>Jumlah data:</strong> 10 baris contoh (1 header + 9 data)</p>';
    echo '</div>';

} else {
    echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin: 20px 0;">';
    echo '<h3>❌ Gagal membuat file Excel</h3>';
    echo '<p>Silakan gunakan file CSV yang sudah tersedia: <a href="contoh_mapping_rename.csv">contoh_mapping_rename.csv</a></p>';
    echo '</div>';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate File Excel Contoh - APOL Manager</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
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
        .content {
            padding: 30px;
        }
        .table-wrapper {
            border-radius: 6px;
            border: 1px solid #ddd;
            overflow-x: auto;
            margin: 20px 0;
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
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Generate File Excel Contoh</h1>
            <p>Mapping Rename untuk Test Aplikasi</p>
        </div>

        <div class="content">
            <div class="alert alert-info">
                <h4>🔍 Masalah "File Tidak Ditemukan" - Kemungkinan Penyebab:</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>Case Sensitivity:</strong> RESEP2-2026010001.pdf ≠ resep2-2026010001.pdf</li>
                    <li><strong>Spasi tersembunyi:</strong> Ada spasi di awal/akhir nama file</li>
                    <li><strong>Encoding karakter:</strong> Karakter non-ASCII atau special characters</li>
                    <li><strong>Hidden characters:</strong> Copy-paste dari sumber lain yang membawa karakter tersembunyi</li>
                    <li><strong>Ekstensi file:</strong> .pdf vs .PDF (case sensitive di beberapa sistem)</li>
                </ul>
            </div>

            <div class="alert alert-warning">
                <h4>💡 Solusi Troubleshooting:</h4>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li>Buka folder D:\APOL di File Explorer</li>
                    <li>Copy nama file langsung dari properti file (klik kanan → Properties)</li>
                    <li>Paste ke Excel - JANGAN ketik manual</li>
                    <li>Pastikan tidak ada spasi di awal/akhir</li>
                    <li>Cek case (huruf besar/kecil) persis sama</li>
                </ol>
            </div>

            <h3>📋 Data Contoh yang Akan Digenerate:</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Lama</th>
                            <th>Nama Baru</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>RESEP2-2026010001.pdf</td><td>001_UMI_0807R006V0126000001.pdf</td></tr>
                        <tr><td>RESEP2-2026010002.pdf</td><td>002_YENI_0807R006V0126000909.pdf</td></tr>
                        <tr><td>RESEP2-2026010003.pdf</td><td>003_BUDI_0807R006V1225000024.pdf</td></tr>
                        <tr><td>RESEP2-2026010004.pdf</td><td>004_SITI_0807R006V0908000012.pdf</td></tr>
                        <tr><td>RESEP2-2026010005.pdf</td><td>005_ANDA_0807R006V0112000056.pdf</td></tr>
                        <tr><td>RESEP2-2026010006.pdf</td><td>006_RINA_0807R006V0133000078.pdf</td></tr>
                        <tr><td>RESEP2-2026010007.pdf</td><td>007_TONO_0807R006V0144000090.pdf</td></tr>
                        <tr><td>RESEP2-2026010008.pdf</td><td>008_LINA_0807R006V0155000012.pdf</td></tr>
                        <tr><td>RESEP2-2026010009.pdf</td><td>009_DIAN_0807R006V0166000034.pdf</td></tr>
                        <tr><td>RESEP2-2026010010.pdf</td><td>010_AGUS_0807R006V0177000056.pdf</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="button-group">
                <a href="batch_rename_form.php" class="btn-secondary">← Kembali ke Form Upload</a>
                <a href="contoh_mapping_rename.xlsx" class="btn-primary" download>📥 Download Excel Contoh</a>
                <a href="contoh_mapping_rename.csv" class="btn-primary" download>📥 Download CSV Contoh</a>
            </div>
        </div>
    </div>
</body>
</html>
