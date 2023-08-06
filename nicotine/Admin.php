<?php
declare(strict_types=1);

namespace nicotine;

/**
| Admin class.
*/
final class Admin extends Dispatcher {

    /**
    | Admin stack models.
    */
    public static array $models = [];

    /**
    | Get an Admin model.
    */
    public function model(string $model): object
    {
        if (empty(self::$models[$model])) {
            $file = __DIR__ .'/../workspace/admin/models/'. $model .'.php';
            $path = realpath($file);

            if (empty($path)) {
                $quote = $this->quote();
                trigger_error('File '. $quote . $file . $quote .' not found!', E_USER_ERROR);
            }

            require_once $path;
            $class = 'workspace\\admin\\models\\'.$model;
            self::$models[$model] = new $class();
            
        }
        return self::$models[$model];
    }
}
