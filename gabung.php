<?php
include 'koneksi.php';
include 'functions.php';

$nopgdn = isset($_GET['nopgdn']) ? $_GET['nopgdn'] : '';
$data = [];
$total_summary = 0;

if (!empty($nopgdn)) {
    $stmt = $koneksi->prepare("SELECT 
        pemesananspjgabungan.nopgdn AS nopgdn,
        databarang.nama_brng AS nama_brng,
        Sum(detailpesan.jumlah) AS jumlah,
        detailpesan.kode_sat AS satuan,
        Avg(detailpesan.h_pesan) AS harga,
        Sum(detailpesan.subtotal) AS total
    FROM
        (((pemesananspjgabungan
        JOIN pemesanan ON (pemesananspjgabungan.no_faktur = pemesanan.no_faktur))
        JOIN detailpesan ON (detailpesan.no_faktur = pemesanan.no_faktur))
        JOIN databarang ON (detailpesan.kode_brng = databarang.kode_brng))
    WHERE
        pemesananspjgabungan.nopgdn = ?
    GROUP BY
        databarang.nama_brng");

    $stmt->bind_param("s", $nopgdn);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $total_summary += $row['total'];
    }

    $stmt->close();
}

$ppn = $total_summary * 0.11;
$total_with_ppn = $total_summary + $ppn;
$terbilang = terbilang($total_with_ppn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Pencarian</title>
    <style>
        .currency {
            text-align: right;
        }
    </style>
</head>
<body>
    <h2>Filter Pencarian</h2>
    <form method="GET">
        <label for="nopgdn">No PGDN:</label>
        <input type="text" name="nopgdn" id="nopgdn" value="<?php echo htmlspecialchars($nopgdn); ?>">
        <button type="submit">Cari</button>
    </form>

    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Nomor Urut</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th class="currency">Harga</th>
                <th class="currency">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)) { ?>
                <?php $no = 1; foreach ($data as $row) { ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_brng']); ?></td>
                        <td><?php echo htmlspecialchars($row['jumlah']); ?></td>
                        <td><?php echo htmlspecialchars($row['satuan']); ?></td>
                        <td class="currency">Rp <?php echo number_format($row['harga'], 2, ',', '.'); ?></td>
                        <td class="currency">Rp <?php echo number_format($row['total'], 2, ',', '.'); ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="5" class="currency"><strong>Summary Total:</strong></td>
                    <td class="currency"><strong>Rp <?php echo number_format($total_summary, 2, ',', '.'); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="5" class="currency"><strong>PPN 11%:</strong></td>
                    <td class="currency"><strong>Rp <?php echo number_format($ppn, 2, ',', '.'); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="5" class="currency"><strong>Total dengan PPN:</strong></td>
                    <td class="currency"><strong>Rp <?php echo number_format($total_with_ppn, 2, ',', '.'); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="6" class="currency"><strong>Terbilang:</strong> <?php echo $terbilang; ?></td>
                </tr>
            <?php } else { ?>
                <tr>
                    <td colspan="6" style="text-align:center;">Data tidak ditemukan</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>
