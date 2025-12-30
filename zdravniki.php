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
?>

<!DOCTYPE html>
<html lang="sl">
  <head>
    <meta charset="UTF-8" />
    <title>Spletni imenik zdravnikov</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="styles/styleIndex.css?v=5" />
    <link rel="stylesheet" href="styles/styleSpecialnosti.css?v=5" />
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
            <li><a href="zdravniki.php" class="nav-link active">Poišči zdravnika</a></li>
            <li><a href="specialnosti.php" class="nav-link">Specialnosti</a></li>
            <li><a href="ustanove.php" class="nav-link">Zdravstvene ustanove</a></li>
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
