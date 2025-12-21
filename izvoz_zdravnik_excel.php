<?php
require __DIR__ . '/povezava.php';

$viewUserId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($viewUserId <= 0) {
  die("Napačen ID.");
}

// poiščemo zdravnika
$stmt = $conn->prepare("
  SELECT
    d.id_zdravnik,
    d.naziv,
    u.ime,
    u.priimek,
    u.email,
    d.telefon,
    d.spletnaStran,
    d.klinika,
    d.bio,
    d.ulica,
    d.mesto,
    d.postaSt,
    d.country
  FROM zdravnik d
  JOIN uporabnik u ON u.id_uporabnik = d.TK_uporabnik
  WHERE d.TK_uporabnik = ?
  LIMIT 1
");
$stmt->bind_param("i", $viewUserId);
$stmt->execute();
$res = $stmt->get_result();
$doc = $res->fetch_assoc();
$stmt->close();

if (!$doc) {
  die("Zdravnik ne obstaja.");
}

// Excel headerji
$filename = "zdravnik_" . $doc['ime'] . "_" . $doc['priimek'] . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// UTF-8
echo "\xEF\xBB\xBF";
?>

<table border="1">
  <tr><th colspan="2">Podatki zdravnika</th></tr>
  <tr><td>Naziv</td><td><?= htmlspecialchars($doc['naziv']) ?></td></tr>
  <tr><td>Ime</td><td><?= htmlspecialchars($doc['ime']) ?></td></tr>
  <tr><td>Priimek</td><td><?= htmlspecialchars($doc['priimek']) ?></td></tr>
  <tr><td>Email</td><td><?= htmlspecialchars($doc['email']) ?></td></tr>
  <tr><td>Telefon</td><td><?= htmlspecialchars($doc['telefon']) ?></td></tr>
  <tr><td>Spletna stran</td><td><?= htmlspecialchars($doc['spletnaStran']) ?></td></tr>
  <tr><td>Klinika</td><td><?= htmlspecialchars($doc['klinika']) ?></td></tr>
  <tr><td>Opis</td><td><?= htmlspecialchars($doc['bio']) ?></td></tr>
  <tr><td>Naslov</td>
      <td><?= htmlspecialchars($doc['ulica']." ".$doc['postaSt']." ".$doc['mesto'].", ".$doc['country']) ?></td>
  </tr>
</table>

<br>

<table border="1">
  <tr>
    <th>Uporabnik</th>
    <th>Ocena</th>
    <th>Komentar</th>
  </tr>

<?php
$st = $conn->prepare("
  SELECT o.ocena, o.komentar, u.ime, u.priimek
  FROM ocene o
  JOIN uporabnik u ON u.id_uporabnik = o.TK_uporabnik
  WHERE o.TK_zdravnik = ?
");
$st->bind_param("i", $doc['id_zdravnik']);
$st->execute();
$r = $st->get_result();

while ($row = $r->fetch_assoc()):
?>
  <tr>
    <td><?= htmlspecialchars($row['ime'].' '.$row['priimek']) ?></td>
    <td><?= (int)$row['ocena'] ?></td>
    <td><?= htmlspecialchars($row['komentar'] ?? '') ?></td>
  </tr>
<?php endwhile; ?>

</table>
