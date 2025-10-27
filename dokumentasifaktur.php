<?php
include 'koneksi.php';

$no_faktur = isset($_GET['no_faktur']) ? $_GET['no_faktur'] : '';
$pesan = '';
$foto_paths = array_fill(0, 10, '');

// Ambil parameter filter dari GET untuk tombol kembali
$filter_params = '';
$filter_keys = ['tgl_pesan_awal','tgl_pesan_akhir','tgl_faktur_awal','tgl_faktur_akhir','nama_suplier'];
foreach ($filter_keys as $key) {
    if (isset($_GET[$key])) {
        $filter_params .= '&' . $key . '=' . urlencode($_GET[$key]);
    }
}

// Ambil data dokumentasi jika sudah ada
$q = mysqli_query($koneksi, "SELECT * FROM pemesanan_dokumentasi WHERE no_faktur='$no_faktur' LIMIT 1");
if ($row = mysqli_fetch_assoc($q)) {
    for ($i = 0; $i < 10; $i++) {
        $foto_paths[$i] = $row['foto' . ($i + 1)];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $no_faktur = $_POST['no_faktur'];
    $upload_dir = 'uploads/faktur/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Cek sudah ada berapa foto
    $q = mysqli_query($koneksi, "SELECT * FROM pemesanan_dokumentasi WHERE no_faktur='$no_faktur' LIMIT 1");
    if ($row = mysqli_fetch_assoc($q)) {
        for ($i = 0; $i < 10; $i++) {
            $foto_paths[$i] = $row['foto' . ($i + 1)];
        }
    }

    // Cari slot kosong
    $slot = -1;
    for ($i=0; $i<10; $i++) {
        if (empty($foto_paths[$i])) {
            $slot = $i;
            break;
        }
    }

    if ($slot === -1) {
        $pesan = "Sudah ada 10 foto untuk faktur ini!";
    } else {
        $safe_no_faktur = preg_replace('/[^A-Za-z0-9]/', '', $no_faktur);
        $file_name = $safe_no_faktur . '_' . ($slot+1) . '.jpg';
        $target_file = $upload_dir . $file_name;
        $tmp_file = $_FILES['foto']['tmp_name'];
        $max_size = 200 * 1024; // 200 KB

        // Kompresi dan konversi ke JPG
        $image_info = getimagesize($tmp_file);
        if ($image_info) {
            $src_img = false;
            switch ($image_info['mime']) {
                case 'image/jpeg':
                    $src_img = imagecreatefromjpeg($tmp_file);
                    break;
                case 'image/png':
                    $src_img = imagecreatefrompng($tmp_file);
                    break;
                case 'image/gif':
                    $src_img = imagecreatefromgif($tmp_file);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $src_img = imagecreatefromwebp($tmp_file);
                    }
                    break;
            }
            if ($src_img) {
                // Resize jika lebar > 1280px (opsional)
                $width = imagesx($src_img);
                $height = imagesy($src_img);
                $max_width = 1280;
                if ($width > $max_width) {
                    $new_width = $max_width;
                    $new_height = intval($height * $new_width / $width);
                    $dst_img = imagecreatetruecolor($new_width, $new_height);
                    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                } else {
                    $dst_img = $src_img;
                }

                // Kompresi dan simpan (loop kualitas sampai <200KB)
                $quality = 80;
                do {
                    ob_start();
                    imagejpeg($dst_img, null, $quality);
                    $img_data = ob_get_clean();
                    $img_size = strlen($img_data);
                    $quality -= 5;
                } while ($img_size > $max_size && $quality > 30);

                // Simpan file hasil kompresi
                file_put_contents($target_file, $img_data);

                // Hapus resource
                if (isset($src_img) && $src_img !== $dst_img) imagedestroy($src_img);
                if (isset($dst_img)) imagedestroy($dst_img);

                // Simpan nama file ke database
                $field = 'foto'.($slot+1);
                if ($q && mysqli_num_rows($q) > 0) {
                    mysqli_query($koneksi, "UPDATE pemesanan_dokumentasi SET $field='$file_name' WHERE no_faktur='$no_faktur'");
                } else {
                    // Buat array untuk INSERT dengan semua field foto
                    $foto_fields = array();
                    $foto_values = array();
                    for ($i = 0; $i < 10; $i++) {
                        $foto_fields[] = 'foto' . ($i + 1);
                        $foto_values[] = ($i == $slot) ? "'$file_name'" : "''";
                    }
                    $fields_str = implode(', ', $foto_fields);
                    $values_str = implode(', ', $foto_values);
                    mysqli_query($koneksi, "INSERT INTO pemesanan_dokumentasi (no_faktur, $fields_str) VALUES ('$no_faktur', $values_str)");
                }
                $pesan = "Foto berhasil diupload dan dikompresi!";
                $foto_paths[$slot] = $file_name;
            } else {
                $pesan = "Format gambar tidak didukung!";
            }
        } else {
            $pesan = "File bukan gambar!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Foto Faktur</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: #007bff;
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 1.5em;
            font-weight: bold;
        }
        .form-upload {
            padding: 30px;
        }
        .faktur-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        .faktur-info label {
            font-size: 16px;
            color: #333;
            margin: 0;
        }
        .faktur-info strong {
            color: #007bff;
            font-size: 18px;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #333;
            font-size: 15px;
        }
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #007bff;
            border-radius: 8px;
            background: #f8f9fa;
            margin-bottom: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        input[type="file"]:hover,
        input[type="file"]:focus {
            background: #e3f2fd;
            border-color: #0056b3;
            outline: none;
        }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .pesan {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
        }
        .pesan.success {
            background: #d1edff;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .pesan.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .foto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            max-height: 600px;
            overflow-y: auto;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        .foto-preview {
            text-align: center;
            padding: 12px;
            background: white;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .foto-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border-color: #007bff;
        }
        .foto-preview img {
            width: 100%;
            max-height: 120px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .foto-preview img:hover {
            transform: scale(1.05);
        }
        .foto-preview .foto-label {
            font-size: 14px;
            font-weight: bold;
            color: #666;
            margin-bottom: 10px;
        }
        .back-button {
            margin-top: 25px;
            text-align: center;
        }
        .back-button a {
            display: inline-block;
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .back-button a:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }
        .max-photos {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ffeaa7;
        }
        
        /* Progress bar untuk foto */
        .foto-progress {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .foto-progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        .foto-progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        .foto-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #666;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .header {
                padding: 20px 15px;
            }
            .header h2 {
                font-size: 1.3em;
            }
            .form-upload {
                padding: 20px 15px;
            }
            .foto-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 12px;
                max-height: 400px;
            }
            .foto-preview {
                padding: 10px;
            }
            .foto-preview img {
                max-height: 100px;
            }
            button {
                padding: 12px;
                font-size: 15px;
            }
            .back-button a {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
        
        /* Extra small screens */
        @media (max-width: 480px) {
            .header {
                padding: 15px 10px;
            }
            .header h2 {
                font-size: 1.2em;
            }
            .form-upload {
                padding: 15px 10px;
            }
            .faktur-info {
                padding: 12px;
            }
            input[type="file"] {
                padding: 10px;
                font-size: 13px;
            }
            button {
                padding: 10px;
                font-size: 14px;
                /* Better touch target */
                min-height: 44px;
            }
            .back-button a {
                /* Better touch target */
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            button:hover {
                transform: none;
            }
            .back-button a:hover {
                transform: none;
            }
            /* Larger touch targets */
            button, .back-button a {
                min-height: 48px;
                padding: 15px 20px;
            }
        }
        
        /* Modal untuk view foto penuh */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
        }
        .modal-content img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-close:hover {
            color: #bbb;
        }
        .modal-caption {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            background: rgba(0,0,0,0.7);
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📸 Upload Foto Faktur</h2>
        </div>
        
        <div class="form-upload">
            <div class="faktur-info">
                <label><strong>No Faktur:</strong> <?php echo htmlspecialchars($no_faktur); ?></label>
            </div>
            
            <?php if ($pesan): ?>
                <div class="pesan <?php echo strpos($pesan, 'berhasil') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>
            
            <!-- Progress Bar -->
            <?php
            $foto_tersedia = 0;
            for ($i=0; $i<10; $i++) if (!empty($foto_paths[$i])) $foto_tersedia++;
            $progress_percent = ($foto_tersedia / 10) * 100;
            ?>
            
            <div class="foto-progress">
                <div class="foto-stats">
                    <span><strong>📊 Progress Upload:</strong></span>
                    <span><strong><?php echo $foto_tersedia; ?>/10 Foto</strong></span>
                </div>
                <div class="foto-progress-bar">
                    <div class="foto-progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                </div>
                <div style="text-align: center; font-size: 12px; color: #666; margin-top: 5px;">
                    <?php echo number_format($progress_percent, 1); ?>% Complete
                </div>
            </div>
            
            <!-- Tampilkan foto yang sudah ada -->
            
            <?php if ($foto_tersedia > 0): ?>
                <div class="foto-grid">
                    <?php for ($i=0; $i<10; $i++): ?>
                        <?php if (!empty($foto_paths[$i])): ?>
                            <div class="foto-preview">
                                <div class="foto-label">📷 Foto <?php echo $i+1; ?></div>
                                <img src="uploads/faktur/<?php echo htmlspecialchars($foto_paths[$i]); ?>" 
                                     alt="Foto <?php echo $i+1; ?>" 
                                     onclick="openModal('uploads/faktur/<?php echo htmlspecialchars($foto_paths[$i]); ?>', 'Foto <?php echo $i+1; ?> - <?php echo htmlspecialchars($no_faktur); ?>')"
                                     title="Klik untuk memperbesar">
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            
            <!-- Form upload -->
            <?php if ($foto_tersedia < 10): ?>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="no_faktur" value="<?php echo htmlspecialchars($no_faktur); ?>">
                    
                    <label>📤 Upload Foto Faktur ke-<?php echo $foto_tersedia+1; ?>:</label>
                    <input type="file" name="foto" accept="image/*" required>
                    <button type="submit">
                        ⬆️ Upload Foto (<?php echo $foto_tersedia; ?>/10)
                    </button>
                </form>
            <?php else: ?>
                <div class="max-photos">
                    ✅ Maksimal 10 foto telah tercapai
                </div>
            <?php endif; ?>
            
            <div class="back-button">
                <a href="pemesanandokumentasi.php?<?php echo ltrim($filter_params, '&'); ?>">
                    ← Kembali ke Daftar Faktur
                </a>
            </div>
        </div>
    </div>

    <!-- Modal untuk melihat foto penuh -->
    <div id="fotoModal" class="modal">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <img id="modalImg" src="" alt="">
            <div id="modalCaption" class="modal-caption"></div>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka modal foto
        function openModal(imgSrc, caption) {
            const modal = document.getElementById('fotoModal');
            const modalImg = document.getElementById('modalImg');
            const modalCaption = document.getElementById('modalCaption');
            
            modal.style.display = 'block';
            modalImg.src = imgSrc;
            modalCaption.textContent = caption;
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            const modal = document.getElementById('fotoModal');
            modal.style.display = 'none';
        }

        // Tutup modal ketika diklik di luar gambar
        window.onclick = function(event) {
            const modal = document.getElementById('fotoModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Tutup modal dengan tombol ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Auto refresh progress setelah upload
        <?php if (strpos($pesan, 'berhasil') !== false): ?>
        setTimeout(function() {
            const progressBar = document.querySelector('.foto-progress-fill');
            const newWidth = '<?php echo $progress_percent; ?>%';
            progressBar.style.width = newWidth;
        }, 100);
        <?php endif; ?>
    </script>
</body>
</html>