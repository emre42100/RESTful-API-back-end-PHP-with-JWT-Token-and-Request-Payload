<?php

/**
 * Database Connection
 */
class DbConnect
{
    private $server = 'localhost';
    private $dbname = 'jwtapi';
    private $user = 'root';
    private $pass = '1234';

    public function connect()
    {
        try {
            date_default_timezone_set ("Europe/Paris");


            $conn = new PDO('mysql:host=' . $this->server . ';dbname=' . $this->dbname, $this->user, $this->pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//            $a = $conn->prepare("SET GLOBAL sql_mode=''");
//            $conn->exec($a);
            $conn->query("SET CHARACTER SET utf8");
            return $conn;
        } catch (\Exception $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }
}

?>