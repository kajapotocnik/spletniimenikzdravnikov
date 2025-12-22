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
        <h2 id="leftTitle">Dobrodošli nazaj!</h2>
        <p id="leftText">
            Da ostaneš povezan z nami, se prosim prijavi s svojim osebnim računom.
        </p>
        <button type="button" class="btn-outline" id="toggleModeBtn">
            Prijava
        </button>
    </div>

    <div class="panel panel-right">
        <h2 id="formTitle">Ustvari račun</h2>

        <!-- REGISTRACIJA -->
        <form id="registerForm" action="register_process.php" method="post">
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

        <!-- PRIJAVA -->
        <form id="loginForm" action="login_process.php" method="post">
            <div class="form-group">
                <input type="email" class="form-input" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-input" name="password" placeholder="Geslo" required>
            </div>

            <button type="submit" class="btn-primary">Prijava</button>
        </form>
    </div>
</div>

<script>
  const preklop = document.getElementById('toggleModeBtn');
  const naslovLevo = document.getElementById('leftTitle');
  const textLevo = document.getElementById('leftText');
  const naslov = document.getElementById('formTitle');
  const regObrazec = document.getElementById('registerForm');
  const prijObrazec = document.getElementById('loginForm');
  const desniPanel = document.querySelector('.panel-right');

  let isPrijava = false;

  function animacija() {
    desniPanel.classList.remove('panel-fade');
    void desniPanel.offsetWidth;
    desniPanel.classList.add('panel-fade');
  }

  function setRegistracija() {
    isPrijava = false;
    naslovLevo.textContent = 'Dobrodošli nazaj!';
    textLevo.textContent  = 'Da ostaneš povezan z nami, se prosim prijavi s svojim osebnim računom.';
    preklop.textContent = 'Prijava';
    naslov.textContent = 'Ustvari račun';
    regObrazec.style.display = 'block';
    prijObrazec.style.display = 'none';
    animacija();
  }

  function setPrijava() {
    isPrijava = true;
    naslovLevo.textContent = 'Pozdravljeni!';
    textLevo.textContent  = 'Vnesite svoje podatke in naredite prvi korak na poti z nami.';
    preklop.textContent = 'Registracija';
    naslov.textContent = 'Prijava';
    regObrazec.style.display = 'none';
    prijObrazec.style.display = 'block';
    animacija();
  }

  preklop.addEventListener('click', () => {
    if (isPrijava) setRegistracija();
    else setPrijava();
  });

  setPrijava();
</script>

</body>
</html>
