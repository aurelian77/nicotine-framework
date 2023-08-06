<?php
declare(strict_types=1);

namespace workspace\admin\models;

use nicotine\Model;

class StaffModel extends Model {

    public function getMembers()
    {
        return $this->db->getAll("
            SELECT `staff`.*, GROUP_CONCAT(`roles`.`description` SEPARATOR ', ') AS `staff_roles`
            FROM `staff`
            LEFT JOIN `staff_roles` ON (`staff`.`id` = `staff_roles`.`staff_id`)
            LEFT JOIN `roles` ON (`staff_roles`.`role_id` = `roles`.`id`)
            GROUP BY  `staff`.`id`
            ORDER BY `staff`.`id` DESC
        ");
    }

    public function getMember($staffId)
    {
        $return = $this->db->getRow("
            SELECT * FROM `staff`
            WHERE `id` = ". intval($staffId) ." LIMIT 1
        ");

        $return['roles'] = $this->db->getColumn("SELECT `role_id` FROM `staff_roles` WHERE `staff_id` = ". intval($staffId));

        return $return;
    }

    public function countByInvitation($staffId, $invitationHash)
    {
        return $this->db->getValue("
            SELECT COUNT(*) FROM `staff`
            WHERE `id` = ". intval($staffId) ." AND `invitation_hash` = :invitation_hash AND `active` IS NULL AND DATEDIFF(NOW(), `created_at`) < 3
        ", [
            ':invitation_hash' => $invitationHash
        ]);
    }

    public function saveInvitation($staffId, $invitationHash)
    {
        $this->db->set("
            UPDATE `staff` SET `invitation_hash` = :invitation_hash
            WHERE `id` = ". intval($staffId) ." AND `active` IS NULL
            LIMIT 1
        ", [
            ':invitation_hash' => $invitationHash
        ]);
        
    }

    public function updateByAdmin($staffId, $data)
    {
        $exists = $this->db->getValue("SELECT COUNT(*) FROM `staff` WHERE `email` = :email AND `id` != ". intval($staffId), [':email' => $data['email']]);

        if (!empty($exists)) {
            return false;
        }

        if (empty($data['roles'])) {
            return false;
        }

        $this->db->set("
            UPDATE `staff` SET `email` = :email
            WHERE `id` = ". intval($staffId) ." LIMIT 1
        ", [
            ':email' => $data['email']
        ]);

        $this->db->set("DELETE FROM `staff_roles` WHERE `staff_id` = ". intval($staffId));

        foreach ($data['roles'] as $role) {
            $this->db->set("INSERT INTO `staff_roles` SET `staff_id` = ". intval($staffId).", `role_id` = ".intval($role));
        }

        return true;
    }

    public function updateByGuest($staffId, $data)
    {
        $this->db->set("
            UPDATE `staff` SET `first_name` = :first_name, `password` = :password, `active` = 1, `invitation_hash` = NULL
            WHERE `id` = ". intval($staffId) ." AND `active` IS NULL LIMIT 1
        ", [
            ':first_name'=> $data['first_name'],
            ':password' => password_hash($data['password_1'], PASSWORD_DEFAULT)
        ]);
    }

    public function countByEmail($email)
    {
        return $this->db->getValue("
            SELECT COUNT(*) FROM `staff` WHERE `email` = :email
        ", [
            ':email' => $email
        ]);
    }

    public function save($data)
    {
        $this->db->set("
            INSERT INTO `staff` SET
            `email` = :email,
            `created_at` = NOW()
        ", [
            ':email' => $data['email'],
        ]);

        $lastInsertId = $this->db->getLastInsertId();

        foreach ($data['roles'] as $role) {
            $this->db->set("
                INSERT INTO `staff_roles` SET
                `staff_id` = ". intval($lastInsertId) .", `role_id` = ". intval($role) ."
            ");
        }
    }

    public function deactivate($staffId)
    {
        $this->db->set("
            UPDATE `staff` SET `active` = NULL WHERE `id` = ".intval($staffId)."
        ");
    }

    public function activate($staffId)
    {
        $this->db->set("
            UPDATE `staff` SET `active` = 1 WHERE `id` = ".intval($staffId)."
        ");
    }

}
