<?php

namespace App\Http\Controllers;
use illuminate\Http\Request;
use Inertia\Inertia;

class AboutController extends Controller
{
    public function index()
    {
        return Inertia::render('about/index');
    }
}
