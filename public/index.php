<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

function check(string $label, callable $fn): void
{
    try {
        $fn();
        echo "<p>✅ $label : OK</p>";
    } catch (Throwable $e) {
        echo "<p>❌ $label : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo '<h1>Vite &amp; Gourmand : test de l\'environnement</h1>';
echo '<p>PHP ' . PHP_VERSION . '</p>';

check('MySQL (PDO)', function () {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('MYSQL_HOST'), getenv('MYSQL_DATABASE')),
        getenv('MYSQL_USER'),
        getenv('MYSQL_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->query('SELECT 1');
});

check('MongoDB', function () {
    $client = new MongoDB\Client(getenv('MONGO_URI'));
    $client->selectDatabase('admin')->command(['ping' => 1]);
});
