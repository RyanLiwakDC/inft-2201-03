<?php
use PHPUnit\Framework\TestCase;
use Application\Mail;

class MailTest extends TestCase {
    protected PDO $pdo;

    protected function setUp(): void
    {
        $dsn = "pgsql:host=" . getenv('DB_TEST_HOST') . ";dbname=" . getenv('DB_TEST_NAME');
        $this->pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("DROP TABLE IF EXISTS mail;");
        $this->pdo->exec("
            CREATE TABLE mail (
                id SERIAL PRIMARY KEY,
                subject TEXT NOT NULL,
                body TEXT NOT NULL
            );
        ");
    }

    public function testCreateMail(): void
    {
        $mail = new Mail($this->pdo);
        $id = $mail->createMail("Alice", "Hello world");
        $this->assertIsInt($id);
        $this->assertEquals(1, $id);
    }

    public function testGetMail(): void
    {
        $mail = new Mail($this->pdo);
        $id = $mail->createMail("Subject 1", "Body 1");

        $row = $mail->getMail($id);
        $this->assertIsArray($row);
        $this->assertEquals($id, (int)$row['id']);
        $this->assertEquals("Subject 1", $row['subject']);
        $this->assertEquals("Body 1", $row['body']);
    }

    public function testGetMailReturnsFalseWhenMissing(): void
    {
        $mail = new Mail($this->pdo);
        $this->assertFalse($mail->getMail(9999));
    }

    public function testGetAllMail(): void
    {
        $mail = new Mail($this->pdo);
        $mail->createMail("S1", "B1");
        $mail->createMail("S2", "B2");

        $all = $mail->getAllMail();
        $this->assertCount(2, $all);
        $this->assertEquals("S1", $all[0]['subject']);
        $this->assertEquals("S2", $all[1]['subject']);
    }

    public function testUpdateMail(): void
    {
        $mail = new Mail($this->pdo);
        $id = $mail->createMail("Old", "Old body");

        $ok = $mail->updateMail($id, "New", "New body");
        $this->assertTrue($ok);

        $row = $mail->getMail($id);
        $this->assertEquals("New", $row['subject']);
        $this->assertEquals("New body", $row['body']);
    }

    public function testUpdateMailReturnsFalseWhenMissing(): void
    {
        $mail = new Mail($this->pdo);
        $this->assertFalse($mail->updateMail(9999, "X", "Y"));
    }

    public function testDeleteMail(): void
    {
        $mail = new Mail($this->pdo);
        $id = $mail->createMail("To delete", "Bye");

        $ok = $mail->deleteMail($id);
        $this->assertTrue($ok);
        $this->assertFalse($mail->getMail($id));
    }

    public function testDeleteMailReturnsFalseWhenMissing(): void
    {
        $mail = new Mail($this->pdo);
        $this->assertFalse($mail->deleteMail(9999));
    }
}
