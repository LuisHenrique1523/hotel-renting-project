<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\Space;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home', [
            'featuredRooms' => RoomType::where('is_featured', true)->orderBy('base_price')->take(3)->get(),
            'services' => Service::where('is_active', true)->orderBy('price')->take(5)->get(),
            'spaces' => Space::where('is_active', true)->orderBy('capacity')->get(),
            'stats' => [
                'rooms' => Room::count(),
                'professionals' => Professional::where('is_active', true)->count(),
                'spaces' => Space::count(),
            ],
        ]);
    }
}
