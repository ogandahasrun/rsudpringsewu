<?php
include 'koneksi.php';

$filter_bulan = isset($_GET['filter_bulan']) ? $_GET['filter_bulan'] : date('Y-m');
// Format input type="month" adalah YYYY-MM
// Format PGDN: PGDN + YY + MM + NNN
$year = substr($filter_bulan, 2, 2);
$month = substr($filter_bulan, 5, 2);
$like_pattern = "PGDN" . $year . $month . "%";

$sql = "SELECT
pemesananspjgabungan.nopgdn,
pemesanan.no_faktur,
datasuplier.nama_suplier,
pemesanan.tgl_faktur,
pemesanan.tagihan
FROM
pemesananspjgabungan
INNER JOIN pemesanan ON pemesananspjgabungan.no_faktur = pemesanan.no_faktur
INNER JOIN datasuplier ON pemesanan.kode_suplier = datasuplier.kode_suplier
WHERE pemesananspjgabungan.nopgdn LIKE ?
ORDER BY pemesananspjgabungan.nopgdn, pemesanan.no_faktur";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $like_pattern);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['nopgdn']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar SPJ Gabungan</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .filter-form { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h2>Daftar SPJ Gabungan</h2>
    
    <div class="filter-form">
        <form method="GET" action="">
            <label for="filter_bulan">Bulan SPJ Gabungan:</label>
            <input type="month" id="filter_bulan" name="filter_bulan" value="<?php echo htmlspecialchars($filter_bulan); ?>">
            <button type="submit">Filter</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>No PGDN</th>
                <th>No Faktur</th>
                <th>Nama Suplier</th>
                <th>Tgl Faktur</th>
                <th>Tagihan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (empty($data)) {
                echo '<tr><td colspan="6" style="text-align: center;">Tidak ada data untuk bulan ini</td></tr>';
            } else {
                foreach ($data as $nopgdn => $items) {
                    $rowspan = count($items);
                    $first = true;
                    foreach ($items as $item) {
                        echo '<tr>';
                        if ($first) {
                            echo '<td rowspan="' . $rowspan . '">' . htmlspecialchars($nopgdn) . '</td>';
                            $first = false;
                        }
                        echo '<td>' . htmlspecialchars($item['no_faktur']) . '</td>';
                        echo '<td>' . htmlspecialchars($item['nama_suplier']) . '</td>';
                        echo '<td>' . htmlspecialchars($item['tgl_faktur']) . '</td>';
                        echo '<td>Rp ' . number_format($item['tagihan'], 0, ',', '.') . '</td>';
                        
                        // Add Aksi buttons on the first row of each nopgdn group
                        if ($item === $items[0]) {
                            echo '<td rowspan="' . $rowspan . '" style="text-align: center;">';
                            // Link to open in new tab
                            echo '<a href="spjgabungan.php?nopgdn=' . urlencode($nopgdn) . '" target="_blank" style="text-decoration: none; background-color: #2196F3; color: white; padding: 5px 10px; border-radius: 3px; font-size: 14px; margin-bottom: 5px; display: inline-block;">Buka Halaman</a><br>';
                            // Button to print directly via iframe
                            echo '<button onclick="printPdf(\'spjgabungan.php?nopgdn=' . urlencode($nopgdn) . '\')" style="background-color: #4CAF50; color: white; padding: 5px 10px; border: none; border-radius: 3px; font-size: 14px; cursor: pointer; display: inline-block;">Simpan PDF</button>';
                            echo '</td>';
                        }

                        echo '</tr>';
                    }
                }
            }
            ?>
        </tbody>
    </table>

    <script>
    function printPdf(url) {
        let iframe = document.getElementById('printFrame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'printFrame';
            iframe.style.display = 'none';
            document.body.appendChild(iframe);
        }
        
        // Disable button temporarily to prevent multiple clicks
        const btns = document.querySelectorAll('button[onclick^="printPdf"]');
        btns.forEach(btn => btn.style.opacity = '0.5');

        iframe.src = url;
        iframe.onload = function() {
            setTimeout(function() {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                // Re-enable buttons
                btns.forEach(btn => btn.style.opacity = '1');
            }, 500); // Wait a bit for rendering
        };
    }
    </script>
</body>
</html>
