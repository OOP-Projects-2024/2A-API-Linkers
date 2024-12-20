<?php
class Delete extends Common {
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

    public function deleteLandlord($id) {
        try {
            if (!$this->validateRecordExists('Landlord', $id)) {
                return $this->generateResponse(null, "failed", "Landlord not found", 404);
            }

            $result = $this->deleteData('Landlord', $id, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "DELETE", "Deleted landlord ID: $id");
                return $this->generateResponse(null, "success", "Landlord deleted successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error deleting landlord: " . $e->getMessage(), 400);
        }
    }

    public function deleteTenant($id) {
        try {
            if (!$this->validateRecordExists('Tenant', $id)) {
                return $this->generateResponse(null, "failed", "Tenant not found", 404);
            }

            $result = $this->deleteData('Tenant', $id, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "DELETE", "Deleted tenant ID: $id");
                return $this->generateResponse(null, "success", "Tenant deleted successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error deleting tenant: " . $e->getMessage(), 400);
        }
    }

    public function deleteApartment($id) {
        try {
            if (!$this->validateRecordExists('Apartment', $id)) {
                return $this->generateResponse(null, "failed", "Apartment not found", 404);
            }

            $result = $this->deleteData('Apartment', $id, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "DELETE", "Deleted apartment ID: $id");
                return $this->generateResponse(null, "success", "Apartment deleted successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error deleting apartment: " . $e->getMessage(), 400);
        }
    }

    public function deleteLease($id) {
        try {
            if (!$this->validateRecordExists('Lease', $id)) {
                return $this->generateResponse(null, "failed", "Lease not found", 404);
            }

            $result = $this->deleteData('Lease', $id, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "DELETE", "Deleted lease ID: $id");
                return $this->generateResponse(null, "success", "Lease deleted successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error deleting lease: " . $e->getMessage(), 400);
        }
    }

    public function deletePayment($id) {
        try {
            if (!$this->validateRecordExists('Payment', $id)) {
                return $this->generateResponse(null, "failed", "Payment not found", 404);
            }

            $result = $this->deleteData('Payment', $id, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "DELETE", "Deleted payment ID: $id");
                return $this->generateResponse(null, "success", "Payment deleted successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error deleting payment: " . $e->getMessage(), 400);
        }
    }

    public function deleteCommunication($id) {
        try {
            if (!$this->validateRecordExists('Communication', $id)) {
                return $this->generateResponse(null, "failed", "Communication not found", 404);
            }

            $result = $this->deleteData('Communication', $id, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "DELETE", "Deleted communication ID: $id");
                return $this->generateResponse(null, "success", "Communication deleted successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error deleting communication: " . $e->getMessage(), 400);
        }
    }

    public function deleteIssue($id) {
        try {
            if (!$this->validateRecordExists('Issue', $id)) {
                return $this->generateResponse(null, "failed", "Issue not found", 404);
            }

            $result = $this->deleteData('Issue', $id, $this->pdo);
            if ($result['code'] === 200) {
                $this->logger("SYSTEM", "DELETE", "Deleted issue ID: $id");
                return $this->generateResponse(null, "success", "Issue deleted successfully", 200);
            }
            return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error deleting issue: " . $e->getMessage(), 400);
        }
    }
}
?>