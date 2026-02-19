<?php
/**
 * Hospital Action Handler
 * - schedule: pending → approved (Scheduled)
 * - complete: approved → completed + PDF report email
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
require_once 'csrf_helper.php';

// Email is now sent directly via SMTP (no background queue needed)

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hospital') {
    header('Location: login.php');
    exit();
}

$hospital_id = $_SESSION['user_id'];
$action      = $_POST['action']  ?? '';
$appt_id     = intval($_POST['appt_id'] ?? 0);
$result_note = trim($_POST['result_note'] ?? '');

// CSRF check
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}

if (!$appt_id || !in_array($action, ['schedule', 'complete', 'reject'])) {
    header('Location: hospital_dashboard.php');
    exit();
}

try {
    // Fetch appointment (must belong to this hospital)
    $stmt = $pdo->prepare("
        SELECT a.*, 
               p.name as patient_name, p.email as patient_email, p.phone as patient_phone,
               h.name as hospital_name, h.location as hospital_location,
               v.name as vaccine_name
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        JOIN users h ON a.hospital_id = h.id
        LEFT JOIN vaccines v ON a.vaccine_id = v.id
        WHERE a.id = ? AND a.hospital_id = ?
    ");
    $stmt->execute([$appt_id, $hospital_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        die('Appointment not found or access denied.');
    }

    // ── SCHEDULE (pending → approved) ──────────────────────────────
    if ($action === 'schedule' && $appt['status'] === 'pending') {
        $pdo->prepare("UPDATE appointments SET status = 'approved' WHERE id = ? AND hospital_id = ?")
            ->execute([$appt_id, $hospital_id]);

        // Email patient — plain confirmation, NO PDF
        require_once 'mail_helper.php';

        $appt_date_fmt = date('l, d F Y', strtotime($appt['appointment_date']));
        $appt_time_fmt = date('h:i A', strtotime($appt['appointment_time']));
        $type_label    = $appt['type'] === 'vaccination' ? 'Vaccination' : 'COVID-19 Test';

        $subject = "✅ Appointment Confirmed – MediCore";
        $body = "
            Dear <strong>{$appt['patient_name']}</strong>,<br><br>
            Great news! Your appointment has been <strong style='color:#3b82f6;'>Confirmed</strong> by
            <strong>{$appt['hospital_name']}</strong>.<br><br>

            <div style='background:#eff6ff;border-left:4px solid #3b82f6;padding:16px 20px;border-radius:6px;margin:10px 0;'>
                <p style='margin:0 0 12px 0;font-size:15px;font-weight:700;color:#1e3a5f;'>📋 Appointment Details</p>
                <table style='border-collapse:collapse;width:100%;font-size:14px;'>
                    <tr>
                        <td style='padding:7px 0;color:#555;width:40%;'>📅 <strong>Date</strong></td>
                        <td style='padding:7px 0;font-weight:700;color:#1e3a5f;'>{$appt_date_fmt}</td>
                    </tr>
                    <tr style='background:#dbeafe22;'>
                        <td style='padding:7px 0;color:#555;'>⏰ <strong>Time</strong></td>
                        <td style='padding:7px 0;font-weight:700;color:#1e3a5f;'>{$appt_time_fmt}</td>
                    </tr>
                    <tr>
                        <td style='padding:7px 0;color:#555;'>🏥 <strong>Hospital</strong></td>
                        <td style='padding:7px 0;font-weight:600;'>{$appt['hospital_name']}</td>
                    </tr>
                    <tr style='background:#dbeafe22;'>
                        <td style='padding:7px 0;color:#555;'>📍 <strong>Location</strong></td>
                        <td style='padding:7px 0;font-weight:600;'>{$appt['hospital_location']}</td>
                    </tr>
                    <tr>
                        <td style='padding:7px 0;color:#555;'>🔬 <strong>Type</strong></td>
                        <td style='padding:7px 0;font-weight:600;'>{$type_label}</td>
                    </tr>
                    " . ($appt['vaccine_name'] ? "
                    <tr style='background:#dbeafe22;'>
                        <td style='padding:7px 0;color:#555;'>💉 <strong>Vaccine</strong></td>
                        <td style='padding:7px 0;font-weight:600;'>{$appt['vaccine_name']}</td>
                    </tr>" : "") . "
                </table>
            </div>

            <div style='background:#fefce8;border:1px solid #fde68a;padding:12px 16px;border-radius:6px;margin:14px 0;font-size:13px;color:#78350f;'>
                ⚠️ <strong>Important Reminders:</strong><br>
                • Please arrive <strong>10–15 minutes early</strong> at the hospital.<br>
                • Bring a valid <strong>CNIC / ID card</strong>.<br>
                " . ($appt['type'] === 'vaccination' ? "• Wear comfortable clothing for easy vaccine administration.<br>" : "• Avoid eating 2 hours before the test if instructed.<br>") . "
                • Keep this email as your appointment reference.
            </div>

            <p style='color:#555;font-size:13px;'>If you need to reschedule or have any questions, please contact the hospital directly.</p>
            <em style='color:#888;font-size:12px;'>MediCore Health System – Automated Notification</em>
        ";

        // Send email directly via SMTP (instant)
        sendMail($appt['patient_email'], $subject, $body);

        header('Location: hospital_dashboard.php?msg=scheduled&id=' . $appt_id);
        exit();
    }

    // ── COMPLETE (approved → completed) + PDF Report ────────────────
    if ($action === 'complete' && $appt['status'] === 'approved') {
        // Update status and result
        $pdo->prepare("UPDATE appointments SET status = 'completed', result = ? WHERE id = ? AND hospital_id = ?")
            ->execute([$result_note ?: 'Completed successfully', $appt_id, $hospital_id]);

        require_once 'mail_helper.php';

        // ── Auto-generate 120-word professional remarks based on type ──
        $auto_remarks = generateAutoRemarks($appt, $result_note);

        // Generate PDF report with auto remarks
        $pdf_path = generateAppointmentReport($appt, $result_note, $hospital_id, $auto_remarks);

        // Email patient with PDF
        $type_label    = $appt['type'] === 'vaccination' ? 'Vaccination' : 'COVID-19 Test';
        $appt_date_fmt = date('d M Y', strtotime($appt['appointment_date']));

        $subject = "📄 Your Medical Report is Ready – MediCore";
        $body = "
            Dear <strong>{$appt['patient_name']}</strong>,<br><br>
            Your <strong>{$type_label}</strong> appointment at <strong>{$appt['hospital_name']}</strong>
            has been <strong style='color:#10b981;'>Completed</strong> successfully.<br><br>

            <div style='background:#f0fdf4;border-left:4px solid #10b981;padding:16px 20px;border-radius:6px;margin:10px 0;'>
                <p style='margin:0 0 12px 0;font-size:15px;font-weight:700;color:#064e3b;'>📋 Appointment Summary</p>
                <table style='border-collapse:collapse;width:100%;font-size:14px;'>
                    <tr>
                        <td style='padding:7px 0;color:#555;width:40%;'>📅 <strong>Date</strong></td>
                        <td style='padding:7px 0;font-weight:700;color:#064e3b;'>{$appt_date_fmt}</td>
                    </tr>
                    <tr style='background:#dcfce722;'>
                        <td style='padding:7px 0;color:#555;'>🏥 <strong>Hospital</strong></td>
                        <td style='padding:7px 0;font-weight:600;'>{$appt['hospital_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding:7px 0;color:#555;'>🔬 <strong>Type</strong></td>
                        <td style='padding:7px 0;font-weight:600;'>{$type_label}</td>
                    </tr>
                    " . ($appt['vaccine_name'] ? "
                    <tr style='background:#dcfce722;'>
                        <td style='padding:7px 0;color:#555;'>💉 <strong>Vaccine</strong></td>
                        <td style='padding:7px 0;font-weight:600;'>{$appt['vaccine_name']}</td>
                    </tr>" : "") . "
                    <tr>
                        <td style='padding:7px 0;color:#555;'>📋 <strong>Result</strong></td>
                        <td style='padding:7px 0;font-weight:600;color:#10b981;'>" . htmlspecialchars($result_note ?: 'Completed successfully') . "</td>
                    </tr>
                </table>
            </div>

            <div style='background:#fafafa;border:1px solid #e5e7eb;padding:14px 18px;border-radius:6px;margin:14px 0;font-size:13px;color:#374151;'>
                📎 <strong>Your detailed medical report (PDF) is attached</strong> to this email.<br>
                Please download and keep it for your records.
            </div>

            <em style='color:#888;font-size:12px;'>MediCore Health System – Automated Notification</em>
        ";

        // Send email with PDF directly via SMTP (instant)
        sendMail($appt['patient_email'], $subject, $body, $pdf_path, true);

        header('Location: hospital_dashboard.php?msg=completed&id=' . $appt_id);
        exit();
    }

    // ── REJECT (pending → rejected) ────────────────────────────────
    if ($action === 'reject' && $appt['status'] === 'pending') {
        $pdo->prepare("UPDATE appointments SET status = 'rejected', result = ? WHERE id = ? AND hospital_id = ?")
            ->execute([$result_note ?: 'Rejected by hospital', $appt_id, $hospital_id]);

        require_once 'mail_helper.php';
        $subject = "Appointment Update – MediCore";
        $body = "
            Dear <strong>{$appt['patient_name']}</strong>,<br><br>
            We regret to inform you that your appointment request at <strong>{$appt['hospital_name']}</strong> has been <strong style='color:#ef4444;'>Rejected</strong>.<br><br>
            " . ($result_note ? "<strong>Reason:</strong> " . htmlspecialchars($result_note) . "<br><br>" : "") . "
            You may book a new appointment at a different time or hospital.<br><br>
            <em style='color:#888;'>MediCore Health System</em>
        ";
        // Send email directly via SMTP (instant)
        sendMail($appt['patient_email'], $subject, $body);

        header('Location: hospital_dashboard.php?msg=rejected');
        exit();
    }

    header('Location: hospital_dashboard.php');
    exit();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// ── Auto-generate 120-word professional remarks ──────────────────────
function generateAutoRemarks($appt, $result_note) {
    $patient = $appt['patient_name'];
    $hospital = $appt['hospital_name'];
    $date = date('d M Y', strtotime($appt['appointment_date']));
    $result = $result_note ?: 'Completed successfully';

    if ($appt['type'] === 'vaccination') {
        $vaccine = $appt['vaccine_name'] ?? 'the prescribed vaccine';
        return "This is to certify that {$patient} successfully received the {$vaccine} vaccination at {$hospital} on {$date}. "
             . "The vaccination procedure was carried out by qualified medical personnel following all standard safety protocols and WHO guidelines. "
             . "The patient was observed for the mandatory post-vaccination monitoring period and showed no immediate adverse reactions. "
             . "Outcome: {$result}. "
             . "The patient is advised to monitor for any delayed reactions over the next 48–72 hours and to contact the hospital immediately if any unusual symptoms arise. "
             . "This certificate serves as an official record of vaccination administered under the MediCore Health System.";
    } else {
        // COVID Test or general test
        return "This report certifies that {$patient} underwent a COVID-19 diagnostic test at {$hospital} on {$date}. "
             . "The test was conducted by certified laboratory staff in accordance with national health authority protocols and standard biosafety procedures. "
             . "All necessary precautions were taken to ensure accuracy and reliability of the test results. "
             . "Test Outcome: {$result}. "
             . "Based on the result, the patient is advised to follow the recommended health guidelines issued by the relevant health authority. "
             . "If the result is positive, immediate self-isolation and medical consultation are strongly recommended. "
             . "This document is an official report generated by MediCore Health System.";
    }
}

// ── PDF Report Generator (FPDF) ─────────────────────────────────────
function generateAppointmentReport($appt, $result_note, $hospital_id, $auto_remarks = '') {
    require_once __DIR__ . '/fpdf/fpdf.php';

    $is_vacc     = $appt['type'] === 'vaccination';
    $type_label  = $is_vacc ? 'Vaccination Certificate' : 'COVID-19 Test Report';
    $date_str    = date('d M Y', strtotime($appt['appointment_date']));
    $time_str    = date('h:i A', strtotime($appt['appointment_time']));
    $result_text = $result_note ?: 'Completed successfully';
    $gen_date    = date('d M Y, h:i A');
    $report_id   = 'MC-' . strtoupper(substr(md5($appt['id'] . $hospital_id), 0, 8));

    // ── Color Palette ────────────────────────────────────────────────
    $p_r = 10;  $p_g = 18;  $p_b = 40;          // Primary dark navy
    $a_r = $is_vacc ? 5   : 37;                  // Accent
    $a_g = $is_vacc ? 150 : 99;
    $a_b = $is_vacc ? 105 : 235;
    $al_r = $is_vacc ? 236 : 239;                // Accent light (alt row bg)
    $al_g = $is_vacc ? 253 : 246;
    $al_b = $is_vacc ? 245 : 255;
    $lb_r = 107; $lb_g = 114; $lb_b = 128;       // Label grey
    $tx_r = 17;  $tx_g = 24;  $tx_b = 39;        // Text dark

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 22);

    $pw = $pdf->GetPageWidth();
    $ph = $pdf->GetPageHeight();
    $ml = 18; $mr = 18;
    $cw = $pw - $ml - $mr;

    // ── LEFT ACCENT SIDEBAR STRIP ────────────────────────────────────
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect(0, 0, 5, $ph, 'F');

    // ── HEADER BACKGROUND ────────────────────────────────────────────
    $pdf->SetFillColor($p_r, $p_g, $p_b);
    $pdf->Rect(0, 0, $pw, 54, 'F');

    // Accent bottom line on header
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect(0, 52, $pw, 2.5, 'F');

    // ── HEADER: Logo square (M badge) ───────────────────────────────
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect($ml, 11, 11, 11, 'F');
    $pdf->SetXY($ml, 13);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(11, 6, 'M', 0, 0, 'C');

    // System name & tagline
    $pdf->SetXY($ml + 14, 11);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(90, 6, 'MediCore Health System', 0, 1);
    $pdf->SetX($ml + 14);
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(160, 190, 220);
    $pdf->Cell(90, 5, 'Official Medical Documentation', 0, 1);

    // ── HEADER: Document title ───────────────────────────────────────
    $pdf->SetXY($ml, 29);
    $pdf->SetFont('Helvetica', 'B', 19);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($cw, 10, strtoupper($type_label), 0, 1, 'L');

    $pdf->SetX($ml);
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(160, 185, 215);
    $pdf->Cell($cw * 0.5, 5, 'Generated: ' . $gen_date, 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(200, 220, 255);
    $pdf->Cell($cw * 0.5, 5, 'Report ID: ' . $report_id, 0, 1, 'R');

    // ── TYPE BADGE (top-right) ───────────────────────────────────────
    $badge_label = $is_vacc ? 'VACCINATION' : 'COVID-19 TEST';
    $badge_w = 44;
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect($pw - $mr - $badge_w, 7, $badge_w, 10, 'F');
    $pdf->SetXY($pw - $mr - $badge_w, 9);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($badge_w, 5, $badge_label, 0, 0, 'C');

    $pdf->SetY(62);

    // ── HELPER: Section Header ───────────────────────────────────────
    $drawSection = function($title) use ($pdf, $ml, $cw, $a_r, $a_g, $a_b, $p_r, $p_g, $p_b) {
        $pdf->Ln(2);
        // Grey bg bar
        $pdf->SetFillColor(243, 245, 249);
        $pdf->SetDrawColor(220, 226, 236);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($ml, $pdf->GetY(), $cw, 9, 'DF');
        // Left accent tick
        $pdf->SetFillColor($a_r, $a_g, $a_b);
        $pdf->Rect($ml, $pdf->GetY(), 4, 9, 'F');
        // Title
        $pdf->SetXY($ml + 7, $pdf->GetY() + 2);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor($p_r, $p_g, $p_b);
        $pdf->Cell($cw - 7, 5, strtoupper($title), 0, 1);
        $pdf->Ln(1);
    };

    // ── HELPER: Info Row ─────────────────────────────────────────────
    $rowIdx = [0];
    $drawRow = function($label, $value) use ($pdf, $ml, $cw, $lb_r, $lb_g, $lb_b, $tx_r, $tx_g, $tx_b, $al_r, $al_g, $al_b, &$rowIdx) {
        if ($rowIdx[0] % 2 === 1) {
            $pdf->SetFillColor($al_r, $al_g, $al_b);
            $pdf->Rect($ml, $pdf->GetY(), $cw, 8.5, 'F');
        }
        $pdf->SetX($ml + 5);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor($lb_r, $lb_g, $lb_b);
        $pdf->Cell($cw * 0.36, 8.5, $label, 0, 0);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->SetTextColor($tx_r, $tx_g, $tx_b);
        $pdf->Cell($cw * 0.64, 8.5, $value, 0, 1);
        $pdf->SetDrawColor(228, 233, 242);
        $pdf->SetLineWidth(0.15);
        $pdf->Line($ml, $pdf->GetY(), $ml + $cw, $pdf->GetY());
        $rowIdx[0]++;
    };

    // ── PATIENT INFORMATION ──────────────────────────────────────────
    $drawSection('Patient Information');
    $rowIdx[0] = 0;
    $drawRow('Full Name',  $appt['patient_name']);
    $drawRow('Email',      $appt['patient_email']);
    $drawRow('Phone',      $appt['patient_phone'] ?? 'N/A');

    // ── APPOINTMENT DETAILS ──────────────────────────────────────────
    $drawSection('Appointment Details');
    $rowIdx[0] = 0;
    $drawRow('Hospital',   $appt['hospital_name']);
    $drawRow('Location',   $appt['hospital_location'] ?? 'N/A');
    $drawRow('Date',       $date_str);
    $drawRow('Time',       $time_str);
    $drawRow('Type',       $is_vacc ? 'Vaccination' : 'COVID-19 Test');
    if (!empty($appt['vaccine_name'])) {
        $drawRow('Vaccine', $appt['vaccine_name']);
    }

    // ── RESULT BANNER ────────────────────────────────────────────────
    $drawSection('Result / Outcome');
    $pdf->Ln(2);

    $box_y = $pdf->GetY();
    // Outer box
    $pdf->SetFillColor($al_r, $al_g, $al_b);
    $pdf->SetDrawColor($a_r, $a_g, $a_b);
    $pdf->SetLineWidth(0.8);
    $pdf->Rect($ml, $box_y, $cw, 18, 'DF');
    // Left solid accent strip
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect($ml, $box_y, 7, 18, 'F');
    // Label
    $pdf->SetXY($ml + 10, $box_y + 2.5);
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor($lb_r, $lb_g, $lb_b);
    $pdf->Cell(40, 4, 'OUTCOME', 0, 1);
    // Value
    $pdf->SetX($ml + 10);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetTextColor($a_r, $a_g, $a_b);
    $pdf->Cell($cw - 14, 8, $result_text, 0, 1);
    $pdf->Ln(4);

    // ── PROFESSIONAL REMARKS ─────────────────────────────────────────
    if (!empty($auto_remarks)) {
        $drawSection('Professional Remarks');
        $pdf->Ln(2);

        $rem_y = $pdf->GetY();
        $pdf->SetFillColor(250, 251, 253);
        $pdf->SetDrawColor(210, 218, 230);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect($ml, $rem_y, $cw, 44, 'DF');
        // Left accent strip
        $pdf->SetFillColor($a_r, $a_g, $a_b);
        $pdf->Rect($ml, $rem_y, 4, 44, 'F');
        // Text
        $pdf->SetXY($ml + 7, $rem_y + 3);
        $pdf->SetFont('Helvetica', 'I', 8.5);
        $pdf->SetTextColor($tx_r, $tx_g, $tx_b);
        $pdf->MultiCell($cw - 10, 5.8, $auto_remarks, 0, 'L');
        $pdf->Ln(3);
    }

    // ── OFFICIAL STAMP ───────────────────────────────────────────────
    $pdf->Ln(5);
    $stamp_y = $pdf->GetY();
    $stamp_x = ($pw / 2) - 32;

    // Outer double-border stamp
    $pdf->SetDrawColor($a_r, $a_g, $a_b);
    $pdf->SetLineWidth(1.8);
    $pdf->Rect($stamp_x, $stamp_y, 64, 20, 'D');
    $pdf->SetLineWidth(0.5);
    $pdf->Rect($stamp_x + 2, $stamp_y + 2, 60, 16, 'D');

    // Stamp fill
    $pdf->SetFillColor($al_r, $al_g, $al_b);
    $pdf->Rect($stamp_x + 2.1, $stamp_y + 2.1, 59.8, 15.8, 'F');

    // Stamp text
    $pdf->SetXY($stamp_x, $stamp_y + 4);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetTextColor($a_r, $a_g, $a_b);
    $pdf->Cell(64, 7, 'COMPLETED', 0, 1, 'C');
    $pdf->SetX($stamp_x);
    $pdf->SetFont('Helvetica', '', 6.5);
    $pdf->SetTextColor($lb_r, $lb_g, $lb_b);
    $pdf->Cell(64, 5, 'MediCore Health System', 0, 1, 'C');

    // ── FOOTER ───────────────────────────────────────────────────────
    $footer_y = $ph - 22;
    // Top accent line
    $pdf->SetDrawColor($a_r, $a_g, $a_b);
    $pdf->SetLineWidth(0.7);
    $pdf->Line($ml, $footer_y, $ml + $cw, $footer_y);
    $pdf->SetDrawColor(210, 218, 230);
    $pdf->SetLineWidth(0.2);
    $pdf->Line($ml, $footer_y + 1.2, $ml + $cw, $footer_y + 1.2);

    $pdf->SetY($footer_y + 3);
    $pdf->SetX($ml);
    $pdf->SetFont('Helvetica', 'B', 7);
    $pdf->SetTextColor($tx_r, $tx_g, $tx_b);
    $pdf->Cell($cw * 0.65, 4, 'This is an official document generated by MediCore Health System.', 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor($lb_r, $lb_g, $lb_b);
    $pdf->Cell($cw * 0.35, 4, 'Report ID: ' . $report_id, 0, 1, 'R');

    $pdf->SetX($ml);
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor($lb_r, $lb_g, $lb_b);
    $pdf->Cell($cw * 0.65, 4, 'Generated: ' . $gen_date . '  |  For queries, contact your hospital.', 0, 0, 'L');
    $pdf->Cell($cw * 0.35, 4, 'Page 1 of 1', 0, 1, 'R');

    // Bottom dark bar
    $pdf->SetFillColor($p_r, $p_g, $p_b);
    $pdf->Rect(0, $ph - 7, $pw, 7, 'F');
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect(0, $ph - 7, 5, 7, 'F');

    // ── SAVE ─────────────────────────────────────────────────────────
    $pdf_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'medicore_report_' . $appt['id'] . '_' . time() . '.pdf';
    $pdf->Output('F', $pdf_file);

    return $pdf_file;
}
?>
