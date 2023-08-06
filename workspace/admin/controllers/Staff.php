<?php
declare(strict_types=1);

namespace workspace\admin\controllers;

use nicotine\Controller;
use nicotine\RequestMethod;
use nicotine\AdminRoles;
use nicotine\Registry;

class Staff extends Controller {

    public function __construct()
    {
        parent::__construct();
        $this->proxy->layout = 'staff-roles';
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function list()
    {
        $staffMembers = $this->proxy->admin->model('StaffModel')->getMembers();
        $this->proxy->view('staff/list', compact('staffMembers'));
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function sendInvitation($staffId)
    {
        $time = time();
        $invitation = hash('sha512', "{$time}");

        $this->proxy->admin->model('StaffModel')->saveInvitation($staffId, $invitation);

        $body = 'Click on the following link in order to set your password:'.PHP_EOL
            .href("admin/staff/edit-by-guest/{$staffId}/{$invitation}");

        email($staffMember['email'], Registry::get('config')->siteName.' :: Set your password', $body);
        $this->proxy->back();
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function editByAdmin($staffId)
    {
        $staffMember = $this->proxy->admin->model('StaffModel')->getMember($staffId);
        $roles = $this->proxy->admin->model('RolesModel')->getAll();
        $this->proxy->view('staff/editByAdmin', compact('staffMember', 'roles'));
    }

    #[RequestMethod('get')]
    public function editByGuest($staffId, $invitationHash)
    {
        // Check invitation.
        if ($this->proxy->admin->model('StaffModel')->countByInvitation($staffId, $invitationHash) != 1) {
            trigger_error('Your account doesn\'t exists, or is already active, or is created more than 3 days ago, or invitation hash doesn\'t match!', E_USER_ERROR);
        }

        $this->proxy->layout = 'empty';
        $staffMember = $this->proxy->admin->model('StaffModel')->getMember($staffId);
        $this->proxy->view('staff/editByGuest', compact('staffMember', 'invitationHash'));
    }

    #[RequestMethod('post')]
    #[AdminRoles('super_admin')]
    public function updateByAdmin($staffId)
    {
        $ok = $this->proxy->admin->model('StaffModel')->updateByAdmin($staffId, $this->proxy->post());

        if ($ok == true) {
            header("Location: ".href('admin/staff/list'));
        } else {
            $this->proxy->back(['Email already exists, or the guy doesn\'t have roles set!']);
        }
    }

    #[RequestMethod('post')]
    public function updateByGuest($staffId, $invitationHash)
    {
        // Check invitation.
        if ($this->proxy->admin->model('StaffModel')->countByInvitation($staffId, $invitationHash) != 1) {
            trigger_error('Your account doesn\'t exists, or is already active, or is created more than 3 days ago, or invitation hash doesn\'t match!', E_USER_ERROR);
        }

        $password1 = $this->proxy->post('password_1');
        $password2 = $this->proxy->post('password_2');

        if ($password1 != $password2) {
            $this->proxy->back(['Passwords does not match!']);
        }

        if (strlen($password1) < 6) {
            $this->proxy->back(['Password must have at least 6 characters!']);
        }

        $this->proxy->admin->model('StaffModel')->updateByGuest($staffId, $this->proxy->post());
        header("Location: ".href('admin'));
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function add()
    {
        $roles = $this->proxy->admin->model('RolesModel')->getAll();
        $this->proxy->view('staff/add', compact('roles'));
    }

    #[RequestMethod('post')]
    #[AdminRoles('super_admin')]
    public function save()
    {
        $post = $this->proxy->post();

        if (filter_var($post['email'], FILTER_VALIDATE_EMAIL) == false) {
            $this->proxy->back(['Please enter a valid email!']);
        }

        if (empty($post['roles'])) {
            $this->proxy->back(['Please select at least a role!']);
        }

        if (!empty($this->proxy->admin->model('StaffModel')->countByEmail($post['email']))) {
            $this->proxy->back(['Email already exists!']);
        }

        $this->proxy->admin->model('StaffModel')->save($post);
        header("Location: ".href('admin/staff/list'));
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function deactivate($staffId)
    {
        $this->proxy->admin->model('StaffModel')->deactivate($staffId);
        $this->proxy->back();
    }

    #[RequestMethod('get')]
    #[AdminRoles('super_admin')]
    public function activate($staffId)
    {
        $this->proxy->admin->model('StaffModel')->activate($staffId);
        $this->proxy->back();
    }

}