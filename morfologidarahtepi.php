
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Morfologi Darah Tepi</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; letter-spacing: 1px; }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .filter-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .filter-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .filter-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus { outline: none; border-color: #28a745; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; cursor: pointer; user-select: none; position: relative; transition: background 0.3s ease; }
        th:hover { background: linear-gradient(45deg, #495057, #5a6268); }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .patient-info { margin: 20px 0; padding: 15px; background-color: #e9f7ef; border-radius: 8px; border-left: 5px solid #28a745; font-family: Tahoma, Geneva, Verdana, sans-serif; }
        .patient-info p { margin: 5px 0; font-size: 16px; }
        .col-uraian { width: 30%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .col-hasil { width: 70%; }
        .input-hasil { width: 100%; padding: 10px; font-size: 15px; border-radius: 7px; border: 1.5px solid #e9ecef; transition: border 0.3s; }
        .input-hasil:focus { border: 1.5px solid #28a745; outline: none; }
        .save-btn { margin-top: 18px; }
        @media (max-width: 900px) { .container { max-width: 100%; } .content { padding: 10px; } .header { padding: 18px 8px; } }
        @media (max-width: 600px) { th, td { padding: 8px 4px; font-size: 12px; } .header h1 { font-size: 1.2em; } }
        
        /* CSS Tambahan untuk Fitur Template Popup */
        .input-hasil-wrapper {
            display: flex;
            gap: 8px;
            align-items: center;
            width: 100%;
        }
        .input-hasil-wrapper .input-hasil {
            flex: 1;
            margin: 0;
        }
        .btn-template {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border: none;
            border-radius: 7px;
            padding: 10px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-template:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(23, 162, 184, 0.3);
            background: linear-gradient(135deg, #138496, #117a8b);
        }
        .btn-template:active {
            transform: translateY(0);
        }
        .btn-template svg {
            stroke: #fff;
            transition: transform 0.3s ease;
        }
        .btn-template:hover svg {
            transform: scale(1.1);
        }
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-backdrop.show {
            opacity: 1;
        }
        .modal-content {
            background: #ffffff;
            width: 90%;
            max-width: 550px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            transform: scale(0.9) translateY(-10px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            flex-direction: column;
        }
        .modal-backdrop.show .modal-content {
            transform: scale(1) translateY(0);
        }
        .modal-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .modal-close {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            font-size: 18px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        .modal-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .search-box-wrapper {
            position: relative;
        }
        .search-box-wrapper input {
            width: 100%;
            padding: 12px 14px;
            font-size: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }
        .search-box-wrapper input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
        }
        .template-list-container {
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }
        .template-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .template-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
        }
        .template-item:last-child {
            border-bottom: none;
        }
        .template-item:hover {
            background: #f0fdf4;
        }
        .template-text {
            flex: 1;
            cursor: pointer;
            font-size: 13.5px;
            color: #334155;
            font-weight: 500;
            line-height: 1.4;
            padding-right: 10px;
        }
        .template-text:hover {
            color: #15803d;
        }
        .btn-delete-template {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            opacity: 0.4;
            padding: 6px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-delete-template:hover {
            opacity: 1;
            background: #fee2e2;
        }
        .add-template-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
        }
        .add-template-form label {
            font-weight: 700;
            font-size: 13px;
            color: #475569;
        }
        .add-template-input-wrapper {
            display: flex;
            gap: 10px;
        }
        .add-template-input-wrapper textarea {
            flex: 1;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            resize: none;
            min-height: 50px;
            font-family: inherit;
            font-size: 13.5px;
            transition: all 0.3s ease;
        }
        .add-template-input-wrapper textarea:focus {
            border-color: #28a745;
            outline: none;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
        }
        .btn-add-template {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
            white-space: nowrap;
            align-self: flex-start;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-add-template:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        .btn-add-template:active {
            transform: translateY(0);
        }
        
        /* CSS Tambahan untuk Fitur Pemilihan Pasien Periode */
        .btn-pilih {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 6px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }
        .btn-pilih:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            background: linear-gradient(135deg, #218838, #1e7e34);
        }
        .active-row td {
            background-color: #e8f5e8 !important;
            border-left: 4px solid #28a745;
        }
        .active-row td strong {
            color: #15803d;
        }
        .badge-active {
            background: linear-gradient(135deg, #15803d, #166534);
            color: white;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(22, 101, 52, 0.2);
        }
    </style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.input-hasil').forEach(input => {
        // Format nilai yang sudah ada saat halaman dimuat
        if (input.value) {
            input.value = formatText(input.value);
        }

        input.addEventListener('input', function (e) {
            // Simpan posisi kursor dan nilai sebelum perubahan
            const cursorPos = this.selectionStart;
            const originalValue = this.value;
            
            // Format teks
            this.value = formatText(originalValue);
            
            // Kembalikan posisi kursor
            if (cursorPos === 1 && originalValue.length === 0) {
                this.setSelectionRange(2, 2); // Penanganan khusus untuk karakter pertama
            } else {
                this.setSelectionRange(cursorPos, cursorPos);
            }
        });

        // Fungsi untuk memformat teks
        function formatText(text) {
            if (text.length === 0) return text;
            
            // Ambil karakter pertama (kapital) dan sisanya (biarkan asli)
            return text.charAt(0).toUpperCase() + text.slice(1);
        }
    });

    // Modal Template Logic
    const modal = document.getElementById('templateModal');
    const modalTitle = document.getElementById('modalTitle');
    const templateSearchInput = document.getElementById('templateSearchInput');
    const templateList = document.getElementById('templateList');
    const noTemplatesMsg = document.getElementById('noTemplatesMsg');
    const newTemplateText = document.getElementById('newTemplateText');
    const addTemplateBtn = document.getElementById('addTemplateBtn');
    const addErrorMsg = document.getElementById('addErrorMsg');
    const closeModalBtn = document.getElementById('closeModalBtn');
    
    let activeTargetInput = null;
    let activeIdTemplate = null;
    let activePemeriksaan = '';

    // Handle template button click
    document.querySelectorAll('.btn-template').forEach(button => {
        button.addEventListener('click', function () {
            const idTemplate = this.getAttribute('data-id-template');
            const pemeriksaan = this.getAttribute('data-pemeriksaan');
            const targetId = this.getAttribute('data-target-id');
            
            activeTargetInput = document.getElementById(targetId);
            activeIdTemplate = idTemplate;
            activePemeriksaan = pemeriksaan;
            
            modalTitle.innerText = `Cari Template: ${pemeriksaan}`;
            templateSearchInput.value = '';
            newTemplateText.value = '';
            addErrorMsg.style.display = 'none';
            
            openModal();
            loadTemplates(idTemplate);
        });
    });

    function openModal() {
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
            templateSearchInput.focus();
        }, 10);
    }

    function closeModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    closeModalBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Load templates via AJAX
    function loadTemplates(idTemplate) {
        templateList.innerHTML = '<li style="text-align:center; padding:15px; color:#666; font-size: 13px; font-style: italic;">Memuat data template...</li>';
        noTemplatesMsg.style.display = 'none';
        
        fetch(`template_pemeriksaan_handler.php?action=get&id_template=${idTemplate}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    renderTemplates(data.templates);
                } else {
                    templateList.innerHTML = `<li style="color:#ef4444; text-align:center; padding:15px; font-size: 13px; font-weight:bold;">Error: ${data.message}</li>`;
                }
            })
            .catch(err => {
                templateList.innerHTML = '<li style="color:#ef4444; text-align:center; padding:15px; font-size: 13px; font-weight:bold;">Gagal menghubungi server.</li>';
                console.error(err);
            });
    }

    // Render templates into list
    function renderTemplates(templates) {
        templateList.innerHTML = '';
        if (templates.length === 0) {
            noTemplatesMsg.style.display = 'block';
            return;
        }
        
        noTemplatesMsg.style.display = 'none';
        templates.forEach(nilai => {
            const li = document.createElement('li');
            li.className = 'template-item';
            
            const span = document.createElement('span');
            span.className = 'template-text';
            span.innerText = nilai;
            span.addEventListener('click', function () {
                if (activeTargetInput) {
                    activeTargetInput.value = nilai;
                    // Trigger input event to let the capitalize format logic execute
                    activeTargetInput.dispatchEvent(new Event('input'));
                }
                closeModal();
            });
            
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn-delete-template';
            deleteBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';
            deleteBtn.title = 'Hapus Template';
            deleteBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (confirm(`Apakah Anda yakin ingin menghapus template ini?\n"${nilai}"`)) {
                    deleteTemplate(activeIdTemplate, nilai);
                }
            });
            
            li.appendChild(span);
            li.appendChild(deleteBtn);
            templateList.appendChild(li);
        });
    }

    // Filter templates in real-time
    templateSearchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const items = templateList.querySelectorAll('.template-item');
        let visibleCount = 0;
        
        items.forEach(item => {
            const text = item.querySelector('.template-text').innerText.toLowerCase();
            if (text.includes(query)) {
                item.style.setProperty('display', 'flex', 'important');
                visibleCount++;
            } else {
                item.style.setProperty('display', 'none', 'important');
            }
        });
        
        if (visibleCount === 0 && items.length > 0) {
            noTemplatesMsg.innerText = 'Template tidak ditemukan untuk pencarian tersebut.';
            noTemplatesMsg.style.display = 'block';
        } else if (items.length > 0) {
            noTemplatesMsg.style.display = 'none';
        } else {
            noTemplatesMsg.innerText = 'Belum ada template. Silakan tambah template baru di bawah.';
            noTemplatesMsg.style.display = 'block';
        }
    });

    // Add new template via AJAX
    addTemplateBtn.addEventListener('click', function () {
        const value = newTemplateText.value.trim();
        if (!value) {
            showAddError('Teks template tidak boleh kosong.');
            return;
        }
        
        addTemplateBtn.disabled = true;
        addTemplateBtn.innerText = '...';
        addErrorMsg.style.display = 'none';
        
        const formData = new FormData();
        formData.append('id_template', activeIdTemplate);
        formData.append('nilai', value);
        
        fetch('template_pemeriksaan_handler.php?action=add', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            addTemplateBtn.disabled = false;
            addTemplateBtn.innerText = 'Tambah';
            if (data.status === 'success') {
                newTemplateText.value = '';
                loadTemplates(activeIdTemplate);
            } else {
                showAddError(data.message);
            }
        })
        .catch(err => {
            addTemplateBtn.disabled = false;
            addTemplateBtn.innerText = 'Tambah';
            showAddError('Gagal menyimpan template.');
            console.error(err);
        });
    });

    function showAddError(msg) {
        addErrorMsg.innerText = msg;
        addErrorMsg.style.display = 'block';
    }

    // Delete template via AJAX
    function deleteTemplate(idTemplate, value) {
        const formData = new FormData();
        formData.append('id_template', idTemplate);
        formData.append('nilai', value);
        
        fetch('template_pemeriksaan_handler.php?action=delete', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                loadTemplates(idTemplate);
            } else {
                alert(`Gagal menghapus: ${data.message}`);
            }
        })
        .catch(err => {
            alert('Gagal menghubungi server.');
            console.error(err);
        });
    }
});
</script>

</head>
<body>
<div class="container">
    <div class="header">
        <h1>🧬 MORFOLOGI DARAH TEPI</h1>
    </div>
    <div class="content">
        <div class="back-button">
            <a href="laboratorium.php">← Kembali ke Menu Laboratorium</a>
        </div>

    <?php
    include 'koneksi.php';

    // Get filter dates (default: today)
    $tgl_awal = date('Y-m-d');
    $tgl_akhir = date('Y-m-d');
    if (isset($_REQUEST['tgl_awal']) && !empty($_REQUEST['tgl_awal'])) {
        $tgl_awal = $_REQUEST['tgl_awal'];
    }
    if (isset($_REQUEST['tgl_akhir']) && !empty($_REQUEST['tgl_akhir'])) {
        $tgl_akhir = $_REQUEST['tgl_akhir'];
    }

    $no_rawat = "";
    if (isset($_REQUEST['no_rawat'])) {
        $no_rawat = $_REQUEST['no_rawat'];
    }

    $no_rkm_medis = "";
    $nm_pasien = "";
    $data_lab = [];

    if (isset($_POST['simpan_semua'])) {
        $no_rawat = mysqli_real_escape_string($koneksi, $_POST['no_rawat']);
        $nilai_arr = $_POST['nilai'];
        $id_template_arr = $_POST['id_template'];

        for ($i = 0; $i < count($nilai_arr); $i++) {
            $id_template = mysqli_real_escape_string($koneksi, $id_template_arr[$i]);
            $nilai = mysqli_real_escape_string($koneksi, $nilai_arr[$i]);

            $update_query = "UPDATE detail_periksa_lab
                             SET nilai = '$nilai'
                             WHERE no_rawat = '$no_rawat'
                               AND id_template = '$id_template'
                               AND kd_jenis_prw = 'J000014'";

            mysqli_query($koneksi, $update_query);
        }

        echo "<script>alert('Semua perubahan berhasil disimpan!');</script>";
    }

    // Query patients list for the selected period
    $tgl_awal_esc = mysqli_real_escape_string($koneksi, $tgl_awal);
    $tgl_akhir_esc = mysqli_real_escape_string($koneksi, $tgl_akhir);
    
    $query_pasien = "SELECT DISTINCT
                        detail_periksa_lab.no_rawat,
                        pasien.no_rkm_medis,
                        pasien.nm_pasien
                     FROM
                        detail_periksa_lab
                        INNER JOIN reg_periksa ON detail_periksa_lab.no_rawat = reg_periksa.no_rawat
                        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                     WHERE
                        detail_periksa_lab.tgl_periksa BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc' AND
                        detail_periksa_lab.kd_jenis_prw = 'J000014'
                     ORDER BY
                        pasien.nm_pasien ASC";
    $result_pasien = mysqli_query($koneksi, $query_pasien);
    $list_pasien = [];
    if ($result_pasien) {
        while ($row = mysqli_fetch_assoc($result_pasien)) {
            $list_pasien[] = $row;
        }
    }

    // Query template data if a patient is selected
    if (!empty($no_rawat)) {
        $no_rawat_esc = mysqli_real_escape_string($koneksi, $no_rawat);
        $query = "SELECT
                    template_laboratorium.Pemeriksaan,
                    detail_periksa_lab.nilai,
                    template_laboratorium.id_template,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien
                  FROM
                    reg_periksa
                    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                    INNER JOIN detail_periksa_lab ON detail_periksa_lab.no_rawat = reg_periksa.no_rawat
                    INNER JOIN template_laboratorium ON detail_periksa_lab.id_template = template_laboratorium.id_template
                  WHERE
                    detail_periksa_lab.kd_jenis_prw = 'J000014' AND
                    reg_periksa.no_rawat = '$no_rawat_esc'
                  ORDER BY
                    template_laboratorium.urut";

        $result = mysqli_query($koneksi, $query);

        if (!$result) {
            die("Query error: " . mysqli_error($koneksi));
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $no_rkm_medis = $row['no_rkm_medis'];
            $nm_pasien = $row['nm_pasien'];
            $data_lab[] = $row;
        }
    }
    ?>

    <form method="GET" class="filter-form">
        <div class="filter-title">📅 Periode Pemeriksaan Morfologi Darah Tepi</div>
        <div class="filter-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="filter-group">
                <label for="tgl_awal">Tanggal Awal</label>
                <input type="date" id="tgl_awal" name="tgl_awal" required value="<?php echo htmlspecialchars($tgl_awal); ?>">
            </div>
            <div class="filter-group">
                <label for="tgl_akhir">Tanggal Akhir</label>
                <input type="date" id="tgl_akhir" name="tgl_akhir" required value="<?php echo htmlspecialchars($tgl_akhir); ?>">
            </div>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">🔍 Cari Pasien</button>
        </div>
    </form>

    <!-- Table Daftar Pasien -->
    <div class="table-container" style="margin-bottom: 30px;">
        <div style="padding: 18px; background: linear-gradient(45deg, #343a40, #495057); color: white; font-weight: bold; border-radius: 8px 8px 0 0; display: flex; align-items: center; gap: 8px; font-size: 14px;">
            📋 Daftar Pasien Periode: <?php echo htmlspecialchars(date('d-m-Y', strtotime($tgl_awal))); ?> s.d. <?php echo htmlspecialchars(date('d-m-Y', strtotime($tgl_akhir))); ?>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">No. Rawat</th>
                    <th style="width: 15%;">No. RM</th>
                    <th style="width: 35%;">Nama Pasien</th>
                    <th style="width: 20%; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($list_pasien)) { ?>
                    <?php foreach ($list_pasien as $idx => $p): ?>
                        <tr class="<?php echo ($no_rawat === $p['no_rawat']) ? 'active-row' : ''; ?>">
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo htmlspecialchars($p['no_rawat']); ?></td>
                            <td><?php echo htmlspecialchars($p['no_rkm_medis']); ?></td>
                            <td><strong><?php echo htmlspecialchars($p['nm_pasien']); ?></strong></td>
                            <td style="text-align: center;">
                                <?php if ($no_rawat === $p['no_rawat']) { ?>
                                    <span class="badge-active">Terpilih</span>
                                <?php } else { ?>
                                    <a href="?no_rawat=<?php echo urlencode($p['no_rawat']); ?>&tgl_awal=<?php echo urlencode($tgl_awal); ?>&tgl_akhir=<?php echo urlencode($tgl_akhir); ?>" class="btn-pilih">Pilih →</a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" class="no-data">Tidak ada pasien pemeriksaan Morfologi Darah Tepi pada periode ini.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php
    if (!empty($no_rkm_medis) && !empty($nm_pasien)) {
        echo '<div class="patient-info">';
        echo '<p><strong>Nomor Rekam Medis :</strong> ' . htmlspecialchars($no_rkm_medis) . '</p>';
        echo '<p><strong>Nama Pasien :</strong> ' . htmlspecialchars($nm_pasien) . '</p>';
        echo '</div>';
    }
    ?>

    <?php if (!empty($data_lab)) { ?>
        <form method="POST">
            <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($no_rawat) ?>">
            <input type="hidden" name="tgl_awal" value="<?= htmlspecialchars($tgl_awal) ?>">
            <input type="hidden" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th class="col-uraian">URAIAN</th>
                            <th class="col-hasil">HASIL</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($data_lab as $index => $row): ?>
                        <tr>
                            <td class="col-uraian">
                                <?= htmlspecialchars($row['Pemeriksaan']) ?>
                                <input type="hidden" name="id_template[]" value="<?= $row['id_template'] ?>">
                                <input type="hidden" name="pemeriksaan[]" value="<?= htmlspecialchars($row['Pemeriksaan']) ?>">
                            </td>
                            <td class="col-hasil">
                                <div class="input-hasil-wrapper">
                                    <input type="text" name="nilai[]" class="input-hasil" id="input-hasil-<?= $index ?>" value="<?= htmlspecialchars($row['nilai']) ?>">
                                    <button type="button" class="btn-template" data-id-template="<?= $row['id_template'] ?>" data-pemeriksaan="<?= htmlspecialchars($row['Pemeriksaan']) ?>" data-target-id="input-hasil-<?= $index ?>" title="Pilih Template">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="save-btn">
                <button type="submit" name="simpan_semua" class="btn btn-primary">💾 Simpan Semua Perubahan</button>
            </div>
        </form>
    <?php } elseif (!empty($no_rawat) && empty($data_lab)) { ?>
        <div class="no-data"><em>Template pemeriksaan laboratorium untuk nomor rawat ini tidak ditemukan.</em></div>
    <?php } ?>
    </div>
</div>

<!-- Modal Popup Template -->
<div id="templateModal" class="modal-backdrop">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Cari Template</h3>
            <button type="button" class="modal-close" id="closeModalBtn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="search-box-wrapper">
                <input type="text" id="templateSearchInput" placeholder="Cari template..." autocomplete="off">
            </div>
            
            <div class="template-list-container">
                <ul class="template-list" id="templateList">
                    <!-- Loaded dynamically via AJAX -->
                </ul>
                <div id="noTemplatesMsg" class="no-data" style="display: none; padding: 20px; font-size: 13px; text-align: center; color: #666; font-style: italic;">
                    Belum ada template. Silakan tambah template baru di bawah.
                </div>
            </div>
            
            <div class="add-template-form">
                <label for="newTemplateText">Tambah Template Baru</label>
                <div class="add-template-input-wrapper">
                    <textarea id="newTemplateText" placeholder="Ketik template baru di sini..." rows="2"></textarea>
                    <button type="button" class="btn-add-template" id="addTemplateBtn">Tambah</button>
                </div>
                <div id="addErrorMsg" style="color: #dc3545; font-size: 12px; display: none; margin-top: 4px; font-weight: bold;"></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
