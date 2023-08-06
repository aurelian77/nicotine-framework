<div id="title">
    <h1>Staff Members</h1>
    <a href="<?php print href('admin/staff/add'); ?>" class="btn btn-default">Add Member</a>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Id</th>
            <th>First Name</th>
            <th>Email</th>
            <th>Created At</th>
            <th>Last Login</th>
            <th>Active</th>
            <th>Roles</th>
            <th colspan="3" style="width:420px;">
                Actions
            </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($this->vars->staffMembers as $staffMember) { ?>
            <tr>
                <td><?php print $staffMember['id']; ?></td>
                <td><?php print $staffMember['first_name']; ?></td>
                <td><?php print $staffMember['email']; ?></td>
                <td>
                    <?php 
                    print empty($staffMember['created_at']) ? '-' : (new \DateTimeImmutable($staffMember['created_at']))->format('l, jS F Y, h:i:s A'); 
                    ?>
                </td>
                <td>
                    <?php print empty($staffMember['last_login']) ? '-' : (new \DateTimeImmutable($staffMember['last_login']))->format('l, jS F Y, h:i:s A'); ?>
                </td>
                <td><?php print ($staffMember['active'] == 1) ? 'Yes' : 'No'; ?></td>
                <td><?php print $staffMember['staff_roles']; ?></td>
                <td>
                    <a class="btn btn-green"<?php print !empty($staffMember['active']) ? ' disabled="disabled"' : ''; ?>
                        href="<?php print href('admin/staff/send-invitation/'.$staffMember['id']); ?>">
                        Send Invitation
                    </a>
                </td>
                <td>
                    <a class="btn btn-blue"
                        href="<?php print href('admin/staff/edit-by-admin/'.$staffMember['id']); ?>">Edit</a>
                </td>
                <td>
                    <?php if ($staffMember['active'] == 1) { ?>
                        <a class="btn btn-orange"
                            href="<?php print href('admin/staff/deactivate/'.$staffMember['id']); ?>">Deactivate</a>
                    <?php } else { ?>
                        <a class="btn btn-orange"
                            href="<?php print href('admin/staff/activate/'.$staffMember['id']); ?>">Activate</a>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="10"></td>
        </tr>
    </tfoot>
</table>