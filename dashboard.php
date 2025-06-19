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

// Function to parse phpinfo() output into array
function parsePhpInfo() {
    ob_start();
    phpinfo();
    $phpinfo = ob_get_clean();
    
    $phpinfo_array = [];
    
    // Parse the HTML output
    $phpinfo = strip_tags($phpinfo, '<h2><th><td>');
    $phpinfo = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $phpinfo);
    $phpinfo = preg_replace('/<td[^>]*>([^<]*)<\/td>/', '<info>\1</info>', $phpinfo);
    $vTmp = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $phpinfo, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    for ($i = 1; $i < count($vTmp); $i++) {
        if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $vTmp[$i], $vMat)) {
            $vName = trim($vMat[1]);
            $vVal = $vTmp[$i + 1];
            $vVal = preg_replace('/<info>([^<]*)<\/info>/', '\1' . "\n", $vVal);
            $vVal = explode("\n", $vVal);
            
            for ($j = 0; $j < count($vVal); $j++) {
                $line = trim($vVal[$j]);
                if (!empty($line)) {
                    if ($j % 2 == 0 && isset($vVal[$j + 1])) {
                        $phpinfo_array[$vName][trim($line)] = trim($vVal[$j + 1]);
                    }
                }
            }
            $i++;
        }
    }
    
    return $phpinfo_array;
}

// Handle CSV download request
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    $serverIP = getLocalIPAddress();
    $serverName = gethostname();
    $currentTime = date('Y-m-d H:i:s');
    $containerInfo = getContainerInfo();
    
    // Get comprehensive PHP info using phpinfo()
    $phpinfo_array = parsePhpInfo();
    
    // Initialize CSV data
    $csvData = [
        ['Category', 'Setting', 'Value'],
        ['Server Info', 'Local IP Address', $serverIP],
        ['Server Info', 'Server Name', $serverName],
        ['Server Info', 'Container Name', $containerInfo['name']],
        ['Server Info', 'Server Type', $containerInfo['server_type']],
        ['Server Info', 'Port Mapping', $containerInfo['port_mapping']],
        ['Server Info', 'Current Time', $currentTime],
        ['', '', ''], // Empty row for separation
    ];
    
    // Add all phpinfo() sections to CSV
    foreach ($phpinfo_array as $section => $settings) {
        $csvData[] = ['=== ' . $section . ' ===', '', ''];
        
        foreach ($settings as $key => $value) {
            // Clean up the value (remove extra whitespace, handle arrays)
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $value = preg_replace('/\s+/', ' ', trim($value));
            
            $csvData[] = [$section, $key, $value];
        }
        $csvData[] = ['', '', '']; // Empty row for separation
    }
    
    // Add loaded extensions section
    $csvData[] = ['=== Loaded Extensions ===', '', ''];
    $extensions = get_loaded_extensions();
    foreach ($extensions as $index => $extension) {
        $csvData[] = ['Extensions', 'Extension #' . ($index + 1), $extension];
        
        // Add extension version if available
        $version = phpversion($extension);
        if ($version) {
            $csvData[] = ['Extensions', $extension . ' Version', $version];
        }
    }
    
    // Add detailed configuration
    $csvData[] = ['', '', ''];
    $csvData[] = ['=== Additional Configuration ===', '', ''];
    
    // Get all ini settings
    $iniSettings = ini_get_all();
    foreach ($iniSettings as $key => $setting) {
        $global_value = isset($setting['global_value']) ? $setting['global_value'] : 'N/A';
        $local_value = isset($setting['local_value']) ? $setting['local_value'] : 'N/A';
        
        $csvData[] = ['INI Settings', $key . ' (Global)', $global_value];
        if ($global_value !== $local_value) {
            $csvData[] = ['INI Settings', $key . ' (Local)', $local_value];
        }
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="php_complete_info_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output CSV with BOM for proper UTF-8 handling
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    foreach ($csvData as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

$serverIP = getLocalIPAddress();
$serverName = gethostname();
$currentTime = date('Y-m-d H:i:s');

// Detect container and port information
function getContainerInfo() {
    $containerInfo = [
        'name' => 'Unknown',
        'port_mapping' => 'Unknown',
        'server_type' => 'Unknown'
    ];
    
    // Try to get container name from hostname
    $hostname = gethostname();
    $containerInfo['name'] = $hostname;
    
    // Detect server type based on SAPI and environment
    $sapi = php_sapi_name();
    if ($sapi === 'apache2handler') {
        $containerInfo['server_type'] = 'Apache + PHP';
        // Check if this is the Apache container
        if (strpos($hostname, 'apache') !== false) {
            $containerInfo['port_mapping'] = '8080 → 80';
        }
    } elseif ($sapi === 'fpm-fcgi') {
        $containerInfo['server_type'] = 'Nginx + PHP-FPM';
        // Check if this is the Nginx container
        if (strpos($hostname, 'nginx') !== false) {
            $containerInfo['port_mapping'] = '8081 → 80';
        }
    }
    
    // Try to detect port from SERVER variables
    if (isset($_SERVER['SERVER_PORT'])) {
        $internal_port = $_SERVER['SERVER_PORT'];
        
        // Map internal port to external port based on container type
        if ($internal_port == '80') {
            if ($containerInfo['server_type'] === 'Apache + PHP') {
                $containerInfo['port_mapping'] = '8080 → 80';
            } elseif ($containerInfo['server_type'] === 'Nginx + PHP-FPM') {
                $containerInfo['port_mapping'] = '8081 → 80';
            }
        }
    }
    
    return $containerInfo;
}

$containerInfo = getContainerInfo();

// Get PHP configuration info
$phpInfo = [
    'version' => phpversion(),
    'sapi' => php_sapi_name(),
    'os' => PHP_OS,
    'architecture' => php_uname('m'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'timezone' => date_default_timezone_get(),
    'extensions_count' => count(get_loaded_extensions()),
    'loaded_extensions' => array_slice(get_loaded_extensions(), 0, 20)
];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Server Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'php-blue': '#4F46E5',
                        'php-purple': '#7C3AED',
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        body {
            height: 100vh;
            overflow: hidden;
        }
        .compact-grid {
            height: calc(100vh - 2rem);
        }
    </style>
</head>
<body class="gradient-bg">
    <div class="h-screen p-4 overflow-hidden">
        <!-- Full HD Optimized Layout - Uniform Height Columns -->
        <div class="grid grid-cols-12 gap-4 h-full">
            
            <!-- Left Column - Server & IP Info -->
            <div class="col-span-4">
                <div class="glass-effect bg-white/20 backdrop-blur-md rounded-2xl p-6 border border-white/30 h-full flex flex-col">
                    <!-- Header Section -->
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold text-white mb-2">🌐 Server Dashboard</h1>
                        <!-- CSV Download Button -->
                        <a href="?download=csv" class="inline-flex items-center bg-gradient-to-r from-green-500 to-blue-600 hover:from-green-600 hover:to-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            PHPInfo CSV Download
                        </a>
                    </div>

                    <!-- IP Address Section -->
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-white mb-3 text-center">ローカルIPアドレス</h2>
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-4 mb-4">
                            <p class="text-3xl font-mono font-bold text-white text-center tracking-wider animate-pulse-slow">
                                <?php echo htmlspecialchars($serverIP); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Basic Info Grid -->
                    <div class="grid grid-cols-1 gap-3 mb-6">
                        <div class="bg-white/10 rounded-lg p-3 border border-white/20">
                            <h3 class="text-xs font-semibold text-blue-100 uppercase mb-1">🖥️ サーバー名</h3>
                            <p class="text-lg font-mono text-white"><?php echo htmlspecialchars($serverName); ?></p>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 border border-white/20">
                            <h3 class="text-xs font-semibold text-blue-100 uppercase mb-1">⏰ 現在時刻</h3>
                            <p class="text-lg font-mono text-white"><?php echo $currentTime; ?></p>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3 border border-white/20">
                            <h3 class="text-xs font-semibold text-blue-100 uppercase mb-1">🐘 PHP Version</h3>
                            <p class="text-xl font-bold text-white"><?php echo $phpInfo['version']; ?></p>
                            <p class="text-blue-200 text-sm">Running on <?php echo $phpInfo['sapi']; ?></p>
                        </div>
                    </div>

                    <!-- Container Info -->
                    <div class="mt-auto">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-blue-500/20 rounded-lg p-2 border border-blue-400/30">
                                <p class="text-xs text-blue-200">コンテナ</p>
                                <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($containerInfo['name']); ?></p>
                            </div>
                            <div class="bg-purple-500/20 rounded-lg p-2 border border-purple-400/30">
                                <p class="text-xs text-purple-200">ポート</p>
                                <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($containerInfo['port_mapping']); ?></p>
                            </div>
                        </div>
                        <div class="bg-green-500/20 rounded-lg p-2 mt-2 border border-green-400/30 text-center">
                            <p class="text-xs text-green-200">サーバータイプ</p>
                            <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($containerInfo['server_type']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Column - PHP Configuration -->
            <div class="col-span-5">
                <div class="glass-effect bg-white/15 backdrop-blur-md rounded-2xl p-6 border border-white/30 h-full flex flex-col">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-white mb-2">📋 PHP設定情報</h2>
                        <p class="text-sm text-blue-100">システム設定とパフォーマンス詳細</p>
                    </div>

                    <!-- Configuration Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div class="bg-white/10 rounded-lg p-3 border border-white/20 hover:bg-white/15 transition-all duration-300">
                            <div class="flex items-center mb-2">
                                <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                                <h4 class="text-xs font-semibold text-blue-100 uppercase">OS</h4>
                            </div>
                            <p class="text-sm font-semibold text-white"><?php echo $phpInfo['os']; ?></p>
                            <p class="text-xs text-blue-200"><?php echo $phpInfo['architecture']; ?></p>
                        </div>

                        <div class="bg-white/10 rounded-lg p-3 border border-white/20 hover:bg-white/15 transition-all duration-300">
                            <div class="flex items-center mb-2">
                                <div class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></div>
                                <h4 class="text-xs font-semibold text-blue-100 uppercase">Memory</h4>
                            </div>
                            <p class="text-sm font-semibold text-white"><?php echo $phpInfo['memory_limit']; ?></p>
                        </div>

                        <div class="bg-white/10 rounded-lg p-3 border border-white/20 hover:bg-white/15 transition-all duration-300">
                            <div class="flex items-center mb-2">
                                <div class="w-2 h-2 bg-red-400 rounded-full mr-2"></div>
                                <h4 class="text-xs font-semibold text-blue-100 uppercase">Max Exec</h4>
                            </div>
                            <p class="text-sm font-semibold text-white"><?php echo $phpInfo['max_execution_time']; ?>s</p>
                        </div>

                        <div class="bg-white/10 rounded-lg p-3 border border-white/20 hover:bg-white/15 transition-all duration-300">
                            <div class="flex items-center mb-2">
                                <div class="w-2 h-2 bg-purple-400 rounded-full mr-2"></div>
                                <h4 class="text-xs font-semibold text-blue-100 uppercase">Upload Max</h4>
                            </div>
                            <p class="text-sm font-semibold text-white"><?php echo $phpInfo['upload_max_filesize']; ?></p>
                        </div>

                        <div class="bg-white/10 rounded-lg p-3 border border-white/20 hover:bg-white/15 transition-all duration-300">
                            <div class="flex items-center mb-2">
                                <div class="w-2 h-2 bg-blue-400 rounded-full mr-2"></div>
                                <h4 class="text-xs font-semibold text-blue-100 uppercase">POST Max</h4>
                            </div>
                            <p class="text-sm font-semibold text-white"><?php echo $phpInfo['post_max_size']; ?></p>
                        </div>

                        <div class="bg-white/10 rounded-lg p-3 border border-white/20 hover:bg-white/15 transition-all duration-300">
                            <div class="flex items-center mb-2">
                                <div class="w-2 h-2 bg-indigo-400 rounded-full mr-2"></div>
                                <h4 class="text-xs font-semibold text-blue-100 uppercase">Timezone</h4>
                            </div>
                            <p class="text-sm font-semibold text-white"><?php echo $phpInfo['timezone']; ?></p>
                        </div>
                    </div>

                    <!-- Extensions Section -->
                    <div class="flex-1">
                        <div class="bg-white/10 rounded-lg p-4 border border-white/20 h-full flex flex-col">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-white">🔧 拡張モジュール</h3>
                                <span class="bg-blue-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                    <?php echo $phpInfo['extensions_count']; ?>
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-1 overflow-y-auto flex-1 content-start">
                                <?php foreach ($phpInfo['loaded_extensions'] as $extension): ?>
                                    <span class="bg-gradient-to-r from-blue-500/30 to-purple-500/30 text-white px-2 py-0.5 rounded-full text-xs border border-white/20 hover:from-blue-500/50 hover:to-purple-500/50 transition-all duration-300 flex-shrink-0 leading-tight">
                                        <?php echo htmlspecialchars($extension); ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if ($phpInfo['extensions_count'] > 20): ?>
                                    <span class="text-blue-200 px-2 py-0.5 text-xs flex-shrink-0 leading-tight">
                                        +<?php echo $phpInfo['extensions_count'] - 20; ?> more...
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - System Statistics -->
            <div class="col-span-3">
                <div class="glass-effect bg-white/15 backdrop-blur-md rounded-2xl p-6 border border-white/30 h-full flex flex-col">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">📊 システム統計</h3>
                        <p class="text-sm text-blue-100">詳細情報とクイックアクセス</p>
                    </div>

                    <!-- System Stats -->
                    <div class="space-y-4 mb-6">
                        <div class="bg-white/10 rounded-lg p-4 border border-white/20">
                            <h4 class="text-xs font-semibold text-blue-100 uppercase mb-2">拡張モジュール数</h4>
                            <p class="text-3xl font-bold text-white"><?php echo $phpInfo['extensions_count']; ?></p>
                        </div>
                        
                        <div class="bg-white/10 rounded-lg p-4 border border-white/20">
                            <h4 class="text-xs font-semibold text-blue-100 uppercase mb-2">PHP SAPI</h4>
                            <p class="text-lg font-semibold text-white"><?php echo $phpInfo['sapi']; ?></p>
                        </div>
                        
                        <div class="bg-white/10 rounded-lg p-4 border border-white/20">
                            <h4 class="text-xs font-semibold text-blue-100 uppercase mb-2">アーキテクチャ</h4>
                            <p class="text-lg font-semibold text-white"><?php echo $phpInfo['architecture']; ?></p>
                        </div>
                    </div>

                    <!-- Status Indicators -->
                    <div class="space-y-3 mt-auto">
                        <div class="bg-green-500/20 rounded-lg p-3 border border-green-400/30">
                            <p class="text-xs text-green-200">ステータス</p>
                            <p class="text-sm font-bold text-white">✅ Online & Ready</p>
                        </div>
                        
                        <div class="bg-orange-500/20 rounded-lg p-3 border border-orange-400/30">
                            <p class="text-xs text-orange-200">エクスポート機能</p>
                            <p class="text-sm font-bold text-white">📊 CSV Ready</p>
                        </div>
                        
                        <div class="bg-blue-500/20 rounded-lg p-3 border border-blue-400/30 text-center">
                            <p class="text-sm font-bold text-white">🚀 詳細表示画面</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Animation script -->
    <script>
        // Add subtle animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.bg-white\\/10, .bg-white\\/15, .bg-white\\/20');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.05}s`;
                card.classList.add('animate-fade-in');
            });
        });
    </script>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.4s ease-out forwards;
        }
    </style>
</body>
</html>