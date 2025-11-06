// Utilitaires simples
const $ = id => document.getElementById(id);
const show = (el) => el.style.display = '';
const hide = (el) => el.style.display = 'none';
const msg = (text, type = '') => {
    const m = $('message');
    m.className = 'message ' + (type === 'success' ? 'success' : type === 'error' ? 'error' : '');
    m.textContent = text;
};

// UI toggles
$('btn-login').addEventListener('click', () => {
    hide($('register-form')); hide($('message'));
    show($('login-form'));
});
$('btn-register').addEventListener('click', () => {
    hide($('login-form')); hide($('message'));
    show($('register-form'));
});

// Crypto helper: SHA-256 hex
async function sha256Hex(text) {
    const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
    return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('');
}



// Auth flows
async function authSignUp(e) {
    e.preventDefault();
    const data = {
        name: $('reg-name').value.trim(),
        email: $('reg-email').value.trim().toLowerCase(),
        password: $('reg-password').value
    };

    try {
        const res = await fetch('register.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify(data)
        });
        const result = await res.json();
        msg(result.message, result.status);
    } catch(err){
        msg('Erreur serveur : '+err.message, 'error');
        console.error(err);
    }
}

async function authSignIn(e){
    e.preventDefault();
    const data = {
        email: $('login-email').value.trim().toLowerCase(),
        password: $('login-password').value
    };

    try {
        const res = await fetch('login.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify(data)
        });
        const result = await res.json();
        msg(result.message, result.status);

        if(result.status === 'success'){
            window.location.href = 'dashboard.php';
        }
    } catch(err){
        msg('Erreur serveur : '+err.message, 'error');
        console.error(err);
    }
}

// Logout
$('btn-logout').addEventListener('click', () => {
    localStorage.removeItem('as_session');
    hideDashboard();
    msg("Déconnecté.", 'success');
});
