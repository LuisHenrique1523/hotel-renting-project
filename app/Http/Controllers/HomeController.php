<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\SpaceCategory;
use App\Models\Unit;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $units = Unit::active()->withCount(['spaces' => fn ($q) => $q->bookable()])->orderBy('city')->get();

        return view('site.home', [
            'units' => $units,
            'cities' => $units->pluck('city')->unique()->values(),
            'categories' => SpaceCategory::active()->ordered()
                ->withCount(['spaces' => fn ($q) => $q->bookable()])
                ->withMin(['spaces' => fn ($q) => $q->bookable()], 'price')
                ->get(),
            'featured' => Space::bookable()->with('unit', 'category')->where('is_featured', true)->orderBy('price')->take(4)->get(),
            'stats' => [
                'units' => $units->count(),
                'cities' => $units->pluck('city')->unique()->count(),
                'spaces' => Space::bookable()->count(),
            ],
        ]);
    }
}
