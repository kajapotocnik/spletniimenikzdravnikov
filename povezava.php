<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 

$streznik = "localhost:3306";
$uporabnik = "root";
$geslo = "sladoled";
$baza = "spletniimenikzdravnikov";

$conn = new mysqli($streznik, $uporabnik, $geslo, $baza);

if ($conn->connect_error) {
    die("Povezava ni uspela: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
