<div id="title">
    <h1>Edit My Data</h1>
</div>

<form method="post" action="<?php print href('admin/staff/update-by-guest/'.$this->vars->staffMember['id'].'/'.$this->vars->invitationHash); ?>">
    <table>
        <tr>
            <td>First Name</td>
            <td><input type="text" name="first_name" required="required" value="<?php print transient('first_name', $this->vars->staffMember['first_name']); ?>" /></td>
        </tr>
        <tr>
            <td>Password</td>
            <td><input type="password" name="password_1" value="<?php print transient('password_1'); ?>" /></td>
        </tr>
        <tr>
            <td>Confirm Password</td>
            <td><input type="password" name="password_2" value="<?php print transient('password_2'); ?>" /></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" value="Save" class="btn btn-default float-left" />
            </td>
        </tr>
    </table>
</form>
