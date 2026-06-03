<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Helpers\Database;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Services\TicketService;

class TicketController extends BaseController
{
    private TicketRepository $repo;
    private TicketService $service;
    public function __construct() { $this->repo = new TicketRepository(); $this->service = new TicketService(); }

    public function index(): void
    {
        $filters = ['status'=>$_GET['status']??'','priority'=>$_GET['priority']??'','search'=>$_GET['search']??''];
        $page = max(1, (int)($_GET['page']??1)); $perPage = 25;
        $tickets = $this->repo->getAll($filters, $perPage, ($page-1)*$perPage);
        $total = $this->repo->count($filters);
        $categories = $this->repo->getCategories();
        $this->view('tickets/index', ['pageTitle'=>'Incidents','tickets'=>$tickets,'total'=>$total,'page'=>$page,'perPage'=>$perPage,'filters'=>$filters,'categories'=>$categories]);
    }

    public function create(): void
    {
        $categories = $this->repo->getCategories();
        $departments = Database::getInstance()->fetchAll("SELECT id,name FROM departments WHERE deleted_at IS NULL ORDER BY name");
        $buildings = Database::getInstance()->fetchAll("SELECT id,name FROM buildings WHERE deleted_at IS NULL ORDER BY name");
        $assets = Database::getInstance()->fetchAll("SELECT id,asset_tag,name FROM assets WHERE deleted_at IS NULL ORDER BY name");
        $technicians = (new UserRepository())->getTechnicians();
        $this->view('tickets/create', ['pageTitle'=>'Create Incident','categories'=>$categories,'departments'=>$departments,'buildings'=>$buildings,'assets'=>$assets,'technicians'=>$technicians]);
    }

    public function store(): void
    {
        $v = new Validator($_POST);
        $v->required('title')->required('description')->required('priority');
        if($v->fails()){Session::flash('error',$v->firstError());$this->redirect('/tickets/create');}
        $data = ['title'=>$_POST['title'],'description'=>$_POST['description'],'priority'=>$_POST['priority'],'category_id'=>$_POST['category_id']?:null,'subcategory_id'=>$_POST['subcategory_id']?:null,'department_id'=>$_POST['department_id']?:null,'building_id'=>$_POST['building_id']?:null,'asset_id'=>$_POST['asset_id']?:null];
        if(!empty($_POST['assigned_to'])){$data['assigned_to']=$_POST['assigned_to'];$data['status']='assigned';}
        $id = $this->service->createTicket($data);
        if(isset($_FILES['attachment'])&&$_FILES['attachment']['error']===UPLOAD_ERR_OK){
            $path=$this->uploadFile('attachment','tickets');
            $this->repo->addAttachment(['ticket_id'=>$id,'user_id'=>Session::userId(),'file_name'=>$_FILES['attachment']['name'],'file_path'=>$path,'file_size'=>$_FILES['attachment']['size'],'file_type'=>$_FILES['attachment']['type']]);
        }
        Session::flash('success','Ticket created successfully.');$this->redirect('/tickets/'.$id);
    }

    public function show(string $id): void
    {
        $ticket = $this->repo->findById((int)$id);
        if(!$ticket) $this->abort(404);
        $comments = $this->repo->getComments((int)$id);
        $attachments = $this->repo->getAttachments((int)$id);
        $history = $this->repo->getHistory((int)$id);
        $technicians = (new UserRepository())->getTechnicians();
        $this->view('tickets/show', ['pageTitle'=>'Ticket #'.$ticket->ticket_number,'ticket'=>$ticket,'comments'=>$comments,'attachments'=>$attachments,'history'=>$history,'technicians'=>$technicians]);
    }

    public function edit(string $id): void
    {
        $ticket = $this->repo->findById((int)$id);
        if(!$ticket) $this->abort(404);
        $categories = $this->repo->getCategories();
        $departments = Database::getInstance()->fetchAll("SELECT id,name FROM departments WHERE deleted_at IS NULL ORDER BY name");
        $buildings = Database::getInstance()->fetchAll("SELECT id,name FROM buildings WHERE deleted_at IS NULL ORDER BY name");
        $assets = Database::getInstance()->fetchAll("SELECT id,asset_tag,name FROM assets WHERE deleted_at IS NULL ORDER BY name");
        $this->view('tickets/edit', ['pageTitle'=>'Edit Ticket','ticket'=>$ticket,'categories'=>$categories,'departments'=>$departments,'buildings'=>$buildings,'assets'=>$assets]);
    }

    public function update(string $id): void
    {
        $data = ['title'=>$_POST['title'],'description'=>$_POST['description'],'priority'=>$_POST['priority'],'category_id'=>$_POST['category_id']?:null,'department_id'=>$_POST['department_id']?:null,'asset_id'=>$_POST['asset_id']?:null];
        $this->repo->update((int)$id, $data);
        $this->repo->addHistory(['ticket_id'=>(int)$id,'user_id'=>Session::userId(),'action'=>'updated']);
        Session::flash('success','Ticket updated.');$this->redirect('/tickets/'.$id);
    }

    public function assign(string $id): void
    {
        $this->service->assignTicket((int)$id, (int)$_POST['assigned_to'], $_POST['notes']??null);
        Session::flash('success','Ticket assigned.');$this->redirect('/tickets/'.$id);
    }
    public function escalate(string $id): void { $this->service->escalateTicket((int)$id); Session::flash('success','Ticket escalated.');$this->redirect('/tickets/'.$id); }
    public function resolve(string $id): void { $this->service->resolveTicket((int)$id, $_POST['resolution_notes']??null); Session::flash('success','Ticket resolved.');$this->redirect('/tickets/'.$id); }
    public function close(string $id): void { $this->service->closeTicket((int)$id); Session::flash('success','Ticket closed.');$this->redirect('/tickets/'.$id); }
    public function reopen(string $id): void { $this->service->reopenTicket((int)$id); Session::flash('success','Ticket reopened.');$this->redirect('/tickets/'.$id); }

    public function addComment(string $id): void
    {
        $this->repo->addComment(['ticket_id'=>(int)$id,'user_id'=>Session::userId(),'comment'=>$_POST['comment'],'is_internal'=>isset($_POST['is_internal'])?1:0]);
        $this->repo->addHistory(['ticket_id'=>(int)$id,'user_id'=>Session::userId(),'action'=>'commented']);
        Session::flash('success','Comment added.');$this->redirect('/tickets/'.$id);
    }

    public function dataList(): void { $this->json(['data'=>$this->repo->getAll([], 100)]); }
}
