<?php
session_start();
include 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Search Logic
// Basic Params
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$hospital_filter = isset($_GET['hospital_filter']) ? intval($_GET['hospital_filter']) : '';

// Build Query
$where_clauses = [];
if (!empty($search)) {
    $where_clauses[] = "(p.name LIKE '%$search%' OR h.name LIKE '%$search%')";
}
if (!empty($hospital_filter)) {
    // We filter by the hospital associated via appointments
    $where_clauses[] = "h.id = $hospital_filter";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Complete SQL
$sql = "SELECT p.*, MAX(h.name) as hospital_name, MAX(a.remarks) as issue, MAX(h.id) as hospital_id, MAX(a.id) as latest_appt_id
        FROM patients p 
        LEFT JOIN appointments a ON p.id = a.patient_id 
        LEFT JOIN hospitals h ON a.hospital_id = h.id 
        $where_sql
        GROUP BY p.id";

// Handle Export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="patients_list.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'Name', 'Age', 'Issue', 'Hospital', 'Status', 'Phone', 'Email'));
    
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) {
        // Calculate Age
        $age = 'N/A';
        if (!empty($row['dob'])) {
            $dob = new DateTime($row['dob']);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
        }
        $issue = !empty($row['issue']) ? $row['issue'] : 'N/A';
        $status = isset($row['status']) ? $row['status'] : 'Active';
        
        fputcsv($output, array(
            $row['id'], 
            $row['name'], 
            $age, 
            $issue, 
            $row['hospital_name'], 
            $status,
            $row['phone'],
            $row['email']
        ));
    }
    fclose($output);
    exit();
}

$result = $conn->query($sql);

// Fetch Hospitals for Filter
$hospitals = $conn->query("SELECT id, name FROM hospitals");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patients List | MediCore Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Glass Navbar -->
    <nav class="glass-nav">
        <a href="admin_dashboard.php" class="nav-brand">
            <i class="fa-solid fa-heart-pulse text-primary"></i>
            <span>MediCore</span>
        </a>

        <div class="nav-links-container">
            <a href="admin_dashboard.php" class="nav-link">
                <i class="fa-solid fa-chart-grid"></i> Dashboard
            </a>
            <a href="manage_vaccines.php" class="nav-link">
                <i class="fa-solid fa-syringe"></i> Vaccines
            </a>
             <a href="logout.php" class="nav-link" style="color: #ef4444;">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </nav>

    <div class="main-wrapper animate-fade">
        <div class="page-header-simple">
            <div>
                <h1>Patient Records</h1>
                <p class="text-muted">Detailed list of all registered patients</p>
            </div>
            
            <div class="header-actions" style="gap: 15px;">
                 <form method="GET" class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or hospital..." class="glass-input">
                </form>
                <div class="time-display" id="clock">00:00:00</div>
            </div>
        </div>

        <div class="glass-card animate-fade">
            <div class="card-header">
                <h3><i class="fa-solid fa-users-medical"></i> All Patients</h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <!-- Filter Form -->
                    <form method="GET" style="margin: 0; display: flex; align-items: center;">
                        <?php if(!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        
                        <div style="position: relative;">
                            <i class="fa-solid fa-filter" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem;"></i>
                            <select name="hospital_filter" onchange="this.form.submit()" class="glass-input" style="padding-left: 30px; padding-top: 8px; padding-bottom: 8px; width: auto; font-size: 0.9rem; border-radius: 8px;">
                                <option value="">All Hospitals</option>
                                <?php 
                                if ($hospitals->num_rows > 0) {
                                    while($h = $hospitals->fetch_assoc()) {
                                        $selected = ($hospital_filter == $h['id']) ? 'selected' : '';
                                        echo "<option value='{$h['id']}' $selected>{$h['name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </form>
                    
                    <!-- Export Link -->
                    <a href="?export=1&search=<?php echo urlencode($search); ?>&hospital_filter=<?php echo $hospital_filter; ?>" class="btn btn-outline" style="text-decoration: none; padding: 8px 16px; border-radius: 8px; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-download"></i> Export
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Issue/Condition</th>
                            <th>Assigned Hospital</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                // Mock status based on random for visual if column missing
                                $status = isset($row['status']) ? $row['status'] : 'Active'; 
                                $statusClass = $status == 'Active' ? 'status-active' : 'status-pending';
                                
                                // Calculate Age
                                if (isset($row['dob']) && !empty($row['dob'])) {
                                    $dob = new DateTime($row['dob']);
                                    $now = new DateTime();
                                    $age = $now->diff($dob)->y;
                                } else {
                                    $age = 'N/A';
                                }

                                // Issue
                                $issue = !empty($row['issue']) ? $row['issue'] : 'N/A';

                                // Latest Appointment Report Link
                                $actionBtn = "";
                                if (!empty($row['latest_appt_id'])) {
                                    $actionBtn = "<a href='view_report.php?id={$row['latest_appt_id']}' class='icon-btn-sm' title='View Report' target='_blank'><i class='fa-solid fa-file-medical'></i></a>";
                                } else {
                                    $actionBtn = "<button class='icon-btn-sm' disabled title='No Report Available'><i class='fa-solid fa-eye-slash'></i></button>";
                                }

                                echo "<tr>
                                    <td>#{$row['id']}</td>
                                    <td>
                                        <div class='fw-bold'>{$row['name']}</div>
                                    </td>
                                    <td>{$age}</td>
                                    <td>{$issue}</td>
                                    <td><i class='fa-solid fa-hospital text-accent'></i> {$row['hospital_name']}</td>
                                    <td><span class='badge $statusClass'>$status</span></td>
                                    <td>
                                        {$actionBtn}
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-5 text-muted'>No patients found matching your search.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Simulating Pagination Removed as requested -->
        </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
