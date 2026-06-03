<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Helpers\Session;

class AuthService
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function attempt(string $email, string $password): array
    {
        $user = $this->userRepo->findByEmail($email);
        if (!$user) return ['success' => false, 'message' => 'Invalid credentials.'];
        if ($user->status === 'inactive') return ['success' => false, 'message' => 'Account is inactive.'];
        if ($user->locked_until && strtotime($user->locked_until) > time()) {
            $mins = ceil((strtotime($user->locked_until) - time()) / 60);
            return ['success' => false, 'message' => "Account locked. Try again in {$mins} minutes."];
        }

        if (!password_verify($password, $user->password)) {
            $attempts = $user->login_attempts + 1;
            $config = $GLOBALS['appConfig']['login_throttle'];
            $lockedUntil = null;
            if ($attempts >= $config['max_attempts']) {
                $lockedUntil = date('Y-m-d H:i:s', time() + $config['lockout_time']);
            }
            $this->userRepo->updateLoginAttempts($user->id, $attempts, $lockedUntil);
            $remaining = $config['max_attempts'] - $attempts;
            if ($remaining > 0) {
                return ['success' => false, 'message' => "Invalid credentials. {$remaining} attempts remaining."];
            }
            return ['success' => false, 'message' => 'Account locked due to too many failed attempts.'];
        }

        $this->userRepo->recordLogin($user->id);
        $permissions = $this->userRepo->getPermissions($user->id);

        Session::regenerate();
        Session::set('user_id', $user->id);
        Session::set('user', [
            'id' => $user->id, 'first_name' => $user->first_name, 'last_name' => $user->last_name,
            'email' => $user->email, 'avatar' => $user->avatar, 'job_title' => $user->job_title,
            'department_name' => $user->department_name ?? '',
        ]);
        Session::set('role', $user->role_slug ?? 'staff');
        Session::set('role_name', $user->role_name ?? 'Staff');
        Session::set('permissions', $permissions);

        $this->audit($user->id, AUDIT_LOGIN, 'user', $user->id);
        return ['success' => true, 'user' => $user];
    }

    public function logout(): void
    {
        $userId = Session::userId();
        if ($userId) $this->audit($userId, AUDIT_LOGOUT, 'user', $userId);
        Session::destroy();
    }

    public function generateResetToken(string $email): ?string
    {
        $user = $this->userRepo->findByEmail($email);
        if (!$user) return null;
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $this->userRepo->setPasswordResetToken($user->id, hash('sha256', $token), $expires);
        return $token;
    }

    public function resetPassword(string $token, string $password): bool
    {
        $user = $this->userRepo->findByResetToken(hash('sha256', $token));
        if (!$user) return false;
        $this->userRepo->update($user->id, [
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'password_reset_token' => null, 'password_reset_expires' => null,
            'login_attempts' => 0, 'locked_until' => null,
        ]);
        return true;
    }

    public function changePassword(int $userId, string $current, string $new): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) return ['success' => false, 'message' => 'User not found.'];
        if (!password_verify($current, $user->password)) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        $this->userRepo->update($userId, [
            'password' => password_hash($new, PASSWORD_BCRYPT, ['cost' => 12])
        ]);
        return ['success' => true, 'message' => 'Password changed successfully.'];
    }

    private function audit(int $userId, string $action, string $entity, int $entityId): void
    {
        try {
            $db = \App\Helpers\Database::getInstance();
            $db->insert('audit_logs', [
                'user_id' => $userId, 'action' => $action,
                'entity_type' => $entity, 'entity_id' => $entityId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);
        } catch (\Exception $e) { /* silent */ }
    }
}
