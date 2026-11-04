<?php

namespace App\Http\Controllers;

use App\Enums\ServiceCategory;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaController extends Controller
{
    public function index(): View
    {
        $services = Service::where('is_active', true)->withCount('professionals')->orderBy('category')->orderBy('price')->get();

        return view('site.spa.index', [
            'services' => $services,
            'categories' => ServiceCategory::cases(),
        ]);
    }

    public function show(Request $request, Service $service): View
    {
        abort_unless($service->is_active, 404);
        $this->rememberForLogin($request);

        return view('site.spa.show', [
            'service' => $service,
            'professionals' => $service->professionals()->where('is_active', true)->with('user')->get(),
            'related' => Service::where('is_active', true)->where('category', $service->category)->whereKeyNot($service->id)->take(3)->get(),
        ]);
    }
}
