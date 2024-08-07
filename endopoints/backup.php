<?php

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    $response = [];

    $headers = getallheaders();
    if (isset($headers['Username'])) {
        $username = $headers['Username'];
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Username header is missing';
        echo json_encode($response);
        exit;
    }

    $host = 'localhost';
    $dbUsername = 'root';
    $password = '';
    $database = 'practice';
    $backupDir = 'backups';
    $backupFile = $backupDir . '/' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database", $dbUsername, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT Role FROM data WHERE Username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['Role'] !== 'ADMIN') {
            $response['status'] = 'error';
            $response['message'] = 'Access denied: You must be an admin to perform this action';
            echo json_encode($response);
            exit;
        }

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $command = sprintf(
            'mysqldump --column-statistics=0 --host=%s --user=%s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($dbUsername),
            escapeshellarg($database),
            escapeshellarg($backupFile)
        );

        $output = [];
        $return_var = null;
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            $response['status'] = 'success';
            $response['message'] = 'Database backup created successfully';
            $response['backupFile'] = $backupFile;
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Error creating database backup. Return code: ' . $return_var;
            $response['errorDetails'] = implode("\n", $output);
        }
    } catch (PDOException $e) {
        $response['status'] = 'error';
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
} else {
    $data = [];
    $data['status'] = 'failed';
    $data['message'] = 'Method Type is not correct';
    echo json_encode($data);
}
