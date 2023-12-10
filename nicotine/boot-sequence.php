<?php
declare(strict_types=1);

namespace nicotine;

require realpath(__DIR__.'/Registry.php');

require realpath(__DIR__.'/../config.php');

require realpath(__DIR__.'/../workspace/db/map.php');

require realpath(__DIR__.'/Dispatcher.php');

require realpath(__DIR__.'/ContentTypes.php');
Registry::set('ContentTypes', new ContentTypes());

require realpath(__DIR__.'/Error.php');
Registry::set('Error', new Error());

require realpath(__DIR__.'/Database.php');
Registry::set('Database', new Database());

require realpath(__DIR__.'/Kernel.php');
Registry::set('Kernel', new Kernel());

require realpath(__DIR__.'/Proxy.php');
Registry::set('Proxy', new Proxy());

require realpath(__DIR__.'/Utils.php');
Registry::set('Utils', new Utils());

require realpath(__DIR__.'/helpers.php');

$dispatcher = new Dispatcher();

if ($dispatcher->isCliRequest()) {
    $dispatcher->parseCliRequest();
} else {
    $dispatcher->parseHttpRequest();
}

Registry::set('Dispatcher', $dispatcher);

Registry::get('Proxy')->session([
    'user_request' => [],
    'custom_errors' => [],
    'messages_type' => null,
]);
