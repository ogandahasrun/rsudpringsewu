<?php
// Include koneksi untuk ambil konfigurasi
require_once 'koneksi.php';

/**
 * Fungsi untuk mendapatkan token dari Mobile JKN Auth
 * @return array Response dengan token atau error
 */
function getTokenMJKN() {
    global $URLAUTHMJKN, $USERNAMEAUTHMJKN, $PASSWORDAUTHMJKN;
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $URLAUTHMJKN);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-username: ' . $USERNAMEAUTHMJKN,
        'x-password: ' . $PASSWORDAUTHMJKN
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    // Check for cURL errors
    if ($response === false) {
        return array(
            'success' => false,
            'message' => 'Curl Error: ' . $curl_error,
            'token' => null
        );
    }
    
    // Decode response
    $response_data = json_decode($response, true);
    
    // Check HTTP status
    if ($http_code == 200) {
        return array(
            'success' => true,
            'message' => 'Token berhasil didapatkan',
            'token' => isset($response_data['token']) ? $response_data['token'] : $response_data,
            'http_code' => $http_code,
            'response' => $response_data
        );
    } else {
        return array(
            'success' => false,
            'message' => 'Gagal mendapatkan token. HTTP Code: ' . $http_code,
            'token' => null,
            'http_code' => $http_code,
            'response' => $response_data
        );
    }
}

// Jika diakses langsung (bukan di-include), tampilkan halaman testing
if (basename($_SERVER['PHP_SELF']) == 'gettokenmjkn.php') {
    $token_result = null;
    
    if (isset($_POST['get_token'])) {
        $token_result = getTokenMJKN();
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Token Mobile JKN - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .info-box { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
        .info-box strong { color: #0c5460; }
        .info-box ul { margin: 10px 0 0 20px; color: #0c5460; }
        .config-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .config-table th, .config-table td { padding: 12px; border: 1px solid #dee2e6; text-align: left; }
        .config-table th { background: #f8f9fa; font-weight: bold; width: 30%; }
        .config-table td { font-family: 'Courier New', monospace; }
        .btn { padding: 14px 28px; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-success { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .alert-error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .response-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-top: 20px; }
        .response-title { font-weight: bold; color: #333; margin-bottom: 10px; font-size: 16px; }
        .response-content { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 13px; max-height: 400px; overflow-y: auto; }
        .token-display { background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 15px; margin-top: 15px; }
        .token-display strong { color: #856404; display: block; margin-bottom: 10px; }
        .token-value { background: #2d2d2d; color: #4CAF50; padding: 12px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 14px; word-break: break-all; }
        .copy-btn { background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-top: 10px; font-size: 13px; }
        .copy-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 Get Token Mobile JKN</h1>
            <p>Service untuk mendapatkan authentication token</p>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="dashboard.php">← Kembali</a>
            </div>
            
            <div class="info-box">
                <strong>ℹ️ Informasi:</strong>
                <ul>
                    <li>Service ini digunakan untuk mendapatkan token autentikasi dari Mobile JKN</li>
                    <li>Token yang didapatkan dapat digunakan untuk service API lainnya</li>
                    <li>Konfigurasi (URL, Username, Password) tersimpan di file koneksi.php</li>
                </ul>
            </div>
            
            <h3 style="margin-bottom: 15px; color: #333;">📋 Konfigurasi Saat Ini:</h3>
            <table class="config-table">
                <tr>
                    <th>URL Auth</th>
                    <td><?php echo htmlspecialchars($URLAUTHMJKN); ?></td>
                </tr>
                <tr>
                    <th>Username (x-username)</th>
                    <td><?php echo htmlspecialchars($USERNAMEAUTHMJKN); ?></td>
                </tr>
                <tr>
                    <th>Password (x-password)</th>
                    <td><?php echo str_repeat('*', strlen($PASSWORDAUTHMJKN)); ?> (hidden)</td>
                </tr>
                <tr>
                    <th>Method</th>
                    <td>GET</td>
                </tr>
            </table>
            
            <?php if ($token_result): ?>
                <?php if ($token_result['success']): ?>
                    <div class="alert alert-success">
                        <strong>✅ Berhasil!</strong> <?php echo htmlspecialchars($token_result['message']); ?>
                    </div>
                    
                    <div class="token-display">
                        <strong>🔐 Token:</strong>
                        <div class="token-value" id="tokenValue">
                            <?php echo htmlspecialchars(is_array($token_result['token']) ? json_encode($token_result['token']) : $token_result['token']); ?>
                        </div>
                        <button class="copy-btn" onclick="copyToken()">📋 Copy Token</button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <strong>❌ Error!</strong> <?php echo htmlspecialchars($token_result['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="response-box">
                    <div class="response-title">📡 Response Detail:</div>
                    <div class="response-content">
                        <?php echo json_encode($token_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" style="margin-top: 30px;">
                <button type="submit" name="get_token" class="btn btn-success">
                    🔑 Get Token Sekarang
                </button>
            </form>
            
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-weight: bold; color: #333; margin-bottom: 15px;">💡 Cara Penggunaan di File Lain:</div>
                <div style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; overflow-x: auto;">
<pre style="margin: 0; color: #f8f8f2;">// Include file
require_once 'gettokenmjkn.php';

// Dapatkan token
$result = getTokenMJKN();

if ($result['success']) {
    $token = $result['token'];
    echo "Token: " . $token;
} else {
    echo "Error: " . $result['message'];
}</pre>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyToken() {
            const tokenValue = document.getElementById('tokenValue').textContent;
            
            // Create temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = tokenValue;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            
            // Select and copy
            textarea.select();
            try {
                document.execCommand('copy');
                alert('✅ Token berhasil disalin ke clipboard!');
            } catch (err) {
                alert('❌ Gagal menyalin token.');
            }
            
            // Remove textarea
            document.body.removeChild(textarea);
        }
    </script>
</body>
</html>
<?php
}
?>
