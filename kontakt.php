<?php
require __DIR__ . '/povezava.php';

$success = isset($_GET['sent']) && $_GET['sent'] === '1';

// prijava
$isLoggedIn = isset($_SESSION['user_id']);
$userIme = $_SESSION['user_ime'] ?? '';
$userPriimek = $_SESSION['user_priimek'] ?? '';
$userFullName = trim($userIme . ' ' . $userPriimek);

$userVloga = $_SESSION['user_vloga'] ?? null;
$isDoctor = $isLoggedIn && $userVloga === 'ZDRAVNIK';

// če ni imena
$initials = 'U';
if ($userIme !== '' || $userPriimek !== '') {
  $first = mb_substr($userIme, 0, 1, 'UTF-8');
  $last  = mb_substr($userPriimek, 0, 1, 'UTF-8');
  $initials = mb_strtoupper($first . $last, 'UTF-8');
}
?>


<!doctype html>
<html lang="sl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kontakt</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/styleIndex.css?v=5" />
  <link rel="stylesheet" href="styles/styleKontakt.css?v=1">
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
        <li><a href="kontakt.php" class="nav-link active">Kontakt</a></li>
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
            <a href="admin_panel.php" class="user-dropdown-item">Admin panel</a>
          <?php endif; ?>

          <a href="logout.php" class="user-dropdown-item">Odjava</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</header>

<section class="hero">
    <div class="hero-content">
        <div class="hero-inner">
            <div class="hero-text">
                <h1>Kontaktirajte nas</h1>
                <p>
                    Imate vprašanje, predlog ali potrebujete pomoč?
                    Pišite nam in odgovorili vam bomo v najkrajšem možnem času.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="contact-mid">
  <div class="contact-mid-inner">
    <div class="contact-form-card">
      <form class="cform" action="#" method="post" onsubmit="return false;">
        <div class="cform-row">
          <input class="cinput" type="email" placeholder="Email">
          <input class="cinput" type="text" placeholder="Telefonska številka">
        </div>

        <div class="cform-row single">
          <input class="cinput" type="text" placeholder="Ime in priimek">
        </div>

        <div class="cform-row single">
          <textarea class="ctextarea" rows="6" placeholder="Sporočilo"></textarea>
        </div>

        <button class="cbtn" type="submit">Pošlji</button>
      </form>
    </div>


    <aside class="newsletter-card">
      <div class="newsletter-inner">
        <h3>Naše novice</h3>
        <p>
          Občasno pošljemo novosti o posodobitvah, novih zdravnikih in
          pomembnih informacijah.
        </p>

        <input class="ninput" type="email" placeholder="Email">
        <button class="nbtn" type="button">Naroči</button>
      </div>
    </aside>

  </div>
</section>


</body>
</html>
