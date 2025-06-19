<?php
function getLocalIPAddress() {
    $localIP = '';
    
    // Try to get IP from various sources
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $localIP = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $localIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $localIP = $_SERVER['REMOTE_ADDR'];
    }
    
    // If we still don't have an IP, try to get the server's local IP
    if (empty($localIP) || $localIP === '::1' || $localIP === '127.0.0.1') {
        $localIP = gethostbyname(trim(`hostname`));
    }
    
    return $localIP;
}

$serverIP = getLocalIPAddress();
$serverName = gethostname();
$currentTime = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ローカルIP表示 - PHPランディングページ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            text-align: center;
            max-width: 600px;
            width: 90%;
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .ip-display {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .ip-label {
            font-size: 1.2em;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .ip-value {
            font-size: 2em;
            font-weight: bold;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            font-family: 'Courier New', monospace;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .info-title {
            font-size: 1.1em;
            margin-bottom: 8px;
            opacity: 0.8;
        }
        
        .info-value {
            font-size: 1.3em;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            opacity: 0.7;
            font-size: 0.9em;
        }
        
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2em;
            }
            
            .ip-value {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 ローカルIP情報</h1>
        
        <div class="ip-display">
            <div class="ip-label">あなたのローカルIPアドレス</div>
            <div class="ip-value"><?php echo htmlspecialchars($serverIP); ?></div>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <div class="info-title">サーバー名</div>
                <div class="info-value"><?php echo htmlspecialchars($serverName); ?></div>
            </div>
            
            <div class="info-card">
                <div class="info-title">現在時刻</div>
                <div class="info-value"><?php echo $currentTime; ?></div>
            </div>
        </div>
        
        <div class="footer">
            <p>PHP <?php echo phpversion(); ?> で動作中</p>
            <p>最新のPHPバージョンを使用したプロジェクトです</p>
        </div>
    </div>
</body>
</html>