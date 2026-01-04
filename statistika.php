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

$stZdravnikov = 0;
$stSpecializacij = 0;
$stUstanov = 0;

if (isset($conn) && $conn instanceof mysqli) {
  // zdravniki
  if ($res = $conn->query("SELECT COUNT(*) AS c FROM zdravnik")) {
    $row = $res->fetch_assoc();
    $stZdravnikov = (int)($row['c'] ?? 0);
    $res->free();
  }

  // specializacije
  if ($res = $conn->query("SELECT COUNT(*) AS c FROM specializacija")) {
    $row = $res->fetch_assoc();
    $stSpecializacij = (int)($row['c'] ?? 0);
    $res->free();
  }

  // ustanove
  if ($res = $conn->query("SELECT COUNT(DISTINCT klinika) AS c FROM zdravnik WHERE klinika IS NOT NULL AND klinika <> ''")) {
    $row = $res->fetch_assoc();
    $stUstanov = (int)($row['c'] ?? 0);
    $res->free();
  }
}

// zdanje ocene
$ocene = [];
if (isset($conn) && $conn instanceof mysqli) {
  $sql = "
    SELECT
      o.id_ocene,
      o.ocena,
      o.komentar,
      u.ime AS uporabnik_ime,
      u.priimek AS uporabnik_priimek,
      uu.ime AS zdravnik_ime,
      uu.priimek AS zdravnik_priimek
    FROM ocene o
    JOIN uporabnik u ON u.id_uporabnik = o.TK_uporabnik
    JOIN zdravnik z ON z.id_zdravnik = o.TK_zdravnik
    JOIN uporabnik uu ON uu.id_uporabnik = z.TK_uporabnik
    ORDER BY o.id_ocene DESC
    LIMIT 8
  ";
  if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
      $ocene[] = $row;
    }
    $res->free();
  }
}

function zvezdice(int $ocena): string {
  $ocena = max(1, min(5, $ocena));
  return str_repeat('★', $ocena) . str_repeat('☆', 5 - $ocena);
}

// graf: zdravniki po specialnosti
$specLabels = [];
$specCounts = [];

$sqlSpec = "
  SELECT s.naziv AS naziv, COUNT(sz.TK_zdravnik) AS st
  FROM specializacija s
  LEFT JOIN specializacija_zdravnik sz
    ON sz.TK_specializacija = s.id_specializacija
  GROUP BY s.id_specializacija, s.naziv
  ORDER BY st DESC, s.naziv ASC
";

if ($res = $conn->query($sqlSpec)) {
  while ($row = $res->fetch_assoc()) {
    $specLabels[] = (string)$row['naziv'];
    $specCounts[] = (int)$row['st'];
  }
  $res->free();
}

// graf: zdravniki po ustanovi (klinika)
$ustLabels = [];
$ustCounts = [];

$sqlUst = "
  SELECT klinika AS naziv, COUNT(*) AS st
  FROM zdravnik
  WHERE klinika IS NOT NULL AND klinika <> ''
  GROUP BY klinika
  ORDER BY st DESC, naziv ASC
";

if ($res = $conn->query($sqlUst)) {
  while ($row = $res->fetch_assoc()) {
    $ustLabels[] = (string)$row['naziv'];
    $ustCounts[] = (int)$row['st'];
  }
  $res->free();
}

// graf: zdravniki po mestu
$mestaLabels = [];
$mestaCounts = [];

$sqlMesta = "
  SELECT mesto AS naziv, COUNT(*) AS st
  FROM zdravnik
  WHERE mesto IS NOT NULL AND mesto <> ''
  GROUP BY mesto
  ORDER BY st DESC, naziv ASC
";

if ($res = $conn->query($sqlMesta)) {
  while ($row = $res->fetch_assoc()) {
    $mestaLabels[] = (string)$row['naziv'];
    $mestaCounts[] = (int)$row['st'];
  }
  $res->free();
}

// graf: povprečna ocena vsakega zdravnika
$zdrLabels = [];
$zdrAvg = [];

$sqlZdr = "
  SELECT z.id_zdravnik,
  CONCAT(u.ime, ' ', u.priimek) AS zdravnik,
  ROUND(AVG(o.ocena), 2) AS povprecje
  FROM zdravnik z
  JOIN uporabnik u ON u.id_uporabnik = z.TK_uporabnik
  LEFT JOIN ocene o ON o.TK_zdravnik = z.id_zdravnik
  GROUP BY z.id_zdravnik, u.ime, u.priimek
  ORDER BY povprecje DESC, zdravnik ASC
";

if ($res = $conn->query($sqlZdr)) {
  while ($row = $res->fetch_assoc()) {
    $zdrLabels[] = (string)$row['zdravnik'];
    // če ni ocen, AVG je NULL -> damo 0
    $zdrAvg[] = $row['povprecje'] !== null ? (float)$row['povprecje'] : 0.0;
  }
  $res->free();
}

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
    <link rel="stylesheet" href="styles/styleStatistika.css?v=5" />
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
              <a href="/spletniimenikzdravnikov/ustanove" class="nav-link"
                >Zdravstvene ustanove</a
              >
            </li>
            <li>
              <a
                href="/spletniimenikzdravnikov/statistika"
                class="nav-link active"
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
          <div class="spec-kicker">NAŠA STATISTIKA</div>
          <h1>Pregled podatkov <span>v imeniku</span></h1>
        </div>
      </section>

      <section class="dashboard">
        <div class="stats-top">
          <div class="stat-card">
            <div class="stat-ico" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path
                  d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
                <circle
                  cx="9"
                  cy="7"
                  r="4"
                  stroke="currentColor"
                  stroke-width="2"
                />
                <path
                  d="M20 8v6"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
                <path
                  d="M23 11h-6"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
            </div>
            <div class="stat-meta">
              <div class="stat-label">Število zdravnikov</div>
              <div class="stat-value"><?= (int)$stZdravnikov ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-ico" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path
                  d="M4 19V5a2 2 0 0 1 2-2h9l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"
                  stroke="currentColor"
                  stroke-width="2"
                />
                <path d="M14 3v6h6" stroke="currentColor" stroke-width="2" />
                <path
                  d="M8 13h8"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
                <path
                  d="M8 17h5"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
            </div>
            <div class="stat-meta">
              <div class="stat-label">Število specializacij</div>
              <div class="stat-value"><?= (int)$stSpecializacij ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-ico" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path
                  d="M3 21V7a2 2 0 0 1 2-2h6l2 2h6a2 2 0 0 1 2 2v12"
                  stroke="currentColor"
                  stroke-width="2"
                />
                <path
                  d="M7 21v-4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v4"
                  stroke="currentColor"
                  stroke-width="2"
                />
                <path
                  d="M12 11v2"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
                <path
                  d="M11 12h2"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
            </div>
            <div class="stat-meta">
              <div class="stat-label">Število ustanov</div>
              <div class="stat-value"><?= (int)$stUstanov ?></div>
            </div>
          </div>
        </div>

        <div class="dash-grid">
          <div>
            <div class="card-box">
              <div class="card-head">
                <div class="card-title">Povprečna ocena vsakega zdravnika</div>
              </div>
              <div class="card-body">
                <div class="chart-placeholder mini chart-wrap">
                  <canvas id="zdravnikiChart"></canvas>
                </div>
              </div>
            </div>

            <div class="charts-row">
              <div class="card-box">
                <div class="card-head">
                  <div class="card-title">Specialnosti</div>
                </div>
                <div class="card-body">
                  <div class="chart-placeholder mini chart-wrap">
                    <canvas id="specialnostiChart"></canvas>
                  </div>
                </div>
              </div>

              <div class="card-box">
                <div class="card-head">
                  <div class="card-title">Ustanove</div>
                </div>
                <div class="card-body">
                  <div class="chart-placeholder mini chart-wrap">
                    <canvas id="ustanoveChart"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div style="display: grid; gap: 18px">
            <div class="card-box">
              <div class="card-head">
                <div class="card-title">Mesta</div>
              </div>
              <div class="card-body">
                <div class="chart-placeholder mini chart-wrap">
                  <canvas id="mestaChart"></canvas>
                </div>
              </div>
            </div>

            <div class="card-box">
              <div class="card-head">
                <div class="card-title">Zadnje ocene in mnenja</div>
              </div>
              <div class="card-body">
                <?php
                    // samo zadnje 3 ocene
                    $zadnjeOcene = array_slice($ocene, -3);
                    ?>

                <?php if (empty($zadnjeOcene)): ?>
                <div style="color: #64748b; font-size: 13px">
                  Trenutno ni ocen.
                </div>
                <?php else: ?>
                <div class="reviews-list">
                  <?php foreach ($zadnjeOcene as $o): ?>
                  <?php
                    $ime = trim(($o['uporabnik_ime'] ?? '') . ' ' . ($o['uporabnik_priimek'] ?? ''));
                    $doc = trim(($o['zdravnik_ime'] ?? '') . ' ' . ($o['zdravnik_priimek'] ?? ''));
                    $komentar = trim((string)($o['komentar'] ?? ''));
                    if ($komentar === '') $komentar = 'Brez komentarja.';
                        ?>
                  <div class="review-item">
                    <div class="review-top">
                      <div class="review-name">
                        <?= htmlspecialchars($ime !== '' ? $ime : 'Uporabnik') ?>
                      </div>
                      <div
                        class="review-stars"
                        title="<?= (int)$o['ocena'] ?>/5"
                      >
                        <?= htmlspecialchars(zvezdice((int)$o['ocena'])) ?>
                      </div>
                    </div>
                    <div class="review-text">
                      <?= htmlspecialchars($komentar) ?>
                    </div>
                    <div class="review-doc">
                      Za zdravnika:
                      <strong
                        ><?= htmlspecialchars($doc !== '' ? $doc : '—') ?></strong
                      >
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
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

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    (function () {
      const labels = <?= json_encode($specLabels, JSON_UNESCAPED_UNICODE) ?> || [];
      const values = <?= json_encode($specCounts, JSON_UNESCAPED_UNICODE) ?> || [];

      const canvas = document.getElementById('specialnostiChart');
      if (!canvas || labels.length === 0) return;

      new Chart(canvas, {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{
            label: 'Št. zdravnikov',
            data: values,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: false,
            title: { display: false }
          }
        }
      });
    })();
  </script>

  <script>
    (function () {
      const labels = <?= json_encode($ustLabels, JSON_UNESCAPED_UNICODE) ?> || [];
      const values = <?= json_encode($ustCounts, JSON_UNESCAPED_UNICODE) ?> || [];

      const canvas = document.getElementById('ustanoveChart');
      const wrap = canvas?.closest('.chart-wrap');

      if (!canvas || labels.length === 0) {
        if (wrap) wrap.innerHTML = '<div style="padding:12px;color:#64748b;font-size:13px">Ni podatkov za prikaz grafa.</div>';
        return;
      }

      new Chart(canvas, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Št. zdravnikov',
            data: values,
            borderWidth: 2,
            borderRadius: Number.MAX_VALUE,
            borderSkipped: false
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: false,
            title: { display: false}
          },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
          }
        }
      });
    })();
  </script>

  <script>
    (function () {
      const labels = <?= json_encode($mestaLabels, JSON_UNESCAPED_UNICODE) ?> || [];
      const values = <?= json_encode($mestaCounts, JSON_UNESCAPED_UNICODE) ?> || [];

      const canvas = document.getElementById('mestaChart');
      const wrap = canvas?.closest('.chart-wrap');

      if (!canvas || labels.length === 0) {
        if (wrap) wrap.innerHTML = '<div style="padding:12px;color:#64748b;font-size:13px">Ni podatkov za prikaz grafa.</div>';
        return;
      }

      new Chart(canvas, {
        type: 'radar',
        data: {
          labels,
          datasets: [
          {
              data: values,
              fill: true
          }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            title: { display: false}
          },
          scales: {
            r: {
              beginAtZero: true,
              ticks: { display: false }
            }
          }
        }
      });
    })();
  </script>

  <script>
    (function () {
      const labels = <?= json_encode($zdrLabels, JSON_UNESCAPED_UNICODE) ?> || [];
      const values = <?= json_encode($zdrAvg, JSON_UNESCAPED_UNICODE) ?> || [];

      const canvas = document.getElementById('zdravnikiChart');
      const wrap = canvas?.closest('.chart-wrap');

      if (!canvas || labels.length === 0) {
        if (wrap) wrap.innerHTML = '<div style="padding:12px;color:#64748b;font-size:13px">Ni podatkov za prikaz grafa.</div>';
        return;
      }

      new Chart(canvas, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Povprečna ocena',
            data: values,
            tension: 0.3,
            fill: true,
            pointRadius: 3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            title: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              min: 0,
              max: 5,
              ticks: { stepSize: 1 }
            }
          }
        }
      });
    })();
  </script>
</html>

