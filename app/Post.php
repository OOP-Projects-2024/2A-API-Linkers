<?php
class Post extends Common {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function postLandlord($body) {
        try {
            return $this->postData('Landlord', $body, $this->pdo);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error creating landlord: " . $e->getMessage(), 400);
        }
    }

    public function postTenant($body) {
        try {
            return $this->postData('Tenant', $body, $this->pdo);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error creating tenant: " . $e->getMessage(), 400);
        }
    }

    public function postApartment($body) {
        try {
            // Validate required fields
            $required = ['name', 'location', 'price', 'availability', 'landlord_id'];
            foreach ($required as $field) {
                if (!isset($body[$field])) {
                    return $this->generateResponse(null, "failed", "Missing required field: $field", 400);
                }
            }

            // Validate availability enum
            $validAvailability = ['Available', 'Occupied', 'Under Maintenance'];
            if (!in_array($body['availability'], $validAvailability)) {
                return $this->generateResponse(null, "failed", "Invalid availability status", 400);
            }

            // Validate landlord exists
            $stmt = $this->pdo->prepare("SELECT id FROM Landlord WHERE id = ?");
            $stmt->execute([$body['landlord_id']]);
            if ($stmt->rowCount() === 0) {
                return $this->generateResponse(null, "failed", "Landlord not found", 404);
            }

            // Insert apartment
            $result = $this->postData('Apartment', $body, $this->pdo);
            
            if ($result['code'] === 200) {
                // Get the inserted apartment details
                $lastId = $this->pdo->lastInsertId();
                $stmt = $this->pdo->prepare("SELECT * FROM Apartment WHERE id = ?");
                $stmt->execute([$lastId]);
                $apartment = $stmt->fetch(PDO::FETCH_ASSOC);

                return $this->generateResponse(
                    $apartment,
                    "success",
                    "Apartment created successfully",
                    201
                );
            }

            return $this->generateResponse(null, "failed", "Failed to create apartment", 400);
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error creating apartment: " . $e->getMessage(), 400);
        }
    }

    public function postLease($body) {
        try {
            // Validate required fields
            $required = ['tenant_id', 'apartment_id', 'start_date', 'end_date', 'monthly_rent'];
            foreach ($required as $field) {
                if (!isset($body[$field])) {
                    return $this->generateResponse(null, "failed", "Missing required field: $field", 400);
                }
            }

            // Verify tenant exists
            $tenantStmt = $this->pdo->prepare("SELECT id FROM Tenant WHERE id = ?");
            $tenantStmt->execute([$body['tenant_id']]);
            if ($tenantStmt->rowCount() === 0) {
                return $this->generateResponse(null, "failed", "Tenant ID does not exist", 404);
            }

            // Verify apartment exists
            $aptStmt = $this->pdo->prepare("SELECT id, availability FROM Apartment WHERE id = ?");
            $aptStmt->execute([$body['apartment_id']]);
            if ($aptStmt->rowCount() === 0) {
                return $this->generateResponse(null, "failed", "Apartment ID does not exist", 404);
            }

            $apartment = $aptStmt->fetch(PDO::FETCH_ASSOC);
            if ($apartment['availability'] !== 'Available') {
                return $this->generateResponse(null, "failed", "Apartment is not available", 400);
            }

            // Validate dates
            $start = new DateTime($body['start_date']);
            $end = new DateTime($body['end_date']);
            if ($start > $end) {
                return $this->generateResponse(null, "failed", "End date must be after start date", 400);
            }

            // Begin transaction
            $this->pdo->beginTransaction();

            try {
                // Create lease
                $result = $this->postData('Lease', $body, $this->pdo);

                // Update apartment availability
                $updateApt = $this->pdo->prepare("UPDATE Apartment SET availability = 'Occupied' WHERE id = ?");
                $updateApt->execute([$body['apartment_id']]);

                $this->pdo->commit();

                return $this->generateResponse(
                    ["lease_id" => $this->pdo->lastInsertId()],
                    "success",
                    "Lease created successfully",
                    201
                );
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error creating lease: " . $e->getMessage(), 400);
        }
    }

    public function postPayment($body) {
        try {
            // Validate required fields
            $required = ['lease_id', 'payment_date', 'amount_paid', 'payment_method', 'status'];
            foreach ($required as $field) {
                if (!isset($body[$field])) {
                    return $this->generateResponse(null, "failed", "Missing required field: $field", 400);
                }
            }

            // Validate payment method
            $validMethods = ['Cash', 'Credit Card', 'Bank Transfer'];
            if (!in_array($body['payment_method'], $validMethods)) {
                return $this->generateResponse(null, "failed", 
                    "Invalid payment method. Must be one of: " . implode(', ', $validMethods), 400);
            }

            // Validate status
            $validStatuses = ['Pending', 'Completed', 'Failed'];
            if (!in_array($body['status'], $validStatuses)) {
                return $this->generateResponse(null, "failed", 
                    "Invalid status. Must be one of: " . implode(', ', $validStatuses), 400);
            }

            // Verify lease exists
            $leaseStmt = $this->pdo->prepare("SELECT id, monthly_rent FROM Lease WHERE id = ?");
            $leaseStmt->execute([$body['lease_id']]);
            if ($leaseStmt->rowCount() === 0) {
                return $this->generateResponse(null, "failed", "Lease ID does not exist", 404);
            }

            // Validate payment amount
            $lease = $leaseStmt->fetch(PDO::FETCH_ASSOC);
            if ($body['amount_paid'] <= 0 || $body['amount_paid'] > $lease['monthly_rent']) {
                return $this->generateResponse(null, "failed", 
                    "Invalid payment amount. Must be between 0 and " . $lease['monthly_rent'], 400);
            }

            $this->pdo->beginTransaction();

            try {
                // Create payment
                $result = $this->postData('Payment', $body, $this->pdo);
                
                if ($result['code'] === 200) {
                    $this->pdo->commit();
                    return $this->generateResponse(
                        [
                            "payment_id" => $this->pdo->lastInsertId(),
                            "lease_id" => $body['lease_id'],
                            "amount_paid" => $body['amount_paid'],
                            "status" => $body['status']
                        ],
                        "success",
                        "Payment created successfully",
                        201
                    );
                }

                $this->pdo->rollBack();
                return $this->generateResponse(null, "failed", "Failed to create payment", 400);
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error creating payment: " . $e->getMessage(), 400);
        }
    }

    public function postCommunication($body) {
        try {
            // Validate required fields
            $required = ['sender_id', 'receiver_id', 'message'];
            foreach ($required as $field) {
                if (!isset($body[$field]) || empty(trim($body[$field]))) {
                    return $this->generateResponse(null, "failed", "Missing or empty required field: $field", 400);
                }
            }

            // Verify sender (tenant) exists
            $senderStmt = $this->pdo->prepare("SELECT id FROM Tenant WHERE id = ?");
            $senderStmt->execute([$body['sender_id']]);
            if ($senderStmt->rowCount() === 0) {
                return $this->generateResponse(null, "failed", "Sender (Tenant) ID does not exist", 404);
            }

            // Verify receiver (landlord) exists
            $receiverStmt = $this->pdo->prepare("SELECT id FROM Landlord WHERE id = ?");
            $receiverStmt->execute([$body['receiver_id']]);
            if ($receiverStmt->rowCount() === 0) {
                return $this->generateResponse(null, "failed", "Receiver (Landlord) ID does not exist", 404);
            }

            // Set default is_read status if not provided
            if (!isset($body['is_read'])) {
                $body['is_read'] = false;
            }

            $this->pdo->beginTransaction();

            try {
                // Create communication
                $result = $this->postData('Communication', $body, $this->pdo);
                
                if ($result['code'] === 200) {
                    $this->pdo->commit();
                    return $this->generateResponse(
                        ["communication_id" => $this->pdo->lastInsertId()],
                        "success",
                        "Communication sent successfully",
                        201
                    );
                }

                $this->pdo->rollBack();
                return $this->generateResponse(null, "failed", "Failed to send communication", 400);
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error creating communication: " . $e->getMessage(), 400);
        }
    }

    public function postIssue($body) {
        try {
            // Validate required fields
            $required = ['tenant_id', 'apartment_id', 'description'];
            foreach ($required as $field) {
                if (!isset($body[$field])) {
                    return $this->generateResponse(null, "failed", "Missing required field: $field", 400);
                }
            }

            // Verify tenant exists
            $tenantStmt = $this->pdo->prepare("SELECT id FROM Tenant WHERE id = ?");
            $tenantStmt->execute([$body['tenant_id']]);
            if ($tenantStmt->rowCount() === 0) {
                return $this->generateResponse(null, "failed", "Tenant ID does not exist", 404);
            }

            // Verify apartment exists
            $aptStmt = $this->pdo->prepare("SELECT id FROM Apartment WHERE id = ?");
            $aptStmt->execute([$body['apartment_id']]);
            if ($aptStmt->rowCount() === 0) {
                return $this->generateResponse(null, "failed", "Apartment ID does not exist", 404);
            }

            // Validate status if provided
            $validStatuses = ['Pending', 'In Progress', 'Resolved'];
            if (isset($body['status']) && !in_array($body['status'], $validStatuses)) {
                return $this->generateResponse(null, "failed", "Invalid status. Must be one of: " . implode(', ', $validStatuses), 400);
            }

            // Set default status if not provided
            if (!isset($body['status'])) {
                $body['status'] = 'Pending';
            }

            $this->pdo->beginTransaction();

            try {
                // Create issue
                $result = $this->postData('Issue', $body, $this->pdo);
                
                if ($result['code'] === 200) {
                    $this->pdo->commit();
                    return $this->generateResponse(
                        ["issue_id" => $this->pdo->lastInsertId()],
                        "success",
                        "Issue reported successfully",
                        201
                    );
                }

                $this->pdo->rollBack();
                return $this->generateResponse(null, "failed", "Failed to create issue", 400);
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error creating issue: " . $e->getMessage(), 400);
        }
    }

    protected function validateLandlord($data) {
        $required = ['first_name', 'last_name', 'email', 'contact_number', 'age', 'sex'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        return true;
    }

    protected function validateTenant($data) {
        $required = ['first_name', 'last_name', 'email', 'contact_number', 'age', 'sex'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        return true;
    }

    protected function validateApartment($data) {
        $required = ['name', 'location', 'price', 'availability', 'landlord_id'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        return true;
    }

    protected function logger($user, $method, $action) {
        parent::logger($user, $method, $action);
    }
}
?>