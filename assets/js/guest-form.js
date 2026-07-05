/**
 * Media HUB — Guest Onboarding (guest_form.php)
 * Consume api/guest_submissions.php. Sin dependencias externas.
 */
(function () {
  'use strict';

  var token = window.MH_GUEST_TOKEN || '';

  var elLoading   = document.getElementById('gfLoading');
  var elExpired   = document.getElementById('gfExpired');
  var elExpiredMsg = document.getElementById('gfExpiredMessage');
  var elForm      = document.getElementById('gfForm');
  var elLogo      = document.getElementById('gfLogo');
  var elChannelBadge = document.getElementById('gfChannelBadge');
  var elName      = document.getElementById('gfProgramName');
  var elDesc      = document.getElementById('gfProgramDescription');
  var elInviteLine = document.getElementById('gfInviteLine');
  var elThemeNotice = document.getElementById('gfThemeNotice');
  var elNotesBox  = document.getElementById('gfConductorNotesBox');
  var elNotesText = document.getElementById('gfConductorNotesText');
  var elContactBox = document.getElementById('gfContactBox');
  var elContactButtons = document.getElementById('gfContactButtons');
  var elClicksLeft = document.getElementById('gfClicksLeft');
  var elSystemMessage = document.getElementById('gfSystemMessage');
  var elSubmitBtn = document.getElementById('gfSubmitBtn');
  var guestForm   = document.getElementById('guestForm');

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str === null || str === undefined ? '' : String(str);
    return div.innerHTML;
  }

  function showState(el) {
    [elLoading, elExpired, elForm].forEach(function (node) {
      node.classList.add('hidden');
    });
    el.classList.remove('hidden');
  }

  function fillField(id, value) {
    var field = document.getElementById(id);
    if (field && value) {
      field.value = value;
    }
  }

  function apiUrl(base) {
    return 'api/guest_submissions.php?token=' + encodeURIComponent(token) + (base || '');
  }

  if (!token) {
    elExpiredMsg.textContent = 'No se encontro un token de invitacion valido en el enlace.';
    showState(elExpired);
    return;
  }

  // -----------------------------------------------------------------
  // Carga inicial: GET dispara el avance del contador de clics (TTL)
  // -----------------------------------------------------------------
  fetch(apiUrl(), { method: 'GET', headers: { 'Accept': 'application/json' } })
    .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
    .then(function (result) {
      if (!result.ok) {
        elExpiredMsg.textContent = result.body.message || 'Este enlace ya no esta disponible.';
        showState(elExpired);
        return;
      }

      var data = result.body.data;
      var conductorName = (data.conductor && data.conductor.full_name) || '';

      document.getElementById('csrf_token').value = data.csrf_token || '';
      elName.textContent = data.program.name || 'Invitacion de produccion';
      elDesc.textContent = data.program.catalog_description || '';
      elChannelBadge.textContent = data.program.affiliated_channel || 'Invitacion de produccion';

      // Fase 5.8 — Light Mode: la pagina se presenta como el show del
      // Conductor, no como Media HUB. Mensaje personalizado con su nombre.
      elInviteLine.textContent = conductorName
        ? conductorName + ' te invita a participar en ' + (data.program.name || 'su programa') + '.'
        : 'Has sido invitado a participar en ' + (data.program.name || 'este programa') + '.';

      if (data.program.logo_url) {
        elLogo.src = data.program.logo_url;
        elLogo.classList.remove('hidden');
      }

      // Fase 5.5: si el enlace esta atado a un llamado real, mostrar fecha/
      // hora/locacion exactas en vez del texto generico de marcador.
      if (data.call) {
        var dateNotice = document.getElementById('gfDateNotice');
        if (dateNotice) {
          dateNotice.innerHTML = '<strong>Fecha y hora:</strong> ' +
            data.call.call_date + ', ' + data.call.start_time + ' - ' + data.call.end_time +
            ' (' + data.call.location + ')';
        }

        // Fase 5.8: tema del episodio, si el Conductor ya lo definio.
        if (data.call.episode_theme) {
          elThemeNotice.innerHTML = '<strong>Tema de este episodio:</strong> ' + escapeHtml(data.call.episode_theme);
          elThemeNotice.classList.remove('hidden');
        }
      }

      // Fase 5.8: recomendaciones del Conductor (se leen UNA VEZ por show).
      if (data.program.conductor_notes) {
        elNotesText.textContent = data.program.conductor_notes;
        elNotesBox.classList.remove('hidden');
      }

      // Fase 5.8: contacto directo opt-in (WhatsApp/Email), solo si el
      // Conductor activo la visibilidad publica de ese dato.
      if (data.conductor && (data.conductor.whatsapp || data.conductor.email)) {
        var buttons = '';
        if (data.conductor.whatsapp) {
          var waNumber = String(data.conductor.whatsapp).replace(/[^\d+]/g, '');
          buttons += '<a class="gf-contact-btn gf-contact-whatsapp" href="https://wa.me/' +
            encodeURIComponent(waNumber) + '" target="_blank" rel="noopener">WhatsApp</a>';
        }
        if (data.conductor.email) {
          buttons += '<a class="gf-contact-btn gf-contact-email" href="mailto:' +
            encodeURIComponent(data.conductor.email) + '">Email</a>';
        }
        elContactButtons.innerHTML = buttons;
        elContactBox.classList.remove('hidden');
      }

      if (data.submission) {
        fillField('full_name', data.submission.full_name);
        fillField('title_position', data.submission.title_position);
        fillField('social_links', data.submission.social_links);
        fillField('whatsapp', data.submission.whatsapp);
        fillField('website', data.submission.website);
        fillField('email', data.submission.email);
        fillField('invite_message', data.submission.invite_message);
        fillField('qa_notes', data.submission.qa_notes);
      }

      elClicksLeft.textContent = data.clicks_restantes === 1
        ? 'Podras editar tus datos una vez mas antes de que este enlace expire.'
        : 'Clics restantes en este enlace: ' + data.clicks_restantes;

      showState(elForm);
    })
    .catch(function () {
      elExpiredMsg.textContent = 'No se pudo cargar la invitacion. Verifica tu conexion e intenta de nuevo.';
      showState(elExpired);
    });

  // -----------------------------------------------------------------
  // Guardado parcial (crea o actualiza)
  // -----------------------------------------------------------------
  guestForm.addEventListener('submit', function (event) {
    event.preventDefault();

    ['full_name', 'title_position', 'email'].forEach(function (id) {
      var field = document.getElementById(id);
      var msg   = document.getElementById(id + '_message');
      if (field && field.parentElement) {
        field.parentElement.classList.remove('gf-invalid');
      }
      if (msg) {
        msg.textContent = '';
      }
    });

    var fullName  = document.getElementById('full_name').value.trim();
    var titlePos  = document.getElementById('title_position').value.trim();
    var hasError  = false;

    if (fullName === '') {
      document.getElementById('full_name').parentElement.classList.add('gf-invalid');
      document.getElementById('full_name_message').textContent = 'Este campo es obligatorio.';
      hasError = true;
    }
    if (titlePos === '') {
      document.getElementById('title_position').parentElement.classList.add('gf-invalid');
      document.getElementById('title_position_message').textContent = 'Este campo es obligatorio.';
      hasError = true;
    }
    if (hasError) {
      return;
    }

    var payload = {
      csrf_token: document.getElementById('csrf_token').value,
      full_name: fullName,
      title_position: titlePos,
      social_links: document.getElementById('social_links').value.trim(),
      whatsapp: document.getElementById('whatsapp').value.trim(),
      website: document.getElementById('website').value.trim(),
      email: document.getElementById('email').value.trim(),
      invite_message: document.getElementById('invite_message').value.trim(),
      qa_notes: document.getElementById('qa_notes').value.trim(),
    };

    elSubmitBtn.disabled = true;
    elSubmitBtn.textContent = 'Guardando...';
    elSystemMessage.textContent = '';
    elSystemMessage.className = 'gf-system-message';

    fetch(apiUrl(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
      .then(function (result) {
        elSubmitBtn.disabled = false;
        elSubmitBtn.textContent = 'Guardar mis datos';

        if (!result.ok) {
          elSystemMessage.textContent = result.body.message || 'No se pudo guardar. Intenta de nuevo.';
          elSystemMessage.classList.add('gf-error');
          return;
        }

        elSystemMessage.textContent = result.body.message || 'Datos guardados correctamente.';
        elSystemMessage.classList.add('gf-success');
      })
      .catch(function () {
        elSubmitBtn.disabled = false;
        elSubmitBtn.textContent = 'Guardar mis datos';
        elSystemMessage.textContent = 'Error de conexion. Intenta de nuevo.';
        elSystemMessage.classList.add('gf-error');
      });
  });
})();
