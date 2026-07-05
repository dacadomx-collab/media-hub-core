/**
 * Media HUB — Catalogo publico de Programas Nativos (index.php #programas)
 * Consume api/programs.php?action=public_catalog (endpoint publico, sin sesion).
 * Maquetacion ARF-Grid: el contenedor padre ya trae flex/flex-wrap/justify-center
 * en el HTML; este script solo inyecta las tarjetas hijas, sin anchos fijos en px.
 */
(function () {
  'use strict';

  var grid = document.getElementById('programasGrid');
  var loadingEl = document.getElementById('programasLoading');

  if (!grid) {
    return;
  }

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  function renderEmptyState() {
    grid.innerHTML =
      '<p class="text-sm text-deep-sea/50 dark:text-digital-white/50 text-center">' +
      'Estamos preparando el catalogo de nuestros programas nativos. Vuelve pronto.' +
      '</p>';
  }

  function renderCard(program) {
    var logo = program.logo_url
      ? '<img src="' + escapeHtml(program.logo_url) + '" alt="' + escapeHtml(program.name) + '" class="absolute inset-0 w-full h-full object-cover" loading="lazy">'
      : '<div class="absolute inset-0 grid place-items-center bg-turquoise/10 text-turquoise font-display font-bold text-2xl">' +
        escapeHtml((program.name || '?').slice(0, 2).toUpperCase()) +
        '</div>';

    // public_social_links es columna JSON en BD (Fase 5.3): arreglo
    // estructurado [{platform,url}, ...]. PDO devuelve el texto JSON
    // crudo, hay que decodificarlo antes de mostrarlo.
    var socialEntries = [];
    if (program.public_social_links) {
      try {
        var parsed = JSON.parse(program.public_social_links);
        if (Array.isArray(parsed)) socialEntries = parsed;
      } catch (err) { /* dato legado o invalido: se omite */ }
    }
    var socialLinks = socialEntries.length
      ? '<p class="text-xs text-turquoise mt-2 flex flex-wrap gap-2">' +
        socialEntries.map(function (entry) {
          var label = escapeHtml(entry.platform || entry.url || '');
          return entry.url
            ? '<a href="' + escapeHtml(entry.url) + '" target="_blank" rel="noopener" class="underline">' + label + '</a>'
            : '<span>' + label + '</span>';
        }).join('') +
        '</p>'
      : '';

    return (
      '<article class="w-full sm:basis-[47%] lg:basis-[23%] max-w-sm rounded-2xl overflow-hidden border border-turquoise/15 bg-white dark:bg-deep-sea/60 shadow-sm">' +
        '<div class="relative w-full aspect-square overflow-hidden">' + logo + '</div>' +
        '<div class="p-5">' +
          '<h3 class="font-display font-bold text-base mb-1">' + escapeHtml(program.name) + '</h3>' +
          '<p class="text-sm text-deep-sea/70 dark:text-digital-white/70 line-clamp-3">' +
            escapeHtml(program.catalog_description || '') +
          '</p>' +
          socialLinks +
        '</div>' +
      '</article>'
    );
  }

  fetch('api/programs.php?action=public_catalog', { headers: { 'Accept': 'application/json' } })
    .then(function (res) { return res.json(); })
    .then(function (result) {
      var programs = (result && result.data && result.data.programs) || [];

      if (programs.length === 0) {
        renderEmptyState();
        return;
      }

      grid.innerHTML = programs.map(renderCard).join('');
    })
    .catch(function () {
      if (loadingEl) {
        loadingEl.textContent = 'No se pudo cargar el catalogo de programas. Intenta recargar la pagina.';
      }
    });
})();
