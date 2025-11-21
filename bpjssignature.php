<?php
/**
 * BPJS Signature Generator
 * Fungsi untuk generate signature BPJS Mobile JKN
 */

// Include koneksi untuk mendapatkan konfigurasi BPJS
require_once 'koneksi.php';

/**
 * Generate BPJS Signature
 * 
 * @param string $consid - Consumer ID (default dari config)
 * @param string $secretkey - Secret Key (default dari config)
 * @return array - Array berisi X-cons-id, X-timestamp, dan X-signature
 */
function generateBPJSSignature($consid = null, $secretkey = null) {
    global $CONSIDAPIMOBILEJKN, $SECRETKEYAPIMOBILEJKN;
    
    // Gunakan nilai dari parameter jika ada, jika tidak gunakan dari config
    $data = $consid ?? $CONSIDAPIMOBILEJKN;
    $secretKey = $secretkey ?? $SECRETKEYAPIMOBILEJKN;
    
    // Set timezone ke UTC
    date_default_timezone_set('UTC');
    
    // Hitung timestamp
    $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
    
    // Hitung signature dengan hash HMAC SHA256
    $signature = hash_hmac('sha256', $data . "&" . $tStamp, $secretKey, true);
    
    // Base64 encode
    $encodedSignature = base64_encode($signature);
    
    // Return array
    return array(
        'x-cons-id' => $data,
        'x-timestamp' => $tStamp,
        'x-signature' => $encodedSignature
    );
}

/**
 * Generate BPJS Headers untuk cURL
 * 
 * @param string $userkey - User Key (default dari config)
 * @return array - Array header siap pakai untuk cURL
 */
function getBPJSHeaders($userkey = null) {
    global $USERKEYAPIMOBILEJKN;
    
    $signature = generateBPJSSignature();
    $user_key = $userkey ?? $USERKEYAPIMOBILEJKN;
    
    return array(
        'X-cons-id: ' . $signature['x-cons-id'],
        'X-timestamp: ' . $signature['x-timestamp'],
        'X-signature: ' . $signature['x-signature'],
        'user_key: ' . $user_key,
        'Content-Type: application/json'
    );
}

/**
 * Debug - Tampilkan signature (untuk testing)
 */
function displayBPJSSignature() {
    global $URLAPIMOBILEJKN, $CONSIDAPIMOBILEJKN, $SECRETKEYAPIMOBILEJKN, $USERKEYAPIMOBILEJKN;
    
    $signature = generateBPJSSignature();
    $headers = getBPJSHeaders();
    
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BPJS Signature Test - RSUD Pringsewu</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
            .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
            .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 30px; text-align: center; }
            .header h1 { font-size: 2em; margin-bottom: 10px; }
            .header p { opacity: 0.9; }
            .content { padding: 30px; }
            .section { margin-bottom: 30px; }
            .section-title { font-size: 1.3em; color: #333; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #28a745; }
            .config-grid { display: grid; grid-template-columns: 200px 1fr; gap: 15px; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
            .config-label { font-weight: bold; color: #495057; }
            .config-value { font-family: 'Courier New', monospace; color: #007bff; word-break: break-all; }
            .signature-box { background: #e7f3ff; border-left: 4px solid #007bff; padding: 20px; border-radius: 8px; margin-bottom: 15px; }
            .signature-item { margin-bottom: 15px; }
            .signature-label { font-weight: bold; color: #333; margin-bottom: 5px; }
            .signature-value { background: white; padding: 10px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 13px; word-break: break-all; border: 1px solid #ddd; }
            .headers-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 8px; }
            .header-item { background: white; padding: 10px; margin-bottom: 10px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 12px; border: 1px solid #ddd; }
            .status { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
            .status.success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
            .status.warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
            .copy-btn { background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-top: 5px; }
            .copy-btn:hover { background: #0056b3; }
            .refresh-btn { display: inline-block; background: #28a745; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 20px; }
            .refresh-btn:hover { background: #218838; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔐 BPJS Signature Generator</h1>
                <p>Testing & Debugging Tool - RSUD Pringsewu</p>
            </div>
            
            <div class="content">
                <div class="status success">
                    <strong>✅ Status:</strong> Signature berhasil di-generate! File berfungsi dengan baik.
                </div>
                
                <?php if ($CONSIDAPIMOBILEJKN == 'your_consumer_id_here' || $SECRETKEYAPIMOBILEJKN == 'your_secret_key_here'): ?>
                <div class="status warning">
                    <strong>⚠️ Perhatian:</strong> Anda masih menggunakan konfigurasi default. Silakan update kredensial BPJS di file <code>koneksi.php</code>
                </div>
                <?php endif; ?>
                
                <div class="section">
                    <div class="section-title">📋 Konfigurasi BPJS dari koneksi.php</div>
                    <div class="config-grid">
                        <div class="config-label">URL API:</div>
                        <div class="config-value"><?php echo htmlspecialchars($URLAPIMOBILEJKN); ?></div>
                        
                        <div class="config-label">Consumer ID:</div>
                        <div class="config-value"><?php echo htmlspecialchars($CONSIDAPIMOBILEJKN); ?></div>
                        
                        <div class="config-label">Secret Key:</div>
                        <div class="config-value"><?php echo str_repeat('*', strlen($SECRETKEYAPIMOBILEJKN) - 4) . substr($SECRETKEYAPIMOBILEJKN, -4); ?></div>
                        
                        <div class="config-label">User Key:</div>
                        <div class="config-value"><?php echo str_repeat('*', strlen($USERKEYAPIMOBILEJKN) - 4) . substr($USERKEYAPIMOBILEJKN, -4); ?></div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">🔑 Generated Signature</div>
                    <div class="signature-box">
                        <div class="signature-item">
                            <div class="signature-label">X-cons-id:</div>
                            <div class="signature-value" id="consid"><?php echo htmlspecialchars($signature['x-cons-id']); ?></div>
                            <button class="copy-btn" onclick="copyToClipboard('consid')">📋 Copy</button>
                        </div>
                        
                        <div class="signature-item">
                            <div class="signature-label">X-timestamp:</div>
                            <div class="signature-value" id="timestamp"><?php echo htmlspecialchars($signature['x-timestamp']); ?></div>
                            <button class="copy-btn" onclick="copyToClipboard('timestamp')">📋 Copy</button>
                        </div>
                        
                        <div class="signature-item">
                            <div class="signature-label">X-signature:</div>
                            <div class="signature-value" id="signature"><?php echo htmlspecialchars($signature['x-signature']); ?></div>
                            <button class="copy-btn" onclick="copyToClipboard('signature')">📋 Copy</button>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">📤 Complete Headers untuk cURL Request</div>
                    <div class="headers-box">
                        <?php foreach ($headers as $header): ?>
                            <div class="header-item"><?php echo htmlspecialchars($header); ?></div>
                        <?php endforeach; ?>
                        <button class="copy-btn" onclick="copyHeaders()">📋 Copy All Headers</button>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">💡 Cara Penggunaan</div>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                        <p style="margin-bottom: 15px;"><strong>Di file PHP Anda:</strong></p>
                        <pre style="background: #2d2d2d; color: #fff; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px;">
<span style="color: #ff79c6;">&lt;?php</span>
<span style="color: #6272a4;">// Include file signature</span>
<span style="color: #8be9fd;">require_once</span> <span style="color: #f1fa8c;">'bpjssignature.php'</span>;

<span style="color: #6272a4;">// Get headers untuk request</span>
<span style="color: #ff79c6;">$</span>headers = <span style="color: #50fa7b;">getBPJSHeaders</span>();

<span style="color: #6272a4;">// Contoh request ke BPJS</span>
<span style="color: #ff79c6;">$</span>ch = <span style="color: #50fa7b;">curl_init</span>();
<span style="color: #50fa7b;">curl_setopt</span>(<span style="color: #ff79c6;">$</span>ch, CURLOPT_URL, <span style="color: #ff79c6;">$</span>URLAPIMOBILEJKN . <span style="color: #f1fa8c;">"/Peserta/nik/1234567890"</span>);
<span style="color: #50fa7b;">curl_setopt</span>(<span style="color: #ff79c6;">$</span>ch, CURLOPT_HTTPHEADER, <span style="color: #ff79c6;">$</span>headers);
<span style="color: #50fa7b;">curl_setopt</span>(<span style="color: #ff79c6;">$</span>ch, CURLOPT_RETURNTRANSFER, <span style="color: #bd93f9;">true</span>);
<span style="color: #ff79c6;">$</span>response = <span style="color: #50fa7b;">curl_exec</span>(<span style="color: #ff79c6;">$</span>ch);
<span style="color: #50fa7b;">curl_close</span>(<span style="color: #ff79c6;">$</span>ch);

<span style="color: #ff79c6;">echo</span> <span style="color: #ff79c6;">$</span>response;
<span style="color: #ff79c6;">?&gt;</span></pre>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <a href="?refresh=1" class="refresh-btn">🔄 Generate Ulang (Timestamp Baru)</a>
                </div>
            </div>
        </div>
        
        <script>
            function copyToClipboard(elementId) {
                const element = document.getElementById(elementId);
                const text = element.textContent;
                
                navigator.clipboard.writeText(text).then(() => {
                    const btn = element.nextElementSibling;
                    const originalText = btn.textContent;
                    btn.textContent = '✅ Copied!';
                    btn.style.background = '#28a745';
                    
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.style.background = '#007bff';
                    }, 2000);
                });
            }
            
            function copyHeaders() {
                const headers = document.querySelectorAll('.header-item');
                let text = '';
                headers.forEach(header => {
                    text += header.textContent + '\n';
                });
                
                navigator.clipboard.writeText(text).then(() => {
                    const btn = event.target;
                    const originalText = btn.textContent;
                    btn.textContent = '✅ All Headers Copied!';
                    btn.style.background = '#28a745';
                    
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.style.background = '#007bff';
                    }, 2000);
                });
            }
        </script>
    </body>
    </html>
    <?php
}

// Jika file ini diakses langsung, tampilkan signature untuk testing
if (basename($_SERVER['PHP_SELF']) == 'bpjssignature.php') {
    displayBPJSSignature();
}
?>
