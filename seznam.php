<?php
require __DIR__ . '/povezava.php';

echo "<h1>Izpis vseh podatkov iz baze</h1>";

/*uporabniki*/
echo "<h2>Tabela: uporabnik</h2>";
$rezultat = $conn->query("SELECT * FROM uporabnik");
if ($rezultat && $rezultat->num_rows > 0) { //ali ma vsaj 1 vrstico
    echo "<table border='1' cellpadding='5'><tr>
            <th>id_uporabnik</th><th>email</th><th>geslo</th>
            <th>ime</th><th>priimek</th><th>vloga</th></tr>";
    while ($row = $rezultat->fetch_assoc()) { //dokler so vrstice
        echo "<tr>
                <td>{$row['id_uporabnik']}</td>
                <td>{$row['email']}</td>
                <td>{$row['geslo']}</td>
                <td>{$row['ime']}</td>
                <td>{$row['priimek']}</td>
                <td>{$row['vloga']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Ni uporabnikov.</p>";
}

/*zdravniki*/
echo "<h2>Tabela: zdravnik</h2>";
$rezultat = $conn->query("SELECT * FROM zdravnik");
if ($rezultat && $rezultat->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr>
            <th>id_zdravnik</th><th>TK_uporabnik</th><th>naziv</th>
            <th>telefon</th><th>spletnaStran</th><th>klinika</th>
            <th>mesto</th><th>postaSt</th><th>country</th>
          </tr>";
    while ($row = $rezultat->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id_zdravnik']}</td>
                <td>{$row['TK_uporabnik']}</td>
                <td>{$row['naziv']}</td>
                <td>{$row['telefon']}</td>
                <td>{$row['spletnaStran']}</td>
                <td>{$row['klinika']}</td>
                <td>{$row['mesto']}</td>
                <td>{$row['postaSt']}</td>
                <td>{$row['country']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Ni zdravnikov.</p>";
}

/*specializacija*/
echo "<h2>Tabela: specializacija</h2>";
$rezultat = $conn->query("SELECT * FROM specializacija");
if ($rezultat && $rezultat->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>id_specializacija</th><th>naziv</th></tr>";
    while ($row = $rezultat->fetch_assoc()) {
        echo "<tr><td>{$row['id_specializacija']}</td><td>{$row['naziv']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Ni specializacij.</p>";
}

/*specializacija_zdravnik*/
echo "<h2>Tabela: specializacija_zdravnik</h2>";
$rezultat = $conn->query("SELECT * FROM specializacija_zdravnik");
if ($rezultat && $rezultat->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>TK_specializacija</th><th>TK_zdravnik</th></tr>";
    while ($row = $rezultat->fetch_assoc()) {
        echo "<tr><td>{$row['TK_specializacija']}</td><td>{$row['TK_zdravnik']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Ni povezav zdravnik-specializacija.</p>";
}

/*ocene*/
echo "<h2>Tabela: ocene</h2>";
$rezultat = $conn->query("SELECT * FROM ocene");
if ($rezultat && $rezultat->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr>
            <th>id_ocene</th><th>ocena</th><th>komentar</th>
            <th>TK_uporabnik</th><th>TK_zdravnik</th>
          </tr>";
    while ($row = $rezultat->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id_ocene']}</td>
                <td>{$row['ocena']}</td>
                <td>{$row['komentar']}</td>
                <td>{$row['TK_uporabnik']}</td>
                <td>{$row['TK_zdravnik']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Ni ocen.</p>";
}

$conn->close();
?>
