/* assets/js/app.js: SamFix client-side helpers
 * 1. Form validation hints (empty required fields, bad email) before submit
 * 2. Delete confirmation prompt before a delete form is sent
 * 3. Mobile menu open/close
 * Server-side validation still runs on every request; this only gives faster feedback.
 */
(function () {
  'use strict';

  /* ---------- Styles for the hints ---------- */
  var style = document.createElement('style');
  style.textContent =
    '.field-error{color:#dc2626;font-size:12px;line-height:1.4;margin-top:6px}' +
    '[aria-invalid="true"]{border-color:#dc2626 !important}';
  document.head.appendChild(style);

  /* ---------- 1. Form validation hints ----------
   * Add data-validate to a <form> to turn this on.
   * Optional on a field: data-error="Custom message for an empty field".
   */
  function labelOf(el) {
    var text = '';
    if (el.id) {
      var l = document.querySelector('label[for="' + el.id + '"]');
      if (l) { text = l.textContent; }
    }
    if (!text) { text = el.getAttribute('aria-label') || el.placeholder || el.name || 'This field'; }
    return text.replace(/[:*]/g, '').replace(/\s+/g, ' ').trim();
  }

  function isEmpty(el) {
    if (!el.required) { return false; }
    if (el.type === 'checkbox' || el.type === 'radio') { return !el.checked; }
    return el.value.trim() === '';
  }

  function messageFor(el) {
    if (isEmpty(el)) {
      if (el.dataset.error) { return el.dataset.error; }
      var label = labelOf(el);
      return el.tagName === 'SELECT' ? 'Please select an option for ' + label + '.' : label + ' is required.';
    }
    if (el.type === 'email' && el.value.trim() !== '' && el.validity.typeMismatch) {
      return 'Enter a valid email address, for example name@example.com.';
    }
    if (el.minLength > 0 && el.value !== '' && el.value.length < el.minLength) {
      return labelOf(el) + ' must be at least ' + el.minLength + ' characters.';
    }
    return '';
  }

  function clearHint(el) {
    el.removeAttribute('aria-invalid');
    el.removeAttribute('aria-describedby');
    if (el._hint) { el._hint.remove(); el._hint = null; }
  }

  function showHint(el, msg) {
    clearHint(el);
    var p = document.createElement('p');
    p.className = 'field-error';
    p.setAttribute('role', 'alert');
    p.id = 'err-' + (el.id || el.name || Math.random().toString(36).slice(2));
    p.textContent = msg;
    // If the field sits in a flex/grid wrapper (like the search box with its button), put the
    // message after the wrapper so it doesn't squeeze the input.
    var anchor = el;
    var parent = el.parentElement;
    if (parent && /flex|grid/.test(window.getComputedStyle(parent).display) && parent.tagName !== 'FORM') { anchor = parent; }
    anchor.insertAdjacentElement('afterend', p);
    el.setAttribute('aria-invalid', 'true');
    el.setAttribute('aria-describedby', p.id);
    el._hint = p;
  }

  function checkField(el) {
    var msg = messageFor(el);
    if (msg) { showHint(el, msg); return false; }
    clearHint(el);
    return true;
  }

  function validatable(form) {
    return Array.prototype.filter.call(form.elements, function (el) {
      return el.name && !el.disabled && /^(INPUT|SELECT|TEXTAREA)$/.test(el.tagName) &&
             !/^(hidden|submit|button|reset|file)$/.test(el.type);
    });
  }

  function initValidation() {
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
      form.noValidate = true;   // we show our own messages instead of the browser's bubbles
      var attempted = false;

      form.addEventListener('submit', function (e) {
        attempted = true;
        var firstBad = null;
        validatable(form).forEach(function (el) {
          if (!checkField(el) && !firstBad) { firstBad = el; }
        });
        if (firstBad) {
          e.preventDefault();
          firstBad.focus();
        }
      });

      // After the first failed attempt, fix hints as the user types or changes a field
      ['input', 'change'].forEach(function (evt) {
        form.addEventListener(evt, function (ev) {
          var el = ev.target;
          if (attempted && el.name) { checkField(el); }
        });
      });
    });
  }

  /* ---------- 2. Delete confirmation ----------
   * Put data-confirm="Delete this service?" on a <form> (or on its submit button).
   * The form is only sent if the user clicks OK.
   */
  function initConfirm() {
    document.addEventListener('submit', function (e) {
      if (e.defaultPrevented) { return; }   // validation already stopped it
      var msg = (e.submitter && e.submitter.dataset.confirm) || e.target.dataset.confirm;
      if (msg && !window.confirm(msg)) { e.preventDefault(); }
    });
  }

  /* ---------- 3. Mobile menu ---------- */
  function initMobileMenu() {
    var btn = document.getElementById('mobileMenuBtn');
    var menu = document.getElementById('mobileMenu');
    if (!btn || !menu) { return; }

    function setOpen(open) {
      menu.classList.toggle('hidden', !open);
      btn.setAttribute('aria-expanded', String(open));
      btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      var icon = btn.querySelector('i');
      if (icon) { icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'; }
    }

    btn.addEventListener('click', function () { setOpen(menu.classList.contains('hidden')); });
    menu.addEventListener('click', function (e) { if (e.target.closest('a, button')) { setOpen(false); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { setOpen(false); } });
    window.matchMedia('(min-width: 768px)').addEventListener('change', function (e) { if (e.matches) { setOpen(false); } });
  }

  function init() { initValidation(); initConfirm(); initMobileMenu(); }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();