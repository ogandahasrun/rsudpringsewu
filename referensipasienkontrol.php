<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referensi Pasien Kontrol - RSUD Pringsewu</title>
    <style>
        /* Copy all styles from referensipasienmjkn.php */
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3); }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .filter-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .filter-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input, .filter-group select { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1); }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #007bff, #0056b3); color: white; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4); }
        .btn-success { background: linear-gradient(45deg, #28a745, #20c997); color: white; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4); }
        .btn-secondary { background: #6c757d; color: white; box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3); }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        .table-responsive { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; background: white; min-width: 1000px; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e3f2fd; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }

        /* WhatsApp Button Styling */
        .whatsapp-btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: bold;
            white-space: nowrap;
        }
        .whatsapp-btn:hover {
            background: #1DA851;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        }
        .whatsapp-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 768px) { body { padding: 10px; } .header { padding: 20px 15px; } .header h1 { font-size: 1.5em; } .content { padding: 15px; } .filter-form { padding: 20px 15px; } .filter-grid { grid-template-columns: 1fr; gap: 15px; } .filter-actions { justify-content: stretch; } .btn { padding: 10px 15px; font-size: 13px; } th, td { padding: 8px 6px; font-size: 12px; } table { min-width: 800px; } }
        @media (max-width: 480px) { .header h1 { font-size: 1.3em; } .filter-title { font-size: 16px; } }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector(".table-responsive");
            if (table) {
                let range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try {
                    document.execCommand("copy");
                    alert("✅ Tabel berhasil disalin ke clipboard!");
                } catch(err) {
                    alert("❌ Gagal menyalin tabel");
                }
                window.getSelection().removeAllRanges();
            }
        }
        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-01'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('nm_poli').value = '';
            document.getElementById('nm_dokter_bpjs').value = '';
        }

        // Format Hari, Tanggal Indonesia (misal: Kamis, 10 September 2026)
        function formatHariTanggalIndo(tglStr) {
            if (!tglStr) return '-';
            const parts = tglStr.split('-');
            if (parts.length !== 3) return tglStr;
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const day = parseInt(parts[2], 10);
            
            const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const bulan = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            
            const dt = new Date(year, month - 1, day);
            const namaHari = hari[dt.getDay()];
            const namaBulan = bulan[month - 1];
            
            return `${namaHari}, ${day} ${namaBulan} ${year}`;
        }

        // Buka WhatsApp Web / API untuk pengingat jadwal kontrol pasien
        function openWhatsApp(nohp, namaPasien, tglRencana, nmPoli, nmDokter, noSurat, noAntrean, estimasiJam) {
            if (!nohp || nohp.trim() === '') {
                alert('❌ Nomor HP tidak tersedia');
                return;
            }
            
            // Format nomor HP untuk WhatsApp (hapus karakter non-digit)
            let cleanNumber = nohp.replace(/\D/g, '');
            
            // Cek jika nomor tidak valid (hanya 0 atau kurang dari 9 digit)
            if (/^0+$/.test(cleanNumber) || cleanNumber.length < 9) {
                alert('❌ Nomor HP tidak valid (' + nohp + ')');
                return;
            }
            
            // Jika nomor dimulai dengan 0, ganti dengan 62 (kode negara Indonesia)
            if (cleanNumber.startsWith('0')) {
                cleanNumber = '62' + cleanNumber.substring(1);
            }
            // Jika tidak dimulai dengan 62, tambahkan 62 di depan
            else if (!cleanNumber.startsWith('62')) {
                cleanNumber = '62' + cleanNumber;
            }

            // Format Hari, Tanggal (misal: Kamis, 10 September 2026)
            const hariTanggal = formatHariTanggalIndo(tglRencana);

            // Format Klinik Tujuan (misal: "Orthopedi" menjadi "Klinik Orthopedi")
            let klinikTujuan = nmPoli ? nmPoli.trim() : '-';
            if (klinikTujuan !== '-' && !/^klinik\s+/i.test(klinikTujuan)) {
                if (/^poli\s+/i.test(klinikTujuan)) {
                    klinikTujuan = klinikTujuan.replace(/^poli\s+/i, 'Klinik ');
                } else {
                    klinikTujuan = 'Klinik ' + klinikTujuan;
                }
            }

            // Format Nomor Antrean
            let antreanDisplay = (noAntrean !== undefined && noAntrean !== null && noAntrean !== '') ? String(noAntrean).trim() : '-';
            if (/^0+[0-9]+$/.test(antreanDisplay)) {
                antreanDisplay = antreanDisplay.replace(/^0+/, '');
            }

            // Format Estimasi Jam
            let jamDisplay = (estimasiJam !== undefined && estimasiJam !== null && estimasiJam !== '') ? String(estimasiJam).trim() : '08.00 - 12.00 WIB';

            // Pesan Pengingat Kontrol RSUD Pringsewu
            let message = `Yth. Bapak/Ibu ${namaPasien},\n\n` +
                          `Mengingatkan jadwal kontrol/berobat Anda di RSUD Pringsewu:\n\n` +
                          `Hari, Tanggal: ${hariTanggal}\n` +
                          `Klinik Tujuan : ${klinikTujuan}\n` +
                          `Dokter : ${nmDokter}\n` +
                          `Nomor Antrean: ${antreanDisplay}\n\n` +
                          `Estimasi Jam : ${jamDisplay}\n\n` +
                          `Penting untuk Diperhatikan:\n\n` +
                          `Mohon melakukan Check-in / Fingerprint di lokasi RSUD Pringsewu 15 menit sebelum estimasi pelayanan.\n\n` +
                          `Sesuai ketentuan BPJS Kesehatan, pasien wajib datang tepat pada tanggal yang tertera di Surat Kontrol (tidak bisa maju/mundur).\n\n` +
                          `Jika membutuhkan bantuan atau konfirmasi kendala jadwal, silakan balas pesan ini.\n\n` +
                          `Terima kasih.`;
            
            const encodedMessage = encodeURIComponent(message);
            
            // Buka WhatsApp (pada desktop langsung membuka web.whatsapp.com, pada mobile menggunakan api.whatsapp.com)
            const isMobile = /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
            const whatsappUrl = isMobile 
                ? `https://api.whatsapp.com/send?phone=${cleanNumber}&text=${encodedMessage}`
                : `https://web.whatsapp.com/send?phone=${cleanNumber}&text=${encodedMessage}`;
                
            window.open(whatsappUrl, '_blank');
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Referensi Pasien Kontrol</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="bpjs.php">← Kembali ke BPJS</a>
            </div>
<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');
// Ambil data untuk dropdown filter
function getOptions($koneksi, $field, $table, $where = "") {
    $options = [];
    $query = "SELECT DISTINCT $field FROM $table $where ORDER BY $field";
    $result = mysqli_query($koneksi, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $options[] = $row[$field];
    }
    return $options;
}
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
$nm_poli = isset($_POST['nm_poli']) ? $_POST['nm_poli'] : '';
$nm_dokter_bpjs = isset($_POST['nm_dokter_bpjs']) ? $_POST['nm_dokter_bpjs'] : '';
$nm_poli_options = getOptions($koneksi, 'nm_poli_bpjs', 'bridging_surat_kontrol_bpjs');
$nm_dokter_bpjs_options = getOptions($koneksi, 'nm_dokter_bpjs', 'bridging_surat_kontrol_bpjs');
?>
            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Referensi Pasien Kontrol
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Rencana Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Rencana Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="nm_poli">🏥 Nama Poli BPJS</label>
                        <select id="nm_poli" name="nm_poli">
                            <option value="">-- Semua Poli --</option>
                            <?php foreach ($nm_poli_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($nm_poli == $opt) echo "selected"; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="nm_dokter_bpjs">👨‍⚕️ Nama Dokter BPJS</label>
                        <select id="nm_dokter_bpjs" name="nm_dokter_bpjs">
                            <option value="">-- Semua Dokter --</option>
                            <?php foreach ($nm_dokter_bpjs_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($nm_dokter_bpjs == $opt) echo "selected"; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">📊 Tampilkan Data</button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">🔄 Reset Filter</button>
                </div>
            </form>
<?php
if (isset($_POST['filter'])) {
    $where_conditions = ["bridging_surat_kontrol_bpjs.tgl_rencana BETWEEN '" . $tanggal_awal . "' AND '" . $tanggal_akhir . "'"];
    if ($nm_poli != '') {
        $where_conditions[] = "bridging_surat_kontrol_bpjs.nm_poli_bpjs = '" . mysqli_real_escape_string($koneksi, $nm_poli) . "'";
    }
    if ($nm_dokter_bpjs != '') {
        $where_conditions[] = "bridging_surat_kontrol_bpjs.nm_dokter_bpjs = '" . mysqli_real_escape_string($koneksi, $nm_dokter_bpjs) . "'";
    }
    $where = "WHERE " . implode(" AND ", $where_conditions);
    $query = "SELECT 
                bridging_surat_kontrol_bpjs.no_surat,
                bridging_surat_kontrol_bpjs.no_sep,
                bridging_sep.nomr, 
                bridging_sep.nama_pasien, 
                bridging_surat_kontrol_bpjs.tgl_rencana, 
                bridging_surat_kontrol_bpjs.nm_dokter_bpjs, 
                bridging_surat_kontrol_bpjs.nm_poli_bpjs,
                bridging_sep.notelep,
                pasien.no_tlp,
                mjkn.nomorantrean,
                mjkn.angkaantrean,
                mjkn.jampraktek,
                mjkn.estimasidilayani,
                br.no_reg,
                br.waktu_kunjungan
            FROM bridging_surat_kontrol_bpjs 
            INNER JOIN bridging_sep ON bridging_surat_kontrol_bpjs.no_sep = bridging_sep.no_sep 
            LEFT JOIN pasien ON bridging_sep.nomr = pasien.no_rkm_medis
            LEFT JOIN referensi_mobilejkn_bpjs mjkn 
                ON (mjkn.norm = bridging_sep.nomr AND (mjkn.nomorreferensi = bridging_surat_kontrol_bpjs.no_surat OR mjkn.tanggalperiksa = bridging_surat_kontrol_bpjs.tgl_rencana) AND mjkn.status != 'Batal')
            LEFT JOIN booking_registrasi br
                ON (br.no_rkm_medis = bridging_sep.nomr AND br.tanggal_periksa = bridging_surat_kontrol_bpjs.tgl_rencana AND br.status != 'Batal')
            $where 
            GROUP BY bridging_surat_kontrol_bpjs.no_surat
            ORDER BY bridging_surat_kontrol_bpjs.tgl_rencana DESC";

    $result = mysqli_query($koneksi, $query);
    if ($result) {
        $total_rows = mysqli_num_rows($result);
        echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
        echo '<div style="font-weight: bold; color: #495057;">📊 Total Data: <span style="color: #007bff;">' . $total_rows . '</span> referensi</div>';
        echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
        echo '</div>';
        echo "<div class='table-responsive'><table>\n<tr>\n<th>No</th>\n<th>NO SURAT KONTROL</th>\n<th>NO RM</th>\n<th>NAMA PASIEN</th>\n<th>NO HP</th>\n<th>TANGGAL RENCANA</th>\n<th>DOKTER BPJS</th>\n<th>POLI BPJS</th>\n<th>NO ANTREAN</th>\n<th>WhatsApp</th>\n</tr>";
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            // Prioritaskan nomor telepon dari bridging_sep jika valid (ada angka selain nol), jika tidak gunakan no_tlp dari tabel pasien
            $nohp = (!empty($row['notelep']) && preg_match('/[1-9]/', $row['notelep'])) ? $row['notelep'] : (!empty($row['no_tlp']) ? $row['no_tlp'] : '');
            $is_valid_hp = !empty($nohp) && preg_match('/[1-9]/', $nohp);

            // Format nomor antrean
            $no_antrean = '-';
            if (!empty($row['angkaantrean'])) {
                $no_antrean = ltrim($row['angkaantrean'], '0');
                if ($no_antrean === '') $no_antrean = '0';
            } elseif (!empty($row['nomorantrean'])) {
                $no_antrean = $row['nomorantrean'];
            } elseif (!empty($row['no_reg'])) {
                $no_antrean = ltrim($row['no_reg'], '0');
                if ($no_antrean === '') $no_antrean = '0';
            }

            // Format estimasi jam
            $estimasi_jam = '-';
            if (!empty($row['estimasidilayani']) && is_numeric($row['estimasidilayani']) && $row['estimasidilayani'] > 0) {
                $ts = (int)($row['estimasidilayani'] / 1000);
                $h = (int)date('H', $ts);
                $h_end = $h + 1;
                $estimasi_jam = sprintf("%02d.00 - %02d.00 WIB", $h, $h_end);
            } elseif (!empty($row['jampraktek'])) {
                $jam = str_replace(':', '.', $row['jampraktek']);
                if (strpos($jam, '-') !== false) {
                    $parts = explode('-', $jam);
                    $estimasi_jam = trim($parts[0]) . " - " . trim($parts[1]) . " WIB";
                } else {
                    $estimasi_jam = trim($jam) . " WIB";
                }
            } elseif (!empty($row['waktu_kunjungan'])) {
                $h = (int)date('H', strtotime($row['waktu_kunjungan']));
                $estimasi_jam = sprintf("%02d.00 - %02d.00 WIB", $h, $h + 1);
            } else {
                $estimasi_jam = "08.00 - 12.00 WIB";
            }

            $pasien_js = htmlspecialchars(addslashes($row['nama_pasien']), ENT_QUOTES, 'UTF-8');
            $poli_js   = htmlspecialchars(addslashes($row['nm_poli_bpjs']), ENT_QUOTES, 'UTF-8');
            $dokter_js = htmlspecialchars(addslashes($row['nm_dokter_bpjs']), ENT_QUOTES, 'UTF-8');
            $surat_js  = htmlspecialchars(addslashes($row['no_surat']), ENT_QUOTES, 'UTF-8');
            $nohp_js   = htmlspecialchars(addslashes($nohp), ENT_QUOTES, 'UTF-8');
            $antrean_js = htmlspecialchars(addslashes($no_antrean), ENT_QUOTES, 'UTF-8');
            $jam_js    = htmlspecialchars(addslashes($estimasi_jam), ENT_QUOTES, 'UTF-8');

            echo "<tr>\n";
            echo "<td>{$no}</td>\n";
            echo "<td>{$row['no_surat']}</td>\n";
            echo "<td>{$row['nomr']}</td>\n";
            echo "<td>" . htmlspecialchars($row['nama_pasien']) . "</td>\n";
            echo "<td>" . htmlspecialchars($nohp ?: '-') . "</td>\n";
            echo "<td>{$row['tgl_rencana']}</td>\n";
            echo "<td>" . htmlspecialchars($row['nm_dokter_bpjs']) . "</td>\n";
            echo "<td>" . htmlspecialchars($row['nm_poli_bpjs']) . "</td>\n";
            echo "<td>" . htmlspecialchars($no_antrean) . "</td>\n";
            echo "<td>\n";
            echo "<button class='whatsapp-btn' onclick=\"openWhatsApp('{$nohp_js}', '{$pasien_js}', '{$row['tgl_rencana']}', '{$poli_js}', '{$dokter_js}', '{$surat_js}', '{$antrean_js}', '{$jam_js}')\" " . (!$is_valid_hp ? "disabled title='Nomor HP tidak tersedia atau tidak valid'" : "") . ">\n";
            echo "📱 WhatsApp\n";
            echo "</button>\n";
            echo "</td>\n";
            echo "</tr>\n";
            $no++;
        }
        echo "</table></div>";
        if ($total_rows == 0) {
            echo '<div class="no-data">📊 Tidak ada data referensi pada filter yang dipilih</div>';
        }
    } else {
        echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb;">';
        echo "❌ Terjadi kesalahan dalam query: " . mysqli_error($koneksi);
        echo '</div>';
    }
    mysqli_close($koneksi);
}
?>
        </div>
    </div>
</body>
</html>
