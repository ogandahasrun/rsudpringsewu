<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Ambil nama instansi dari database setting
$nama_instansi = "RSUD Pringsewu";
$query_instansi = "SELECT nama_instansi FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
if ($result_instansi && $row_instansi = mysqli_fetch_assoc($result_instansi)) {
    if (!empty($row_instansi['nama_instansi'])) {
        $nama_instansi = $row_instansi['nama_instansi'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referensi Pasien MJKN &amp; Kontrol - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <style>
        * {
            box-sizing: border-box;
        }
        body, table, th, td, input, select, button {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            margin: 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 1.8em;
            font-weight: bold;
        }
        .header p {
            margin: 0;
            font-size: 0.95em;
            opacity: 0.92;
        }
        .content {
            padding: 25px;
        }
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .back-button a {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
            font-size: 13px;
        }
        .back-button a:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .filter-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        .filter-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 20px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-group label {
            font-weight: bold;
            color: #495057;
            font-size: 13.5px;
        }
        .filter-group input,
        .filter-group select {
            padding: 11px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 13.5px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Summary Stat Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #007bff;
            border-radius: 10px;
            padding: 14px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .summary-card.card-irisan { border-left-color: #16a34a; }
        .summary-card.card-kontrol { border-left-color: #ea580c; }
        .summary-card.card-mjkn { border-left-color: #0284c7; }
        .summary-card .s-label {
            font-size: 12px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .summary-card .s-value {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
        }
        .summary-card .s-desc {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Source Category Badges */
        .source-badge {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }
        .source-irisan {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .source-kontrol {
            background: #ffedd5;
            color: #c2410c;
            border: 1px solid #fdba74;
        }
        .source-mjkn {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #7dd3fc;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 15px;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 1250px;
        }
        th {
            background: linear-gradient(45deg, #343a40, #495057);
            color: white;
            padding: 14px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 12.5px;
            white-space: nowrap;
        }
        td {
            padding: 11px 10px;
            border-bottom: 1px solid #e9ecef;
            font-size: 12.5px;
            vertical-align: middle;
        }
        tr:nth-child(even) td {
            background: #f8f9fa;
        }
        tr:hover td {
            background: #e3f2fd;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
        }

        /* WhatsApp Button Styling */
        .wa-btn-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 155px;
        }
        .whatsapp-btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-weight: bold;
            white-space: nowrap;
        }
        .whatsapp-btn:hover {
            background: #1DA851;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(37, 211, 102, 0.3);
        }
        .whatsapp-btn-warning {
            background: #ea580c;
        }
        .whatsapp-btn-warning:hover {
            background: #c2410c;
            box-shadow: 0 3px 8px rgba(234, 88, 12, 0.3);
        }
        .whatsapp-btn:disabled,
        .whatsapp-btn-warning:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Bulk Action Bar */
        .bulk-action-bar {
            background: linear-gradient(135deg, #1e293b, #334155);
            border-radius: 10px;
            padding: 12px 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            animation: fadeInDown 0.25s ease;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .bulk-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .bulk-badge {
            background: #0284c7;
            color: white;
            padding: 5px 12px;
            border-radius: 999px;
            font-weight: bold;
            font-size: 13px;
        }
        .bulk-hint {
            font-size: 13px;
            color: #cbd5e1;
        }
        .bulk-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-wa-bulk {
            background: linear-gradient(45deg, #16a34a, #22c55e);
            color: white;
            border: none;
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-wa-bulk:hover {
            background: linear-gradient(45deg, #15803d, #16a34a);
            transform: translateY(-1px);
        }
        .btn-wa-bulk-warning {
            background: linear-gradient(45deg, #ea580c, #f97316);
            color: white;
            border: none;
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(234, 88, 12, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-wa-bulk-warning:hover {
            background: linear-gradient(45deg, #c2410c, #ea580c);
            transform: translateY(-1px);
        }
        .btn-sm {
            padding: 8px 14px;
            font-size: 12px;
        }
        tr.row-processed td {
            background: #e8f5e9 !important;
        }
        .queue-status-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 4px;
            display: inline-block;
            text-align: center;
        }
        .queue-status-badge.sent {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .queue-status-badge.skipped {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        /* Queue Modal Styling */
        .queue-modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .queue-modal-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
        }
        .queue-modal-content {
            position: relative;
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            z-index: 10000;
            animation: modalZoomIn 0.25s ease-out;
        }
        @keyframes modalZoomIn {
            from { transform: scale(0.92); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .queue-modal-header {
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .queue-modal-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .queue-modal-title h3 {
            margin: 0;
            font-size: 1.15rem;
            color: #1e293b;
        }
        .queue-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #64748b;
            cursor: pointer;
            line-height: 1;
            padding: 0;
        }
        .queue-close-btn:hover { color: #0f172a; }
        .queue-modal-body {
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .queue-progress-section {
            background: #f1f5f9;
            padding: 14px 18px;
            border-radius: 10px;
        }
        .queue-progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #334155;
            margin-bottom: 8px;
        }
        .queue-progress-bar-bg {
            height: 10px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .queue-progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.3s ease;
        }
        .queue-patient-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
        .queue-patient-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .queue-badge-urutan {
            background: #0284c7;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .queue-patient-header h4 {
            margin: 0;
            font-size: 1.1rem;
            color: #0f172a;
        }
        .queue-patient-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 13px;
            color: #475569;
            margin-bottom: 12px;
        }
        .queue-msg-preview-wrap label {
            font-size: 11px;
            font-weight: bold;
            color: #64748b;
            display: block;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .queue-msg-preview {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12px;
            line-height: 1.45;
            color: #1e293b;
            max-height: 110px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-family: inherit;
            margin: 0;
        }
        .queue-controls-box {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .queue-timer-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f1f5f9;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .queue-timer-toggle label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: #334155;
            font-weight: 500;
        }
        .timer-delay-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .timer-delay-wrap select {
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
        }
        .countdown-badge {
            background: #ea580c;
            color: white;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: bold;
        }
        .queue-action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-wa-next {
            flex: 2;
            min-width: 200px;
            background: linear-gradient(45deg, #16a34a, #22c55e);
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-wa-next:hover {
            background: linear-gradient(45deg, #15803d, #16a34a);
            transform: translateY(-1px);
        }
        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-danger:hover { background: #dc2626; }
        .queue-tip {
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 10px 14px;
            border-radius: 8px;
        }
        .queue-completed-view {
            text-align: center;
            padding: 25px 10px;
        }
        .queue-completed-view .completed-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .queue-completed-view h3 {
            margin: 0 0 8px;
            color: #15803d;
            font-size: 1.3rem;
        }
        .queue-completed-view p {
            margin: 0 0 16px;
            color: #64748b;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px 15px; }
            .header h1 { font-size: 1.4em; }
            .content { padding: 15px; }
            .filter-form { padding: 20px 15px; }
            .filter-grid { grid-template-columns: 1fr; gap: 15px; }
            .btn { padding: 10px 15px; font-size: 13px; }
            th, td { padding: 8px 6px; font-size: 12px; }
        }
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
            document.getElementById('kd_poli_bpjs').value = '';
            document.getElementById('nm_dokter_bpjs').value = '';
            document.getElementById('kategori_sumber').value = 'semua';
        }

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

        const NAMA_INSTANSI = <?php echo json_encode($nama_instansi); ?>;

        function sanitizeWhatsAppNumber(nohp) {
            if (!nohp || nohp.trim() === '') {
                alert('❌ Nomor HP tidak tersedia');
                return null;
            }
            let cleanNumber = nohp.replace(/\D/g, '');
            if (/^0+$/.test(cleanNumber) || cleanNumber.length < 9) {
                alert('❌ Nomor HP tidak valid (' + nohp + ')');
                return null;
            }
            if (cleanNumber.startsWith('0')) {
                cleanNumber = '62' + cleanNumber.substring(1);
            } else if (!cleanNumber.startsWith('62')) {
                cleanNumber = '62' + cleanNumber;
            }
            return cleanNumber;
        }

        function formatKlinikTujuan(nmPoli) {
            let klinikTujuan = nmPoli ? nmPoli.trim() : '';
            if (klinikTujuan !== '' && !/^klinik\s+/i.test(klinikTujuan)) {
                if (/^poli\s+/i.test(klinikTujuan)) {
                    klinikTujuan = klinikTujuan.replace(/^poli\s+/i, 'Klinik ');
                } else {
                    klinikTujuan = 'Klinik ' + klinikTujuan;
                }
            }
            return klinikTujuan;
        }

        function launchWhatsAppUrl(cleanNumber, message) {
            const encodedMessage = encodeURIComponent(message);
            const isMobile = /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
            const whatsappUrl = isMobile 
                ? `https://api.whatsapp.com/send?phone=${cleanNumber}&text=${encodedMessage}`
                : `https://web.whatsapp.com/send?phone=${cleanNumber}&text=${encodedMessage}`;
            window.open(whatsappUrl, '_blank');
        }

        // 1. Buka WhatsApp untuk pengingat jadwal kontrol / kunjungan pasien
        function openWhatsApp(nohp, namaPasien, tglRencana, nmPoli, nmDokter, noAntrean, estimasiJam, rowIndex) {
            const cleanNumber = sanitizeWhatsAppNumber(nohp);
            if (!cleanNumber) return;

            const hariTanggal = formatHariTanggalIndo(tglRencana);
            const klinikTujuan = formatKlinikTujuan(nmPoli) || '-';

            let antreanDisplay = (noAntrean !== undefined && noAntrean !== null && noAntrean !== '') ? String(noAntrean).trim() : '-';
            if (/^0+[0-9]+$/.test(antreanDisplay)) {
                antreanDisplay = antreanDisplay.replace(/^0+/, '');
            }

            let jamDisplay = (estimasiJam !== undefined && estimasiJam !== null && estimasiJam !== '') ? String(estimasiJam).trim() : '08.00 - 12.00 WIB';

            let message = `Yth. Bapak/Ibu ${namaPasien},\n\n` +
                          `Mengingatkan jadwal kontrol/berobat Anda di ${NAMA_INSTANSI}:\n\n` +
                          `Hari, Tanggal: ${hariTanggal}\n` +
                          `Klinik Tujuan : ${klinikTujuan}\n` +
                          `Dokter : ${nmDokter}\n` +
                          `Nomor Antrean: ${antreanDisplay}\n\n` +
                          `Estimasi Jam : ${jamDisplay}\n\n` +
                          `Penting untuk Diperhatikan:\n\n` +
                          `Mohon melakukan Check-in / Fingerprint di lokasi ${NAMA_INSTANSI} 15 menit sebelum estimasi pelayanan.\n\n` +
                          `Sesuai ketentuan BPJS Kesehatan, pasien wajib datang tepat pada tanggal yang tertera di Surat Kontrol (tidak bisa maju/mundur).\n\n` +
                          `Jika membutuhkan bantuan atau konfirmasi kendala jadwal, silakan balas pesan ini.\n\n` +
                          `Terima kasih.`;

            launchWhatsAppUrl(cleanNumber, message);
            if (rowIndex) markRowProcessed(rowIndex, '✅ Terbuka', 'sent');
        }

        // 2. Buka WhatsApp untuk pemberitahuan dokter berhalangan hadir
        function openWhatsAppDokterBerhalangan(nohp, namaPasien, tglRencana, nmPoli, nmDokter, rowIndex) {
            const cleanNumber = sanitizeWhatsAppNumber(nohp);
            if (!cleanNumber) return;

            const hariTanggal = formatHariTanggalIndo(tglRencana);
            const klinikTujuan = formatKlinikTujuan(nmPoli);
            const bagianPoli = klinikTujuan ? ` ${klinikTujuan}` : '';

            let message = `Halo ${namaPasien} \n` +
                          `Kami dari ${NAMA_INSTANSI} akan menginformasikan terkait jadwal pemeriksaan Anda. \n\n` +
                          `Dokter ${nmDokter}${bagianPoli} berhalangan hadir pada ${hariTanggal}.\n` +
                          `Untuk reschedule jadwal atau pengambilan ulang jadwal kontrol, harap konfirmasi ke Nomor Pendaftaran ${NAMA_INSTANSI} di https://wa.me/6281394022020\n\n` +
                          `Apabila sudah mendapatkan jadwal kontrol baru, Silahkan ambil antrian ulang di aplikasi MJKN. \n\n` +
                          `Mohon maaf atas ketidaknyamananya. Terimakasih`;

            launchWhatsAppUrl(cleanNumber, message);
            if (rowIndex) markRowProcessed(rowIndex, '✅ Terbuka', 'sent');
        }

        // ===== BULK QUEUE STATE & FUNCTIONS =====
        let queueData = [];
        let queueIndex = 0;
        let queueType = 'pengingat';
        let queueTimerInterval = null;
        let queueCountdown = 0;

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.patient-checkbox:checked');
            const totalValid = document.querySelectorAll('.patient-checkbox:not(:disabled)').length;
            const count = checkboxes.length;

            const bulkBar = document.getElementById('bulkActionBar');
            const countBadge = document.getElementById('selectedCountBadge');
            const numSpans = document.querySelectorAll('.selected-num');
            const checkAll = document.getElementById('checkAll');

            if (count > 0) {
                if (bulkBar) bulkBar.style.display = 'flex';
                if (countBadge) countBadge.textContent = count + ' Pasien Terpilih';
                numSpans.forEach(el => el.textContent = count);
            } else {
                if (bulkBar) bulkBar.style.display = 'none';
                numSpans.forEach(el => el.textContent = '0');
            }

            if (checkAll) {
                if (count === 0) {
                    checkAll.checked = false;
                    checkAll.indeterminate = false;
                } else if (count === totalValid && totalValid > 0) {
                    checkAll.checked = true;
                    checkAll.indeterminate = false;
                } else {
                    checkAll.checked = false;
                    checkAll.indeterminate = true;
                }
            }
        }

        function toggleSelectAll(masterCheckbox) {
            const checkboxes = document.querySelectorAll('.patient-checkbox:not(:disabled)');
            checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
            updateSelectedCount();
        }

        function clearAllSelections() {
            const checkboxes = document.querySelectorAll('.patient-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            const checkAll = document.getElementById('checkAll');
            if (checkAll) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
            }
            updateSelectedCount();
        }

        function buildQueueMessage(type, item) {
            const hariTanggal = formatHariTanggalIndo(item.tgl);
            const klinikTujuan = formatKlinikTujuan(item.poli);
            const bagianPoli = klinikTujuan ? ` ${klinikTujuan}` : '';

            if (type === 'berhalangan') {
                return `Halo ${item.pasien} \n` +
                       `Kami dari ${NAMA_INSTANSI} akan menginformasikan terkait jadwal pemeriksaan Anda. \n\n` +
                       `Dokter ${item.dokter}${bagianPoli} berhalangan hadir pada ${hariTanggal}.\n` +
                       `Untuk reschedule jadwal atau pengambilan ulang jadwal kontrol, harap konfirmasi ke Nomor Pendaftaran ${NAMA_INSTANSI} di https://wa.me/6281394022020\n\n` +
                       `Apabila sudah mendapatkan jadwal kontrol baru, Silahkan ambil antrian ulang di aplikasi MJKN. \n\n` +
                       `Mohon maaf atas ketidaknyamananya. Terimakasih`;
            } else {
                let antreanDisplay = (item.antrean !== undefined && item.antrean !== null && item.antrean !== '') ? String(item.antrean).trim() : '-';
                if (/^0+[0-9]+$/.test(antreanDisplay)) {
                    antreanDisplay = antreanDisplay.replace(/^0+/, '');
                }
                let jamDisplay = (item.jam !== undefined && item.jam !== null && item.jam !== '') ? String(item.jam).trim() : '08.00 - 12.00 WIB';

                return `Yth. Bapak/Ibu ${item.pasien},\n\n` +
                       `Mengingatkan jadwal kontrol/berobat Anda di ${NAMA_INSTANSI}:\n\n` +
                       `Hari, Tanggal: ${hariTanggal}\n` +
                       `Klinik Tujuan : ${klinikTujuan || '-'}\n` +
                       `Dokter : ${item.dokter}\n` +
                       `Nomor Antrean: ${antreanDisplay}\n\n` +
                       `Estimasi Jam : ${jamDisplay}\n\n` +
                       `Penting untuk Diperhatikan:\n\n` +
                       `Mohon melakukan Check-in / Fingerprint di lokasi ${NAMA_INSTANSI} 15 menit sebelum estimasi pelayanan.\n\n` +
                       `Sesuai ketentuan BPJS Kesehatan, pasien wajib datang tepat pada tanggal yang tertera di Surat Kontrol (tidak bisa maju/mundur).\n\n` +
                       `Jika membutuhkan bantuan atau konfirmasi kendala jadwal, silakan balas pesan ini.\n\n` +
                       `Terima kasih.`;
            }
        }

        function startBulkQueue(type) {
            const checkedBoxes = Array.from(document.querySelectorAll('.patient-checkbox:checked'));
            if (checkedBoxes.length === 0) {
                alert('⚠️ Silakan pilih/centang minimal 1 pasien terlebih dahulu.');
                return;
            }

            queueData = checkedBoxes.map(cb => ({
                index: cb.getAttribute('data-index'),
                nohp: cb.getAttribute('data-nohp'),
                pasien: cb.getAttribute('data-pasien'),
                tgl: cb.getAttribute('data-tgl'),
                poli: cb.getAttribute('data-poli'),
                dokter: cb.getAttribute('data-dokter'),
                antrean: cb.getAttribute('data-antrean'),
                jam: cb.getAttribute('data-jam')
            }));

            queueIndex = 0;
            queueType = type;
            stopAutoTimerCountdown();

            const modal = document.getElementById('waQueueModal');
            const modalTitle = document.getElementById('queueModalTitle');
            const modalIcon = document.getElementById('queueModalIcon');

            if (type === 'berhalangan') {
                modalTitle.textContent = 'Antrean Kirim Info Dokter Berhalangan';
                modalIcon.textContent = '⚠️';
            } else {
                modalTitle.textContent = 'Antrean Kirim Pengingat Kehadiran';
                modalIcon.textContent = '📢';
            }

            document.getElementById('queueActiveCard').style.display = 'block';
            document.getElementById('queueControlsBox').style.display = 'flex';
            document.getElementById('queueCompletedView').style.display = 'none';

            if (modal) modal.style.display = 'flex';
            renderQueueCurrent();
        }

        function renderQueueCurrent() {
            stopAutoTimerCountdown();

            if (queueIndex >= queueData.length) {
                document.getElementById('queueActiveCard').style.display = 'none';
                document.getElementById('queueControlsBox').style.display = 'none';
                document.getElementById('queueCompletedView').style.display = 'block';
                document.getElementById('queueCompletedSummary').textContent = 
                    `Selesai memproses ${queueData.length} pasien terpilih.`;
                document.getElementById('queueProgressText').textContent = 
                    `${queueData.length} dari ${queueData.length} (100%)`;
                document.getElementById('queueProgressBar').style.width = '100%';
                return;
            }

            const current = queueData[queueIndex];
            const pct = Math.round(((queueIndex) / queueData.length) * 100);

            document.getElementById('queueProgressText').textContent = 
                `${queueIndex + 1} dari ${queueData.length} (${pct}%)`;
            document.getElementById('queueProgressBar').style.width = pct + '%';

            document.getElementById('queueUrutanBadge').textContent = `Pasien #${queueIndex + 1} dari ${queueData.length}`;
            document.getElementById('queuePatientName').textContent = current.pasien;
            document.getElementById('queuePatientHp').textContent = current.nohp || '-';
            document.getElementById('queuePatientPoli').textContent = current.poli || '-';
            document.getElementById('queuePatientDokter').textContent = current.dokter || '-';
            document.getElementById('queuePatientTgl').textContent = formatHariTanggalIndo(current.tgl);

            const msg = buildQueueMessage(queueType, current);
            document.getElementById('queueMsgPreview').textContent = msg;

            const btnNext = document.getElementById('btnQueueNext');
            btnNext.textContent = `▶ Buka WA Pasien Ini & Lanjut (${queueIndex + 1}/${queueData.length})`;
        }

        function markRowProcessed(index, label, type) {
            const row = document.getElementById('row-' + index);
            if (row) row.classList.add('row-processed');

            const badge = document.getElementById('status-badge-' + index);
            if (badge) {
                badge.style.display = 'inline-block';
                badge.className = 'queue-status-badge ' + (type || 'sent');
                badge.textContent = label || '✅ Terbuka';
            }
        }

        function processQueueCurrentAndAdvance() {
            stopAutoTimerCountdown();

            if (queueIndex >= queueData.length) return;

            const current = queueData[queueIndex];
            const cleanNumber = sanitizeWhatsAppNumber(current.nohp);

            if (cleanNumber) {
                const message = buildQueueMessage(queueType, current);
                const encodedMessage = encodeURIComponent(message);
                const isMobile = /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
                const whatsappUrl = isMobile 
                    ? `https://api.whatsapp.com/send?phone=${cleanNumber}&text=${encodedMessage}`
                    : `https://web.whatsapp.com/send?phone=${cleanNumber}&text=${encodedMessage}`;

                window.open(whatsappUrl, 'waQueueWindow');
                markRowProcessed(current.index, '✅ Terbuka', 'sent');
            }

            queueIndex++;

            if (queueIndex < queueData.length) {
                renderQueueCurrent();
                checkAndStartAutoTimer();
            } else {
                renderQueueCurrent();
            }
        }

        function skipQueueCurrent() {
            stopAutoTimerCountdown();
            if (queueIndex >= queueData.length) return;

            const current = queueData[queueIndex];
            markRowProcessed(current.index, '⏭ Dilewati', 'skipped');
            queueIndex++;
            renderQueueCurrent();
            checkAndStartAutoTimer();
        }

        function checkAndStartAutoTimer() {
            const autoCheck = document.getElementById('queueAutoTimerCheck');
            if (autoCheck && autoCheck.checked && queueIndex < queueData.length) {
                const delaySelect = document.getElementById('queueDelaySec');
                const delaySec = parseInt(delaySelect ? delaySelect.value : '5', 10) || 5;
                queueCountdown = delaySec;

                const badge = document.getElementById('queueCountdownBadge');
                if (badge) {
                    badge.style.display = 'inline-block';
                    badge.textContent = `⏱ Lanjut dalam ${queueCountdown}s...`;
                }

                queueTimerInterval = setInterval(() => {
                    queueCountdown--;
                    if (badge) badge.textContent = `⏱ Lanjut dalam ${queueCountdown}s...`;
                    if (queueCountdown <= 0) {
                        stopAutoTimerCountdown();
                        processQueueCurrentAndAdvance();
                    }
                }, 1000);
            }
        }

        function stopAutoTimerCountdown() {
            if (queueTimerInterval) {
                clearInterval(queueTimerInterval);
                queueTimerInterval = null;
            }
            const badge = document.getElementById('queueCountdownBadge');
            if (badge) badge.style.display = 'none';
        }

        function toggleAutoTimer() {
            const autoCheck = document.getElementById('queueAutoTimerCheck');
            if (autoCheck && autoCheck.checked) {
                checkAndStartAutoTimer();
            } else {
                stopAutoTimerCountdown();
            }
        }

        function changeDelaySec() {
            const autoCheck = document.getElementById('queueAutoTimerCheck');
            if (autoCheck && autoCheck.checked) {
                stopAutoTimerCountdown();
                checkAndStartAutoTimer();
            }
        }

        function closeQueueModalConfirm() {
            stopAutoTimerCountdown();
            const modal = document.getElementById('waQueueModal');
            if (modal) modal.style.display = 'none';
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋📱 Referensi Gabungan Pasien MJKN &amp; Kontrol</h1>
            <p>Integrasi data Surat Kontrol BPJS &amp; Antrean Mobile JKN tanpa duplikasi baris</p>
        </div>

        <div class="content">
            <div class="top-nav">
                <div class="back-button">
                    <a href="bpjs.php">← Kembali ke Menu BPJS</a>
                </div>
                <div class="back-button" style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a href="referensipasienmjkn.php" style="background:#0284c7;">📱 Halaman MJKN Saja</a>
                    <a href="referensipasienkontrol.php" style="background:#16a34a;">📋 Halaman Kontrol Saja</a>
                </div>
            </div>

<?php
// Ambil daftar Poliklinik BPJS (gabungan mapping & surat kontrol)
$poli_options = [];
$qPoliOpt = "SELECT mp.kd_poli_bpjs, mp.nm_poli_bpjs, pl.nm_poli
             FROM maping_poli_bpjs mp
             LEFT JOIN poliklinik pl ON mp.kd_poli_rs = pl.kd_poli
             ORDER BY COALESCE(pl.nm_poli, mp.nm_poli_bpjs) ASC";
$resPoliOpt = mysqli_query($koneksi, $qPoliOpt);
if ($resPoliOpt) {
    while ($r = mysqli_fetch_assoc($resPoliOpt)) {
        $kd = $r['kd_poli_bpjs'];
        $label = !empty($r['nm_poli']) ? ($r['nm_poli'] . ' (' . $r['nm_poli_bpjs'] . ')') : $r['nm_poli_bpjs'];
        $poli_options[$kd] = $label;
    }
}

// Ambil daftar Dokter BPJS
$dokter_options = [];
$qDokOpt = "SELECT DISTINCT nm_dokter_bpjs FROM (
                SELECT nm_dokter_bpjs FROM maping_dokter_dpjpvclaim WHERE nm_dokter_bpjs != ''
                UNION
                SELECT nm_dokter_bpjs FROM bridging_surat_kontrol_bpjs WHERE nm_dokter_bpjs != ''
            ) t ORDER BY nm_dokter_bpjs ASC";
$resDokOpt = mysqli_query($koneksi, $qDokOpt);
if ($resDokOpt) {
    while ($r = mysqli_fetch_assoc($resDokOpt)) {
        $dokter_options[] = $r['nm_dokter_bpjs'];
    }
}

$tanggal_awal    = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir   = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
$kd_poli_bpjs    = isset($_POST['kd_poli_bpjs']) ? $_POST['kd_poli_bpjs'] : '';
$nm_dokter_bpjs  = isset($_POST['nm_dokter_bpjs']) ? $_POST['nm_dokter_bpjs'] : '';
$kategori_sumber = isset($_POST['kategori_sumber']) ? $_POST['kategori_sumber'] : 'semua';
?>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Gabungan Pasien MJKN &amp; Rencana Kontrol
                </div>

                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Periksa / Rencana Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Periksa / Rencana Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="kd_poli_bpjs">🏥 Poliklinik</label>
                        <select id="kd_poli_bpjs" name="kd_poli_bpjs">
                            <option value="">-- Semua Poliklinik --</option>
                            <?php foreach ($poli_options as $kd => $label): ?>
                                <option value="<?php echo htmlspecialchars($kd); ?>" <?php if ($kd_poli_bpjs === $kd) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="nm_dokter_bpjs">👨‍⚕️ Dokter BPJS</label>
                        <select id="nm_dokter_bpjs" name="nm_dokter_bpjs">
                            <option value="">-- Semua Dokter --</option>
                            <?php foreach ($dokter_options as $dok): ?>
                                <option value="<?php echo htmlspecialchars($dok); ?>" <?php if ($nm_dokter_bpjs === $dok) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($dok); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="kategori_sumber">📌 Status Sumber Data</label>
                        <select id="kategori_sumber" name="kategori_sumber">
                            <option value="semua" <?php if ($kategori_sumber === 'semua') echo 'selected'; ?>>Semua Gabungan (Tanpa Duplikat)</option>
                            <option value="irisan" <?php if ($kategori_sumber === 'irisan') echo 'selected'; ?>>✅ Irisan: Kontrol + Sudah Daftar MJKN</option>
                            <option value="kontrol_saja" <?php if ($kategori_sumber === 'kontrol_saja') echo 'selected'; ?>>⚠️ Hanya Kontrol: Belum Ambil Antrean MJKN</option>
                            <option value="mjkn_saja" <?php if ($kategori_sumber === 'mjkn_saja') echo 'selected'; ?>>📱 Hanya MJKN: Non-Kontrol / Rujukan</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        📊 Tampilkan Data Gabungan
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

<?php
if (isset($_POST['filter'])) {
    $tglAwalEsc  = mysqli_real_escape_string($koneksi, $tanggal_awal);
    $tglAkhirEsc = mysqli_real_escape_string($koneksi, $tanggal_akhir);

    // -----------------------------------------------------------------
    // 1. Ambil Data Surat Kontrol (bridging_surat_kontrol_bpjs)
    // -----------------------------------------------------------------
    $whereKontrol = ["sk.tgl_rencana BETWEEN '$tglAwalEsc' AND '$tglAkhirEsc'"];
    if ($kd_poli_bpjs !== '') {
        $whereKontrol[] = "sk.kd_poli_bpjs = '" . mysqli_real_escape_string($koneksi, $kd_poli_bpjs) . "'";
    }
    if ($nm_dokter_bpjs !== '') {
        $whereKontrol[] = "sk.nm_dokter_bpjs = '" . mysqli_real_escape_string($koneksi, $nm_dokter_bpjs) . "'";
    }

    $sqlKontrol = "SELECT 
                        sk.no_surat,
                        sk.no_sep,
                        sep.nomr AS no_rkm_medis,
                        COALESCE(NULLIF(p.nm_pasien, ''), sep.nama_pasien) AS nm_pasien,
                        p.no_ktp AS nik,
                        sep.no_kartu AS nomorkartu,
                        sep.notelep,
                        p.no_tlp,
                        sk.tgl_rencana AS tanggal,
                        sk.kd_dokter_bpjs,
                        sk.nm_dokter_bpjs,
                        sk.kd_poli_bpjs,
                        sk.nm_poli_bpjs,
                        pl.nm_poli,
                        br.no_reg,
                        br.waktu_kunjungan
                   FROM bridging_surat_kontrol_bpjs sk
                   INNER JOIN bridging_sep sep ON sk.no_sep = sep.no_sep
                   LEFT JOIN pasien p ON sep.nomr = p.no_rkm_medis
                   LEFT JOIN maping_poli_bpjs mp ON sk.kd_poli_bpjs = mp.kd_poli_bpjs
                   LEFT JOIN poliklinik pl ON mp.kd_poli_rs = pl.kd_poli
                   LEFT JOIN booking_registrasi br 
                       ON (br.no_rkm_medis = sep.nomr AND br.tanggal_periksa = sk.tgl_rencana AND br.status != 'Batal')
                   WHERE " . implode(" AND ", $whereKontrol) . "
                   GROUP BY sk.no_surat
                   ORDER BY sk.tgl_rencana DESC";

    $resKontrol = mysqli_query($koneksi, $sqlKontrol);
    $kontrolList = [];
    $kontrolBySurat = [];
    $kontrolByRmTglPoli = [];
    $kontrolByRmTgl = [];

    if ($resKontrol) {
        while ($rk = mysqli_fetch_assoc($resKontrol)) {
            $idx = count($kontrolList);
            $kontrolList[$idx] = $rk;
            if (!empty($rk['no_surat'])) {
                $kontrolBySurat[$rk['no_surat']] = $idx;
            }
            $keyPoli = $rk['no_rkm_medis'] . '|' . $rk['tanggal'] . '|' . $rk['kd_poli_bpjs'];
            $kontrolByRmTglPoli[$keyPoli][] = $idx;

            $keyDate = $rk['no_rkm_medis'] . '|' . $rk['tanggal'];
            $kontrolByRmTgl[$keyDate][] = $idx;
        }
    }

    // -----------------------------------------------------------------
    // 2. Ambil Data Mobile JKN (referensi_mobilejkn_bpjs)
    // -----------------------------------------------------------------
    $whereMjkn = ["m.tanggalperiksa BETWEEN '$tglAwalEsc' AND '$tglAkhirEsc'"];
    if ($kd_poli_bpjs !== '') {
        $whereMjkn[] = "m.kodepoli = '" . mysqli_real_escape_string($koneksi, $kd_poli_bpjs) . "'";
    }
    if ($nm_dokter_bpjs !== '') {
        $whereMjkn[] = "md.nm_dokter_bpjs = '" . mysqli_real_escape_string($koneksi, $nm_dokter_bpjs) . "'";
    }

    $sqlMjkn = "SELECT
                    m.tanggalperiksa AS tanggal,
                    m.nobooking,
                    m.no_rawat,
                    COALESCE(NULLIF(rp.no_rkm_medis, ''), m.norm) AS no_rkm_medis,
                    p.nm_pasien,
                    m.nik,
                    m.nomorkartu,
                    m.nohp,
                    p.no_tlp,
                    m.kodepoli AS kd_poli_bpjs,
                    mp.nm_poli_bpjs,
                    pl.nm_poli,
                    m.kodedokter AS kd_dokter_bpjs,
                    md.nm_dokter_bpjs,
                    m.jeniskunjungan,
                    m.nomorreferensi,
                    m.nomorantrean,
                    m.angkaantrean,
                    m.estimasidilayani,
                    m.jampraktek,
                    m.status AS status_mjkn,
                    sk_ref.no_surat AS ref_no_surat
                FROM referensi_mobilejkn_bpjs m
                INNER JOIN reg_periksa rp ON m.no_rawat = rp.no_rawat
                INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                INNER JOIN maping_dokter_dpjpvclaim md ON m.kodedokter = md.kd_dokter_bpjs
                INNER JOIN maping_poli_bpjs mp ON m.kodepoli = mp.kd_poli_bpjs
                INNER JOIN poliklinik pl ON mp.kd_poli_rs = pl.kd_poli
                LEFT JOIN bridging_surat_kontrol_bpjs sk_ref ON m.nomorreferensi = sk_ref.no_surat
                WHERE " . implode(" AND ", $whereMjkn) . "
                GROUP BY m.nobooking
                ORDER BY m.tanggalperiksa DESC, m.nobooking ASC";

    $resMjkn = mysqli_query($koneksi, $sqlMjkn);

    $matchedKontrolIdx = [];
    $combinedRows = [];

    $countIrisan = 0;
    $countKontrolSaja = 0;
    $countMjknSaja = 0;

    // Helper pilih nomor HP terbaik (mengandung angka 1-9)
    $pickValidHp = function($candidates) {
        foreach ($candidates as $hp) {
            if (!empty($hp) && preg_match('/[1-9]/', $hp)) {
                return trim($hp);
            }
        }
        return '';
    };

    if ($resMjkn) {
        while ($rm = mysqli_fetch_assoc($resMjkn)) {
            $matchIdx = null;

            // Cek pencocokan 1: nomorreferensi == no_surat pada daftar kontrol periode ini
            if (!empty($rm['nomorreferensi']) && isset($kontrolBySurat[$rm['nomorreferensi']])) {
                $cand = $kontrolBySurat[$rm['nomorreferensi']];
                if (!isset($matchedKontrolIdx[$cand])) {
                    $matchIdx = $cand;
                }
            }

            // Cek pencocokan 2: No RM + Tanggal + Kode Poli BPJS sama
            if ($matchIdx === null) {
                $kPoli = $rm['no_rkm_medis'] . '|' . $rm['tanggal'] . '|' . $rm['kd_poli_bpjs'];
                if (!empty($kontrolByRmTglPoli[$kPoli])) {
                    foreach ($kontrolByRmTglPoli[$kPoli] as $cand) {
                        if (!isset($matchedKontrolIdx[$cand])) {
                            $matchIdx = $cand;
                            break;
                        }
                    }
                }
            }

            // Cek pencocokan 3: No RM + Tanggal sama
            if ($matchIdx === null) {
                $kDate = $rm['no_rkm_medis'] . '|' . $rm['tanggal'];
                if (!empty($kontrolByRmTgl[$kDate])) {
                    foreach ($kontrolByRmTgl[$kDate] as $cand) {
                        if (!isset($matchedKontrolIdx[$cand])) {
                            $matchIdx = $cand;
                            break;
                        }
                    }
                }
            }

            if ($matchIdx !== null) {
                // PASANGAN IRISAN (Ada di Surat Kontrol & Ada di MJKN)
                $matchedKontrolIdx[$matchIdx] = true;
                $rk = $kontrolList[$matchIdx];
                $countIrisan++;

                $nohp = $pickValidHp([$rm['nohp'], $rk['notelep'], $rm['no_tlp'], $rk['no_tlp']]);

                $combinedRows[] = [
                    'kategori'         => 'irisan',
                    'kategori_label'   => 'Kontrol + MJKN',
                    'tanggal'          => $rm['tanggal'],
                    'no_surat'         => $rk['no_surat'],
                    'nobooking'        => $rm['nobooking'],
                    'no_rawat'         => $rm['no_rawat'],
                    'no_rkm_medis'     => $rm['no_rkm_medis'],
                    'nm_pasien'        => $rm['nm_pasien'] ?: $rk['nm_pasien'],
                    'nik'              => $rm['nik'] ?: $rk['nik'],
                    'nomorkartu'       => $rm['nomorkartu'] ?: $rk['nomorkartu'],
                    'nohp'             => $nohp,
                    'nm_poli_display'  => $rm['nm_poli'] ?: $rk['nm_poli_bpjs'],
                    'nm_poli_bpjs'     => $rk['nm_poli_bpjs'] ?: $rm['nm_poli_bpjs'],
                    'nm_dokter_bpjs'   => $rm['nm_dokter_bpjs'] ?: $rk['nm_dokter_bpjs'],
                    'nomorantrean'     => $rm['nomorantrean'],
                    'angkaantrean'     => $rm['angkaantrean'],
                    'no_reg'           => $rk['no_reg'],
                    'estimasidilayani' => $rm['estimasidilayani'],
                    'jampraktek'       => $rm['jampraktek'],
                    'waktu_kunjungan'  => $rk['waktu_kunjungan']
                ];
            } else {
                // Jika nomorreferensi merujuk ke surat kontrol di tabel bridging_surat_kontrol_bpjs, tetap kategori irisan
                if (!empty($rm['ref_no_surat'])) {
                    $countIrisan++;
                    $kat = 'irisan';
                    $katLabel = 'Kontrol + MJKN';
                    $noSuratVal = $rm['ref_no_surat'];
                } else {
                    $countMjknSaja++;
                    $kat = 'mjkn_saja';
                    $katLabel = 'MJKN (Non-Kontrol)';
                    $noSuratVal = '-';
                }

                $nohp = $pickValidHp([$rm['nohp'], $rm['no_tlp']]);

                $combinedRows[] = [
                    'kategori'         => $kat,
                    'kategori_label'   => $katLabel,
                    'tanggal'          => $rm['tanggal'],
                    'no_surat'         => $noSuratVal,
                    'nobooking'        => $rm['nobooking'],
                    'no_rawat'         => $rm['no_rawat'],
                    'no_rkm_medis'     => $rm['no_rkm_medis'],
                    'nm_pasien'        => $rm['nm_pasien'],
                    'nik'              => $rm['nik'],
                    'nomorkartu'       => $rm['nomorkartu'],
                    'nohp'             => $nohp,
                    'nm_poli_display'  => $rm['nm_poli'] ?: $rm['nm_poli_bpjs'],
                    'nm_poli_bpjs'     => $rm['nm_poli_bpjs'],
                    'nm_dokter_bpjs'   => $rm['nm_dokter_bpjs'],
                    'nomorantrean'     => $rm['nomorantrean'],
                    'angkaantrean'     => $rm['angkaantrean'],
                    'no_reg'           => '',
                    'estimasidilayani' => $rm['estimasidilayani'],
                    'jampraktek'       => $rm['jampraktek'],
                    'waktu_kunjungan'  => ''
                ];
            }
        }
    }

    // -----------------------------------------------------------------
    // 3. Tambahkan Pasien Kontrol yang BELUM mengambil antrean via MJKN
    // -----------------------------------------------------------------
    foreach ($kontrolList as $idx => $rk) {
        if (isset($matchedKontrolIdx[$idx])) {
            continue;
        }
        $countKontrolSaja++;
        $nohp = $pickValidHp([$rk['notelep'], $rk['no_tlp']]);

        $combinedRows[] = [
            'kategori'         => 'kontrol_saja',
            'kategori_label'   => 'Kontrol (Belum MJKN)',
            'tanggal'          => $rk['tanggal'],
            'no_surat'         => $rk['no_surat'],
            'nobooking'        => '-',
            'no_rawat'         => '-',
            'no_rkm_medis'     => $rk['no_rkm_medis'],
            'nm_pasien'        => $rk['nm_pasien'],
            'nik'              => $rk['nik'] ?: '-',
            'nomorkartu'       => $rk['nomorkartu'] ?: '-',
            'nohp'             => $nohp,
            'nm_poli_display'  => $rk['nm_poli'] ?: $rk['nm_poli_bpjs'],
            'nm_poli_bpjs'     => $rk['nm_poli_bpjs'],
            'nm_dokter_bpjs'   => $rk['nm_dokter_bpjs'],
            'nomorantrean'     => '',
            'angkaantrean'     => '',
            'no_reg'           => $rk['no_reg'],
            'estimasidilayani' => '',
            'jampraktek'       => '',
            'waktu_kunjungan'  => $rk['waktu_kunjungan']
        ];
    }

    $totalGabunganUnik = count($combinedRows);

    // Filter berdasarkan pilihan dropdown kategori_sumber
    if ($kategori_sumber !== 'semua') {
        $combinedRows = array_values(array_filter($combinedRows, function($item) use ($kategori_sumber) {
            return $item['kategori'] === $kategori_sumber;
        }));
    }

    // Urutkan berdasarkan tanggal DESC, lalu nama pasien ASC
    usort($combinedRows, function($a, $b) {
        $cmp = strcmp($b['tanggal'], $a['tanggal']);
        if ($cmp !== 0) return $cmp;
        return strcmp($a['nm_pasien'], $b['nm_pasien']);
    });

    $totalDisplayed = count($combinedRows);
?>

            <!-- Ringkasan Statistik Deduplikasi -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="s-label">Total Gabungan (Unik)</div>
                    <div class="s-value"><?php echo number_format($totalGabunganUnik, 0, ',', '.'); ?></div>
                    <div class="s-desc">Pasien tanpa duplikasi baris</div>
                </div>
                <div class="summary-card card-irisan">
                    <div class="s-label">✅ Irisan (Kontrol + MJKN)</div>
                    <div class="s-value"><?php echo number_format($countIrisan, 0, ',', '.'); ?></div>
                    <div class="s-desc">Punya Surat Kontrol &amp; sudah ambil MJKN</div>
                </div>
                <div class="summary-card card-kontrol">
                    <div class="s-label">⚠️ Kontrol (Belum MJKN)</div>
                    <div class="s-value"><?php echo number_format($countKontrolSaja, 0, ',', '.'); ?></div>
                    <div class="s-desc">Ada jadwal kontrol, belum daftar MJKN</div>
                </div>
                <div class="summary-card card-mjkn">
                    <div class="s-label">📱 MJKN (Non-Kontrol)</div>
                    <div class="s-value"><?php echo number_format($countMjknSaja, 0, ',', '.'); ?></div>
                    <div class="s-desc">Daftar MJKN rujukan baru / non-kontrol</div>
                </div>
            </div>

            <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="font-weight: bold; color: #495057;">
                    📊 Menampilkan: <span style="color: #007bff;"><?php echo number_format($totalDisplayed, 0, ',', '.'); ?></span> data pasien
                </div>
                <button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>
            </div>

            <!-- Bulk Action Bar -->
            <div id="bulkActionBar" class="bulk-action-bar" style="display: none;">
                <div class="bulk-info">
                    <span class="bulk-badge" id="selectedCountBadge">0 Pasien Terpilih</span>
                    <span class="bulk-hint">Pilih template pesan untuk dikirim berurutan:</span>
                </div>
                <div class="bulk-buttons">
                    <button type="button" class="btn-wa-bulk" onclick="startBulkQueue('pengingat')">
                        🔔 Kirim Pengingat Hadir (<span class="selected-num">0</span>)
                    </button>
                    <button type="button" class="btn-wa-bulk-warning" onclick="startBulkQueue('berhalangan')">
                        ⚠️ Kirim Dokter Berhalangan (<span class="selected-num">0</span>)
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="clearAllSelections()">
                        ✕ Batal Pilih
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <tr>
                        <th style="width: 38px; text-align: center;">
                            <input type="checkbox" id="checkAll" onchange="toggleSelectAll(this)" title="Pilih Semua Pasien" style="width: 17px; height: 17px; cursor: pointer;">
                        </th>
                        <th>No</th>
                        <th>STATUS SUMBER</th>
                        <th>TANGGAL</th>
                        <th>NO SURAT KONTROL</th>
                        <th>NO BOOKING MJKN</th>
                        <th>NO RAWAT</th>
                        <th>NO RM</th>
                        <th>NAMA PASIEN</th>
                        <th>NO KARTU</th>
                        <th>NO HP</th>
                        <th>POLIKLINIK</th>
                        <th>DOKTER BPJS</th>
                        <th>NO ANTREAN</th>
                        <th>Aksi WhatsApp</th>
                    </tr>
<?php
    $no = 1;
    foreach ($combinedRows as $row) {
        // Format nomor antrean
        $no_antrean = '-';
        if (!empty($row['angkaantrean'])) {
            $no_antrean = ltrim($row['angkaantrean'], '0');
            if ($no_antrean === '') $no_antrean = '0';
            if (!empty($row['nomorantrean'])) {
                $no_antrean .= ' (' . $row['nomorantrean'] . ')';
            }
        } elseif (!empty($row['nomorantrean'])) {
            $no_antrean = $row['nomorantrean'];
        } elseif (!empty($row['no_reg'])) {
            $no_antrean = ltrim($row['no_reg'], '0');
            if ($no_antrean === '') $no_antrean = '0';
        }

        // Nomor antrean bersih untuk pesan WA
        $antrean_wa = '-';
        if (!empty($row['angkaantrean'])) {
            $antrean_wa = ltrim($row['angkaantrean'], '0');
            if ($antrean_wa === '') $antrean_wa = '0';
        } elseif (!empty($row['nomorantrean'])) {
            $antrean_wa = $row['nomorantrean'];
        } elseif (!empty($row['no_reg'])) {
            $antrean_wa = ltrim($row['no_reg'], '0');
            if ($antrean_wa === '') $antrean_wa = '0';
        }

        // Format estimasi jam
        $estimasi_jam = '08.00 - 12.00 WIB';
        if (!empty($row['estimasidilayani']) && is_numeric($row['estimasidilayani']) && $row['estimasidilayani'] > 0) {
            $ts = (int)($row['estimasidilayani'] / 1000);
            $h = (int)date('H', $ts);
            $estimasi_jam = sprintf("%02d.00 - %02d.00 WIB", $h, $h + 1);
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
        }

        $badgeClass = 'source-mjkn';
        if ($row['kategori'] === 'irisan') {
            $badgeClass = 'source-irisan';
        } elseif ($row['kategori'] === 'kontrol_saja') {
            $badgeClass = 'source-kontrol';
        }

        $pasien_js  = htmlspecialchars(addslashes($row['nm_pasien']), ENT_QUOTES, 'UTF-8');
        $poli_js    = htmlspecialchars(addslashes($row['nm_poli_display']), ENT_QUOTES, 'UTF-8');
        $dokter_js  = htmlspecialchars(addslashes($row['nm_dokter_bpjs']), ENT_QUOTES, 'UTF-8');
        $nohp_js    = htmlspecialchars(addslashes($row['nohp']), ENT_QUOTES, 'UTF-8');
        $antrean_js = htmlspecialchars(addslashes($antrean_wa), ENT_QUOTES, 'UTF-8');
        $jam_js     = htmlspecialchars(addslashes($estimasi_jam), ENT_QUOTES, 'UTF-8');
        $is_valid_hp = !empty($row['nohp']) && preg_match('/[1-9]/', $row['nohp']);
?>
                    <tr id="row-<?php echo $no; ?>">
                        <td style="text-align: center;">
                            <input type="checkbox"
                                   class="patient-checkbox"
                                   data-index="<?php echo $no; ?>"
                                   data-nohp="<?php echo $nohp_js; ?>"
                                   data-pasien="<?php echo $pasien_js; ?>"
                                   data-tgl="<?php echo htmlspecialchars($row['tanggal']); ?>"
                                   data-poli="<?php echo $poli_js; ?>"
                                   data-dokter="<?php echo $dokter_js; ?>"
                                   data-antrean="<?php echo $antrean_js; ?>"
                                   data-jam="<?php echo $jam_js; ?>"
                                   onchange="updateSelectedCount()"
                                   <?php echo !$is_valid_hp ? "disabled title='Nomor HP tidak valid'" : ""; ?>
                                   style="width: 17px; height: 17px; cursor: pointer;">
                        </td>
                        <td><?php echo $no; ?></td>
                        <td><span class="source-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['kategori_label']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                        <td><?php echo htmlspecialchars($row['no_surat']); ?></td>
                        <td><?php echo htmlspecialchars($row['nobooking']); ?></td>
                        <td><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                        <td><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['nm_pasien']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['nomorkartu']); ?></td>
                        <td><?php echo htmlspecialchars($row['nohp'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['nm_poli_display']); ?></td>
                        <td><?php echo htmlspecialchars($row['nm_dokter_bpjs']); ?></td>
                        <td><?php echo htmlspecialchars($no_antrean); ?></td>
                        <td>
                            <div class="wa-btn-group">
                                <button class="whatsapp-btn"
                                        onclick="openWhatsApp('<?php echo $nohp_js; ?>', '<?php echo $pasien_js; ?>', '<?php echo htmlspecialchars($row['tanggal']); ?>', '<?php echo $poli_js; ?>', '<?php echo $dokter_js; ?>', '<?php echo $antrean_js; ?>', '<?php echo $jam_js; ?>', <?php echo $no; ?>)"
                                        <?php echo !$is_valid_hp ? "disabled title='Nomor HP tidak tersedia atau tidak valid'" : "title='Kirim Pesan Pengingat Kehadiran'"; ?>>
                                    🔔 Ingatkan Hadir
                                </button>
                                <button class="whatsapp-btn whatsapp-btn-warning"
                                        onclick="openWhatsAppDokterBerhalangan('<?php echo $nohp_js; ?>', '<?php echo $pasien_js; ?>', '<?php echo htmlspecialchars($row['tanggal']); ?>', '<?php echo $poli_js; ?>', '<?php echo $dokter_js; ?>', <?php echo $no; ?>)"
                                        <?php echo !$is_valid_hp ? "disabled title='Nomor HP tidak tersedia atau tidak valid'" : "title='Kirim Info Dokter Berhalangan Hadir'"; ?>>
                                    ⚠️ Dokter Berhalangan
                                </button>
                                <span id="status-badge-<?php echo $no; ?>" class="queue-status-badge" style="display:none;"></span>
                            </div>
                        </td>
                    </tr>
<?php
        $no++;
    }
?>
                </table>
            </div>

<?php
    if ($totalDisplayed === 0) {
        echo '<div class="no-data">📊 Tidak ada data referensi pada filter yang dipilih</div>';
    }
    mysqli_close($koneksi);
}
?>
        </div>
    </div>

    <!-- Modal Queue Runner WhatsApp -->
    <div id="waQueueModal" class="queue-modal" style="display: none;">
        <div class="queue-modal-overlay" onclick="closeQueueModalConfirm()"></div>
        <div class="queue-modal-content">
            <div class="queue-modal-header">
                <div class="queue-modal-title">
                    <span id="queueModalIcon" style="font-size: 1.4rem;">📢</span>
                    <h3 id="queueModalTitle">Antrean Pengiriman WhatsApp</h3>
                </div>
                <button type="button" class="queue-close-btn" onclick="closeQueueModalConfirm()" title="Tutup Antrean">&times;</button>
            </div>

            <div class="queue-modal-body">
                <div class="queue-progress-section">
                    <div class="queue-progress-label">
                        <span>Progress Pengiriman:</span>
                        <strong id="queueProgressText">0 dari 0 (0%)</strong>
                    </div>
                    <div class="queue-progress-bar-bg">
                        <div id="queueProgressBar" class="queue-progress-bar-fill" style="width: 0%;"></div>
                    </div>
                </div>

                <div id="queueActiveCard" class="queue-patient-card">
                    <div class="queue-patient-header">
                        <span class="queue-badge-urutan" id="queueUrutanBadge">Pasien #1</span>
                        <h4 id="queuePatientName">Nama Pasien</h4>
                    </div>
                    <div class="queue-patient-details">
                        <div><strong>📱 No. HP:</strong> <span id="queuePatientHp">-</span></div>
                        <div><strong>🏥 Poliklinik:</strong> <span id="queuePatientPoli">-</span></div>
                        <div><strong>👨‍⚕️ Dokter:</strong> <span id="queuePatientDokter">-</span></div>
                        <div><strong>📅 Jadwal:</strong> <span id="queuePatientTgl">-</span></div>
                    </div>

                    <div class="queue-msg-preview-wrap">
                        <label>Pratinjau Pesan yang Dibuka:</label>
                        <pre id="queueMsgPreview" class="queue-msg-preview"></pre>
                    </div>
                </div>

                <div id="queueCompletedView" class="queue-completed-view" style="display: none;">
                    <div class="completed-icon">🎉</div>
                    <h3>Semua Antrean Selesai Diproses!</h3>
                    <p id="queueCompletedSummary">Sebanyak X pasien telah dibuka di WhatsApp Web.</p>
                    <button type="button" class="btn btn-primary" onclick="closeQueueModalConfirm()">Tutup Jendela</button>
                </div>

                <div class="queue-controls-box" id="queueControlsBox">
                    <div class="queue-timer-toggle">
                        <label>
                            <input type="checkbox" id="queueAutoTimerCheck" onchange="toggleAutoTimer()">
                            <span>Otomatis buka pasien berikutnya setelah jeda</span>
                        </label>
                        <div class="timer-delay-wrap">
                            <select id="queueDelaySec" onchange="changeDelaySec()">
                                <option value="4">4 detik</option>
                                <option value="5" selected>5 detik</option>
                                <option value="7">7 detik</option>
                                <option value="10">10 detik</option>
                            </select>
                            <span id="queueCountdownBadge" class="countdown-badge" style="display: none;">⏱ 5s</span>
                        </div>
                    </div>

                    <div class="queue-action-buttons">
                        <button type="button" class="btn-wa-next" id="btnQueueNext" onclick="processQueueCurrentAndAdvance()">
                            ▶ Buka WhatsApp Pasien Ini
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="btnQueueSkip" onclick="skipQueueCurrent()">
                            ⏭ Lewati
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="closeQueueModalConfirm()">
                            ⏹ Tutup
                        </button>
                    </div>
                </div>

                <div class="queue-tip">
                    💡 <strong>Tips Efisien:</strong> WhatsApp dibuka pada <em>satu tab browser yang sama</em> secara bergantian. Setelah chat WhatsApp terbuka, Anda cukup menekan tombol <strong>Enter/Kirim</strong> di tab WhatsApp tersebut, lalu kembali ke sini untuk melanjutkan (atau aktifkan checklist jeda otomatis di atas).
                </div>
            </div>
        </div>
    </div>
</body>
</html>

