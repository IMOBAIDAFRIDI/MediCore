<?php
/**
 * Hospital Dashboard (Enhanced)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
require_once 'csrf_helper.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hospital') {
    header('Location: login.php');
    exit();
}

$hospital_id = $_SESSION['user_id'];

try {
    // Hospital Profile
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$hospital_id]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);

    // Stats by status
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0, 'rejected' => 0, 'cancelled' => 0];
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM appointments WHERE hospital_id = ? GROUP BY status");
    $stmt->execute([$hospital_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $stats[$r['status']] = (int)$r['count'];
        $stats['total'] += (int)$r['count'];
    }

    // Stats by type
    $stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM appointments WHERE hospital_id = ? GROUP BY type");
    $stmt->execute([$hospital_id]);
    $type_stats = ['test' => 0, 'vaccination' => 0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $type_stats[$r['type']] = (int)$r['count'];
    }

    // Monthly trend (last 6 months)
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(appointment_date, '%b %Y') as month,
               DATE_FORMAT(appointment_date, '%Y-%m') as month_key,
               COUNT(*) as count
        FROM appointments
        WHERE hospital_id = ? AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month_key, month
        ORDER BY month_key ASC
    ");
    $stmt->execute([$hospital_id]);
    $monthly_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $monthly_labels = array_column($monthly_raw, 'month');
    $monthly_data   = array_column($monthly_raw, 'count');

    // Daily activity (last 14 days)
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(appointment_date, '%d %b') as day,
               appointment_date,
               COUNT(*) as count
        FROM appointments
        WHERE hospital_id = ? AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY appointment_date
        ORDER BY appointment_date ASC
    ");
    $stmt->execute([$hospital_id]);
    $daily_raw    = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $daily_labels = array_column($daily_raw, 'day');
    $daily_data   = array_column($daily_raw, 'count');

    // Unique patients
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) as cnt FROM appointments WHERE hospital_id = ?");
    $stmt->execute([$hospital_id]);
    $unique_patients = (int)$stmt->fetchColumn();

    // Today's appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE hospital_id = ? AND appointment_date = CURDATE()");
    $stmt->execute([$hospital_id]);
    $today_count = (int)$stmt->fetchColumn();

    // All appointments with patient info
    $stmt = $pdo->prepare("
        SELECT a.*, p.name as patient_name, p.phone, p.email
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        WHERE a.hospital_id = ?
        ORDER BY a.appointment_date DESC
        LIMIT 50
    ");
    $stmt->execute([$hospital_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// JSON for charts
$donut_data   = json_encode([$stats['pending'], $stats['approved'], $stats['completed'], $stats['rejected']]);
$monthly_json = json_encode(array_values($monthly_data));
$monthly_lbl  = json_encode(array_values($monthly_labels));
$daily_json   = json_encode(array_values($daily_data));
$daily_lbl    = json_encode(array_values($daily_labels));
$type_json    = json_encode([$type_stats['test'], $type_stats['vaccination']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hospital Dashboard | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { background: var(--background); color: var(--text-light); min-height: 100vh; }

        /* Action Buttons */
        .btn-schedule { background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .btn-schedule:hover { background: #3b82f6; color: #fff; }
        .btn-complete { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .btn-complete:hover { background: #10b981; color: #fff; }
        .btn-reject { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.25); padding: 5px 10px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .btn-reject:hover { background: #ef4444; color: #fff; }

        /* Action Mini Modal */
        .action-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); backdrop-filter:blur(8px); z-index:2000; align-items:center; justify-content:center; padding:20px; }
        .action-overlay.open { display:flex; }
        .action-box { background:#141414; border:1px solid rgba(255,255,255,0.12); border-radius:18px; width:100%; max-width:440px; padding:28px; box-shadow:0 24px 60px rgba(0,0,0,0.7); animation:modalIn 0.2s ease; }
        .action-box h3 { font-size:1.05rem; font-weight:800; margin-bottom:6px; display:flex; align-items:center; gap:10px; }
        .action-box p { color:rgba(255,255,255,0.55); font-size:0.85rem; margin-bottom:18px; }
        .action-box textarea { width:100%; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff; padding:12px; font-size:0.88rem; resize:vertical; min-height:80px; outline:none; margin-bottom:16px; font-family:inherit; }
        .action-box textarea:focus { border-color:var(--primary); }
        .action-btn-row { display:flex; gap:10px; justify-content:flex-end; }
        .action-btn-row .cancel-btn { background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:rgba(255,255,255,0.7); padding:9px 18px; border-radius:10px; cursor:pointer; font-size:0.88rem; transition:background 0.2s; }
        .action-btn-row .cancel-btn:hover { background:rgba(255,255,255,0.1); }
        .action-btn-row .confirm-btn { padding:9px 20px; border-radius:10px; font-size:0.88rem; font-weight:700; cursor:pointer; border:none; transition:all 0.2s; }

        /* Flash message */
        .flash-msg { position:fixed; top:20px; right:20px; z-index:9999; padding:14px 20px; border-radius:12px; font-size:0.88rem; font-weight:600; display:flex; align-items:center; gap:10px; box-shadow:0 8px 24px rgba(0,0,0,0.4); animation:slideIn 0.3s ease; }
        .flash-success { background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.4); color:#10b981; }
        .flash-error   { background:rgba(239,68,68,0.15);  border:1px solid rgba(239,68,68,0.4);  color:#ef4444; }
        @keyframes slideIn { from{opacity:0;transform:translateX(30px)} to{opacity:1;transform:translateX(0)} }

        /* Header */
        header { background: rgba(0,0,0,0.6); backdrop-filter: blur(12px); padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.06); position: sticky; top: 0; z-index: 100; }
        .header-inner { display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.15rem; }
        .logo img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .nav-links { display: flex; align-items: center; gap: 18px; }
        .nav-links a { color: rgba(255,255,255,0.75); text-decoration: none; font-weight: 600; font-size: 0.88rem; transition: color 0.2s; }
        .nav-links a:hover { color: var(--primary); }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(59,130,246,0.08));
            border: 1px solid rgba(16,185,129,0.2);
            border-radius: 20px;
            padding: 22px 28px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .welcome-text h1 { font-size: 1.5rem; font-weight: 800; margin-bottom: 4px; }
        .welcome-text p { color: var(--text-muted); font-size: 0.9rem; }
        .welcome-badge { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: var(--primary); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
        @media(max-width:700px)  { .stats-grid { grid-template-columns: repeat(2, 1fr); } }

        .stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 18px;
            padding: 20px 18px;
            position: relative;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
            cursor: pointer;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.3); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 18px 18px 0 0;
        }
        .stat-card.c-primary::before { background: var(--primary); }
        .stat-card.c-warning::before { background: #f59e0b; }
        .stat-card.c-blue::before   { background: #3b82f6; }
        .stat-card.c-green::before  { background: #10b981; }
        .stat-card.c-red::before    { background: #ef4444; }
        .stat-card.c-purple::before { background: #8b5cf6; }

        .stat-icon-wrap { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: 12px; }
        .stat-value { font-size: 2rem; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .stat-label { color: #ffffff; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }
        .stat-trend { font-size: 0.72rem; margin-top: 6px; color: rgba(255,255,255,0.75); }

        /* Charts Row */
        .charts-row { display: grid; grid-template-columns: 1fr 1.8fr 1fr; gap: 20px; margin-bottom: 28px; }
        @media(max-width:1100px) { .charts-row { grid-template-columns: 1fr 1fr; } }
        @media(max-width:700px)  { .charts-row { grid-template-columns: 1fr; } }

        .chart-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 20px;
            padding: 20px;
        }
        .chart-title { font-size: 0.9rem; font-weight: 700; margin-bottom: 16px; color: #ffffff; display: flex; align-items: center; gap: 8px; }
        .chart-title i { color: var(--primary); }
        .chart-wrap { position: relative; }

        /* Second charts row */
        .charts-row2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 28px; }
        @media(max-width:900px) { .charts-row2 { grid-template-columns: 1fr; } }

        /* Table */
        .table-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 20px; padding: 20px; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
        .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-tab { padding: 6px 14px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); background: none; color: rgba(255,255,255,0.6); font-size: 0.8rem; cursor: pointer; transition: all 0.2s; }
        .filter-tab.active, .filter-tab:hover { background: var(--primary); color: #000; border-color: var(--primary); font-weight: 600; }

        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { padding: 10px 14px; text-align: left; color: var(--text-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .modern-table td { padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.88rem; }
        .modern-table tr:hover td { background: rgba(255,255,255,0.02); }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending   { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .badge-approved  { background: rgba(59,130,246,0.15); color: #3b82f6; }
        .badge-completed { background: rgba(16,185,129,0.15); color: #10b981; }
        .badge-rejected  { background: rgba(239,68,68,0.15);  color: #ef4444; }
        .badge-cancelled { background: rgba(107,114,128,0.15); color: #9ca3af; }

        .dropdown { position: relative; display: inline-block; }
        .dropdown-trigger { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; cursor: pointer; padding: 5px 10px; border-radius: 8px; transition: background 0.2s; }
        .dropdown-trigger:hover { background: rgba(255,255,255,0.1); }
        .dropdown-menu { display: none; position: absolute; right: 0; background: #1e1e1e; min-width: 150px; border: 1px solid #333; border-radius: 10px; z-index: 100; box-shadow: 0 8px 24px rgba(0,0,0,0.4); overflow: hidden; }
        .dropdown-menu.show { display: block; }
        .dropdown-item { display: block; padding: 10px 14px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 0.85rem; transition: background 0.15s; }
        .dropdown-item:hover { background: rgba(255,255,255,0.06); color: var(--primary); }

        /* Legend */
        .legend { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 12px; }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.78rem; color: #ffffff; }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; }

        /* Activity feed */
        .activity-feed { display: flex; flex-direction: column; gap: 10px; }
        .activity-item { display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: 12px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); }
        .activity-dot { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0; }
        .activity-info { flex: 1; }
        .activity-info strong { font-size: 0.85rem; display: block; color: #ffffff; }
        .activity-info span { font-size: 0.75rem; color: rgba(255,255,255,0.7); }
        .activity-time { font-size: 0.72rem; color: rgba(255,255,255,0.7); white-space: nowrap; }

        /* ── Modals ─────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(6px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #141414;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            width: 100%;
            max-width: 820px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 60px rgba(0,0,0,0.6);
            animation: modalIn 0.25s ease;
        }
        @keyframes modalIn {
            from { opacity:0; transform: scale(0.95) translateY(10px); }
            to   { opacity:1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            flex-shrink: 0;
        }
        .modal-header h3 { font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .modal-close {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.05);
            color: white;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .modal-close:hover { background: rgba(239,68,68,0.2); color: #ef4444; }
        .modal-body { overflow-y: auto; padding: 18px 22px; flex: 1; }
        .modal-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }
        .modal-stat {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
        }
        .modal-stat-val { font-size: 1.6rem; font-weight: 800; }
        .modal-stat-lbl { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-top: 2px; }
        .modal-table { width: 100%; border-collapse: collapse; }
        .modal-table th { padding: 9px 12px; text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.7px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .modal-table td { padding: 11px 12px; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.85rem; }
        .modal-table tr:last-child td { border-bottom: none; }
        .modal-table tr:hover td { background: rgba(255,255,255,0.02); }
        .modal-empty { text-align: center; padding: 40px; color: var(--text-muted); }
        .modal-empty i { font-size: 2.5rem; margin-bottom: 10px; display: block; }
    </style>
</head>
<body>
<?php
$flash_msg = '';
$flash_type = 'success';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'scheduled') { $flash_msg = '<i class="fa-solid fa-calendar-check"></i> Appointment scheduled! Patient notified by email.'; }
    elseif ($_GET['msg'] === 'completed') { $flash_msg = '<i class="fa-solid fa-check-double"></i> Appointment completed! PDF report emailed to patient.'; }
    elseif ($_GET['msg'] === 'rejected') { $flash_msg = '<i class="fa-solid fa-xmark"></i> Appointment rejected. Patient notified.'; $flash_type = 'error'; }
}
?>
<?php if ($flash_msg): ?>
<div class="flash-msg flash-<?php echo $flash_type; ?>" id="flashMsg">
    <?php echo $flash_msg; ?>
</div>
<script>setTimeout(() => { const f = document.getElementById('flashMsg'); if(f) f.style.display='none'; }, 4000);</script>
<?php endif; ?>

    <header>
        <div class="container header-inner">
            <div class="logo">
                <?php if (!empty($hospital['image'])): ?>
                    <img src="<?php echo htmlspecialchars($hospital['image']); ?>" alt="Logo">
                <?php else: ?>
                    <div style="width:38px;height:38px;border-radius:50%;background:rgba(16,185,129,0.15);display:flex;align-items:center;justify-content:center;border:2px solid var(--primary);">
                        <i class="fa-solid fa-hospital" style="color:var(--primary);font-size:1rem;"></i>
                    </div>
                <?php endif; ?>
                <?php echo htmlspecialchars($hospital['name']); ?>
            </div>
            <div class="nav-links">
                <a href="manage_vaccines.php"><i class="fa-solid fa-vial"></i> Vaccines</a>
                <a href="edit_hospital_profile.php"><i class="fa-solid fa-user-gear"></i> Profile</a>
                <a href="logout.php" class="btn btn-primary" style="padding:7px 16px;font-size:0.85rem;color:#000000;">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container" style="padding-top: 30px; padding-bottom: 40px;">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h1>Welcome back, <span style="color:var(--primary);"><?php echo htmlspecialchars($hospital['name']); ?></span> 👋</h1>
                <p>Here's what's happening at your hospital today — <?php echo date('l, d F Y'); ?></p>
            </div>
            <div>
                <span class="welcome-badge"><i class="fa-solid fa-circle" style="font-size:0.5rem;vertical-align:middle;margin-right:4px;"></i> Live Dashboard</span>
            </div>
        </div>

        <!-- Stats Grid (6 cards) -->
        <div class="stats-grid">
            <div class="stat-card c-primary" onclick="openModal('modal-all')" style="cursor:pointer;">
                <div class="stat-icon-wrap" style="background:rgba(16,185,129,0.12);">
                    <i class="fa-solid fa-calendar-check" style="color:#10b981;"></i>
                </div>
                <div class="stat-value" style="color:#10b981;"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Appointments</div>
                <div class="stat-trend"><i class="fa-solid fa-arrow-trend-up"></i> All time</div>
            </div>
            <div class="stat-card c-warning" onclick="openModal('modal-pending')" style="cursor:pointer;">
                <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.12);">
                    <i class="fa-solid fa-clock" style="color:#f59e0b;"></i>
                </div>
                <div class="stat-value" style="color:#f59e0b;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
                <div class="stat-trend"><i class="fa-solid fa-hourglass-half"></i> Awaiting action</div>
            </div>
            <div class="stat-card c-blue" onclick="openModal('modal-approved')" style="cursor:pointer;">
                <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.12);">
                    <i class="fa-solid fa-clipboard-list" style="color:#3b82f6;"></i>
                </div>
                <div class="stat-value" style="color:#3b82f6;"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Scheduled</div>
                <div class="stat-trend"><i class="fa-solid fa-calendar"></i> Upcoming</div>
            </div>
            <div class="stat-card c-green" onclick="openModal('modal-completed')" style="cursor:pointer;">
                <div class="stat-icon-wrap" style="background:rgba(16,185,129,0.12);">
                    <i class="fa-solid fa-check-double" style="color:#10b981;"></i>
                </div>
                <div class="stat-value" style="color:#10b981;"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
                <div class="stat-trend"><i class="fa-solid fa-circle-check"></i> Done</div>
            </div>
            <div class="stat-card c-purple" onclick="openModal('modal-patients')" style="cursor:pointer;">
                <div class="stat-icon-wrap" style="background:rgba(139,92,246,0.12);">
                    <i class="fa-solid fa-users" style="color:#8b5cf6;"></i>
                </div>
                <div class="stat-value" style="color:#8b5cf6;"><?php echo $unique_patients; ?></div>
                <div class="stat-label">Unique Patients</div>
                <div class="stat-trend"><i class="fa-solid fa-user-plus"></i> Total served</div>
            </div>
            <div class="stat-card c-red" onclick="openModal('modal-today')" style="cursor:pointer;">
                <div class="stat-icon-wrap" style="background:rgba(239,68,68,0.12);">
                    <i class="fa-solid fa-calendar-day" style="color:#ef4444;"></i>
                </div>
                <div class="stat-value" style="color:#ef4444;"><?php echo $today_count; ?></div>
                <div class="stat-label">Today</div>
                <div class="stat-trend"><i class="fa-solid fa-sun"></i> <?php echo date('d M'); ?></div>
            </div>
        </div>

        <!-- Charts Row 1: Donut | Bar (Monthly) | Type Pie -->
        <div class="charts-row">
            <!-- Donut: Status Breakdown -->
            <div class="chart-card">
                <div class="chart-title"><i class="fa-solid fa-chart-pie"></i> Status Breakdown</div>
                <div class="chart-wrap" style="height:200px;">
                    <canvas id="donutChart"></canvas>
                </div>
                <div class="legend" style="justify-content:center; margin-top:14px;">
                    <div class="legend-item"><div class="legend-dot" style="background:#f59e0b;"></div>Pending</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#3b82f6;"></div>Approved</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#10b981;"></div>Completed</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#ef4444;"></div>Rejected</div>
                </div>
            </div>

            <!-- Bar: Monthly Trend -->
            <div class="chart-card">
                <div class="chart-title"><i class="fa-solid fa-chart-bar"></i> Monthly Appointment Trend</div>
                <div class="chart-wrap" style="height:220px;">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <!-- Doughnut: Test vs Vaccination -->
            <div class="chart-card">
                <div class="chart-title"><i class="fa-solid fa-syringe"></i> Appointment Types</div>
                <div class="chart-wrap" style="height:200px;">
                    <canvas id="typeChart"></canvas>
                </div>
                <div class="legend" style="justify-content:center; margin-top:14px;">
                    <div class="legend-item"><div class="legend-dot" style="background:#3b82f6;"></div>Tests</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#10b981;"></div>Vaccinations</div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Line (Daily) | Activity Feed -->
        <div class="charts-row2">
            <!-- Line: Daily Activity -->
            <div class="chart-card">
                <div class="chart-title"><i class="fa-solid fa-chart-line"></i> Daily Activity (Last 14 Days)</div>
                <div class="chart-wrap" style="height:220px;">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

            <!-- Recent Activity Feed -->
            <div class="chart-card">
                <div class="chart-title"><i class="fa-solid fa-bolt"></i> Recent Activity</div>
                <div class="activity-feed">
                    <?php
                    $recent = array_slice($appointments, 0, 5);
                    $colors = ['pending'=>'#f59e0b','approved'=>'#3b82f6','completed'=>'#10b981','rejected'=>'#ef4444','cancelled'=>'#9ca3af'];
                    $icons  = ['pending'=>'fa-clock','approved'=>'fa-check','completed'=>'fa-check-double','rejected'=>'fa-xmark','cancelled'=>'fa-ban'];
                    foreach ($recent as $a):
                        $c = $colors[$a['status']] ?? '#888';
                        $ic = $icons[$a['status']] ?? 'fa-circle';
                    ?>
                    <div class="activity-item">
                        <div class="activity-dot" style="background:<?php echo $c; ?>22; color:<?php echo $c; ?>;">
                            <i class="fa-solid <?php echo $ic; ?>"></i>
                        </div>
                        <div class="activity-info">
                            <strong><?php echo htmlspecialchars($a['patient_name']); ?></strong>
                            <span><?php echo strtoupper($a['type']); ?> · <?php echo ucfirst($a['status']); ?></span>
                        </div>
                        <div class="activity-time"><?php echo date('d M', strtotime($a['appointment_date'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recent)): ?>
                        <p style="color:var(--text-muted);text-align:center;font-size:0.85rem;padding:20px 0;">No recent activity</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>


    </div><!-- /container -->

    <script>
    // ── Chart defaults ──────────────────────────────────────────
    Chart.defaults.color = 'rgba(255,255,255,0.5)';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
    Chart.defaults.font.family = 'Inter, sans-serif';

    // ── Donut: Status ───────────────────────────────────────────
    new Chart(document.getElementById('donutChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending','Approved','Completed','Rejected'],
            datasets: [{
                data: <?php echo $donut_data; ?>,
                backgroundColor: ['#f59e0b','#3b82f6','#10b981','#ef4444'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: { legend: { display: false }, tooltip: { callbacks: {
                label: ctx => ` ${ctx.label}: ${ctx.raw}`
            }}}
        }
    });

    // ── Bar: Monthly Trend ──────────────────────────────────────
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: <?php echo $monthly_lbl; ?>,
            datasets: [{
                label: 'Appointments',
                data: <?php echo $monthly_json; ?>,
                backgroundColor: 'rgba(16,185,129,0.25)',
                borderColor: '#10b981',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.04)' } }
            }
        }
    });

    // ── Doughnut: Type ──────────────────────────────────────────
    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: ['Tests','Vaccinations'],
            datasets: [{
                data: <?php echo $type_json; ?>,
                backgroundColor: ['#3b82f6','#10b981'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: { legend: { display: false } }
        }
    });

    // ── Line: Daily Activity ────────────────────────────────────
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: <?php echo $daily_lbl; ?>,
            datasets: [{
                label: 'Appointments',
                data: <?php echo $daily_json; ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.08)',
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.04)' } }
            }
        }
    });

    // ── Table Filter ────────────────────────────────────────────
    function filterTable(status, btn) {
        document.querySelectorAll('.appt-row').forEach(row => {
            row.style.display = (status === 'all' || row.classList.contains('status-' + status)) ? '' : 'none';
        });
        if (btn) {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
        }
    }

    // ── Dropdown ────────────────────────────────────────────────
    function toggleDropdown(btn, e) {
        e.stopPropagation();
        document.querySelectorAll('.dropdown-menu').forEach(m => m !== btn.nextElementSibling && m.classList.remove('show'));
        btn.nextElementSibling.classList.toggle('show');
    }

    // ── Modals ───────────────────────────────────────────────────
    function openModal(id) {
        document.getElementById(id).classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
    }
    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });
    // Close on ESC
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
    });

    window.onclick = () => document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    </script>

<?php
// Helper: build modal table rows for a given filter
function modalRows($appointments, $filter = 'all') {
    $rows = array_filter($appointments, function($a) use ($filter) {
        if ($filter === 'all') return true;
        if ($filter === 'today') return $a['appointment_date'] === date('Y-m-d');
        if ($filter === 'patients') return true; // handled separately
        return $a['status'] === $filter;
    });
    return array_values($rows);
}

$colors = ['pending'=>'#f59e0b','approved'=>'#3b82f6','completed'=>'#10b981','rejected'=>'#ef4444','cancelled'=>'#9ca3af'];
$icons  = ['pending'=>'fa-clock','approved'=>'fa-check','completed'=>'fa-check-double','rejected'=>'fa-xmark','cancelled'=>'fa-ban'];

// Unique patients list
$unique_list = [];
$seen_ids = [];
foreach ($appointments as $a) {
    if (!in_array($a['patient_id'], $seen_ids)) {
        $seen_ids[] = $a['patient_id'];
        $unique_list[] = $a;
    }
}

$modals = [
    'modal-all'       => ['title'=>'All Appointments',    'icon'=>'fa-calendar-check', 'color'=>'#10b981', 'filter'=>'all'],
    'modal-pending'   => ['title'=>'Pending Appointments','icon'=>'fa-clock',           'color'=>'#f59e0b', 'filter'=>'pending'],
    'modal-approved'  => ['title'=>'Scheduled Appointments','icon'=>'fa-clipboard-list','color'=>'#3b82f6', 'filter'=>'approved'],
    'modal-completed' => ['title'=>'Completed Appointments','icon'=>'fa-check-double',  'color'=>'#10b981', 'filter'=>'completed'],
    'modal-today'     => ['title'=>"Today's Appointments", 'icon'=>'fa-calendar-day',   'color'=>'#ef4444', 'filter'=>'today'],
];
?>

<!-- ══ MODALS ══════════════════════════════════════════════════════ -->

<?php foreach ($modals as $modal_id => $m):
    $rows = modalRows($appointments, $m['filter']);
    $cnt  = count($rows);
?>
<div class="modal-overlay" id="<?php echo $modal_id; ?>">
    <div class="modal-box">
        <div class="modal-header">
            <h3>
                <span style="width:32px;height:32px;border-radius:10px;background:<?php echo $m['color']; ?>22;display:inline-flex;align-items:center;justify-content:center;">
                    <i class="fa-solid <?php echo $m['icon']; ?>" style="color:<?php echo $m['color']; ?>;font-size:0.9rem;"></i>
                </span>
                <?php echo $m['title']; ?>
                <span style="background:<?php echo $m['color']; ?>22;color:<?php echo $m['color']; ?>;padding:2px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;"><?php echo $cnt; ?></span>
            </h3>
            <button class="modal-close" onclick="closeModal('<?php echo $modal_id; ?>')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <!-- Mini stats inside modal -->
            <div class="modal-stats-row">
                <?php
                $m_total     = count($rows);
                $m_test      = count(array_filter($rows, fn($a) => $a['type'] === 'test'));
                $m_vacc      = count(array_filter($rows, fn($a) => $a['type'] === 'vaccination'));
                $m_completed = count(array_filter($rows, fn($a) => $a['status'] === 'completed'));
                ?>
                <div class="modal-stat">
                    <div class="modal-stat-val" style="color:<?php echo $m['color']; ?>"><?php echo $m_total; ?></div>
                    <div class="modal-stat-lbl">Total</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-val" style="color:#3b82f6;"><?php echo $m_test; ?></div>
                    <div class="modal-stat-lbl">Tests</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-val" style="color:#10b981;"><?php echo $m_vacc; ?></div>
                    <div class="modal-stat-lbl">Vaccinations</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-val" style="color:#10b981;"><?php echo $m_completed; ?></div>
                    <div class="modal-stat-lbl">Completed</div>
                </div>
            </div>

            <?php if (empty($rows)): ?>
                <div class="modal-empty">
                    <i class="fa-solid fa-calendar-xmark" style="color:var(--text-muted);"></i>
                    No appointments found
                </div>
            <?php else: ?>
            <table class="modal-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Type</th>
                        <th>Date &amp; Time</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $i => $a):
                        $sc = $colors[$a['status']] ?? '#888';
                        $si = $icons[$a['status']] ?? 'fa-circle';
                    ?>
                    <tr>
                        <td style="color:var(--text-muted);font-size:0.78rem;"><?php echo $i+1; ?></td>
                        <td>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($a['patient_name']); ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted);"><?php echo htmlspecialchars($a['email'] ?? ''); ?></div>
                        </td>
                        <td>
                            <?php if ($a['type'] === 'vaccination'): ?>
                                <span style="color:#10b981;font-size:0.82rem;"><i class="fa-solid fa-syringe"></i> Vaccination</span>
                            <?php else: ?>
                                <span style="color:#3b82f6;font-size:0.82rem;"><i class="fa-solid fa-flask"></i> Test</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size:0.85rem;"><?php echo date('d M Y', strtotime($a['appointment_date'])); ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted);"><?php echo substr($a['appointment_time'],0,5); ?></div>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $a['status']; ?>">
                                <i class="fa-solid <?php echo $si; ?>"></i> <?php echo ucfirst($a['status']); ?>
                            </span>
                        </td>
                        <td style="color:var(--text-muted);font-size:0.82rem;"><?php echo $a['result'] ? htmlspecialchars($a['result']) : '—'; ?></td>
                        <td>
                            <?php if ($a['status'] === 'pending'): ?>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <button class="btn-schedule" onclick="openActionModal('schedule', <?php echo $a['id']; ?>, '<?php echo addslashes($a['patient_name']); ?>', '<?php echo $a['type']; ?>')">
                                        <i class="fa-solid fa-calendar-check"></i> Schedule
                                    </button>
                                    <button class="btn-reject" onclick="openActionModal('reject', <?php echo $a['id']; ?>, '<?php echo addslashes($a['patient_name']); ?>', '<?php echo $a['type']; ?>')">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            <?php elseif ($a['status'] === 'approved'): ?>
                                <button class="btn-complete" onclick="openActionModal('complete', <?php echo $a['id']; ?>, '<?php echo addslashes($a['patient_name']); ?>', '<?php echo $a['type']; ?>')">
                                    <i class="fa-solid fa-check-double"></i> Complete
                                </button>
                            <?php else: ?>
                                <span style="color:rgba(255,255,255,0.25);font-size:0.78rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal: Unique Patients -->
<div class="modal-overlay" id="modal-patients">
    <div class="modal-box">
        <div class="modal-header">
            <h3>
                <span style="width:32px;height:32px;border-radius:10px;background:#8b5cf622;display:inline-flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-users" style="color:#8b5cf6;font-size:0.9rem;"></i>
                </span>
                Unique Patients
                <span style="background:#8b5cf622;color:#8b5cf6;padding:2px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;"><?php echo count($unique_list); ?></span>
            </h3>
            <button class="modal-close" onclick="closeModal('modal-patients')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-stats-row" style="grid-template-columns:repeat(3,1fr);">
                <div class="modal-stat">
                    <div class="modal-stat-val" style="color:#8b5cf6;"><?php echo count($unique_list); ?></div>
                    <div class="modal-stat-lbl">Total Patients</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-val" style="color:#10b981;"><?php echo $stats['total']; ?></div>
                    <div class="modal-stat-lbl">Total Visits</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-val" style="color:#3b82f6;"><?php echo count($unique_list) > 0 ? round($stats['total'] / count($unique_list), 1) : 0; ?></div>
                    <div class="modal-stat-lbl">Avg Visits/Patient</div>
                </div>
            </div>
            <?php if (empty($unique_list)): ?>
                <div class="modal-empty">
                    <i class="fa-solid fa-users" style="color:var(--text-muted);"></i>
                    No patients yet
                </div>
            <?php else: ?>
            <table class="modal-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Last Visit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unique_list as $i => $a): ?>
                    <tr>
                        <td style="color:var(--text-muted);font-size:0.78rem;"><?php echo $i+1; ?></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($a['patient_name']); ?></td>
                        <td style="color:var(--text-muted);font-size:0.82rem;"><?php echo htmlspecialchars($a['email'] ?? '—'); ?></td>
                        <td style="color:var(--text-muted);font-size:0.82rem;"><?php echo htmlspecialchars($a['phone'] ?? '—'); ?></td>
                        <td style="font-size:0.82rem;"><?php echo date('d M Y', strtotime($a['appointment_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ ACTION MINI-MODAL ══════════════════════════════════════════ -->
<style>
/* Result chips */
.result-chips { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
.result-chip { padding:6px 14px; border-radius:20px; font-size:0.78rem; font-weight:700; cursor:pointer; border:1.5px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.6); transition:all 0.18s; user-select:none; }
.result-chip:hover { border-color:rgba(255,255,255,0.3); color:#fff; background:rgba(255,255,255,0.1); }
.result-chip.active { border-color:var(--chip-color,#10b981); background:rgba(16,185,129,0.15); color:var(--chip-color,#10b981); }
.result-label { font-size:0.75rem; text-transform:uppercase; letter-spacing:0.8px; color:rgba(255,255,255,0.4); margin-bottom:8px; font-weight:700; }
.result-required { color:#ef4444; margin-left:3px; }
.char-count { font-size:0.72rem; color:rgba(255,255,255,0.3); text-align:right; margin-top:-10px; margin-bottom:14px; }
.result-section { display:none; }
.result-section.visible { display:block; }
</style>

<div class="action-overlay" id="actionOverlay">
    <div class="action-box" style="max-width:500px;">
        <h3 id="actionTitle"><i class="fa-solid fa-circle-question"></i> Confirm Action</h3>
        <p id="actionDesc" style="margin-bottom:20px;">Are you sure?</p>
        <form method="POST" action="hospital_action.php" id="actionForm" onsubmit="return validateActionForm()">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" id="actionType">
            <input type="hidden" name="appt_id" id="actionApptId">

            <!-- RESULT SECTION (only for complete) -->
            <div class="result-section" id="resultSection">
                <div class="result-label">Quick Select Result <span class="result-required">*</span></div>
                <div class="result-chips" id="resultChips"></div>

                <div class="result-label">Detailed Result / Notes <span class="result-required">*</span></div>
                <textarea name="result_note" id="actionNote"
                    placeholder="Describe the result in detail..."
                    oninput="updateCharCount(this)"
                    style="min-height:90px;"></textarea>
                <div class="char-count" id="charCount">0 characters</div>
            </div>

            <!-- NOTE SECTION (for schedule / reject) -->
            <div class="result-section" id="noteSection">
                <div class="result-label">Note <span style="color:rgba(255,255,255,0.3);font-weight:400;">(optional)</span></div>
                <textarea name="note_only" id="actionNoteOnly"
                    placeholder="Add a note..."
                    style="min-height:70px;"></textarea>
            </div>

            <div class="action-btn-row">
                <button type="button" class="cancel-btn" onclick="closeActionModal()">Cancel</button>
                <button type="submit" class="confirm-btn" id="actionConfirmBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
const CHIPS = {
    vaccination: [
        { label: '✅ Vaccinated',      color: '#10b981' },
        { label: '⚠️ Partial Dose',    color: '#f59e0b' },
        { label: '❌ Not Administered', color: '#ef4444' },
        { label: '🔄 Rescheduled',     color: '#3b82f6' },
    ],
    test: [
        { label: '🟢 Negative',        color: '#10b981' },
        { label: '🔴 Positive',        color: '#ef4444' },
        { label: '🟡 Inconclusive',    color: '#f59e0b' },
        { label: '🔄 Retest Required', color: '#3b82f6' },
    ]
};

function openActionModal(action, apptId, patientName, apptType) {
    // Reset
    document.getElementById('actionNote').value = '';
    document.getElementById('actionNoteOnly').value = '';
    document.getElementById('charCount').textContent = '0 characters';
    document.querySelectorAll('.result-chip').forEach(c => c.classList.remove('active'));

    document.getElementById('actionType').value = action;
    document.getElementById('actionApptId').value = apptId;

    const resultSection = document.getElementById('resultSection');
    const noteSection   = document.getElementById('noteSection');
    const btn = document.getElementById('actionConfirmBtn');

    if (action === 'complete') {
        document.getElementById('actionTitle').innerHTML = '<i class="fa-solid fa-check-double" style="color:#10b981"></i> Complete Appointment';
        document.getElementById('actionDesc').innerHTML  = '<strong style="color:rgba(255,255,255,0.8);">' + patientName + '</strong> — Enter the result below.';
        btn.textContent = '✅ Complete';
        btn.style.cssText = 'background:#10b981;color:#fff;';

        // Build chips
        const chipKey = (apptType === 'vaccination') ? 'vaccination' : 'test';
        const chipsEl = document.getElementById('resultChips');
        chipsEl.innerHTML = '';
        CHIPS[chipKey].forEach(chip => {
            const el = document.createElement('span');
            el.className = 'result-chip';
            el.textContent = chip.label;
            el.style.setProperty('--chip-color', chip.color);
            el.onclick = function() {
                document.querySelectorAll('.result-chip').forEach(c => c.classList.remove('active'));
                el.classList.add('active');
                const ta = document.getElementById('actionNote');
                ta.value = chip.label.replace(/^[^a-zA-Z]+/, '').trim(); // strip emoji prefix
                updateCharCount(ta);
            };
            chipsEl.appendChild(el);
        });

        resultSection.classList.add('visible');
        noteSection.classList.remove('visible');

    } else if (action === 'schedule') {
        document.getElementById('actionTitle').innerHTML = '<i class="fa-solid fa-calendar-check" style="color:#3b82f6"></i> Schedule Appointment';
        document.getElementById('actionDesc').innerHTML  = '<strong style="color:rgba(255,255,255,0.8);">' + patientName + '</strong> — Confirm this appointment. The patient will be notified by email.';
        document.getElementById('actionNoteOnly').placeholder = 'Optional note for patient (e.g. bring ID, fasting required)...';
        btn.textContent = '📅 Confirm Schedule';
        btn.style.cssText = 'background:#3b82f6;color:#fff;';
        resultSection.classList.remove('visible');
        noteSection.classList.add('visible');

    } else if (action === 'reject') {
        document.getElementById('actionTitle').innerHTML = '<i class="fa-solid fa-xmark" style="color:#ef4444"></i> Reject Appointment';
        document.getElementById('actionDesc').innerHTML  = '<strong style="color:rgba(255,255,255,0.8);">' + patientName + '</strong> — This request will be rejected and the patient will be notified.';
        document.getElementById('actionNoteOnly').placeholder = 'Reason for rejection (optional)...';
        btn.textContent = '❌ Reject';
        btn.style.cssText = 'background:#ef4444;color:#fff;';
        resultSection.classList.remove('visible');
        noteSection.classList.add('visible');
    }

    document.getElementById('actionOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function updateCharCount(el) {
    document.getElementById('charCount').textContent = el.value.length + ' characters';
}

function validateActionForm() {
    const action = document.getElementById('actionType').value;
    if (action === 'complete') {
        const result = document.getElementById('actionNote').value.trim();
        if (!result) {
            document.getElementById('actionNote').style.borderColor = '#ef4444';
            document.getElementById('actionNote').focus();
            document.getElementById('actionNote').placeholder = '⚠️ Result is required before completing!';
            return false;
        }
        document.getElementById('actionNote').style.borderColor = '';
    }
    // Copy note_only value into result_note for schedule/reject
    if (action === 'schedule' || action === 'reject') {
        document.getElementById('actionNote').value = document.getElementById('actionNoteOnly').value;
    }
    return true;
}

function closeActionModal() {
    document.getElementById('actionOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

document.getElementById('actionOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeActionModal();
});
</script>

</body>
</html>

