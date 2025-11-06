// Mobile nav toggle
const toggle = document.querySelector('.menu-toggle');
const navList = document.getElementById('main-nav');

toggle.addEventListener('click', () => {
    const open = navList.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open);
});

// Join form
document.getElementById('join-form').addEventListener('submit', e => {
    e.preventDefault();
    document.getElementById('join-msg').style.display = 'block';
    e.target.reset();
});

// Contact form
document.getElementById('contact-form').addEventListener('submit', e => {
    e.preventDefault();
    document.getElementById('contact-msg').style.display = 'block';
    e.target.reset();
});

