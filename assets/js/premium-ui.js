(() => {
  'use strict';
  const ready = fn => document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn();
  ready(() => {
    document.body.insertAdjacentHTML('afterbegin','<div class="premium-scroll-progress" aria-hidden="true"></div><div class="premium-orb o1" aria-hidden="true"></div><div class="premium-orb o2" aria-hidden="true"></div>');
    const root = document.documentElement;
    let ticking = false;
    const updateScroll = () => {
      const max = Math.max(1, document.documentElement.scrollHeight - innerHeight);
      root.style.setProperty('--scroll', `${Math.min(100, scrollY / max * 100)}%`);
      ticking = false;
    };
    addEventListener('scroll', () => { if (!ticking) { requestAnimationFrame(updateScroll); ticking = true; } }, {passive:true});
    updateScroll();

    if (matchMedia('(pointer:fine)').matches && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
      addEventListener('pointermove', e => {
        root.style.setProperty('--mx', `${e.clientX}px`);
        root.style.setProperty('--my', `${e.clientY}px`);
      }, {passive:true});

      const cards = document.querySelectorAll('.product-card, .card-product, .brand-static-card, .auth-card');
      cards.forEach(card => {
        card.classList.add('premium-tilt');
        if (!card.querySelector(':scope > .premium-spot')) card.insertAdjacentHTML('beforeend','<span class="premium-spot" aria-hidden="true"></span>');
        card.addEventListener('pointermove', e => {
          const r = card.getBoundingClientRect();
          const x = (e.clientX-r.left)/r.width, y=(e.clientY-r.top)/r.height;
          card.style.setProperty('--lx', `${x*100}%`); card.style.setProperty('--ly', `${y*100}%`);
          card.style.transform = `perspective(900px) rotateX(${(0.5-y)*4.5}deg) rotateY(${(x-0.5)*5.5}deg) translateY(-8px)`;
        }, {passive:true});
        card.addEventListener('pointerleave', () => { card.style.transform=''; }, {passive:true});
      });
    }

    document.querySelectorAll('.btn, .header-action-btn, .social-icon').forEach(el => {
      el.addEventListener('pointerup', () => {
        el.animate([{filter:'brightness(1.18)'},{filter:'brightness(1)'}],{duration:260,easing:'ease-out'});
      }, {passive:true});
    });
  });
})();
