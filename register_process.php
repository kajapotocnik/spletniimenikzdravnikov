<?php
require __DIR__ . '/povezava.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prijava.php');
    exit;
}

// podatki iz forme
$ime     = trim($_POST['name']    ?? '');
$priimek = trim($_POST['surname'] ?? '');
$email   = trim($_POST['email']   ?? '');
$geslo   = $_POST['password']     ?? '';

// validacija
if ($ime === '' || $priimek === '' || $email === '' || $geslo === '') {
    header('Location: prijava.php?error=reg_prazna_polja');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: prijava.php?error=reg_email');
    exit;
}

// preveri, če email že obstaja
$check = $conn->prepare("SELECT 1 FROM uporabnik WHERE email = ? LIMIT 1");
$check->bind_param('s', $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    header('Location: prijava.php?error=reg_email_obstaja');
    exit;
}
$check->close();

// vedno navaden uporabnik
$vloga = 'UPORABNIK';

// insert uporabnika
$ins = $conn->prepare("
    INSERT INTO uporabnik (email, geslo, ime, priimek, vloga)
    VALUES (?, ?, ?, ?, ?)
");
$ins->bind_param('sssss', $email, $geslo, $ime, $priimek, $vloga);

if (!$ins->execute()) {
    $ins->close();
    header('Location: prijava.php?error=reg_neuspesno');
    exit;
}

$userId = (int)$ins->insert_id;
$ins->close();

// samodejna prijava
$_SESSION['user_id']      = $userId;
$_SESSION['user_ime']     = $ime;
$_SESSION['user_priimek'] = $priimek;
$_SESSION['user_email']   = $email;
$_SESSION['user_vloga']   = $vloga;

header('Location: index.php');
exit;
