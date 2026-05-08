/* ============================================
   EuroAlumínios do Sul — JavaScript
   ============================================ */
'use strict';

/* -----------------------------------------------
   1. NAVBAR — scroll + hamburger
   ----------------------------------------------- */
const navbar    = document.getElementById('navbar');
const hamburger = document.getElementById('hamburger');
const mobileNav = document.getElementById('mobileNav');

window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 60);
  updateActiveNav();
});

hamburger.addEventListener('click', () => {
  hamburger.classList.toggle('open');
  mobileNav.classList.toggle('open');
  document.body.style.overflow = mobileNav.classList.contains('open') ? 'hidden' : '';
});

function closeMobileNav() {
  hamburger.classList.remove('open');
  mobileNav.classList.remove('open');
  document.body.style.overflow = '';
}
window.addEventListener('keydown', e => { if (e.key === 'Escape') closeMobileNav(); });

function updateActiveNav() {
  const sections = document.querySelectorAll('section[id]');
  let current = 'inicio';
  sections.forEach(s => {
    if (s.getBoundingClientRect().top <= 100) current = s.id;
  });
  document.querySelectorAll('.nav-links a').forEach(a => {
    a.classList.toggle('active', a.getAttribute('href') === '#' + current);
  });
}


/* -----------------------------------------------
   2. HERO SLIDESHOW
   ----------------------------------------------- */
(function initHeroSlider() {
  const slides   = document.querySelectorAll('.hero-slide');
  const dotsWrap = document.getElementById('heroDots');
  const prevBtn  = document.getElementById('heroPrev');
  const nextBtn  = document.getElementById('heroNext');
  if (!slides.length) return;

  let current  = 0;
  let timer    = null;
  const DELAY  = 5000;

  // Criar dots
  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.className = 'hero-dot' + (i === 0 ? ' active' : '');
    dot.setAttribute('aria-label', 'Slide ' + (i + 1));
    dot.addEventListener('click', () => goTo(i));
    dotsWrap.appendChild(dot);
  });

  function goTo(idx) {
    slides[current].classList.remove('active');
    dotsWrap.children[current].classList.remove('active');
    current = (idx + slides.length) % slides.length;
    slides[current].classList.add('active');
    dotsWrap.children[current].classList.add('active');
    resetTimer();
  }

  function resetTimer() {
    clearInterval(timer);
    timer = setInterval(() => goTo(current + 1), DELAY);
  }

  prevBtn && prevBtn.addEventListener('click', () => goTo(current - 1));
  nextBtn && nextBtn.addEventListener('click', () => goTo(current + 1));

  // Swipe suporte mobile
  let touchStartX = 0;
  const slider = document.getElementById('heroSlider');
  slider.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
  slider.addEventListener('touchend',   e => {
    const diff = touchStartX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) goTo(diff > 0 ? current + 1 : current - 1);
  });

  resetTimer();
})();


/* -----------------------------------------------
   3. ANIMAÇÕES DE SCROLL
   ----------------------------------------------- */
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

document.querySelectorAll('.reveal, .reveal-left, .reveal-right')
  .forEach(el => revealObserver.observe(el));


/* -----------------------------------------------
   4. CONTADORES ANIMADOS
   ----------------------------------------------- */
function animateCounter(el, target, dur = 1800) {
  let start = null;
  const step = ts => {
    if (!start) start = ts;
    const p = Math.min((ts - start) / dur, 1);
    const eased = 1 - Math.pow(2, -10 * p);
    el.textContent = Math.floor(eased * target);
    if (p < 1) requestAnimationFrame(step);
    else el.textContent = target;
  };
  requestAnimationFrame(step);
}

let countersStarted = false;
const statsSection  = document.querySelector('.stats-bar');
if (statsSection) {
  new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && !countersStarted) {
      countersStarted = true;
      document.querySelectorAll('.stat-number[data-target]').forEach(el => {
        animateCounter(el, parseInt(el.dataset.target));
      });
    }
  }, { threshold: 0.4 }).observe(statsSection);
}


/* -----------------------------------------------
   5. GALERIA — filtro
   ----------------------------------------------- */
const filtroBtns   = document.querySelectorAll('.filtro-btn');
const galeriaItems = document.querySelectorAll('.galeria-item');

filtroBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    filtroBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const filter = btn.dataset.filter;
    galeriaItems.forEach(item => {
      const match = filter === 'todos' || item.dataset.cat === filter;
      item.style.display = match ? '' : 'none';
    });
  });
});


/* -----------------------------------------------
   6. FORMULÁRIO — validação + envio AJAX
   ----------------------------------------------- */
const contactForm = document.getElementById('contactForm');
const formSuccess = document.getElementById('formSuccess');
const formError   = document.getElementById('formError');

if (contactForm) {
  contactForm.addEventListener('submit', async e => {
    e.preventDefault();
    const nome     = contactForm.nome.value.trim();
    const email    = contactForm.email.value.trim();
    const mensagem = contactForm.mensagem.value.trim();

    if (!nome || !email || !mensagem) {
      showMsg('error', '⚠️ Preencha os campos obrigatórios: Nome, Email e Mensagem.');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showMsg('error', '⚠️ Email inválido. Verifique e tente novamente.');
      return;
    }

    const btn = contactForm.querySelector('[type="submit"]');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar…';
    btn.disabled = true;

    // Copia email para _replyto (Formspree reply-to)
    const replyTo = contactForm.querySelector('#_replyto');
    if (replyTo) replyTo.value = contactForm.email.value.trim();

    try {
      const res = await fetch(contactForm.action, {
        method: 'POST',
        body: new FormData(contactForm),
        headers: { 'Accept': 'application/json' },
      });
      if (res.ok) {
        showMsg('success', '✅ Mensagem enviada! Entraremos em contacto brevemente.');
        contactForm.reset();
      } else {
        const json = await res.json().catch(() => ({}));
        throw new Error((json.errors || []).map(e => e.message).join(', ') || 'Erro no servidor');
      }
    } catch (err) {
      showMsg('error', '❌ Não foi possível enviar. Por favor contacte-nos por telefone: 939 258 868');
      console.error(err);
    } finally {
      btn.innerHTML = orig;
      btn.disabled = false;
    }
  });
}

function showMsg(type, text) {
  formSuccess.style.display = 'none';
  formError.style.display   = 'none';
  const el = type === 'success' ? formSuccess : formError;
  el.textContent = text;
  el.style.display = 'block';
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  if (type === 'success') setTimeout(() => { el.style.display = 'none'; }, 8000);
}


/* -----------------------------------------------
   7. SMOOTH SCROLL
   ----------------------------------------------- */
document.querySelectorAll('a[href^="#"]').forEach(link => {
  link.addEventListener('click', e => {
    const target = document.querySelector(link.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const top = target.getBoundingClientRect().top + window.scrollY - 80;
      window.scrollTo({ top, behavior: 'smooth' });
    }
  });
});


/* -----------------------------------------------
   8. FADE IN ao carregar
   ----------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
  document.body.style.opacity = '0';
  document.body.style.transition = 'opacity 0.5s ease';
  setTimeout(() => { document.body.style.opacity = '1'; }, 40);
});
