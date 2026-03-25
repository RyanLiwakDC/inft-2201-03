<?php
require __DIR__ . '/../../../autoload.php';

use Application\Mail;
use Application\Database;
use Application\Page;
use Application\Verifier;

function unauthorizedResponse(): void
{
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

function jsonResponse($payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function getAuthorizationHeader(): string
{
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return (string) $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                return (string) $value;
            }
        }
    }

    return '';
}

$database = new Database('prod');
$page = new Page();
$mail = new Mail($database->getDb());
$verifier = new Verifier();

$authorizationHeader = getAuthorizationHeader();
if (!$verifier->decode($authorizationHeader)) {
    unauthorizedResponse();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data) || !array_key_exists('name', $data) || !array_key_exists('message', $data)) {
        $page->badRequest();
        exit;
    }

    $name = trim((string) $data['name']);
    $message = trim((string) $data['message']);

    if ($name === '' || $message === '') {
        $page->badRequest();
        exit;
    }

    $userId = (int) $verifier->userId;
    if ($verifier->role === 'admin' && isset($data['userId']) && is_numeric($data['userId'])) {
        $userId = (int) $data['userId'];
    }

    $id = $mail->createMail($name, $message, $userId);
    jsonResponse([
        'id' => (int) $id,
        'name' => $name,
        'message' => $message,
        'userId' => $userId,
    ], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($verifier->role === 'admin') {
        jsonResponse($mail->listMail());
    }

    jsonResponse($mail->listMailByUserId((int) $verifier->userId));
}

$page->badRequest();
