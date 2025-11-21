<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Prijava / Registracija</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/stylePrijava.css?v=5" />
</head>
<body>

<header class="navbar">
    <div class="container">
        <div class="logo">
          <img src="img/logo.png" alt="Logo" />
        </div>
        <nav>
          <ul>
              <li><a href="index.php">Domov</a></li>
              <li><a href="#">Poišči zdravnika</a></li>
              <li><a href="#">Specialnosti</a></li>
              <li><a href="#">Mesta / regije</a></li>
              <li><a href="#">Zdravstvene ustanove</a></li>
              <li><a href="#">Kontakt</a></li>
          </ul>
        </nav>
        <a href="prijava.php" class="btn">Prijava</a>
      </div>
</header>

<div class="auth-wrapper">
    <div class="panel panel-left">

        <h2>Dobrodošli nazaj!</h2>
        <p>Da ostaneš povezan z nami, se prosim prijavi s svojim osebnim računom.</p>

        <form action="login_process.php" method="post">
            <button type="submit" class="btn-outline">Prijava</button>
        </form>
    </div>

    <div class="panel panel-right">
        <h2>Ustvari račun</h2>

        <form action="register_process.php" method="post">
            <div class="form-group">
                <input type="text" class="form-input" name="name" placeholder="Ime" required>
            </div>
            <div class="form-group">
                <input type="text" class="form-input" name="surname" placeholder="Priimek" required>
            </div>
            <div class="form-group">
                <input type="email" class="form-input" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-input" name="password" placeholder="Geslo" required>
            </div>

            <button type="submit" class="btn-primary">Registracija</button>
        </form>
    </div>
</div>

</body>
</html>
