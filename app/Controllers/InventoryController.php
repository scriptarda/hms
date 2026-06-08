<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Services\InventoryService;

class InventoryController extends BaseController
{
    private InventoryService $service;

    public function __construct()
    {
        $this->service = new InventoryService();
    }

    public function index(): void
    {
        $this->view('inventory/index', array_merge(
            ['pageTitle' => 'Inventory Dashboard'],
            $this->service->dashboard()
        ));
    }

    public function items(): void
    {
        $this->view('inventory/items', [
            'pageTitle' => 'Inventory List',
            'items' => $this->service->list($this->filters()),
            'filters' => $this->filters(),
        ] + $this->service->formData());
    }

    public function create(): void
    {
        $this->view('inventory/create', ['pageTitle' => 'Add Inventory Item'] + $this->service->formData());
    }

    public function store(): void
    {
        $result = $this->service->create($_POST, (int)Session::userId());
        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/inventory/create');
        }

        Session::flash('success', $result['message']);
        $this->redirect('/inventory/' . $result['id']);
    }

    public function show(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $bundle['pageTitle'] = 'Item: ' . $bundle['item']->sku;
        $this->view('inventory/show', $bundle);
    }

    public function edit(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $this->view('inventory/edit', [
            'pageTitle' => 'Edit Item ' . $bundle['item']->sku,
            'item' => $bundle['item'],
        ] + $this->service->formData());
    }

    public function update(string $id): void
    {
        $result = $this->service->update((int)$id, $_POST);
        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/inventory/' . $id . '/edit');
        }

        Session::flash('success', $result['message']);
        $this->redirect('/inventory/' . $id);
    }

    public function addTransaction(string $id): void
    {
        $result = $this->service->transaction((int)$id, $_POST, (int)Session::userId());
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/inventory/' . $id);
    }

    public function transactions(): void
    {
        $this->view('inventory/transactions', [
            'pageTitle' => 'Inventory Transactions',
            'transactions' => $this->service->transactions(),
        ]);
    }

    public function reorderAlerts(): void
    {
        $this->view('inventory/reorder_alerts', [
            'pageTitle' => 'Reorder Alerts',
            'items' => $this->service->reorderAlerts(),
        ]);
    }

    public function suppliers(): void
    {
        $this->view('inventory/suppliers', [
            'pageTitle' => 'Supplier Tracking',
            'suppliers' => $this->service->suppliers(),
        ]);
    }

    public function storeSupplier(): void
    {
        $result = $this->service->createSupplier($_POST);
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/inventory/suppliers');
    }

    public function purchaseRequests(): void
    {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'item_id' => $_GET['item_id'] ?? '',
        ];
        $this->view('inventory/purchase_requests', [
            'pageTitle' => 'Purchase Requests',
            'requests' => $this->service->purchaseRequests($filters),
            'filters' => $filters,
        ] + $this->service->formData() + ['items' => $this->service->list([])]);
    }

    public function storePurchaseRequest(): void
    {
        $result = $this->service->createPurchaseRequest($_POST, (int)Session::userId());
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/inventory/purchase-requests');
    }

    public function updatePurchaseRequestStatus(string $id): void
    {
        $result = $this->service->updatePurchaseRequestStatus((int)$id, $_POST['status'] ?? '', (int)Session::userId());
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/inventory/purchase-requests');
    }

    public function dataList(): void
    {
        $this->apiItems();
    }

    public function apiDashboard(): void
    {
        $this->json($this->service->dashboard());
    }

    public function apiItems(): void
    {
        $this->json(['data' => $this->service->list($this->filters())]);
    }

    public function apiShow(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->json(['success' => false, 'message' => 'Inventory item not found.'], 404);
        }
        $this->json(['success' => true] + $bundle);
    }

    public function apiTransactions(): void
    {
        $this->json(['data' => $this->service->transactions()]);
    }

    public function apiReorderAlerts(): void
    {
        $this->json(['data' => $this->service->reorderAlerts()]);
    }

    public function apiSuppliers(): void
    {
        $this->json(['data' => $this->service->suppliers()]);
    }

    public function apiPurchaseRequests(): void
    {
        $this->json(['data' => $this->service->purchaseRequests(['status' => $_GET['status'] ?? ''])]);
    }

    private function filters(): array
    {
        return [
            'category_id' => $_GET['category_id'] ?? '',
            'supplier_id' => $_GET['supplier_id'] ?? '',
            'stock' => $_GET['stock'] ?? '',
            'search' => trim($_GET['search'] ?? ''),
        ];
    }
}
