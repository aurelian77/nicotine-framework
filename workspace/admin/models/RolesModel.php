<?php
declare(strict_types=1);

namespace workspace\admin\models;

use nicotine\Model;

class RolesModel extends Model {

    public function getAll()
    {
        return $this->db->getAll("SELECT * FROM `roles`");
    }

    public function checkIfRoleExists($name)
    {
        if ($this->db->getValue("SELECT COUNT(*) FROM `roles` WHERE `name` = :name", [':name' => $name]) > 0) {
            return true;
        }

        return false;
    }

    public function saveRole($data)
    {
        $this->db->set("
            INSERT INTO `roles` SET `name` = :name, `description` = :description
        ", [
            ':name' => $data['name'], ':description' => $data['description']
        ]);
    }

    public function getRole($id)
    {
        return $this->db->getRow("SELECT * FROM `roles` WHERE `id` = ".intval($id));
    }

    public function checkRoleUpdateExists($id, $name)
    {
        if ($this->db->getValue("SELECT COUNT(*) FROM `roles` WHERE `name` = :name AND `id` != ".intval($id), [':name' => $name]) > 0) {
            return true;
        }

        return false;
    }

    public function updateRole($id, $data)
    {
        $this->db->set("
            UPDATE `roles` SET `name` = :name, `description` = :description WHERE `id` = ".intval($id)." LIMIT 1
        ", [
            ':name' => $data['name'], ':description' => $data['description']
        ]);
    }

    public function deleteRole($id)
    {
        $this->db->set("DELETE FROM `roles` WHERE `id` = ".intval($id)." LIMIT 1");
    }
}
