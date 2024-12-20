<?php
class Get extends Common {
    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Landlords
    public function getLandlords($id = null) {
        $result = $id ? 
            $this->getDataByTable("Landlord", "id = $id", $this->pdo) :
            $this->getDataByTable("Landlord", "1", $this->pdo);

        if($result['code'] == 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved landlords.", $result['code']);
        }
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    // Tenants
    public function getTenants($id = null) {
        $result = $id ? 
            $this->getDataByTable("Tenant", "id = $id", $this->pdo) :
            $this->getDataByTable("Tenant", "1", $this->pdo);

        if($result['code'] == 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved tenants.", $result['code']);
        }
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    // Apartments
    public function getApartments($id = null) {
        $sql = "SELECT a.*, CONCAT(l.first_name, ' ', l.last_name) as landlord_name 
                FROM Apartment a
                JOIN Landlord l ON a.landlord_id = l.id";
        
        if($id) {
            $sql .= " WHERE a.id = $id";
        }

        $result = $this->getDataBySQL($sql, $this->pdo);

        if($result['code'] == 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved apartments.", $result['code']);
        }
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    // Communications
    public function getCommunications($id = null) {
        $sql = "SELECT c.*, 
                CONCAT(s.first_name, ' ', s.last_name) as sender_name,
                CONCAT(r.first_name, ' ', r.last_name) as receiver_name
                FROM Communication c
                JOIN Tenant s ON c.sender_id = s.id
                JOIN Landlord r ON c.receiver_id = r.id";
        
        if($id) {
            $sql .= " WHERE c.id = $id";
        }

        $result = $this->getDataBySQL($sql, $this->pdo);

        if($result['code'] == 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved communications.", $result['code']);
        }
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    // Issues
    public function getIssues($id = null) {
        $sql = "SELECT i.*, 
                CONCAT(t.first_name, ' ', t.last_name) as tenant_name,
                a.name as apartment_name
                FROM Issue i
                JOIN Tenant t ON i.tenant_id = t.id
                JOIN Apartment a ON i.apartment_id = a.id";
        
        if($id) {
            $sql .= " WHERE i.id = $id";
        }

        $result = $this->getDataBySQL($sql, $this->pdo);

        if($result['code'] == 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved issues.", $result['code']);
        }
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    // Leases
    public function getLeases($id = null) {
        $sql = "SELECT l.*, 
                CONCAT(t.first_name, ' ', t.last_name) as tenant_name,
                a.name as apartment_name
                FROM Lease l
                JOIN Tenant t ON l.tenant_id = t.id
                JOIN Apartment a ON l.apartment_id = a.id";
        
        if($id) {
            $sql .= " WHERE l.id = $id";
        }

        $result = $this->getDataBySQL($sql, $this->pdo);

        if($result['code'] == 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved leases.", $result['code']);
        }
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    // Payments
    public function getPayments($id = null) {
        $sql = "SELECT p.*, 
                CONCAT(t.first_name, ' ', t.last_name) as tenant_name,
                l.id as lease_id,
                p.payment_method,
                p.status
                FROM Payment p
                JOIN Lease l ON p.lease_id = l.id
                JOIN Tenant t ON l.tenant_id = t.id";
        
        if($id) {
            $sql .= " WHERE p.id = $id";
        }

        $result = $this->getDataBySQL($sql, $this->pdo);

        if($result['code'] == 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved payments.", $result['code']);
        }
        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }
}
?>