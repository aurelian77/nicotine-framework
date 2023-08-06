<!DOCTYPE html>
<html>
    <head>

    </head>
    <body>
        <?php 
        if (!empty($this->session('custom_errors'))) {
            foreach ($this->session('custom_errors') as $error) { 
            ?>
                <p class="<?php print $this->session('messages_type'); ?>"><?php print $error; ?></p>
            <?php 
            } 
        }
        print $this->contentForLayout;
        ?>
    </body>
</html>