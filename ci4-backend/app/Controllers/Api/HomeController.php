<?php

namespace App\Controllers\Api;
use App\Controllers\BaseController;

class HomeController extends BaseController
{
    public function homeView()
    {
        return view('halaman_utama');
    }
}
