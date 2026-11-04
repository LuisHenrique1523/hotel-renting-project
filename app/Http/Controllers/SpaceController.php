<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaceController extends Controller
{
    public function index(): View
    {
        return view('site.spaces.index', [
            'spaces' => Space::where('is_active', true)->orderBy('capacity')->get(),
        ]);
    }

    public function show(Request $request, Space $space): View
    {
        abort_unless($space->is_active, 404);
        $this->rememberForLogin($request);

        return view('site.spaces.show', [
            'space' => $space,
            'others' => Space::where('is_active', true)->whereKeyNot($space->id)->get(),
        ]);
    }
}
