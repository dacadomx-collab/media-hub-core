/**
 * Media HUB — set_password.php
 * Validacion en tiempo real de fortaleza de contrasena + confirmacion.
 * Sin dependencias externas.
 */
(function () {
  'use strict';

  var form = document.getElementById('setPasswordForm');
  if (!form) {
    return;
  }

  var passwordField = document.getElementById('password');
  var confirmField  = document.getElementById('password_confirm');
  var matchMessage  = document.getElementById('matchMessage');
  var submitBtn     = document.getElementById('spSubmitBtn');

  var rules = {
    length:  { test: function (v) { return v.length >= 8; }, el: document.getElementById('ruleLength') },
    upper:   { test: function (v) { return /[A-Z]/.test(v); }, el: document.getElementById('ruleUpper') },
    lower:   { test: function (v) { return /[a-z]/.test(v); }, el: document.getElementById('ruleLower') },
    number:  { test: function (v) { return /[0-9]/.test(v); }, el: document.getElementById('ruleNumber') },
    special: { test: function (v) { return /[^A-Za-z0-9]/.test(v); }, el: document.getElementById('ruleSpecial') },
  };

  function allRulesValid(value) {
    return Object.keys(rules).every(function (key) { return rules[key].test(value); });
  }

  function updateRules() {
    var value = passwordField.value;

    Object.keys(rules).forEach(function (key) {
      var rule = rules[key];
      rule.el.classList.toggle('sp-valid', rule.test(value));
    });

    validateForm();
  }

  function validateForm() {
    var pass    = passwordField.value;
    var confirm = confirmField.value;
    var strong  = allRulesValid(pass);
    var match   = confirm === '' || pass === confirm;

    matchMessage.textContent = (!match && confirm !== '') ? 'Las contrasenas no coinciden.' : '';
    matchMessage.classList.toggle('error', !match && confirm !== '');

    submitBtn.disabled = !(strong && pass === confirm && confirm !== '');
  }

  passwordField.addEventListener('input', updateRules);
  confirmField.addEventListener('input', validateForm);

  form.addEventListener('submit', function (event) {
    if (!allRulesValid(passwordField.value) || passwordField.value !== confirmField.value) {
      event.preventDefault();
      validateForm();
      return;
    }

    // Anti Double-Click (Regla de Oro Global): el envio navega a otra
    // pagina, pero un doble clic aun puede duplicar el POST antes de que
    // el navegador redirija.
    submitBtn.disabled = true;
    submitBtn.textContent = 'Procesando...';
  });
})();
