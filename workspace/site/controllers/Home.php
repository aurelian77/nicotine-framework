<?php
declare(strict_types=1);

namespace workspace\site\controllers;

use nicotine\Controller;
use nicotine\RequestMethod;

class Home extends Controller 
{
    public function index()
    {
        dd($_SERVER);
    }
}
