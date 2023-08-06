<fieldset>
    <legend>Login</legend>

    <form method="post" action="<?php print href('admin/login/check'); ?>">
        <p><input type="email" name="email" placeholder="Email" value="<?php print transient('email'); ?>" required="required" /></p>
        <p><input type="password" name="password" placeholder="Password" value="<?php print transient('password'); ?>" required="required" /></p>
        <p><input type="submit" value="Login" /></p>
    </form>
</fieldset>