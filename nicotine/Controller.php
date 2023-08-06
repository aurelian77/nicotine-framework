<?php
declare(strict_types=1);

namespace nicotine;

/**
| Controller class.
*/
class Controller {

    /**
    | Proxy object.
    */
    public object $proxy;

    /**
    | Database object.
    */
    public object $db;

    /**
    | Class constructor.
    */
    public function __construct()
    {
        $this->proxy = Registry::get('Proxy');
        $this->db = Registry::get('Database');
    }

}
