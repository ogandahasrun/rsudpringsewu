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
        
        /* Tambahan pembeda baris */
        #data-table tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        #data-table tbody tr:nth-child(even) {
            background-color: #ffffff;
        }

        #data-table tbody tr:hover {
            background-color: #d0ebff;
            cursor: pointer;
        }

</style>


</head>
<body>
    <div class="header-container">
        <div class="header-content">
            <h1>LAMPUNG EYE CENTER</h1>
            <p>RINCIAN PASIEN BPJS</p>
            <p>Periode : <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?></p>
            <p>Dokter : <?php echo isset($_GET['nm_dokter']) && $_GET['nm_dokter'] != '' ? $_GET['nm_dokter'] : 'Semua Dokter'; ?></p>
        </div>
    </div>
    <div class="garis-pembatas"></div>

    <form method="GET" action="" class="filter-form">
        <label for="bulanklaim">Bulan Klaim:</label>
        <select name="bulanklaim" id="bulanklaim" required>
            <option value="">-- Pilih Bulan --</option>
            <?php
            $query_bulan = "SELECT DISTINCT bulanklaim FROM rspsw_umbal ORDER BY bulanklaim DESC";
            $result_bulan = mysqli_query($koneksi, $query_bulan);
            while ($row_bulan = mysqli_fetch_assoc($result_bulan)) {
                $selected = (isset($_GET['bulanklaim']) && $_GET['bulanklaim'] == $row_bulan['bulanklaim']) ? 'selected' : '';
                echo "<option value='{$row_bulan['bulanklaim']}' $selected>{$row_bulan['bulanklaim']}</option>";
            }
            ?>
        </select>
        
        <label for="nm_dokter">Nama Dokter:</label>
        <select name="nm_dokter" id="nm_dokter">
            <option value="">-- Semua Dokter --</option>
            <?php
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

    if (isset($_GET['bulanklaim']) && !empty($_GET['bulanklaim'])) {
        $bulanklaim = $_GET['bulanklaim'];
        $nm_dokter = isset($_GET['nm_dokter']) ? $_GET['nm_dokter'] : '';

        $query = "SELECT 
                    lec_umbal.no_sep,
                    lec_umbal.no_rawat,
                    lec_umbal.tgl_registrasi,
                    lec_umbal.nm_pasien,
                    lec_umbal.nm_dokter,
                    lec_umbal.diagnosa,
                    lec_kelompok_prosedur.prosedur,
                    rspsw_umbal.diajukan,
                    rspsw_umbal.disetujui,
                    lec_umbal.status,
                    pasien.no_rkm_medis
                FROM 
                    lec_umbal
                    LEFT JOIN rspsw_umbal ON lec_umbal.no_sep = rspsw_umbal.no_sep
                    INNER JOIN reg_periksa ON rspsw_umbal.no_rawat = reg_periksa.no_rawat
                    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                    LEFT JOIN lec_kelompok_prosedur ON lec_umbal.kd_prosedur = lec_kelompok_prosedur.kd_prosedur
                WHERE
                    rspsw_umbal.bulanklaim = '$bulanklaim'";

        if (!empty($nm_dokter)) {
            $query .= " AND lec_umbal.nm_dokter = '$nm_dokter'";
        }

        $result = mysqli_query($koneksi, $query);

        if (mysqli_num_rows($result) > 0) {
            echo "<table id='data-table' class='display nowrap' style='width:100%'>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No SEP</th>
                            <th>No Rawat</th>
                            <th>Tgl Registrasi</th>
                            <th>Nama Pasien</th>                            
                            <th>Nomor RM</th>
                            <th>Nama Dokter</th>
                            <th>Diagnosa</th>
                            <th>Prosedur</th>
                            <th>Diajukan</th>
                            <th>Disetujui</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>";
            $no = 1;
            $total_diajukan = 0;
            $total_disetujui = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$no}</td>
                        <td>{$row['no_sep']}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['tgl_registrasi']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_dokter']}</td>
                        <td>{$row['diagnosa']}</td>
                        <td>{$row['prosedur']}</td>
                        <td>" . formatRupiah($row['diajukan']) . "</td>
                        <td>" . formatRupiah($row['disetujui']) . "</td>
                        <td>{$row['status']}</td>
                    </tr>";
                $no++;
                $total_diajukan += $row['diajukan'];
                $total_disetujui += $row['disetujui'];
            }

            echo "</tbody>
                <tfoot>
                    <tr>
                        <th colspan='6'>Total</th>
                        <th>" . formatRupiah($total_diajukan) . "</th>
                        <th>" . formatRupiah($total_disetujui) . "</th>
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
                                text: 'LAMPUNG EYE CENTER\nRINCIAN PASIEN BPJS\nPeriode: <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?>\nDokter: <?php echo isset($_GET['nm_dokter']) && $_GET['nm_dokter'] != '' ? $_GET['nm_dokter'] : 'Semua Dokter'; ?>',
                                fontSize: 14,
                                bold: true,
                                margin: [0, 0, 0, 12]
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
