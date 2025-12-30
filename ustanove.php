<?php
require __DIR__ . '/povezava.php';

// prijava
$isLoggedIn = isset($_SESSION['user_id']);
$userIme = $_SESSION['user_ime'] ?? '';
$userPriimek = $_SESSION['user_priimek'] ?? '';
$userFullName = trim($userIme . ' ' . $userPriimek);
$userEmail = $_SESSION['user_email'] ?? '';

// inicialke (za avatar)
$inicialke = 'U';
if ($userIme !== '' || $userPriimek !== '') {
  $prva = mb_substr($userIme, 0, 1, 'UTF-8');
  $druga = mb_substr($userPriimek, 0, 1, 'UTF-8');
  $inicialke = mb_strtoupper($prva . $druga, 'UTF-8');
}

$sql = "
  SELECT
    d.klinika,
    GROUP_CONCAT(DISTINCT d.mesto ORDER BY d.mesto SEPARATOR ', ') AS mesta,
    COUNT(d.id_zdravnik) AS st_zdravnikov
  FROM zdravnik d
  WHERE d.klinika IS NOT NULL AND d.klinika <> ''
  GROUP BY d.klinika
  ORDER BY d.klinika
";

$rezultat = $conn->query($sql);
$ustanove = $rezultat ? $rezultat->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="sl">
  <head>
    <meta charset="UTF-8" />
    <title>Spletni imenik zdravnikov</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="styles/styleIndex.css?v=5" />
    <link rel="stylesheet" href="styles/styleSpecialnosti.css?v=5" />
    <link rel="stylesheet" href="styles/styleUstanove.css?v=5" />
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
            <li><a href="zdravniki.php" class="nav-link">Poišči zdravnika</a></li>
            <li><a href="specialnosti.php" class="nav-link">Specialnosti</a></li>
            <li><a href="ustanove.php" class="nav-link active">Zdravstvene ustanove</a></li>
            <li><a href="kontakt.php" class="nav-link">Kontakt</a></li>
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

              <?php if (($_SESSION['user_vloga'] ?? '') === 'ADMIN'): ?>
                <a href="admin_panel.php" class="user-dropdown-item">Admin plošča</a>
              <?php endif; ?>

              <a href="logout.php" class="user-dropdown-item">Odjava</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </header>

<main>
<section class="spec-hero">
    <div class="spec-hero-inner">
      <div class="spec-kicker">ZDRAVSTVENE USTANOVE</div>
      <h1>Seznam vseh <span> klinik in bolnišnic</span></h1>
    </div>
  </section>

   <section class="ustanove-wrap">

    <?php if (empty($ustanove)): ?>
      <div class="ustanova-card">
        <div class="ustanova-right">
          <h2 class="ustanova-title">Ni podatkov</h2>
          <p class="ustanova-desc">
            Trenutno v sistemu ni vpisanih zdravstvenih ustanov.
          </p>
        </div>
      </div>

    <?php else: ?>
      <?php foreach ($ustanove as $u): ?>
        <div class="ustanova-card">
          <div class="ustanova-left">
            <div class="ustanova-photo"
                 style="background-image:url('img/hospital.jpg');">
            </div>

            <div class="ustanova-mini">
              <div class="mini-top">
                <span class="mini-name">
                  <?= htmlspecialchars($u['klinika']) ?>
                </span>
              </div>

              <div class="mini-from">
                LOKACIJA<br>
                <span><?= htmlspecialchars($u['mesta'] ?: 'ni podatka') ?></span>
              </div>
            </div>
          </div>

          <div class="ustanova-right">
            <span class="pill">O NAS</span>

            <h2 class="ustanova-title">
              <?= htmlspecialchars($u['klinika']) ?>
            </h2>

            <p class="ustanova-desc">
              Združujemo strokovno medicinsko znanje, sodobno tehnologijo
              ter celostni pristop, osredotočen na pacienta.
            </p>

            <div class="ustanova-actions">
              <a href="#" class="btn-main">Več informacij</a>

              <div class="ustanova-meta">
                <div>
                  <div class="meta-label">Število zdravnikov</div>
                  <div class="meta-value">
                    <?= (int)$u['st_zdravnikov'] ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </section>
</main>

    <footer>
      <div class="container">
        <div>
          ©
          <?php echo date('Y'); ?>
          Spletni imenik zdravnikov. Vse pravice pridržane.
        </div>
        <div>
          Mnenja pacientov niso nadomestilo za strokovni zdravniški nasvet.
        </div>
      </div>
    </footer>
  </body>

<script>

</script>
</html>
