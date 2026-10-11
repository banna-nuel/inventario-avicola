@extends('layouts.app')
@section('title', 'Movimientos')
@section('volver', route('home'))
@use('App\Enums\UnidadMedida')
@section('content')
    @php
        $total = $movimientos->total();
    @endphp

    <x-encabezado titulo="Movimientos" :subtitulo="$total . ' ' . ($total === 1 ? 'movimiento' : 'movimientos')">
        <x-boton :href="route('movimientos.create')" tamano="icono" aria-label="Nuevo movimiento" title="Nuevo movimiento" data-abre-hoja>
            <x-icono nombre="mas" class="size-5" />
        </x-boton>
    </x-encabezado>

    @if ($movimientos->isEmpty())
        <x-estado-vacio titulo="Aún no hay movimientos"
            mensaje="Registra una entrada o una salida; aquí verás el historial, del más reciente al más antiguo.">
            <x-boton :href="route('movimientos.create')" data-abre-hoja>Nuevo movimiento</x-boton>
        </x-estado-vacio>
    @else
        <x-titulo-seccion titulo="Historial" :contador="$total" class="pb-3.5" />

        <x-tabla>
            <x-slot:encabezado>
                <tr>
                    <th>Producto</th>
                    {{-- En móvil, tipo, fecha y usuario van bajo el nombre --}}
                    <th class="hidden sm:table-cell">Tipo</th>
                    <th class="hidden md:table-cell">Fecha</th>
                    <th class="hidden lg:table-cell">Registró</th>
                    <th class="text-right">Cantidad</th>
                </tr>
            </x-slot:encabezado>

            @foreach ($movimientos as $movimiento)
                @php
                    $entrada = $movimiento->tipo === 'entrada';
                    $unidad = $movimiento->producto->unidad_medida;
                    $fecha = $movimiento->fecha->translatedFormat('j M Y · H:i');
                @endphp
                <tr>
                    <td class="w-full">
                        <span class="block font-display text-base [overflow-wrap:anywhere]">{{ $movimiento->producto->nombre }}</span>
                        <span class="block font-mono text-micro font-medium text-tenue sm:hidden">
                            {{ $entrada ? 'Entrada' : 'Salida' }} · {{ $fecha }} · {{ $movimiento->usuario->name }}
                        </span>
                        <span class="hidden font-mono text-micro font-medium text-tenue sm:block md:hidden">{{ $fecha }}</span>
                    </td>
                    <td class="hidden sm:table-cell">
                        <x-badge :variante="$entrada ? 'neutro' : 'oscuro'">{{ $entrada ? 'Entrada' : 'Salida' }}</x-badge>
                    </td>
                    <td class="hidden font-mono text-xs whitespace-nowrap md:table-cell">{{ $fecha }}</td>
                    <td class="hidden font-mono text-xs whitespace-nowrap lg:table-cell">{{ $movimiento->usuario->name }}</td>
                    <td class="text-right whitespace-nowrap">
                        {{-- "+" suma al stock (entrada), "−" resta (salida) --}}
                        <span class="block font-display text-base">
                            <span aria-hidden="true">{{ $entrada ? '+' : '−' }}</span>{{ UnidadMedida::numero($movimiento->cantidad) }}
                        </span>
                        <span class="block font-mono text-micro font-medium text-tenue">{{ $unidad->nombrePara($movimiento->cantidad) }}</span>
                    </td>
                </tr>
            @endforeach
        </x-tabla>

        <div class="pt-6">
            {{ $movimientos->links() }}
        </div>
    @endif
@endsection
