<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../objects/message.php';
include_once '../../index.php';

$database = new Database();
$database->getData($cleardb_server, $cleardb_db ,$cleardb_username, $cleardb_password);
$db = $database->getConnection();

$message = new Message($db);
 
$stmt = $message->read();

$messages_arr=array();
$messages_arr["records"]=array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    extract($row);
    $message_item=array(
        "id" => $id,
        "user" => $user,
        "text" => $text,
        "date" => $date
    );

    array_push($messages_arr["records"], $message_item);
}

http_response_code(200);

echo json_encode($messages_arr);