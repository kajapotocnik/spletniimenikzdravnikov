<?php
require __DIR__ . '/povezava.php';

// brez prijave ni profila
if (!isset($_SESSION['user_id'])) {
    header('Location: prijava.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$currentRole   = $_SESSION['user_vloga'] ?? null;

$viewUserId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;

// ali lahko ureja
$canEdit = ($currentRole === 'ZDRAVNIK' && $currentUserId === $viewUserId);

// slika
if (!function_exists('doctorImage')) {
    function doctorImage(?string $dbUrl, int $id): string {
        if (!empty($dbUrl)) {
            return $dbUrl;
        }

        $path = "img/doctors/$id.jpg";
        return file_exists(__DIR__ . "/$path") ? $path : "img/doctor-placeholder.jpg";
    }
}


// da  ma zdravnik zapis v tabeli zdravnik
$doctorId = null;
$check = $conn->prepare("SELECT id_zdravnik FROM zdravnik WHERE TK_uporabnik = ? LIMIT 1");
$check->bind_param('i', $viewUserId);
$check->execute();
$res = $check->get_result();
$docRow = $res->fetch_assoc();
$check->close();

if ($docRow) {
    $doctorId = (int)$docRow['id_zdravnik'];
}

if (!$docRow && $currentRole === 'ZDRAVNIK' && $currentUserId === $viewUserId) {
    // ustvarimo prazen zapis
    $insEmpty = $conn->prepare("INSERT INTO zdravnik (TK_uporabnik) VALUES (?)");
    $insEmpty->bind_param('i', $viewUserId);
    $insEmpty->execute();
    $doctorId = (int)$insEmpty->insert_id;
    $insEmpty->close();
}

// preberi specializacije
$allSpecs = [];
$specRes = $conn->query("SELECT id_specializacija, naziv FROM specializacija ORDER BY naziv");
if ($specRes) {
    $allSpecs = $specRes->fetch_all(MYSQLI_ASSOC);
}

// preberi specializacijo od zdravnika
$selectedSpecs = [];
if ($doctorId) {
    $sel = $conn->prepare("SELECT TK_specializacija FROM specializacija_zdravnik WHERE TK_zdravnik = ?");
    $sel->bind_param('i', $doctorId);
    $sel->execute();
    $selRes = $sel->get_result();
    while ($row = $selRes->fetch_assoc()) {
        $selectedSpecs[] = (int)$row['TK_specializacija'];
    }
    $sel->close();
}

// imena specializacij
$doctorSpecs = [];

if (!empty($allSpecs) && !empty($selectedSpecs)) {
    foreach ($allSpecs as $s) {
        if (in_array((int)$s['id_specializacija'], $selectedSpecs, true)) {
            $doctorSpecs[] = $s['naziv'];
        }
    }
}



if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $naziv       = trim($_POST['naziv']       ?? '');
    $telefon     = trim($_POST['telefon']     ?? '');
    $spletnaStran= trim($_POST['spletnaStran']?? '');
    $klinika     = trim($_POST['klinika']     ?? '');
    $bio         = trim($_POST['bio']         ?? '');
    $ulica       = trim($_POST['ulica']       ?? '');
    $mesto       = trim($_POST['mesto']       ?? '');
    $postaSt     = trim($_POST['postaSt']     ?? '');
    $country     = trim($_POST['country']     ?? '');
    $latitude    = $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
    $longitude   = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

    $stmt = $conn->prepare("
        UPDATE zdravnik
        SET naziv = ?, telefon = ?, spletnaStran = ?, klinika = ?, bio = ?,
            ulica = ?, mesto = ?, postaSt = ?, country = ?, latitude = ?, longitude = ?
        WHERE TK_uporabnik = ?
    ");

    $stmt->bind_param(
        'sssssssssddi',
        $naziv,
        $telefon,
        $spletnaStran,
        $klinika,
        $bio,
        $ulica,
        $mesto,
        $postaSt,
        $country,
        $latitude,
        $longitude,
        $viewUserId
    );

    $stmt->execute();
    $stmt->close();

    // upload slika
    $newImagePath = null;

    if (isset($_FILES['slika']) && $_FILES['slika']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['slika']['error'] === UPLOAD_ERR_OK) {
            $maxSize = 2 * 1024 * 1024;

            if ($_FILES['slika']['size'] <= $maxSize) {
                $ext = strtolower(pathinfo($_FILES['slika']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png'];

                if (in_array($ext, $allowed, true)) {
                    $targetDir = __DIR__ . '/img/doctors';

                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }

                    // ime datoteke
                    $fileName   = 'doctor_' . $doctorId . '.' . $ext;
                    $fullPath   = $targetDir . '/' . $fileName;
                    $relative   = 'img/doctors/' . $fileName;

                    if (move_uploaded_file($_FILES['slika']['tmp_name'], $fullPath)) {
                        $newImagePath = $relative;
                    }
                }
            }
        }
    }

    // zapiše v bazo
    if ($newImagePath !== null && $doctorId) {
        $stmtImg = $conn->prepare("UPDATE zdravnik SET slika_url = ? WHERE id_zdravnik = ?");
        $stmtImg->bind_param('si', $newImagePath, $doctorId);
        $stmtImg->execute();
        $stmtImg->close();
    }
    
    // posodobi specializacije
    if ($doctorId) {
        // zbriši stare
        $del = $conn->prepare("DELETE FROM specializacija_zdravnik WHERE TK_zdravnik = ?");
        $del->bind_param('i', $doctorId);
        $del->execute();
        $del->close();

        // dodaj nove
        if (!empty($_POST['specializacije']) && is_array($_POST['specializacije'])) {
            $insSpec = $conn->prepare("
                INSERT INTO specializacija_zdravnik (TK_specializacija, TK_zdravnik) VALUES (?, ?)
            ");

            foreach ($_POST['specializacije'] as $specId) {
                $specId = (int)$specId;
                if ($specId > 0) {
                    $insSpec->bind_param('ii', $specId, $doctorId);
                    $insSpec->execute();
                }
            }

            $insSpec->close();
        }
    }


    header('Location: profil_zdravnik.php');
    exit;
}


// branje podatkov
$stmt = $conn->prepare("
    SELECT 
      z.id_zdravnik,
      z.TK_uporabnik,
      z.naziv,
      z.telefon,
      z.spletnaStran,
      z.klinika,
      z.bio,
      z.ulica,
      z.mesto,
      z.postaSt,
      z.country,
      z.latitude,
      z.longitude,
      u.ime,
      u.priimek,
      u.email,
      z.slika_url
    FROM zdravnik z
    JOIN uporabnik u ON u.id_uporabnik = z.TK_uporabnik
    WHERE z.TK_uporabnik = ?
    LIMIT 1
");
$stmt->bind_param('i', $viewUserId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc) {
    echo "Profil zdravnika ni na voljo.";
    exit;
}

// PRIJAVA - podatki
$isLoggedIn = isset($_SESSION['user_id']);
$userIme = $_SESSION['user_ime'] ?? '';
$userPriimek = $_SESSION['user_priimek'] ?? '';
$userFullName = trim($userIme . ' ' . $userPriimek);

$userVloga = $_SESSION['user_vloga'] ?? null;
$isDoctor = $isLoggedIn && $userVloga === 'ZDRAVNIK'; // samo zdravniki

// če ni imena
$initials = 'U';
if ($userIme !== '' || $userPriimek !== '') {
    $first = mb_substr($userIme, 0, 1, 'UTF-8');
    $last  = mb_substr($userPriimek, 0, 1, 'UTF-8');
    $initials = mb_strtoupper($first . $last, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8" />
    <title>Spletni imenik zdravnikov</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="styles/styleProfilZdravnik.css">
    <link rel="stylesheet" href="styles/styleIndex.css?v=5" />
  </head>
<body>

<header class="navbar">
      <div class="container">
        <div class="logo">
          <img src="img/logo1.png" alt="Logo" />
        </div>
        <nav>
          <ul>
            <li><a href="index.php" class="nav-link">Domov</a></li>
            <li><a href="#" class="nav-link">Poišči zdravnika</a></li>
            <li><a href="#" class="nav-link">Specialnosti</a></li>
            <li><a href="#" class="nav-link">Mesta</a></li>
            <li><a href="#" class="nav-link">Zdravstvene ustanove</a></li>
            <li><a href="#" class="nav-link">Kontakt</a></li>
          </ul>
        </nav>
        
        <?php if (!$isLoggedIn): ?>
          <a href="prijava.php" class="btn-nav">Prijava</a>
        <?php else: ?>
          <div class="user-menu">
            <button class="user-menu-trigger" type="button">
              <span class="user-avatar"><?= htmlspecialchars($initials) ?></span>
              <span class="user-name"><?= htmlspecialchars($userFullName) ?></span>
              <span class="user-chevron">
                <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </span>
            </button>

            <div class="user-dropdown">
              <?php if ($isDoctor): ?>
                <a href="profil_zdravnik.php" class="user-dropdown-item">Moj profil</a>
              <?php endif; ?>

              <a href="logout.php" class="user-dropdown-item">Odjava</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </header>

    <main class="doctor-profile-page">
        <div class="doctor-profile-wrapper">

            <aside class="doctor-profile-left">
                <div class="doctor-avatar-wrap">
                    <img
                        class="doctor-profile-avatar"
                        src="<?= htmlspecialchars(doctorImage($doc['slika_url'] ?? null, (int)$doc['id_zdravnik'])) ?>"
                        alt="<?= htmlspecialchars($doc['ime'] . ' ' . $doc['priimek']) ?>"
                    >
                </div>

                    <h1 class="doctor-profile-name">
                        <?= htmlspecialchars(trim(($doc['naziv'] ?? '') . ' ' . $doc['ime'] . ' ' . $doc['priimek'])) ?>
                    </h1>

                    <?php if (!empty($doctorSpecs)): ?>
                        <div class="doctor-specialities">
                            <?php foreach ($doctorSpecs as $spec): ?>
                                <span class="speciality-pill">
                                    <?= htmlspecialchars($spec) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <div class="doctor-contact">
                    <p><strong>Email:</strong> <?= htmlspecialchars($doc['email']) ?></p>
                    <?php if (!empty($doc['telefon'])): ?>
                        <p><strong>Telefon:</strong> <?= htmlspecialchars($doc['telefon']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($doc['spletnaStran'])): ?>
                        <p>
                            <strong>Spletna stran:</strong>
                            <a href="<?= htmlspecialchars($doc['spletnaStran']) ?>" target="_blank">
                                <?= htmlspecialchars($doc['spletnaStran']) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="doctor-profile-right">
                <header class="profile-header">
                    <h2>Podatki zdravnika</h2>
                    <?php if ($canEdit): ?>
                        <p>Tu lahko posodobiš svoje podatke, ki jih vidijo pacienti.</p>
                    <?php else: ?>
                        <p>To so podatki izbranega zdravnika.</p>
                    <?php endif; ?>
                </header>

                 <form method="post" class="doctor-profile-form" enctype="multipart/form-data">
                    <?php if ($canEdit): ?>
                        <div class="profile-group">
                            <label for="slika">Profilna slika</label>
                            <input
                                type="file"
                                id="slika"
                                name="slika"
                                accept="image/*"
                            >
                            <small>Podprte vrste: JPG, PNG, max. 2 MB.</small>
                        </div>
                    <?php endif; ?>

                    <div class="profile-row">
                        <div class="profile-group">
                            <label for="naziv">Naziv</label>
                            <input
                                type="text"
                                id="naziv"
                                name="naziv"
                                value="<?= htmlspecialchars($doc['naziv'] ?? '') ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>

                        <div class="profile-group">
                            <label for="telefon">Telefon</label>
                            <input
                                type="text"
                                id="telefon"
                                name="telefon"
                                value="<?= htmlspecialchars($doc['telefon'] ?? '') ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                    </div>

                    <div class="profile-row">
                        <div class="profile-group">
                            <label for="spletnaStran">Spletna stran</label>
                            <input
                                type="text"
                                id="spletnaStran"
                                name="spletnaStran"
                                value="<?= htmlspecialchars($doc['spletnaStran'] ?? '') ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>

                        <div class="profile-group">
                            <label for="klinika">Klinika / ustanovа</label>
                            <input
                                type="text"
                                id="klinika"
                                name="klinika"
                                value="<?= htmlspecialchars($doc['klinika'] ?? '') ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                    </div>

                    <div class="profile-group">
                        <label for="bio">Opis (bio)</label>
                        <textarea
                            id="bio"
                            name="bio"
                            <?= $canEdit ? '' : 'readonly' ?>
                        ><?= htmlspecialchars($doc['bio'] ?? '') ?></textarea>
                    </div>

                    <h3 class="profile-subtitle">Lokacija</h3>

                    <div class="profile-row">
                        <div class="profile-group">
                            <label for="ulica">Ulica</label>
                            <input
                                type="text"
                                id="ulica"
                                name="ulica"
                                value="<?= htmlspecialchars($doc['ulica'] ?? '') ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                        <div class="profile-group">
                            <label for="mesto">Mesto</label>
                            <input
                                type="text"
                                id="mesto"
                                name="mesto"
                                value="<?= htmlspecialchars($doc['mesto'] ?? '') ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                    </div>

                    <div class="profile-row">
                        <div class="profile-group">
                            <label for="postaSt">Poštna številka</label>
                            <input
                                type="text"
                                id="postaSt"
                                name="postaSt"
                                value="<?= htmlspecialchars($doc['postaSt'] ?? '') ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                        <div class="profile-group">
                            <label for="country">Država</label>
                            <input
                                type="text"
                                id="country"
                                name="country"
                                value="<?= htmlspecialchars($doc['country'] ?? '') ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                    </div>

                    <div class="profile-row">
                        <div class="profile-group">
                            <label for="latitude">Latitude</label>
                            <input
                                type="number"
                                step="0.000001"
                                id="latitude"
                                name="latitude"
                                value="<?= $doc['latitude'] !== null ? htmlspecialchars($doc['latitude']) : '' ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                        <div class="profile-group">
                            <label for="longitude">Longitude</label>
                            <input
                                type="number"
                                step="0.000001"
                                id="longitude"
                                name="longitude"
                                value="<?= $doc['longitude'] !== null ? htmlspecialchars($doc['longitude']) : '' ?>"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                        
                        <?php if (!empty($allSpecs)): ?>
                            <h3 class="profile-subtitle">Specializacije</h3>
                            <div class="profile-group">
                                <div class="spec-list">
                                    <?php foreach ($allSpecs as $s): 
                                        $checked = in_array((int)$s['id_specializacija'], $selectedSpecs, true);
                                    ?>
                                        <label class="spec-pill">
                                            <input
                                                type="checkbox"
                                                name="specializacije[]"
                                                value="<?= (int)$s['id_specializacija'] ?>"
                                                <?= $checked ? 'checked' : '' ?>
                                                <?= $canEdit ? '' : 'disabled' ?>
                                            >
                                            <span><?= htmlspecialchars($s['naziv']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($canEdit): ?>
                        <div class="profile-actions">
                            <button type="submit" class="profile-save-btn">
                                Shrani podatke
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </section>
        </div>
    </main>

</body>
</html>
