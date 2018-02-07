<?php

namespace Waynik\Models;

use Waynik\Repository\DataConnectionInterface;

class UserModel
{
    private $storageHelper;

    private $table = "users";

    private $fields = [
        'id',
        'email',
        'password',
        'token'
    ];

    public function __construct(DataConnectionInterface $dataConnection)
    {
        $this->storageHelper = $dataConnection;
    }

    public function authenticate(array $headers)
    {
        if (!array_key_exists('email', $headers) || !array_key_exists('token', $headers)) {
            throw new \Exception('email and token are required headers', 401);
        }

        $token = $headers['token'];
        if (!$token) {
            $token = $headers['token'][0];
        }

        $email = $headers['email'];
        if (!$email) {
            $email = $headers['email'][0];
        }

        $sql = "SELECT u.id, u.email, u.name, if(count(*) > 0 and c.created_at is not NULL, 1, 0) as hasCheckins 
                FROM users u 
                JOIN user_custom_fields ucf ON ucf.user_id = u.id AND ucf.attribute = 'apiToken' AND ucf.value = ? 
                LEFT JOIN checkins c ON c.user_id = u.id 
        		WHERE u.email = ? GROUP BY u.id LIMIT 1";
        $params = [$token, $email];

        $results = $this->storageHelper->query($sql, $params);
        if (!$results) {
            throw new \Exception("Invalid user credentials", 401);
        }

        $user = array_shift($results);
        return $user;
    }

}