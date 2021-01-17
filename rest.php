<?php
require_once('constants.php');

class Rest
{
    protected $request;
    protected $serviceName;
    protected $param;
    protected $dbConn;
    protected $userId;


    public function __construct()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->throwError(405, 'Request Method is not valid.');
        }
        $handler = fopen('php://input', 'r');
        $this->request = stream_get_contents($handler);
        $this->validateRequest();
        $db = new DbConnect;
        $this->dbConn = $db->connect();


    }

    Public function validateRequest()
    {
        if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
            http_response_code(415);
            $this->throwError(415, '415 Unsupported Media Type');
        }

        $data = json_decode($this->request, true);

        if (!isset($data['name']) || $data['name'] == "") {
            http_response_code(400);
            $this->throwError(400, "API name is required.");
        }
        $this->serviceName = $data['name'];

        if (!is_array($data['param'])) {
            http_response_code(400);
            $this->throwError(400, "API PARAM is required.");
        }
        $this->param = $data['param'];
    }

    public function validateParameter($fieldName, $value, $dataType, $required = true)
    {
        if ($required == true && empty($value) == true) {
            http_response_code(400);
            $this->throwError(400, $fieldName . " parameter is required.");
        }


        switch ($dataType) {
            case BOOLEAN:
                if (!is_bool($value)) {
                    http_response_code(400);
                    $this->throwError(400, "Datatype is not valid for " . $fieldName . '. It should be boolean.');
                }
                break;
            case INTEGER:
                if (!is_numeric($value)) {
                    http_response_code(400);
                    $this->throwError(400, "Datatype is not valid for " . $fieldName . '. It should be numeric.');
                }
                break;

            case STRING:
                if (!is_string($value)) {
                    http_response_code(400);
                    $this->throwError(400, "Datatype is not valid for " . $fieldName . '. It should be string.');
                }
                break;

            default:
                http_response_code(400);
                $this->throwError(400, "Datatype is not valid for " . $fieldName);
                break;
        }




        return $value;

    }

    public function validateToken()
    {
        try {
            $token = $this->getBearerToken();
            $payload = JWT::decode($token, SECRETE_KEY, ['HS256']);

            $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE id = :userId");
            $stmt->bindParam(":userId", $payload->userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($user)) {
                http_response_code(404);
                $this->returnResponse(404, "This user is not found in our database.");
            }

            if ($user['active'] == 0) {
                http_response_code(401);
                $this->returnResponse(401, "This user may be decactived. Please contact to admin.");
            }
            $this->userId = $payload->userId;
        } catch (Exception $e) {
            http_response_code(401);
            $this->throwError(401, $e->getMessage());
        }
    }


    public function validateToken1()
    {

    }

    public function processApi()
    {
        try {
            $api = new API;
            $rMethod = new reflectionMethod('API', $this->serviceName);
            if (!method_exists($api, $this->serviceName)) {
                http_response_code(400);
                $this->throwError(400, "API does not exist.");
            }
            $rMethod->invoke($api);
        } catch (Exception $e) {
            http_response_code(400);
            $this->throwError(400, $e);
        }

    }

    public function throwError($code, $message)
    {
        header("content-type: application/json");
        $errorMsg = json_encode(['error' => ['status' => $code, 'message' => $message]]);
        echo $errorMsg;
        exit;
    }

    public function returnResponse($code, $data)
    {
        header("content-type: application/json");
        $response = json_encode(['response' => ['status' => $code, "result" => $data]], JSON_UNESCAPED_SLASHES);
        echo $response;
        exit;
    }

    /**
     * Get hearder Authorization
     * */
    public function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * get access token from header
     * */
    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        http_response_code(401);
        $this->throwError(401, 'Access Token Not found');
    }
}

?>