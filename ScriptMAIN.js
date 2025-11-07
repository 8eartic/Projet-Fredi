// === NAVIGATION MOBILE ===
const toggle = document.querySelector('.menu-toggle');
const navList = document.getElementById('main-nav');

toggle.addEventListener('click', () => {
  const open = navList.classList.toggle('open');
  toggle.setAttribute('aria-expanded', open);
});

// === FORMULAIRE ADHÉSION ===
const joinForm = document.getElementById('join-form');
const joinMsg = document.getElementById('join-msg');

joinForm.addEventListener('submit', e => {
  e.preventDefault();
  joinMsg.style.display = 'block';
  joinForm.reset();
});

// === FORMULAIRE CONTACT ===
const contactForm = document.getElementById('contact-form');
const contactMsg = document.getElementById('contact-msg');

contactForm.addEventListener('submit', e => {
  e.preventDefault();
  contactMsg.style.display = 'block';
  contactForm.reset();
});
