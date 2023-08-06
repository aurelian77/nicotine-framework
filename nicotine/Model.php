<?php
declare(strict_types=1);

namespace nicotine;

/**
| Generic model.
*/
class Model {

    /**
    | Database object.
    */
    public object $db;

    /**
    | Proxy object;
    */
    public object $proxy;

    /**
    | Class constructor.
    */
    public function __construct() 
    {
        $this->db = Registry::get('Database');
        $this->proxy = Registry::get('Proxy');
    }

}
