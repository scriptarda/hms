<?php
namespace App\Services;

use App\Repositories\InventoryRepository;

class InventoryService
{
    private InventoryRepository $repo;

    public function __construct()
    {
        $this->repo = new InventoryRepository();
    }

    public function dashboard(): array
    {
        return [
            'metrics' => $this->repo->metrics(),
            'alerts' => array_slice($this->repo->reorderAlerts(), 0, 8),
            'recentTransactions' => $this->repo->transactions(null, 8),
            'purchaseRequests' => array_slice($this->repo->purchaseRequests(['status' => 'submitted']), 0, 8),
            'suppliers' => $this->repo->suppliers(true),
        ];
    }

    public function list(array $filters = []): array
    {
        return $this->repo->items($filters);
    }

    public function detail(int $id): ?array
    {
        $item = $this->repo->findItem($id);
        if (!$item) {
            return null;
        }

        return [
            'item' => $item,
            'transactions' => $this->repo->transactions($id),
            'purchaseRequests' => $this->repo->purchaseRequests(['item_id' => $id]),
            'categories' => $this->repo->categories(),
            'suppliers' => $this->repo->suppliers(true),
        ];
    }

    public function formData(): array
    {
        return [
            'categories' => $this->repo->categories(),
            'suppliers' => $this->repo->suppliers(true),
        ];
    }

    public function create(array $input, int $actorId): array
    {
        $validation = $this->validateItem($input);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->itemPayload($input);
        if ($this->repo->skuExists($data['sku'])) {
            return ['success' => false, 'message' => 'SKU already registered.'];
        }

        $this->repo->beginTransaction();
        try {
            $id = $this->repo->createItem($data);
            if ($data['quantity'] > 0) {
                $this->repo->addTransaction([
                    'item_id' => $id,
                    'type' => 'in',
                    'quantity' => $data['quantity'],
                    'reference_type' => 'initial_stock',
                    'user_id' => $actorId,
                    'notes' => 'Initial stock entry',
                ]);
            }
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to create inventory item: ' . $e->getMessage()];
        }

        return ['success' => true, 'id' => $id, 'message' => 'Inventory item added.'];
    }

    public function update(int $id, array $input): array
    {
        $item = $this->repo->findItem($id);
        if (!$item) {
            return ['success' => false, 'message' => 'Inventory item not found.'];
        }

        $validation = $this->validateItem($input, true);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->itemPayload($input, true);
        if ($this->repo->skuExists($data['sku'], $id)) {
            return ['success' => false, 'message' => 'SKU already registered to another item.'];
        }

        try {
            $this->repo->updateItem($id, $data);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to update inventory item: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Inventory item updated.'];
    }

    public function transaction(int $itemId, array $input, int $actorId): array
    {
        $item = $this->repo->findItem($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Inventory item not found.'];
        }

        $type = $input['type'] ?? '';
        if (!in_array($type, ['in', 'out', 'transfer', 'adjustment', 'return'], true)) {
            return ['success' => false, 'message' => 'Transaction type is invalid.'];
        }

        $qty = (int)($input['quantity'] ?? 0);
        if ($qty <= 0) {
            return ['success' => false, 'message' => 'Quantity must be a positive integer.'];
        }

        $current = (int)$item->quantity;
        $newQty = $current;
        if (in_array($type, ['in', 'return'], true)) {
            $newQty += $qty;
        } elseif ($type === 'adjustment' && ($input['adjustment_mode'] ?? '') === 'set') {
            $newQty = $qty;
        } else {
            if ($qty > $current) {
                return ['success' => false, 'message' => 'Insufficient stock.'];
            }
            $newQty -= $qty;
        }

        $this->repo->beginTransaction();
        try {
            $update = ['quantity' => $newQty];
            if (in_array($type, ['in', 'return'], true)) {
                $update['last_restocked_at'] = date('Y-m-d H:i:s');
            }
            $this->repo->updateItem($itemId, $update);
            $this->repo->addTransaction([
                'item_id' => $itemId,
                'type' => $type,
                'quantity' => $qty,
                'reference_type' => trim($input['reference_type'] ?? ''),
                'reference_id' => !empty($input['reference_id']) ? (int)$input['reference_id'] : null,
                'user_id' => $actorId,
                'notes' => trim($input['notes'] ?? ''),
            ]);
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to process stock transaction: ' . $e->getMessage()];
        }

        if ($newQty <= (int)$item->reorder_level) {
            $this->notifyLowStock($item, $newQty);
        }

        return ['success' => true, 'message' => 'Stock transaction processed.'];
    }

    public function createSupplier(array $input): array
    {
        if (trim($input['name'] ?? '') === '') {
            return ['success' => false, 'message' => 'Supplier name is required.'];
        }

        try {
            $id = $this->repo->createSupplier([
                'name' => trim($input['name']),
                'code' => trim($input['code'] ?? '') ?: null,
                'contact_name' => trim($input['contact_name'] ?? ''),
                'email' => trim($input['email'] ?? ''),
                'phone' => trim($input['phone'] ?? ''),
                'address' => trim($input['address'] ?? ''),
                'lead_time_days' => (int)($input['lead_time_days'] ?? 7),
                'payment_terms' => trim($input['payment_terms'] ?? ''),
                'rating' => isset($input['rating']) && $input['rating'] !== '' ? (float)$input['rating'] : null,
                'is_active' => isset($input['is_active']) ? 1 : 0,
                'notes' => trim($input['notes'] ?? ''),
            ]);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create supplier: ' . $e->getMessage()];
        }

        return ['success' => true, 'id' => $id, 'message' => 'Supplier added.'];
    }

    public function createPurchaseRequest(array $input, int $actorId): array
    {
        $item = $this->repo->findItem((int)($input['item_id'] ?? 0));
        if (!$item) {
            return ['success' => false, 'message' => 'Inventory item is required.'];
        }

        $qty = (int)($input['quantity'] ?? 0);
        if ($qty <= 0) {
            return ['success' => false, 'message' => 'Request quantity must be positive.'];
        }

        $unitCost = isset($input['unit_cost']) && $input['unit_cost'] !== '' ? (float)$input['unit_cost'] : (float)($item->unit_cost ?? 0);

        $status = in_array($input['status'] ?? 'submitted', ['draft', 'submitted', 'approved', 'ordered'], true)
            ? $input['status']
            : 'submitted';

        try {
            $id = $this->repo->createPurchaseRequest([
                'request_number' => $this->repo->generateRequestNumber(),
                'item_id' => (int)$item->id,
                'supplier_id' => !empty($input['supplier_id']) ? (int)$input['supplier_id'] : ($item->supplier_id ?: null),
                'requested_by' => $actorId,
                'status' => $status,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost * $qty,
                'needed_by' => !empty($input['needed_by']) ? $input['needed_by'] : null,
                'submitted_at' => date('Y-m-d H:i:s'),
                'notes' => trim($input['notes'] ?? ''),
            ]);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create purchase request: ' . $e->getMessage()];
        }

        foreach ($this->repo->managers() as $manager) {
            try {
                $this->repo->notify((int)$manager->id, 'purchase_request', 'Purchase Request Submitted', 'Inventory purchase request created for ' . $item->sku, '/inventory/purchase-requests');
            } catch (\Exception $e) {}
        }

        return ['success' => true, 'id' => $id, 'message' => 'Purchase request submitted.'];
    }

    public function updatePurchaseRequestStatus(int $id, string $status, int $actorId): array
    {
        $request = $this->repo->findPurchaseRequest($id);
        if (!$request) {
            return ['success' => false, 'message' => 'Purchase request not found.'];
        }
        if (!in_array($status, ['approved', 'ordered', 'received', 'rejected', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'Purchase request status is invalid.'];
        }

        $data = ['status' => $status];
        if ($status === 'approved') {
            $data['approved_by'] = $actorId;
            $data['approved_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'ordered') {
            $data['ordered_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'received') {
            $data['received_at'] = date('Y-m-d H:i:s');
        }

        $this->repo->beginTransaction();
        try {
            $this->repo->updatePurchaseRequest($id, $data);
            if ($status === 'received') {
                $item = $this->repo->findItem((int)$request->item_id);
                if (!$item) {
                    throw new \RuntimeException('Inventory item not found.');
                }
                $this->repo->updateItem((int)$item->id, [
                    'quantity' => (int)$item->quantity + (int)$request->quantity,
                    'last_restocked_at' => date('Y-m-d H:i:s'),
                ]);
                $this->repo->addTransaction([
                    'item_id' => (int)$item->id,
                    'type' => 'in',
                    'quantity' => (int)$request->quantity,
                    'reference_type' => 'purchase_request',
                    'reference_id' => $id,
                    'user_id' => $actorId,
                    'notes' => 'Received purchase request ' . $request->request_number,
                ]);
            }
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to update purchase request: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Purchase request updated.'];
    }

    public function suppliers(): array
    {
        return $this->repo->suppliers();
    }

    public function purchaseRequests(array $filters = []): array
    {
        return $this->repo->purchaseRequests($filters);
    }

    public function reorderAlerts(): array
    {
        return $this->repo->reorderAlerts();
    }

    public function transactions(?int $itemId = null): array
    {
        return $this->repo->transactions($itemId);
    }

    private function validateItem(array $input, bool $updating = false): array
    {
        foreach (['name', 'sku', 'reorder_level'] as $field) {
            if (trim((string)($input[$field] ?? '')) === '') {
                return ['success' => false, 'message' => ucwords(str_replace('_', ' ', $field)) . ' is required.'];
            }
        }
        if (!$updating && !isset($input['quantity'])) {
            return ['success' => false, 'message' => 'Initial quantity is required.'];
        }
        return ['success' => true];
    }

    private function itemPayload(array $input, bool $updating = false): array
    {
        $data = [
            'name' => trim($input['name']),
            'sku' => trim($input['sku']),
            'category_id' => !empty($input['category_id']) ? (int)$input['category_id'] : null,
            'description' => trim($input['description'] ?? ''),
            'unit' => trim($input['unit'] ?? 'pcs'),
            'min_quantity' => (int)($input['min_quantity'] ?? 0),
            'max_quantity' => (int)($input['max_quantity'] ?? 0),
            'reorder_level' => (int)$input['reorder_level'],
            'reorder_quantity' => (int)($input['reorder_quantity'] ?? 0),
            'unit_cost' => isset($input['unit_cost']) && $input['unit_cost'] !== '' ? (float)$input['unit_cost'] : null,
            'location' => trim($input['location'] ?? ''),
            'supplier_id' => !empty($input['supplier_id']) ? (int)$input['supplier_id'] : null,
            'supplier' => trim($input['supplier'] ?? ''),
            'is_active' => isset($input['is_active']) || !$updating ? 1 : 0,
        ];

        if (!$updating) {
            $data['quantity'] = (int)($input['quantity'] ?? 0);
            if ($data['quantity'] > 0) {
                $data['last_restocked_at'] = date('Y-m-d H:i:s');
            }
        }

        return $data;
    }

    private function notifyLowStock(object $item, int $newQty): void
    {
        foreach ($this->repo->managers() as $manager) {
            try {
                $this->repo->notify(
                    (int)$manager->id,
                    NOTIFY_LOW_STOCK,
                    'Low Stock Alert: ' . $item->sku,
                    "{$item->name} stock is now {$newQty} {$item->unit}; reorder level is {$item->reorder_level}.",
                    '/inventory/' . $item->id
                );
            } catch (\Exception $e) {}
        }
    }
}
