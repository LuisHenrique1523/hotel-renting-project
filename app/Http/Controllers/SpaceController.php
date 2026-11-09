<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\SpaceCategory;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaceController extends Controller
{
    /** Busca de espaços com filtros por cidade, unidade, categoria e número de pessoas. */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cidade' => ['nullable', 'string', 'max:80'],
            'unidade' => ['nullable', 'string', 'max:120'],
            'categoria' => ['nullable', 'string', 'max:120'],
            'pessoas' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $spaces = Space::bookable()
            ->with('unit', 'category')
            ->when($filters['cidade'] ?? null, fn ($q, $city) => $q->whereHas('unit', fn ($u) => $u->where('city', $city)))
            ->when($filters['unidade'] ?? null, fn ($q, $slug) => $q->whereHas('unit', fn ($u) => $u->where('slug', $slug)))
            ->when($filters['categoria'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($filters['pessoas'] ?? null, fn ($q, $n) => $q->where('capacity', '>=', $n))
            ->orderByDesc('is_featured')
            ->orderBy('price')
            ->get();

        return view('site.spaces.index', [
            'spaces' => $spaces,
            'filters' => $filters,
            'cities' => Unit::active()->orderBy('city')->distinct()->pluck('city'),
            'units' => Unit::active()->orderBy('name')->get(['name', 'slug', 'city']),
            'categories' => SpaceCategory::active()->ordered()->get(),
        ]);
    }

    public function show(Request $request, Space $space): View
    {
        $space->load('unit', 'category');
        abort_unless($space->isBookable(), 404);
        $this->rememberForLogin($request);

        return view('site.spaces.show', [
            'space' => $space,
            'others' => Space::bookable()->with('unit', 'category')
                ->where('unit_id', $space->unit_id)
                ->whereKeyNot($space->id)
                ->inRandomOrder()
                ->take(3)
                ->get(),
        ]);
    }
}
