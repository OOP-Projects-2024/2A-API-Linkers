<?php
require_once __DIR__ . '/Common.php';

class Authentication extends Common {
    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function isAuthorized() {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        if (!isset($headers['authorization'])) {
            return false;
        }
        return $this->getToken() === $headers['authorization'];
    }

    private function getToken() {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        if (!isset($headers['x-auth-user'])) {
            return "";
        }

        $sqlString = "SELECT token FROM users WHERE email = ?";
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$headers['x-auth-user']]);
            $result = $stmt->fetch();
            if ($result) {
                return $result['token'];
            }
        } catch (Exception $e) {
            error_log("Error retrieving token: " . $e->getMessage());
        }
        return "";
    }

    private function generateHeader() {
        $header = [
            "typ" => "JWT",
            "alg" => "HS256",
            "app" => "RentConnect",
            "dev" => "Chrizelda Norial"
        ];
        return base64_encode(json_encode($header));
    }

    private function generatePayload($id, $username) {
        $payload = [
            "uid" => $id,
            "uc" => $username,
            "email" => "example@example.com",
            "date" => date("Y-m-d H:i:s"),
            "exp" => date("Y-m-d H:i:s", strtotime('+24 hours'))
        ];
        return base64_encode(json_encode($payload));
    }

    private function generateToken($id, $username) {
        $header = $this->generateHeader();
        $payload = $this->generatePayload($id, $username);
        $signature = hash_hmac("sha256", "$header.$payload", 'your_secret_key');
        return "$header.$payload." . base64_encode($signature);
    }

    private function isSamePassword($inputPassword, $existingHash) {
        return password_verify($inputPassword, $existingHash);
    }

    public function saveToken($token, $email) {
        try {
            $sqlString = "UPDATE users SET token = ? WHERE email = ?";
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$token, $email]);
            return ["data" => null, "code" => 200];
        } catch (\PDOException $e) {
            error_log("Error saving token: " . $e->getMessage());
            return ["errmsg" => $e->getMessage(), "code" => 400];
        }
    }

    public function login($body) {
        $email = $body['email'];
        $password = $body['password'];

        try {
            $sqlString = "SELECT id, email, password, token FROM users WHERE email = ?";
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch();
                if ($this->isSamePassword($password, $result['password'])) {
                    $token = $this->generateToken($result['id'], $result['email']);
                    $token_arr = explode('.', $token);
                    $this->saveToken($token_arr[2], $result['email']);
                    return [
                        "payload" => [
                            "id" => $result['id'],
                            "email" => $result['email'],
                            "token" => $token_arr[2]
                        ],
                        "remarks" => "success",
                        "message" => "Logged in successfully",
                        "code" => 200
                    ];
                } else {
                    return [
                        "payload" => null,
                        "remarks" => "failed",
                        "message" => "Incorrect password.",
                        "code" => 401
                    ];
                }
            } else {
                return [
                    "payload" => null,
                    "remarks" => "failed",
                    "message" => "Email does not exist.",
                    "code" => 401
                ];
            }
        } catch (\PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                "payload" => null,
                "remarks" => "failed",
                "message" => $e->getMessage(),
                "code" => 400
            ];
        }
    }

    public function register($body) {
        try {
            // Validate required fields
            $required = ['firstname', 'lastname', 'email', 'password', 'role'];
            foreach ($required as $field) {
                if (!isset($body[$field])) {
                    return $this->generateResponse(null, "failed", "Missing required field: $field", 400);
                }
            }

            // Validate role
            if (!in_array($body['role'], ['Landlord', 'Tenant'])) {
                return $this->generateResponse(null, "failed", "Invalid role. Must be Landlord or Tenant", 400);
            }

            $this->pdo->beginTransaction();

            try {
                // Insert into users table first
                $encryptedPassword = password_hash($body['password'], PASSWORD_DEFAULT);
                $userStmt = $this->pdo->prepare("INSERT INTO users (firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?)");
                $userStmt->execute([
                    $body['firstname'],
                    $body['lastname'],
                    $body['email'],
                    $encryptedPassword,
                    $body['role']
                ]);

                // Insert into role-specific table
                $roleTable = $body['role'];
                $roleStmt = $this->pdo->prepare("INSERT INTO {$roleTable} (first_name, last_name, email, contact_number, age, sex) VALUES (?, ?, ?, ?, ?, ?)");
                $roleStmt->execute([
                    $body['firstname'],
                    $body['lastname'],
                    $body['email'],
                    $body['contact_number'] ?? null,
                    $body['age'] ?? null,
                    $body['sex'] ?? null
                ]);

                $this->pdo->commit();
                return $this->generateResponse(
                    [
                        "user_id" => $this->pdo->lastInsertId(),
                        "role" => $body['role']
                    ],
                    "success",
                    "Registration successful",
                    201
                );

            } catch (\Exception $e) {
                $this->pdo->rollBack();
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    return $this->generateResponse(null, "failed", "Email already exists", 409);
                }
                throw $e;
            }
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Registration failed: " . $e->getMessage(), 400);
        }
    }
}