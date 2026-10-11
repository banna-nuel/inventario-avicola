// Icosaedro 3D interactivo (RNF-08). Decorativo: no recibe foco y los lectores de pantalla lo ignoran.
//
// - Componente: resources/views/components/icosaedro.blade.php. Ahí está el SVG estático que se ve
//   mientras carga, sin WebGL o con "reducir movimiento".
// - Three.js está autoalojado en public/js/vendor (ver THIRD_PARTY.md) y se descarga solo si hace falta:
//   después del evento "load" y cuando el icosaedro está cerca de la pantalla (no frena la carga).
// - Gira lento y siempre. Con el mouse encima se inclina hacia el cursor; el scroll y el click le dan un
//   impulso que se va frenando; en táctil, arrastrar en horizontal lo gira (el scroll vertical sigue igual).
// - Se pausa con la pestaña oculta o fuera de pantalla, y libera la memoria de la GPU al salir de la página.

// Tienen que coincidir con app/View/Components/Icosaedro.php (el SVG estático)
const ROTACION_INICIAL = { x: 0.4, y: 0.6 };
const DISTANCIA_CAMARA = 4;
const CAMPO_DE_VISION = 35;

const COLOR_ARISTAS = 0x1c1c1c;   // tinta-suave
const COLOR_FONDO = 0xf4f4f4;     // fondo: la niebla aclara las aristas de atrás hacia este color
const GROSOR = 0.012;             // radio de los tubos de las aristas
const VELOCIDAD_BASE = 0.25;      // rad/s: giro lento y continuo
const AMORTIGUACION = 2.2;        // qué tan rápido se frena un impulso
const IMPULSO_MAXIMO = 9;         // rad/s

const reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)');

// Three.js r186 necesita WebGL 2. Se prueba con un lienzo: si no hay, no se escribe ningún error
function crearContextoWebGL() {
    const lienzo = document.createElement('canvas');
    try {
        const gl = lienzo.getContext('webgl2', { alpha: true, antialias: true });
        return gl ? { lienzo, gl } : null;
    } catch (error) {
        return null;
    }
}

function cuandoCargueLaPagina(funcion) {
    if (document.readyState === 'complete') {
        funcion();
    } else {
        window.addEventListener('load', funcion, { once: true });
    }
}

// Espera a que el icosaedro esté cerca de la pantalla para descargar Three.js y armar el 3D
function prepararCuandoSeVea(contenedor) {
    if (reducirMovimiento.matches || contenedor.icosaedro || contenedor.esperando) {
        return;
    }
    contenedor.esperando = true;

    const observador = new IntersectionObserver(function (entradas) {
        if (!entradas[0].isIntersecting) {
            return;
        }
        observador.disconnect();
        contenedor.esperando = false;

        const contexto = crearContextoWebGL();
        if (!contexto) {
            contenedor.dataset.modo = 'sin-webgl';   // se queda el SVG estático
            return;
        }

        import('./vendor/three.module.js')
            .then((THREE) => iniciar(contenedor, contexto, THREE))
            .catch(() => { contenedor.dataset.modo = 'estatico'; });
    }, { rootMargin: '200px' });

    observador.observe(contenedor);
}

// Las aristas son tubos finos y los vértices, esferitas: las líneas de WebGL miden 1 píxel físico
// (casi invisibles en pantallas de alta densidad). InstancedMesh dibuja todas las aristas de una vez.
function crearIcosaedro(THREE) {
    const phi = (1 + Math.sqrt(5)) / 2;
    const vertices = [
        [-1, phi, 0], [1, phi, 0], [-1, -phi, 0], [1, -phi, 0],
        [0, -1, phi], [0, 1, phi], [0, -1, -phi], [0, 1, -phi],
        [phi, 0, -1], [phi, 0, 1], [-phi, 0, -1], [-phi, 0, 1],
    ].map(([x, y, z]) => new THREE.Vector3(x, y, z).normalize());

    // Las 30 aristas unen los vértices que están a la distancia de un lado
    const lado = vertices[0].distanceTo(vertices[1]);
    const aristas = [];
    vertices.forEach((a, i) => vertices.forEach((b, j) => {
        if (j > i && Math.abs(a.distanceTo(b) - lado) < 0.001) {
            aristas.push([a, b]);
        }
    }));

    const material = new THREE.MeshBasicMaterial({ color: COLOR_ARISTAS });
    const tubo = new THREE.CylinderGeometry(GROSOR, GROSOR, 1, 8, 1, true);
    const esfera = new THREE.SphereGeometry(GROSOR, 10, 8);

    // Cada tubo (de alto 1, vertical) se lleva al centro de su arista, se gira y se estira a su largo
    const tubos = new THREE.InstancedMesh(tubo, material, aristas.length);
    const matriz = new THREE.Matrix4();
    const vertical = new THREE.Vector3(0, 1, 0);
    aristas.forEach(([a, b], i) => {
        const direccion = new THREE.Vector3().subVectors(b, a);
        const largo = direccion.length();
        const giro = new THREE.Quaternion().setFromUnitVectors(vertical, direccion.normalize());
        matriz.compose(a.clone().add(b).multiplyScalar(0.5), giro, new THREE.Vector3(1, largo, 1));
        tubos.setMatrixAt(i, matriz);
    });

    const nudos = new THREE.InstancedMesh(esfera, material, vertices.length);
    vertices.forEach((v, i) => nudos.setMatrixAt(i, matriz.makeTranslation(v.x, v.y, v.z)));

    const figura = new THREE.Group();
    figura.add(tubos, nudos);

    return { figura, recursos: [tubos, nudos, tubo, esfera, material] };
}

function iniciar(contenedor, { lienzo, gl }, THREE) {
    const renderer = new THREE.WebGLRenderer({ canvas: lienzo, context: gl, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));   // más de 2 no se nota y cuesta mucho
    renderer.setClearColor(0x000000, 0);                             // fondo transparente

    const escena = new THREE.Scene();
    escena.fog = new THREE.Fog(COLOR_FONDO, DISTANCIA_CAMARA - 0.9, DISTANCIA_CAMARA + 2.6);

    const camara = new THREE.PerspectiveCamera(CAMPO_DE_VISION, 1, 0.1, 20);
    camara.position.z = DISTANCIA_CAMARA;

    const { figura, recursos } = crearIcosaedro(THREE);
    escena.add(figura);

    const estado = {
        giro: { ...ROTACION_INICIAL },   // giro continuo + impulsos + arrastre
        inclinacion: { x: 0, y: 0 },     // hacia el cursor, suavizada
        objetivo: { x: 0, y: 0 },        // hacia dónde apunta el cursor
        impulso: 0,                      // velocidad extra (rad/s) que se va frenando
    };
    // Para depurar y para las pruebas de Playwright (la app no lo usa)
    contenedor.icosaedro = estado;

    let visible = true;
    let idCuadro = 0;
    let anterior = 0;
    let ultimoScroll = window.scrollY;
    let arrastre = null;

    function dibujar() {
        figura.rotation.set(estado.giro.x + estado.inclinacion.x, estado.giro.y + estado.inclinacion.y, 0);
        renderer.render(escena, camara);
    }

    function cuadro(ahora) {
        const dt = Math.min((ahora - anterior) / 1000, 0.05);   // segundos (con tope si la pestaña se congeló)
        anterior = ahora;

        estado.impulso *= Math.exp(-AMORTIGUACION * dt);
        estado.giro.y += (VELOCIDAD_BASE + estado.impulso) * dt;
        estado.giro.x += VELOCIDAD_BASE * 0.3 * dt;

        const suavizado = 1 - Math.exp(-5 * dt);
        estado.inclinacion.x += (estado.objetivo.x - estado.inclinacion.x) * suavizado;
        estado.inclinacion.y += (estado.objetivo.y - estado.inclinacion.y) * suavizado;

        dibujar();
        idCuadro = requestAnimationFrame(cuadro);
    }

    // Anima solo si vale la pena: pestaña visible, componente en pantalla y sin "reducir movimiento"
    function actualizar() {
        const animar = visible && !document.hidden && !reducirMovimiento.matches;

        if (animar && !idCuadro) {
            anterior = performance.now();
            idCuadro = requestAnimationFrame(cuadro);
            contenedor.dataset.modo = 'animado';
        } else if (!animar && idCuadro) {
            cancelAnimationFrame(idCuadro);
            idCuadro = 0;
            contenedor.dataset.modo = reducirMovimiento.matches ? 'estatico' : 'pausado';
        }
    }

    function sumarImpulso(valor) {
        estado.impulso = Math.max(-IMPULSO_MAXIMO, Math.min(IMPULSO_MAXIMO, estado.impulso + valor));
    }

    function alMoverPuntero(evento) {
        // Táctil: arrastrar en horizontal gira, y al soltar sigue con la inercia del gesto
        if (evento.pointerType === 'touch') {
            if (!arrastre) {
                return;
            }
            const dx = evento.clientX - arrastre.x;
            const dt = Math.max((evento.timeStamp - arrastre.t) / 1000, 0.001);
            estado.giro.y += dx * 0.012;
            estado.impulso = Math.max(-IMPULSO_MAXIMO, Math.min(IMPULSO_MAXIMO, (dx * 0.012 / dt) * 0.5));
            arrastre = { x: evento.clientX, t: evento.timeStamp };
            return;
        }

        // Mouse o lápiz encima: se inclina hacia el cursor (de -1 a 1 en cada eje del componente)
        const caja = contenedor.getBoundingClientRect();
        estado.objetivo.y = (((evento.clientX - caja.left) / caja.width) * 2 - 1) * 0.6;
        estado.objetivo.x = (((evento.clientY - caja.top) / caja.height) * 2 - 1) * 0.45;
    }

    function alSalirPuntero() {
        estado.objetivo.x = 0;
        estado.objetivo.y = 0;
    }

    function alPresionar(evento) {
        if (evento.pointerType === 'touch') {
            arrastre = { x: evento.clientX, t: evento.timeStamp };
        }
    }

    function alSoltar() {
        arrastre = null;
    }

    function alHacerClick() {
        sumarImpulso(4);
    }

    // El scroll de la página da un impulso según cuánto se movió (escucha pasiva: nunca bloquea el scroll)
    function alHacerScroll() {
        const delta = window.scrollY - ultimoScroll;
        ultimoScroll = window.scrollY;
        sumarImpulso(delta * 0.01);
    }

    function ajustarTamano() {
        const ancho = contenedor.clientWidth;
        const alto = contenedor.clientHeight;
        renderer.setSize(ancho, alto, false);   // false: el tamaño en pantalla lo da el CSS
        camara.aspect = ancho / alto;
        camara.updateProjectionMatrix();
        dibujar();
    }

    const observador = new IntersectionObserver(function (entradas) {
        visible = entradas[0].isIntersecting;
        actualizar();
    });
    const observadorTamano = new ResizeObserver(ajustarTamano);

    // [elemento, evento, función]: todas pasivas (ninguna llama a preventDefault)
    const escuchas = [
        [contenedor, 'pointermove', alMoverPuntero],
        [contenedor, 'pointerleave', alSalirPuntero],
        [contenedor, 'pointerdown', alPresionar],
        [contenedor, 'pointerup', alSoltar],
        [contenedor, 'pointercancel', alSoltar],
        [contenedor, 'click', alHacerClick],
        [window, 'scroll', alHacerScroll],
        [document, 'visibilitychange', actualizar],
        [reducirMovimiento, 'change', actualizar],
    ];

    // Al salir de la página: libera geometrías, materiales y el contexto de WebGL
    function liberar() {
        cancelAnimationFrame(idCuadro);
        idCuadro = 0;
        observador.disconnect();
        observadorTamano.disconnect();
        escuchas.forEach(([objetivo, evento, funcion]) => objetivo.removeEventListener(evento, funcion));
        recursos.forEach((recurso) => recurso.dispose());
        renderer.dispose();
        renderer.forceContextLoss();
        lienzo.remove();
        delete contenedor.dataset.webgl;
        contenedor.dataset.modo = 'estatico';
        contenedor.icosaedro = null;
    }

    lienzo.setAttribute('aria-hidden', 'true');
    contenedor.append(lienzo);
    contenedor.dataset.webgl = '';   // el CSS oculta el SVG estático
    ajustarTamano();

    escuchas.forEach(([objetivo, evento, funcion]) => objetivo.addEventListener(evento, funcion, { passive: true }));
    observador.observe(contenedor);
    observadorTamano.observe(contenedor);
    window.addEventListener('pagehide', liberar, { once: true });

    actualizar();
}

document.querySelectorAll('[data-icosaedro]').forEach(function (contenedor) {
    contenedor.dataset.modo = 'estatico';
    cuandoCargueLaPagina(() => prepararCuandoSeVea(contenedor));

    // Si se apaga "reducir movimiento" con la página abierta, arma el 3D
    reducirMovimiento.addEventListener('change', () => prepararCuandoSeVea(contenedor));

    // Al volver con "Atrás" (página restaurada desde la caché del navegador), lo arma de nuevo
    window.addEventListener('pageshow', (evento) => {
        if (evento.persisted) {
            prepararCuandoSeVea(contenedor);
        }
    });
});
