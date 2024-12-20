<?php
class Patch extends Common {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    private function validateRecordExists($table, $id) {
        $sql = "SELECT COUNT(*) FROM $table WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }

    public function patchCommunication($id, $data) {
        try {
            if (!$this->validateRecordExists('Communication', $id)) {
                return $this->generateResponse(null, "failed", "Communication not found", 404);
            }
    
            $fields = ['sender_id', 'receiver_id', 'message', 'is_read'];
            $updateData = array_intersect_key($data, array_flip($fields));
    
            if (empty($updateData)) {
                return $this->generateResponse(null, "failed", "No valid fields to update", 400);
            }
    
            $result = $this->updateData('Communication', $id, $updateData, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "PATCH", "Updated communication ID: $id");
                return $this->generateResponse(null, "success", "Communication updated successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error updating communication: " . $e->getMessage(), 400);
        }
    }

    public function patchLandlord($id, $data) {
        try {
            if (!$this->validateRecordExists('Landlord', $id)) {
                return $this->generateResponse(null, "failed", "Landlord not found", 404);
            }

            $fields = ['first_name', 'last_name', 'middle_initial', 'email', 'contact_number', 'age', 'sex'];
            $updateData = array_intersect_key($data, array_flip($fields));
            
            if (empty($updateData)) {
                return $this->generateResponse(null, "failed", "No valid fields to update", 400);
            }

            $result = $this->updateData('Landlord', $id, $updateData, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "PATCH", "Updated landlord ID: $id");
                return $this->generateResponse(null, "success", "Landlord updated successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error updating landlord: " . $e->getMessage(), 400);
        }
    }

    public function patchTenant($id, $data) {
        try {
            if (!$this->validateRecordExists('Tenant', $id)) {
                return $this->generateResponse(null, "failed", "Tenant not found", 404);
            }

            $fields = ['first_name', 'last_name', 'middle_initial', 'email', 'contact_number', 'age', 'sex'];
            $updateData = array_intersect_key($data, array_flip($fields));

            if (empty($updateData)) {
                return $this->generateResponse(null, "failed", "No valid fields to update", 400);
            }

            $result = $this->updateData('Tenant', $id, $updateData, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "PATCH", "Updated tenant ID: $id");
                return $this->generateResponse(null, "success", "Tenant updated successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error updating tenant: " . $e->getMessage(), 400);
        }
    }

    public function patchApartment($id, $data) {
        try {
            if (!$this->validateRecordExists('Apartment', $id)) {
                return $this->generateResponse(null, "failed", "Apartment not found", 404);
            }

            $fields = ['name', 'location', 'price', 'availability', 'ratings', 'landlord_id'];
            $updateData = array_intersect_key($data, array_flip($fields));

            if (empty($updateData)) {
                return $this->generateResponse(null, "failed", "No valid fields to update", 400);
            }

            $result = $this->updateData('Apartment', $id, $updateData, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "PATCH", "Updated apartment ID: $id");
                return $this->generateResponse(null, "success", "Apartment updated successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error updating apartment: " . $e->getMessage(), 400);
        }
    }

    public function patchLease($id, $data) {
        try {
            if (!$this->validateRecordExists('Lease', $id)) {
                return $this->generateResponse(null, "failed", "Lease not found", 404);
            }

            $fields = ['tenant_id', 'apartment_id', 'start_date', 'end_date', 'monthly_rent'];
            $updateData = array_intersect_key($data, array_flip($fields));

            if (empty($updateData)) {
                return $this->generateResponse(null, "failed", "No valid fields to update", 400);
            }

            $result = $this->updateData('Lease', $id, $updateData, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "PATCH", "Updated lease ID: $id");
                return $this->generateResponse(null, "success", "Lease updated successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error updating lease: " . $e->getMessage(), 400);
        }
    }

    public function patchPayment($id, $data) {
        try {
            if (!$this->validateRecordExists('Payment', $id)) {
                return $this->generateResponse(null, "failed", "Payment not found", 404);
            }

            $fields = ['lease_id', 'payment_date', 'amount_paid', 'payment_method', 'status'];
            $updateData = array_intersect_key($data, array_flip($fields));

            if (empty($updateData)) {
                return $this->generateResponse(null, "failed", "No valid fields to update", 400);
            }

            $result = $this->updateData('Payment', $id, $updateData, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "PATCH", "Updated payment ID: $id");
                return $this->generateResponse(null, "success", "Payment updated successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error updating payment: " . $e->getMessage(), 400);
        }
    }

    public function patchIssue($id, $data) {
        try {
            if (!$this->validateRecordExists('Issue', $id)) {
                return $this->generateResponse(null, "failed", "Issue not found", 404);
            }

            $fields = ['tenant_id', 'apartment_id', 'description', 'status'];
            $updateData = array_intersect_key($data, array_flip($fields));

            if (empty($updateData)) {
                return $this->generateResponse(null, "failed", "No valid fields to update", 400);
            }

            $result = $this->updateData('Issue', $id, $updateData, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "PATCH", "Updated issue ID: $id");
                return $this->generateResponse(null, "success", "Issue updated successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error updating issue: " . $e->getMessage(), 400);
        }
    }
}
?>