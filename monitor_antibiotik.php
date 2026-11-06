<?php
require_once 'koneksi.php';

// Get date range from GET parameters, default to last 30 days
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$end   = isset($_GET['end'])   ? $_GET['end']   : date('Y-m-d');

// Prepare statement
$query = "SELECT d.no_rawat,
                 r.no_rkm_medis,
                 p.nm_pasien,
                 MIN(d.tgl_perawatan) AS start_date,
                 MAX(d.tgl_perawatan) AS end_date,
                 COUNT(DISTINCT d.tgl_perawatan) AS days,
                 GROUP_CONCAT(DISTINCT d.kode_brng SEPARATOR ', ') AS kode_barang,
                 GROUP_CONCAT(DISTINCT b.nama_brng SEPARATOR ', ') AS nama_barang
          FROM detail_pemberian_obat d
          INNER JOIN databarang b ON d.kode_brng = b.kode_brng
          INNER JOIN reg_periksa r ON d.no_rawat = r.no_rawat
          INNER JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
          WHERE b.kode_golongan = 'AB'
            AND d.tgl_perawatan BETWEEN ? AND ?
          GROUP BY d.no_rawat, r.no_rkm_medis, p.nm_pasien
          HAVING days >= 3
          ORDER BY days DESC";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'ss', $start, $end);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitor Penggunaan Antibiotik ≥ 3 Hari</title>
    <style>
        body {font-family: Arial, sans-serif; margin: 20px;}
        table {border-collapse: collapse; width: 100%;}
        th, td {border: 1px solid #ccc; padding: 8px; text-align: left;}
        th {background-color: #f2f2f2;}
        .copy-btn {cursor: pointer; color: #0066cc; text-decoration: underline;}
    </style>
</head>
<body>
    <h2>Monitor Penggunaan Antibiotik (≥ 3 Hari per No Rawat)</h2>
    <form method="GET" style="margin-bottom:20px;">
        <label>Periode: </label>
        <input type="date" name="start" value="<?php echo htmlspecialchars($start); ?>" required>
        s/d 
        <input type="date" name="end" value="<?php echo htmlspecialchars($end); ?>" required>
        <button type="submit">Tampilkan</button>
    </form>
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <table id="antibiotik-table">
        <thead>
            <tr>
                <th>No Rawat</th>
                <th>No RKM Medis</th>
                <th>Nama Pasien</th>
                <th>Periode (Mulai - Selesai)</th>
                <th>Jumlah Hari</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                <td><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                <td><?php echo htmlspecialchars($row['start_date'] . ' - ' . $row['end_date']); ?></td>
                <td><?php echo htmlspecialchars($row['days']); ?></td>
                <td><?php echo htmlspecialchars($row['kode_barang']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                <td><span class="copy-btn" data-row="<?php echo htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">Copy</span></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Tidak ada data yang memenuhi kriteria.</p>
    <?php endif; ?>
    <script>
        document.querySelectorAll('.copy-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                const rowData = JSON.parse(this.getAttribute('data-row'));
                const text = `No Rawat: ${rowData.no_rawat}\nNo RKM Medis: ${rowData.no_rkm_medis}\nNama Pasien: ${rowData.nm_pasien}\nPeriode: ${rowData.start_date} - ${rowData.end_date}\nHari: ${rowData.days}\nKode Barang: ${rowData.kode_barang}\nNama Barang: ${rowData.nama_barang}`;
                navigator.clipboard.writeText(text).then(()=>{
                    alert('Baris data disalin ke clipboard');
                }).catch(err=>{
                    console.error('Copy gagal', err);
                });
            });
        });
    </script>
</body>
</html>

