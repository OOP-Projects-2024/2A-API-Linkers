<?php
class Common {

    protected function deleteData($table, $id, $pdo) {
        try {
            $sql = "DELETE FROM $table WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            return ["code" => 200];
        } catch (\PDOException $e) {
            return ["code" => 400, "errmsg" => $e->getMessage()];
        }
    }

    protected function updateData($table, $id, $data, $pdo) {
        try {
            $fields = [];
            $values = [];
            foreach ($data as $key => $value) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
            $values[] = $id;
            
            $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            return ["code" => 200];
        } catch (\PDOException $e) {
            return ["code" => 400, "errmsg" => $e->getMessage()];
        }
    }


    protected function logger($user, $method, $action) {
        $filename = date("Y-m-d") . ".log";
        $datetime = date("Y-m-d H:i:s");
        $logMessage = "$datetime,$method,$user,$action" . PHP_EOL;
        
        if (!is_dir(__DIR__ . "/../logs")) {
            mkdir(__DIR__ . "/../logs", 0777, true);
        }
        
        error_log($logMessage, 3, __DIR__ . "/../logs/$filename");
    }

    private function generateInsertString($tablename, $body) {
        $keys = array_keys($body);
        $fields = implode(",", $keys);
        $parameter_array = [];
        for ($i = 0; $i < count($keys); $i++) {
            $parameter_array[$i] = "?";
        }
        $parameters = implode(',', $parameter_array);
        $sql = "INSERT INTO $tablename($fields) VALUES ($parameters)";
        return $sql;
    }

    protected function getDataByTable($tableName, $condition, \PDO $pdo) {
        $sqlString = "SELECT * FROM $tableName WHERE $condition";
        $data = [];
        $errmsg = "";
        $code = 0;

        try {
            if ($result = $pdo->query($sqlString)->fetchAll()) {
                foreach ($result as $record) {
                    array_push($data, $record);
                }
                $result = null;
                $code = 200;
                return ["code" => $code, "data" => $data];
            } else {
                $errmsg = "No data found";
                $code = 404;
            }
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 403;
        }

        return ["code" => $code, "errmsg" => $errmsg];
    }

    protected function getDataBySQL($sqlString, \PDO $pdo) {
        $data = [];
        $errmsg = "";
        $code = 0;

        try {
            if ($result = $pdo->query($sqlString)->fetchAll()) {
                foreach ($result as $record) {
                    array_push($data, $record);
                }
                $result = null;
                $code = 200;
                return ["code" => $code, "data" => $data];
            } else {
                $errmsg = "No data found";
                $code = 404;
            }
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 403;
        }

        return ["code" => $code, "errmsg" => $errmsg];
    }

    protected function generateResponse($data, $remark, $message, $statusCode) {
        $status = [
            "remark" => $remark,
            "message" => $message
        ];

        http_response_code($statusCode);

        return [
            "payload" => $data,
            "status" => $status,
            "prepared_by" => "Chrizelda Norial",
            "date_generated" => date_create()
        ];
    }

    public function postData($tableName, $body, \PDO $pdo) {
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach ($body as $value) {
            array_push($values, $value);
        }

        try {
            $sqlString = $this->generateInsertString($tableName, $body);
            $sql = $pdo->prepare($sqlString);
            $sql->execute($values);

            $code = 200;
            $data = null;

            return ["data" => $data, "code" => $code];
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 400;
        }

        return ["errmsg" => $errmsg, "code" => $code];
    }

    public function getLogs($date) {
        $filename = __DIR__ . "/../logs/$date.log";
        if (file_exists($filename)) {
            return file_get_contents($filename);
        } else {
            return ["message" => "Log file not found"];
        }
    }
}
?>