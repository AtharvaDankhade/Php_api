<?php
require '../dbConnect/dbcon.php';
function getList()
{

    global $conn;
    $sqlSelect = 'SELECT * from data';

    $result = mysqli_query($conn, $sqlSelect);

    if (mysqli_num_rows($result) > 0) {
        $res = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $data = [
            'status' => 200,
            'message' => 'List Fetched Successfully',
            'result' => $res,
        ];
        header("HTTP/1.0 200 Ok");
        return json_encode($data);
    } else {
        $data = [
            'status' => 405,
            'message' => 'Data Not Found',
        ];
        header("HTTP/1.0 405 Not Found");
        return json_encode($data);
    }
}
