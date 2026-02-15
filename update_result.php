<?php
session_start();
include 'db_connect.php';

// Auth Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hospital') {
    header("Location: login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];
$appt_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Appointment Details with Hospital Info
$sql = "SELECT a.*, p.name as pname, p.email, h.name as hname, h.email as hemail, h.address as haddress, v.name as vname, v.description as vdesc 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        JOIN hospitals h ON a.hospital_id = h.id 
        LEFT JOIN vaccines v ON a.vaccine_id = v.id
        WHERE a.id = $appt_id AND a.hospital_id = $hospital_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Invalid Request or Appointment not found.";
    exit();
}

$appointment = $result->fetch_assoc();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result_status = $_POST['result'];
    $remarks = $conn->real_escape_string($_POST['remarks']); // Basic sanitization

    // Validate result based on type
    // Start with a basic update query
    $update_sql = "UPDATE appointments SET 
                   status = 'completed', 
                   result = '$result_status', 
                   remarks = '$remarks' 
                   WHERE id = $appt_id AND hospital_id = $hospital_id";

    if ($conn->query($update_sql) === TRUE) {
        
        // Send Email Notification
        if (!empty($appointment['email'])) {
            include_once 'mail_helper.php';
            require('FPDF/fpdf.php');

            // Generate PDF
            class PDF extends FPDF {
                protected $hospitalName;
                protected $hospitalAddress;
                protected $hospitalEmail;

                function setHospitalDetails($name, $address, $email) {
                    $this->hospitalName = $name;
                    $this->hospitalAddress = $address;
                    $this->hospitalEmail = $email;
                }

                function Header() {
                    // 1. Left Side: Hospital Info (Logo placeholder)
                    $this->SetFont('Arial', 'B', 20);
                    $this->SetTextColor(13, 110, 253); // Blue
                    $this->Cell(100, 10, strtoupper($this->hospitalName), 0, 1, 'L');
                    
                    $this->SetFont('Arial', '', 9);
                    $this->SetTextColor(0, 0, 0);
                    $this->Cell(100, 5, $this->hospitalAddress, 0, 1, 'L');
                    $this->Cell(100, 5, $this->hospitalEmail, 0, 1, 'L');
                    
                    // 2. Right Side: Patient Info Box
                    // Coordinates for the box
                    $boxX = 110;
                    $boxY = 10;
                    $boxWidth = 90;
                    $boxHeight = 35;
                    
                    $this->SetDrawColor(0, 0, 0);
                    $this->SetLineWidth(0.2);
                    $this->Rect($boxX, $boxY, $boxWidth, $boxHeight);
                    
                    // Inside the box
                    $this->SetXY($boxX + 2, $boxY + 2);
                    $this->SetFont('Arial', 'B', 8);
                    $this->Cell(15, 5, 'Lab No:', 0, 0);
                    $this->SetFont('Arial', '', 8);
                    $this->Cell(30, 5, $GLOBALS['appt_id'], 0, 0);
                    
                    $this->SetFont('Arial', 'B', 8);
                    $this->Cell(15, 5, 'Date:', 0, 0);
                    $this->SetFont('Arial', '', 8);
                    $this->Cell(25, 5, date('d-m-Y H:i'), 0, 1);
                    
                    $this->SetX($boxX + 2);
                    $this->SetFont('Arial', 'B', 8);
                    $this->Cell(15, 5, 'Name:', 0, 0);
                    $this->SetFont('Arial', 'B', 9); // Name bold
                    $this->Cell(70, 5, strtoupper($GLOBALS['pname']), 0, 1);
                    
                    $this->SetX($boxX + 2);
                    $this->SetFont('Arial', 'B', 8);
                    $this->Cell(15, 5, 'Ref By:', 0, 0);
                    $this->SetFont('Arial', '', 8);
                    $this->Cell(70, 5, $this->hospitalName, 0, 1);
                    
                    $this->SetX($boxX + 2);
                    $this->SetFont('Arial', 'B', 8);
                    $this->Cell(15, 5, 'Status:', 0, 0);
                    $this->SetFont('Arial', '', 8);
                    $this->Cell(70, 5, 'Reported', 0, 1);

                    // Move down for body
                    $this->SetY(55);
                    $this->Line(10, 50, 200, 50); // Separator line
                }

                function Footer() {
                    // Doctor Signature Area
                    $this->SetY(-40);
                    $this->SetFont('Arial', 'B', 10);
                    $this->Cell(0, 5, 'Dr. Ayesha', 0, 1, 'R');
                    $this->SetFont('Times', 'I', 14); // Use Times Italic as a standard script-like alternative
                    $this->SetTextColor(13, 110, 253); // Blue ink
                    $this->Cell(0, 10, 'Ayesha', 0, 1, 'R'); // Simulation of signature
                    $this->SetTextColor(0,0,0);
                    $this->SetFont('Arial', '', 8);
                    $this->Cell(0, 5, '(Pathologist)', 0, 1, 'R');

                    // Vertical Sidebar Text
                    $this->SetFont('Arial', 'B', 8);
                    $this->SetTextColor(150, 150, 150);
                    $this->RotatedText(8, 200, 'NOT VALID FOR MEDICO-LEGAL PURPOSE', 90);

                    // Standard Footer
                    $this->SetY(-15);
                    $this->SetFont('Arial', '', 7);
                    $this->SetTextColor(0, 0, 0);
                    $this->Cell(100, 10, 'This is a computer generated report and does not require a signature.', 0, 0, 'L');
                    $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb} - Printed on: ' . date('d-m-Y H:i:s'), 0, 0, 'R');
                }

                function RotatedText($x, $y, $txt, $angle) {
                    $this->Rotate($angle, $x, $y);
                    $this->Text($x, $y, $txt);
                    $this->Rotate(0);
                }

                var $angle = 0;
                function Rotate($angle, $x=-1, $y=-1) {
                    if($x==-1) $x=$this->x;
                    if($y==-1) $y=$this->y;
                    if($this->angle!=0) $this->_out('Q');
                    $this->angle=$angle;
                    if($angle!=0) {
                        $angle*=M_PI/180;
                        $c=cos($angle);
                        $s=sin($angle);
                        $cx=$x*$this->k;
                        $cy=($this->h-$y)*$this->k;
                        $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
                    }
                }
            }

            // Define globals for Header
            $GLOBALS['appt_id'] = str_pad($appt_id, 8, '0', STR_PAD_LEFT);
            $GLOBALS['pname'] = $appointment['pname'];

            $pdf = new PDF();
            $pdf->setHospitalDetails($appointment['hname'], $appointment['haddress'], $appointment['hemail']);
            $pdf->AliasNbPages();
            $pdf->AddPage();

            // Report Body
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 10, 'CLINICAL PATHOLOGY REPORT', 0, 1, 'L');
            
            // Table Header
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(60, 8, 'Test Name', 1, 0, 'L', true);
            $pdf->Cell(40, 8, 'Result', 1, 0, 'C', true);
            $pdf->Cell(30, 8, 'Unit', 1, 0, 'C', true);
            $pdf->Cell(60, 8, 'Normal Range', 1, 1, 'L', true);

            // Table Data
            $pdf->SetFont('Arial', '', 9);
            
            // Test Name
            $pdf->Cell(60, 10, strtoupper($appointment['type']) . ' TEST', 1, 0, 'L');
            
            // Result (Bold if positive?)
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(40, 10, strtoupper($result_status), 1, 0, 'C');
            
            // Unit (Dummy)
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(30, 10, '-', 1, 0, 'C');
            
            // Normal Range
            $pdf->Cell(60, 10, 'Negative', 1, 1, 'L');

            // Remarks Section
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(0, 5, 'Remarks / Comments:', 0, 1);
            $pdf->SetFont('Arial', '', 9);
            $remarks_text = !empty($remarks) ? $remarks : "No specific remarks.";
            $pdf->MultiCell(0, 5, $remarks_text);

            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(0, 5, 'Please correlate with other clinical and diagnostic parameters.', 0, 1);
            
            // Vaccine Details Section
            if ($appointment['type'] == 'vaccination' && !empty($appointment['vname'])) {
                $pdf->Ln(10);
                $pdf->SetDrawColor(13, 110, 253);
                $pdf->SetLineWidth(0.5);
                $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
                $pdf->Ln(5);
                
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(13, 110, 253);
                $pdf->Cell(0, 8, 'VACCINATION DETAILS', 0, 1, 'L');
                
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(40, 8, 'Vaccine Name:', 0, 0);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(0, 8, $appointment['vname'], 0, 1);
                
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(40, 8, 'Description:', 0, 0);
                $pdf->SetFont('Arial', '', 10);
                $pdf->MultiCell(0, 8, $appointment['vdesc']);
                
                $pdf->SetDrawColor(0,0,0); // Reset
            }

            // Watermark (Center Page)
            $pdf->SetFont('Arial', 'B', 50);
            $pdf->SetTextColor(240, 240, 240);
            $pdf->RotatedText(50, 180, strtoupper($result_status), 30);

            // Save PDF to temp file
            $temp_file = sys_get_temp_dir() . '/medical_report_' . $appt_id . '.pdf';
            $pdf->Output('F', $temp_file);
            
            $subject = "Medical Report & Certificate - MediCore";
            $body = "Dear <strong>{$appointment['pname']}</strong>,<br><br>
                     Your medical report is attached to this email.<br>
                     <strong>Result Status:</strong> " . ucfirst($result_status);

            // Send with attachment and request deletion after sending
            sendMail($appointment['email'], $subject, $body, $temp_file, true);
            
            // Note: File deletion is handled by the queue processor
        }

        header("Location: hospital_dashboard.php");
        exit();
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Result</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <div class="container" style="display: flex; justify-content: center; padding-top: 60px; padding-bottom: 60px;">
        <div class="auth-box animate-fade" style="width: 100%; max-width: 600px;">
            <h2 style="margin-bottom: 20px; text-align: center;">Update Patient Result</h2>
            
            <?php if(isset($error)) echo "<div class='badge badge-danger' style='width:100%; text-align:center; margin-bottom:20px;'>$error</div>"; ?>

            <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--glass-border);">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span style="color:var(--text-muted);">Patient Name</span>
                    <span style="font-weight:600;"><?php echo $appointment['pname']; ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span style="color:var(--text-muted);">Appointment Type</span>
                    <span class="badge badge-warning"><?php echo strtoupper($appointment['type']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-muted);">Scheduled Date</span>
                    <span><?php echo $appointment['appointment_date']; ?> <small style="color:var(--text-muted);"><?php echo $appointment['appointment_time']; ?></small></span>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="result">Result / Status</label>
                    <select name="result" id="result" required>
                        <option value="">-- Select Result --</option>
                        <?php if ($appointment['type'] == 'test'): ?>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                        <?php else: ?>
                            <option value="vaccinated">Vaccinated</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="remarks">Remarks (Optional)</label>
                    <textarea name="remarks" id="remarks" rows="4" placeholder="Enter any additional notes..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Update & Complete</button>
            </form>
        </div>
    </div>
</body>
</html>
