<?php
// Lightweight health check page
$serverIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$serverName = gethostname();
$currentTime = date('Y-m-d H:i:s');
$phpVersion = phpversion();
$sapi = php_sapi_name();

// Detect server type for container identification
$serverType = 'Unknown';
if ($sapi === 'apache2handler') {
    $serverType = 'Apache + PHP';
} elseif ($sapi === 'fpm-fcgi') {
    $serverType = 'Nginx + PHP-FPM';
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Server Health Check</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Health Check Status -->
        <div class="glass-effect bg-white/20 backdrop-blur-md rounded-3xl p-8 border border-white/30 shadow-2xl text-center">
            <!-- Status Icon -->
            <div class="mb-6">
                <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">✅ Server Online</h1>
                <p class="text-blue-100 text-sm">Health check passed</p>
            </div>

            <!-- Quick Info -->
            <div class="space-y-3 mb-6">
                <div class="bg-white/10 rounded-lg p-3 border border-white/20">
                    <p class="text-xs text-blue-200 uppercase tracking-wide">Server Type</p>
                    <p class="text-white font-semibold"><?php echo htmlspecialchars($serverType); ?></p>
                </div>
                
                <div class="bg-white/10 rounded-lg p-3 border border-white/20">
                    <p class="text-xs text-blue-200 uppercase tracking-wide">PHP Version</p>
                    <p class="text-white font-semibold"><?php echo htmlspecialchars($phpVersion); ?></p>
                </div>
                
                <div class="bg-white/10 rounded-lg p-3 border border-white/20">
                    <p class="text-xs text-blue-200 uppercase tracking-wide">Container</p>
                    <p class="text-white font-semibold"><?php echo htmlspecialchars($serverName); ?></p>
                </div>
                
                <div class="bg-white/10 rounded-lg p-3 border border-white/20">
                    <p class="text-xs text-blue-200 uppercase tracking-wide">Current Time</p>
                    <p class="text-white font-semibold"><?php echo $currentTime; ?></p>
                </div>
            </div>

            <!-- Dashboard Link -->
            <div class="space-y-3">
                <a href="dashboard.php" class="block w-full bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                    📊 Full Dashboard
                </a>
                
                <p class="text-xs text-blue-200">
                    View comprehensive server information and download CSV
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-white/70 text-sm">
                🚀 Lightweight Health Check Page
            </p>
        </div>
    </div>

    <!-- Entrance Animation -->
    <script>
        // Add entrance animation
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.glass-effect');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease-out';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>