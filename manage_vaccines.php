<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'hospital'])) {
    header("Location: login.php");
    exit();
}

$start_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$message = "";

// Handle Admin Actions (Update / Delete Vaccines)
if ($start_role === 'admin') {
    // Handle Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vaccine'])) {
        $id = intval($_POST['vaccine_id']);
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $img = $_POST['image']; // URL
        
        $stmt = $conn->prepare("UPDATE vaccines SET name=?, description=?, image=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $desc, $img, $id);
        if ($stmt->execute()) {
            $message = "Vaccine updated successfully.";
        } else {
            $message = "Error updating vaccine.";
        }
    }

    if (isset($_GET['delete_id'])) {
        $del_id = intval($_GET['delete_id']);
        $conn->query("DELETE FROM hospital_vaccines WHERE vaccine_id=$del_id"); // Cleanup dependencies first
        $conn->query("DELETE FROM vaccines WHERE id=$del_id");
        header("Location: manage_vaccines.php?msg=deleted");
        exit();
    }
}

// Handle Hospital Actions (Toggle Status)
if ($start_role === 'hospital') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vaccine_id']) && isset($_POST['status'])) {
        $vaccine_id = intval($_POST['vaccine_id']);
        $status = $_POST['status'] === 'available' ? 'available' : 'unavailable';
        
        // Check if entry exists
        $check_sql = "SELECT id FROM hospital_vaccines WHERE hospital_id = ? AND vaccine_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $user_id, $vaccine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update
            $update_sql = "UPDATE hospital_vaccines SET status = ? WHERE hospital_id = ? AND vaccine_id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("sii", $status, $user_id, $vaccine_id);
            $stmt_update->execute();
        } else {
            // Insert
            $insert_sql = "INSERT INTO hospital_vaccines (hospital_id, vaccine_id, status) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_sql);
            $stmt_insert->bind_param("iis", $user_id, $vaccine_id, $status);
            $stmt_insert->execute();
        }
        $message = "Vaccine status updated successfully.";
    }
}

// Fetch Data
if ($start_role === 'admin') {
    $result = $conn->query("SELECT * FROM vaccines ORDER BY name ASC");
} else {
    // Hospital view with status
    $sql_vaccines = "SELECT v.*, hv.status as hospital_status 
                     FROM vaccines v 
                     LEFT JOIN hospital_vaccines hv ON v.id = hv.vaccine_id AND hv.hospital_id = $user_id 
                     ORDER BY v.name ASC";
    $result = $conn->query($sql_vaccines);
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
        .vaccine-list-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        @media (max-width: 992px) {
            .vaccine-list-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .vaccine-list-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .vaccine-item-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }

        .vaccine-item-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .vaccine-card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid var(--glass-border);
        }

        .vaccine-card-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .vaccine-info h3 {
            margin: 0 0 10px 0;
            color: var(--light);
            font-size: 1.2rem;
            font-weight: 600;
        }

        .vaccine-desc-sm {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }
        
        .vaccine-card-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px solid var(--glass-border);
        }

        .status-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        
        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.1);
            transition: .4s;
            border-radius: 34px;
            border: 1px solid var(--glass-border);
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: var(--text-muted);
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: rgba(16, 185, 129, 0.2);
            border-color: #10b981;
        }
        
        input:checked + .slider:before {
            transform: translateX(24px);
            background-color: #10b981;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #1a1a1a;
            margin: 5% auto;
            padding: 30px;
            border: 1px solid var(--glass-border);
            width: 90%;
            max-width: 600px;
            border-radius: 16px;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .close-modal {
            color: var(--text-muted);
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: var(--light);
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            color: white;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo"><i class="fa-solid fa-syringe"></i> Manage Vaccines</div>
                <div class="nav-links">
                    <a href="<?php echo $start_role === 'admin' ? 'admin_dashboard.php' : 'hospital_dashboard.php'; ?>" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" style="padding-top: 40px; padding-bottom: 60px;">
        
        <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div style="font-size: 1.5rem; font-weight: 600; color: var(--light);">
                <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> 
                <?php echo $start_role === 'admin' ? 'System Vaccines' : 'Vaccine Inventory Control'; ?>
            </div>
            
            <?php if ($start_role === 'admin'): ?>
                <a href="add_vaccine.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Vaccine</a>
            <?php endif; ?>
        </div>
        
        <?php if ($start_role === 'hospital'): ?>
        <p style="color: var(--text-muted); margin-bottom: 30px;">
            Toggle the switch to mark vaccines as available or unavailable at your facility. 
            Patients will only see vaccines marked as "Available" when booking with your hospital.
        </p>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                <i class="fa-solid fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] == 'deleted'): ?>
                 <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.2);">
                    <i class="fa-solid fa-trash"></i> Vaccine deleted successfully.
                </div>
            <?php elseif ($_GET['msg'] == 'added'): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-check-circle"></i> New vaccine added successfully.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="vaccine-list-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $image = !empty($row['image']) ? $row['image'] : 'https://images.unsplash.com/photo-1584036561566-b93a50208ccf?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
                    $desc = !empty($row['description']) ? $row['description'] : 'No description available.';
                    
                    // Safely encode data for JS
                    $safe_id = $row['id'];
                    $safe_name = htmlspecialchars($row['name'], ENT_QUOTES);
                    $safe_desc = htmlspecialchars($row['description'], ENT_QUOTES);
                    $safe_image = htmlspecialchars($row['image'], ENT_QUOTES);
                    
                    echo '
                    <div class="vaccine-item-card">
                        <img src="'.$image.'" alt="'.$row['name'].'" class="vaccine-card-img">
                        <div class="vaccine-card-body">
                            <div class="vaccine-info">
                                <h3>'.$row['name'].'</h3>
                                <p class="vaccine-desc-sm">'.$desc.'</p>
                            </div>
                            <div class="vaccine-card-footer">
                                <span style="font-size: 0.85rem; color: var(--text-muted);">ID: #'.$row['id'].'</span>
                    ';

                    // Hospital Role: Show Toggle
                    if ($start_role === 'hospital') {
                        $isChecked = ($row['hospital_status'] == 'available') ? 'checked' : '';
                        echo '
                                <form method="POST" class="status-toggle">
                                    <input type="hidden" name="vaccine_id" value="'.$row['id'].'">
                                    <input type="hidden" name="status" value="'.($isChecked ? 'unavailable' : 'available').'">
                                    <label class="switch">
                                        <input type="checkbox" '.$isChecked.' onchange="this.form.submit()">
                                        <span class="slider"></span>
                                    </label>
                                </form>
                        ';
                    } 
                    // Admin Role: Show Edit & Delete
                    else {
                        echo '
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="openEditModal('.$safe_id.', \''.$safe_name.'\', \''.$safe_desc.'\', \''.$safe_image.'\')" class="btn btn-outline btn-sm" style="color: var(--primary); border-color: var(--primary); padding: 5px 10px;">
                                        <i class="fa-solid fa-edit"></i> Edit
                                    </button>
                                    <a href="?delete_id='.$row['id'].'" class="btn btn-outline btn-sm" onclick="return confirm(\'Are you sure you want to delete this vaccine?\')" style="color: var(--danger); border-color: var(--danger); padding: 5px 10px;"> 
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                        ';
                    }

                    echo '
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo '<p class="text-muted">No vaccines found in the system database.</p>';
            }
            ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content animate-fade">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h2 style="color: var(--primary); margin-bottom: 20px;"><i class="fa-solid fa-edit"></i> Edit Vaccine</h2>
            <form method="POST">
                <input type="hidden" name="update_vaccine" value="1">
                <input type="hidden" id="edit_id" name="vaccine_id">
                
                <div class="form-group">
                    <label style="color: var(--light); display: block; margin-bottom: 8px;">Vaccine Name</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label style="color: var(--light); display: block; margin-bottom: 8px;">Description</label>
                    <textarea id="edit_desc" name="description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label style="color: var(--light); display: block; margin-bottom: 8px;">Image URL</label>
                    <input type="text" id="edit_image" name="image" class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Update Vaccine</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, desc, image) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_desc').value = desc;
            document.getElementById('edit_image').value = image;
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
