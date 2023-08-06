<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            html, body {
                height: 100%;
                width: 100%;
                background-color: #fafafa;
                font-family: sans-serif;
                font-size: 16px;
                line-height: 20px;
                color: #222;
            }
            fieldset {
                width: 460px;
                height: 180px;
                border: 1px solid #ccc;
                position: absolute;
                top: calc(50% - 90px);
                left: calc(50% - 230px);
                background-color: #fff;
                text-align: center;
                padding: 5px;
            }
            fieldset * {
                padding: 5px;
            }
            input[type="email"],
            input[type="password"] {
                text-align: left;
                width: 100%;
                font-family: sans-serif;
                font-size: 16px;
                line-height: 20px;
            }
            input[type="submit"] {
                cursor: pointer;
                background-color: #eee;
                border: 1px solid #ccc;
                padding: 5px 15px;
                font-family: sans-serif;
                font-size: 16px;
                line-height: 20px;
            }
            .error {
                background-color: #fff0f5;
                padding: 5px;
                margin-bottom: 1px;
                border: 1px solid #ccc;
                text-align: center;
                color: #b00;
            }
        </style>
    </head>
    <body>
        <?php
        if (!empty($this->session('custom_errors'))) {
            foreach ($this->session('custom_errors') as $error) { 
            ?>
                <div class="<?php print $this->session('messages_type'); ?>"><?php print $error; ?></div>
            <?php 
            } 
        }
        print $this->contentForLayout; 
        ?>
    </body>
</html>