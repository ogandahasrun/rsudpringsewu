<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Data Klaim</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
    <style>
        body { font-family: Calibri, sans-serif; }
        table { font-size: 12px; }
        .header-container { font-size: 14px; }
        .filter-form { margin-bottom: 20px; }
        .filter-form select, .filter-form button { 
            padding: 5px 10px; 
            margin-right: 10px; 
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="header-content">
            <h1>LAMPUNG EYE CENTER</h1>
            <p>RINCIAN PASIEN BPJS</p>
            <p>Periode : <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?></p>
            <p>Dokter : <?php echo isset($_GET['nm_dokter']) ? $_GET['nm_dokter'] : ''; ?></p>
        </div>
    </div>
    <div class="garis-pembatas"></div>

    <form method="GET" action="" class="filter-form">
        <label for="bulanklaim">Bulan Klaim:</label>
        <select name="bulanklaim" id="bulanklaim" required>
            <option value="">-- Pilih Bulan --</option>
            <?php
            // Query untuk mendapatkan bulan klaim yang tersedia
            $query_bulan = "SELECT DISTINCT bulanklaim FROM lec_umbal ORDER BY bulanklaim DESC";
            $result_bulan = mysqli_query($koneksi, $query_bulan);
            while ($row_bulan = mysqli_fetch_assoc($result_bulan)) {
                $selected = (isset($_GET['bulanklaim']) && $_GET['bulanklaim'] == $row_bulan['bulanklaim']) ? 'selected' : '';
                echo "<option value='{$row_bulan['bulanklaim']}' $selected>{$row_bulan['bulanklaim']}</option>";
            }
            ?>
        </select>
        
        <label for="nm_dokter">Nama Dokter:</label>
        <select name="nm_dokter" id="nm_dokter" required>
            <option value="">-- Pilih Dokter --</option>
            <?php
            // Query untuk mendapatkan nama dokter yang tersedia
            $query_dokter = "SELECT DISTINCT nm_dokter FROM lec_umbal ORDER BY nm_dokter";
            $result_dokter = mysqli_query($koneksi, $query_dokter);
            while ($row_dokter = mysqli_fetch_assoc($result_dokter)) {
                $selected = (isset($_GET['nm_dokter']) && $_GET['nm_dokter'] == $row_dokter['nm_dokter']) ? 'selected' : '';
                echo "<option value='{$row_dokter['nm_dokter']}' $selected>{$row_dokter['nm_dokter']}</option>";
            }
            ?>
        </select>
        
        <button type="submit">Filter</button>
    </form>

    <?php
    function formatRupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }

    if (isset($_GET['bulanklaim']) && isset($_GET['nm_dokter'])) {
        $bulanklaim = $_GET['bulanklaim'];
        $nm_dokter = $_GET['nm_dokter'];
        
        $query = "SELECT no_sep, disetujui, no_rawat, tgl_registrasi, nm_pasien, nm_dokter, konsul, visit, operasi FROM lec_umbal WHERE bulanklaim = '$bulanklaim' AND nm_dokter = '$nm_dokter'";
        $result = mysqli_query($koneksi, $query);
        
        if (mysqli_num_rows($result) > 0) {
            echo "<table id='data-table' class='display nowrap' style='width:100%'>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No SEP</th>
                            <th>Disetujui</th>
                            <th>No Rawat</th>
                            <th>Tgl Registrasi</th>
                            <th>Nama Pasien</th>
                            <th>Nama Dokter</th>
                            <th>Konsul</th>
                            <th>Visit</th>
                            <th>Operasi</th>
                        </tr>
                    </thead>
                    <tbody>";
            $sum_konsul = $sum_visit = $sum_operasi = 0;
            $no = 1; // Variabel untuk nomor urut
            while ($row = mysqli_fetch_assoc($result)) {
                $sum_konsul += $row['konsul'];
                $sum_visit += $row['visit'];
                $sum_operasi += $row['operasi'];
                echo "<tr>
                        <td>{$no}</td>
                        <td>{$row['no_sep']}</td>
                        <td>" . formatRupiah($row['disetujui']) . "</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['tgl_registrasi']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['nm_dokter']}</td>
                        <td>" . formatRupiah($row['konsul']) . "</td>
                        <td>" . formatRupiah($row['visit']) . "</td>
                        <td>" . formatRupiah($row['operasi']) . "</td>
                      </tr>";
                $no++; // Increment nomor urut
            }
            $total = $sum_konsul + $sum_visit + $sum_operasi;
            $pph = 0.05 * (0.5 * $total);
            $jumlah_diterima = $total - $pph;
            echo "</tbody>
                <tfoot>
                    <tr>
                        <th colspan='7'>Jumlah</th>
                        <th>" . formatRupiah($sum_konsul) . "</th>
                        <th>" . formatRupiah($sum_visit) . "</th>
                        <th>" . formatRupiah($sum_operasi) . "</th>
                    </tr>
                    <tr>
                        <th colspan='9'>Total</th>
                        <th>" . formatRupiah($total) . "</th>
                    </tr>
                    <tr>
                        <th colspan='9'>PPh 5% dari (50% x Total)</th>
                        <th>" . formatRupiah($pph) . "</th>
                    </tr>
                    <tr>
                        <th colspan='9'>Jumlah yang diterima</th>
                        <th>" . formatRupiah($jumlah_diterima) . "</th>
                    </tr>
                </tfoot>
                </table>";
        } else {
            echo "<p>Data tidak ditemukan.</p>";
        }
    }
    ?>

    <script>
        $(document).ready(function() {
            $('#data-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copyHtml5',
                    {
                        extend: 'pdfHtml5',
                        orientation: 'landscape',
                        pageSize: 'A3',
                        title: 'Data Klaim RS Lampung Eye Center Periode: <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?>',
                        customize: function(doc) {
                            doc.content.unshift({
                                text: 'LAMPUNG EYE CENTER\nRINCIAN PASIEN BPJS\nPeriode: <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?>\nDokter: <?php echo isset($_GET['nm_dokter']) ? $_GET['nm_dokter'] : ''; ?>',
                                fontSize: 14,
                                bold: true,
                                margin: [0, 0, 0, 12]
                            });
                            doc.content.push({
                                text: 'Konsul : <?php echo isset($sum_konsul) ? formatRupiah($sum_konsul) : 'Rp 0'; ?> , Visit : <?php echo isset($sum_visit) ? formatRupiah($sum_visit) : 'Rp 0'; ?> , Operasi : <?php echo isset($sum_operasi) ? formatRupiah($sum_operasi) : 'Rp 0'; ?> \nTotal: <?php echo isset($total) ? formatRupiah($total) : 'Rp 0'; ?>\nPPh 5% dari (50% x Total): <?php echo isset($pph) ? formatRupiah($pph) : 'Rp 0'; ?>\nJumlah yang diterima : <?php echo isset($jumlah_diterima) ? formatRupiah($jumlah_diterima) : 'Rp 0'; ?>',
                                fontSize: 12,
                                bold: true,
                                margin: [0, 20, 0, 0]
                            });
                        }
                    }
                ],
                paging: false
            });
        });
    </script>
</body>
</html>