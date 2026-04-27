<?php

declare(strict_types=1);

final class Database
{
    private PDO $pdo;

    public function __construct()
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('DB_PORT') ?: 3306);
        $name = getenv('DB_NAME') ?: '';
        $user = getenv('DB_USER') ?: '';
        $pass = getenv('DB_PASS') ?: '';

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);

        try{
            $this->pdo = new PDO($dsn, $user, $pass);
        }
    
        catch(Exception $ex){
            echo "erreur liee a la BDD :".utf8_encode($ex->getMessage())."<br/>";
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
