<?php
declare(strict_types=1);

namespace nicotine;

/**
| Proxy class.
*/
final class Proxy extends Dispatcher {

    /**
    | Site object.
    */
    public object $site;

    /**
    | Admin object.
    */
    public object $admin;

    /**
    | Current user defined layout.
    */
    public string $layout;

    /**
    | Content for {$this->layout}
    */
    public string $contentForLayout;

    /**
    | Front vars.
    */
    public object $vars;

    /**
    | Class constructor.
    */
    public function __construct()
    {
        require_once realpath(__DIR__ . '/Site.php');

        if (is_null(Registry::get('Site'))) {
            Registry::set('Site', new Site());
        }

        $this->site = Registry::get('Site');
   
        require_once realpath(__DIR__ . '/Admin.php');

        if (is_null(Registry::get('Admin'))) {
            Registry::set('Admin', new Admin());
        }

        $this->admin = Registry::get('Admin');

        $this->vars = (object) [];
    }

    /**
    | Redirect back.
    | @param string $messageType = error (default) | warning | success
    */
    public function back(array $errors = [], $messageType = 'error'): never
    {
        $this->session([
            'custom_errors' => $errors,
            'messages_type' => $messageType,
            'user_request' => $_REQUEST
        ]);

        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: {$_SERVER['HTTP_REFERER']}");
        }

        exit;
    }

    /**
    | $_GET
    */
    public function get(string $key = null, mixed $default = null): mixed
    {
        switch (func_num_args()) {
            case 0:
                return $_GET ?? [];
            break;

            case 1:
                return $_GET[$key] ?? null;
            break;

            case 2:
                return (!empty($key) && !empty($_GET[$key])) ? $_GET[$key] : $default;
            break;
        }
    }

    /**
    | $_POST
    */
    public function post(string $key = null, mixed $default = null): mixed
    {
        switch (func_num_args()) {
            case 0:
                return $_POST ?? [];
            break;

            case 1:
                return $_POST[$key] ?? null;
            break;

            case 2:
                return (!empty($key) && !empty($_POST[$key])) ? $_POST[$key] : $default;
            break;
        }
    }

    /**
    | $_REQUEST
    */
    public function request(string $key = null, mixed $default = null): mixed
    {
        switch (func_num_args()) {
            case 0:
                return $_REQUEST ?? [];
            break;

            case 1:
                return $_REQUEST[$key] ?? null;
            break;

            case 2:
                return (!empty($key) && !empty($_REQUEST[$key])) ? $_REQUEST[$key] : $default;
            break;
        }
    }

    /**
    | $_COOKIE
    */
    public function cookie(string $key = null, mixed $default = null): mixed
    {
        switch (func_num_args()) {
            case 0:
                return $_COOKIE ?? [];
            break;

            case 1:
                return $_COOKIE[$key] ?? null;
            break;

            case 2:
                return (!empty($key) && !empty($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
            break;
        }
    }

    /**
    | $_SERVER
    */
    public function server(string $key = null, mixed $default = null): mixed
    {
        switch (func_num_args()) {
            case 0:
                return $_SERVER;
            break;

            case 1:
                return $_SERVER[$key] ?? null;
            break;

            case 2:
                return (!empty($key) && !empty($_SERVER[$key])) ? $_SERVER[$key] : $default;
            break;
        }
    }

    /**
    | $_SESSION
    */
    public function session($param = null, $default = null)
    {
        switch (func_num_args()) {
            case 0:
                return $_SESSION ?? [];
            break;

            case 1:
                // Is set.
                if (is_array($param)) {
                    foreach ($param as $key => $value) {
                        $_SESSION[$key] = $value;
                    }

                // Is get.
                } else {
                    return $_SESSION[$param] ?? null;
                }
            break;

            case 2:
                return (!empty($param) && !empty($_SESSION[$param])) ? $_SESSION[$param] : $default;
            break;
        }
    }

    /**
    | View
    */
    public function view(string $viewName, array $viewArguments = []): void
    {
        if (empty($this->layout)) {
            trigger_error('Layout name cannot be empty!', E_USER_ERROR);
        }

        $relativeLayoutPath = '';

        if ($this->isSiteRequest()) {
            $relativeLayoutPath = 'workspace/site/layouts/'. $this->layout .'.php';
        }
        elseif ($this->isAdminRequest()) {
            $relativeLayoutPath = 'workspace/admin/layouts/'. $this->layout .'.php';
        }

        $layoutFile = __DIR__ .'/../'. $relativeLayoutPath;
        $layoutPath = realpath($layoutFile);
        
        if (empty($layoutPath) || empty($relativeLayoutPath)) {
            trigger_error('Layout &quot;'. $relativeLayoutPath .'&quot; not found!', E_USER_ERROR);
        } 

        $relativeViewPath = '';

        if ($this->isSiteRequest()) {
            $relativeViewPath = 'workspace/site/views/'. $viewName .'.php';
        }
        elseif ($this->isAdminRequest()) {
            $relativeViewPath = 'workspace/admin/views/'. $viewName .'.php';
        }

        $viewFile = __DIR__ .'/../'. $relativeViewPath;
        $viewPath = realpath($viewFile);

        if (empty($viewPath) || empty($relativeViewPath)) {
            trigger_error('View &quot;'. $relativeViewPath .'&quot; not found!', E_USER_ERROR);
        } 

        foreach ($viewArguments as $key => $value) {
            $this->vars->{$key} = $value;
        }

        ob_start();
        require $viewPath;
        $this->contentForLayout = ob_get_clean();

        require $layoutPath;
    }

}
