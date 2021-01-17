
<?php

class Customer
{
    private $id;
    private $name;
    private $aciklama;
    private $address;
    private $updatedOn;
    private $createdOn;
    private $tableName = 'customers';
    private $dbConn;
    private $userId;


    public function getAciklama()
    {
        return $this->aciklama;
    }


    public function setAciklama($aciklama)
    {
        $this->aciklama = $aciklama;
    }


    function setUserId($userId)
    {
        $this->userId = $userId;
    }

    function getUserId()
    {
        return $this->userId;
    }

    function setId($id)
    {
        $this->id = $id;
    }

    function getId()
    {
        return $this->id;
    }

    function setName($name)
    {
        $this->name = $name;
    }

    function getName()
    {
        return $this->name;
    }


    function setAddress($address)
    {
        $this->address = $address;
    }

    function getAddress()
    {
        return $this->address;
    }



    function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;
    }

    function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
    }

    function getCreatedOn()
    {
        return $this->createdOn;
    }


    public function __construct()
    {
        $db = new DbConnect();
        $this->dbConn = $db->connect();
    }

    public function getAllCustomers()
    {
        $stmt = $this->dbConn->prepare("SELECT * FROM " . $this->tableName ." order by created_on desc");
        $stmt->execute();
        return $stmt;
    }


    public function getCustomerDetailsById()
    {

        $sql = "SELECT * FROM customers WHERE User_id = :Userid and id =:id";


        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindParam(':Userid', $this->userId);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        return $stmt;
    }

    public function getCustomerDetailsByIdAll()
    {

        $sql = "SELECT * FROM customers WHERE User_id = :Userid order by created_on desc";


        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindParam(':Userid', $this->userId);
        $stmt->execute();

        return $stmt;
    }


    public function aramaYap($a)
    {


        $sql = "SELECT * FROM customers WHERE address like :param";

        // bu normalde "%$a" idi.
      $b = "$a";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindParam(":param",$b);
        $stmt->execute();

        return $stmt;
    }


    public function insert()
    {

        $sql = 'INSERT INTO ' . $this->tableName . '(id, User_id, name, aciklama, address,  created_on) VALUES(null, :Userid , :name, :aciklama, :address, :createdOn)';

        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':aciklama', $this->aciklama);
        $stmt->bindParam(':createdOn', $this->createdOn);
        $stmt->bindParam(':Userid', $this->userId);

        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function update()
    {

        $sql = "UPDATE $this->tableName SET";


        if (null != $this->getAciklama()) {
            $sql .= " aciklama = '" . addslashes($this->getAciklama()) . "',";
        }

        if (null != $this->getAddress()) {
            $sql .= " address = '" . $this->getAddress() . "',";
        }



        $sql .= " updated_on = :updatedOn
					WHERE id = :id and User_id = :userId";

        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindParam(':id', $this->getId());
        $stmt->bindParam(':userId', $this->getUserId());
        $stmt->bindParam(':updatedOn', $this->getUpdatedOn());

        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function delete()
    {
        $stmt = $this->dbConn->prepare('DELETE FROM ' . $this->tableName . ' WHERE id = :id and User_id = :userId');
        $stmt->bindParam(':id', $this->getId());
        $stmt->bindParam(':userId', $this->getUserId());


        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }


}


?>