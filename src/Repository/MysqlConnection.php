<?php

namespace Waynik\Repository;

use PDO;

class MysqlConnection implements DataConnectionInterface
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = new PDO(
            'mysql:dbname=' . getenv('DB_NAME') . ';host=' . getenv('DB_HOST'),
            getenv('DB_USER'),
            getenv('DB_PASS')
        );
    }

    public function query($sqlString, array $params)
    {
        $stmt = $this->pdo->prepare($sqlString);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($sqlString, array $params)
    {
        $stmt = $this->pdo->prepare($sqlString);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }
}
