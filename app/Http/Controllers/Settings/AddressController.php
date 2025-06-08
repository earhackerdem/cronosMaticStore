<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    /**
     * Display the address management page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/addresses');
    }
}
