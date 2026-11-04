<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** Visitante não logado: após o login, volta para esta mesma página. */
    protected function rememberForLogin(Request $request): void
    {
        if (! $request->user()) {
            session(['url.intended' => $request->fullUrl()]);
        }
    }
}
