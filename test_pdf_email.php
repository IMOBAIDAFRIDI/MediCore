<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db_connect_pdo.php';
include 'mail_helper.php';
require('FPDF/fpdf.php');

echo "Starting PDF Email Test...\n";

// 1. Generate Dummy PDF
// 1. Generate Dummy PDF using the new Advanced Style
// 1. Generate Dummy PDF using the new Reference Design
class PDF extends FPDF {
    protected $hospitalName = 'Test Hospital Center';
    protected $hospitalAddress = '123 Medical Drive, Health City';
    protected $hospitalEmail = 'contact@test.com';

    function Header() {
        // 1. Left Side: Hospital Info
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(13, 110, 253); // Blue
        $this->Cell(100, 10, strtoupper($this->hospitalName), 0, 1, 'L');
        
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(100, 5, $this->hospitalAddress, 0, 1, 'L');
        $this->Cell(100, 5, $this->hospitalEmail, 0, 1, 'L');
        
        // 2. Right Side: Patient Info Box
        $boxX = 110; $boxY = 10; $boxWidth = 90; $boxHeight = 35;
        $this->SetDrawColor(0, 0, 0); $this->SetLineWidth(0.2);
        $this->Rect($boxX, $boxY, $boxWidth, $boxHeight);
        
        $this->SetXY($boxX + 2, $boxY + 2);
        $this->SetFont('Arial', 'B', 8); $this->Cell(15, 5, 'Lab No:', 0, 0);
        $this->SetFont('Arial', '', 8);  $this->Cell(30, 5, $GLOBALS['appt_id'], 0, 0);
        
        $this->SetFont('Arial', 'B', 8); $this->Cell(15, 5, 'Date:', 0, 0);
        $this->SetFont('Arial', '', 8);  $this->Cell(25, 5, date('d-m-Y'), 0, 1);
        
        $this->SetX($boxX + 2);
        $this->SetFont('Arial', 'B', 8); $this->Cell(15, 5, 'Name:', 0, 0);
        $this->SetFont('Arial', 'B', 9);  $this->Cell(70, 5, $GLOBALS['pname'], 0, 1);
        
        $this->SetX($boxX + 2);
        $this->SetFont('Arial', 'B', 8); $this->Cell(15, 5, 'Ref By:', 0, 0);
        $this->SetFont('Arial', '', 8);  $this->Cell(70, 5, $this->hospitalName, 0, 1);
        
        $this->SetY(55);
        $this->Line(10, 50, 200, 50);
    }

    function Footer() {
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(150, 150, 150);
        $this->RotatedText(8, 200, 'NOT VALID FOR MEDICO-LEGAL PURPOSE', 90);
        $this->SetY(-15);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(100, 10, 'This is a computer generated report.', 0, 0, 'L');
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

$GLOBALS['appt_id'] = '12345';
$GLOBALS['pname'] = 'TEST PATIENT';

$pdf = new PDF();
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
$pdf->Cell(60, 10, 'TEST TYPE', 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(40, 10, 'NEGATIVE', 1, 0, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(30, 10, '-', 1, 0, 'C');
$pdf->Cell(60, 10, 'Negative', 1, 1, 'L');

$temp_file = sys_get_temp_dir() . '/test_report.pdf';
$pdf->Output('F', $temp_file);

echo "PDF generated at: $temp_file\n";

// 2. Send Email with Attachment
$to = 'imobaidafridi@gmail.com'; // Using the email from previous context
$subject = "PDF Attachment Test";
$body = "Please find the attached PDF report.";

if (sendMail($to, $subject, $body, $temp_file)) {
    echo "Email with PDF SENT successfully.\n";
} else {
    echo "Email with PDF FAILED.\n";
}

// Cleanup
if (file_exists($temp_file)) {
    unlink($temp_file);
    echo "Temp file deleted.\n";
}
?>
