<?php
require __DIR__ . '/povezava.php';

$sql = "
SELECT 
  z.id_zdravnik,
  u.ime, u.priimek,
  z.mesto,
  COALESCE(p.avg_ocena, 0) AS avg_ocena,
  COALESCE(p.st_ocen, 0) AS st_ocen,
  GROUP_CONCAT(DISTINCT s.naziv ORDER BY s.naziv SEPARATOR ', ') AS specializacije
FROM zdravnik z
JOIN uporabnik u ON u.id_uporabnik = z.TK_uporabnik
LEFT JOIN specializacija_zdravnik sz ON sz.TK_zdravnik = z.id_zdravnik
LEFT JOIN specializacija s ON s.id_specializacija = sz.TK_specializacija
LEFT JOIN (
    SELECT TK_zdravnik,
           ROUND(AVG(ocena), 2) AS avg_ocena,
           COUNT(*) AS st_ocen
    FROM ocene
    GROUP BY TK_zdravnik
) p ON p.TK_zdravnik = z.id_zdravnik
GROUP BY z.id_zdravnik, u.ime, u.priimek, z.mesto, p.avg_ocena, p.st_ocen
ORDER BY avg_ocena DESC, st_ocen DESC, u.priimek, u.ime
";

$res = $conn->query($sql);

if (!$res) {
    die("Napaka pri branju podatkov: " . $conn->error);
}

$rows = $res->fetch_all(MYSQLI_ASSOC);
$res->free();
$conn->close();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Spletni imenik zdravnikov</title>
</head>
<body>

<h1>Seznam zdravnikov</h1>

<?php if (empty($rows)): ?>
    <p>Ni podatkov.</p>
<?php else: ?>
    <?php foreach ($rows as $r): ?>
        <div>
            <p><b><?= htmlspecialchars($r['ime'] . ' ' . $r['priimek']) ?></b></p>
            <p>Mesto: <?= htmlspecialchars($r['mesto'] ?? '-') ?></p>
            <p>Specializacije: <?= htmlspecialchars($r['specializacije'] ?? '-') ?></p>
            <p>Povprečna ocena: <?= (float)$r['avg_ocena'] ?> (<?= (int)$r['st_ocen'] ?> ocen)</p>
            <hr>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
