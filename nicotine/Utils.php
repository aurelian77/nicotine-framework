<?php
declare(strict_types=1);

namespace nicotine;

use nicotine\Registry;

/**
| Utils class; @see file Helpers.php
*/
class Utils extends Dispatcher {

    /**
    | Var dumper (without wrapper); Recursion.
    */
    public function varDump(mixed $var, int $indent = 0): void
    {
        $indentStr = str_repeat('&nbsp;', $indent);
        $type = gettype($var);

        switch ($type) 
        {
            case 'boolean':
                print '<span style="color:#0a0;">'. ($var === true ? 'true' : 'false') .'</span><br />';
            break;

            case 'integer':
                print '<span style="color:#00f;">'. $var .'</span><br />';
            break;

            case 'double':
                print '<span style="color:#ff1493;">'. $var .'</span><br />';
            break;

            case 'string':
                print '&quot;<span style="color:#bc3333;">'. htmlspecialchars($var) .'</span>&quot;<br />';
            break;

            case 'array':
                print 'array (<br />';

                foreach ($var as $key => $value) {
                    print $indentStr .'&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#555;">'. $key .'</span> =&gt; ';
                    $this->varDump($value, $indent + 4);
                }

                print $indentStr .')<br />';
            break;

            case 'object':
                print 'Object '. get_class($var) .' {<br />';

                foreach (get_object_vars($var) as $key => $value) {
                    print $indentStr .'&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#555;">'. $key .'</span> =&gt; ';
                    $this->varDump($value, $indent + 4);
                }

                print $indentStr . '}<br />';
            break;

            case 'resource':
                print '<span style="color:#487e4c;">Resource: '. get_resource_type($var) .'</span><br />';
            break;

            case 'resource (closed)':
                print '<span style="color:#709561;">Resource (closed): '. get_resource_type($var) .'</span><br />';
            break;

            case 'NULL':
                print '<span style="color:#f39a4a;">null</span><br />';
            break;

            default:
                print 'Unknown type: '. $type .' <span style="color:#222;">'. print_r($var, true) .'</span><br />';
            break;
        }
    }

    /**
    | Wrapper for var dumper.
    */
    public function dump($var, $withType = false): void
    {
        if (!$this->isAjaxRequest() && !$this->isCliRequest())
        {
            $_style = [
                'font-family' => 'monospace',
                'font-size' => '14px',
                'line-height' => '16px',
                'text-align' => 'left',
                'background-color' => '#f0f0f0',
                'border' => '1px solid #ccc',
                'padding' => '0',
                'margin' => '1px',
                'color' => '#222'
            ];

            $style = '';

            foreach ($_style as $key => $value) {
                $style .= "{$key}:{$value};";
            }
            
            $trace = debug_backtrace();

            print '
                <div style="'. $style .'">
                    <div style="background-color:#222;color:#fff;padding:1px;">
                        File: &quot;'. $trace[1]['file'] .'&quot;, Line: '. $trace[1]['line'] .'
                    </div>
            ';
                $this->varDump($var);
            print '
                </div>
            ';
        } else {
            if ($withType == true) {
                var_dump($var);
            } else {
                print_r($var);
            }
        }
    }

    /**
    | Format bytes.
    */
    public function formatBytes(int $bytes, int $precision = 4): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . $units[$pow];
    }

    /**
    | Reverse engineering for parse HTTP request.
    | With any parameter returns homepage for site side.
    |
    | Without "installation folder" and "URL suffix" - they will be added.
    | @note Don't hardcode links; use this method instead. Installation folder and URL suffix can be changed any time.
    |
    | @param string $to @example admin/foo-bar/baz/param/1
    | @param array $params e.g. ['category' => 123, 'sort' => 'asc']
    */
    public function href(string $to = '', array $params = []): string
    {
        $config = Registry::get('config');

        if (empty($to)) {
            return $config->baseHref;
        }

        return $config->baseHref .'/'. $to
            .((!empty($config->urlSuffix) && !str_starts_with($to, 'admin/static/')) ? '.'. $config->urlSuffix : '')
            .(!empty($params) ? '?'. http_build_query($params) : '')
        ;
    }

    /**
    | Get roles.
    */
    public function getRoles()
    {
        if (isset($_SESSION)
            && is_array($_SESSION)
            && array_key_exists('staff_member', $_SESSION)
            && is_array($_SESSION['staff_member'])
            && array_key_exists('admin_roles', $_SESSION['staff_member'])
            && is_array($_SESSION['staff_member']['admin_roles'])
        ) {
            return $_SESSION['staff_member']['admin_roles'];
        }

        return [];
    }

    /**
    | Get user.
    */
    public function getUser()
    {
        if (isset($_SESSION)
            && is_array($_SESSION)
            && array_key_exists('staff_member', $_SESSION)
            && is_array($_SESSION['staff_member'])
        ) {
            return $_SESSION['staff_member'];
        }

        return [];
    }

    /**
    | Check if SESSION has a role.
    */
    public function hasRole(string $role): bool
    {
        if (in_array($role, $this->getRoles())) {
            return true;
        }
        return false;
    }

    // > 0
    public function isNatural(mixed $var): bool {
        $var = "{$var}";
        if (preg_match('/^[1-9]{1}[0-9]*$/', $var)) {
            return true;
        }
        return false;
    }

    public function transient(string $key, mixed $default = null): mixed {
        if (array_key_exists('user_request', $_SESSION) && is_array($_SESSION['user_request'])) {
            if (array_key_exists($key, $_SESSION['user_request'])) {
                return $_SESSION['user_request'][$key];
            }

            if (!is_null($default)) {
                return $default;
            }
        }

        return null;
    }

    public function email($to, $subject, $body, $headers) {
        switch (Registry::get('config')->errorReporting) {
            case 'PRODUCTION_MODE':
                mail($to, $subject, $body, $headers);
            break;

            case 'STAGING_MODE':
                mail(Registry::get('config')->redirectEmailsTo, $subject, $body, $headers);
            break;

            case 'DEVELOPMENT_MODE':
                d(func_get_args());
            break;
        }
    }

    /**
    | Note no trailing slash at the end.
    */
    public function emptyDirectory(string $directory, string $exclude): void {
        $iterator = new \DirectoryIterator($directory);

        $skip = ['.', '..'];
        if (!empty($exclude)) {
            $skip[] = $exclude;
        }

        foreach ($iterator as $file) {
            $name = $file->getFilename();
            $path = $directory.DIRECTORY_SEPARATOR.$name;

            if (is_file($path) && !in_array($name, $skip)) {
                unlink($path);
            }
        }
    }

    /**
    | Translate.
    */
    public function translate(string $string): string {
        return Registry::get('language')[$string] ?? $string;
    }

    /**
    | Generate random hexa hash, 128 chrs length.
    | This should be temporary. After user request you should set it to null into the database.
    */
    public function generateHash(): string
    {
        $hash = str_split(hash('sha512', (string) time()));
        shuffle($hash);
        return implode('', $hash);
    }

}
