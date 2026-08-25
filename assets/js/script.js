(() => {
  'use strict';
  const ready = (fn) => document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn();
  ready(() => {
    const interactive = 'button, .btn, .header-action-btn, .social-icon, .nav-link-custom, .wishlist-btn, .btn-wishlist';
    document.querySelectorAll(interactive).forEach(el => {
      if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
      el.style.overflow = 'hidden';
      el.addEventListener('pointerdown', e => {
        el.classList.add('ui-touch');
        const r = el.getBoundingClientRect();
        const size = Math.max(r.width, r.height);
        const ripple = document.createElement('span');
        ripple.className = 'ui-ripple';
        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${e.clientX - r.left - size / 2}px`;
        ripple.style.top = `${e.clientY - r.top - size / 2}px`;
        el.appendChild(ripple);
        setTimeout(() => ripple.remove(), 650);
      }, {passive:true});
      ['pointerup','pointercancel','pointerleave'].forEach(evt => el.addEventListener(evt, () => el.classList.remove('ui-touch'), {passive:true}));
    });

    const revealTargets = document.querySelectorAll('.card, .product-card, .card-product, .comment-card, .brand-static-card, .address-box, section > .container');
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(entries => entries.forEach(entry => {
        if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
      }), {threshold: .08, rootMargin: '0px 0px -30px'});
      revealTargets.forEach((el, i) => { el.classList.add('ui-reveal'); el.style.transitionDelay = `${Math.min(i % 6, 5) * 55}ms`; observer.observe(el); });
    }

    document.querySelectorAll('img').forEach(img => { if (!img.hasAttribute('loading')) img.loading = 'lazy'; img.addEventListener('error', () => img.classList.add('img-load-error')); });
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => window.bootstrap?.Tooltip && new bootstrap.Tooltip(el));
  });
})();
