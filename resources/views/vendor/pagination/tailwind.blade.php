{{--
    Paginación del proyecto. Reemplaza la vista 'pagination::tailwind' de Laravel
    (por estar aquí, Tailwind la escanea; la de vendor/ quedaría sin estilos).
    Se usa sola con: {{ $productos->links() }}
--}}
@if ($paginator->hasPages())
    @php
        $circulo = 'inline-flex size-10 items-center justify-center rounded-full font-mono text-xs transition';
    @endphp

    <nav role="navigation" aria-label="Paginación" class="flex items-center justify-between gap-4 py-6">
        @if ($paginator->onFirstPage())
            <span class="{{ $circulo }} text-tinta/30" aria-disabled="true">
                <x-icono nombre="flecha-izquierda" class="size-4" />
                <span class="sr-only">Página anterior</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $circulo }} bg-superficie hover:bg-tinta hover:text-superficie">
                <x-icono nombre="flecha-izquierda" class="size-4" />
                <span class="sr-only">Página anterior</span>
            </a>
        @endif

        {{-- En móvil solo "Página X de Y"; desde sm se ven los números --}}
        <p class="font-mono text-xs text-tenue sm:hidden">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
        </p>

        <ul class="hidden items-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="{{ $circulo }} text-tenue">{{ $element }}</li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $circulo }} bg-tinta text-superficie">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $circulo }} hover:bg-superficie" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $circulo }} bg-superficie hover:bg-tinta hover:text-superficie">
                <x-icono nombre="flecha-derecha" class="size-4" />
                <span class="sr-only">Página siguiente</span>
            </a>
        @else
            <span class="{{ $circulo }} text-tinta/30" aria-disabled="true">
                <x-icono nombre="flecha-derecha" class="size-4" />
                <span class="sr-only">Página siguiente</span>
            </span>
        @endif
    </nav>
@endif
