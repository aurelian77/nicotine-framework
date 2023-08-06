<?php
declare(strict_types=1);

namespace workspace\admin\controllers;

use nicotine\Controller;
use nicotine\RequestMethod;
use nicotine\AdminRoles;

class Login extends Controller {

    public function __construct()
    {
        parent::__construct();
        $this->proxy->layout = 'login';
    }

    #[RequestMethod('get')]
    public function index(): void
    {
        $this->proxy->view('login/login');
    }
    
    #[RequestMethod('post')]
    public function check(): void
    {
        if ($this->proxy->admin->model('LoginModel')->check($this->proxy->post()) != true) {
            $this->proxy->back(['Invalid login!']);
        } else {
            // FIXME: depends of user role(s).
            header('Location: '.href('admin/staff/list'));
        }
    }

    #[RequestMethod('get')]
    public function logout()
    {
        $this->proxy->session(['staff_member'=> null]);
        header("Location: ".href('admin'));
    }

}
