<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\Database;

class NotificationController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $userId = Session::userId();

        $notifications = $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = ? 
             ORDER BY is_read ASC, created_at DESC",
            [$userId]
        );

        $this->view('notifications/index', [
            'pageTitle' => 'Notification Inbox',
            'notifications' => $notifications
        ]);
    }

    public function markRead(string $id): void
    {
        $userId = Session::userId();

        $this->db->update(
            'notifications',
            ['is_read' => 1],
            'id = ? AND user_id = ?',
            [(int)$id, $userId]
        );

        if ($this->isAjax()) {
            $this->json(['success' => true]);
            return;
        }

        $this->back();
    }

    public function markAllRead(): void
    {
        $userId = Session::userId();

        $this->db->update(
            'notifications',
            ['is_read' => 1],
            'user_id = ?',
            [$userId]
        );

        if ($this->isAjax()) {
            $this->json(['success' => true]);
            return;
        }

        Session::flash('success', 'All notifications marked as read.');
        $this->redirect('/notifications');
    }

    public function getUnread(): void
    {
        $userId = Session::userId();

        $count = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );

        $list = $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = ? AND is_read = 0 
             ORDER BY created_at DESC LIMIT 5",
            [$userId]
        );

        $this->json([
            'count' => $count,
            'list' => $list
        ]);
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
