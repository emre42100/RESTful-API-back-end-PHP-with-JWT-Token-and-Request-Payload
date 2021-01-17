<?php


class Api extends Rest
{


    public function __construct()
    {
        parent::__construct();
    }

    //1
    public function generateToken()
    {
        if ('generatetoken' != strtolower($this->serviceName)) {
            $this->validateToken();
        }


        $email = $this->validateParameter('email', $this->param['email'], STRING);
        $pass = $this->validateParameter('pass', sha1(sha1(md5(sha1(sha1(sha1(md5(sha1(md5(md5(md5(sha1(sha1(md5(sha1(sha1($this->param['pass'])))))))))))))))), STRING);
        try {
            $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE email = :email AND password = :pass");
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":pass", $pass);

            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($user)) {
                http_response_code(401);
                $this->returnResponse(401, "Email or Password is incorrect.");
            }

            if ($user['active'] == 0) {
                $this->returnResponse(401, "User is not activated. Please contact to admin.");
            }

            $payload = [
                'iat' => time(),
                'iss' => 'localhost',
                'exp' => time() + (1 * 60),
                'userId' => $user['id']

            ];

            $payloadRefresh = [
                'iat' => time(),
                'iss' => 'localhost',
                'exp' => time() + (2 * 60),
                'userId' => $user['id']

            ];

            $token = JWT::encode($payload, SECRETE_KEY);
            $tokenRefresh = JWT::encode($payloadRefresh, SECRETE_KEY);

            $data = ['token' => $token, 'refreshToken' => $tokenRefresh];
            $this->returnResponse(SUCCESS_RESPONSE, $data);


        } catch (Exception $e) {
            http_response_code(401);
            $this->throwError(401, $e->getMessage());
        }
    }


    public function passwordReset()
    {
        if ('generatetoken' != strtolower($this->serviceName)) {
            $this->validateToken();
        }


        $pass = $this->validateParameter('pass', sha1(sha1(md5(sha1(sha1(sha1(md5(sha1(md5(md5(md5(sha1(sha1(md5(sha1(sha1($this->param['pass'])))))))))))))))), STRING);
        $passNew = $this->validateParameter('passNew', sha1(sha1(md5(sha1(sha1(sha1(md5(sha1(md5(md5(md5(sha1(sha1(md5(sha1(sha1($this->param['passNew'])))))))))))))))), STRING);
        $passNewAgain = $this->validateParameter('passNewAgain', sha1(sha1(md5(sha1(sha1(sha1(md5(sha1(md5(md5(md5(sha1(sha1(md5(sha1(sha1($this->param['passNewAgain'])))))))))))))))), STRING);
        try {

            $token = $this->getBearerToken();
            try {
                $payload = JWT::decode($token, SECRETE_KEY, ['HS256']);
            } catch (Exception $e) {
            }


            $stmt = $this->dbConn->prepare('SELECT * from users where password = :pass and id = :userId');
            $stmt->bindParam(":pass", $pass);
            $stmt->bindParam(":userId", $payload->userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);


            if (preg_match('/^[a-zA-Z0-9]{6,30}$/', $this->param['passNew'])) {


                if ($pass == $user['password']) {


                    if ($passNew == $passNewAgain) {


                        $stmt = $this->dbConn->prepare('update users set password = :newpass where id = :userId');
                        $stmt->bindParam(":newpass", $passNew);
                        $stmt->bindParam(":userId", $payload->userId);
                        $stmt->execute();
                        http_response_code(201);
                        $this->returnResponse(201, "Şifreniz değişti, tekrardan giriş yapmalısınız.");
                    } else {

                        http_response_code(200);
                        $this->returnResponse(200, "Yeni şifreniz eşleşmedi.");
                    }

                } else {

                    http_response_code(200);
                    $this->returnResponse(200, "Mevcut girdiğiniz şifre hatalı.");
                }

            } else {

                http_response_code(200);
                $this->returnResponse(200, "Şifre en az 6 karakter olmalıdır.");
            }

        } catch (Exception $e) {
            http_response_code(401);
            $this->throwError(401, $e->getMessage());
        }
    }


    public function signUp()
    {
        if ('signup' != strtolower($this->serviceName)) {
            $this->validateToken1();

        }
        $name = $this->validateParameter('name', $this->param['name'], STRING);
        $email = $this->validateParameter('email', $this->param['email'], STRING);
        $pass = $this->validateParameter('pass', sha1(sha1(md5(sha1(sha1(sha1(md5(sha1(md5(md5(md5(sha1(sha1(md5(sha1(sha1($this->param['pass'])))))))))))))))), STRING);

        try {


            if (preg_match('/^[a-zA-Z0-9]{6,30}$/', $this->param['pass'])) {


                $stmt = $this->dbConn->prepare("insert into  users (id, name, email, password, active, created_on) values (null ,:name, :email, :pass, 1,:date)");


                $stmt->bindParam(":name", $name);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":pass", $pass);
                @$stmt->bindParam(":date", date("Y-m-d"));
                $stmt->execute();
                http_response_code(201);

                $this->returnResponse(201, "User created successfully.");

            } else {

                http_response_code(422);
                $this->returnResponse(422, "Parola en az 6 karakter olmalıdır.");

            }
        } catch (PDOException $e) {


            header("content-type: application/json");


            $this->returnResponse(409, $e->getMessage());


        }


    }


    public function addCustomer()
    {
        if ('generatetoken' != strtolower($this->serviceName)) {
            $this->validateToken();
        }

        $token = $this->getBearerToken();
        try {
            $payload = JWT::decode($token, SECRETE_KEY, ['HS256']);
        } catch (Exception $e) {
        }

        $stmt = $this->dbConn->prepare("select name from users where id = :userId");
        $stmt->bindParam(":userId", $payload->userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $b = json_encode(array_values($user),JSON_UNESCAPED_UNICODE);

        $t = str_replace('[', "", $b);
        $f = str_replace(']', "", $t);
        $s = str_replace('"', "", $f);



        $name = $this->validateParameter('name', $s, STRING, false);
        $aciklama = $this->validateParameter('aciklama', rtrim($this->param['aciklama']), STRING, false);
        $addr = $this->validateParameter('addr', rtrim($this->param['addr']), STRING, false);


        try {
            $token = $this->getBearerToken();
            $payload = JWT::decode($token, SECRETE_KEY, ['HS256']);

            $stmt = $this->dbConn->prepare("select * from users where id = :userId");
            $stmt->bindParam(":userId", $payload->userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $cust = new Customer;

            if (!is_array($user)) {

                http_response_code(404);
                $this->returnResponse(404, "This user is not found in our db");

            }

            if ($user['active'] == 0) {

                http_response_code(401);
                $this->returnResponse(401, "not active");
            }

            if (preg_match('/[A-Za-z0-9\s.]{1,}/', $this->param['aciklama'])) {


                $cust->setAciklama($aciklama);


            } else {

                http_response_code(422);
                $this->returnResponse(422, "En az 1 karakter girmelisiniz");

            }


            if ($this->param['name'] = "d") {

                $cust->setName($name);

            }


            if (preg_match('/[A-Za-z0-9\s.]{1,50}/', $this->param['addr'])) {


                $cust->setAddress($addr);

                $cust->setCreatedOn(date('Y.m.d H:i'));
                $cust->setUserId($this->userId);


            } else {

                http_response_code(422);
                $this->returnResponse(422, "En az 6 ve en fazla 100 karakter girmelisiniz.");

            }


            if (!$cust->insert()) {
                $message = 'Failed to insert.';
            } else {

                $message = "Inserted successfully.";
            }

            http_response_code(201);
            $this->returnResponse(201, $message);


        } catch (Exception $e) {

            $this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
        }

    }


    //1
    public function getCustomerDetails()
    {
        if ('generatetoken' != strtolower($this->serviceName)) {
            $this->validateToken();
        }

        header("content-type: application/json");
        $customerId = $this->validateParameter('customerId', $this->param['customerId'], STRING, false);
        $userId = $this->validateParameter('userId', $this->param['userId'], INTEGER);

        $cust = new Customer;


        $cust->setUserId($userId);
        $cust->setId($customerId);


        if (empty($customerId)) {
            $stmt2 = $cust->getCustomerDetailsByIdAll();

            $num2 = $stmt2->rowCount();


            if ($num2 > 0) {


                $products_arr = array();
                $products_arr["toplam"] = $num2;
                $products_arr["data"] = array();


                if (!is_array($products_arr)) {

                    $this->returnResponse(SUCCESS_RESPONSE, ['message' => 'Customer details not found.']);
                }

                while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {

                    extract($row);

                    if ($row['updated_on'] != null) {

                        $s = date_create($row['updated_on']);
                    } else {
                        $s = '';
                    }


                    $product_item = array(
                        "id" => $stmt2->id = $row['id'],
                        "userid" => $stmt2->userId = $row['User_id'],
                        "name" => $stmt2->name = $row['name'],
                        "aciklama" => $stmt2->aciklama = $row['aciklama'],
                        "address" => $stmt2->address = $row['address'],
                        "lastUpdatedBy" => @$stmt2->lastUpdatedBy = date_format($s, 'd.m.Y H:i'),
                        "CreatedOn" => $stmt2->createdOn = date_format(date_create($row['created_on']), 'd.m.Y H:i')


                    );

                    array_push($products_arr["data"], $product_item);

                }
                if ($this->userId != $stmt2->userId) {

                    http_response_code(403);
                    $this->returnResponse(403, "403 Forbidden");
                    exit();
                }

                echo json_encode($products_arr);
                //   $this->returnResponse(200, $products_arr);


            } else {

                http_response_code(404);
                $this->returnResponse(404, "404 Not Found");
                exit();
            }
        }


        $stmt = $cust->getCustomerDetailsById();

        $num = $stmt->rowCount();


        if ($num > 0) {

            $products_arr = array();
            $products_arr["toplam"] = $num;
            $products_arr["data"] = array();


            if (!is_array($products_arr)) {

                $this->returnResponse(SUCCESS_RESPONSE, ['message' => 'Customer details not found.']);
            }

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                extract($row);


                $product_item = array(
                    "id" => $stmt->id = $row['id'],
                    "userid" => $stmt->userId = $row['User_id'],
                    "name" => $stmt->name = $row['name'],
                    "aciklama" => $stmt->aciklama = $row['aciklama'],
                    "address" => $stmt->address = $row['address'],
                    "lastUpdatedBy" => $stmt->lastUpdatedBy = date_format(date_create($row['updated_on']), 'd.m.Y H:i'),
                    "CreatedOn" => $stmt->createdOn = date_format(date_create($row['created_on']), 'd.m.Y H:i')


                );

                array_push($products_arr["data"], $product_item);

            }
            if ($this->userId != $stmt->userId) {

                http_response_code(403);
                $this->returnResponse(403, "403 Forbidden");
                exit();
            }

            // $this->returnResponse(200, $products_arr);
            echo json_encode($products_arr);

        } else if (!$customerId == "") {
            http_response_code(404);
            $this->returnResponse(404, "Data not found");
            exit();


        }

    }


    public function getAllCustomer()
    {
        if ('getallcustomer' != strtolower($this->serviceName)) {
            $this->validateToken1();
        }
        header("content-type: charset=iso-8859-1");
        header("content-type: application/json");


        $value = $this->validateParameter('customerId', htmlspecialchars(strip_tags($this->param['customerId'])), STRING);
        $key = $this->param = 'DFSG456dsfS!D-6!F§4§5d§sf4_!-6s-µµ**5_21§45W4d_ç!fD§3!$*ù6x§w54';

        if ($value == $key) {

            $cust = new Customer;
            $stmt = $cust->getAllCustomers();

            $num = $stmt->rowCount();

            if ($num > 0) {

                $products_arr = array();
                $products_arr["toplam"] = $num;
                $products_arr["data"] = array();


                if (!is_array($products_arr)) {

                    $this->returnResponse(SUCCESS_RESPONSE, ['message' => 'Customer details not found.']);
                }


                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    extract($row);

                    if ($row['updated_on'] != null) {

                        $s = date_create($row['updated_on']);
                    } else {
                        $s = '';
                    }


                    $product_item = array(


                        "id" => $stmt->id = $row['id'],
                        "user_id" => $stmt->userId = $row['User_id'],
                        "name" => $stmt->name = $row['name'],
                        "aciklama" => $stmt->aciklama = $row['aciklama'],
                        "address" => $stmt->address = $row['address'],
                        "lastUpdatedBy" => @$stmt->lastUpdatedBy = date_format($s, 'd.m.Y H:i'),
                        "CreatedOn" => $stmt->createdOn = date_format(date_create($row['created_on']), 'd.m.Y H:i')


                    );


                    array_push($products_arr["data"], $product_item);

                }

                //  $this->returnResponse(200, $products_arr);
                header('Content-Type: application/json;charset=utf-8');
              echo  json_encode($products_arr,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);


            } else {
                http_response_code(404);
                $this->returnResponse(404, "Data not found");
                exit();
            }


        } else {
            http_response_code(401);
            $this->returnResponse(401, "401 Unauthorized");
            exit();
        }


    }


    public
    function ara()
    {
        header("content-type: application/json");


        if ('ara' != strtolower($this->serviceName)) {
            $this->validateToken();
        }

        $addr = $this->validateParameter('arama', trim($this->param['arama']), STRING, false);


        $cust = new Customer;
        $a = $cust->aramaYap($addr);


        $ds = $a->rowCount();

        if ($ds > 0) {
            $products_arr = array();
            $products_arr["toplam"] = $ds;
            $products_arr["name"] = $addr;
            $products_arr["data"] = array();


            while ($row = $a->fetch(PDO::FETCH_ASSOC)) {

                extract($row);

                if ($row['updated_on'] != null) {

                    $s = date_create($row['updated_on']);
                } else {
                    $s = '';
                }


                $product_item = array(


                    "id" => $a->id = $row['id'],
                    "user_id" => $a->userId = $row['User_id'],
                    "name" => $a->name = $row['name'],
                    "aciklama" => $a->aciklama = $row['aciklama'],
                    "address" => $a->address = $row['address'],
                    "lastUpdatedBy" => @$a->lastUpdatedBy = date_format($s, 'd.m.Y H:i'),
                    "CreatedOn" => $a->createdOn = date_format(date_create($row['created_on']), 'd.m.Y H:i')


                );


                array_push($products_arr["data"], $product_item);

            }

            echo json_encode($products_arr);


        } else {
            http_response_code(404);
            $this->returnResponse(404, "Veri bulunamadı.");
            exit();
        }


    }


//1
    public
    function updateCustomer()
    {

        error_reporting(0);

        if ('generatetoken' != strtolower($this->serviceName)) {
            $this->validateToken();
        }

        $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
        $userId = $this->validateParameter('userId', $this->param['userId'], INTEGER);
        $aciklama = $this->validateParameter('aciklama', rtrim($this->param['aciklama']), STRING, false);
        $addr = $this->validateParameter('addr', rtrim($this->param['addr']), STRING, false);


        $cust = new Customer;
        $cust->setUserId($this->userId);
        $cust->setId($customerId);
        $cust->setAciklama($aciklama);
        $cust->setAddress($addr);
        $cust->setUpdatedOn(date('Y-m-d H:i:s'));


        if ($this->userId != $cust->getUserId()) {

            http_response_code(403);
            $this->returnResponse(403, "403 Forbidden");
            exit();

        } else {


            if (!$cust->update()) {
                $message = 'Failed to update.';
            } else {
                $message = "Updated successfully.";
            }

            $this->returnResponse(SUCCESS_RESPONSE, $message);
        }


    }

// 1
    public
    function deleteCustomer()
    {
        if ('generatetoken' != strtolower($this->serviceName)) {
            $this->validateToken();
        }

        $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
        $userid = $this->validateParameter('userId', $this->param['userId'], INTEGER);

        $cust = new Customer;
        $cust->setId($customerId);
        $cust->setUserId($userid);


        if ($this->userId != $cust->getUserId()) {

            http_response_code(403);
            $this->returnResponse(403, "403 Forbidden");
            exit();

        } else {


            if (!$cust->delete()) {
                $message = 'Failed to delete.';
            } else {
                $message = "deleted successfully.";
            }
            $this->returnResponse(SUCCESS_RESPONSE, $message);
        }


    }
}


?>