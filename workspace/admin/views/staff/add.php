<div id="title">
    <h1>Add Staff Member</h1>
</div>

<form method="post" action="<?php print href('admin/staff/save'); ?>">
    <table>
        <tr>
            <td>Email</td>
            <td><input type="email" name="email" required="required" value="<?php print transient('email'); ?>" /></td>
        </tr>
        <tr>
            <td>Roles</td>
            <td>
                <select name="roles[]" required="required" multiple="multiple">
                    <?php foreach ($this->vars->roles as $role) { ?>
                        <option value="<?php print $role['id']; ?>"><?php print $role['description']; ?> (<?php print $role['name']; ?>)</option>
                    <?php } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" value="Save" class="btn btn-default float-left" />
            </td>
        </tr>
    </table>
</form>
