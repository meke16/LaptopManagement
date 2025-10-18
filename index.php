<?php
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // only the path (e.g., /display)
$query = $_SERVER['QUERY_STRING'] ?? ''; // optional query (e.g., id=1)

$routes = [
    '/' => 'home.php',
    '/home' => 'home.php',
    '/display' => 'display.php',
    '/update' => 'update.php',
];

// check if path exists
if (array_key_exists($url, $routes)) {
    include $routes[$url];
} else {
    // if it starts with /display or /update and has ?id=...
    if (preg_match('#^/display$#', $url) && isset($_GET['id'])) {
        include 'display.php';
    } elseif (preg_match('#^/update$#', $url) && isset($_GET['id'])) {
        include 'update.php';
    } else {
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
        echo "<p>The page '$url' was not found.</p>";
    }
}
?>
