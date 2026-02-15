/**
 * ============================================
 * InfinityFree — app.js  (v2.0 Production)
 * Global UI Engine: Toast · Spinner · Skeleton
 * Micro-Interactions · AJAX · Empty States
 * ============================================
 */

(function (global) {
  'use strict';

  /* ==========================================
     1. TOAST NOTIFICATION SYSTEM
     ========================================== */
  const Toast = (() => {
    let container = null;

    function _ensureContainer() {
      if (container) return;
      container = document.createElement('div');
      container.id = 'toast-container';
      Object.assign(container.style, {
        position: 'fixed',
        top: '1.5rem',
        right: '1.5rem',
        zIndex: '99999',
        display: 'flex',
        flexDirection: 'column',
        gap: '0.75rem',
        pointerEvents: 'none',
      });
      document.body.appendChild(container);
    }

    const ICONS = {
      success: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`,
      error:   `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`,
      warning: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,
      info:    `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"/></svg>`,
    };

    const COLORS = {
      success: { bg: '#ecfdf5', border: '#10b981', icon: '#10b981', text: '#065f46' },
      error:   { bg: '#fef2f2', border: '#ef4444', icon: '#ef4444', text: '#991b1b' },
      warning: { bg: '#fffbeb', border: '#f59e0b', icon: '#f59e0b', text: '#92400e' },
      info:    { bg: '#eff6ff', border: '#3b82f6', icon: '#3b82f6', text: '#1e40af' },
    };

    function show(message, type = 'info', duration = 4000) {
      _ensureContainer();
      const cfg  = COLORS[type] || COLORS.info;
      const icon = ICONS[type]  || ICONS.info;

      const toast = document.createElement('div');
      toast.setAttribute('role', 'alert');
      toast.setAttribute('aria-live', 'assertive');

      Object.assign(toast.style, {
        display:       'flex',
        alignItems:    'flex-start',
        gap:           '0.75rem',
        padding:       '0.875rem 1.125rem',
        background:    cfg.bg,
        border:        `1px solid ${cfg.border}`,
        borderLeft:    `4px solid ${cfg.border}`,
        borderRadius:  '10px',
        boxShadow:     '0 4px 24px rgba(0,0,0,0.10)',
        pointerEvents: 'all',
        cursor:        'pointer',
        maxWidth:      '380px',
        transform:     'translateX(420px)',
        opacity:       '0',
        transition:    'transform 0.35s cubic-bezier(.22,1,.36,1), opacity 0.35s ease',
        fontFamily:    'inherit',
      });

      toast.innerHTML = `
        <span style="color:${cfg.icon};flex-shrink:0;margin-top:1px;">${icon}</span>
        <span style="font-size:0.9rem;line-height:1.45;color:${cfg.text};flex:1;">${message}</span>
        <button style="background:none;border:none;cursor:pointer;color:${cfg.icon};opacity:0.6;padding:0;margin-left:0.25rem;font-size:1.2rem;line-height:1;" aria-label="Dismiss">×</button>
      `;

      const dismiss = () => {
        toast.style.transform = 'translateX(420px)';
        toast.style.opacity   = '0';
        setTimeout(() => toast.remove(), 380);
      };

      toast.querySelector('button').addEventListener('click', dismiss);
      toast.addEventListener('click', dismiss);

      container.appendChild(toast);

      // Animate in
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          toast.style.transform = 'translateX(0)';
          toast.style.opacity   = '1';
        });
      });

      if (duration > 0) setTimeout(dismiss, duration);
      return { dismiss };
    }

    return { show, success: (m,d) => show(m,'success',d), error: (m,d) => show(m,'error',d), warning: (m,d) => show(m,'warning',d), info: (m,d) => show(m,'info',d) };
  })();

  /* ==========================================
     2. BUTTON LOADING SPINNER
     ========================================== */
  const Spinner = (() => {
    const SPINNER_SVG = `<svg class="btn-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin .7s linear infinite;vertical-align:middle;margin-right:6px;"><path stroke-linecap="round" d="M12 2a10 10 0 0 1 10 10"/></svg>`;

    // Inject keyframe once
    if (!document.getElementById('if-spinner-style')) {
      const s = document.createElement('style');
      s.id = 'if-spinner-style';
      s.textContent = '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
      document.head.appendChild(s);
    }

    function start(btn, text = 'Loading…') {
      if (!btn) return;
      btn.dataset.originalText = btn.innerHTML;
      btn.disabled = true;
      btn.style.opacity = '0.75';
      btn.style.cursor  = 'not-allowed';
      btn.innerHTML = `${SPINNER_SVG}${text}`;
    }

    function stop(btn, text = null) {
      if (!btn) return;
      btn.disabled = false;
      btn.style.opacity = '';
      btn.style.cursor  = '';
      btn.innerHTML = text || btn.dataset.originalText || btn.innerHTML;
    }

    return { start, stop };
  })();

  /* ==========================================
     3. SKELETON LOADER
     ========================================== */
  const Skeleton = (() => {
    function _injectStyles() {
      if (document.getElementById('if-skeleton-style')) return;
      const s = document.createElement('style');
      s.id = 'if-skeleton-style';
      s.textContent = `
        .skeleton {
          background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
          background-size: 400% 100%;
          animation: skeleton-wave 1.6s ease-in-out infinite;
          border-radius: 6px;
        }
        @keyframes skeleton-wave {
          0%   { background-position: 200% 0 }
          100% { background-position: -200% 0 }
        }
        .skeleton-card {
          background: #fff;
          border-radius: 12px;
          padding: 1.5rem;
          box-shadow: 0 2px 12px rgba(0,0,0,.06);
          display: flex;
          flex-direction: column;
          gap: .85rem;
        }
        .skeleton-line { height: 14px; }
        .skeleton-line.lg { height: 22px; width: 60%; }
        .skeleton-line.sm { height: 10px; width: 40%; }
        .skeleton-block { height: 80px; }
        .skeleton-circle { border-radius: 50%; width: 48px; height: 48px; flex-shrink: 0; }
      `;
      document.head.appendChild(s);
    }

    function card(count = 4, containerId = null) {
      _injectStyles();
      const html = Array.from({ length: count }, () => `
        <div class="skeleton-card">
          <div class="skeleton skeleton-line lg"></div>
          <div class="skeleton skeleton-block"></div>
          <div class="skeleton skeleton-line"></div>
          <div class="skeleton skeleton-line sm"></div>
        </div>
      `).join('');

      if (containerId) {
        const el = document.getElementById(containerId);
        if (el) el.innerHTML = html;
      }
      return html;
    }

    function replace(selector, count = 4) {
      _injectStyles();
      const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
      if (!el) return;
      el.innerHTML = card(count);
    }

    return { card, replace };
  })();

  /* ==========================================
     4. FORM HELPERS — Auto-spinner + Toast
     ========================================== */
  const Forms = (() => {
    function bindAll() {
      document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', _handleAjaxForm);
      });

      // Standard forms get spinner only (no AJAX)
      document.querySelectorAll('form:not([data-ajax])').forEach(form => {
        form.addEventListener('submit', function (e) {
          const btn = form.querySelector('[type="submit"]');
          if (btn && !btn.dataset.noSpinner) {
            Spinner.start(btn, btn.dataset.loadingText || 'Patientez…');
          }
        });
      });
    }

    async function _handleAjaxForm(e) {
      e.preventDefault();
      const form    = e.target;
      const btn     = form.querySelector('[type="submit"]');
      const url     = form.action || form.dataset.url;
      const method  = (form.method || 'POST').toUpperCase();

      Spinner.start(btn, btn?.dataset.loadingText || 'Traitement…');

      try {
        const res  = await fetch(url, {
          method,
          body:    new FormData(form),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        if (data.success) {
          Toast.success(data.message || 'Opération réussie !');
          if (data.redirect) setTimeout(() => { location.href = data.redirect; }, 800);
          form.dispatchEvent(new CustomEvent('ajax:success', { detail: data }));
        } else {
          Toast.error(data.message || 'Une erreur est survenue.');
          Spinner.stop(btn);
          form.dispatchEvent(new CustomEvent('ajax:error', { detail: data }));
        }
      } catch (err) {
        Toast.error('Erreur de connexion. Veuillez réessayer.');
        Spinner.stop(btn);
      }
    }

    return { bindAll };
  })();

  /* ==========================================
     5. MICRO-INTERACTIONS
     ========================================== */
  const Interactions = (() => {
    const CSS = `
      /* Button press */
      .btn, .admin-btn, [class*="btn-"] {
        transition: transform 0.15s cubic-bezier(.34,1.56,.64,1), box-shadow 0.15s ease, background-color 0.2s ease !important;
      }
      .btn:active, .admin-btn:active { transform: scale(0.96) !important; }

      /* Card hover lift */
      .feature-card, .stat-card, .admin-card, .lesson-item {
        transition: transform 0.22s ease, box-shadow 0.22s ease !important;
      }
      .feature-card:hover, .admin-card:hover {
        transform: translateY(-4px) !important;
        box-shadow: 0 16px 40px rgba(37,99,235,0.12) !important;
      }

      /* Nav links */
      .nav-item, .nav-menu a {
        position: relative;
        transition: color 0.2s ease, background 0.2s ease !important;
      }

      /* Input focus ring */
      .form-control:focus {
        transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
      }

      /* Toggle switch */
      .toggle-slider { transition: background-color 0.3s ease !important; }
      .toggle-slider:before { transition: transform 0.3s cubic-bezier(.34,1.56,.64,1) !important; }

      /* Dropdown fade-slide */
      .lang-dropdown, .user-dropdown {
        transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease !important;
      }

      /* Status badges pulse on completed */
      .status-active { animation: subtlePulse 3s ease-in-out infinite; }
      @keyframes subtlePulse {
        0%,100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
        50%      { box-shadow: 0 0 0 5px rgba(16,185,129,0.15); }
      }

      /* Page entrance */
      .page-enter { animation: pageEnter 0.5s ease both; }
      @keyframes pageEnter {
        from { opacity:0; transform: translateY(18px); }
        to   { opacity:1; transform: translateY(0); }
      }

      /* Floating XP number */
      @keyframes floatUp {
        0%   { opacity:1; transform: translateY(0) scale(1); }
        100% { opacity:0; transform: translateY(-80px) scale(1.4); }
      }
      .xp-float {
        position: fixed;
        font-size: 1.6rem;
        font-weight: 800;
        color: #10b981;
        pointer-events: none;
        z-index: 99998;
        animation: floatUp 1.8s ease forwards;
        text-shadow: 0 2px 8px rgba(16,185,129,0.4);
      }
    `;

    function _injectStyles() {
      if (document.getElementById('if-interactions-style')) return;
      const s = document.createElement('style');
      s.id = 'if-interactions-style';
      s.textContent = CSS;
      document.head.appendChild(s);
    }

    function _pageEntrance() {
      const main = document.querySelector('main') || document.querySelector('.admin-content');
      if (main) main.classList.add('page-enter');
    }

    function floatXP(amount, x, y) {
      const el = document.createElement('div');
      el.className = 'xp-float';
      el.textContent = `+${amount} XP`;
      el.style.left = `${x}px`;
      el.style.top  = `${y}px`;
      document.body.appendChild(el);
      setTimeout(() => el.remove(), 1900);
    }

    function bindRipple() {
      document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn,.admin-btn');
        if (!btn || btn.dataset.noRipple) return;
        const circle  = document.createElement('span');
        const rect    = btn.getBoundingClientRect();
        const size    = Math.max(rect.width, rect.height);
        circle.style.cssText = `
          position:absolute;width:${size}px;height:${size}px;
          background:rgba(255,255,255,0.35);border-radius:50%;
          left:${e.clientX - rect.left - size/2}px;
          top:${e.clientY - rect.top - size/2}px;
          transform:scale(0);animation:ripple .5s ease-out;
          pointer-events:none;
        `;
        if (!document.getElementById('if-ripple-style')) {
          const s = document.createElement('style');
          s.id = 'if-ripple-style';
          s.textContent = '@keyframes ripple{to{transform:scale(2.5);opacity:0}}';
          document.head.appendChild(s);
        }
        const prevPos = getComputedStyle(btn).position;
        if (prevPos === 'static') btn.style.position = 'relative';
        btn.style.overflow = 'hidden';
        btn.appendChild(circle);
        setTimeout(() => circle.remove(), 520);
      });
    }

    function init() {
      _injectStyles();
      _pageEntrance();
      bindRipple();
    }

    return { init, floatXP };
  })();

  /* ==========================================
     6. EMPTY STATE RENDERER
     ========================================== */
  const EmptyState = (() => {
    const DEFAULTS = {
      lessons:  { icon: '📚', title: 'Aucune leçon disponible', body: 'Les leçons apparaîtront ici une fois ajoutées par votre enseignant.', cta: 'Explorer les niveaux', href: '/levels' },
      users:    { icon: '👥', title: 'Aucun utilisateur', body: 'Les utilisateurs inscrits apparaîtront ici.', cta: null, href: null },
      progress: { icon: '🎯', title: 'Aucune progression', body: 'Commencez votre premier cours pour voir votre progression ici.', cta: 'Commencer maintenant', href: '/levels' },
      search:   { icon: '🔍', title: 'Aucun résultat', body: 'Essayez de modifier vos critères de recherche.', cta: null, href: null },
      default:  { icon: '📭', title: 'Rien à afficher', body: 'Revenez plus tard.', cta: null, href: null },
    };

    function render(type = 'default', overrides = {}) {
      const cfg = { ...DEFAULTS[type] || DEFAULTS.default, ...overrides };
      const cta = cfg.cta
        ? `<a href="${cfg.href}" class="btn btn-primary" style="margin-top:1.25rem;display:inline-flex;align-items:center;gap:.5rem;">${cfg.cta}</a>`
        : '';

      return `
        <div class="empty-state" style="text-align:center;padding:4rem 2rem;color:#6b7280;">
          <div style="font-size:3.5rem;margin-bottom:1rem;line-height:1;">${cfg.icon}</div>
          <h3 style="font-size:1.25rem;font-weight:600;color:#374151;margin-bottom:.5rem;">${cfg.title}</h3>
          <p style="font-size:.938rem;max-width:360px;margin:0 auto;line-height:1.6;">${cfg.body}</p>
          ${cta}
        </div>`;
    }

    function inject(containerSelector, type, overrides) {
      const el = document.querySelector(containerSelector);
      if (el) el.innerHTML = render(type, overrides);
    }

    return { render, inject };
  })();

  /* ==========================================
     7. MOBILE RESPONSIVE IFRAMES
     ========================================== */
  const ResponsiveMedia = (() => {
    function fix() {
      // Fix YouTube iframes on mobile
      document.querySelectorAll('.video-container iframe, .preview-video').forEach(iframe => {
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '');
        // Ensure the parent uses 16:9 padding trick
        const parent = iframe.parentElement;
        if (parent && !parent.classList.contains('video-container')) {
          parent.style.cssText += ';position:relative;padding-bottom:56.25%;height:0;overflow:hidden;';
          Object.assign(iframe.style, { position: 'absolute', top: '0', left: '0', width: '100%', height: '100%', border: '0' });
        }
      });

      // PDF on mobile — reduce height
      if (window.innerWidth < 768) {
        document.querySelectorAll('.pdf-viewer').forEach(pdf => {
          pdf.style.height = '420px';
        });
      }
    }

    return { fix };
  })();

  /* ==========================================
     8. COUNT-UP ANIMATION (reusable)
     ========================================== */
  const CountUp = (() => {
    function animate(el, from = 0, to, duration = 1400, suffix = '') {
      if (!el || isNaN(to)) return;
      const startTime = performance.now();
      const easeOut = t => 1 - Math.pow(1 - t, 3);

      function tick(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        el.textContent  = Math.floor(easeOut(progress) * (to - from) + from) + suffix;
        if (progress < 1) requestAnimationFrame(tick);
        else el.textContent = to + suffix;
      }
      requestAnimationFrame(tick);
    }

    function bindAll() {
      document.querySelectorAll('[data-countup]').forEach(el => {
        const to      = parseFloat(el.dataset.countup);
        const suffix  = el.dataset.suffix  || '';
        const duration = parseInt(el.dataset.duration) || 1400;
        const observer = new IntersectionObserver(([entry]) => {
          if (entry.isIntersecting) {
            animate(el, 0, to, duration, suffix);
            observer.disconnect();
          }
        }, { threshold: 0.3 });
        observer.observe(el);
      });
    }

    return { animate, bindAll };
  })();

  /* ==========================================
     9. GLOBAL AJAX HELPER (csrf-aware)
     ========================================== */
  const Ajax = (() => {
    function getCsrfToken() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      if (meta) return meta.content;
      const input = document.querySelector('[name="csrf_token"]');
      return input ? input.value : '';
    }

    async function post(url, data = {}) {
      const body = new URLSearchParams({ ...data, csrf_token: getCsrfToken() });
      const res = await fetch(url, {
        method:  'POST',
        headers: {
          'Content-Type':    'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body,
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    }

    async function get(url, params = {}) {
      const qs  = new URLSearchParams(params).toString();
      const res = await fetch(qs ? `${url}?${qs}` : url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    }

    return { post, get };
  })();

  /* ==========================================
     10. CONFIRM DIALOG (replaces window.confirm)
     ========================================== */
  const Confirm = (() => {
    function _injectStyles() {
      if (document.getElementById('if-confirm-style')) return;
      const s = document.createElement('style');
      s.id = 'if-confirm-style';
      s.textContent = `
        #if-confirm-overlay {
          position:fixed;inset:0;z-index:999999;background:rgba(0,0,0,.55);
          display:flex;align-items:center;justify-content:center;
          animation:fadeIn .2s ease;
        }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        #if-confirm-box {
          background:#fff;border-radius:16px;padding:2rem 2.5rem;max-width:420px;width:90%;
          box-shadow:0 20px 60px rgba(0,0,0,.25);animation:slideUp .25s cubic-bezier(.34,1.56,.64,1);
          font-family:inherit;
        }
        @keyframes slideUp { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }
        #if-confirm-box h4 { margin:0 0 .5rem;font-size:1.125rem;color:#111827; }
        #if-confirm-box p  { margin:0 0 1.5rem;color:#6b7280;font-size:.938rem;line-height:1.5; }
        #if-confirm-actions { display:flex;gap:.75rem;justify-content:flex-end; }
      `;
      document.head.appendChild(s);
    }

    function show(message, title = 'Confirmer l\'action') {
      _injectStyles();
      return new Promise(resolve => {
        const overlay = document.createElement('div');
        overlay.id = 'if-confirm-overlay';
        overlay.innerHTML = `
          <div id="if-confirm-box">
            <h4>${title}</h4>
            <p>${message}</p>
            <div id="if-confirm-actions">
              <button class="btn" id="if-confirm-cancel" style="background:#f3f4f6;color:#374151;padding:.625rem 1.25rem;border:none;border-radius:8px;cursor:pointer;font-size:.938rem;">Annuler</button>
              <button class="btn btn-primary" id="if-confirm-ok" style="padding:.625rem 1.25rem;border:none;border-radius:8px;cursor:pointer;font-size:.938rem;background:#ef4444;color:#fff;">Confirmer</button>
            </div>
          </div>`;

        document.body.appendChild(overlay);

        const close = (val) => { overlay.remove(); resolve(val); };
        overlay.querySelector('#if-confirm-cancel').addEventListener('click', () => close(false));
        overlay.querySelector('#if-confirm-ok').addEventListener('click', () => close(true));
        overlay.addEventListener('click', e => { if (e.target === overlay) close(false); });
      });
    }

    return { show };
  })();

  /* ==========================================
     11. SCROLL REVEAL
     ========================================== */
  const ScrollReveal = (() => {
    function _injectStyles() {
      if (document.getElementById('if-reveal-style')) return;
      const s = document.createElement('style');
      s.id = 'if-reveal-style';
      s.textContent = `
        .reveal { opacity:0; transform:translateY(28px); transition:opacity .55s ease, transform .55s cubic-bezier(.22,1,.36,1); }
        .reveal.visible { opacity:1; transform:translateY(0); }
      `;
      document.head.appendChild(s);
    }

    function bind(selector = '.feature-card, .stat-card, .admin-card') {
      _injectStyles();
      const els = document.querySelectorAll(selector);
      const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12 });

      els.forEach((el, i) => {
        el.classList.add('reveal');
        el.style.transitionDelay = `${i * 70}ms`;
        io.observe(el);
      });
    }

    return { bind };
  })();

  /* ==========================================
     12. INIT
     ========================================== */
  function init() {
    Interactions.init();
    Forms.bindAll();
    CountUp.bindAll();
    ResponsiveMedia.fix();
    ScrollReveal.bind();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose to global scope
  global.InfinityFree = {
    Toast,
    Spinner,
    Skeleton,
    Forms,
    EmptyState,
    ResponsiveMedia,
    CountUp,
    Ajax,
    Confirm,
    ScrollReveal,
    Interactions,
  };

})(window);
