<?php

class Message {
    private $conn;
    private $table_name = "message";

    public $id;
    public $user;
    public $text;
    public $date;

    public function __construct($db){
        $this->conn = $db;
    }

    function read(){
        $query = "SELECT * FROM `message`";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    function create(){
        $query = "INSERT INTO `message` SET user=:user, text=:text, date=:date";
        $stmt = $this->conn->prepare($query);
        $this->user = htmlspecialchars(strip_tags($this->user));
        $this->text = htmlspecialchars(strip_tags($this->text));
        $this->date = htmlspecialchars(strip_tags($this->date));
        $stmt->bindParam(":user", $this->user);
        $stmt->bindParam(":text", $this->text);
        $stmt->bindParam(":date", $this->date);
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    function delete(){
        $query = "DELETE FROM message WHERE id=:id"; 
        $stmt = $this->conn->prepare($query);
        $this->id=htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":id", $this->id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>