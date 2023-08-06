<?php
declare(strict_types=1);

namespace workspace\admin\controllers;

use nicotine\Controller;
use nicotine\RequestMethod;
use nicotine\AdminRoles;
use nicotine\Registry;

class Roles extends Controller {

    public function __construct()
    {
        parent::__construct();
        $this->proxy->layout = 'staff-roles';
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function list()
    {
        $roles = $this->proxy->admin->model('RolesModel')->getAll();
        $this->proxy->view('roles/list', compact('roles'));
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function add()
    {
        $this->proxy->view('roles/add');
    }

    #[RequestMethod('post')]
    #[AdminRoles('super_admin')]
    public function save()
    {
        $data = $this->proxy->post();
        $data['name'] = trim($data['name']);

        if (empty($data['name'])) {
            $this->proxy->back(['Role name is required!']);
        }

        if ($this->proxy->admin->model('RolesModel')->checkIfRoleExists($data['name']) == true) {
            $this->proxy->back(['Role already exists!']);
        }

        $this->proxy->admin->model('RolesModel')->saveRole($data);
        header("Location: ".href('admin/roles/list'));
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function edit($id)
    {
        $role = $this->proxy->admin->model('RolesModel')->getRole($id);
        $this->proxy->view('roles/edit', compact('role'));
    }

    #[RequestMethod('post')]
    #[AdminRoles('super_admin')]
    public function update($id)
    {
        $data = $this->proxy->post();
        $data['name'] = trim($data['name']);

        if (empty($data['name'])) {
            $this->proxy->back(['Role name is required!']);
        }

        if ($this->proxy->admin->model('RolesModel')->checkRoleUpdateExists($id, $data['name']) == true) {
            $this->proxy->back(['Role already exists!']);
        }

        $this->proxy->admin->model('RolesModel')->updateRole($id, $data);
        header("Location: ".href('admin/roles/list'));
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function delete($id)
    {
        $this->proxy->admin->model('RolesModel')->deleteRole($id);
        header("Location: ".href('admin/roles/list'));
    }

}