<?php
/**
 * Patient Dashboard (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header('Location: login.php');
    exit();
}

$patient_id = $_SESSION['user_id'];

// Fetch data
try {
    // Stats
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0];
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM appointments WHERE patient_id = ? GROUP BY status");
    $stmt->execute([$patient_id]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($res as $r) {
        $stats[$r['status']] = $r['count'];
        $stats['total'] += $r['count'];
    }

    // Appointments
    $stmt = $pdo->prepare("SELECT a.*, h.name as hospital_name, h.location 
                          FROM appointments a 
                          JOIN users h ON a.hospital_id = h.id 
                          WHERE a.patient_id = ? 
                          ORDER BY a.appointment_date DESC");
    $stmt->execute([$patient_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // All Vaccines — available count ke saath
    $stmt = $pdo->query("
        SELECT v.*,
               COUNT(DISTINCT CASE WHEN hv.status = 'available' AND h.status = 'approved' THEN hv.hospital_id END) as hospital_count
        FROM vaccines v
        LEFT JOIN hospital_vaccines hv ON v.id = hv.vaccine_id
        LEFT JOIN users h ON hv.hospital_id = h.id AND h.role = 'hospital'
        GROUP BY v.id
        ORDER BY v.name ASC
    ");
    $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Partner Hospitals
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'hospital' AND status = 'approved' ORDER BY name ASC");
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .stat-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px; padding: 24px; }
        .searchable-card { transition: all 0.3s ease; background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.05); }
        .searchable-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5); }
        header { background: rgba(0,0,0,0.5); padding: 15px 0; }

        /* Modals */
        .p-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.72); backdrop-filter:blur(6px); z-index:1000; align-items:center; justify-content:center; padding:20px; }
        .p-modal-overlay.open { display:flex; }
        .p-modal-box { background:#141414; border:1px solid rgba(255,255,255,0.1); border-radius:20px; width:100%; max-width:780px; max-height:85vh; display:flex; flex-direction:column; box-shadow:0 24px 60px rgba(0,0,0,0.6); animation:pmIn 0.25s ease; }
        @keyframes pmIn { from{opacity:0;transform:scale(0.95) translateY(12px)} to{opacity:1;transform:scale(1) translateY(0)} }
        .p-modal-header { display:flex; align-items:center; justify-content:space-between; padding:16px 22px; border-bottom:1px solid rgba(255,255,255,0.07); flex-shrink:0; }
        .p-modal-header h3 { font-size:1rem; font-weight:700; display:flex; align-items:center; gap:10px; color:#fff; }
        .p-modal-close { width:32px; height:32px; border-radius:50%; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:white; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:0.9rem; transition:background 0.2s; }
        .p-modal-close:hover { background:rgba(239,68,68,0.2); color:#ef4444; }
        .p-modal-body { overflow-y:auto; padding:18px 22px; flex:1; }
        .p-mini-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:18px; }
        .p-mini-stat { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:14px; text-align:center; }
        .p-mini-val { font-size:1.6rem; font-weight:800; }
        .p-mini-lbl { font-size:0.72rem; color:rgba(255,255,255,0.55); text-transform:uppercase; letter-spacing:0.6px; margin-top:2px; }
        .p-modal-table { width:100%; border-collapse:collapse; }
        .p-modal-table th { padding:9px 12px; text-align:left; color:rgba(255,255,255,0.45); font-size:0.74rem; text-transform:uppercase; letter-spacing:0.7px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .p-modal-table td { padding:11px 12px; border-bottom:1px solid rgba(255,255,255,0.04); font-size:0.85rem; color:#fff; }
        .p-modal-table tr:last-child td { border-bottom:none; }
        .p-modal-table tr:hover td { background:rgba(255,255,255,0.02); }
        .p-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:0.74rem; font-weight:600; }
        .p-empty { text-align:center; padding:40px; color:rgba(255,255,255,0.4); }
        .p-empty i { font-size:2.5rem; margin-bottom:10px; display:block; }
    </style>
</head>
<body style="background: var(--background); color: var(--text-light);">
    <header>
        <div class="container" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="logo" style="font-weight:700; color:var(--primary);">Patient Portal</div>
            <div class="nav-links" style="display:flex; align-items:center; gap:15px;">
                <span style="color:var(--text-muted);">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="logout.php" class="btn btn-outline" style="padding: 8px 16px;">Logout</a>
            </div>
        </div>
    </header>

    <div class="container" style="padding-top: 40px; padding-bottom: 60px;">
        <!-- Advanced Stats Cards -->
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:36px;">

            <!-- Total Appointments -->
            <div onclick="openPModal('pm-all')" style="
                background: rgba(255,255,255,0.03);
                border: 1px solid rgba(255,255,255,0.07);
                border-radius: 20px;
                padding: 22px 20px;
                position: relative;
                overflow: hidden;
                cursor: pointer;
                transition: transform 0.25s, box-shadow 0.25s;
                border-top: 3px solid #10b981;
            " onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px rgba(0,0,0,0.35)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(16,185,129,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                    <i class="fa-solid fa-calendar-check" style="color:#10b981;font-size:1.1rem;"></i>
                </div>
                <div style="font-size:2.2rem;font-weight:800;color:#10b981;line-height:1;margin-bottom:5px;"><?php echo $stats['total']; ?></div>
                <div style="color:#ffffff;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;margin-bottom:6px;">Total Appointments</div>
                <div style="color:rgba(255,255,255,0.65);font-size:0.72rem;"><i class="fa-solid fa-arrow-trend-up"></i> All time</div>
                <i class="fa-solid fa-calendar-check" style="position:absolute;right:16px;bottom:14px;font-size:3rem;color:rgba(16,185,129,0.06);"></i>
            </div>

            <!-- Pending -->
            <div onclick="openPModal('pm-pending')" style="
                background: rgba(255,255,255,0.03);
                border: 1px solid rgba(255,255,255,0.07);
                border-radius: 20px;
                padding: 22px 20px;
                position: relative;
                overflow: hidden;
                cursor: pointer;
                transition: transform 0.25s, box-shadow 0.25s;
                border-top: 3px solid #f59e0b;
            " onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px rgba(0,0,0,0.35)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                    <i class="fa-solid fa-clock" style="color:#f59e0b;font-size:1.1rem;"></i>
                </div>
                <div style="font-size:2.2rem;font-weight:800;color:#f59e0b;line-height:1;margin-bottom:5px;"><?php echo $stats['pending']; ?></div>
                <div style="color:#ffffff;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;margin-bottom:6px;">Pending</div>
                <div style="color:rgba(255,255,255,0.65);font-size:0.72rem;"><i class="fa-solid fa-hourglass-half"></i> Awaiting confirmation</div>
                <i class="fa-solid fa-clock" style="position:absolute;right:16px;bottom:14px;font-size:3rem;color:rgba(245,158,11,0.06);"></i>
            </div>

            <!-- Confirmed -->
            <div onclick="openPModal('pm-approved')" style="
                background: rgba(255,255,255,0.03);
                border: 1px solid rgba(255,255,255,0.07);
                border-radius: 20px;
                padding: 22px 20px;
                position: relative;
                overflow: hidden;
                cursor: pointer;
                transition: transform 0.25s, box-shadow 0.25s;
                border-top: 3px solid #3b82f6;
            " onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px rgba(0,0,0,0.35)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                    <i class="fa-solid fa-check-circle" style="color:#3b82f6;font-size:1.1rem;"></i>
                </div>
                <div style="font-size:2.2rem;font-weight:800;color:#3b82f6;line-height:1;margin-bottom:5px;"><?php echo $stats['approved']; ?></div>
                <div style="color:#ffffff;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;margin-bottom:6px;">Confirmed</div>
                <div style="color:rgba(255,255,255,0.65);font-size:0.72rem;"><i class="fa-solid fa-calendar"></i> Upcoming visits</div>
                <i class="fa-solid fa-check-circle" style="position:absolute;right:16px;bottom:14px;font-size:3rem;color:rgba(59,130,246,0.06);"></i>
            </div>

            <!-- Completed -->
            <div onclick="openPModal('pm-completed')" style="
                background: rgba(255,255,255,0.03);
                border: 1px solid rgba(255,255,255,0.07);
                border-radius: 20px;
                padding: 22px 20px;
                position: relative;
                overflow: hidden;
                cursor: pointer;
                transition: transform 0.25s, box-shadow 0.25s;
                border-top: 3px solid #8b5cf6;
            " onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px rgba(0,0,0,0.35)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,92,246,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                    <i class="fa-solid fa-flag-checkered" style="color:#8b5cf6;font-size:1.1rem;"></i>
                </div>
                <div style="font-size:2.2rem;font-weight:800;color:#8b5cf6;line-height:1;margin-bottom:5px;"><?php echo $stats['completed']; ?></div>
                <div style="color:#ffffff;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;margin-bottom:6px;">Completed</div>
                <div style="color:rgba(255,255,255,0.65);font-size:0.72rem;"><i class="fa-solid fa-circle-check"></i> Successfully done</div>
                <i class="fa-solid fa-flag-checkered" style="position:absolute;right:16px;bottom:14px;font-size:3rem;color:rgba(139,92,246,0.06);"></i>
            </div>

        </div>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Quick Actions</h2>
            <a href="book_appointment.php" class="btn btn-primary"><i class="fa-solid fa-plus mr-2"></i> Book New Appointment</a>
        </div>

        <div class="mb-8">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-muted"></i>
                <input type="text" id="dashboardSearch" placeholder="Search for vaccines or hospitals..." class="w-full pl-12 pr-4 py-3 rounded-full bg-slate-800/50 border border-slate-700 outline-none focus:border-primary text-white">
            </div>
        </div>

        <h2 style="font-size:1.5rem;font-weight:800;margin-bottom:24px;color:#fff;display:flex;align-items:center;gap:12px;">
            <span style="width:8px;height:24px;background:var(--primary);border-radius:4px;display:inline-block;"></span>
            Available Vaccines
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <?php foreach($vaccines as $v): ?>
                <div class='searchable-card' style="
                    background: rgba(255,255,255,0.03);
                    border: 1px solid rgba(255,255,255,0.08);
                    border-radius: 24px;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                " onmouseover="this.style.transform='translateY(-8px)';this.style.boxShadow='0 20px 40px -10px rgba(0,0,0,0.5)';"
                  onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';">
                    
                    <div style="height:180px;background:#1e293b;position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                        <div style="position:absolute;top:0;left:0;right:0;height:100%;background:radial-gradient(circle at center, rgba(16,185,129,0.15) 0%, transparent 70%);"></div>
                        <img src='<?php echo !empty($v['image']) ? $v['image'] : "https://via.placeholder.com/300x200?text=Vaccine"; ?>' 
                             style="width:100%;height:100%;object-fit:cover;z-index:1;transition:transform 0.4s ease;"
                             onmouseover="this.style.transform='scale(1.08) rotate(2deg)'"
                             onmouseout="this.style.transform='scale(1) rotate(0)'">
                    </div>

                    <div style="padding:24px;flex:1;display:flex;flex-direction:column;background:linear-gradient(180deg, rgba(255,255,255,0.01) 0%, rgba(255,255,255,0.03) 100%);">
                        <div style="margin-bottom:auto;">
                            <h3 style="font-size:1.25rem;font-weight:700;color:#fff;margin-bottom:10px;line-height:1.4;"><?php echo $v['name']; ?></h3>
                            <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;line-height:1.6;margin-bottom:20px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                                <?php echo $v['description']; ?>
                            </p>
                        </div>
                        <?php if ($v['hospital_count'] > 0): ?>
                        <a href='book_appointment.php?vaccine_id=<?php echo $v['id']; ?>' class='btn btn-primary' 
                           style="width:100%;text-align:center;padding:12px;border-radius:14px;background:linear-gradient(45deg, #10b981, #059669);border:none;font-weight:600;letter-spacing:0.5px;box-shadow:0 4px 15px rgba(16,185,129,0.3);position:relative;overflow:hidden;">
                           <i class="fa-solid fa-syringe" style="margin-right:8px;"></i> Book Vaccine
                        </a>
                        <?php else: ?>
                        <div style="width:100%;text-align:center;padding:12px;border-radius:14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#ef4444;font-weight:600;font-size:0.88rem;display:flex;align-items:center;justify-content:center;gap:8px;">
                            <i class="fa-solid fa-ban"></i> Unavailable
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 style="font-size:1.5rem;font-weight:800;margin-bottom:24px;color:#fff;display:flex;align-items:center;gap:12px;">
            <span style="width:8px;height:24px;background:#3b82f6;border-radius:4px;display:inline-block;"></span>
            Partner Hospitals
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <?php foreach($hospitals as $h): ?>
                <div class='searchable-card' style="
                    background: rgba(255,255,255,0.03);
                    border: 1px solid rgba(255,255,255,0.08);
                    border-radius: 20px;
                    padding: 24px;
                    display: flex;
                    align-items: center;
                    gap: 24px;
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                " onmouseover="this.style.transform='translateY(-6px)';this.style.background='rgba(255,255,255,0.06)';this.style.boxShadow='0 15px 35px -5px rgba(0,0,0,0.4)';"
                  onmouseout="this.style.transform='translateY(0)';this.style.background='rgba(255,255,255,0.03)';this.style.boxShadow='none';">
                    
                    <div style="width:90px;height:90px;flex-shrink:0;position:relative;">
                        <img src='<?php echo !empty($h['image']) ? $h['image'] : "https://via.placeholder.com/300x200?text=Hospital"; ?>' 
                             style="width:100%;height:100%;border-radius:18px;object-fit:cover;border:2px solid rgba(255,255,255,0.1);box-shadow:0 8px 20px rgba(0,0,0,0.3);">
                        <div style="position:absolute;bottom:-4px;right:-4px;background:#10b981;width:20px;height:20px;border-radius:50%;border:3px solid #141414;"></div>
                    </div>
                    
                    <div style="flex:1;min-width:0;">
                        <h3 style="font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:6px;"><?php echo $h['name']; ?></h3>
                        <p style="color:rgba(255,255,255,0.55);font-size:0.9rem;margin-bottom:14px;display:flex;align-items:center;gap:6px;">
                            <i class='fa-solid fa-location-dot' style="color:#f59e0b;"></i> <?php echo $h['location']; ?>
                        </p>
                        <a href='book_appointment.php?hospital_id=<?php echo $h['id']; ?>' 
                           style="display:inline-flex;align-items:center;padding:8px 18px;background:rgba(59,130,246,0.15);color:#3b82f6;border-radius:30px;font-size:0.85rem;font-weight:700;text-decoration:none;transition:all 0.2s;"
                           onmouseover="this.style.background='#3b82f6';this.style.color='#fff';"
                           onmouseout="this.style.background='rgba(59,130,246,0.15)';this.style.color='#3b82f6';">
                           Book Appointment <i class="fa-solid fa-arrow-right" style="margin-left:6px;font-size:0.8rem;"></i>
                        </a>
                    </div>
                    <!-- Decorative BG Icon -->
                    <i class="fa-solid fa-hospital" style="position:absolute;right:-20px;bottom:-20px;font-size:8rem;color:rgba(255,255,255,0.03);transform:rotate(-15deg);pointer-events:none;"></i>
                </div>
            <?php endforeach; ?>
        </div>


    </div>

    <script>
        document.getElementById('dashboardSearch').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.searchable-card').forEach(card => {
                card.style.display = card.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        });

        function openPModal(id) {
            document.getElementById(id).classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closePModal(id) {
            document.getElementById(id).classList.remove('open');
            document.body.style.overflow = '';
        }
        document.querySelectorAll('.p-modal-overlay').forEach(o => {
            o.addEventListener('click', function(e) { if(e.target===this) closePModal(this.id); });
        });
        document.addEventListener('keydown', e => {
            if(e.key==='Escape') document.querySelectorAll('.p-modal-overlay.open').forEach(m=>closePModal(m.id));
        });
    </script>

<?php
$badge_colors = ['pending'=>['bg'=>'rgba(245,158,11,0.15)','c'=>'#f59e0b'], 'approved'=>['bg'=>'rgba(59,130,246,0.15)','c'=>'#3b82f6'], 'completed'=>['bg'=>'rgba(16,185,129,0.15)','c'=>'#10b981'], 'rejected'=>['bg'=>'rgba(239,68,68,0.15)','c'=>'#ef4444'], 'cancelled'=>['bg'=>'rgba(107,114,128,0.15)','c'=>'#9ca3af']];
$modal_defs = [
    'pm-all'       => ['title'=>'All Appointments',       'icon'=>'fa-calendar-check', 'color'=>'#10b981', 'filter'=>'all'],
    'pm-pending'   => ['title'=>'Pending Appointments',   'icon'=>'fa-clock',           'color'=>'#f59e0b', 'filter'=>'pending'],
    'pm-approved'  => ['title'=>'Confirmed Appointments', 'icon'=>'fa-check-circle',    'color'=>'#3b82f6', 'filter'=>'approved'],
    'pm-completed' => ['title'=>'Completed Appointments', 'icon'=>'fa-flag-checkered',  'color'=>'#8b5cf6', 'filter'=>'completed'],
];
foreach ($modal_defs as $mid => $m):
    $rows = array_filter($appointments, fn($a) => $m['filter']==='all' || $a['status']===$m['filter']);
    $rows = array_values($rows);
    $cnt  = count($rows);
    $m_test = count(array_filter($rows, fn($a)=>$a['type']==='test'));
    $m_vacc = count(array_filter($rows, fn($a)=>$a['type']==='vaccination'));
?>
<div class="p-modal-overlay" id="<?php echo $mid; ?>">
    <div class="p-modal-box">
        <div class="p-modal-header">
            <h3>
                <span style="width:32px;height:32px;border-radius:10px;background:<?php echo $m['color']; ?>22;display:inline-flex;align-items:center;justify-content:center;">
                    <i class="fa-solid <?php echo $m['icon']; ?>" style="color:<?php echo $m['color']; ?>;font-size:0.9rem;"></i>
                </span>
                <?php echo $m['title']; ?>
                <span style="background:<?php echo $m['color']; ?>22;color:<?php echo $m['color']; ?>;padding:2px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;"><?php echo $cnt; ?></span>
            </h3>
            <button class="p-modal-close" onclick="closePModal('<?php echo $mid; ?>')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-modal-body">
            <div class="p-mini-stats">
                <div class="p-mini-stat">
                    <div class="p-mini-val" style="color:<?php echo $m['color']; ?>"><?php echo $cnt; ?></div>
                    <div class="p-mini-lbl">Total</div>
                </div>
                <div class="p-mini-stat">
                    <div class="p-mini-val" style="color:#3b82f6"><?php echo $m_test; ?></div>
                    <div class="p-mini-lbl">Tests</div>
                </div>
                <div class="p-mini-stat">
                    <div class="p-mini-val" style="color:#10b981"><?php echo $m_vacc; ?></div>
                    <div class="p-mini-lbl">Vaccinations</div>
                </div>
            </div>
            <?php if(empty($rows)): ?>
                <div class="p-empty"><i class="fa-solid fa-calendar-xmark"></i>No appointments found</div>
            <?php else: ?>
            <table class="p-modal-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Hospital</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rows as $i=>$a):
                        $bc = $badge_colors[$a['status']] ?? ['bg'=>'rgba(255,255,255,0.1)','c'=>'#fff'];
                    ?>
                    <tr>
                        <td style="color:rgba(255,255,255,0.4);font-size:0.78rem;"><?php echo $i+1; ?></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($a['hospital_name']); ?></td>
                        <td>
                            <?php if($a['type']==='vaccination'): ?>
                                <span style="color:#10b981;font-size:0.82rem;"><i class="fa-solid fa-syringe"></i> Vaccination</span>
                            <?php else: ?>
                                <span style="color:#3b82f6;font-size:0.82rem;"><i class="fa-solid fa-flask"></i> Test</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.85rem;"><?php echo date('d M Y', strtotime($a['appointment_date'])); ?></td>
                        <td>
                            <span class="p-badge" style="background:<?php echo $bc['bg']; ?>;color:<?php echo $bc['c']; ?>;"><?php echo ucfirst($a['status']); ?></span>
                        </td>
                        <td style="color:rgba(255,255,255,0.55);font-size:0.82rem;">
                            <?php if($a['status']==='completed' && !empty($a['result'])): ?>
                                <a href="view_report.php?id=<?php echo $a['id']; ?>" style="color:#10b981;text-decoration:none;"><i class="fa-solid fa-file-medical"></i> View Report</a>
                            <?php else: ?>
                                <?php echo $a['result'] ?: '—'; ?>
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

</body>
</html>
