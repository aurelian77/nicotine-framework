<div id="title">
    <h1>Roles</h1>
    <a href="<?php print href('admin/roles/add'); ?>" class="btn btn-default">Add Role</a>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Id</th>
            <th>Name</th>
            <th>Description</th>
            <th>Edit</th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($this->vars->roles as $role) { ?>
            <tr>
                <td><?php print $role['id']; ?></td>
                <td><?php print $role['name']; ?></td>
                <td><?php print $role['description']; ?></td>
                <td>
                    <a class="btn btn-orange"
                        href="<?php print href('admin/roles/edit/'.$role['id']); ?>">Edit</a>
                </td>
                <td>
                    <a class="btn btn-red" onclick="return confirm('Are you sure? Operation is irreversible!');"
                        href="<?php print href('admin/roles/delete/'.$role['id']); ?>">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5"></td>
        </tr>
    </tfoot>
</table>