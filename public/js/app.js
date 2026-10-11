// JavaScript vanilla del proyecto (sin npm ni bundler). Se carga con defer en todas las páginas.

// 1) Feedback al enviar formularios: deshabilita el botón, muestra un spinner y evita el doble envío.
document.addEventListener('submit', function (evento) {
    const formulario = evento.target;

    // Formularios peligrosos (eliminar) piden confirmación: <form data-confirmar="¿Eliminar…?">
    if (formulario.dataset.confirmar && !window.confirm(formulario.dataset.confirmar)) {
        evento.preventDefault();
        return;
    }

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

// 2) Campo grande de las hojas (components/campo-titulo.blade.php):
//    la letra se achica cuando el nombre es largo, como en el Figma (hasta 14 letras: 40 px, etc.)
const TAMANOS_TITULO = [[14, 40], [22, 32], [34, 26]];

function ajustarCampoTitulo(campo) {
    const largo = campo.value.length;
    const regla = TAMANOS_TITULO.find(function (par) { return largo <= par[0]; });
    campo.style.fontSize = (regla ? regla[1] : 22) + 'px';
}

document.querySelectorAll('[data-campo-titulo]').forEach(ajustarCampoTitulo);

document.addEventListener('input', function (evento) {
    if (evento.target.matches('[data-campo-titulo]')) {
        ajustarCampoTitulo(evento.target);
    }
});

document.addEventListener('keydown', function (evento) {
    // Enter en el campo grande guarda (no salta de línea)
    if (evento.key === 'Enter' && evento.target.matches('[data-campo-titulo]')) {
        evento.preventDefault();
        evento.target.form.requestSubmit();
    }

    // Esc cierra la hoja abierta
    const hoja = document.querySelector('[data-hoja]');
    if (evento.key === 'Escape' && hoja) {
        window.location.href = hoja.dataset.cerrar;
    }
});

// 3) Cerrar mensajes flash con el botón ×
document.addEventListener('click', function (evento) {
    const cerrar = evento.target.closest('[data-cerrar-alerta]');
    if (cerrar) {
        cerrar.closest('[data-alerta]').remove();
    }
});
