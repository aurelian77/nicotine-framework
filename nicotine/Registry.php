<?php
declare(strict_types=1);

namespace nicotine;

/**
  Hold all instances, configuration directives and so on.
*/
final class Registry {

    /**
      Instances, configuration directives and so on.
      @var array<string, mixed>
    */
    public static array $data = [];

    /**
      Setter for {self::$data}, once.
    */
    public static function set(
        string $propertyName, 
        
        #[\SensitiveParameter]
        #[\SensitiveParameterValue]
        mixed $propertyValue
    ): void {
        // Set once.
        if (!isset(self::$data[$propertyName])) {
            self::$data[$propertyName] = $propertyValue;
        }
    }

    /**
      Getter for {self::$data}
    */
    public static function get(string $propertyName): mixed
    {
        return self::$data[$propertyName] ?? null;
    }

}
