<?php
declare(strict_types=1);

namespace workspace\admin\models;

use nicotine\Model;

class LoginModel extends Model {

    public function check(array $data): mixed
    {
        $member = $this->db->getRow("
            SELECT * FROM `staff`
            WHERE `staff`.`active` = 1 AND `staff`.`email` = :email AND `invitation_hash` IS NULL", [':email' => $data['email']]
        );

        if (empty($member)) {
            return false;
        }

        if (!password_verify($data['password'], $member['password'])) {
            return false;
        }

       if (password_needs_rehash($member['password'], PASSWORD_DEFAULT)) {
            $this->db->set("
                UPDATE `staff` SET `password` = :password WHERE `email` = :email LIMIT 1
            ", [
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':email' => $data['email']
            ]);
        }

        $this->db->set("
            UPDATE `staff` SET `last_login` = NOW() WHERE `email` = :email LIMIT 1
        ", [
            ':email' => $data['email']
        ]);

        $roles = $this->db->getColumn("
            SELECT `roles`.`name`
            FROM `staff`
            INNER JOIN `staff_roles` ON (`staff`.`id` = `staff_roles`.`staff_id`)
            INNER JOIN `roles` ON (`staff_roles`.`role_id` = `roles`.`id`)
            WHERE `staff`.`active` = 1 AND `staff`.`email` = :email", [':email' => $data['email']]
        );

        if (empty($roles)) {
            return false;
        }

        $session = ['staff_member' => []];

        $session['staff_member'] = $member;
        $session['staff_member']['admin_roles'] = $roles;

        $this->proxy->session($session);

        return true;
    }

}