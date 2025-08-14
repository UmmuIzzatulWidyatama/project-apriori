<?php

namespace App\Controllers\Api;
use App\Controllers\BaseController;

class HomeController extends BaseController
{
    public function index()
    {
        return view('halaman_utama');
    }
}
