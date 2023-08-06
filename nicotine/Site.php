<?php
declare(strict_types=1);

namespace nicotine;

/**
| Site class.
*/
final class Site extends Dispatcher {

    /**
    | Site models.
    */
    public static array $models = [];

    /**
    | Site model.
    */
    public function model(string $model): object
    {
        if (empty(self::$models[$model])) {
            $file = __DIR__ .'/../workspace/site/models/'. $model .'.php';
            $path = realpath($file);

            if (empty($path)) {
                $quote = $this->quote();
                trigger_error('File '. $quote . $file . $quote .' not found!', E_USER_ERROR);
            }

            require_once $path;
            $class = 'workspace\\site\\models\\'.$model;
            self::$models[$model] = new $class();
            
        }
        return self::$models[$model];
    }
}
