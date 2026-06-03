<?php
namespace App\Models;

class User
{
    public int $id;
    public string $first_name;
    public string $last_name;
    public string $email;
    public string $password;
    public ?string $phone;
    public ?string $avatar;
    public ?int $department_id;
    public ?string $job_title;
    public string $status;
    public ?string $last_login_at;
    public int $login_attempts;
    public ?string $locked_until;
    public ?string $department_name;
    public ?string $role_name;
    public ?string $role_slug;

    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getInitials(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    public function isLocked(): bool
    {
        if ($this->status === 'locked') return true;
        if ($this->locked_until && strtotime($this->locked_until) > time()) return true;
        return false;
    }

    public static function fromObject(object $obj): self
    {
        $user = new self();
        foreach (get_object_vars($obj) as $key => $value) {
            if (property_exists($user, $key)) {
                $user->$key = $value;
            }
        }
        return $user;
    }
}
