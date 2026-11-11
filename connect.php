<?php
declare(strict_types=1);

final class SQLiteResult
{
    private array $rows;
    private int $position = 0;
    public int $num_rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc(): ?array
    {
        return $this->rows[$this->position++] ?? null;
    }
}

final class SQLiteStatement
{
    private PDOStatement $statement;
    private array $values = [];

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    public function bind_param(string $types, &...$values): void
    {
        $this->values = $values;
    }

    public function execute(): bool
    {
        return $this->statement->execute($this->values);
    }
}

final class SQLiteConnection
{
    private PDO $pdo;
    public string $error = '';

    public function __construct(string $path)
    {
        $this->pdo = new PDO('sqlite:' . $path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    public function exec(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    public function query(string $sql): SQLiteResult
    {
        $statement = $this->pdo->query($sql);
        return new SQLiteResult($statement ? $statement->fetchAll() : []);
    }

    public function prepare(string $sql): SQLiteStatement
    {
        return new SQLiteStatement($this->pdo->prepare($sql));
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}

function escape_sqlite_value(string $value): string
{
    return str_replace("'", "''", $value);
}

try {
    $conn = new SQLiteConnection(__DIR__ . '/database.sqlite');
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS student (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            sex TEXT NOT NULL DEFAULT "",
            idNumber TEXT NOT NULL UNIQUE,
            department TEXT NOT NULL,
            campus TEXT NOT NULL DEFAULT "",
            pcSerialNumber TEXT NOT NULL,
            pcModel TEXT NOT NULL DEFAULT "",
            contact TEXT NOT NULL DEFAULT "",
            photo TEXT NOT NULL DEFAULT "",
            year TEXT NOT NULL DEFAULT "",
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $seed = $conn->pdo()->prepare('SELECT id FROM users WHERE email = ?');
    $seed->execute(['admin@gmail.com']);
    if (!$seed->fetch()) {
        $insertUser = $conn->pdo()->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
        $insertUser->execute(['admin@gmail.com', password_hash('password', PASSWORD_DEFAULT)]);
    }
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed.');
}
