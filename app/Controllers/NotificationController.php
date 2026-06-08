<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Services\NotificationService;

class NotificationController extends BaseController
{
    private NotificationService $service;

    public function __construct()
    {
        $this->service = new NotificationService();
    }

    public function index(): void
    {
        $userId = Session::userId();
        $filters = [
            'read' => $_GET['read'] ?? '',
            'type' => $_GET['type'] ?? '',
            'severity' => $_GET['severity'] ?? '',
            'search' => trim($_GET['search'] ?? ''),
        ];

        $this->view('notifications/index', [
            'pageTitle' => 'Notification Inbox',
        ] + $this->service->center((int)$userId, $filters));
    }

    public function markRead(string $id): void
    {
        $this->service->markRead((int)$id, (int)Session::userId(), true);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
            return;
        }

        $this->back();
    }

    public function markUnread(string $id): void
    {
        $this->service->markRead((int)$id, (int)Session::userId(), false);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
            return;
        }

        $this->back();
    }

    public function markAllRead(): void
    {
        $this->service->markAllRead((int)Session::userId());

        if ($this->isAjax()) {
            $this->json(['success' => true]);
            return;
        }

        Session::flash('success', 'All notifications marked as read.');
        $this->redirect('/notifications');
    }

    public function getUnread(): void
    {
        $this->json($this->service->unread((int)Session::userId()));
    }

    public function preferences(): void
    {
        $this->view('notifications/preferences', [
            'pageTitle' => 'Notification Preferences',
            'preferences' => $this->service->preferences((int)Session::userId()),
            'types' => NotificationService::types(),
        ]);
    }

    public function updatePreferences(): void
    {
        $this->service->savePreferences((int)Session::userId(), $_POST);
        Session::flash('success', 'Notification preferences updated.');
        $this->redirect('/notifications/preferences');
    }

    public function subscribePush(): void
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = $this->service->registerPushSubscription((int)Session::userId(), [
            'endpoint' => $payload['endpoint'] ?? '',
            'p256dh_key' => $payload['keys']['p256dh'] ?? ($payload['p256dh_key'] ?? ''),
            'auth_token' => $payload['keys']['auth'] ?? ($payload['auth_token'] ?? ''),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        $this->json(['success' => true, 'id' => $id]);
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
