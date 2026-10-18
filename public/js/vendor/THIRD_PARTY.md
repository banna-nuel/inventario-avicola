# Código de terceros (autoalojado)

Archivos de librerías externas que la app sirve desde su propio dominio (sin CDN ni npm).
Para actualizarlos, ver la sección "Actualizar Three.js" del README del proyecto.

## Three.js r186

- **Uso:** icosaedro 3D de Inicio y del login (`public/js/icosaedro.js`, RNF-08).
- **Versión:** r186 (`REVISION = '186'`), publicada el 2026-09-24.
- **Origen:** repositorio oficial https://github.com/mrdoob/three.js, tag `r186`, carpeta `build/`.
  - https://raw.githubusercontent.com/mrdoob/three.js/r186/build/three.module.js
  - https://raw.githubusercontent.com/mrdoob/three.js/r186/build/three.core.js
- **Descargado:** 2026-10-11. Sin modificar: el hash de blob de git de cada archivo coincide
  con el del repositorio en ese tag.
- **Ubicación:** `public/js/vendor/three-r186/`. La carpeta lleva la versión porque el servidor web
  le pide al navegador que guarde `/js/vendor/` un año: con otra versión cambia la ruta, y ningún
  navegador se queda con la copia vieja (ni con una mezcla de las dos).
- `three.module.js` importa `./three.core.js`: los dos archivos tienen que estar en la misma carpeta.

| Archivo | Bytes | SHA-256 |
|---|---|---|
| `three-r186/three.module.js` | 662772 | `9052042d676cb0fdc1ddfefe193053f34b7ac0513a616fdac4535d49987812ea` |
| `three-r186/three.core.js` | 1458113 | `9edde002b066a9a05676a6127f67735b62baf399bdea529f2f7e31657da769e6` |

### Licencia (copia del archivo LICENSE del repositorio en el tag r186)

```
The MIT License

Copyright © 2010-2026 three.js authors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
```
