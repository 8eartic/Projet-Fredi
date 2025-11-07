// === UTILITAIRES ===
const $ = id => document.getElementById(id);
const show = el => el.style.display = '';
const hide = el => el.style.display = 'none';
const msg = (text, type = '') => {
  const m = $('message');
  m.className = 'message ' + (type === 'success' ? 'success' : type === 'error' ? 'error' : '');
  m.textContent = text;
};

// === GESTION DES BOUTONS ===
$('btn-login').addEventListener('click', () => {
  hide($('register-form'));
  hide($('message'));
  show($('login-form'));
});

$('btn-register').addEventListener('click', () => {
  hide($('login-form'));
  hide($('message'));
  show($('register-form'));
});

// === INSCRIPTION ===
async function authSignUp(e) {
  e.preventDefault();

  const data = {
    nom: $('reg-nom').value.trim(),
    prenom: $('reg-prenom').value.trim(),
    adresse: $('reg-adresse').value.trim(),
    tel: $('reg-tel').value.trim(),
    mobile: $('reg-mobile').value.trim(),
    email: $('reg-email').value.trim().toLowerCase(),
    mdp: $('reg-password').value
  };

  try {
    const res = await fetch('register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await res.json();
    msg(result.message, result.status);
  } catch (err) {
    msg('Erreur serveur : ' + err.message, 'error');
    console.error(err);
  }
}

// === CONNEXION ===
async function authSignIn(e) {
  e.preventDefault(); // très important ! sinon le formulaire se soumet normalement

  const data = {
    email: document.getElementById('login-email').value.trim().toLowerCase(),
    password: document.getElementById('login-password').value
  };

  try {
    const res = await fetch('login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await res.json();
    alert(result.message); // pour tester
    if (result.status === 'success') {
      window.location.href = 'Main.html'; // redirection
    }
  } catch (err) {
    alert('Erreur serveur : ' + err.message);
    console.error(err);
  }
}


// === DÉCONNEXION ===
$('btn-logout').addEventListener('click', () => {
  // Ici tu peux détruire la session côté PHP si besoin
  fetch('logout.php').then(() => {
    msg('Déconnecté.', 'success');
    hide($('dashboard'));
    show($('forms'));
  });
});
