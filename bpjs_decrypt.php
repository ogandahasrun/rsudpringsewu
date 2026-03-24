<?php
/**
 * BPJS VClaim Response Decryption
 * File khusus untuk mendekripsi response dari BPJS VClaim API
 *
 * Spesifikasi:
 * - Kompresi: LZ-string
 * - Enkripsi: AES 256 CBC dengan SHA256
 * - Key: consid + conspwd + timestamp (concatenate)
 * - Langkah: 1. AES decrypt, 2. LZ-string decompress
 */

require_once 'vendor/autoload.php';
require_once 'koneksi.php';

/**
 * Decrypt BPJS VClaim Response
 *
 * @param string $encryptedResponse - Response terenkripsi dari API
 * @param string $consid - Consumer ID
 * @param string $conspwd - Consumer Password/Secret Key
 * @param string $timestamp - Timestamp yang digunakan untuk request
 * @return string - Data terdekripsi dalam format JSON
 */
function decryptVClaimResponse($encryptedResponse, $consid, $conspwd, $timestamp) {
    // Generate key: consid + conspwd + timestamp (concatenate)
    $key = $consid . $conspwd . $timestamp;

    // AES Decrypt
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);

    $decrypted = openssl_decrypt(base64_decode($encryptedResponse), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);

    // LZ-string Decompress
    $decompressed = \LZCompressor\LZString::decompressFromEncodedURIComponent($decrypted);

    return $decompressed;
}

/**
 * Decrypt BPJS VClaim Response menggunakan config global
 * (untuk kemudahan penggunaan dengan data dari koneksi.php)
 *
 * @param string $encryptedResponse - Response terenkripsi dari API
 * @param string $timestamp - Timestamp yang digunakan untuk request
 * @return string - Data terdekripsi dalam format JSON
 */
function decryptVClaimResponseGlobal($encryptedResponse, $timestamp) {
    global $CONSIDVCLAIM, $SECRETKEYVCLAIM;

    return decryptVClaimResponse($encryptedResponse, $CONSIDVCLAIM, $SECRETKEYVCLAIM, $timestamp);
}

/**
 * Helper function untuk AES decrypt saja
 *
 * @param string $key - Key untuk dekripsi
 * @param string $encryptedString - String terenkripsi
 * @return string - Data terdekripsi
 */
function stringDecrypt($key, $encryptedString) {
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);

    $output = openssl_decrypt(base64_decode($encryptedString), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);

    return $output;
}

/**
 * Helper function untuk LZ-string decompress saja
 *
 * @param string $compressedString - String terkompresi
 * @return string - Data terdekompresi
 */
function decompressLZString($compressedString) {
    return \LZCompressor\LZString::decompressFromEncodedURIComponent($compressedString);
}
?>