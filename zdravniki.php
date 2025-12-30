<?php
require __DIR__ . '/povezava.php';

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

// prebere zdravnike
$sql = "
SELECT
  d.id_zdravnik,
  u.id_uporabnik,
  u.ime,
  u.priimek,
  GROUP_CONCAT(DISTINCT s.naziv ORDER BY s.naziv SEPARATOR ', ') AS specializacije,
  ROUND(AVG(o.ocena), 1)  AS povprecje_ocen,
  COUNT(o.ocena) AS st_ocen,
  d.slika_url
FROM zdravnik d
JOIN uporabnik u ON u.id_uporabnik = d.TK_uporabnik
LEFT JOIN ocene o ON o.TK_zdravnik = d.id_zdravnik
LEFT JOIN specializacija_zdravnik sz ON sz.TK_zdravnik = d.id_zdravnik
LEFT JOIN specializacija s ON s.id_specializacija = sz.TK_specializacija
GROUP BY d.id_zdravnik, u.ime, u.priimek, d.slika_url
ORDER BY u.priimek, u.ime
";

// za specializacije
$specSql = "
  SELECT id_specializacija, naziv
  FROM specializacija
  ORDER BY naziv
";

$specRezultat = $conn->query($specSql); 

// če ne rezultat ne obstaja se pretvori v prazno polje
$specializacije = $specRezultat ?
$specRezultat->fetch_all(MYSQLI_ASSOC) : []; 

$rezultat = $conn->query($sql);

//vsi zdravniki se pretvorijo v polje
$zdravniki = $rezultat ? $rezultat->fetch_all(MYSQLI_ASSOC) : []; 

// za sliko
function doctorImage(?string $dbUrl, int $id): string { 
  if (!empty($dbUrl)) return
  $dbUrl; $path = "img/doctors/$id.jpg"; return file_exists(__DIR__ . "/$path") ?
  $path : "img/doctor-placeholder.jpg"; 
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
    <link rel="stylesheet" href="styles/styleZdravniki.css?v=5" />
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
  <section class="spec-hero">
    <div class="spec-hero-inner">
      <div class="spec-kicker">NAŠI ZDRAVNIKI</div>
      <h1>Širok nabor specialnosti <span>za tvoje zdravje</span></h1>
    </div>
  </section>

  <section class="search-modes">
    <button class="mode-btn active" data-mode="spec">Iskanje po specializaciji</button>
    <button class="mode-btn" data-mode="ime">Iskanje po imenu</button>
    <button class="mode-btn" data-mode="lokacija">Iskanje po lokaciji</button>
  </section>

  <span class="search-separator"></span>

  <section class="doctor-tools">

    <div id="toolSpec" class="tool-panel">
      <div class="doctor-filters">
        <button class="filter-btn active" data-filter="all">Vsi</button>

        <?php foreach ($specializacije as $s): ?>
          <button class="filter-btn" data-filter="<?= htmlspecialchars($s['naziv']) ?>">
            <?= htmlspecialchars($s['naziv']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="toolIme" class="tool-panel" hidden>
      <div class="search">
        <input
          type="text"
          id="doctorSearch"
          placeholder="Išči zdravnika (ime, priimek, specializacija)..."
          autocomplete="off"
        />
      </div>
    </div>

    <div id="toolLokacija" class="tool-panel" hidden>
      <div class="location-placeholder">
        Iskanje po lokaciji bo dodano kasneje.
      </div>
    </div>

  </section>

  <section class="home-doctors">
    <div class="container">
      <div class="home-doctors-grid">

        <?php if (!$zdravniki): ?>
          <p>Trenutno ni zdravnikov v bazi.</p>
        <?php endif; ?>

        <?php foreach ($zdravniki as $d): ?>
          <article
            class="doctor-card"
            data-specializacija="<?= htmlspecialchars($d['specializacije']) ?>"
          >

            <?php if ($d['povprecje_ocen'] !== null): ?>
              <div
                class="rating-badge"
                title="<?= htmlspecialchars(
                  number_format((float)$d['povprecje_ocen'], 1, ',', '') .
                  ' (' . (int)$d['st_ocen'] . ' ocen)'
                ) ?>"
              >
                <span class="rating-star">★</span>
                <span class="rating-score">
                  <?= number_format((float)$d['povprecje_ocen'], 1, ',', '') ?>
                </span>
              </div>
            <?php endif; ?>

            <div class="doctor-photo">
              <img
                src="<?= htmlspecialchars(doctorImage($d['slika_url'] ?? null, (int)$d['id_zdravnik'])) ?>"
                alt="<?= htmlspecialchars($d['ime'].' '.$d['priimek']) ?>"
                loading="lazy"
              />
            </div>

            <div class="doctor-info">
              <h3 class="doctor-name">
                <?= htmlspecialchars($d['ime'].' '.$d['priimek']) ?>
              </h3>

              <p class="doctor-specialization">
                <?= $d['specializacije'] ? htmlspecialchars($d['specializacije']) : '—' ?>
              </p>

              <a href="profil_zdravnik.php?id=<?= (int)$d['id_uporabnik'] ?>" class="read-more-btn">
                Preberi več
              </a>
            </div>

          </article>
        <?php endforeach; ?>

      </div>
    </div>
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
  document.addEventListener("DOMContentLoaded", () => {
    // obnovi
    const savedScroll = sessionStorage.getItem("indexScrollY");
    if (savedScroll !== null) {
      window.scrollTo(0, parseInt(savedScroll, 10));
      sessionStorage.removeItem("indexScrollY");
    }

    // shrani ob kliku na "Preberi več"
    document.querySelectorAll(".read-more-btn").forEach((link) => {
      link.addEventListener("click", () => {
        sessionStorage.setItem("indexScrollY", String(window.scrollY));
      });
    });

    // za preklop
    const gumbiNacina = document.querySelectorAll(".mode-btn");
    const panelSpec = document.getElementById("toolSpec");
    const panelIme = document.getElementById("toolIme");
    const panelLok = document.getElementById("toolLokacija");

    // za filtriranje
    const gumbiFilter = document.querySelectorAll(".filter-btn");
    const kartice = document.querySelectorAll(".doctor-card");
    const vnosIskanja = document.getElementById("doctorSearch");

    let aktivniFilter = "all";
    let iskalniNiz = "";

    function uporabiFiltre() {
      const q = iskalniNiz.toLowerCase().trim();

      kartice.forEach((k) => {
        const spec = (k.dataset.specializacija || "").toLowerCase();
        const ime = (k.querySelector(".doctor-name")?.textContent || "").toLowerCase();

        const ujemaSpec = (aktivniFilter === "all") || spec.includes(aktivniFilter.toLowerCase());
        const ujemaIskanje = (q === "") || ime.includes(q) || spec.includes(q);

        k.style.display = (ujemaSpec && ujemaIskanje) ? "block" : "none";
      });
    }

    // skrije vse panele in pokaže samo izbranega
    function nastaviNacin(nacin) {
      panelSpec.classList.add("skrito");
      panelIme.classList.add("skrito");
      panelLok.classList.add("skrito");

      if (nacin === "spec") panelSpec.classList.remove("skrito");
      if (nacin === "ime") panelIme.classList.remove("skrito");
      if (nacin === "lokacija") panelLok.classList.remove("skrito");
    }

    // klik na gumbe načina
    gumbiNacina.forEach((gumb) => {
      gumb.addEventListener("click", () => {
        gumbiNacina.forEach((g) => g.classList.remove("active"));
        gumb.classList.add("active");

        const nacin = gumb.dataset.mode;
        nastaviNacin(nacin);

        if (nacin === "spec") {
          // počisti iskanje
          if (vnosIskanja) vnosIskanja.value = "";
          iskalniNiz = "";
          uporabiFiltre();
        }

        if (nacin === "ime") {
          // reset filter na "Vsi"
          aktivniFilter = "all";
          gumbiFilter.forEach((b) => b.classList.remove("active"));
          document.querySelector('.filter-btn[data-filter="all"]')?.classList.add("active");
          uporabiFiltre();

          // fokus na search
          vnosIskanja?.focus();
        }

        // lokacija
      });
    });

    // privzeto prikaži specializacije
    nastaviNacin("spec");

    // klik na filter gumbe
    gumbiFilter.forEach((g) => {
      g.addEventListener("click", () => {
        gumbiFilter.forEach((x) => x.classList.remove("active"));
        g.classList.add("active");

        aktivniFilter = g.dataset.filter || "all";
        uporabiFiltre();
      });
    });

    // vnos v search
    if (vnosIskanja) {
      vnosIskanja.addEventListener("input", (e) => {
        iskalniNiz = e.target.value || "";
        uporabiFiltre();
      });
    }
  });
</script>


</html>
