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

        // Spinner + texto creados como elementos (textContent, no HTML): el texto nunca se interpreta como código
        const spinner = document.createElement('span');
        spinner.className = 'spinner';
        spinner.setAttribute('aria-hidden', 'true');
        const texto = document.createElement('span');
        texto.textContent = boton.dataset.textoCargando;
        boton.replaceChildren(spinner, texto);
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

// Enter en el campo grande guarda (no salta de línea)
document.addEventListener('keydown', function (evento) {
    if (evento.key === 'Enter' && evento.target.matches('[data-campo-titulo]')) {
        evento.preventDefault();
        evento.target.form.requestSubmit();
    }
});

// 3) Hoja de formulario (components/hoja.blade.php): se comporta como un diálogo modal.
//    - el foco empieza en el primer campo (o en el que tiene error)
//    - Tab y Shift+Tab no salen de la hoja
//    - Esc la cierra
//    - al cerrarla (o guardar), el foco vuelve al enlace que la abrió
const hoja = document.querySelector('[data-hoja]');
const CAMPOS = 'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])';
const ENFOCABLES = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"]), ' + CAMPOS;

// sessionStorage puede fallar (modo privado estricto): sin él, solo se pierde el retorno del foco
function recordar(clave, valor) {
    try {
        valor === null ? sessionStorage.removeItem(clave) : sessionStorage.setItem(clave, valor);
    } catch (error) { /* sin memoria de sesión */ }
}

function recuperar(clave) {
    try {
        return sessionStorage.getItem(clave);
    } catch (error) {
        return null;
    }
}

// Elementos de la hoja a los que llega Tab (visibles; de cada grupo de radios, solo el marcado)
function enfocablesDeLaHoja() {
    return Array.from(hoja.querySelectorAll(ENFOCABLES)).filter(function (elemento) {
        const visible = elemento.getClientRects().length > 0;
        const radioSinMarcar = elemento.type === 'radio' && !elemento.checked;
        return visible && !radioSinMarcar;
    });
}

if (hoja) {
    // Primero el campo con error; si no, el marcado con data-foco-inicial; si no, el primer campo.
    // (No se usa el atributo autofocus: este código ya decide dónde va el foco.)
    const inicial = hoja.querySelector('[aria-invalid="true"]') || hoja.querySelector('[data-foco-inicial]') || hoja.querySelector(CAMPOS);
    if (inicial) {
        inicial.focus();
    }

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            window.location.href = hoja.dataset.cerrar;
            return;
        }

        if (evento.key !== 'Tab') {
            return;
        }

        const enfocables = enfocablesDeLaHoja();
        const primero = enfocables[0];
        const ultimo = enfocables[enfocables.length - 1];
        const fuera = !hoja.contains(document.activeElement);

        if (evento.shiftKey && (document.activeElement === primero || fuera)) {
            evento.preventDefault();
            ultimo.focus();
        } else if (!evento.shiftKey && (document.activeElement === ultimo || fuera)) {
            evento.preventDefault();
            primero.focus();
        }
    });
} else {
    // Página sin hoja: si venimos de cerrar una, devolvemos el foco al enlace que la abrió
    const origen = recuperar('hoja-origen');
    if (origen) {
        recordar('hoja-origen', null);
        const enlace = document.querySelector('[data-abre-hoja][href="' + CSS.escape(origen) + '"]');
        if (enlace) {
            enlace.focus();
        }
    }
}

// Los enlaces que abren una hoja llevan data-abre-hoja: guardamos cuál fue
document.addEventListener('click', function (evento) {
    const enlace = evento.target.closest('[data-abre-hoja]');
    if (enlace) {
        recordar('hoja-origen', enlace.getAttribute('href'));
    }
});

// 4) Filtros: un <select data-enviar-al-cambiar> aplica el filtro apenas se elige una opción
document.addEventListener('change', function (evento) {
    if (evento.target.matches('[data-enviar-al-cambiar]')) {
        evento.target.form.requestSubmit();
    }
});

// 5) Hoja "Nuevo movimiento" (movimientos/create.blade.php): antes de guardar, muestra el
//    stock del producto elegido y cómo queda con la cantidad escrita. Es solo una ayuda:
//    la validación real (RN-01) la hace el servidor.
const formularioMovimiento = document.querySelector('[data-movimiento]');

// 1250.5 → "1.250,5 kilogramos" (igual que UnidadMedida::cantidad en PHP)
function cantidadConUnidad(numero, opcion) {
    const texto = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 }).format(numero);
    return texto + ' ' + (numero === 1 ? opcion.dataset.singular : opcion.dataset.unidad);
}

function actualizarResumen() {
    const resumen = formularioMovimiento.querySelector('[data-resumen]');
    const opcion = formularioMovimiento.elements.producto_id.selectedOptions[0];

    if (!opcion || !opcion.value) {
        resumen.textContent = 'Elige un producto para ver su stock.';
        resumen.dataset.estado = '';
        return;
    }

    const stock = parseFloat(opcion.dataset.stock);
    const minimo = parseFloat(opcion.dataset.minimo);
    const tipo = formularioMovimiento.querySelector('[name="tipo"]:checked');
    const cantidad = parseFloat(formularioMovimiento.elements.cantidad.value.replace(',', '.'));

    let texto = 'Stock actual: ' + cantidadConUnidad(stock, opcion) + ' · mínimo ' + cantidadConUnidad(minimo, opcion);
    let estado = '';

    if (tipo && cantidad > 0) {
        // Redondeo a 2 decimales para evitar restos como 0.30000000000000004
        const queda = Math.round((tipo.value === 'entrada' ? stock + cantidad : stock - cantidad) * 100) / 100;

        if (queda < 0) {
            texto = 'No alcanza: hay ' + cantidadConUnidad(stock, opcion) + '.';
            estado = 'insuficiente';
        } else {
            texto = 'Quedarán ' + cantidadConUnidad(queda, opcion);
            if (queda < minimo) {
                texto += ' · bajo el mínimo (' + cantidadConUnidad(minimo, opcion) + ')';
                estado = 'bajo';
            }
        }
    }

    resumen.textContent = texto;
    resumen.dataset.estado = estado;   // el color sale del CSS según data-estado
}

// "Registrar" queda deshabilitado hasta que se elija entrada o salida (ninguno viene marcado)
function actualizarBotonMovimiento() {
    const tipoElegido = formularioMovimiento.querySelector('[name="tipo"]:checked');
    formularioMovimiento.querySelector('[type="submit"]').disabled = !tipoElegido;
}

if (formularioMovimiento) {
    formularioMovimiento.addEventListener('input', actualizarResumen);
    formularioMovimiento.addEventListener('change', function () {
        actualizarResumen();
        actualizarBotonMovimiento();
    });
    actualizarResumen();
    actualizarBotonMovimiento();
}

// Reportes: el botón "Imprimir" (data-imprimir) abre el diálogo de impresión del navegador.
// Lo que sale en papel lo deciden los estilos @media print de resources/css/input.css.
document.addEventListener('click', function (evento) {
    if (evento.target.closest('[data-imprimir]')) {
        window.print();
    }
});

// 6) Cerrar mensajes flash con el botón ×
document.addEventListener('click', function (evento) {
    const cerrar = evento.target.closest('[data-cerrar-alerta]');
    if (cerrar) {
        cerrar.closest('[data-alerta]').remove();
    }
});
