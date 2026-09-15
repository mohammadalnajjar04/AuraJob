(function () {
  'use strict';

  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  var currentSuccessEl = null;

  function showSuccess(message) {
    if (!currentSuccessEl) return;
    var textEl = currentSuccessEl.querySelector('.auth-success__text');
    if (textEl) textEl.textContent = message;
    currentSuccessEl.hidden = false;
  }

  function hideSuccess() {
    if (!currentSuccessEl) return;
    currentSuccessEl.hidden = true;
    var textEl = currentSuccessEl.querySelector('.auth-success__text');
    if (textEl) textEl.textContent = '';
  }

  function Field(form, inputSelector, errorSelector, rule) {
    this.form = form;
    this.errorEl = form.querySelector(errorSelector);
    this.rule = rule;
    this.inputs = inputSelector
      ? Array.prototype.slice.call(form.querySelectorAll(inputSelector))
      : [];
  }

  Field.prototype.firstInput = function () {
    return this.inputs[0];
  };

  Field.prototype.isInvalid = function () {
    return !this.errorEl.hidden;
  };

  Field.prototype.showError = function () {
    var message = this.rule(this);
    if (!message) {
      this.clear();
      return false;
    }
    var errorId = this.errorEl.getAttribute('id');
    for (var i = 0; i < this.inputs.length; i++) {
      this.inputs[i].setAttribute('aria-invalid', 'true');
      if (this.inputs[i].getAttribute('id')) {
        this.inputs[i].setAttribute('aria-describedby', errorId);
      }
    }
    this.errorEl.textContent = message;
    this.errorEl.hidden = false;
    return true;
  };

  Field.prototype.clear = function () {
    var errorId = this.errorEl.getAttribute('id');
    for (var i = 0; i < this.inputs.length; i++) {
      this.inputs[i].removeAttribute('aria-invalid');
      if (this.inputs[i].getAttribute('aria-describedby') === errorId) {
        this.inputs[i].removeAttribute('aria-describedby');
      }
    }
    this.errorEl.textContent = '';
    this.errorEl.hidden = true;
  };

  function ruleEmail(field) {
    var value = field.firstInput().value.trim();
    if (!value) return 'Enter your email address.';
    if (!EMAIL_RE.test(value)) return 'Enter a valid email address.';
    return '';
  }

  function ruleLoginPassword(field) {
    if (!field.firstInput().value) return 'Enter your password.';
    return '';
  }

  function ruleName(field) {
    var value = field.firstInput().value.trim();
    if (!value) return 'Enter your full name.';
    if (value.length < 2) return 'Full name must be at least 2 characters.';
    return '';
  }

  function ruleRegPassword(field) {
    var value = field.firstInput().value;
    if (!value) return 'Enter a password.';
    if (value.length < 8) return 'Password must be at least 8 characters.';
    return '';
  }

  function ruleConfirm(field) {
    var value = field.firstInput().value;
    if (!value) return 'Re-enter your password.';
    var passwordEl = field.form.querySelector('#reg-password');
    if (value !== passwordEl.value) return 'Passwords do not match.';
    return '';
  }

  function ruleRole(field) {
    var checked = field.form.querySelector('[name="role"]:checked');
    if (!checked) return 'Select an account type to continue.';
    return '';
  }

  function ruleTerms(field) {
    if (!field.firstInput().checked) return 'Please accept the Terms and Privacy Policy.';
    return '';
  }

  function attachLive(field) {
    for (var i = 0; i < field.inputs.length; i++) {
      (function (input) {
        var eventName = (input.type === 'checkbox' || input.type === 'radio') ? 'change' : 'input';
        input.addEventListener(eventName, function () {
          hideSuccess();
          var message = field.rule(field);
          if (message) {
            if (field.isInvalid()) field.showError();
          } else {
            field.clear();
          }
        });
        input.addEventListener('blur', function () {
          var isCheckable = input.type === 'checkbox' || input.type === 'radio';
          var hasContent = isCheckable || (input.value && input.value.trim() !== '');
          if (!hasContent) return;
          var message = field.rule(field);
          if (message) {
            field.showError();
          } else {
            field.clear();
          }
        });
      })(field.inputs[i]);
    }
  }

  function setupForm(form, fields, submitBtn, successEl, opts) {
    var submitting = false;
    currentSuccessEl = successEl;

    form.addEventListener('submit', function (event) {
      if (submitting) return;

      hideSuccess();
      var firstInvalid = null;
      var invalidCount = 0;

      for (var i = 0; i < fields.length; i++) {
        if (fields[i].showError()) {
          invalidCount++;
          if (!firstInvalid) firstInvalid = fields[i].firstInput();
        }
      }

      if (invalidCount > 0) {
        event.preventDefault();
        if (firstInvalid) firstInvalid.focus();
        return;
      }

      if (opts.nativeSubmit) {
        if (submitBtn) submitBtn.disabled = true;
        return;
      }

      event.preventDefault();
      submitting = true;

      var label = submitBtn.querySelector('.btn__label');
      var defaultLabel = label.textContent;

      var spinner = document.createElement('span');
      spinner.className = 'btn-spinner';
      spinner.setAttribute('aria-hidden', 'true');
      submitBtn.insertBefore(spinner, label);
      submitBtn.classList.add('is-loading');
      submitBtn.disabled = true;
      submitBtn.setAttribute('aria-busy', 'true');
      label.textContent = opts.loadingLabel;

      window.setTimeout(function () {
        label.textContent = defaultLabel;
        submitBtn.removeChild(spinner);
        submitBtn.classList.remove('is-loading');
        submitBtn.removeAttribute('aria-busy');
        submitBtn.disabled = false;
        showSuccess(opts.successMessage);
        submitting = false;
      }, opts.delay || 900);
    });
  }

  var passwordToggles = document.querySelectorAll('[data-password-toggle]');

  Array.prototype.forEach.call(passwordToggles, function (btn) {
    btn.addEventListener('click', function () {
      var control = document.getElementById(btn.getAttribute('aria-controls'));
      if (!control) return;
      var show = btn.getAttribute('aria-pressed') === 'true';
      var next = show ? 'false' : 'true';
      control.setAttribute('type', show ? 'password' : 'text');
      btn.setAttribute('aria-pressed', next);
      btn.setAttribute('aria-label', show ? 'Show password' : 'Hide password');
    });
  });

  var loginForm = document.getElementById('login-form');

  if (loginForm) {
    setupForm(loginForm, [
      new Field(loginForm, '#login-email', '#login-email-error', ruleEmail),
      new Field(loginForm, '#login-password', '#login-password-error', ruleLoginPassword)
    ], loginForm.querySelector('[data-auth-submit]'), document.getElementById('login-success'), {
      loadingLabel: 'Signing in\u2026',
      successMessage: '',
      nativeSubmit: true
    });

    attachLive(new Field(loginForm, '#login-email', '#login-email-error', ruleEmail));
    attachLive(new Field(loginForm, '#login-password', '#login-password-error', ruleLoginPassword));
  }

  var registerForm = document.getElementById('register-form');

  if (registerForm) {
    var roleField = new Field(registerForm, '[name="role"]', '#reg-role-error', ruleRole);
    var nameField = new Field(registerForm, '#reg-name', '#reg-name-error', ruleName);
    var emailField = new Field(registerForm, '#reg-email', '#reg-email-error', ruleEmail);
    var passwordField = new Field(registerForm, '#reg-password', '#reg-password-error', ruleRegPassword);
    var confirmField = new Field(registerForm, '#reg-confirm', '#reg-confirm-error', ruleConfirm);
    var termsField = new Field(registerForm, '#reg-terms', '#reg-terms-error', ruleTerms);

    setupForm(registerForm, [roleField, nameField, emailField, passwordField, confirmField, termsField],
      registerForm.querySelector('[data-auth-submit]'), document.getElementById('register-success'), {
      loadingLabel: 'Creating account\u2026',
      successMessage: '',
      nativeSubmit: true
    });

    attachLive(roleField);
    attachLive(nameField);
    attachLive(emailField);
    attachLive(passwordField);
    attachLive(confirmField);
    attachLive(termsField);

    var passwordInput = registerForm.querySelector('#reg-password');
    passwordInput.addEventListener('input', function () {
      if (!confirmField.firstInput().value) return;
      var message = confirmField.rule(confirmField);
      if (message) {
        confirmField.showError();
      } else {
        confirmField.clear();
      }
    });

    var roleFieldset = document.getElementById('reg-roles');
    var roleNote = document.getElementById('reg-role-note');
    var ARTICLES = { applicant: 'a', employer: 'an' };

    function updateRoleNote() {
      if (!roleNote) return;
      var checked = roleFieldset.querySelector('input[type="radio"]:checked');
      if (!checked) {
        roleNote.textContent = '';
        return;
      }
      var label = checked.value.charAt(0).toUpperCase() + checked.value.slice(1);
      roleNote.textContent = 'You\u2019re signing up as ' + (ARTICLES[checked.value] || 'a') + ' ' + label + '.';
    }

    updateRoleNote();
    roleFieldset.addEventListener('change', updateRoleNote);
  }
})();