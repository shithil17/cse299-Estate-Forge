<?php
class Database {
    private $servername = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "eduor";
    public $conn;

    public function __construct() {
        $this->conn =  new mysqli(
            $this->servername,
            $this->username,
            $this->password,
            $this->dbname
        );

    if($this->conn->connect_error)
    {
        die("Connection failed" . $this->connection_error);   
    }
    else {
        //echo "Connected successfully to database";
    }

    }
    public function getConnection()
    {
        return $this->conn;
    }
    
}
?>