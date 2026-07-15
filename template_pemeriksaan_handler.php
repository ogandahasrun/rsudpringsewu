<?php
header('Content-Type: application/json');
include 'koneksi.php';

// Disable error reporting to output, but log them, to keep JSON response clean
ini_set('display_errors', 0);
error_reporting(E_ALL);

function respondError($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function respondSuccess($data = []) {
    echo json_encode(array_merge(['status' => 'success'], $data));
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id_template = isset($_REQUEST['id_template']) ? intval($_REQUEST['id_template']) : 0;

if ($id_template <= 0) {
    respondError('ID Template tidak valid.');
}

if ($action === 'get') {
    $id_template_esc = mysqli_real_escape_string($koneksi, $id_template);
    $query = "SELECT nilai FROM template_pemeriksaan_laboratorium WHERE id_template = '$id_template_esc' ORDER BY nilai ASC";
    $result = mysqli_query($koneksi, $query);
    
    if (!$result) {
        respondError('Gagal mengambil data template: ' . mysqli_error($koneksi));
    }
    
    $templates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $templates[] = $row['nilai'];
    }
    
    respondSuccess(['templates' => $templates]);

} elseif ($action === 'add') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondError('Request method harus POST.');
    }
    
    $nilai = isset($_POST['nilai']) ? trim($_POST['nilai']) : '';
    if (empty($nilai)) {
        respondError('Nilai template tidak boleh kosong.');
    }
    
    if (strlen($nilai) > 200) {
        respondError('Panjang template maksimal 200 karakter.');
    }
    
    $id_template_esc = mysqli_real_escape_string($koneksi, $id_template);
    $nilai_esc = mysqli_real_escape_string($koneksi, $nilai);
    
    // Check if duplicate exists
    $check_query = "SELECT COUNT(*) FROM template_pemeriksaan_laboratorium WHERE id_template = '$id_template_esc' AND nilai = '$nilai_esc'";
    $check_res = mysqli_query($koneksi, $check_query);
    $check_row = mysqli_fetch_row($check_res);
    
    if ($check_row[0] > 0) {
        respondError('Template sudah ada.');
    }
    
    // Insert new template
    $insert_query = "INSERT INTO template_pemeriksaan_laboratorium (id_template, nilai) VALUES ('$id_template_esc', '$nilai_esc')";
    if (mysqli_query($koneksi, $insert_query)) {
        respondSuccess();
    } else {
        respondError('Gagal menyimpan template: ' . mysqli_error($koneksi));
    }

} elseif ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondError('Request method harus POST.');
    }
    
    $nilai = isset($_POST['nilai']) ? trim($_POST['nilai']) : '';
    if (empty($nilai)) {
        respondError('Nilai template tidak boleh kosong.');
    }
    
    $id_template_esc = mysqli_real_escape_string($koneksi, $id_template);
    $nilai_esc = mysqli_real_escape_string($koneksi, $nilai);
    
    $delete_query = "DELETE FROM template_pemeriksaan_laboratorium WHERE id_template = '$id_template_esc' AND nilai = '$nilai_esc'";
    if (mysqli_query($koneksi, $delete_query)) {
        if (mysqli_affected_rows($koneksi) > 0) {
            respondSuccess();
        } else {
            respondError('Template tidak ditemukan.');
        }
    } else {
        respondError('Gagal menghapus template: ' . mysqli_error($koneksi));
    }

} else {
    respondError('Aksi tidak dikenal.');
}
?>
