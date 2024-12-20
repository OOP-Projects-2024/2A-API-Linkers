<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-User");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/app/Auth.php';
require_once __DIR__ . '/app/Common.php';
require_once __DIR__ . '/app/Crypt.php';
require_once __DIR__ . '/app/Get.php';
require_once __DIR__ . '/app/Post.php';
require_once __DIR__ . '/app/Patch.php';
require_once __DIR__ . '/app/Delete.php';

try {
    $database = new Connection();
    $pdo = $database->connect();

    $auth = new Authentication($pdo);
    $common = new Common();
    $crypt = new Crypt();
    $get = new Get($pdo);
    $post = new Post($pdo);
    $patch = new Patch($pdo);
    $delete = new Delete($pdo);

    function handleError($code, $message) {
        http_response_code($code);
        echo json_encode([
            "status" => "error",
            "code" => $code,
            "message" => $message
        ]);
    }

    if (!isset($_REQUEST['request'])) {
        handleError(404, "URL does not exist");
        exit();
    }

    $request = explode('/', trim($_REQUEST['request']));

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($auth->isAuthorized()) {
                switch ($request[0]) {
                    case 'landlords':
                        echo json_encode($get->getLandlords($request[1] ?? null));
                        break;
                    case 'tenants':
                        echo json_encode($get->getTenants($request[1] ?? null));
                        break;
                    case 'apartments':
                        echo json_encode($get->getApartments($request[1] ?? null));
                        break;
                    case 'leases':
                        echo json_encode($get->getLeases($request[1] ?? null));
                        break;
                    case 'payments':
                        echo json_encode($get->getPayments($request[1] ?? null));
                        break;
                    case 'issues':
                        echo json_encode($get->getIssues($request[1] ?? null));
                        break;
                    case 'communications':
                        echo json_encode($get->getCommunications($request[1] ?? null));
                        break;
                    default:
                        handleError(404, "Invalid endpoint");
                        break;
                }
            } else {
                handleError(401, "Unauthorized");
            }
            break;

        case 'POST':
            $body = json_decode(file_get_contents("php://input"), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                handleError(400, "Invalid JSON payload");
                break;
            }

            switch ($request[0]) {
                case 'login':
                    echo json_encode($auth->login($body));
                    break;
                case 'signup':
                    echo json_encode($auth->register($body));
                    break;
                case 'landlords':
                    if ($auth->isAuthorized()) {
                        echo json_encode($post->postLandlord($body));
                    } else {
                        handleError(401, "Unauthorized");
                    }
                    break;
                case 'tenants':
                    if ($auth->isAuthorized()) {
                        echo json_encode($post->postTenant($body));
                    } else {
                        handleError(401, "Unauthorized");
                    }
                    break;
                case 'apartments':
                    if ($auth->isAuthorized()) {
                        echo json_encode($post->postApartment($body));
                    } else {
                        handleError(401, "Unauthorized");
                    }
                    break;
                case 'leases':
                    if ($auth->isAuthorized()) {
                        echo json_encode($post->postLease($body));
                    } else {
                        handleError(401, "Unauthorized");
                    }
                    break;
                case 'payments':
                    if ($auth->isAuthorized()) {
                        echo json_encode($post->postPayment($body));
                    } else {
                        handleError(401, "Unauthorized");
                    }
                    break;
                case 'issues':
                    if ($auth->isAuthorized()) {
                        echo json_encode($post->postIssue($body));
                    } else {
                        handleError(401, "Unauthorized");
                    }
                    break;
                case 'communications':
                    if ($auth->isAuthorized()) {
                        echo json_encode($post->postCommunication($body));
                    } else {
                        handleError(401, "Unauthorized");
                    }
                    break;
                default:
                    handleError(404, "Invalid endpoint");
                    break;
            }
            break;

        case 'PATCH':
            if (!$auth->isAuthorized()) {
                handleError(401, "Unauthorized");
                break;
            }

            if (!isset($request[1])) {
                handleError(400, "ID is required");
                break;
            }

            $body = json_decode(file_get_contents("php://input"), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                handleError(400, "Invalid JSON payload");
                break;
            }

            switch ($request[0]) {
                case 'landlords':
                    echo json_encode($patch->patchLandlord($request[1], $body));
                    break;
                case 'tenants':
                    echo json_encode($patch->patchTenant($request[1], $body));
                    break;
                case 'apartments':
                    echo json_encode($patch->patchApartment($request[1], $body));
                    break;
                case 'leases':
                    echo json_encode($patch->patchLease($request[1], $body));
                    break;
                case 'payments':
                    echo json_encode($patch->patchPayment($request[1], $body));
                    break;
                case 'issues':
                    echo json_encode($patch->patchIssue($request[1], $body));
                    break;
                case 'communications':
                    echo json_encode($patch->patchCommunication($request[1], $body));
                    break;
                default:
                    handleError(404, "Invalid endpoint");
                    break;
            }
            break;

        case 'DELETE':
            if (!$auth->isAuthorized()) {
                handleError(401, "Unauthorized");
                break;
            }

            if (!isset($request[1])) {
                handleError(400, "ID is required");
                break;
            }

            switch ($request[0]) {
                case 'landlords':
                    echo json_encode($delete->deleteLandlord($request[1]));
                    break;
                case 'tenants':
                    echo json_encode($delete->deleteTenant($request[1]));
                    break;
                case 'apartments':
                    echo json_encode($delete->deleteApartment($request[1]));
                    break;
                case 'leases':
                    echo json_encode($delete->deleteLease($request[1]));
                    break;
                case 'payments':
                    echo json_encode($delete->deletePayment($request[1]));
                    break;
                case 'issues':
                    echo json_encode($delete->deleteIssue($request[1]));
                    break;
                case 'communications':
                    echo json_encode($delete->deleteCommunication($request[1]));
                    break;
                default:
                    handleError(404, "Invalid endpoint");
                    break;
            }
            break;

        default:
            handleError(405, "Method not allowed");
            break;
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    handleError(500, "Internal server error");
} finally {
    $pdo = null;
}
?>