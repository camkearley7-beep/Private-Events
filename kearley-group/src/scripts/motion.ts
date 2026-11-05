/**
 * Kearley Group — interaction layer.
 * Small, dependency-free, and respectful of prefers-reduced-motion.
 */

const root = document.documentElement;
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- Opening ---------- */
const markLoaded = () => requestAnimationFrame(() => root.classList.add('is-loaded'));
if (document.fonts?.ready) {
  Promise.race([document.fonts.ready, new Promise((r) => setTimeout(r, 900))]).then(markLoaded);
} else markLoaded();

/* ---------- Reveals ---------- */
const revealables = document.querySelectorAll<HTMLElement>('[data-reveal], [data-lines]');
if ('IntersectionObserver' in window && !reduce) {
  const io = new IntersectionObserver(
    (entries) => {
      for (const e of entries) {
        if (e.isIntersecting) {
          e.target.classList.add('is-in');
          io.unobserve(e.target);
        }
      }
    },
    { rootMargin: '0px 0px -12% 0px', threshold: 0.01 }
  );
  revealables.forEach((el) => {
    if (el.closest('[data-hero-lines]') || el.hasAttribute('data-hero-lines')) return;
    io.observe(el);
  });
} else {
  revealables.forEach((el) => el.classList.add('is-in'));
}
// The hero headline rises as part of the opening.
document.querySelectorAll('[data-hero-lines]').forEach((el) => setTimeout(() => el.classList.add('is-in'), reduce ? 0 : 900));

/* ---------- Header ---------- */
const header = document.querySelector<HTMLElement>('[data-header]');
const darkSections = Array.from(document.querySelectorAll<HTMLElement>('[data-header-dark]'));
let lastY = window.scrollY;

function updateHeader() {
  if (!header) return;
  const y = window.scrollY;
  const menuOpen = root.classList.contains('menu-open');
  header.classList.toggle('is-solid', y > 40 && !isOverDark());
  if (!menuOpen) header.classList.toggle('is-hidden', y > lastY && y > 400);
  header.classList.toggle('is-dark', isOverDark());
  lastY = y;
}
function isOverDark() {
  const probe = 36;
  return darkSections.some((s) => {
    const r = s.getBoundingClientRect();
    return r.top <= probe && r.bottom >= probe;
  });
}

/* ---------- Parallax ---------- */
const parallaxEls = reduce
  ? []
  : Array.from(document.querySelectorAll<HTMLElement>('[data-parallax], [data-parallax-img], [data-parallax-x]'));
const wide = window.matchMedia('(min-width: 721px)');

function updateParallax() {
  const vh = window.innerHeight;
  for (const el of parallaxEls) {
    const target = el.hasAttribute('data-parallax-img') ? el.parentElement! : el;
    const r = target.getBoundingClientRect();
    if (r.bottom < -100 || r.top > vh + 100) continue;
    const progress = (r.top + r.height / 2 - vh / 2) / vh; // -1 … 1 around centre
    if (el.dataset.parallaxX) {
      const f = parseFloat(el.dataset.parallaxX);
      el.style.transform = `translate3d(${progress * f * 100}%, 0, 0)`;
    } else if (el.dataset.parallaxImg) {
      const f = parseFloat(el.dataset.parallaxImg);
      el.style.translate = `0 ${progress * f * -100}%`;
    } else if (wide.matches) {
      const f = parseFloat(el.dataset.parallax || '0');
      el.style.transform = `translate3d(0, ${progress * f * vh}px, 0)`;
    }
  }
}

let ticking = false;
function onScroll() {
  if (ticking) return;
  ticking = true;
  requestAnimationFrame(() => {
    updateHeader();
    updateParallax();
    updateActiveNav();
    ticking = false;
  });
}
window.addEventListener('scroll', onScroll, { passive: true });
window.addEventListener('resize', onScroll, { passive: true });

/* ---------- Active nav state ---------- */
const navLinks = Array.from(document.querySelectorAll<HTMLAnchorElement>('.hdr__nav a'));
const navTargets = navLinks.map((a) => document.querySelector<HTMLElement>(a.getAttribute('href')!));
function updateActiveNav() {
  const mid = window.innerHeight * 0.35;
  let active = -1;
  navTargets.forEach((t, i) => {
    if (t && t.getBoundingClientRect().top <= mid) active = i;
  });
  navLinks.forEach((a, i) => (i === active ? a.setAttribute('aria-current', 'true') : a.removeAttribute('aria-current')));
}

/* ---------- Mobile menu ---------- */
const toggle = document.querySelector<HTMLButtonElement>('[data-menu-toggle]');
const menu = document.querySelector<HTMLElement>('[data-menu]');
const label = document.querySelector<HTMLElement>('[data-menu-label]');

function setMenu(open: boolean) {
  if (!toggle || !menu) return;
  toggle.setAttribute('aria-expanded', String(open));
  if (label) label.textContent = open ? 'Close' : 'Menu';
  root.classList.toggle('menu-open', open);
  if (open) {
    menu.hidden = false;
    requestAnimationFrame(() => requestAnimationFrame(() => menu.classList.add('is-open')));
    document.body.style.overflow = 'hidden';
    menu.querySelector<HTMLElement>('a')?.focus({ preventScroll: true });
  } else {
    menu.classList.remove('is-open');
    document.body.style.overflow = '';
    const done = () => { if (!menu.classList.contains('is-open')) menu.hidden = true; };
    reduce ? done() : setTimeout(done, 900);
  }
}
toggle?.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
menu?.querySelectorAll('[data-menu-link]').forEach((a) => a.addEventListener('click', () => setMenu(false)));
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && root.classList.contains('menu-open')) {
    setMenu(false);
    toggle?.focus();
  }
  // Keep focus inside the open menu
  if (e.key === 'Tab' && root.classList.contains('menu-open') && menu && toggle) {
    const focusables = [toggle, ...Array.from(menu.querySelectorAll<HTMLElement>('a'))];
    const first = focusables[0], last = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  }
});

/* ---------- Portfolio index preview ---------- */
const index = document.querySelector<HTMLElement>('[data-index]');
const preview = document.querySelector<HTMLElement>('[data-index-preview]');
if (index && preview && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
  const thumbs = Array.from(preview.children) as HTMLElement[];
  index.querySelectorAll<HTMLElement>('[data-index-link]').forEach((link) => {
    link.addEventListener('mouseenter', () => {
      const i = Number(link.dataset.indexLink);
      thumbs.forEach((t, j) => t.classList.toggle('is-active', i === j));
      preview.classList.add('is-on');
    });
  });
  index.addEventListener('mouseleave', () => preview.classList.remove('is-on'));
  index.addEventListener('mousemove', (e) => {
    const w = preview.offsetWidth, h = preview.offsetHeight;
    const x = Math.min(window.innerWidth - w - 24, e.clientX + 32);
    preview.style.setProperty('--x', `${x}px`);
    preview.style.setProperty('--y', `${e.clientY - h / 2}px`);
  });
}

onScroll();
