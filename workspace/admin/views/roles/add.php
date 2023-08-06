<div id="title">
    <h1>Add Role</h1>
</div>

<form method="post" action="<?php print href('admin/roles/save'); ?>">
    <table>
        <tr>
            <td>Name</td>
            <td><input type="text" name="name" required="required" value="<?php print transient('name'); ?>" /></td>
        </tr>
        <tr>
            <td>Description</td>
            <td><input type="text" name="description" value="<?php print transient('description'); ?>" /></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" value="Save" class="btn btn-default float-left" />
            </td>
        </tr>
    </table>
</form>
