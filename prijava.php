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

        <!-- PRIJAVA (skrita na začetku) -->
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


</body>

<script>
  const toggleBtn   = document.getElementById('toggleModeBtn');
  const leftTitle   = document.getElementById('leftTitle');
  const leftText    = document.getElementById('leftText');
  const formTitle   = document.getElementById('formTitle');
  const regForm     = document.getElementById('registerForm');
  const loginForm   = document.getElementById('loginForm');
  const panelRight  = document.querySelector('.panel-right');

  let isLoginMode = false;

  function triggerFade() {
    panelRight.classList.remove('panel-fade');
    void panelRight.offsetWidth;
    panelRight.classList.add('panel-fade');
  }

  function setRegisterMode() {
    isLoginMode = false;

    leftTitle.textContent = 'Dobrodošli nazaj!';
    leftText.textContent  = 'Da ostaneš povezan z nami, se prosim prijavi s svojim osebnim računom.';
    toggleBtn.textContent = 'Prijava';

    formTitle.textContent = 'Ustvari račun';

    regForm.style.display   = 'block';
    loginForm.style.display = 'none';

    triggerFade();
  }

  function setLoginMode() {
    isLoginMode = true;

    leftTitle.textContent = 'Pozdravljeni!';
    leftText.textContent  = 'Vnesite svoje podatke in naredite prvi korak na poti z nami.';
    toggleBtn.textContent = 'Registracija';

    formTitle.textContent = 'Prijava';

    regForm.style.display   = 'none';
    loginForm.style.display = 'block';

    triggerFade();
  }

  toggleBtn.addEventListener('click', () => {
    if (isLoginMode) {
      setRegisterMode();
    } else {
      setLoginMode();
    }
  });

  setRegisterMode();
</script>


</html>
