@extends('layouts.app')
@section('title', 'Stock por categoría')
@section('volver', route('reportes.index'))
@use('App\Enums\UnidadMedida')
@section('content')
    @include('reportes.partes.encabezado', ['titulo' => 'Stock por categoría', 'subtitulo' => 'Productos activos con su stock actual'])

    <p class="mx-auto max-w-xl pb-8 text-center font-mono text-xs text-balance text-tenue">
        Cada producto se muestra en su propia unidad. Por categoría solo se cuentan los productos:
        no se suman cantidades de unidades distintas.
    </p>

    @if ($categorias->isEmpty())
        <x-estado-vacio titulo="Aún no hay categorías" mensaje="Cuando existan categorías con productos, aquí verás su stock." />
    @else
        <div class="flex flex-col gap-10">
            @foreach ($categorias as $categoria)
                @php
                    $cantidad = $categoria->productos->count();
                @endphp
                <section aria-labelledby="categoria-{{ $categoria->id }}" class="flex flex-col gap-3.5">
                    {{-- Solo el conteo de productos: nunca un total de cantidades --}}
                    <div class="flex items-baseline gap-2">
                        <h2 id="categoria-{{ $categoria->id }}" class="font-display text-seccion font-normal">{{ $categoria->nombre }}</h2>
                        <span class="font-mono text-xs text-tenue">{{ $cantidad }} {{ $cantidad === 1 ? 'producto' : 'productos' }}</span>
                    </div>

                    @if ($cantidad === 0)
                        <p class="rounded-tarjeta border border-dashed border-borde-fuerte px-4 py-4 font-mono text-xs text-tenue">Sin productos activos.</p>
                    @else
                        <x-tabla>
                            <x-slot:encabezado>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-right">Stock</th>
                                    <th class="hidden text-right sm:table-cell">Mínimo</th>
                                </tr>
                            </x-slot:encabezado>

                            @foreach ($categoria->productos as $producto)
                                <tr>
                                    <td class="w-full">
                                        <a href="{{ route('movimientos.index', ['producto' => $producto->id]) }}"
                                            class="block font-display text-base underline-offset-4 [overflow-wrap:anywhere] hover:underline">{{ $producto->nombre }}</a>
                                        <span class="block font-mono text-micro font-medium text-tenue sm:hidden">mín. {{ UnidadMedida::numero($producto->stock_minimo) }}</span>
                                        @if ($producto->enStockBajo())
                                            <x-badge variante="alerta" class="mt-1.5">Stock bajo</x-badge>
                                        @endif
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <span class="block font-display text-base">{{ UnidadMedida::numero($producto->stock_actual) }}</span>
                                        <span class="block font-mono text-micro font-medium text-tenue">{{ $producto->unidad_medida->nombrePara($producto->stock_actual) }}</span>
                                    </td>
                                    <td class="hidden text-right font-mono text-xs whitespace-nowrap sm:table-cell">{{ UnidadMedida::numero($producto->stock_minimo) }}</td>
                                </tr>
                            @endforeach
                        </x-tabla>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
@endsection
