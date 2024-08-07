<?php

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Exception\RequestException;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'vendor/autoload.php';

header('Content-Type: application/json');

$response = [];

$headers = getallheaders();
if (isset($headers['Username'])) {
    $username = $headers['Username'];
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Username header is missing']);
    exit;
}

$conn = mysqli_connect('localhost', 'root', '', 'practice');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=practice", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT Role FROM data WHERE Username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['Role'] !== 'ADMIN') {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['status' => 'error', 'message' => 'Access denied: You must be an admin to perform this action']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['file'])) {
            $fileName = $_FILES['file']['tmp_name'];

            if ($_FILES['file']['size'] > 0) {
                $file = fopen($fileName, 'r');
                $promises = [];
                $client = new Client();

                while (($column = fgetcsv($file, 1000, ',')) !== FALSE) {
                    $sqlInsert = "INSERT INTO `data` (`Name`,`Email`,`Username`,`Address`,`Role`) VALUES ('" . $column[0] . "','" . $column[1] . "','" . $column[2] . "','" . $column[3] . "','" . $column[4] . "')";
                    $result = mysqli_query($conn, $sqlInsert);

                    if ($result) {
                        $email = $column[1];
                        $promises[] = $client->postAsync('http://localhost/assignment/endopoints/send_email.php', [
                            'form_params' => ['email' => $email]
                        ]);
                        $response[] = ["message" => "Uploaded Successfully, added email task for $email", "email" => $email];
                    } else {
                        $response[] = ["message" => "Error inserting data for $email", "email" => $email];
                    }
                }

                $results = Utils::settle($promises)->wait();

                foreach ($results as $result) {
                    if ($result['state'] === 'fulfilled') {
                        $response[] = ["message" => "Email sent successfully"];
                    } else {
                        $reason = $result['reason'];
                        if ($reason instanceof RequestException) {
                            $response[] = ["message" => "Email failed: " . $reason->getMessage()];
                        }
                    }
                }
            } else {
                $response[] = ["message" => "Empty file uploaded"];
            }
        } else {
            $response[] = ["message" => "No file uploaded"];
        }
    } else {
        $response[] = ["message" => "Invalid request method"];
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $response[] = ["status" => 'error', "message" => 'Database error: ' . $e->getMessage()];
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $response[] = ["status" => 'error', "message" => $e->getMessage()];
}

echo json_encode($response);
