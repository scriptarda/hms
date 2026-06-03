<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Services\AuthService;
use App\Repositories\UserRepository;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function login(): void
    {
        if (Session::isLoggedIn()) { $this->redirect('/dashboard'); }
        $this->view('auth/login', ['pageTitle' => 'Login'], null);
    }

    public function doLogin(): void
    {
        if (!CSRF::validate()) { Session::flash('error', 'Invalid request.'); $this->redirect('/login'); }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $v = new Validator($_POST);
        $v->required('email')->email('email')->required('password');
        if ($v->fails()) { Session::flash('error', $v->firstError()); $this->redirect('/login'); }

        $result = $this->authService->attempt($email, $password);
        if (!$result['success']) { Session::flash('error', $result['message']); $this->redirect('/login'); }
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->authService->logout();
        $this->redirect('/login');
    }

    public function forgotPassword(): void
    {
        $this->view('auth/forgot-password', ['pageTitle' => 'Forgot Password'], null);
    }

    public function doForgotPassword(): void
    {
        if (!CSRF::validate()) { $this->redirect('/forgot-password'); }
        $email = trim($_POST['email'] ?? '');
        $token = $this->authService->generateResetToken($email);
        Session::flash('success', 'If an account with that email exists, a reset link has been sent.');
        if ($token) {
            $resetUrl = ($GLOBALS['appConfig']['url'] ?? '') . '/reset-password/' . $token;
            error_log("Password reset link for {$email}: {$resetUrl}");
        }
        $this->redirect('/forgot-password');
    }

    public function resetPassword(string $token): void
    {
        $this->view('auth/reset-password', ['pageTitle' => 'Reset Password', 'token' => $token], null);
    }

    public function doResetPassword(): void
    {
        if (!CSRF::validate()) { $this->redirect('/login'); }
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $v = new Validator($_POST);
        $v->required('password')->minLength('password', 8)->match('password', 'password_confirmation', 'Passwords');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/reset-password/' . $token);
        }
        if ($this->authService->resetPassword($token, $password)) {
            Session::flash('success', 'Password reset successfully. Please login.');
        } else {
            Session::flash('error', 'Invalid or expired reset link.');
        }
        $this->redirect('/login');
    }

    public function profile(): void
    {
        $repo = new UserRepository();
        $user = $repo->findById(Session::userId());
        $this->view('auth/profile', ['pageTitle' => 'My Profile', 'user' => $user]);
    }

    public function updateProfile(): void
    {
        if (!CSRF::validate()) { $this->back(); }
        $repo = new UserRepository();
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $data['avatar'] = $this->uploadFile('avatar', 'avatars');
        }
        $repo->update(Session::userId(), $data);
        $user = Session::get('user');
        $user['first_name'] = $data['first_name'];
        $user['last_name'] = $data['last_name'];
        Session::set('user', $user);
        Session::flash('success', 'Profile updated successfully.');
        $this->redirect('/profile');
    }

    public function changePassword(): void
    {
        if (!CSRF::validate()) { $this->back(); }
        $v = new Validator($_POST);
        $v->required('current_password')->required('new_password')->minLength('new_password', 8)
          ->match('new_password', 'confirm_password', 'New passwords');
        if ($v->fails()) { Session::flash('error', $v->firstError()); $this->redirect('/profile'); }

        $result = $this->authService->changePassword(Session::userId(), $_POST['current_password'], $_POST['new_password']);
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/profile');
    }
}
