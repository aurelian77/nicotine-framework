<?php
declare(strict_types=1);

namespace workspace\api;

use nicotine\Registry;

$db = Registry::get('Database');
$id = 1;
d($db->getCustom("SELECT * from `roles` WHERE `id` = ".intval($id), [], \PDO::FETCH_UNIQUE|\PDO::FETCH_OBJ, false));

$proxy = Registry::get('Proxy');
d($proxy->admin->model('RolesModel')->checkIfRoleExists('super_admin'));
