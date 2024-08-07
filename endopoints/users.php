<?php
include 'getList.php';

header('Access-Control-Allow-Origin:*');
header('Content-Type: application/json');
header('Access-Control-Allow-Method: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Request-With');

$requestMethod = $_SERVER['REQUEST_METHOD'];
if ($requestMethod == 'GET') {
    $list = getList();
    echo $list;
} else {
    $data = [
        'status' => 405,
        'message' => 'Data Not Found',
        'result' => $res,
    ];
    header("HTTP/1.0 405 Not Found");
    echo json_encode($data);
}
