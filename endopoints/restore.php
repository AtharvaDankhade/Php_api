<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $headers = getallheaders();
    if (isset($headers['Username'])) {
        $username = $headers['Username'];
    } else {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => 'error', 'message' => 'Username header is missing']);
        exit;
    }

    $host = 'localhost';
    $dbUsername = 'root';
    $password = '';
    $database = 'practice';
    $restoreDir = 'restores';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database", $dbUsername, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT Role FROM data WHERE Username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['Role'] !== 'ADMIN') {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['status' => 'error', 'message' => 'Access denied: You must be an admin to perform this action']);
            exit;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
            exit;
        }

        if (!file_exists($restoreDir)) {
            mkdir($restoreDir, 0777, true);
        }

        $uploadedFile = $_FILES['file']['tmp_name'];
        $restoreFile = $restoreDir . '/' . basename($_FILES['file']['name']);

        if (!move_uploaded_file($uploadedFile, $restoreFile)) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
            exit;
        }

        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($dbUsername),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($restoreFile)
        );

        $output = [];
        $return_var = null;
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            $response['status'] = 'success';
            $response['message'] = 'Database restored successfully';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Error restoring database. Return code: ' . $return_var;
            $response['errorDetails'] = implode("\n", $output);
        }

        unlink($restoreFile);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        $response['status'] = 'error';
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
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
