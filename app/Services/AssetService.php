<?php
namespace App\Services;

use App\Repositories\AssetRepository;
use App\Models\Asset;

class AssetService
{
    private AssetRepository $repo;

    public function __construct()
    {
        $this->repo = new AssetRepository();
    }

    public function registry(array $filters = []): array
    {
        return $this->repo->getAll($filters);
    }

    public function metrics(): array
    {
        return $this->repo->getMetrics();
    }

    public function formData(): array
    {
        return [
            'categories' => $this->repo->getCategories(),
            'departments' => $this->repo->getDepartments(),
            'buildings' => $this->repo->getBuildings(),
            'users' => $this->repo->getUsers(),
        ];
    }

    public function detail(int $id): ?array
    {
        $asset = $this->repo->findById($id);
        if (!$asset) {
            return null;
        }

        return [
            'asset' => $asset,
            'assignments' => $this->repo->getAssignments($id),
            'activeAssignment' => $this->repo->getActiveAssignment($id),
            'history' => $this->repo->getHistory($id),
            'tickets' => $this->repo->getTickets($id),
            'maintenance' => $this->repo->getMaintenance($id),
            'users' => $this->repo->getUsers(),
            'warranty' => Asset::warrantyState($asset->warranty_expiry ?? null),
        ];
    }

    public function create(array $input, int $actorId): array
    {
        $validation = $this->validate($input);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->payload($input);
        if ($this->repo->assetTagExists($data['asset_tag'])) {
            return ['success' => false, 'message' => 'Asset tag already registered.'];
        }

        $assignedUserId = (int)($input['assigned_user_id'] ?? 0);

        $this->repo->beginTransaction();
        try {
            $id = $this->repo->create($data);
            $this->repo->addHistory($id, $actorId, 'registered', 'Asset registered in system');

            if ($assignedUserId > 0) {
                $assigned = $this->assign($id, $assignedUserId, $actorId, trim($input['assignment_notes'] ?? 'Initial assignment'), false);
                if (!$assigned['success']) {
                    throw new \RuntimeException($assigned['message']);
                }
            }

            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to create asset: ' . $e->getMessage()];
        }

        return ['success' => true, 'id' => $id, 'message' => 'Asset registered successfully.'];
    }

    public function update(int $id, array $input, int $actorId): array
    {
        $asset = $this->repo->findRaw($id);
        if (!$asset) {
            return ['success' => false, 'message' => 'Asset not found.'];
        }

        $validation = $this->validate($input);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->payload($input);
        if ($this->repo->assetTagExists($data['asset_tag'], $id)) {
            return ['success' => false, 'message' => 'Asset tag already registered to another asset.'];
        }

        $assignedUserId = (int)($input['assigned_user_id'] ?? 0);
        $activeAssignment = $this->repo->getActiveAssignment($id);

        $this->repo->beginTransaction();
        try {
            $this->repo->update($id, $data);
            $this->repo->addHistory($id, $actorId, 'updated', 'Asset details updated');

            if ($assignedUserId > 0 && (!$activeAssignment || (int)$activeAssignment->user_id !== $assignedUserId)) {
                $assigned = $this->assign($id, $assignedUserId, $actorId, trim($input['assignment_notes'] ?? 'Assignment updated from edit form'), false);
                if (!$assigned['success']) {
                    throw new \RuntimeException($assigned['message']);
                }
            } elseif ($assignedUserId === 0 && $activeAssignment && !empty($input['clear_assignment'])) {
                $returned = $this->returnAssignment($id, $actorId, 'Assignment cleared from edit form', false);
                if (!$returned['success']) {
                    throw new \RuntimeException($returned['message']);
                }
            }

            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to update asset: ' . $e->getMessage()];
        }

        return ['success' => true, 'id' => $id, 'message' => 'Asset details updated.'];
    }

    public function delete(int $id, int $actorId): array
    {
        $asset = $this->repo->findRaw($id);
        if (!$asset) {
            return ['success' => false, 'message' => 'Asset not found.'];
        }

        $this->repo->beginTransaction();
        try {
            $this->repo->closeActiveAssignments($id);
            $this->repo->addHistory($id, $actorId, 'deleted', 'Asset soft-deleted from registry');
            $this->repo->softDelete($id);
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to delete asset: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Asset removed from registry.'];
    }

    public function assign(int $assetId, int $userId, int $actorId, string $notes = '', bool $transaction = true): array
    {
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Assigned user is required.'];
        }

        if (!$this->repo->findRaw($assetId)) {
            return ['success' => false, 'message' => 'Asset not found.'];
        }

        if ($transaction) {
            $this->repo->beginTransaction();
        }

        try {
            $this->repo->closeActiveAssignments($assetId);
            $this->repo->createAssignment($assetId, $userId, $actorId, $notes);

            $userName = $this->userName($userId);
            $this->repo->addHistory($assetId, $actorId, 'assigned', 'Asset assigned to ' . $userName . ($notes ? '. Notes: ' . $notes : ''));

            if ($transaction) {
                $this->repo->commit();
            }
        } catch (\Exception $e) {
            if ($transaction) {
                $this->repo->rollback();
            }
            return ['success' => false, 'message' => 'Failed to assign asset: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Asset assigned successfully.'];
    }

    public function returnAssignment(int $assetId, int $actorId, string $notes = '', bool $transaction = true): array
    {
        $active = $this->repo->getActiveAssignment($assetId);
        if (!$active) {
            return ['success' => false, 'message' => 'Asset has no active assignment.'];
        }

        if ($transaction) {
            $this->repo->beginTransaction();
        }

        try {
            $this->repo->closeActiveAssignments($assetId);
            $this->repo->addHistory($assetId, $actorId, 'returned', 'Asset returned from ' . $active->user_name . ($notes ? '. Notes: ' . $notes : ''));
            if ($transaction) {
                $this->repo->commit();
            }
        } catch (\Exception $e) {
            if ($transaction) {
                $this->repo->rollback();
            }
            return ['success' => false, 'message' => 'Failed to return asset: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Asset returned successfully.'];
    }

    public function warrantyAlerts(int $days = 90): array
    {
        return $this->repo->getWarrantyAlerts($days);
    }

    public function qrPayload(int $assetId, string $url): ?array
    {
        $asset = $this->repo->findById($assetId);
        if (!$asset) {
            return null;
        }

        return [
            'asset_id' => $asset->id,
            'asset_tag' => $asset->asset_tag,
            'name' => $asset->name,
            'serial_number' => $asset->serial_number,
            'manufacturer' => $asset->manufacturer,
            'model' => $asset->model,
            'department' => $asset->department_name,
            'assigned_user' => $asset->assigned_user_name,
            'scan_url' => $url,
        ];
    }

    private function validate(array $input): array
    {
        if (trim($input['asset_tag'] ?? '') === '') {
            return ['success' => false, 'message' => 'Asset tag is required.'];
        }
        if (trim($input['name'] ?? '') === '') {
            return ['success' => false, 'message' => 'Asset name is required.'];
        }
        if (!in_array($input['status'] ?? 'active', ['active', 'inactive', 'maintenance', 'retired', 'disposed'], true)) {
            return ['success' => false, 'message' => 'Asset status is not valid.'];
        }
        return ['success' => true];
    }

    private function payload(array $input): array
    {
        return [
            'asset_tag' => trim($input['asset_tag']),
            'name' => trim($input['name']),
            'serial_number' => trim($input['serial_number'] ?? ''),
            'category_id' => !empty($input['category_id']) ? (int)$input['category_id'] : null,
            'manufacturer' => trim($input['manufacturer'] ?? ''),
            'model' => trim($input['model'] ?? ''),
            'department_id' => !empty($input['department_id']) ? (int)$input['department_id'] : null,
            'building_id' => !empty($input['building_id']) ? (int)$input['building_id'] : null,
            'floor_id' => !empty($input['floor_id']) ? (int)$input['floor_id'] : null,
            'room_id' => !empty($input['room_id']) ? (int)$input['room_id'] : null,
            'status' => $input['status'] ?? 'active',
            'purchase_date' => !empty($input['purchase_date']) ? $input['purchase_date'] : null,
            'purchase_cost' => isset($input['purchase_cost']) && $input['purchase_cost'] !== '' ? $input['purchase_cost'] : null,
            'warranty_expiry' => !empty($input['warranty_expiry']) ? $input['warranty_expiry'] : null,
            'notes' => trim($input['notes'] ?? ''),
        ];
    }

    private function userName(int $userId): string
    {
        foreach ($this->repo->getUsers() as $user) {
            if ((int)$user->id === $userId) {
                return trim($user->first_name . ' ' . $user->last_name);
            }
        }

        return 'user #' . $userId;
    }
}
