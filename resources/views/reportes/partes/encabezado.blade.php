{{--
    Encabezado común de los reportes: título, descripción, cuándo se generó y el botón Imprimir.
    El botón no sale en la hoja impresa (print:hidden); ver también @media print en resources/css/input.css.

    @include('reportes.partes.encabezado', ['titulo' => 'Stock bajo', 'subtitulo' => 'Productos activos bajo su mínimo'])
--}}
<x-encabezado :titulo="$titulo" :subtitulo="$subtitulo">
    {{-- data-imprimir: public/js/app.js abre el diálogo de impresión del navegador (window.print) --}}
    <x-boton tipo="button" variante="secundario" tamano="chico" data-imprimir class="print:hidden">
        <x-icono nombre="imprimir" class="size-4" />
        Imprimir
    </x-boton>
</x-encabezado>

<p class="-mt-5 pb-6 text-center font-mono text-micro font-medium text-tenue">
    Generado el {{ now()->translatedFormat('j \d\e F \d\e Y, H:i') }}
</p>
