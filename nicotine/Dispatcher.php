<?php
declare(strict_types=1);

namespace nicotine;

/**
| Dispatcher class for every request type.
*/
class Dispatcher {

    /**
    | Class constructor.
    */
    public function __construct()
    {
        //
    }

    /**
    | Match optional project folder prefix. E.g. http://localhost:80/my-project => "my-project"
    */
    public function getInstallationFolder(): string
    {
        preg_match('/^http[s]?\:\/\/[^\/]+[\/]{1}([^\/\.\?]+)$/i', Registry::get('config')->baseHref, $matches);

        if (!empty($matches[1]) && !preg_match('/^\/'. preg_quote($matches[1], '/') .'/i', $_SERVER['REQUEST_URI'])) {
            trigger_error(
                'Missing installation directory as URI prefix! Please check '. $this->quote() .'baseHref'. $this->quote() .' configuration directive!',
                E_USER_ERROR
            );
        }

        return $matches[1] ?? '';
    }

    /**
    | Get matched route, e.g. '' (site), 'admin', 'api'.
    */
    public function matchUriPrefix(): string
    {
        preg_match('/^\/'. preg_quote($this->getInstallationFolder(), '/') .'[\/]?([^\/\.\?]+)/i', $_SERVER['REQUEST_URI'], $matches);
        return (isset($matches[1]) && in_array($matches[1], ['admin', 'api'])) ? $matches[1] : ''; /* including site ('') */
    }

    /**
    | It is a site (public) request?
    */
    public function isSiteRequest(): bool
    {
        if (!$this->isCliRequest() && $this->matchUriPrefix() == '') {
            return true;
        }
        return false;
    }

    /**
    | It is an Admin request?
    */
    public function isAdminRequest(): bool
    {
        if (!$this->isCliRequest() && $this->matchUriPrefix() == 'admin') {
            return true;
        }
        return false;
    }

    /**
    | It is an AJAX request?
    */
    public function isAjaxRequest(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
    | It is a CLI request?
    */
    public function isCliRequest(): bool
    {
        return (empty($_SERVER['REMOTE_ADDR']) && empty($_SERVER['HTTP_USER_AGENT']) && empty($_SERVER['REQUEST_METHOD']) && defined('STDIN'));
    }

    /**
    | It is an API request?
    */
    public function isApiRequest(): bool
    {
        if (!$this->isCliRequest() && $this->matchUriPrefix() == 'api') {
            return true;
        }
        return false;
    }

    /**
    | Get quote character, based on request type.
    */
    public function quote(): string
    {
        if ($this->isAjaxRequest() || $this->isCliRequest()) {
            return '"';
        }
        return '&quot;';
    }

    /**
    | Possible values, e.g.:
    |   [installFolder]/api/foo-script[.html]
    |
    |   [installFolder][admin|'']/Homepage[.html]
    |   [installFolder][admin|'']/Homepage/index[.html]
    |
    |   [installFolder][admin|'']/Homepage/index/1234/seo-param[.html][?countries[]=5678]
    */
    public function parseHttpRequest(): void
    {
        $prefix = $this->getInstallationFolder();
        $subPrefix = $this->matchUriPrefix();
        $suffix = Registry::get('config')->urlSuffix;

        $controller = '';
        $action = '';
        $args = [];
        
        $requestUri = $_SERVER['REQUEST_URI'];

        if (str_contains($requestUri, '..')) {
            trigger_error(
                'As a security measure, the characters '. $this->quote() .'..'. $this->quote() .' are not allowed for maped requests, '
                .'because users can navigate up into directories! Please rename '. $this->quote(). $requestUri . $this->quote() .' to something else!',
                E_USER_ERROR
            );
        }

        if (str_starts_with($_SERVER['REQUEST_URI'], '/admin/static/'))
        {
            if (empty(get_roles())) {
                trigger_error('You don\'t have any role in order to access this resource!', E_USER_ERROR);
            }

            $protectedFile = __DIR__ .'/../workspace'. $_SERVER['REQUEST_URI'];
            $protectedFilePath = realpath($protectedFile);

            if (!empty($protectedFilePath))
            {
                $ContentTypes = Registry::get('ContentTypes')->extensions;
                $pathInfoExtension = pathinfo($protectedFilePath)['extension'];

                if (!empty($pathInfoExtension) && array_key_exists($pathInfoExtension, $ContentTypes))
                {
                    $header = $ContentTypes[$pathInfoExtension];
                    header("Content-Type: {$header}");
                }

                print file_get_contents($protectedFilePath);
                exit;
            } else {
                trigger_error('File '. $this->quote() .$protectedFile. $this->quote().' not found!');
            }
        }

        // Destroy prefixes.
        $requestUri = preg_replace('/^[\/]{1}/', '', $requestUri);
        $requestUri = preg_replace('/^'. preg_quote($prefix, '/') .'[\/]?/i', '', $requestUri);
        $requestUri = preg_replace('/^'. $subPrefix .'[\/]?/i', '', $requestUri);

        $suffixPattern= '/\.'. preg_quote($suffix, '/') .'[\?]?[^\/]*$/i';
        $hasQuery = str_starts_with($requestUri, '?');

        if (!empty($suffix) && !empty($requestUri) && !preg_match($suffixPattern, $requestUri) && !$hasQuery) {
            trigger_error('Missing URL suffix! Please check '. $this->quote() .'urlSuffix'. $this->quote() .' configuration directive!', E_USER_ERROR);
        }

        // Destroy suffix.
        $requestUri = preg_replace($suffixPattern, '', $requestUri);

        if ($hasQuery) {
            $requestUri = '';
        }

        // API request.
        if ($this->isApiRequest())
        {
            if (empty($requestUri)) {
                trigger_error('Listing API directory is not allowed!', E_USER_ERROR);
            }

            $relativePath = 'workspace/api/'. $requestUri .'.php';
            $fullPath = realpath(__DIR__ .'/../'. $relativePath);
            
            if (empty($fullPath)) {
                trigger_error('API file '. $this->quote() . $relativePath . $this->quote() .' not found!', E_USER_ERROR);
            }

            require $fullPath;
        }

        // Site, Admin request.
        if ($this->isSiteRequest() || $this->isAdminRequest())
        {
            $requestUri = explode('/', $requestUri);

            foreach ($requestUri as $segment) {
                $segment = trim($segment);
                
                if (!empty($segment)) 
                {
                    if (empty($controller)) {
                        $controller = str_replace(' ', '', ucwords(str_replace('-', ' ', $segment)));
                        $segment = '';
                    }
                    if (empty($action)) {
                        $action = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $segment))));
                        $segment = '';
                    }
                    if (!empty($segment)) {
                        $args[] = $segment;
                    }
                }
            }

            if (empty($controller) && empty($action)) {
                switch ($subPrefix) {
                    // Site request.
                     case '':
                        $controller = Registry::get('config')->homeController;
                        $action = Registry::get('config')->homeAction;
                    break;
                    
                    // Admin request.
                    case 'admin':
                        $controller = Registry::get('config')->loginController;
                        $action = Registry::get('config')->loginAction;
                    break;
                }
            }

            if (!empty($controller) && empty($action)) {
               $action = 'index';
            }

            $relativePath = 'workspace/'. ($subPrefix == '' ? 'site' : 'admin') .'/controllers/'. $controller .'.php';
            $fullPath = realpath(__DIR__ .'/../'. $relativePath);
            
            if (empty($fullPath)) {
                trigger_error(($subPrefix == '' ? 'Site' : 'Admin') .' controller '. $this->quote() . $relativePath . $this->quote().' not found!', E_USER_ERROR);
            }

            require $fullPath;

            $controllerNamespace = '\\workspace\\'. ($subPrefix == '' ? 'site' : 'admin') .'\\controllers\\'. $controller;

            $object = new $controllerNamespace();
            
            if (!method_exists($object, $action)) {
                trigger_error(
                    'Action '. $this->quote() . $action . $this->quote() .' not found for '.
                    ($subPrefix == '' ? 'Site' : 'Admin') .' controller '. $this->quote() . $controller . $this->quote(),
                    E_USER_ERROR
                );
            }
            
            $reflexion = new \ReflectionMethod($object, $action);
            $minArgsNumber = $reflexion->getNumberOfRequiredParameters();

            $sizeOfArgs = sizeof($args);

            if ($minArgsNumber > $sizeOfArgs) {
                trigger_error(
                    'Too few arguments for method '. $this->quote() . $action . $this->quote() .' of '.
                    ($subPrefix == '' ? 'Site' : 'Admin') .' controller '. $this->quote() . $controller . $this->quote().
                    '! Expecting at least '. $minArgsNumber .'! '. $sizeOfArgs .' provided!',
                    E_USER_ERROR
                );
            }

            $sessionRoles = get_roles();

            $attributes = (new \ReflectionMethod($object, $action))->getAttributes();

            foreach ($attributes as $attribute)
            {
                if ($attribute->getName() == 'nicotine\\RequestMethod') 
                {
                    $params = $attribute->getArguments();

                    if (is_array($params) && isset($params[0]) && is_string($params[0])) 
                    {
                        switch (strtolower($params[0])) 
                        {
                            case 'get':
                                if (strtolower($_SERVER['REQUEST_METHOD']) != 'get') {
                                    trigger_error('Request method does not match action signature!', E_USER_ERROR);
                                }
                            break;
                            
                            case 'post':
                                if (strtolower($_SERVER['REQUEST_METHOD']) != 'post') {
                                    trigger_error('Request method does not match action signature!', E_USER_ERROR);
                                }
                            break;
                        }
                    }
                }

                if ($attribute->getName() == 'nicotine\\AdminRoles') 
                {
                    $params = $attribute->getArguments();

                    if (is_array($params) && !empty($params) && !in_array('super_admin', $sessionRoles))
                    {
                        $intersect = array_intersect($params, $sessionRoles);
                        
                        if (empty($intersect)) {
                            trigger_error('You don\'t have permission to access this resource!', E_USER_ERROR);
                        }
                    }
                }
            }

            call_user_func_array([$object, $action], $args);
        }
    }

    /**
    | It is cron Cli request?
    | @return string|bool cron name or false on failure
    */
    public function isCronRequest(): string|bool
    {
        if ($this->isCliRequest() && isset($_SERVER['argv'][1]) && preg_match('/^cron\:(.+)$/i', $_SERVER['argv'][1], $matches)) {
            return $matches[1] ?? false;
        }
        return false;
    }

    /**
    | It is script Cli request?
    | @return string|bool script name or false on failure
    */
    public function isScriptRequest(): string|bool
    {
        if ($this->isCliRequest() && isset($_SERVER['argv'][1]) && preg_match('/^script\:(.+)$/i', $_SERVER['argv'][1], $matches)) {
            return $matches[1] ?? false;
        }
        return false;
    }

    /**
    | Parse Cli request (cron / script).
    */
    public function parseCliRequest(): void
    {
        $script = $this->isScriptRequest();
        $cron = $this->isCronRequest();

        if ($script == false && $cron == false) {
            trigger_error('Invalid Cli command. Please check documentation!', E_USER_ERROR);
        }

        if ($script != false) {
            $file = __DIR__ . '/../workspace/cli/scripts/'. $script .'.php';
            $path = realpath($file);

            if (!empty($path)) {
                require $file;
            } else {
                trigger_error('Script "'. $file .'" not found!', E_USER_ERROR);
            }
        }

        if ($cron != false) {
            $file = __DIR__ . '/../workspace/cli/crons/'. $cron .'.php';
            $path = realpath($file);

            if (!empty($path)) {
                require $file;
            } else {
                trigger_error('Cron "'. $file .'" not found!', E_USER_ERROR);
            }
        }
    }

}
