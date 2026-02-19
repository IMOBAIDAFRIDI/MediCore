<?php
require_once __DIR__ . '/FPDF/fpdf.php';
require_once __DIR__ . '/db_connect_pdo.php';

// Branding extraction
function getDominantColor($imagePath) {
    if (empty($imagePath)) return [0, 0, 0];
    if (!file_exists($imagePath)) return [0, 0, 0];
    try {
        $type = @exif_imagetype($imagePath);
        $img = null;
        if ($type === IMAGETYPE_JPEG) $img = @imagecreatefromjpeg($imagePath);
        elseif ($type === IMAGETYPE_PNG) $img = @imagecreatefrompng($imagePath);
        if (!$img) return [0, 0, 0];
        $width = imagesx($img);
        $height = imagesy($img);
        $step = 5; $colors = [];
        for($x = 0; $x < $width; $x += $step) {
            for($y = 0; $y < $height; $y += $step) {
                $rgb = imagecolorat($img, $x, $y);
                $cols = imagecolorsforindex($img, $rgb);
                if (isset($cols['alpha']) && $cols['alpha'] > 120) continue;
                if ($cols['red'] > 230 && $cols['green'] > 230 && $cols['blue'] > 230) continue;
                $r = round($cols['red'] / 15) * 15; $g = round($cols['green'] / 15) * 15; $b = round($cols['blue'] / 15) * 15;
                $key = "$r,$g,$b";
                if (!isset($colors[$key])) $colors[$key] = 0;
                $colors[$key]++;
            }
        }
        imagedestroy($img);
        if (empty($colors)) return [0, 0, 0];
        arsort($colors);
        $topKey = array_key_first($colors);
        $parts = explode(',', $topKey);
        return [(int)$parts[0], (int)$parts[1], (int)$parts[2]];
    } catch (Exception $e) { return [0,0,0]; }
}

function getImageBackgroundColor($imagePath) {
    if (empty($imagePath) || !file_exists($imagePath)) return [255, 255, 255];
    try {
        $type = @exif_imagetype($imagePath); $img = null;
        if ($type === IMAGETYPE_JPEG) $img = @imagecreatefromjpeg($imagePath);
        elseif ($type === IMAGETYPE_PNG) $img = @imagecreatefrompng($imagePath);
        if (!$img) return [255, 255, 255];
        $rgb = imagecolorat($img, 0, 0);
        $cols = imagecolorsforindex($img, $rgb);
        return [$cols['red'], $cols['green'], $cols['blue']];
    } catch (Exception $e) { return [255, 255, 255]; }
}

class ReportPDF extends FPDF {
    public $hospitalData;
    public $accentColor;
    public $bgColor;
    public $textColor;
    public $localLogoPath = null;
    
    public function __construct($data) {
        parent::__construct();
        $this->hospitalData = $data;
        if (!empty($data['image'])) {
            $path = __DIR__ . '/' . $data['image'];
            if (file_exists($path)) $this->localLogoPath = $path;
        }
        $this->accentColor = $this->localLogoPath ? getDominantColor($this->localLogoPath) : [13, 110, 253];
        $this->bgColor = $this->localLogoPath ? getImageBackgroundColor($this->localLogoPath) : [255, 255, 255];
        $lum = (0.2126*$this->bgColor[0]) + (0.7152*$this->bgColor[1]) + (0.0722*$this->bgColor[2]);
        $this->textColor = ($lum < 140) ? [255, 255, 255] : [0, 0, 0];
    }
    
    function Header() {
        $this->SetFillColor($this->bgColor[0], $this->bgColor[1], $this->bgColor[2]);
        $this->Rect(0, 0, 210, 297, 'F');
        $logo_x = 10; $logo_y = 10; $logo_w = 30; $text_x_offset = 12;
        if ($this->localLogoPath) {
            $this->Image($this->localLogoPath, $logo_x, $logo_y, $logo_w);
            $text_x_offset = $logo_x + $logo_w + 5;
        }
        $this->SetXY($text_x_offset, $logo_y + 4); 
        $this->SetFont('Arial', 'B', 26); 
        $this->SetTextColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
        $this->Cell(0, 20, strtoupper($this->hospitalData['hname']), 0, 1, 'L');
        $this->Ln(15);
        $lineColor = ($this->textColor[0] == 255) ? 200 : 100; 
        $this->SetDrawColor($lineColor, $lineColor, $lineColor);
        $this->Rect(10, 50, 190, 30);
        $this->SetTextColor($this->textColor[0], $this->textColor[1], $this->textColor[2]);
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY(15, 55);
        $this->Cell(30, 6, 'Patient Name:', 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell(70, 6, strtoupper($this->hospitalData['pname']), 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(20, 6, 'Date:', 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell(50, 6, $this->hospitalData['appointment_date'], 0, 1);
        $this->SetX(15);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(30, 6, 'Lab No:', 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell(70, 6, str_pad($this->hospitalData['id'], 8, '0', STR_PAD_LEFT), 0, 0);
        $this->SetY(90);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, $this->hospitalData['hlocation'] . ' | ' . $this->hospitalData['hemail'], 0, 1, 'C');
        $this->SetY(-10);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - Printed on: ' . date('d-m-Y H:i:s'), 0, 0, 'R');
    }
}

function generateReportPDF($id, $mode = 'F') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT a.*, p.name as pname, h.name as hname, h.email as hemail, h.image, h.location as hlocation 
                          FROM appointments a 
                          JOIN users p ON a.patient_id = p.id 
                          JOIN users h ON a.hospital_id = h.id 
                          WHERE a.id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) return false;
    $pdf = new ReportPDF($data);
    $pdf->AliasNbPages(); $pdf->AddPage();
    $pdf->SetY(85);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor($pdf->textColor[0], $pdf->textColor[1], $pdf->textColor[2]);
    $pdf->Cell(0, 10, 'CLINICAL PATHOLOGY REPORT', 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(60, 8, 'Test Name', 1, 0, 'L');
    $pdf->Cell(40, 8, 'Result', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Unit', 1, 0, 'C');
    $pdf->Cell(60, 8, 'Normal Range', 1, 1, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(60, 10, strtoupper($data['type']), 1, 0, 'L');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(40, 10, strtoupper($data['result']), 1, 0, 'C');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(30, 10, '-', 1, 0, 'C');
    $pdf->Cell(60, 10, 'Negative', 1, 1, 'L');
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 7, 'Remarks / Comments:', 0, 1);
    $pdf->SetFont('Arial', '', 9);
    $pdf->MultiCell(0, 5, $data['remarks'], 0, 'L');
    if ($mode == 'F') {
        $filename = 'Report_' . $id . '_' . time() . '.pdf';
        $path = 'uploads/' . $filename;
        if (!is_dir('uploads/')) mkdir('uploads/', 0777, true);
        $pdf->Output('F', $path);
        return $path;
    } else {
        $pdf->Output($mode, 'Report_' . $id . '.pdf');
    }
}
?>
