<?php
namespace Application;

use PDO;

class Mail {
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // CREATE
    public function createMail(string $subject, string $body): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO mail (subject, body) VALUES (?, ?) RETURNING id"
        );
        $stmt->execute([$subject, $body]);
        return (int)$stmt->fetchColumn();
    }

    // READ ONE
    public function getMail(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT id, subject, body FROM mail WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    // READ ALL
    public function getAllMail(): array
    {
        $stmt = $this->pdo->query("SELECT id, subject, body FROM mail ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function updateMail(int $id, string $subject, string $body): bool
    {
        $stmt = $this->pdo->prepare("UPDATE mail SET subject = ?, body = ? WHERE id = ?");
        $stmt->execute([$subject, $body, $id]);
        return $stmt->rowCount() > 0; // false if id doesn't exist
    }

    // DELETE
    public function deleteMail(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM mail WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0; // false if id doesn't exist
    }
}
