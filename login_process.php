<?php
require __DIR__ . '/povezava.php';

// samo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prijava');
    exit;
}

// pridobi podatke
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// zbriši presledek na začetki pa konci
$email = trim($email);

// če je prazno
if ($email === '' || $password === '') {
    header('Location: prijava.php?error=prazna_polja');
    exit;
}

$stmt = $conn->prepare("
    SELECT id_uporabnik, ime, priimek, email, geslo, vloga
    FROM uporabnik
    WHERE email = ?
    LIMIT 1
");

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// če uporabnik ne obstaja
if (!$user) {
    header('Location: prijava.php?error=ne_obstaja');
    exit;
}

// preverjanje gesla
if ($password !== $user['geslo']) {
    header('Location: prijava.php?error=narobno_geslo');
    exit;
}

// shrani
$_SESSION['user_id'] = (int)$user['id_uporabnik'];
$_SESSION['user_ime'] = $user['ime'];
$_SESSION['user_priimek'] = $user['priimek'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_vloga'] = $user['vloga'];

header('Location: index');
exit;
