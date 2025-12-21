<?php
require __DIR__ . '/povezava.php';
require __DIR__ . '/lib/tfpdf/tfpdf.php';

// brez prijave ne izvažamo
if (!isset($_SESSION['user_id'])) {
  header('Location: prijava.php');
  exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$currentRole   = $_SESSION['user_vloga'] ?? null;

// kateri profil izvažamo (če ni id, izvozimo svojega)
$viewUserId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;

// brez prijave ne izvažamo
if (!isset($_SESSION['user_id'])) {
  header('Location: prijava.php');
  exit;
}

// najprej poiščemo id_zdravnik za tega uporabnika (enako kot v profilu)
$doctorId = null;
$check = $conn->prepare("SELECT id_zdravnik FROM zdravnik WHERE TK_uporabnik = ? LIMIT 1");
$check->bind_param('i', $viewUserId);
$check->execute();
$res = $check->get_result();
$docRow = $res->fetch_assoc();
$check->close();

if ($docRow) $doctorId = (int)$docRow['id_zdravnik'];

if (!$doctorId) {
  http_response_code(404);
  echo "Zdravnik ne obstaja.";
  exit;
}

// podatki zdravnika (join na uporabnik)
$stmt = $conn->prepare("
  SELECT
    d.id_zdravnik,
    d.naziv, d.telefon, d.klinika, d.bio,
    d.ulica, d.mesto, d.postaSt, d.country,
    d.spletnaStran,
    u.ime, u.priimek, u.email
  FROM zdravnik d
  JOIN uporabnik u ON u.id_uporabnik = d.TK_uporabnik
  WHERE d.id_zdravnik = ?
  LIMIT 1
");
$stmt->bind_param('i', $doctorId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc) {
  http_response_code(404);
  echo "Podatki zdravnika niso najdeni.";
  exit;
}

// specializacije
$specs = [];
$stSpec = $conn->prepare("
  SELECT s.naziv
  FROM specializacija_zdravnik sz
  JOIN specializacija s ON s.id_specializacija = sz.TK_specializacija
  WHERE sz.TK_zdravnik = ?
  ORDER BY s.naziv
");
$stSpec->bind_param('i', $doctorId);
$stSpec->execute();
$rSpec = $stSpec->get_result();
while ($row = $rSpec->fetch_assoc()) $specs[] = $row['naziv'];
$stSpec->close();

// ocene (zadnjih 20)
$ratings = [];
$stOcene = $conn->prepare("
  SELECT o.ocena, o.komentar, u.ime, u.priimek
  FROM ocene o
  JOIN uporabnik u ON u.id_uporabnik = o.TK_uporabnik
  WHERE o.TK_zdravnik = ?
  ORDER BY o.id_ocene DESC
  LIMIT 20
");
$stOcene->bind_param('i', $doctorId);
$stOcene->execute();
$rOc = $stOcene->get_result();
while ($row = $rOc->fetch_assoc()) $ratings[] = $row;
$stOcene->close();

// ===== PDF =====
$pdf = new tFPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 14);
$pdf->AddPage();

// UTF-8 font (DejaVu)
$pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
$pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);

$pdf->SetFont('DejaVu', 'B', 16);
$pdf->Cell(0, 10, "Profil zdravnika", 0, 1);

$pdf->SetFont('DejaVu', '', 11);
$fullName = trim(($doc['naziv'] ?? '') . ' ' . $doc['ime'] . ' ' . $doc['priimek']);
$pdf->Cell(0, 7, $fullName, 0, 1);

$pdf->SetFont('DejaVu', '', 10);
$pdf->Cell(0, 6, "Email: " . ($doc['email'] ?? '—'), 0, 1);
$pdf->Cell(0, 6, "Telefon: " . (!empty($doc['telefon']) ? $doc['telefon'] : '—'), 0, 1);
$pdf->Cell(0, 6, "Klinika: " . (!empty($doc['klinika']) ? $doc['klinika'] : '—'), 0, 1);
$pdf->Cell(0, 6, "Spletna stran: " . (!empty($doc['spletnaStran']) ? $doc['spletnaStran'] : '—'), 0, 1);

$pdf->Ln(3);
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(0, 8, "Lokacija", 0, 1);
$pdf->SetFont('DejaVu', '', 10);

$addr = trim(
  ($doc['ulica'] ?? '') . ', ' .
  ($doc['postaSt'] ?? '') . ' ' .
  ($doc['mesto'] ?? '') . ', ' .
  ($doc['country'] ?? '')
);
$pdf->MultiCell(0, 6, $addr !== ',  , ' ? $addr : '—');

$pdf->Ln(2);
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(0, 8, "Opis", 0, 1);
$pdf->SetFont('DejaVu', '', 10);
$pdf->MultiCell(0, 6, !empty($doc['bio']) ? $doc['bio'] : '—');

$pdf->Ln(2);
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(0, 8, "Specializacije", 0, 1);
$pdf->SetFont('DejaVu', '', 10);
$pdf->MultiCell(0, 6, !empty($specs) ? implode(", ", $specs) : '—');

$pdf->Ln(2);
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(0, 8, "Zadnje ocene (max 20)", 0, 1);
$pdf->SetFont('DejaVu', '', 10);

if (empty($ratings)) {
  $pdf->MultiCell(0, 6, "Ta zdravnik še nima ocen.");
} else {
  foreach ($ratings as $i => $r) {
    $line1 = ($i+1) . ". " . ($r['ime'] ?? '') . " " . ($r['priimek'] ?? '') . " — " . (int)$r['ocena'] . "/5";
    $pdf->MultiCell(0, 6, $line1);
    $kom = trim((string)($r['komentar'] ?? ''));
    if ($kom !== '') {
      $pdf->SetFont('DejaVu', '', 9);
      $pdf->MultiCell(0, 5, "Komentar: " . $kom);
      $pdf->SetFont('DejaVu', '', 10);
    }
    $pdf->Ln(1);
  }
}

// download headerji
$filename = "profil-zdravnik-" . $doctorId . ".pdf";
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$pdf->Output('I', $filename);
exit;
