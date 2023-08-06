<div id="title">
    <h1>Edit Staff Member</h1>
</div>

<form method="post" action="<?php print href('admin/staff/update-by-admin/'.$this->vars->staffMember['id']); ?>">
    <table>
        <tr>
            <td>Email</td>
            <td><input type="email" name="email" required="required" value="<?php print transient('email', $this->vars->staffMember['email']); ?>" /></td>
        </tr>
        <tr>
            <td>Roles</td>
            <td>
                <select name="roles[]" required="required" multiple="multiple">
                    <?php foreach ($this->vars->roles as $role) { ?>
                        <option value="<?php print $role['id']; ?>"<?php 
                            if (in_array($role['id'], $this->vars->staffMember['roles'])) {
                                print ' selected="selected"';
                            }
                        ?>>
                            <?php print $role['description']; ?> (<?php print $role['name']; ?>)
                        </option>
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
