<?php
namespace Application;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Verifier
{
    public $userId;
    public $role;

    public function decode($jwt)
    {
        if (empty($jwt)) {
            return false;
        }

        $jwt = trim($jwt);

        if (substr($jwt, 0, 7) === 'Bearer ') {
            $jwt = substr($jwt, 7);
        }

        if ($jwt === '') {
            return false;
        }

        try {
            $token = JWT::decode($jwt, new Key('f94e4ad0-94c6-453d-b2cb-51fe3898c43f-mail-a2', 'HS256'));
            $this->userId = (int) $token->userId;
            $this->role = (string) $token->role;
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
