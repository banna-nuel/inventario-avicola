// JavaScript vanilla del proyecto (sin npm ni bundler). Se carga con defer en todas las páginas.

// 1) Feedback al enviar formularios: deshabilita el botón, muestra un spinner y evita el doble envío.
document.addEventListener('submit', function (evento) {
    const formulario = evento.target;

    if (formulario.dataset.enviando === 'si') {
        evento.preventDefault();
        return;
    }
    formulario.dataset.enviando = 'si';

    const boton = evento.submitter || formulario.querySelector('[type="submit"]');
    if (!boton) {
        return;
    }

    boton.setAttribute('aria-busy', 'true');
    boton.disabled = true;

    if (boton.dataset.textoCargando) {
        boton.dataset.textoOriginal = boton.innerHTML;
        boton.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>' + boton.dataset.textoCargando + '</span>';
    }
});

// Si el usuario vuelve con el botón "Atrás", el navegador puede restaurar la página
// con los botones aún deshabilitados: los reactivamos.
window.addEventListener('pageshow', function (evento) {
    if (!evento.persisted) {
        return;
    }
    document.querySelectorAll('form[data-enviando]').forEach(function (formulario) {
        delete formulario.dataset.enviando;
    });
    document.querySelectorAll('[aria-busy="true"]').forEach(function (boton) {
        boton.disabled = false;
        boton.removeAttribute('aria-busy');
        if (boton.dataset.textoOriginal) {
            boton.innerHTML = boton.dataset.textoOriginal;
        }
    });
});

// 2) Cerrar mensajes flash con el botón ×
document.addEventListener('click', function (evento) {
    const cerrar = evento.target.closest('[data-cerrar-alerta]');
    if (cerrar) {
        cerrar.closest('[data-alerta]').remove();
    }
});
