<?php
require '../../../vendor/autoload.php';

use Application\Mail;
use Application\Page;

$dsn = "pgsql:host=" . getenv('DB_PROD_HOST') . ";dbname=" . getenv('DB_PROD_NAME');

try {
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

header('Content-Type: application/json');

$mail = new Mail($pdo);
$page = new Page();

// Extract id from URL (works with /api/mail/1 or /api/mail/1/)
$uri = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim($uri, '/'));
$id = (int)end($parts);

if ($id <= 0) {
    $page->badRequest();
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $item = $mail->getMail($id);
    if (!$item) {
        $page->notFound(); // 404 + {"error":"Not found"}
        exit;
    }
    $page->item($item);
    exit;
}

if ($method === 'PUT') {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    if (!is_array($data) || empty($data['subject']) || empty($data['body'])) {
        $page->badRequest();
        exit;
    }

    $ok = $mail->updateMail($id, $data['subject'], $data['body']);
    if (!$ok) {
        $page->notFound();
        exit;
    }

    $page->item($mail->getMail($id));
    exit;
}

if ($method === 'DELETE') {
    $ok = $mail->deleteMail($id);
    if (!$ok) {
        $page->notFound();
        exit;
    }

    http_response_code(200);
    echo json_encode(["deleted" => true]);
    exit;
}

$page->badRequest();
