<?php
class Database {
 
    public $conn;

    public function getConnection(){

        $db_path =  $_SERVER["DOCUMENT_ROOT"] . '/ИВТ-41-19_Семенов_СВ_БД_ЛР4.accdb';
        $conn = new COM('ADODB.Connection', NULL, CP_UTF8); 
        $conn->Open('Driver={Microsoft Access Driver (*.mdb, *.accdb)}; Dbq=' . $db_path);

        $this->conn = $conn;

        return $this->conn;
    }
}
?>