<?php
declare(strict_types=1);

use nicotine\Registry;

function d($var, $withType = false): void {
    Registry::get('Utils')->dump($var, $withType);
}

function dd($var, $withType = false): never {
    Registry::get('Utils')->dump($var, $withType);
    exit;
}

function href($to, array $params = []): string {
    return Registry::get('Utils')->href($to, $params);
}

function transient(string $key, mixed $default = null): mixed {
    return Registry::get('Utils')->transient($key, $default);
}

// > 0
function is_natural(mixed $var): bool {
    return Registry::get('Utils')->isNatural($var);
}

function has_role(string $role): bool {
    return Registry::get('Utils')->hasRole($role);
}

function get_roles() {
    return Registry::get('Utils')->getRoles();
}

function email($to, $subject, $body, $headers = []) {
    Registry::get('Utils')->email($to, $subject, $body, $headers);
}

function empty_directory(string $directory, string $exclude = '.keep'): void {
    Registry::get('Utils')->emptyDirectory($directory, $exclude);
}

function __(string $string = ''): string {
    return Registry::get('Utils')->translate($string);
}
