<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">Patient Portal</div>
                <button class="hamburger" id="hamburger"><i class="fa-solid fa-bars"></i></button>
                <div class="nav-links" id="navLinks">
                    <span style="align-self:center;">Welcome, <?php echo $_SESSION['name']; ?></span>
                    <a href="profile.php" class="btn btn-outline" style="border:none;">My Profile</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" style="padding-top: 40px; padding-bottom: 40px;">
        
        <div class="card" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: var(--glass-highlight);">
            <h2 style="margin: 0;">My Appointments</h2>
            <a href="book_appointment.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Book New</a>
        </div>

        <!-- Search Section -->
        <div style="margin-bottom: 30px;">
            <input type="text" id="dashboardSearch" placeholder="Search for vaccines or hospitals..." class="form-input" style="width: 100%; padding: 15px; font-size: 1.1rem; border-radius: 50px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: white;">
        </div>

        <!-- Vaccines Section -->
        <h2 style="margin-bottom: 20px; text-shadow: var(--neon-glow);">Available Vaccines</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <?php
            $vaccines = $conn->query("SELECT * FROM vaccines WHERE availability='available'");
            if ($vaccines->num_rows > 0) {
                while($v = $vaccines->fetch_assoc()) {
                    $img = !empty($v['image']) ? $v['image'] : 'https://via.placeholder.com/300x200?text=Vaccine';
                    $searchUrl = "https://www.google.com/search?q=" . urlencode($v['name'] . " vaccine");
                    echo "
                    <div class='card searchable-card' style='padding: 0; overflow: hidden; display: flex; flex-direction: column; position: relative;'>
                        <div style='height: 150px; width: 100%; position: relative; overflow: hidden; background: rgba(255,255,255,0.05);'>
                             <a href='book_appointment.php?vaccine_id={$v['id']}' style='display: block; width: 100%; height: 100%; position: absolute; top: 0; left: 0; z-index: 10;'>
                                <span style='display:none'>Book</span>
                             </a>
                            <img src='{$img}' alt='{$v['name']}' style='width: 100%; height: 100%; object-fit: contain; padding: 10px; transition: transform 0.3s;'>
                        </div>
                        <div style='padding: 20px; flex: 1; display: flex; flex-direction: column;'>
                            <div style='display:flex; justify-content:space-between; align-items:center;'>
                                <h3 style='margin-top: 0;'>{$v['name']}</h3>
                                <a href='$searchUrl' target='_blank' class='btn btn-outline' style='padding: 2px 8px; font-size: 0.8rem; z-index: 20; position: relative;' title='Search on Browser'><i class='fa-brands fa-google'></i></a>
                            </div>
                            <p style='flex: 1; opacity: 0.8; margin-bottom: 15px;'>{$v['description']}</p>
                            <a href='book_appointment.php?vaccine_id={$v['id']}' class='btn btn-primary' style='text-align: center; z-index: 20; position: relative;'>Book Vaccine</a>
                        </div>
                    </div>";
                }
            }
            ?>
        </div>

        <!-- Hospitals Section -->
        <h2 style="margin-bottom: 20px; text-shadow: var(--neon-glow);">Partner Hospitals</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <?php
            $hospitals = $conn->query("SELECT * FROM hospitals WHERE status='approved'");
            if ($hospitals->num_rows > 0) {
                while($h = $hospitals->fetch_assoc()) {
                    $img = !empty($h['image']) ? $h['image'] : 'https://via.placeholder.com/300x200?text=Hospital';
                    $searchUrl = "https://www.google.com/search?q=" . urlencode($h['name'] . " hospital " . $h['location']);
                    echo "
                    <div class='card searchable-card' style='padding: 20px; display: flex; align-items: center; gap: 20px; position: relative; overflow: hidden; flex-direction: row;'>
                        <img src='{$img}' alt='{$h['name']}' style='width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--glass-border); flex-shrink: 0;'>
                        
                        <div style='flex: 1; display: flex; flex-direction: column;'>
                             <div style='display:flex; justify-content:space-between; align-items:center; margin-bottom: 5px;'>
                                <h3 style='margin: 0; font-size: 1.1rem;'>{$h['name']}</h3>
                                <a href='$searchUrl' target='_blank' class='btn btn-outline' style='padding: 2px 8px; font-size: 0.8rem;' title='Search on Browser'><i class='fa-brands fa-google'></i></a>
                            </div>
                            <p style='opacity: 0.8; margin-bottom: 5px; font-size: 0.9rem;'><i class='fa-solid fa-map-marker-alt'></i> {$h['location']}</p>
                            
                            <a href='book_appointment.php?hospital_id={$h['id']}&type=test' class='btn btn-outline' style='align-self: flex-start; padding: 5px 15px; font-size: 0.9rem;'>Book Test</a>
                        </div>
                    </div>";
                }
            }
            ?>
        </div>

        <script>
            document.getElementById('dashboardSearch').addEventListener('keyup', function() {
                const query = this.value.toLowerCase();
                const cards = document.querySelectorAll('.searchable-card');
                
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if(text.includes(query)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        </script>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Hospital</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT a.*, h.name as hname FROM appointments a 
                            JOIN hospitals h ON a.hospital_id = h.id 
                            WHERE a.patient_id = $patient_id ORDER BY a.appointment_date DESC";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $statusBadge = $row['status'] == 'completed' ? 'success' : ($row['status'] == 'rejected' ? 'danger' : 'warning');
                            
                            $actionHtml = "-";
                            if ($row['status'] == 'completed') {
                                $actionHtml = "<a href='view_report.php?id={$row['id']}' target='_blank' class='btn btn-outline' style='font-size: 0.8rem; padding: 4px 8px;'>View Report</a>";
                            }

                            echo "<tr>
                                <td>{$row['hname']}</td>
                                <td>" . strtoupper($row['type']) . "</td>
                                <td>{$row['appointment_date']}</td>
                                <td>{$row['appointment_time']}</td>
                                <td><span class='badge badge-$statusBadge'>{$row['status']}</span></td>
                                <td>{$row['result']}</td>
                                <td>$actionHtml</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No appointments found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
