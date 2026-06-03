<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Helpers\Database;

class InventoryController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $category = $_GET['category_id'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "SELECT i.*, ic.name as category_name
                FROM inventory_items i
                LEFT JOIN inventory_categories ic ON i.category_id = ic.id
                WHERE i.deleted_at IS NULL";
        
        $params = [];
        if ($category) { $sql .= " AND i.category_id = ?"; $params[] = $category; }
        if ($search) {
            $sql .= " AND (i.name LIKE ? OR i.sku LIKE ? OR i.location LIKE ?)";
            $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY i.name ASC";
        $items = $this->db->fetchAll($sql, $params);

        // Compute metrics
        $totalItems = count($items);
        $lowStock = 0;
        $outOfStock = 0;
        foreach ($items as $item) {
            if ($item->quantity == 0) {
                $outOfStock++;
            } elseif ($item->quantity <= $item->reorder_level) {
                $lowStock++;
            }
        }

        $categories = $this->db->fetchAll("SELECT * FROM inventory_categories WHERE deleted_at IS NULL ORDER BY name");

        $this->view('inventory/index', [
            'pageTitle' => 'Inventory Control',
            'items' => $items,
            'categories' => $categories,
            'metrics' => [
                'total' => $totalItems,
                'low' => $lowStock,
                'out' => $outOfStock
            ],
            'filters' => ['category_id' => $category, 'search' => $search]
        ]);
    }

    public function create(): void
    {
        $categories = $this->db->fetchAll("SELECT * FROM inventory_categories WHERE deleted_at IS NULL ORDER BY name");
        $this->view('inventory/create', [
            'pageTitle' => 'Add Inventory Item',
            'categories' => $categories
        ]);
    }

    public function store(): void
    {
        $v = new Validator($_POST);
        $v->required('name')->required('sku')->required('quantity')->required('reorder_level');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/inventory/create');
        }

        // Verify SKU uniqueness
        $exists = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = ? AND deleted_at IS NULL", [trim($_POST['sku'])]);
        if ($exists) {
            Session::flash('error', 'SKU already registered.');
            $this->redirect('/inventory/create');
        }

        $data = [
            'name' => trim($_POST['name']),
            'sku' => trim($_POST['sku']),
            'category_id' => $_POST['category_id'] ?: null,
            'description' => trim($_POST['description'] ?? ''),
            'unit' => trim($_POST['unit'] ?? 'pcs'),
            'quantity' => (int)$_POST['quantity'],
            'min_quantity' => (int)($_POST['min_quantity'] ?? 0),
            'max_quantity' => (int)($_POST['max_quantity'] ?? 0),
            'reorder_level' => (int)$_POST['reorder_level'],
            'unit_cost' => $_POST['unit_cost'] ?: null,
            'location' => trim($_POST['location'] ?? ''),
            'supplier' => trim($_POST['supplier'] ?? ''),
            'is_active' => 1
        ];

        $id = $this->db->insert('inventory_items', $data);

        // Add initial transaction if quantity > 0
        if ($data['quantity'] > 0) {
            $this->db->insert('inventory_transactions', [
                'item_id' => $id,
                'type' => 'in',
                'quantity' => $data['quantity'],
                'notes' => 'Initial stock seeding',
                'user_id' => Session::userId()
            ]);
        }

        Session::flash('success', 'Inventory item added successfully.');
        $this->redirect('/inventory/' . $id);
    }

    public function show(string $id): void
    {
        $item = $this->db->fetch(
            "SELECT i.*, ic.name as category_name
             FROM inventory_items i
             LEFT JOIN inventory_categories ic ON i.category_id = ic.id
             WHERE i.id = ? AND i.deleted_at IS NULL",
            [(int)$id]
        );

        if (!$item) $this->abort(404);

        $transactions = $this->db->fetchAll(
            "SELECT it.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM inventory_transactions it
             JOIN users u ON it.user_id = u.id
             WHERE it.item_id = ? ORDER BY it.created_at DESC",
            [(int)$id]
        );

        $this->view('inventory/show', [
            'pageTitle' => 'Item: ' . $item->sku,
            'item' => $item,
            'transactions' => $transactions
        ]);
    }

    public function edit(string $id): void
    {
        $item = $this->db->fetch("SELECT * FROM inventory_items WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$item) $this->abort(404);

        $categories = $this->db->fetchAll("SELECT * FROM inventory_categories WHERE deleted_at IS NULL ORDER BY name");
        $this->view('inventory/edit', [
            'pageTitle' => 'Edit Item ' . $item->sku,
            'item' => $item,
            'categories' => $categories
        ]);
    }

    public function update(string $id): void
    {
        $item = $this->db->fetch("SELECT * FROM inventory_items WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$item) $this->abort(404);

        $v = new Validator($_POST);
        $v->required('name')->required('sku')->required('reorder_level');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/inventory/' . $id . '/edit');
        }

        // Verify SKU uniqueness excluding self
        $exists = $this->db->fetch("SELECT id FROM inventory_items WHERE sku = ? AND id != ? AND deleted_at IS NULL", [trim($_POST['sku']), (int)$id]);
        if ($exists) {
            Session::flash('error', 'SKU already registered to another item.');
            $this->redirect('/inventory/' . $id . '/edit');
        }

        $data = [
            'name' => trim($_POST['name']),
            'sku' => trim($_POST['sku']),
            'category_id' => $_POST['category_id'] ?: null,
            'description' => trim($_POST['description'] ?? ''),
            'unit' => trim($_POST['unit'] ?? 'pcs'),
            'min_quantity' => (int)($_POST['min_quantity'] ?? 0),
            'max_quantity' => (int)($_POST['max_quantity'] ?? 0),
            'reorder_level' => (int)$_POST['reorder_level'],
            'unit_cost' => $_POST['unit_cost'] ?: null,
            'location' => trim($_POST['location'] ?? ''),
            'supplier' => trim($_POST['supplier'] ?? ''),
        ];

        $this->db->update('inventory_items', $data, 'id = ?', [(int)$id]);

        Session::flash('success', 'Inventory item details updated.');
        $this->redirect('/inventory/' . $id);
    }

    public function addTransaction(string $id): void
    {
        $item = $this->db->fetch("SELECT * FROM inventory_items WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$item) $this->abort(404);

        $v = new Validator($_POST);
        $v->required('type')->required('quantity');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/inventory/' . $id);
        }

        $type = $_POST['type']; // in, out, transfer, adjustment, return
        $qty = (int)$_POST['quantity'];
        $notes = trim($_POST['notes'] ?? '');

        if ($qty <= 0) {
            Session::flash('error', 'Quantity must be a positive integer.');
            $this->redirect('/inventory/' . $id);
        }

        $newQty = $item->quantity;
        if ($type === 'in' || $type === 'return') {
            $newQty += $qty;
        } elseif ($type === 'out' || $type === 'transfer' || $type === 'adjustment') {
            if ($qty > $item->quantity) {
                Session::flash('error', 'Insufficient stock level. Cannot subtract more than available.');
                $this->redirect('/inventory/' . $id);
            }
            $newQty -= $qty;
        }

        $this->db->beginTransaction();
        try {
            // Update quantity
            $this->db->update('inventory_items', ['quantity' => $newQty], 'id = ?', [$item->id]);

            // Insert transaction
            $this->db->insert('inventory_transactions', [
                'item_id' => $item->id,
                'type' => $type,
                'quantity' => $qty,
                'notes' => $notes,
                'user_id' => Session::userId()
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            Session::flash('error', 'Failed to update transaction: ' . $e->getMessage());
            $this->redirect('/inventory/' . $id);
        }

        // Trigger stock alert notification if below reorder level
        if ($newQty <= $item->reorder_level) {
            try {
                // Notify Super admin / managers
                $managers = $this->db->fetchAll(
                    "SELECT u.id FROM users u 
                     JOIN user_roles ur ON u.id = ur.user_id 
                     JOIN roles r ON ur.role_id = r.id 
                     WHERE r.slug IN ('administrator', 'super_administrator', 'manager')"
                );
                foreach ($managers as $m) {
                    $this->db->insert('notifications', [
                        'user_id' => $m->id,
                        'type' => 'low_stock',
                        'title' => 'Low Stock Alert: ' . $item->sku,
                        'message' => "Stock level for {$item->name} has dropped to {$newQty} {$item->unit} (min limit: {$item->reorder_level}).",
                        'link' => '/inventory/' . $item->id
                    ]);
                }
            } catch (\Exception $e) {}
        }

        Session::flash('success', 'Stock transaction processed.');
        $this->redirect('/inventory/' . $id);
    }

    public function dataList(): void
    {
        $items = $this->db->fetchAll("SELECT id, name, sku, quantity, reorder_level, location FROM inventory_items WHERE deleted_at IS NULL");
        $this->json(['data' => $items]);
    }
}
