<?php
/**
 * Manage Vaccines (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

// Auth check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hospital'])) {
    header('Location: login.php');
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Handle Actions (Admin Add/Edit/Delete, Hospital Status)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $role == 'admin') {
    $action = $_POST['action'];
    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO vaccines (name, description, image) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['image']]);
        header('Location: manage_vaccines.php?msg=added'); exit();
    } elseif ($action == 'edit') {
        $stmt = $pdo->prepare("UPDATE vaccines SET name = ?, description = ?, image = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['image'], $_POST['id']]);
        header('Location: manage_vaccines.php?msg=updated'); exit();
    } elseif ($action == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM vaccines WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header('Location: manage_vaccines.php?msg=deleted'); exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $role == 'hospital' && $_POST['action'] == 'update_status') {
    $v_id = $_POST['vaccine_id'];
    $status = $_POST['status'];
    
    // Check if exists in hospital_vaccines
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM hospital_vaccines WHERE hospital_id = ? AND vaccine_id = ?");
    $stmt->execute([$user_id, $v_id]);
    if ($stmt->fetchColumn() > 0) {
        $stmt = $pdo->prepare("UPDATE hospital_vaccines SET status = ? WHERE hospital_id = ? AND vaccine_id = ?");
        $stmt->execute([$status, $user_id, $v_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO hospital_vaccines (hospital_id, vaccine_id, status) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $v_id, $status]);
    }
    header('Location: manage_vaccines.php?msg=status_updated'); exit();
}

// Fetch Vaccines
if ($role == 'admin') {
    $list = $pdo->query("SELECT * FROM vaccines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Hospital view with status
    $stmt = $pdo->prepare("SELECT v.*, hv.status as hospital_status 
                          FROM vaccines v 
                          LEFT JOIN hospital_vaccines hv ON v.id = hv.vaccine_id AND hv.hospital_id = ? 
                          ORDER BY v.name ASC");
    $stmt->execute([$user_id]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Vaccines | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .vaccine-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 30px; 
            padding: 20px 0;
        }

        .vaccine-card-adv {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .vaccine-card-adv:hover {
            transform: translateY(-8px);
            border-color: rgba(0, 210, 255, 0.3);
            background: rgba(255, 255, 255, 0.03);
            box-shadow: 0 40px 80px rgba(0,0,0,0.6);
        }

        .vaccine-card-adv::after {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(0, 210, 255, 0.05) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.5s;
            pointer-events: none;
        }

        .vaccine-card-adv:hover::after { opacity: 1; }

        .v-img-container {
            width: 100%;
            height: 200px;
            margin-bottom: 25px;
            border-radius: 18px;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
        }

        .v-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.6s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .vaccine-card-adv:hover .v-img-container {
            border-color: rgba(0, 210, 255, 0.4);
        }

        .vaccine-card-adv:hover .v-img-container img {
            transform: scale(1.1);
        }

        .v-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 100px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 10;
        }

        .v-info-adv h3 {
            font-size: 1.2rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 15px 0;
            letter-spacing: -0.5px;
            text-align: center;
        }

        .v-telemetry-adv {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 15px;
            background: rgba(0,0,0,0.25);
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,0.03);
            margin-bottom: 20px;
        }

        .v-node-adv {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .v-label-adv {
            font-size: 0.6rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .v-value-adv {
            font-size: 0.75rem;
            font-weight: 700;
            color: #e2e8f0;
        }

        .v-desc-mini {
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 15px;
            min-height: 2em;
        }

        .v-actions-adv {
            display: flex;
            gap: 10px;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 15px;
        }

        .btn-v-action {
            flex: 1;
            padding: 10px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 800;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-v-edit { background: rgba(0, 210, 255, 0.1); color: #00d2ff; border: 1px solid rgba(0, 210, 255, 0.2); }
        .btn-v-edit:hover { background: #00d2ff; color: #000; box-shadow: 0 0 15px rgba(0, 210, 255, 0.3); }

        .btn-v-del { background: rgba(244, 63, 94, 0.1); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.2); }
        .btn-v-del:hover { background: #f43f5e; color: #fff; box-shadow: 0 0 15px rgba(244, 63, 94, 0.3); }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(10px); }
        .modal-content { 
            background: rgba(25, 25, 25, 0.95); 
            padding: 40px; 
            border-radius: 30px; 
            width: 100%; 
            max-width: 500px; 
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 50px 100px rgba(0,0,0,0.8);
        }
    </style>
</head>
<body style="background: var(--background); color: var(--text-light);">

    <div class="container" style="padding-top: 60px; max-width: 1200px;">
        <div style="text-align: center; margin-bottom: 50px;">
            <h1 style="font-size: 2.5rem; font-weight: 800; letter-spacing: 1px; margin:0; text-transform: uppercase;">
                VACCINE <span style="color: var(--primary);">STOCK</span>
            </h1>
            <p style="margin-top: 5px; color: #94a3b8; font-weight: 500; letter-spacing: 0.5px;">Manage vaccine records and node availability.</p>
            
            <?php if ($role == 'admin'): ?>
                <button class="btn btn-primary" onclick="openModal('add')" style="padding: 10px 25px; border-radius: 12px; font-weight: 800; margin-top: 25px; font-size: 0.75rem; letter-spacing: 1px;">
                    <i class="fa-solid fa-plus"></i> ADD VACCINE
                </button>
            <?php endif; ?>
        </div>

        <?php if($msg): ?>
            <div class="animate-fade" style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 12px 20px; border-radius: 12px; margin-bottom: 30px; border: 1px solid rgba(16, 185, 129, 0.2); font-weight: 600; font-size: 0.85rem; text-align: center;">
                <i class="fa-solid fa-circle-check"></i> Status: <?php echo strtoupper(htmlspecialchars($msg)); ?>
            </div>
        <?php endif; ?>

        <div class="vaccine-grid">
            <?php foreach($list as $v): ?>
                <div class="vaccine-card-adv animate-fade">
                    <?php if($role == 'hospital'): ?>
                        <div class="v-badge" style="background: <?php echo ($v['hospital_status'] == 'available') ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; border: 1px solid <?php echo ($v['hospital_status'] == 'available') ? 'rgba(16, 185, 129, 0.3)' : 'rgba(239, 68, 68, 0.3)'; ?>; color: <?php echo ($v['hospital_status'] == 'available') ? '#10b981' : '#ef4444'; ?>;">
                            <?php echo ($v['hospital_status'] == 'available') ? 'READY' : 'OUT OF STOCK'; ?>
                        </div>
                    <?php endif; ?>

                    <div class="v-img-container">
                        <img src="<?php echo !empty($v['image']) ? $v['image'] : 'https://cdn-icons-png.flaticon.com/512/2864/2864448.png'; ?>" alt="Vaccine">
                    </div>

                    <div class="v-info-adv">
                        <h3><?php echo htmlspecialchars($v['name']); ?></h3>
                        <div class="v-desc-mini"><?php echo htmlspecialchars($v['description']); ?></div>
                        
                        <div class="v-telemetry-adv">
                            <div class="v-node-adv">
                                <span class="v-label-adv">ID Nucleus</span>
                                <span class="v-value-adv">#VAC-<?php echo str_pad($v['id'], 3, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="v-node-adv">
                                <span class="v-label-adv">Protocol</span>
                                <span class="v-value-adv">Standard Dose</span>
                            </div>
                            <div class="v-node-adv" style="padding-top: 5px; border-top: 1px solid rgba(255,255,255,0.05); margin-top: 5px;">
                                <span class="v-label-adv">Status</span>
                                <span class="v-value-adv" style="color: #00d2ff; font-weight: 800;">VERIFIED</span>
                            </div>
                        </div>
                    </div>

                    <div class="v-actions-adv">
                        <?php if ($role == 'admin'): ?>
                            <button class="btn-v-action btn-v-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($v)); ?>)">
                                <i class="fa-solid fa-pen"></i> EDIT
                            </button>
                            <form method="POST" onsubmit="return confirm('Delete this record?')" style="flex: 1; display: flex;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                <button type="submit" class="btn-v-action btn-v-del" style="width: 100%;">
                                    <i class="fa-solid fa-trash"></i> DELETE
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="width: 100%;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="vaccine_id" value="<?php echo $v['id']; ?>">
                                <div style="position: relative;">
                                    <select name="status" onchange="this.form.submit()" style="width: 100%; border-radius: 12px; height: 45px; background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.1); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; color: #fff; cursor: pointer; text-align: center;">
                                        <option value="available" <?php echo $v['hospital_status'] == 'available' ? 'selected' : ''; ?>>AVAILABLE</option>
                                        <option value="unavailable" <?php echo ($v['hospital_status'] == 'unavailable' || !$v['hospital_status']) ? 'selected' : ''; ?>>UNAVAILABLE</option>
                                    </select>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="admin_dashboard.php" style="color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; justify-content: center;" onmouseover="this.style.color='#fff'; this.style.transform='translateY(-3px)'" onmouseout="this.style.color='#94a3b8'; this.style.transform='translateY(0)'">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Admin Modal -->
    <?php if ($role == 'admin'): ?>
    <div id="vModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Vaccine</h2>
            <form method="POST">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="vaccineId">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="vName" placeholder="e.g. Pfizer-BioNTech" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="vDesc" rows="4" placeholder="Provide clinical details or dosage info..."></textarea>
                </div>
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image" id="vImage" placeholder="Paste direct image link (PNG/JPG)...">
                </div>
                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" class="btn btn-primary flex-1">Save</button>
                    <button type="button" class="btn btn-outline flex-1" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openModal(action) {
            document.getElementById('modalAction').value = action;
            document.getElementById('modalTitle').innerText = action === 'add' ? 'Add Vaccine' : 'Edit Vaccine';
            document.getElementById('vModal').style.display = 'flex';
        }
        function closeModal() { document.getElementById('vModal').style.display = 'none'; }
        function openEditModal(v) {
            document.getElementById('vaccineId').value = v.id;
            document.getElementById('vName').value = v.name;
            document.getElementById('vDesc').value = v.description;
            document.getElementById('vImage').value = v.image;
            openModal('edit');
        }
    </script>
    <?php endif; ?>

</body>
</html>
