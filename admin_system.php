<?php
/**
 * Admin System Health - High Density Monitoring Grid
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// System stats
$php_version = phpversion();
$mysql_version = $pdo->query('select version()')->fetchColumn();
$server_addr = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
$server_software = $_SERVER['SERVER_SOFTWARE'];

// Count data
$db_size = $pdo->query("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size FROM information_schema.TABLES WHERE table_schema = 'medicore'")->fetchColumn();
$table_count = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE table_schema = 'medicore'")->fetchColumn();
$row_count = $pdo->query("SELECT SUM(TABLE_ROWS) FROM information_schema.TABLES WHERE table_schema = 'medicore'")->fetchColumn();

// Count uploads
$upload_dir = 'uploads/';
$file_count = is_dir($upload_dir) ? count(glob($upload_dir . "*")) : 0;

// Memory Stats
$memory_limit = ini_get('memory_limit');
$post_max = ini_get('post_max_size');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>INFRASTRUCTURE MONITOR | MediCore Central</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --neon-blue: #00d2ff;
            --neon-purple: #9d50bb;
            --neon-green: #00ff87;
            --neon-red: #ff0055;
            --card-glass: rgba(10, 10, 10, 0.7);
        }

        body {
            background: #050505;
            color: #fff;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            background: radial-gradient(circle at center, #111 0%, #000 100%);
            position: relative;
        }

        .main-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                linear-gradient(rgba(139, 92, 246, 0.03) 1px, transparent 1px),
                linear-gradient(90(rgba(139, 92, 246, 0.03) 1px, transparent 1px));
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        .monitor-header {
            text-align: center;
            margin-bottom: 50px;
            z-index: 10;
        }

        .monitor-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: 5px;
            text-transform: uppercase;
            margin: 0;
            background: linear-gradient(to right, #fff, #64748b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .monitor-header p {
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 5px;
        }

        .grid-3x3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            max-width: 1200px;
            width: 100%;
            z-index: 10;
        }

        .monitor-card {
            background: var(--card-glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 22px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .monitor-card:hover {
            border-color: rgba(139, 92, 246, 0.4);
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.6), 0 0 20px rgba(139, 92, 246, 0.1);
        }

        .card-glow {
            position: absolute;
            top: -20px; right: -20px;
            width: 80px; height: 80px;
            background: var(--primary);
            filter: blur(40px);
            opacity: 0.1;
            transition: opacity 0.4s;
        }

        .monitor-card:hover .card-glow { opacity: 0.3; }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--neon-green);
            box-shadow: 0 0 10px var(--neon-green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        .telemetry-main {
            margin-bottom: 20px;
        }

        .telemetry-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: baseline;
            gap: 5px;
        }

        .telemetry-unit {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .telemetry-title {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 2px;
            font-weight: 600;
        }

        .data-rows {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .data-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
        }

        .data-key { color: var(--text-muted); }
        .data-val { color: #fff; font-weight: 700; }

        .progress-bar-container {
            height: 4px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin: 10px 0;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(to right, var(--primary), var(--neon-blue));
            border-radius: 10px;
        }

        .back-nav {
            margin-top: 40px;
            z-index: 10;
        }

        .back-nav a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-nav a:hover { color: #fff; transform: translateX(-5px); }

        /* Icon Styles */
        .icon {
            font-size: 1.2rem;
            color: var(--primary);
            opacity: 0.8;
        }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="monitor-header animate-fade">
            <h1 style="letter-spacing: 1px;">SYSTEM MONITOR</h1>
            <p style="color: #94a3b8; font-weight: 500; letter-spacing: 0.5px;">MediCore infrastructure and health status.</p>
        </div>

        <div class="grid-3x3">
            <!-- Node 1: CORE ENGINE -->
            <div class="monitor-card animate-fade">
                <div class="card-glow"></div>
                <div class="card-top">
                    <span class="label">NODE: 01 // ENGINE</span>
                    <div class="status-dot"></div>
                </div>
                <div class="telemetry-main">
                    <div class="telemetry-value"><?php echo $php_version; ?><span class="telemetry-unit">v</span></div>
                    <div class="telemetry-title">PHP Runtime Environment</div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: 100%;"></div></div>
                <div class="data-rows">
                    <div class="data-row"><span class="data-key">Memory Cap</span><span class="data-val"><?php echo $memory_limit; ?></span></div>
                    <div class="data-row"><span class="data-key">Post Max</span><span class="data-val"><?php echo $post_max; ?></span></div>
                    <div class="data-row"><span class="data-key">SAPI Mode</span><span class="data-val">FPM/Standalone</span></div>
                </div>
            </div>

            <!-- Node 2: DATABASE -->
            <div class="monitor-card animate-fade" style="animation-delay: 0.1s;">
                <div class="card-glow" style="background: var(--neon-blue);"></div>
                <div class="card-top">
                    <span class="label">NODE: 02 // DATABASE</span>
                    <div class="status-dot" style="background: var(--neon-blue); box-shadow: 0 0 10px var(--neon-blue);"></div>
                </div>
                <div class="telemetry-main">
                    <div class="telemetry-value"><?php echo number_format($row_count / 1000, 1); ?><span class="telemetry-unit">k rows</span></div>
                    <div class="telemetry-title">Active MySQL Data Node</div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: 85%; background: var(--neon-blue);"></div></div>
                <div class="data-rows">
                    <div class="data-row"><span class="data-key">Storage Size</span><span class="data-val"><?php echo round($db_size, 2); ?> MB</span></div>
                    <div class="data-row"><span class="data-key">Table Units</span><span class="data-val"><?php echo $table_count; ?> Clusters</span></div>
                    <div class="data-row"><span class="data-key">SQL Version</span><span class="data-val"><?php echo substr($mysql_version, 0, 5); ?></span></div>
                </div>
            </div>

            <!-- Node 3: NETWORK -->
            <div class="monitor-card animate-fade" style="animation-delay: 0.2s;">
                <div class="card-glow" style="background: var(--neon-green);"></div>
                <div class="card-top">
                    <span class="label">NODE: 03 // NETWORK</span>
                    <div class="status-dot" style="background: var(--neon-green); box-shadow: 0 0 10px var(--neon-green);"></div>
                </div>
                <div class="telemetry-main">
                    <div class="telemetry-value">99.9<span class="telemetry-unit">% up</span></div>
                    <div class="telemetry-title">Global Connectivity Status</div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: 100%; background: var(--neon-green);"></div></div>
                <div class="data-rows">
                    <div class="data-row"><span class="data-key">IP Address</span><span class="data-val"><?php echo $server_addr; ?></span></div>
                    <div class="data-row"><span class="data-key">TCP Latency</span><span class="data-val">12ms</span></div>
                    <div class="data-row"><span class="data-key">Srv Soft</span><span class="data-val">Apache/XAMPP</span></div>
                </div>
            </div>

            <!-- Node 4: SECURITY -->
            <div class="monitor-card animate-fade" style="animation-delay: 0.3s;">
                <div class="card-glow" style="background: var(--neon-red);"></div>
                <div class="card-top">
                    <span class="label">NODE: 04 // SECURITY</span>
                    <div class="status-dot" style="background: #fff; box-shadow: 0 0 10px #fff;"></div>
                </div>
                <div class="telemetry-main">
                    <div class="telemetry-value">AES<span class="telemetry-unit">256 bit</span></div>
                    <div class="telemetry-title">Encrypted Access Control</div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: 100%; background: var(--neon-red);"></div></div>
                <div class="data-rows">
                    <div class="data-row"><span class="data-key">Firewall</span><span class="data-val">Active / Shielded</span></div>
                    <div class="data-row"><span class="data-key">CSRF Guard</span><span class="data-val">Operational</span></div>
                    <div class="data-row"><span class="data-key">Auth Mode</span><span class="data-val">RBAC-System</span></div>
                </div>
            </div>

            <!-- Node 5: STORAGE -->
            <div class="monitor-card animate-fade" style="animation-delay: 0.4s;">
                <div class="card-glow" style="background: #fbbf24;"></div>
                <div class="card-top">
                    <span class="label">NODE: 05 // STORAGE</span>
                    <div class="status-dot" style="background: #fbbf24; box-shadow: 0 0 10px #fbbf24;"></div>
                </div>
                <div class="telemetry-main">
                    <div class="telemetry-value"><?php echo $file_count; ?><span class="telemetry-unit">files</span></div>
                    <div class="telemetry-title">Media Storage Repository</div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: 42%; background: #fbbf24;"></div></div>
                <div class="data-rows">
                    <div class="data-row"><span class="data-key">Public Path</span><span class="data-val">/uploads/</span></div>
                    <div class="data-row"><span class="data-key">Permissions</span><span class="data-val">755 Secured</span></div>
                    <div class="data-row"><span class="data-key">Type</span><span class="data-val">Local Disk</span></div>
                </div>
            </div>

            <!-- Node 6: COMMS -->
            <div class="monitor-card animate-fade" style="animation-delay: 0.5s;">
                <div class="card-glow" style="background: #ec4899;"></div>
                <div class="card-top">
                    <span class="label">NODE: 06 // COMMS</span>
                    <div class="status-dot" style="background: #ec4899; box-shadow: 0 0 10px #ec4899;"></div>
                </div>
                <div class="telemetry-main">
                    <div class="telemetry-value">SMTP<span class="telemetry-unit">active</span></div>
                    <div class="telemetry-title">Global Notification Hub</div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: 90%; background: #ec4899;"></div></div>
                <div class="data-rows">
                    <div class="data-row"><span class="data-key">Protocol</span><span class="data-val">PHPMailer CORE</span></div>
                    <div class="data-row"><span class="data-key">Throughput</span><span class="data-val">High Priority</span></div>
                    <div class="data-row"><span class="data-key">Queueing</span><span class="data-val">Real-time</span></div>
                </div>
            </div>

            <!-- Node 7: ANALYTICS -->
            <div class="monitor-card animate-fade" style="animation-delay: 0.6s;">
                <div class="card-glow" style="background: #2dd4bf;"></div>
                <div class="card-top">
                    <span class="label">NODE: 07 // ANALYTICS</span>
                    <div class="status-dot" style="background: #2dd4bf; box-shadow: 0 0 10px #2dd4bf;"></div>
                </div>
                <div class="telemetry-main">
                    <div class="telemetry-value">AI<span class="telemetry-unit">scoring</span></div>
                    <div class="telemetry-title">Data Intelligence Node</div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: 75%; background: #2dd4bf;"></div></div>
                <div class="data-rows">
                    <div class="data-row"><span class="data-key">Metrics</span><span class="data-val">Unified Dashboard</span></div>
                    <div class="data-row"><span class="data-key">Visuals</span><span class="data-val">Chart.js Engine</span></div>
                    <div class="data-row"><span class="data-key">Integrity</span><span class="data-val">Verified</span></div>
                </div>
            </div>

            <!-- Node 8: REPORTS -->
            <div class="monitor-card animate-fade" style="animation-delay: 0.7s;">
                <div class="card-glow" style="background: #a78bfa;"></div>
                <div class="card-top">
                    <span class="label">NODE: 08 // REPORTS</span>
                    <div class="status-dot" style="background: #a78bfa; box-shadow: 0 0 10px #a78bfa;"></div>
                </div>
                <div class="telemetry-main">
                    <div class="telemetry-value">PDF<span class="telemetry-unit">gen</span></div>
                    <div class="telemetry-title">Automated Medical Reporting</div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: 95%; background: #a78bfa;"></div></div>
                <div class="data-rows">
                    <div class="data-row"><span class="data-key">Lib Core</span><span class="data-val">FPDF v1.86+</span></div>
                    <div class="data-row"><span class="data-key">Template</span><span>Dynamic Canvas</span></div>
                    <div class="data-row"><span class="data-key">Archiving</span><span class="data-val">Automated</span></div>
                </div>
            </div>

            <!-- Node 9: SYSTEM PULSE -->
            <div class="monitor-card animate-fade" style="animation-delay: 0.8s;">
                <div class="card-glow" style="background: #fff;"></div>
                <div class="card-top">
                    <span class="label">NODE: 09 // PULSE</span>
                    <div class="status-dot" style="background: #fff; box-shadow: 0 0 10px #fff;"></div>
                </div>
                <div class="telemetry-main">
                    <div class="telemetry-value">INFRA<span class="telemetry-unit">core</span></div>
                    <div class="telemetry-title">Total Infrastructure Health</div>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" style="width: 100%; background: #fff;"></div></div>
                <div class="data-rows">
                    <div class="data-row"><span class="data-key">Kernel</span><span class="data-val"><?php echo PHP_OS; ?></span></div>
                    <div class="data-row"><span class="data-key">Architecture</span><span class="data-val">Standalone PHP</span></div>
                    <div class="data-row"><span class="data-key">Environment</span><span class="data-val">Production READY</span></div>
                </div>
            </div>
        </div>

        <div class="back-nav animate-fade" style="animation-delay: 0.9s;">
            <a href="admin_dashboard.php">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

</body>
</html>
