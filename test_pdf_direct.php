<?php
require_once __DIR__ . '/FPDF/fpdf.php';

// ── Copy of generateAppointmentReport() for isolated test ────────────
function generateAppointmentReport($appt, $result_note, $hospital_id, $auto_remarks = '') {

    $is_vacc     = $appt['type'] === 'vaccination';
    $type_label  = $is_vacc ? 'Vaccination Certificate' : 'COVID-19 Test Report';
    $date_str    = date('d M Y', strtotime($appt['appointment_date']));
    $time_str    = date('h:i A', strtotime($appt['appointment_time']));
    $result_text = $result_note ?: 'Completed successfully';
    $gen_date    = date('d M Y, h:i A');
    $report_id   = 'MC-' . strtoupper(substr(md5($appt['id'] . $hospital_id), 0, 8));

    // Color Palette
    $p_r = 10;  $p_g = 18;  $p_b = 40;
    $a_r = $is_vacc ? 5   : 37;
    $a_g = $is_vacc ? 150 : 99;
    $a_b = $is_vacc ? 105 : 235;
    $al_r = $is_vacc ? 236 : 239;
    $al_g = $is_vacc ? 253 : 246;
    $al_b = $is_vacc ? 245 : 255;
    $lb_r = 107; $lb_g = 114; $lb_b = 128;
    $tx_r = 17;  $tx_g = 24;  $tx_b = 39;

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 22);

    $pw = $pdf->GetPageWidth();
    $ph = $pdf->GetPageHeight();
    $ml = 18; $mr = 18;
    $cw = $pw - $ml - $mr;

    // LEFT ACCENT SIDEBAR
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect(0, 0, 5, $ph, 'F');

    // HEADER BG
    $pdf->SetFillColor($p_r, $p_g, $p_b);
    $pdf->Rect(0, 0, $pw, 54, 'F');

    // Accent line at bottom of header
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect(0, 52, $pw, 2.5, 'F');

    // Logo circle (using Rect as circle substitute)
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

    // Document title
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

    // TYPE BADGE (top-right)
    $badge_label = $is_vacc ? 'VACCINATION' : 'COVID-19 TEST';
    $badge_w = 44;
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect($pw - $mr - $badge_w, 7, $badge_w, 10, 'F');
    $pdf->SetXY($pw - $mr - $badge_w, 9);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($badge_w, 5, $badge_label, 0, 0, 'C');

    $pdf->SetY(62);

    // HELPER: Section Header
    $drawSection = function($title) use ($pdf, $ml, $cw, $a_r, $a_g, $a_b, $p_r, $p_g, $p_b) {
        $pdf->Ln(2);
        $pdf->SetFillColor(243, 245, 249);
        $pdf->SetDrawColor(220, 226, 236);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($ml, $pdf->GetY(), $cw, 9, 'DF');
        $pdf->SetFillColor($a_r, $a_g, $a_b);
        $pdf->Rect($ml, $pdf->GetY(), 4, 9, 'F');
        $pdf->SetXY($ml + 7, $pdf->GetY() + 2);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor($p_r, $p_g, $p_b);
        $pdf->Cell($cw - 7, 5, strtoupper($title), 0, 1);
        $pdf->Ln(1);
    };

    // HELPER: Info Row (alternating shading)
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

    // PATIENT INFORMATION
    $drawSection('Patient Information');
    $rowIdx[0] = 0;
    $drawRow('Full Name',  $appt['patient_name']);
    $drawRow('Email',      $appt['patient_email']);
    $drawRow('Phone',      $appt['patient_phone'] ?? 'N/A');
    $pdf->Ln(4);

    // APPOINTMENT DETAILS
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
    $pdf->Ln(4);

    // RESULT BANNER
    $drawSection('Result / Outcome');
    $pdf->Ln(2);

    $box_y = $pdf->GetY();
    $pdf->SetFillColor($al_r, $al_g, $al_b);
    $pdf->SetDrawColor($a_r, $a_g, $a_b);
    $pdf->SetLineWidth(0.8);
    $pdf->Rect($ml, $box_y, $cw, 18, 'DF');
    // Left accent strip
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

    // PROFESSIONAL REMARKS
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

    // OFFICIAL STAMP
    $pdf->Ln(5);
    $stamp_y = $pdf->GetY();
    $stamp_x = ($pw / 2) - 32;

    $pdf->SetDrawColor($a_r, $a_g, $a_b);
    $pdf->SetLineWidth(1.8);
    $pdf->Rect($stamp_x, $stamp_y, 64, 20, 'D');
    $pdf->SetLineWidth(0.5);
    $pdf->Rect($stamp_x + 2, $stamp_y + 2, 60, 16, 'D');
    $pdf->SetFillColor($al_r, $al_g, $al_b);
    $pdf->Rect($stamp_x + 2.1, $stamp_y + 2.1, 59.8, 15.8, 'F');

    $pdf->SetXY($stamp_x, $stamp_y + 4);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetTextColor($a_r, $a_g, $a_b);
    $pdf->Cell(64, 7, 'COMPLETED', 0, 1, 'C');
    $pdf->SetX($stamp_x);
    $pdf->SetFont('Helvetica', '', 6.5);
    $pdf->SetTextColor($lb_r, $lb_g, $lb_b);
    $pdf->Cell(64, 5, 'MediCore Health System', 0, 1, 'C');

    // FOOTER
    $footer_y = $ph - 22;
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
    $pdf->Cell($cw * 0.65, 4, 'Generated: ' . $gen_date . '  |  For queries, contact your hospital.', 0, 0, 'L');
    $pdf->Cell($cw * 0.35, 4, 'Page 1 of 1', 0, 1, 'R');

    // Bottom dark bar
    $pdf->SetFillColor($p_r, $p_g, $p_b);
    $pdf->Rect(0, $ph - 7, $pw, 7, 'F');
    $pdf->SetFillColor($a_r, $a_g, $a_b);
    $pdf->Rect(0, $ph - 7, 5, 7, 'F');

    // Output to browser
    $pdf->Output('I', 'medicore_report_test.pdf');
    exit;
}

// Test data
$appt = [
    'id'                => 99,
    'patient_name'      => 'Ali Hassan',
    'patient_email'     => 'ali@example.com',
    'patient_phone'     => '0300-1234567',
    'hospital_name'     => 'City General Hospital',
    'hospital_location' => 'Peshawar, KPK',
    'appointment_date'  => '2026-02-18',
    'appointment_time'  => '10:30:00',
    'type'              => 'vaccination',
    'vaccine_name'      => 'Pfizer-BioNTech COVID-19',
];
$result_note  = 'Vaccination administered successfully. No adverse reactions observed.';
$auto_remarks = 'This is to certify that Ali Hassan successfully received the Pfizer-BioNTech COVID-19 vaccination at City General Hospital on 18 Feb 2026. The vaccination procedure was carried out by qualified medical personnel following all standard safety protocols and WHO guidelines. The patient was observed for the mandatory post-vaccination monitoring period and showed no immediate adverse reactions. Outcome: Completed. The patient is advised to monitor for any delayed reactions over the next 48-72 hours and to contact the hospital immediately if any unusual symptoms arise. This certificate serves as an official record of vaccination administered under the MediCore Health System.';

generateAppointmentReport($appt, $result_note, 1, $auto_remarks);
?>
