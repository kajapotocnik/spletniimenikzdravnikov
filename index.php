<?php
require __DIR__ . '/povezava.php';

// Preberi zdravnike
$sql = "
  SELECT
    d.id_zdravnik,
    u.ime,
    u.priimek,
    GROUP_CONCAT(DISTINCT s.naziv ORDER BY s.naziv SEPARATOR ', ') AS specializacije,
    ROUND(AVG(o.ocena), 1)  AS povprecje_ocen,
    COUNT(o.ocena) AS st_ocen
  FROM zdravnik d
  JOIN uporabnik u ON u.id_uporabnik = d.TK_uporabnik
  LEFT JOIN ocene o ON o.TK_zdravnik = d.id_zdravnik
  LEFT JOIN specializacija_zdravnik sz ON sz.TK_zdravnik = d.id_zdravnik
  LEFT JOIN specializacija s ON s.id_specializacija = sz.TK_specializacija
  GROUP BY d.id_zdravnik, u.ime, u.priimek
  ORDER BY u.priimek, u.ime
";

$specSql = "
    SELECT id_specializacija, naziv 
    FROM specializacija
    ORDER BY naziv
";
$specRezultat = $conn->query($specSql); $specializacija = $specRezultat ?
$specRezultat->fetch_all(MYSQLI_ASSOC) : []; $rezultat = $conn->query($sql);
$zdravniki = []; if ($rezultat) { $zdravniki = $rezultat->fetch_all(MYSQLI_ASSOC); }
function doctorImage(int $id): string { $path = "img/doctors/$id.jpg"; return
file_exists(__DIR__ . "/$path") ? $path : "img/doctor-placeholder.jpg"; } ?>
<!DOCTYPE html>
<html lang="sl">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Spletni imenik zdravnikov</title>

    <link rel="stylesheet" href="styles/style.css?v=5" />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
  </head>
  <body>
    <header class="navbar">
      <div class="container">
        <div class="logo">
          <img src="img/logo.png" alt="Logo" />
        </div>
        <nav>
          <ul>
            <li><a href="#">Domov</a></li>
            <li><a href="#">Povezava</a></li>
            <li><a href="#">Povezava</a></li>
            <li><a href="#">Povezava</a></li>
          </ul>
        </nav>
        <a href="#" class="btn">Prijava</a>
      </div>
    </header>

    <section class="hero">
      <div class="hero-overlay">
        <h1>Naši zdravniki</h1>
        <p>Domov / Naši zdravniki</p>
      </div>
    </section>

    <main class="py-5">
      <div class="doctors">
        <div class="doctor-filters">
          <button class="filter-btn active" data-filter="all">Vsi</button>

          <?php foreach ($specializacija as $s): ?>
          <button
            class="filter-btn"
            data-filter="<?= htmlspecialchars($s['naziv']) ?>"
          >
            <?= htmlspecialchars($s['naziv']) ?>
          </button>
          <?php endforeach; ?>
        </div>

        <div
          class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-4 justify-content-center"
        >
          <?php if (!$zdravniki): ?>
          <div class="col">
            <div class="alert alert-warning">Ni zdravnikov v bazi.</div>
          </div>
          <?php endif; ?>

          <?php foreach ($zdravniki as $d): ?>
          <div class="col">
            <article
              class="doctor-card h-100 mx-auto"
              data-specializacija="<?= htmlspecialchars($d['specializacije']) ?>"
            >
              <?php if ($d['povprecje_ocen'] !== null): ?>
              <div
                class="rating-badge"
                title="<?= htmlspecialchars(number_format((float)$d['povprecje_ocen'], 1, ',', '') . ' (' . (int)$d['st_ocen'] . ' ocen)') ?>"
                aria-label="Povprečna ocena: <?= htmlspecialchars(number_format((float)$d['povprecje_ocen'], 1, ',', '')) ?>"
              >
                <svg
                  class="star"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  width="14"
                  height="14"
                  aria-hidden="true"
                >
                  <path
                    fill="currentColor"
                    d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                  />
                </svg>
                <span class="score"
                  ><?= number_format((float)$d['povprecje_ocen'], 1, ',', '') ?></span
                >
              </div>
              <?php endif; ?>

              <div class="doctor-photo">
                <img
                  src="<?= htmlspecialchars(doctorImage((int)$d['id_zdravnik'])) ?>"
                  alt="<?= htmlspecialchars($d['ime'].' '.$d['priimek']) ?>"
                />
              </div>

              <div class="doctor-info">
                <div>
                  <h6 class="doctor-name mb-1">
                    <?= htmlspecialchars($d['ime'].' '.$d['priimek']) ?>
                  </h6>
                  <p class="doctor-role mb-0">
                    <?= $d['specializacije'] ? htmlspecialchars($d['specializacije']) : '—' ?>
                  </p>
                </div>
                <button class="see-more-btn">
                  Izvedi več
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    height="15"
                    width="15"
                    class="icon"
                  >
                    <path
                      stroke-linejoin="round"
                      stroke-linecap="round"
                      stroke-miterlimit="10"
                      stroke-width="1.5"
                      stroke="currentColor"
                      d="M8.91016 19.9201L15.4302 13.4001C16.2002 12.6301 16.2002 11.3701 15.4302 10.6001L8.91016 4.08008"
                    ></path>
                  </svg>
                </button>
              </div>
            </article>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>
  </body>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const buttons = document.querySelectorAll(".filter-btn");
      const cards = document.querySelectorAll(".doctor-card");

      buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
          buttons.forEach((b) => b.classList.remove("active"));
          btn.classList.add("active");

          const filter = btn.dataset.filter;

          cards.forEach((card) => {
            const spec = card.dataset.specializacija;

            if (filter === "all" || spec.includes(filter)) {
              card.parentElement.style.display = "";
            } else {
              card.parentElement.style.display = "none";
            }
          });
        });
      });
    });
  </script>
</html>
