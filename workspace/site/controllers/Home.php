<?php
declare(strict_types=1);

namespace workspace\site\controllers;

use nicotine\Controller;
use nicotine\RequestMethod;
use nicotine\Registry;

class Home extends Controller 
{
    public function index()
    {
        dd($this->proxy->server());
    }
}
