<?php
declare(strict_types=1);

namespace workspace\cli\crons;

use nicotine\Registry;

$db = Registry::get('Database');
d($db->getCustom("SELECT `first_name`, `email` from `staff`", [], \PDO::FETCH_OBJ, true));

$proxy = Registry::get('Proxy');
d($proxy->admin->model('StaffModel')->getMember(2));