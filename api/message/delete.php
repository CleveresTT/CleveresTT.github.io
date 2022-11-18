<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../objects/message.php';
include_once '../../index.php';

$database = new Database();
$database->getData($cleardb_server, $cleardb_db ,$cleardb_username, $cleardb_password);
$db = $database->getConnection();

$message = new Message($db);

$data = json_decode(file_get_contents('php://input'));

$message->id = $data->id;

if ($message->delete()) {
    http_response_code(200);
    echo json_encode($message);
}
else {
    http_response_code(503);
    echo json_encode(array("message" => "Не удалось удалить сообщение."), JSON_UNESCAPED_UNICODE);
}
?>
