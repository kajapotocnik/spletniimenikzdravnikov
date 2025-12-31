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

$slikeUstanov = [
  'CeljMed Center' => 'CeljMed Center.jpg',
  'DermaCenter' => 'DermaCenter.jpg',
  'MediKoper' => 'MediKoper.jpg',
  'Očesna klinika Koper' => 'Ocesna klinika Koper.jpg',
  'Ortopedska klinika Valdoltra' => 'Ortopedska klinika Valdoltra.jpg',
  'Pediatrična klinika' => 'Pediatricna klinika.jpg',
  'SB Celje' => 'SB Celje.jpg',
  'Srčni Center' => 'Srcni Center.jpg',
  'UKC Ljubljana' => 'UKC Ljubljana.jpg',
  'UKC Maribor' => 'UKC Maribor.jpg',
  'ZD Ljubljana Šiška' => 'ZD Ljubljana Siska.jpg',
  'Zdravje Plus' => 'Zdravje Plus.jpg',
  'Zobna ordinacija Mlakar' => 'Zobna ordinacija Mlakar.jpg',
];

$opisiUstanov = [
  'CeljMed Center' =>
    'CeljMed Center združuje sodobno diagnostiko, izkušene specialiste in osebni pristop k zdravljenju pacientov.',

  'DermaCenter' =>
    'DermaCenter je specializiran za dermatološko diagnostiko, zdravljenje kožnih bolezni in estetsko medicino.',

  'MediKoper' =>
    'MediKoper nudi širok spekter specialističnih ambulant z uporabo naprednih medicinskih tehnologij.',

  'Očna klinika Koper' =>
    'Očna klinika Koper se osredotoča na zdravljenje očesnih bolezni, kirurgijo vida in preventivne preglede.',

  'Ortopedska klinika Valdoltra' =>
    'Valdoltra je priznana ortopedska ustanova, specializirana za zdravljenje poškodb in bolezni gibalnega sistema.',

  'Pediatrična klinika' =>
    'Pediatrična klinika zagotavlja celostno zdravstveno oskrbo otrok in mladostnikov z multidisciplinarnim pristopom.',

  'SB Celje' =>
    'Splošna bolnišnica Celje nudi bolnišnično in specialistično zdravljenje z dolgoletno tradicijo.',

  'Srčni Center' =>
    'Srčni Center je specializiran za diagnostiko in zdravljenje bolezni srca ter ožilja.',

  'UKC Ljubljana' =>
    'UKC Ljubljana je največja zdravstvena ustanova v Sloveniji z vrhunsko klinično in raziskovalno dejavnostjo.',

  'UKC Maribor' =>
    'UKC Maribor zagotavlja terciarno zdravstveno oskrbo in sodobne diagnostične ter terapevtske postopke.',

  'ZD Ljubljana Šiška' =>
    'Zdravstveni dom Ljubljana Šiška nudi primarno zdravstveno oskrbo za prebivalce lokalne skupnosti.',

  'Zdravje Plus' =>
    'Zdravje Plus ponuja hitre in kakovostne zdravstvene storitve s poudarkom na preventivi.',

  'Zobna ordinacija Mlakar' =>
    'Zobna ordinacija Mlakar nudi sodobno zobozdravstveno oskrbo z individualnim pristopom k pacientom.',
];

?>

<!DOCTYPE html>
<html lang="sl">
  <head>
    <meta charset="UTF-8" />
    <title>Spletni imenik zdravnikov</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
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
            <li>
              <a href="/spletniimenikzdravnikov/" class="nav-link">Domov</a>
            </li>
            <li>
              <a href="/spletniimenikzdravnikov/zdravniki" class="nav-link"
                >Poišči zdravnika</a
              >
            </li>
            <li>
              <a href="/spletniimenikzdravnikov/specialnosti" class="nav-link"
                >Specialnosti</a
              >
            </li>
            <li>
              <a
                href="/spletniimenikzdravnikov/ustanove"
                class="nav-link active"
                >Zdravstvene ustanove</a
              >
            </li>
            <li>
              <a href="/spletniimenikzdravnikov/statistika" class="nav-link"
                >Statistika</a
              >
            </li>
            <li>
              <a href="/spletniimenikzdravnikov/kontakt" class="nav-link"
                >Kontakt</a
              >
            </li>
          </ul>
        </nav>

        <?php if (!$isLoggedIn): ?>
        <a href="/spletniimenikzdravnikov/prijava" class="btn-nav">Prijava</a>
        <?php else: ?>
        <div class="user-menu">
          <button class="user-menu-trigger" type="button">
            <span class="user-avatar"><?= htmlspecialchars($initials) ?></span>
            <span class="user-name"
              ><?= htmlspecialchars($userFullName) ?></span
            >
            <span class="user-chevron">
              <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  d="M6 9l6 6 6-6"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </span>
          </button>

          <div class="user-dropdown">
            <?php if ($isDoctor): ?>
            <a href="profil_zdravnik.php" class="user-dropdown-item"
              >Moj profil</a
            >
            <?php endif; ?>

            <?php if (($_SESSION['user_vloga'] ?? '') === 'ADMIN'): ?>
            <a href="admin_panel.php" class="user-dropdown-item"
              >Admin plošča</a
            >
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
          <?php
            $imeUstanove = $u['klinika'];

            if (isset($slikeUstanov[$imeUstanove])) {
                $imgPath = 'img/ustanove/' . $slikeUstanov[$imeUstanove];
            } else {
                $imgPath = 'img/ustanove/default.jpg';
            }
            ?>
          <div class="ustanova-left">
            <div
              class="ustanova-photo lazy-bg"
              data-bg="<?= htmlspecialchars($imgPath) ?>"
            ></div>

            <div class="ustanova-mini">
              <div class="mini-top">
                <span class="mini-name">
                  <?= htmlspecialchars($u['klinika']) ?>
                </span>
              </div>

              <div class="mini-from">
                LOKACIJA<br />
                <span
                  ><?= htmlspecialchars($u['mesta'] ?: 'ni podatka') ?></span
                >
              </div>
            </div>
          </div>

          <div class="ustanova-right">
            <span class="pill">O NAS</span>

            <h2 class="ustanova-title">
              <?= htmlspecialchars($u['klinika']) ?>
            </h2>

            <?php
            $imeUstanove = $u['klinika'];

            $opis = $opisiUstanov[$imeUstanove]
                ?? 'Združujemo strokovno medicinsko znanje, sodobno tehnologijo ter celostni pristop, osredotočen na pacienta.';
            ?>
            <p class="ustanova-desc"><?= htmlspecialchars($opis) ?></p>

            <div class="ustanova-actions">
              <a href="#" class="read-more-btnn">Več informacij</a>

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
    document.addEventListener("DOMContentLoaded", () => {
      const elementi = document.querySelectorAll(".lazy-bg");

      if (!("IntersectionObserver" in window)) {
        //če ne podpira IntersectionObserver
        // slike se naložijo brez lazy loading
        elementi.forEach((element) => {
          element.style.backgroundImage = `url('${element.dataset.bg}')`;
          element.classList.remove("lazy-bg");
        });
        return;
      }

      // vidno
      const najdi = new IntersectionObserver(
        (vnosi, obs) => {
          vnosi.forEach((vnos) => {
            if (!vnos.isIntersecting) return;

            const element = vnos.target;
            const src = element.dataset.bg;

            // data-bg
            if (src) element.style.backgroundImage = `url('${src}')`;

            // odstrani lazy-bg
            element.classList.remove("lazy-bg");
            obs.unobserve(element);
          });
        },
        {
          rootMargin: "200px 0px", //je 200px preden je vidna
        }
      );

      elementi.forEach((element) => najdi.observe(element));
    });
  </script>
</html>

