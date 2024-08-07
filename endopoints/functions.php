<?php
require '../dbConnect/dbcon.php';
function getCustomerList()
{
    global $conn;

    $query = "SELECT * FROM customer";
    $query_run = mysqli_query($conn, $query);

    if ($query_run) {
        if (mysqli_num_rows($query_run) > 0) {
            $res = mysqli_fetch_all($query_run, MYSQLI_ASSOC);
            $data = [
                'status' => 200,
                'message' => 'Customer List Fetched Successfully',
                'data' => $res
            ];
            header('HTTP/0.1 200 Customer List Fetched Successfully');
            return json_encode($data);
        } else {
            $data = [
                'status' => 404,
                'message' => 'No Customer Found',
            ];
            header("HTTP/1.0 404 No Customer Found");
            return json_encode($data);
        }
    } else {
        $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}

function uploadData()
{
    global $conn;

    if (isset($_POST['upload'])) {
        $fileName = $_FILES['file']['tmp_name'];

        if ($_FILES['file']['size'] > 0) {
            $file = fopen($fileName, 'r');

            while (($column = fgetcsv($file, 1000, ',')) != FALSE) {
                $sqlInsert = 'Insert into data (name,email,username,address,role) values (' . $column[0] . ',' . $column[1] . ',' . $column[2] . ',' . $column[3] . ',' . $column[4] . ')';

                $result = mysqli_query($conn, $sqlInsert);

                if (!empty($result)) {
                    $data = [
                        'status' => 200,
                        'message' => 'Data Uploded Successfully',
                    ];
                    header('HTTP/0.1 200 Data Uploded Successfully');
                    return json_encode($data);
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Trouble Shoot to find data',
                    ];
                    header("HTTP/1.0 500 ITrouble Shoot to find data");
                    return json_encode($data);
                }
            }
        }
    }
}
