<?php
require __DIR__ . '/povezava.php';

// samo ADMIN
if (!isset($_SESSION['user_id']) || (($_SESSION['user_vloga'] ?? '') !== 'ADMIN')) {
  header('Location: prijava.php');
  exit;
}

$tab = $_GET['tab'] ?? 'users';
$tab = in_array($tab, ['users','doctors','ratings'], true) ? $tab : 'users';

// uporabniki
$users = [];
$uRes = $conn->query("SELECT id_uporabnik, ime, priimek, email, vloga FROM uporabnik ORDER BY id_uporabnik DESC");
if ($uRes) $users = $uRes->fetch_all(MYSQLI_ASSOC);

// zdravniki
$doctors = [];
$dSql = "
  SELECT d.id_zdravnik, d.TK_uporabnik, u.ime, u.priimek, u.email, u.vloga,
         d.naziv, d.telefon, d.klinika, d.mesto
  FROM zdravnik d
  JOIN uporabnik u ON u.id_uporabnik = d.TK_uporabnik
  ORDER BY d.id_zdravnik DESC
";
$dRes = $conn->query($dSql);
if ($dRes) $doctors = $dRes->fetch_all(MYSQLI_ASSOC);

// ocene
$ratings = [];
$rSql = "
  SELECT o.id_ocene, o.ocena, o.komentar, 
         u1.ime AS u_ime, u1.priimek AS u_priimek,
         u2.ime AS d_ime, u2.priimek AS d_priimek,
         d.id_zdravnik
  FROM ocene o
  JOIN uporabnik u1 ON u1.id_uporabnik = o.TK_uporabnik
  JOIN zdravnik d ON d.id_zdravnik = o.TK_zdravnik
  JOIN uporabnik u2 ON u2.id_uporabnik = d.TK_uporabnik
  ORDER BY o.id_ocene DESC
";
$rRes = $conn->query($rSql);
if ($rRes) $ratings = $rRes->fetch_all(MYSQLI_ASSOC);

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// za avatar
$ime = $_SESSION['user_ime'] ?? 'Admin';
$priimek = $_SESSION['user_priimek'] ?? '';
$initials = mb_strtoupper(mb_substr($ime,0,1,'UTF-8') . mb_substr($priimek,0,1,'UTF-8'), 'UTF-8');
if (trim($initials) === '') $initials = 'A';
?>
<!doctype html>
<html lang="sl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/admin.css?v=1">
</head>
<body>

<div class="admin-layout">

  <aside class="sidebar">
    <div class="sb-brand">
      <div class="sb-logo">AD</div>
      <div>
        <div class="sb-title">Admin Panel</div>
        <div class="sb-sub">upravljanje sistema</div>
      </div>
    </div>

    <nav class="sb-nav">
      <a class="sb-link <?= $tab==='users'?'active':'' ?>" href="admin_panel.php?tab=users"> Uporabniki </a>
      <a class="sb-link <?= $tab==='doctors'?'active':'' ?>" href="admin_panel.php?tab=doctors"> Zdravniki </a>
      <a class="sb-link <?= $tab==='ratings'?'active':'' ?>" href="admin_panel.php?tab=ratings"> Ocene </a>

      <div class="sb-sep"></div>

      <a class="sb-link" href="index.php"> Nazaj na imenik </a>
      <a class="sb-link danger" href="logout.php"> <span class="sb-ico">↩</span> Odjava </a>
    </nav>

    <div class="sb-foot">
      <span class="dot"></span>
      <span>Admin način</span>
    </div>
  </aside>


  <main class="main">
    <header class="topbar">
      <div class="topbar-left">
        <div class="page-title">
          <?= $tab==='users' ? 'Uporabniki' : ($tab==='doctors' ? 'Zdravniki' : 'Ocene zdravnikov') ?>
        </div>
      </div>

      <div class="topbar-right">
        <div class="search">
          <input id="tableSearch" type="text" placeholder="Išči po tabeli...">
        </div>
      </div>
    </header>

    <section class="content">

    <!-- dodajanje uporabnika -->
      <?php if ($tab === 'users'): ?>
        <div class="card">
          <div class="card-head">
            <div>
              <div class="card-title">Dodaj uporabnika / zdravnika</div>
              <div class="card-sub">Ustvari nov račun.</div>
            </div>
          </div>

          <form class="form-grid" action="admin_actions.php" method="post">
            <input type="hidden" name="action" value="add_user">
            <div class="fg">
              <label>Ime</label>
              <input name="ime" required>
            </div>
            <div class="fg">
              <label>Priimek</label>
              <input name="priimek" required>
            </div>
            <div class="fg">
              <label>Email</label>
              <input name="email" type="email" required>
            </div>
            <div class="fg">
              <label>Geslo</label>
              <input name="geslo" type="password" required>
            </div>
            <div class="fg">
              <label>Vloga</label>
              <select name="vloga" required>
                <option value="UPORABNIK">UPORABNIK</option>
                <option value="ZDRAVNIK">ZDRAVNIK</option>
              </select>
            </div>
            <div class="fg fg-actions">
              <button class="btn-primary" type="submit">Dodaj</button>
            </div>
          </form>
        </div>

        <!-- seznam uporabnikov -->
        <div class="card">
          <div class="card-head">
            <div>
              <div class="card-title">Seznam uporabnikov</div>
              <div class="card-sub">Brisanje / sprememba podatkov / povišanje v zdravnika.</div>
            </div>
          </div>

          <div class="table-wrap">
            <table class="table" id="dataTable">
              <thead>
                <tr>
                  <th>ID</th><th>Ime</th><th>Priimek</th><th>Email</th><th>Vloga</th><th class="ta-right">Akcije</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td>#<?= e($u['id_uporabnik']) ?></td>
                  <td><?= e($u['ime']) ?></td>
                  <td><?= e($u['priimek']) ?></td>
                  <td><?= e($u['email']) ?></td>
                  <td>
                    <span class="pill <?= $u['vloga']==='ADMIN'?'pill-dark':($u['vloga']==='ZDRAVNIK'?'pill-teal':'pill-soft') ?>">
                      <?= e($u['vloga']) ?>
                    </span>
                  </td>
                  <td class="ta-right">
                    <details class="menu" data-menu>
                      <summary class="menu-btn">⋮</summary>
                      <div class="menu-drop">
                        <?php if ($u['vloga'] !== 'ZDRAVNIK' && $u['vloga'] !== 'ADMIN'): ?>
                          <form method="post" action="admin_actions.php">
                            <input type="hidden" name="action" value="promote_to_doctor">
                            <input type="hidden" name="id_uporabnik" value="<?= e($u['id_uporabnik']) ?>">
                            <button class="menu-item" type="submit">Spremeni v zdravnika</button>
                          </form>
                        <?php endif; ?>

                        <?php if ($u['vloga'] !== 'ADMIN'): ?>
                          <button class="menu-item" type="button"
                            data-id="<?= e($u['id_uporabnik']) ?>"
                            data-ime="<?= e($u['ime']) ?>"
                            data-priimek="<?= e($u['priimek']) ?>"
                            data-email="<?= e($u['email']) ?>"
                            data-vloga="<?= e($u['vloga']) ?>"
                            
                            onclick="closeAllMenus(); openUserEditFromBtn(this);">Uredi
                        </button>

                          <form method="post" action="admin_actions.php" onsubmit="return confirm('Res želiš izbrisati uporabnika? (izbriše tudi povezane podatke)')">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="id_uporabnik" value="<?= e($u['id_uporabnik']) ?>">
                            <button class="menu-item danger" type="submit">Izbriši</button>
                          </form>
                        <?php else: ?>
                          <div class="menu-item muted">Admina ne moreš brisati!</div>
                        <?php endif; ?>
                      </div>
                    </details>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- urejanje uporabnika -->
        <div class="modal" id="userModal" hidden>
          <div class="modal-card">
            <div class="modal-head">
              <div>
                <div class="modal-title">Uredi uporabnika</div>
                <div class="modal-sub">Spremeni osnovne podatke in vlogo.</div>
              </div>
              <button class="icon-btn" type="button" onclick="closeUserEdit()">✕</button>
            </div>

            <form class="form-grid" action="admin_actions.php" method="post">
              <input type="hidden" name="action" value="update_user">
              <input type="hidden" name="id_uporabnik" id="m_id">

              <div class="fg"><label>Ime</label><input name="ime" id="m_ime" required></div>
              <div class="fg"><label>Priimek</label><input name="priimek" id="m_priimek" required></div>
              <div class="fg"><label>Email</label><input name="email" id="m_email" type="email" required></div>
              <div class="fg">
                <label>Vloga</label>
                <select name="vloga" id="m_vloga" required>
                  <option value="UPORABNIK">UPORABNIK</option>
                  <option value="ZDRAVNIK">ZDRAVNIK</option>
                </select>
              </div>
              <div class="fg fg-actions">
                <button class="btn-primary" type="submit">Shrani</button>
                <button class="btn-ghost" type="button" onclick="closeUserEdit()">Prekliči</button>
              </div>
            </form>
          </div>
        </div>

        <!-- zdravniki -->
      <?php elseif ($tab === 'doctors'): ?>

        <div class="card">
          <div class="card-head">
            <div>
              <div class="card-title">Seznam zdravnikov</div>
              <div class="card-sub">Uredi podatke zdravnika.</div>
            </div>
          </div>

          <div class="table-wrap">
            <table class="table" id="dataTable">
              <thead>
                <tr>
                  <th>ID zdravnik</th><th>Ime</th><th>Email</th><th>Naziv</th><th>Klinika</th><th>Mesto</th><th class="ta-right">Akcije</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($doctors as $d): ?>
                <tr>
                  <td>#<?= e($d['id_zdravnik']) ?></td>
                  <td><?= e($d['ime'].' '.$d['priimek']) ?></td>
                  <td><?= e($d['email']) ?></td>
                  <td><?= e($d['naziv']) ?></td>
                  <td><?= e($d['klinika']) ?></td>
                  <td><?= e($d['mesto']) ?></td>
                  <td class="ta-right">
                    <details class="menu" data-menu>
                      <summary class="menu-btn">⋮</summary>
                      <div class="menu-drop">
                        <button class="menu-item" type="button"
                            data-idz="<?= e($d['id_zdravnik']) ?>"
                            data-idu="<?= e($d['TK_uporabnik']) ?>"
                            data-ime="<?= e($d['ime']) ?>"
                            data-priimek="<?= e($d['priimek']) ?>"
                            data-email="<?= e($d['email']) ?>"
                            data-naziv="<?= e($d['naziv']) ?>"
                            data-telefon="<?= e($d['telefon']) ?>"
                            data-klinika="<?= e($d['klinika']) ?>"
                            data-mesto="<?= e($d['mesto']) ?>"
                        
                            onclick="closeAllMenus(); openDoctorEditFromBtn(this);">Uredi
                        </button>

                        <form method="post" action="admin_actions.php" onsubmit="return confirm('Izbrišem zdravnika (tudi ocene in povezave specializacij)?')">
                          <input type="hidden" name="action" value="delete_doctor">
                          <input type="hidden" name="id_zdravnik" value="<?= e($d['id_zdravnik']) ?>">
                          <input type="hidden" name="id_uporabnik" value="<?= e($d['TK_uporabnik']) ?>">
                          <button class="menu-item danger" type="submit">Izbriši</button>
                        </form>
                      </div>
                    </details>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- uredi zdravnika -->
        <div class="modal" id="doctorModal" hidden>
          <div class="modal-card">
            <div class="modal-head">
              <div>
                <div class="modal-title">Uredi zdravnika</div>
                <div class="modal-sub">Uredi osnovne podatke uporabnika in dodatne podatke iz tabele zdravnik.</div>
              </div>
              <button class="icon-btn" type="button" onclick="closeDoctorEdit()">✕</button>
            </div>

            <form class="form-grid" action="admin_actions.php" method="post">
              <input type="hidden" name="action" value="update_doctor">
              <input type="hidden" name="id_zdravnik" id="dm_idz">
              <input type="hidden" name="id_uporabnik" id="dm_idu">

              <div class="fg"><label>Ime</label><input name="ime" id="dm_ime" required></div>
              <div class="fg"><label>Priimek</label><input name="priimek" id="dm_priimek" required></div>
              <div class="fg"><label>Email</label><input name="email" id="dm_email" type="email" required></div>

              <div class="fg"><label>Naziv</label><input name="naziv" id="dm_naziv"></div>
              <div class="fg"><label>Telefon</label><input name="telefon" id="dm_telefon"></div>
              <div class="fg"><label>Klinika</label><input name="klinika" id="dm_klinika"></div>
              <div class="fg"><label>Mesto</label><input name="mesto" id="dm_mesto"></div>

              <div class="fg fg-actions">
                <button class="btn-primary" type="submit">Shrani</button>
                <button class="btn-ghost" type="button" onclick="closeDoctorEdit()">Prekliči</button>
              </div>
            </form>
          </div>
        </div>

        <!-- ocene -->
      <?php else: ?>

        <div class="card">
          <div class="card-head">
            <div>
              <div class="card-title">Ocene</div>
              <div class="card-sub">Briši ocene in komentarje.</div>
            </div>
          </div>

          <div class="table-wrap">
            <table class="table" id="dataTable">
              <thead>
                <tr>
                  <th>ID</th><th>Uporabnik</th><th>Zdravnik</th><th>Ocena</th><th>Komentar</th><th class="ta-right">Akcije</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($ratings as $r): ?>
                <tr>
                  <td>#<?= e($r['id_ocene']) ?></td>
                  <td><?= e($r['u_ime'].' '.$r['u_priimek']) ?></td>
                  <td><?= e($r['d_ime'].' '.$r['d_priimek']) ?> <span class="muted">(ID <?= e($r['id_zdravnik']) ?>)</span></td>
                  <td><span class="pill pill-teal"><?= e($r['ocena']) ?>/5</span></td>
                  <td class="clip"><?= e($r['komentar']) ?></td>
                  <td class="ta-right">
                    <form method="post" action="admin_actions.php" onsubmit="return confirm('Izbrišem to oceno?')">
                      <input type="hidden" name="action" value="delete_rating">
                      <input type="hidden" name="id_ocene" value="<?= e($r['id_ocene']) ?>">
                      <button class="btn-danger" type="submit">Izbriši</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php endif; ?>

    </section>
  </main>
</div>

<script>
  // iskanje fliter
  const s = document.getElementById('tableSearch');
  const t = document.getElementById('dataTable');

  if (s && t) {
    s.addEventListener('input', () => {
      const q = s.value.toLowerCase().trim();

      for (const row of t.tBodies[0].rows) {
        const txt = row.innerText.toLowerCase();
        row.style.display = txt.includes(q) ? '' : 'none';
      }
    });
  }

  // uporabnik tab
  const userModal = document.getElementById('userModal');

  function openUserEdit(id, ime, priimek, email, vloga) {
    document.getElementById('m_id').value = id;
    document.getElementById('m_ime').value = ime;
    document.getElementById('m_priimek').value = priimek;
    document.getElementById('m_email').value = email;
    document.getElementById('m_vloga').value = vloga === 'ZDRAVNIK' ? 'ZDRAVNIK' : 'UPORABNIK';
    userModal.hidden = false;
  }

  function closeUserEdit() {
    userModal.hidden = true;
  }

  // zdravnik tab
  const doctorModal = document.getElementById('doctorModal');

  function openDoctorEdit(idz, idu, ime, priimek, email, naziv, telefon, klinika, mesto) {
    document.getElementById('dm_idz').value = idz;
    document.getElementById('dm_idu').value = idu;
    document.getElementById('dm_ime').value = ime;
    document.getElementById('dm_priimek').value = priimek;
    document.getElementById('dm_email').value = email;
    document.getElementById('dm_naziv').value = naziv ?? '';
    document.getElementById('dm_telefon').value = telefon ?? '';
    document.getElementById('dm_klinika').value = klinika ?? '';
    document.getElementById('dm_mesto').value = mesto ?? '';
    doctorModal.hidden = false;
  }

  function closeDoctorEdit() {
    doctorModal.hidden = true;
  }

  // zapri tab
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (userModal) userModal.hidden = true;
      if (doctorModal) doctorModal.hidden = true;
    }

    // klik na ozadje zapre modal
    if (userModal) {
      userModal.addEventListener('click', (e) => {
        if (e.target === userModal) closeUserEdit();
      });
    }

    if (doctorModal) {
      doctorModal.addEventListener('click', (e) => {
        if (e.target === doctorModal) closeDoctorEdit();
      });
    }
  });

  // ⋮ + click + ESC
  const menus = () => Array.from(document.querySelectorAll('details[data-menu]'));

  function closeAllMenus(except = null) {
    menus().forEach(m => {
      if (m !== except) m.removeAttribute('open');
    });
  }

  // če odpreš en meni, zapri ostale
  document.addEventListener('toggle', (e) => {
    const d = e.target;

    if (d && d.matches && d.matches('details[data-menu]') && d.open) {
      closeAllMenus(d);
    }
  }, true);

  // klik kjerkoli izven -> zapri
  document.addEventListener('click', (e) => {
    const inside = e.target.closest && e.target.closest('details[data-menu]');
    if (!inside) closeAllMenus();
  });

  // ESC -> zapri
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAllMenus();
  });

  function openUserEditFromBtn(btn) {
    openUserEdit(
      btn.dataset.id,
      btn.dataset.ime,
      btn.dataset.priimek,
      btn.dataset.email,
      btn.dataset.vloga
    );
  }

  function openDoctorEditFromBtn(btn) {
    openDoctorEdit(
      btn.dataset.idz,
      btn.dataset.idu,
      btn.dataset.ime,
      btn.dataset.priimek,
      btn.dataset.email,
      btn.dataset.naziv,
      btn.dataset.telefon,
      btn.dataset.klinika,
      btn.dataset.mesto
    );
  }
</script>


</body>
</html>
