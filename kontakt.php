<?php
require __DIR__ . '/povezava.php';

$success = isset($_GET['sent']) && $_GET['sent'] === '1';

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
            <li><a href="/spletniimenikzdravnikov/" class="nav-link">Domov</a></li>
            <li><a href="/spletniimenikzdravnikov/zdravniki" class="nav-link">Poišči zdravnika</a></li>
            <li><a href="/spletniimenikzdravnikov/specialnosti" class="nav-link">Specialnosti</a></li>
            <li><a href="/spletniimenikzdravnikov/ustanove" class="nav-link">Zdravstvene ustanove</a></li>
            <li><a href="/spletniimenikzdravnikov/statistika" class="nav-link">Statistika</a></li>
            <li><a href="/spletniimenikzdravnikov/kontakt" class="nav-link active">Kontakt</a></li>
          </ul>
        </nav>

    <?php if (!$isLoggedIn): ?>
      <a href="/spletniimenikzdravnikov/prijava" class="btn-nav">Prijava</a>
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
            <div class="hero-right hero-image">
            <div class="doctor-bg"></div>
            <img src="img/hero-doctor.png" alt="Zdravnica" class="hero-doctor"/>
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

<section class="contact-boxes">
  <div class="contact-boxes-inner">

    <div class="cbox cbox--dark">
      <div class="cbox-top">
        <span class="cbox-ico">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M21.97 18.33C21.97 18.69 21.89 19.06 21.72 19.42C21.55 19.78 21.33 20.12 21.04 20.44C20.55 20.98 20.01 21.37 19.4 21.62C18.8 21.87 18.15 22 17.45 22C16.43 22 15.34 21.76 14.19 21.27C13.04 20.78 11.89 20.12 10.75 19.29C9.6 18.45 8.51 17.52 7.47 16.49C6.44 15.45 5.51 14.36 4.68 13.22C3.86 12.08 3.2 10.94 2.72 9.81C2.24 8.67 2 7.58 2 6.54C2 5.86 2.12 5.21 2.36 4.61C2.6 4 2.98 3.44 3.51 2.94C4.15 2.31 4.85 2 5.59 2C5.87 2 6.15 2.06 6.4 2.18C6.66 2.3 6.89 2.48 7.07 2.74L9.39 6.01C9.57 6.26 9.7 6.49 9.79 6.71C9.88 6.92 9.93 7.13 9.93 7.32C9.93 7.56 9.86 7.8 9.72 8.03C9.59 8.26 9.4 8.5 9.16 8.74L8.4 9.53C8.29 9.64 8.24 9.77 8.24 9.93C8.24 10.01 8.25 10.08 8.27 10.16C8.3 10.24 8.33 10.3 8.35 10.36C8.53 10.69 8.84 11.12 9.28 11.64C9.73 12.16 10.21 12.69 10.73 13.22C11.27 13.75 11.79 14.24 12.32 14.69C12.84 15.13 13.27 15.43 13.61 15.61C13.66 15.63 13.72 15.66 13.79 15.69C13.87 15.72 13.95 15.73 14.04 15.73C14.21 15.73 14.34 15.67 14.45 15.56L15.21 14.81C15.46 14.56 15.7 14.37 15.93 14.25C16.16 14.11 16.39 14.04 16.64 14.04C16.83 14.04 17.03 14.08 17.25 14.17C17.47 14.26 17.7 14.39 17.95 14.56L21.26 16.91C21.52 17.09 21.7 17.3 21.81 17.55C21.91 17.8 21.97 18.05 21.97 18.33Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"></path> </g></svg>
        </span>
        <h4>(+386) 40 000 000</h4>
      </div>
      <p>Pokliči nas za pomoč ali vprašanja glede uporabe portala.</p>
    </div>

    <div class="cbox cbox--light">
      <div class="cbox-top">
        <span class="cbox-ico">
              <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none"> <polyline fill="none" points="4 8.2 12 14.1 20 8.2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline> <rect fill="none" height="14" rx="2" ry="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" width="18" x="3" y="6.5"></rect> </svg>
        </span>
        <h4>podpora@zdravnik.si</h4>
      </div>
      <p>Piši nam – odgovorimo običajno v 24–48 urah.</p>
    </div>

    <div class="cbox cbox--white">
      <div class="cbox-top">
        <span class="cbox-ico">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M12 21C15.5 17.4 19 14.1764 19 10.2C19 6.22355 15.866 3 12 3C8.13401 3 5 6.22355 5 10.2C5 14.1764 8.5 17.4 12 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </svg>
        </span>
        <h4>Ljubljana, Slovenija</h4>
      </div>
      <p>Naša lokacija je spodaj prikazana na Google zemljevidu.</p>
    </div>
  </div>
</section>

<section class="contact-map">
  <div class="contact-map-inner">
    <div class="map-card">
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d106000.55334626233!2d14.532097749999998!3d46.06623985!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x476531f5969886d1%3A0x400f81c823fec20!2sLjubljana!5e1!3m2!1ssl!2ssi!4v1766326089481!5m2!1ssl!2ssi"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen>
      </iframe>
    </div>
  </div>
</section>

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

</body>
</html>
