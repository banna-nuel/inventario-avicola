{{--
    Tabla dentro de una tarjeta. En pantallas angostas se desplaza horizontalmente
    dentro de la tarjeta (la página nunca hace scroll lateral).
    Los estilos de th/td se aplican desde aquí, así las vistas solo escriben HTML simple.

    <x-tabla>
        <x-slot:encabezado>
            <tr><th>Nombre</th><th class="text-right">Stock</th></tr>
        </x-slot:encabezado>
        <tr><td>Maíz</td><td class="text-right">120 kg</td></tr>
    </x-tabla>
--}}
@props(['encabezado' => null])

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-tarjeta bg-superficie']) }}>
    <table class="w-full border-collapse text-left text-sm
        [&_th]:px-4 [&_th]:py-3 [&_th]:font-mono [&_th]:text-xs [&_th]:font-normal [&_th]:whitespace-nowrap [&_th]:text-tenue
        [&_td]:px-4 [&_td]:py-3.5 [&_td]:align-middle
        [&_tbody_tr]:border-t [&_tbody_tr]:border-borde [&_tbody_tr]:transition-colors [&_tbody_tr:hover]:bg-fondo/60">
        @if ($encabezado)
            <thead>{{ $encabezado }}</thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
