<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Prijava / Registracija</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/stylePrijava.css?v=6" />
</head>
<body>

<a href="index.php" class="back-btn">
  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" 
       viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
       stroke-linecap="round" stroke-linejoin="round">
    <polyline points="15 18 9 12 15 6"></polyline>
  </svg>
  Nazaj domov
</a>



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
