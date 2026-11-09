<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\Unit;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::active()
            ->withCount(['spaces' => fn ($q) => $q->bookable()])
            ->withMin(['spaces' => fn ($q) => $q->bookable()], 'capacity')
            ->orderBy('city')
            ->orderBy('name')
            ->get();

        return view('site.units.index', [
            'cities' => $units->groupBy('city'),
            'total' => $units->count(),
        ]);
    }

    public function show(Unit $unit): View
    {
        abort_unless($unit->is_active, 404);

        $spaces = Space::bookable()
            ->with('category')
            ->where('unit_id', $unit->id)
            ->orderBy('price')
            ->get();

        return view('site.units.show', [
            'unit' => $unit,
            // Espaços agrupados pela categoria, na ordem definida no painel
            'groups' => $spaces->groupBy('space_category_id')
                ->sortBy(fn ($items) => [$items->first()->category->sort_order, $items->first()->category->name]),
            'others' => Unit::active()->whereKeyNot($unit->id)->orderBy('city')->take(3)->get(),
        ]);
    }
}
