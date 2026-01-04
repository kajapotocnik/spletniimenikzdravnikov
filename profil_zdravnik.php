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

// ali je prijavljeni zdravnik lastnik tega profila
$isOwner = ($currentRole === 'ZDRAVNIK' && $currentUserId === $viewUserId);

// ali način urejanja
$isEditMode = $isOwner && isset($_GET['edit']) && $_GET['edit'] === '1';

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

// komentarji
$ratings = [];

if ($doctorId) {
    $stmtOcene = $conn->prepare("
        SELECT 
            o.ocena,
            o.komentar,
            u.ime,
            u.priimek
        FROM ocene o
        JOIN uporabnik u ON u.id_uporabnik = o.TK_uporabnik
        WHERE o.TK_zdravnik = ?
        ORDER BY o.id_ocene DESC
    ");
    $stmtOcene->bind_param('i', $doctorId);
    $stmtOcene->execute();
    $resOcene = $stmtOcene->get_result();

    while ($row = $resOcene->fetch_assoc()) {
        $ratings[] = $row;
    }

    $stmtOcene->close();
}

// samo uporabnik
$canRate = ($currentRole === 'UPORABNIK' && $doctorId !== null);

// je že ocenil ?
$userRating = null;
if ($canRate) {
    $stmtUR = $conn->prepare("
        SELECT ocena, komentar
        FROM ocene
        WHERE TK_uporabnik = ? AND TK_zdravnik = ?
        LIMIT 1
    ");
    $stmtUR->bind_param('ii', $currentUserId, $doctorId);
    $stmtUR->execute();
    $resUR = $stmtUR->get_result();
    $userRating = $resUR->fetch_assoc() ?: null;
    $stmtUR->close();
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



if ($isEditMode && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $naziv = trim($_POST['naziv'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $spletnaStran = trim($_POST['spletnaStran']?? '');
    $klinika = trim($_POST['klinika'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $ulica = trim($_POST['ulica'] ?? '');
    $mesto = trim($_POST['mesto'] ?? '');
    $postaSt = trim($_POST['postaSt'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $latitude = $_POST['latitude'] !== '' ? (float)$_POST['latitude']  : null;
    $longitude = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

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


    header('Location: profil_zdravnik.php?id=' . $viewUserId);
    exit;
}

// shranjevanje ocene
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'rate' &&
    $canRate &&
    $doctorId !== null
) {
    $ocena    = isset($_POST['rate']) ? (int)$_POST['rate'] : 0;
    $komentar = trim($_POST['komentar'] ?? '');

    if ($ocena < 1 || $ocena > 5) {
        $ocena = 0;
    }

    if ($ocena > 0) {
        // en uporabnik lahko oceni zdravnika samo enkrat
        $stmtRate = $conn->prepare("
            INSERT INTO ocene (ocena, komentar, TK_uporabnik, TK_zdravnik)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                ocena = VALUES(ocena),
                komentar = VALUES(komentar)
        ");
        $stmtRate->bind_param('isii', $ocena, $komentar, $currentUserId, $doctorId);
        $stmtRate->execute();
        $stmtRate->close();
    }

    header('Location: profil_zdravnik.php?id=' . $viewUserId);
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
            <li><a href="/spletniimenikzdravnikov/" class="nav-link">Domov</a></li>
            <li><a href="/spletniimenikzdravnikov/zdravniki" class="nav-link">Poišči zdravnika</a></li>
            <li><a href="/spletniimenikzdravnikov/specialnosti" class="nav-link">Specialnosti</a></li>
            <li><a href="/spletniimenikzdravnikov/ustanove" class="nav-link">Zdravstvene ustanove</a></li>
            <li><a href="/spletniimenikzdravnikov/statistika" class="nav-link">Statistika</a></li>
            <li><a href="/spletniimenikzdravnikov/kontakt" class="nav-link">Kontakt</a></li>
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
                    <div class="download-button-row">
                        <a href="izvoz_zdravnik_pdf.php?id=<?= (int)$viewUserId ?>" class="download-link" target="_blank" style="text-decoration:none;">
                            <button class="download-button" type="button">
                                <div class="docs">
                                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"> <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path> <polyline points="14 2 14 8 20 8"></polyline> <line x1="16" y1="13" x2="8" y2="13"></line> <line x1="16" y1="17" x2="8" y2="17"></line> <polyline points="10 9 9 9 8 9"></polyline> </svg>
                                    PDF
                                </div>

                                <div class="download"> <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path> <polyline points="7 10 12 15 17 10"></polyline> <line x1="12" y1="15" x2="12" y2="3"></line> </svg>
                                </div>
                            </button>
                        </a>

                        <a href="izvoz_zdravnik_excel.php?id=<?= (int)$viewUserId ?>" class="download-link" target="_blank" style="text-decoration:none;">
                            <button class="download-button excel" type="button">
                                <div class="docs">
                                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"> <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path> <polyline points="14 2 14 8 20 8"></polyline> <line x1="16" y1="13" x2="8" y2="13"></line> <line x1="16" y1="17" x2="8" y2="17"></line> <polyline points="10 9 9 9 8 9"></polyline> </svg>
                                    EXCEL
                                </div>

                                <div class="download"> <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path> <polyline points="7 10 12 15 17 10"></polyline> <line x1="12" y1="15" x2="12" y2="3"></line> </svg>
                                </div>
                            </button>
                        </a>
                    </div>
                </div>
                <a href="index.php" class="back-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                        viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    Nazaj na stran
                </a>
            </aside>

            <section class="doctor-profile-right">
                <header class="profile-header">
                    <h2>Podatki zdravnika</h2>
                    <?php if ($isEditMode): ?>
                        <p>Tu lahko posodobiš svoje podatke, ki jih vidijo pacienti.</p>
                    <?php else: ?>
                        <p>To so podatki izbranega zdravnika.</p>
                    <?php endif; ?>
                </header>

                 <form method="post" class="doctor-profile-form" enctype="multipart/form-data">
                    <?php if ($isEditMode): ?>
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
                                <?= $isEditMode ? '' : 'readonly' ?>
                            >
                        </div>

                        <div class="profile-group">
                            <label for="telefon">Telefon</label>
                            <input
                                type="text"
                                id="telefon"
                                name="telefon"
                                value="<?= htmlspecialchars($doc['telefon'] ?? '') ?>"
                                <?= $isEditMode ? '' : 'readonly' ?>
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
                                <?= $isEditMode ? '' : 'readonly' ?>
                            >
                        </div>

                        <div class="profile-group">
                            <label for="klinika">Klinika / ustanovа</label>
                            <input
                                type="text"
                                id="klinika"
                                name="klinika"
                                value="<?= htmlspecialchars($doc['klinika'] ?? '') ?>"
                                <?= $isEditMode ? '' : 'readonly' ?>
                            >
                        </div>
                    </div>

                    <div class="profile-group">
                        <label for="bio">Opis (bio)</label>
                        <textarea
                            id="bio"
                            name="bio"
                            <?= $isEditMode ? '' : 'readonly' ?>
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
                                <?= $isEditMode ? '' : 'readonly' ?>
                            >
                        </div>
                        <div class="profile-group">
                            <label for="mesto">Mesto</label>
                            <input
                                type="text"
                                id="mesto"
                                name="mesto"
                                value="<?= htmlspecialchars($doc['mesto'] ?? '') ?>"
                                <?= $isEditMode ? '' : 'readonly' ?>
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
                                <?= $isEditMode ? '' : 'readonly' ?>
                            >
                        </div>
                        <div class="profile-group">
                            <label for="country">Država</label>
                            <input
                                type="text"
                                id="country"
                                name="country"
                                value="<?= htmlspecialchars($doc['country'] ?? '') ?>"
                                <?= $isEditMode ? '' : 'readonly' ?>
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
                                <?= $isEditMode ? '' : 'readonly' ?>
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
                                <?= $isEditMode ? '' : 'readonly' ?>
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
                                                <?= $isEditMode ? '' : 'disabled' ?>
                                            >
                                            <span><?= htmlspecialchars($s['naziv']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-actions">
                        <?php if ($isEditMode): ?>
                            <button type="submit" class="profile-save-btn">
                                Shrani podatke
                            </button>
                        <?php elseif ($isOwner): ?>
                            <a href="profil_zdravnik.php?id=<?= (int)$viewUserId ?>&edit=1"
                            class="profile-save-btn profile-edit-link">
                                Posodobi podatke
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
            </section>
        </div>

        <?php if ($canRate): ?>
            <div class="doctor-rate-box">
                <h3>Oceni tega zdravnika</h3>
                <?php if ($userRating): ?>
                    <p class="rate-note"> Tvoja trenutna ocena: <strong><?= (int)$userRating['ocena'] ?>/5</strong>. Oceno in komentar lahko posodobiš.</p>
                <?php else: ?>
                    <p class="rate-note"> Izberi število zvezdic in dodaj kratek komentar o svoji izkušnji.</p>
                <?php endif; ?>

                <form method="post" class="rating-form">
                    <input type="hidden" name="action" value="rate">

                    <div class="rating">
                        <input type="radio" id="star5" name="rate" value="5" <?= $userRating && (int)$userRating['ocena'] === 5 ? 'checked' : '' ?> />
                        <label title="Odlično" for="star5">
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 576 512">
                                <path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/>
                            </svg>
                        </label>

                        <input value="4" name="rate" id="star4" type="radio" <?= $userRating && (int)$userRating['ocena'] === 4 ? 'checked' : '' ?> />
                        <label title="Zelo dobro" for="star4">
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 576 512">
                                <path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/>
                            </svg>
                        </label>

                        <input value="3" name="rate" id="star3" type="radio" <?= $userRating && (int)$userRating['ocena'] === 3 ? 'checked' : '' ?> />
                        <label title="Dobro" for="star3">
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 576 512">
                                <path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/>
                            </svg>
                        </label>

                        <input value="2" name="rate" id="star2" type="radio" <?= $userRating && (int)$userRating['ocena'] === 2 ? 'checked' : '' ?> />
                        <label title="Povprečno" for="star2">
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 576 512">
                                <path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/>
                            </svg>
                        </label>

                        <input value="1" name="rate" id="star1" type="radio" <?= $userRating && (int)$userRating['ocena'] === 1 ? 'checked' : '' ?> />
                        <label title="Slabo" for="star1">
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 576 512">
                                <path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/>
                            </svg>
                                </label>
                            </div>

                    <textarea name="komentar" class="rating-comment" placeholder="Dodaj kratek komentar (neobvezno)"><?= htmlspecialchars($userRating['komentar'] ?? '') ?></textarea>

                    <button type="submit" class="rating-submit-btn">
                        Pošlji oceno
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <section class="doctor-ratings-card">
        <div class="doctor-ratings-inner">
            <header class="ratings-header">
                <h2>Mnenja pacientov</h2>
                <p>Ocene in komentarji pacientov za tega zdravnika.</p>
            </header>

            <?php if (empty($ratings)): ?>
                <p class="doctor-ratings-empty"> Ta zdravnik še nima ocen. </p>
            <?php else: ?>
                <div class="ratings-table-wrapper">
                    <table class="ratings-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pacient</th>
                                <th>Ocena</th>
                                <th>Komentar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($ratings as $r): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($r['ime'] . ' ' . $r['priimek']) ?></td>
                                    <td>
                                        <span class="rating-stars">
                                            <?php
                                                $oc = (int)$r['ocena'];
                                                echo str_repeat('★', $oc) . str_repeat('☆', 5 - $oc);
                                            ?>
                                        </span>
                                        <span class="rating-number">(<?= (int)$r['ocena'] ?>/5)</span>
                                    </td>
                                    <td>
                                        <?= $r['komentar'] !== null && $r['komentar'] !== '' 
                                            ? htmlspecialchars($r['komentar']) 
                                            : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    </main>

</body>
</html>

<script>
  const backBtn = document.querySelector('.back-btn');
  if (backBtn) {
    backBtn.addEventListener('click', () => {
    });
  }
</script>

