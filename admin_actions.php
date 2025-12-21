<?php
require __DIR__ . '/povezava.php';

// samo ADMIN
if (!isset($_SESSION['user_id']) || (($_SESSION['user_vloga'] ?? '') !== 'ADMIN')) {
  header('Location: prijava.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: admin_panel.php');
  exit;
}

$action = $_POST['action'] ?? '';

function back($tab = 'users') {
  header('Location: admin_panel.php?tab=' . urlencode($tab));
  exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

  // dodaj uporabnika / zdravnika
  if ($action === 'add_user') {
    $ime = trim($_POST['ime'] ?? '');
    $priimek = trim($_POST['priimek'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $geslo = $_POST['geslo'] ?? '';
    $vloga = $_POST['vloga'] ?? 'UPORABNIK';

    if (!in_array($vloga, ['UPORABNIK','ZDRAVNIK'], true)) $vloga = 'UPORABNIK';

    // preveri email
    $chk = $conn->prepare("SELECT 1 FROM uporabnik WHERE email=? LIMIT 1");
    $chk->bind_param('s', $email);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) { $chk->close(); back('users'); }
    $chk->close();

    $ins = $conn->prepare("INSERT INTO uporabnik (email, geslo, ime, priimek, vloga) VALUES (?,?,?,?,?)");
    $ins->bind_param('sssss', $email, $geslo, $ime, $priimek, $vloga);
    $ins->execute();
    $newUserId = (int)$ins->insert_id;
    $ins->close();

    if ($vloga === 'ZDRAVNIK') {
      $insD = $conn->prepare("INSERT INTO zdravnik (TK_uporabnik) VALUES (?)");
      $insD->bind_param('i', $newUserId);
      $insD->execute();
      $insD->close();
    }

    back('users');
  }

  // update uporabnika
  if ($action === 'update_user') {
    $id = (int)($_POST['id_uporabnik'] ?? 0);
    $ime = trim($_POST['ime'] ?? '');
    $priimek = trim($_POST['priimek'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $vloga = $_POST['vloga'] ?? 'UPORABNIK';
    if (!in_array($vloga, ['UPORABNIK','ZDRAVNIK'], true)) $vloga = 'UPORABNIK';

    // ne dovoli sprememb admina
    $cr = $conn->prepare("SELECT vloga FROM uporabnik WHERE id_uporabnik=? LIMIT 1");
    $cr->bind_param('i', $id);
    $cr->execute();
    $oldRole = ($cr->get_result()->fetch_assoc()['vloga'] ?? '');
    $cr->close();
    if ($oldRole === 'ADMIN') back('users');

    $up = $conn->prepare("UPDATE uporabnik SET ime=?, priimek=?, email=?, vloga=? WHERE id_uporabnik=?");
    $up->bind_param('ssssi', $ime, $priimek, $email, $vloga, $id);
    $up->execute();
    $up->close();

    // če je zdravnik -> zapis v zdravnik
    if ($vloga === 'ZDRAVNIK') {
      $chk = $conn->prepare("SELECT id_zdravnik FROM zdravnik WHERE TK_uporabnik=? LIMIT 1");
      $chk->bind_param('i', $id);
      $chk->execute();
      $row = $chk->get_result()->fetch_assoc();
      $chk->close();

      if (!$row) {
        $ins = $conn->prepare("INSERT INTO zdravnik (TK_uporabnik) VALUES (?)");
        $ins->bind_param('i', $id);
        $ins->execute();
        $ins->close();
      }
    }

    back('users');
  }

  // update v zdravnika
  if ($action === 'promote_to_doctor') {
    $id = (int)($_POST['id_uporabnik'] ?? 0);

    $up = $conn->prepare("UPDATE uporabnik SET vloga='ZDRAVNIK' WHERE id_uporabnik=? AND vloga<>'ADMIN'");
    $up->bind_param('i', $id);
    $up->execute();
    $up->close();

    $chk = $conn->prepare("SELECT id_zdravnik FROM zdravnik WHERE TK_uporabnik=? LIMIT 1");
    $chk->bind_param('i', $id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$row) {
      $ins = $conn->prepare("INSERT INTO zdravnik (TK_uporabnik) VALUES (?)");
      $ins->bind_param('i', $id);
      $ins->execute();
      $ins->close();
    }

    back('users');
  }

  // izbrišu uporabnika
  if ($action === 'delete_user') {
    $id = (int)($_POST['id_uporabnik'] ?? 0);

    // varovalka za admina
    $cr = $conn->prepare("SELECT vloga FROM uporabnik WHERE id_uporabnik=? LIMIT 1");
    $cr->bind_param('i', $id);
    $cr->execute();
    $role = ($cr->get_result()->fetch_assoc()['vloga'] ?? '');
    $cr->close();
    if ($role === 'ADMIN') back('users');

    // če je zdravnik, poberi id_zdravnik
    $did = null;
    $d = $conn->prepare("SELECT id_zdravnik FROM zdravnik WHERE TK_uporabnik=? LIMIT 1");
    $d->bind_param('i', $id);
    $d->execute();
    $dr = $d->get_result()->fetch_assoc();
    $d->close();
    if ($dr) $did = (int)$dr['id_zdravnik'];

    // izbriši ocene kere jih je uporabnik dal
    $delR1 = $conn->prepare("DELETE FROM ocene WHERE TK_uporabnik=?");
    $delR1->bind_param('i', $id);
    $delR1->execute();
    $delR1->close();

    if ($did !== null) {
      // izbriši ocene zdravnika
      $delR2 = $conn->prepare("DELETE FROM ocene WHERE TK_zdravnik=?");
      $delR2->bind_param('i', $did);
      $delR2->execute();
      $delR2->close();

      // izbriši povezave specializacij
      $delSZ = $conn->prepare("DELETE FROM specializacija_zdravnik WHERE TK_zdravnik=?");
      $delSZ->bind_param('i', $did);
      $delSZ->execute();
      $delSZ->close();

      // izbriši zdravnika
      $delD = $conn->prepare("DELETE FROM zdravnik WHERE id_zdravnik=?");
      $delD->bind_param('i', $did);
      $delD->execute();
      $delD->close();
    }

    // izbriši uporabnika
    $delU = $conn->prepare("DELETE FROM uporabnik WHERE id_uporabnik=?");
    $delU->bind_param('i', $id);
    $delU->execute();
    $delU->close();

    back('users');
  }

  // update zdravnik
  if ($action === 'update_doctor') {
    $idz = (int)($_POST['id_zdravnik'] ?? 0);
    $idu = (int)($_POST['id_uporabnik'] ?? 0);

    $ime = trim($_POST['ime'] ?? '');
    $priimek = trim($_POST['priimek'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $naziv = trim($_POST['naziv'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $klinika = trim($_POST['klinika'] ?? '');
    $mesto = trim($_POST['mesto'] ?? '');

    // update uporabnika, vloga pa naj ostane ZDRAVNIK
    $upU = $conn->prepare("UPDATE uporabnik SET ime=?, priimek=?, email=?, vloga='ZDRAVNIK' WHERE id_uporabnik=? AND vloga<>'ADMIN'");
    $upU->bind_param('sssi', $ime, $priimek, $email, $idu);
    $upU->execute();
    $upU->close();

    $upD = $conn->prepare("UPDATE zdravnik SET naziv=?, telefon=?, klinika=?, mesto=? WHERE id_zdravnik=?");
    $upD->bind_param('ssssi', $naziv, $telefon, $klinika, $mesto, $idz);
    $upD->execute();
    $upD->close();

    back('doctors');
  }

  // izbriši zdravnika
  if ($action === 'delete_doctor') {
    $idz = (int)($_POST['id_zdravnik'] ?? 0);
    $idu = (int)($_POST['id_uporabnik'] ?? 0);

    // ocene zdravnika
    $delR = $conn->prepare("DELETE FROM ocene WHERE TK_zdravnik=?");
    $delR->bind_param('i', $idz);
    $delR->execute();
    $delR->close();

    // specializacije
    $delSZ = $conn->prepare("DELETE FROM specializacija_zdravnik WHERE TK_zdravnik=?");
    $delSZ->bind_param('i', $idz);
    $delSZ->execute();
    $delSZ->close();

    // zdravnik
    $delD = $conn->prepare("DELETE FROM zdravnik WHERE id_zdravnik=?");
    $delD->bind_param('i', $idz);
    $delD->execute();
    $delD->close();

    // v UPORABNIK
    $up = $conn->prepare("UPDATE uporabnik SET vloga='UPORABNIK' WHERE id_uporabnik=? AND vloga<>'ADMIN'");
    $up->bind_param('i', $idu);
    $up->execute();
    $up->close();

    back('doctors');
  }

  // 7) izbriši oceno
  if ($action === 'delete_rating') {
    $id = (int)($_POST['id_ocene'] ?? 0);
    $del = $conn->prepare("DELETE FROM ocene WHERE id_ocene=?");
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();

    back('ratings');
  }

  back('users');

} catch (Throwable $e) {
  back('users');
}
