<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = Categoria::all();
        return view('categorias.index', compact('categorias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('categorias.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $nombreValidacion = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre'
        ]);
        Categoria::create($nombreValidacion);
        return redirect('/categorias')->with('success', 'Categoría creada');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Categoria $categoria)
    {
        return view(categorias.edit)
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categoria $categoria)
    {
        $nombreValidacion = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre,' . $categoria->id
        ]);
        Categoria::create($nombreValidacion);
        return redirect('/categorias')->with('success', 'Categoría creada');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categoria $categoria)
    {
        //
    }
}
