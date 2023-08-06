<!DOCTYPE html>
<html>
    <head>
        <title>Staff Roles</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <link rel="stylesheet" type="text/css" href="<?php print href('admin/static/css/staff-roles.css'); ?>" />
    </head>
    <body>
        <aside>
            <h1 class="text-right float-none m-top-10">Menu</h1>
            <ul>
                <li>
                    <a href="<?php print href('admin/staff/list'); ?>">Staff</a>
                </li>
                <li>
                    <a href="<?php print href('admin/roles/list'); ?>">Roles</a>
                </li>
            </ul>
        </aside>
        <div id="container">
            <div id="welcome">
                Welcome <?php print $this->session()['staff_member']['first_name'] ?? ''; ?>
                <a href="<?php print href('admin/login/logout'); ?>">Logout</a>
            </div>

            <?php
            if (!empty($this->session('custom_errors'))) {
                foreach ($this->session('custom_errors') as $error) { 
                    ?><div class="<?php print $this->session('messages_type'); ?>"><?php print $error; ?></div><?php 
                } 
            }

            print $this->contentForLayout; 
            ?>
        </div>
        <br style="clear: both;" />
    </body>
</html>