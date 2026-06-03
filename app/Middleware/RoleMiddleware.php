<?php
namespace App\Middleware;

use App\Helpers\Session;

class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(string ...$roles)
    {
        $this->allowedRoles = $roles;
    }

    public function handle(): bool
    {
        $userRole = Session::get('role', '');
        
        // Super admin can access everything
        if ($userRole === ROLE_SUPER_ADMIN) {
            return true;
        }

        if (!empty($this->allowedRoles) && !in_array($userRole, $this->allowedRoles)) {
            http_response_code(403);
            echo '<h1>403 - Access Denied</h1><p>You do not have permission to access this resource.</p>';
            exit;
            return false;
        }

        return true;
    }
}
