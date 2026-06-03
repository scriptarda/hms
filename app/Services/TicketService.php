<?php
namespace App\Services;

use App\Repositories\TicketRepository;
use App\Helpers\Database;
use App\Helpers\Session;

class TicketService
{
    private TicketRepository $repo;
    public function __construct() { $this->repo = new TicketRepository(); }

    public function createTicket(array $data): int
    {
        $data['ticket_number'] = $this->repo->generateTicketNumber();
        $data['requester_id'] = Session::userId();
        $data['status'] = 'new';
        $data['sla_due_at'] = $this->calculateSLA($data['priority'] ?? 'medium');
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $id = $this->repo->create($data);
        $this->repo->addHistory(['ticket_id'=>$id,'user_id'=>Session::userId(),'action'=>'created','new_value'=>$data['ticket_number']]);
        $this->notify($id, 'Ticket Created', "New ticket {$data['ticket_number']}: {$data['title']}");
        return $id;
    }

    public function assignTicket(int $ticketId, int $assigneeId, ?string $notes = null): void
    {
        $this->repo->update($ticketId, ['assigned_to'=>$assigneeId, 'status'=>'assigned']);
        $this->repo->addHistory(['ticket_id'=>$ticketId,'user_id'=>Session::userId(),'action'=>'assigned','new_value'=>$assigneeId]);
        Database::getInstance()->insert('ticket_assignments', ['ticket_id'=>$ticketId,'assigned_by'=>Session::userId(),'assigned_to'=>$assigneeId,'notes'=>$notes]);
        $ticket = $this->repo->findById($ticketId);
        $this->notifyUser($assigneeId, NOTIFY_TICKET_ASSIGNED, 'Ticket Assigned', "You have been assigned ticket {$ticket->ticket_number}", "/tickets/{$ticketId}");
    }

    public function resolveTicket(int $ticketId, ?string $notes = null): void
    {
        $this->repo->update($ticketId, ['status'=>'resolved','resolved_at'=>date('Y-m-d H:i:s'),'resolution_notes'=>$notes]);
        $this->repo->addHistory(['ticket_id'=>$ticketId,'user_id'=>Session::userId(),'action'=>'resolved','new_value'=>$notes]);
    }

    public function closeTicket(int $ticketId): void
    {
        $this->repo->update($ticketId, ['status'=>'closed','closed_at'=>date('Y-m-d H:i:s')]);
        $this->repo->addHistory(['ticket_id'=>$ticketId,'user_id'=>Session::userId(),'action'=>'closed']);
    }

    public function reopenTicket(int $ticketId): void
    {
        $this->repo->update($ticketId, ['status'=>'new','resolved_at'=>null,'closed_at'=>null]);
        $this->repo->addHistory(['ticket_id'=>$ticketId,'user_id'=>Session::userId(),'action'=>'reopened']);
    }

    public function escalateTicket(int $ticketId): void
    {
        $ticket = $this->repo->findById($ticketId);
        $newPriority = match($ticket->priority) { 'low'=>'medium','medium'=>'high','high'=>'critical', default=>'critical' };
        $this->repo->update($ticketId, ['priority'=>$newPriority,'sla_status'=>'warning']);
        $this->repo->addHistory(['ticket_id'=>$ticketId,'user_id'=>Session::userId(),'action'=>'escalated','old_value'=>$ticket->priority,'new_value'=>$newPriority]);
    }

    private function calculateSLA(string $priority): string
    {
        $minutes = $GLOBALS['appConfig']['sla_defaults'][$priority] ?? 480;
        return date('Y-m-d H:i:s', time() + ($minutes * 60));
    }

    private function notify(int $ticketId, string $title, string $message): void
    {
        // Notify relevant users (simplified)
    }

    private function notifyUser(int $userId, string $type, string $title, string $message, string $link = ''): void
    {
        try {
            Database::getInstance()->insert('notifications', ['user_id'=>$userId,'type'=>$type,'title'=>$title,'message'=>$message,'link'=>$link]);
        } catch (\Exception $e) {}
    }
}
