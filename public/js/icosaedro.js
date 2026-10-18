// Icosaedro 3D interactivo que se transforma en huevo (RNF-08). Decorativo: no recibe foco y los
// lectores de pantalla lo ignoran.
//
// - Componente: resources/views/components/icosaedro.blade.php. Ahí están los dos SVG estáticos: el
//   icosaedro, que se ve mientras carga el 3D, y el huevo, que es el estado final sin WebGL o con
//   "reducir movimiento" (en esos dos casos no se descarga Three.js).
// - Three.js está autoalojado en public/js/vendor/three-r186 (ver THIRD_PARTY.md) y se descarga solo si hace falta:
//   después del evento "load" y cuando el icosaedro está cerca de la pantalla (no frena la carga).
// - Aparece como icosaedro y a los 1,2 s se transforma en huevo (en 1,4 s); cada click alterna entre las
//   dos formas (en 1 s). Las 30 aristas se parten en tramos que se curvan sobre la superficie del huevo, y
//   al final se funde el contorno del huevo, que es lo que hace que se lea como huevo.
// - Gira lento y siempre; el huevo, solo alrededor de su eje vertical (no se vuelca). Con el mouse encima
//   se inclina hacia el cursor (el huevo, hasta ±15°); el scroll y el click le dan un impulso que se va
//   frenando; en táctil, arrastrar en horizontal lo gira (el scroll vertical sigue igual).
// - Se pausa con la pestaña oculta o fuera de pantalla, y libera la memoria de la GPU al salir de la
//   página o al activarse "reducir movimiento" (entonces queda el huevo estático).

// Tienen que coincidir con app/View/Components/Icosaedro.php (los SVG estáticos)
const ROTACION_INICIAL = { x: 0.4, y: 0.6 };
const DISTANCIA_CAMARA = 4;
const CAMPO_DE_VISION = 35;
// Huevo: cada punto (x, y, z) de la esfera de radio 1 va a
// x' = x·(1 − k·y), y' = ys·y, z' = z·(1 − k·y), todo multiplicado por la escala
const HUEVO = {
    asimetria: 0.2,       // k: ensancha la mitad de abajo y afina la de arriba
    alargamiento: 1.32,   // ys: alto:ancho ≈ 1,3:1
    escala: 0.82,         // del mismo tamaño en pantalla que el icosaedro
    inclinacion: 0.2,     // rad: se ve un poco desde arriba (así los arcos no parecen rectas)
};
const OPACIDAD_SILUETA = 0.85;

const COLOR_ARISTAS = 0x1c1c1c;   // tinta-suave
const COLOR_FONDO = 0xf4f4f4;     // fondo: la niebla aclara las aristas de atrás hacia este color
const GROSOR = 0.012;             // radio de los tubos de las aristas
const VELOCIDAD_BASE = 0.25;      // rad/s: giro lento y continuo
const AMORTIGUACION = 2.2;        // qué tan rápido se frena un impulso
const IMPULSO_MAXIMO = 9;         // rad/s

const TRAMOS_POR_ARISTA = 16;     // tramos rectos con los que cada arista se curva sobre el huevo
const PUNTOS_SILUETA = 48;        // por cada lado del contorno del huevo
const ESPERA_HUEVO = 1.2;         // s como icosaedro antes de transformarse
const DURACION_HUEVO = 1.4;       // s de la transformación del inicio
const DURACION_ALTERNAR = 1;      // s al alternar con un click
const INCLINACION_PUNTERO = { x: 0.45, y: 0.6 };   // rad hacia el cursor como icosaedro...
const INCLINACION_PUNTERO_HUEVO = 0.26;            // ...y como huevo (≈ ±15°)

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
            contenedor.dataset.modo = 'sin-webgl';   // el CSS muestra el huevo estático
            return;
        }

        // La carpeta lleva la versión: al actualizar Three.js cambia la ruta y ningún navegador
        // se queda con la copia vieja guardada en caché (README, "Actualizar Three.js")
        import('./vendor/three-r186/three.module.js')
            .then((THREE) => iniciar(contenedor, contexto, THREE))
            .catch(() => { contenedor.dataset.modo = 'error'; });
    }, { rootMargin: '200px' });

    observador.observe(contenedor);
}

// La curva de los tokens (--ease-asentar): cubic-bezier(.22, 1, .36, 1). Recibe el avance del tiempo
// (de 0 a 1) y devuelve el de la forma. Busca por bisección el punto de la curva para ese tiempo.
function asentar(avance) {
    const bezier = (p1, p2, t) => 3 * p1 * t * (1 - t) ** 2 + 3 * p2 * t ** 2 * (1 - t) + t ** 3;
    let bajo = 0;
    let alto = 1;
    for (let i = 0; i < 25; i++) {
        const medio = (bajo + alto) / 2;
        if (bezier(0.22, 0.36, medio) < avance) {
            bajo = medio;
        } else {
            alto = medio;
        }
    }
    return bezier(1, 1, (bajo + alto) / 2);
}

// 0 antes de "desde", 1 después de "hasta" y un paso suave en el medio
function suavizar(desde, hasta, valor) {
    const t = Math.min(Math.max((valor - desde) / (hasta - desde), 0), 1);
    return t * t * (3 - 2 * t);
}

// Lleva un punto de la esfera de radio 1 a la superficie del huevo (extremo ancho abajo)
function aHuevo(punto) {
    const ancho = HUEVO.escala * (1 - HUEVO.asimetria * punto.y);
    return punto.clone().set(punto.x * ancho, punto.y * HUEVO.escala * HUEVO.alargamiento, punto.z * ancho);
}

// Contorno del huevo visto desde la cámara: los puntos donde la visual roza la superficie, en el sistema
// del huevo. Es la misma cuenta que contornoDelHuevo() en app/View/Components/Icosaedro.php (ahí está
// explicada). Solo depende de la inclinación: el giro alrededor del eje vertical no lo cambia.
function contornoDelHuevo(inclinacion) {
    const { asimetria: k, alargamiento: ys, escala: s } = HUEVO;
    const h = DISTANCIA_CAMARA * Math.sin(inclinacion);     // la cámara vista desde el huevo
    const rho = DISTANCIA_CAMARA * Math.cos(inclinacion);

    const radio = (v) => s * Math.sin(v) * (1 - k * Math.cos(v));
    const altura = (v) => s * ys * Math.cos(v);
    const condicion = (v) => radio(v) + (s * (Math.cos(v) - k * Math.cos(2 * v))) * (altura(v) - h) / (s * ys * Math.sin(v));

    // Bisección entre un ángulo con contorno (dentro) y otro sin contorno (fuera)
    function borde(dentro, fuera) {
        for (let i = 0; i < 50; i++) {
            const medio = (dentro + fuera) / 2;
            if (Math.abs(condicion(medio)) <= rho) {
                dentro = medio;
            } else {
                fuera = medio;
            }
        }
        return dentro;
    }
    const vMin = borde(Math.PI / 2, 1e-6);
    const vMax = borde(Math.PI / 2, Math.PI - 1e-6);

    const ida = [];
    const vuelta = [];
    for (let i = 0; i <= PUNTOS_SILUETA; i++) {
        // Más puntos cerca de las puntas, donde el contorno se curva más
        const v = vMin + (vMax - vMin) * (1 - Math.cos(Math.PI * i / PUNTOS_SILUETA)) / 2;
        const desvio = Math.acos(Math.max(-1, Math.min(1, condicion(v) / rho)));
        const [r, y] = [radio(v), altura(v)];
        ida.push([-r * Math.sin(desvio), y, r * Math.cos(desvio)]);
        vuelta.push([r * Math.sin(desvio), y, r * Math.cos(desvio)]);
    }

    // Un lado de arriba a abajo y el otro de vuelta, sin repetir las puntas
    return ida.concat(vuelta.reverse().slice(1, -1));
}

// Las aristas son tubos finos y los vértices, esferitas: las líneas de WebGL miden 1 píxel físico
// (casi invisibles en pantallas de alta densidad). InstancedMesh dibuja todos los tubos de una vez.
function crearFigura(THREE) {
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

    // Cada arista se parte en tramos. Cada punto tiene dos lugares: sobre la arista recta (icosaedro) y el
    // mismo punto llevado a la superficie del huevo. La transformación interpola entre los dos: así cada
    // punto del icosaedro tiene su pareja en el huevo.
    const arcos = aristas.map(([a, b]) => Array.from({ length: TRAMOS_POR_ARISTA + 1 }, (_, i) => {
        const ico = new THREE.Vector3().lerpVectors(a, b, i / TRAMOS_POR_ARISTA);
        return { ico, huevo: aHuevo(ico.clone().normalize()) };
    }));
    const verticesHuevo = vertices.map(aHuevo);

    const material = new THREE.MeshBasicMaterial({ color: COLOR_ARISTAS });
    // El contorno del huevo aparece con un fundido; sin niebla, como en el SVG estático
    const materialSilueta = new THREE.MeshBasicMaterial({
        color: COLOR_ARISTAS, transparent: true, opacity: 0, depthWrite: false, fog: false,
    });
    const tubo = new THREE.CylinderGeometry(GROSOR, GROSOR, 1, 8, 1, true);
    const esfera = new THREE.SphereGeometry(GROSOR, 10, 8);

    const tubos = new THREE.InstancedMesh(tubo, material, arcos.length * TRAMOS_POR_ARISTA);
    const nudos = new THREE.InstancedMesh(esfera, material, vertices.length);
    const silueta = new THREE.InstancedMesh(tubo, materialSilueta, 2 * PUNTOS_SILUETA);
    // Las instancias se mueven: que Three.js no las descarte por una caja calculada una sola vez
    [tubos, nudos, silueta].forEach((malla) => { malla.frustumCulled = false; });

    const matriz = new THREE.Matrix4();
    const giro = new THREE.Quaternion();
    const vertical = new THREE.Vector3(0, 1, 0);
    const [desde, hasta, centro, direccion, estirar] = Array.from({ length: 5 }, () => new THREE.Vector3());

    // Cada tubo (de alto 1, vertical) se lleva al centro de su tramo, se gira y se estira a su largo
    function colocarTubo(malla, indice, a, b) {
        direccion.subVectors(b, a);
        const largo = direccion.length();
        giro.setFromUnitVectors(vertical, direccion.divideScalar(largo || 1));
        matriz.compose(centro.addVectors(a, b).multiplyScalar(0.5), giro, estirar.set(1, largo, 1));
        malla.setMatrixAt(indice, matriz);
    }

    // forma: 0 = icosaedro (los tramos de cada arista quedan en línea recta), 1 = huevo
    function transformar(forma) {
        arcos.forEach((puntos, i) => {
            for (let j = 0; j < TRAMOS_POR_ARISTA; j++) {
                desde.lerpVectors(puntos[j].ico, puntos[j].huevo, forma);
                hasta.lerpVectors(puntos[j + 1].ico, puntos[j + 1].huevo, forma);
                colocarTubo(tubos, i * TRAMOS_POR_ARISTA + j, desde, hasta);
            }
        });
        vertices.forEach((v, i) => {
            desde.lerpVectors(v, verticesHuevo[i], forma);
            nudos.setMatrixAt(i, matriz.makeTranslation(desde.x, desde.y, desde.z));
        });
        tubos.instanceMatrix.needsUpdate = true;
        nudos.instanceMatrix.needsUpdate = true;
    }

    // El contorno va aparte (solo con la inclinación, sin el giro) y se recalcula si la inclinación cambia
    const contorno = new THREE.Group();
    contorno.add(silueta);
    let inclinacionDelContorno = NaN;

    function dibujarContorno(inclinacion, opacidad) {
        contorno.rotation.x = inclinacion;
        materialSilueta.opacity = opacidad * OPACIDAD_SILUETA;
        silueta.visible = opacidad > 0;
        if (!silueta.visible || Math.abs(inclinacion - inclinacionDelContorno) < 0.0005) {
            return;
        }
        inclinacionDelContorno = inclinacion;

        const puntos = contornoDelHuevo(inclinacion);
        puntos.forEach((punto, i) => {
            colocarTubo(silueta, i, desde.fromArray(punto), hasta.fromArray(puntos[(i + 1) % puntos.length]));
        });
        silueta.instanceMatrix.needsUpdate = true;
    }

    const figura = new THREE.Group();
    figura.add(tubos, nudos);
    transformar(0);

    return {
        figura,
        contorno,
        transformar,
        dibujarContorno,
        recursos: [tubos, nudos, silueta, tubo, esfera, material, materialSilueta],
    };
}

function iniciar(contenedor, { lienzo, gl }, THREE) {
    // Si se activó "reducir movimiento" mientras se descargaba Three.js, queda el huevo estático
    if (reducirMovimiento.matches) {
        contenedor.dataset.modo = 'estatico';
        return;
    }

    const renderer = new THREE.WebGLRenderer({ canvas: lienzo, context: gl, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));   // más de 2 no se nota y cuesta mucho
    renderer.setClearColor(0x000000, 0);                             // fondo transparente

    const escena = new THREE.Scene();
    escena.fog = new THREE.Fog(COLOR_FONDO, DISTANCIA_CAMARA - 0.9, DISTANCIA_CAMARA + 2.6);

    const camara = new THREE.PerspectiveCamera(CAMPO_DE_VISION, 1, 0.1, 20);
    camara.position.z = DISTANCIA_CAMARA;

    const { figura, contorno, transformar, dibujarContorno, recursos } = crearFigura(THREE);
    escena.add(figura, contorno);

    const estado = {
        giro: { ...ROTACION_INICIAL },   // giro continuo + impulsos + arrastre
        inclinacion: { x: 0, y: 0 },     // hacia el cursor, suavizada
        puntero: { x: 0, y: 0 },         // cursor sobre el componente, de -1 a 1 en cada eje (0 = afuera)
        impulso: 0,                      // velocidad extra (rad/s) que se va frenando
        tiempo: 0,                       // segundos animados (en pausa no corre)
        forma: 0,                        // 0 = icosaedro, 1 = huevo (ya con la curva aplicada)
        transicion: { desde: 0, hasta: 0, inicio: 0, duracion: 1 },
        huevoPendiente: true,            // la transformación automática del inicio
    };
    // Para depurar y para las pruebas de Playwright (la app no lo usa)
    contenedor.icosaedro = estado;

    let visible = true;
    let idCuadro = 0;
    let anterior = 0;
    let ultimoScroll = window.scrollY;
    let arrastre = null;

    function dibujar() {
        // Al volverse huevo, el vuelco del icosaedro (giro en X) deja lugar a la inclinación fija del huevo
        const inclinacion = estado.giro.x + (HUEVO.inclinacion - estado.giro.x) * estado.forma + estado.inclinacion.x;
        figura.rotation.set(inclinacion, estado.giro.y + estado.inclinacion.y, 0);
        dibujarContorno(inclinacion, suavizar(0.8, 1, estado.forma));
        renderer.render(escena, camara);
    }

    // Empieza a ir hacia la otra forma desde donde esté (también a mitad de camino)
    function alternarForma(duracion) {
        if (estado.forma === 0) {
            // Como icosaedro, giro.x da vueltas: se acerca a la inclinación del huevo (una vuelta entera no
            // se nota) para que el huevo se enderece por el camino corto
            const vuelta = 2 * Math.PI;
            const diferencia = estado.giro.x - HUEVO.inclinacion;
            estado.giro.x = HUEVO.inclinacion + ((((diferencia + Math.PI) % vuelta) + vuelta) % vuelta) - Math.PI;
        } else if (estado.forma === 1) {
            // Como huevo, giro.x no se ve: el icosaedro vuelve desde la misma inclinación
            estado.giro.x = HUEVO.inclinacion;
        }
        estado.huevoPendiente = false;
        estado.transicion = {
            desde: estado.forma,
            hasta: estado.transicion.hasta === 1 ? 0 : 1,
            inicio: estado.tiempo,
            duracion,
        };
    }

    function avanzarForma() {
        const { desde, hasta, inicio, duracion } = estado.transicion;
        const avance = (estado.tiempo - inicio) / duracion;
        const forma = avance >= 1 ? hasta : desde + (hasta - desde) * asentar(avance);
        if (forma !== estado.forma) {
            estado.forma = forma;
            transformar(forma);
        }
    }

    function cuadro(ahora) {
        const dt = Math.min((ahora - anterior) / 1000, 0.05);   // segundos (con tope si la pestaña se congeló)
        anterior = ahora;
        estado.tiempo += dt;

        if (estado.huevoPendiente && estado.tiempo >= ESPERA_HUEVO) {
            alternarForma(DURACION_HUEVO);
        }
        avanzarForma();

        estado.impulso *= Math.exp(-AMORTIGUACION * dt);
        estado.giro.y += (VELOCIDAD_BASE + estado.impulso) * dt;
        estado.giro.x += VELOCIDAD_BASE * 0.3 * dt * (1 - estado.forma);   // el huevo no se vuelca

        // Hacia el cursor: la inclinación máxima baja de la del icosaedro a ±15° a medida que se vuelve huevo
        const limiteX = INCLINACION_PUNTERO.x + (INCLINACION_PUNTERO_HUEVO - INCLINACION_PUNTERO.x) * estado.forma;
        const limiteY = INCLINACION_PUNTERO.y + (INCLINACION_PUNTERO_HUEVO - INCLINACION_PUNTERO.y) * estado.forma;
        const suavizado = 1 - Math.exp(-5 * dt);
        estado.inclinacion.x += (estado.puntero.y * limiteX - estado.inclinacion.x) * suavizado;
        estado.inclinacion.y += (estado.puntero.x * limiteY - estado.inclinacion.y) * suavizado;

        dibujar();
        idCuadro = requestAnimationFrame(cuadro);
    }

    // Anima solo si vale la pena: pestaña visible y componente en pantalla
    function actualizar() {
        const animar = visible && !document.hidden;

        if (animar && !idCuadro) {
            anterior = performance.now();
            idCuadro = requestAnimationFrame(cuadro);
            contenedor.dataset.modo = 'animado';
        } else if (!animar && idCuadro) {
            cancelAnimationFrame(idCuadro);
            idCuadro = 0;
            contenedor.dataset.modo = 'pausado';
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
        estado.puntero.x = ((evento.clientX - caja.left) / caja.width) * 2 - 1;
        estado.puntero.y = ((evento.clientY - caja.top) / caja.height) * 2 - 1;
    }

    function alSalirPuntero() {
        estado.puntero.x = 0;
        estado.puntero.y = 0;
    }

    function alPresionar(evento) {
        if (evento.pointerType === 'touch') {
            arrastre = { x: evento.clientX, t: evento.timeStamp };
        }
    }

    function alSoltar() {
        arrastre = null;
    }

    // El click mantiene el impulso de siempre y además alterna entre huevo e icosaedro
    function alHacerClick() {
        sumarImpulso(4);
        alternarForma(DURACION_ALTERNAR);
    }

    // El scroll de la página da un impulso según cuánto se movió (escucha pasiva: nunca bloquea el scroll)
    function alHacerScroll() {
        const delta = window.scrollY - ultimoScroll;
        ultimoScroll = window.scrollY;
        sumarImpulso(delta * 0.01);
    }

    // Con "reducir movimiento", el 3D se cierra y el CSS muestra el huevo estático
    function alCambiarMovimiento() {
        if (reducirMovimiento.matches) {
            liberar();
        }
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
        [window, 'pagehide', liberar],
        [document, 'visibilitychange', actualizar],
        [reducirMovimiento, 'change', alCambiarMovimiento],
    ];

    // Al salir de la página (o con "reducir movimiento"): libera geometrías, materiales y el contexto de
    // WebGL. Quita también sus propias escuchas, así que no puede correr dos veces.
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
    contenedor.dataset.webgl = '';   // el CSS oculta los SVG estáticos
    ajustarTamano();

    escuchas.forEach(([objetivo, evento, funcion]) => objetivo.addEventListener(evento, funcion, { passive: true }));
    observador.observe(contenedor);
    observadorTamano.observe(contenedor);

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
