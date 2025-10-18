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
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - Page Not Found</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    background-color: #f8f9fa;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    text-align: center;
                    font-family: Arial, sans-serif;
                }
                .container {
                    max-width: 600px;
                    padding: 20px;
                    background: #fff;
                    border-radius: 12px;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                }
                h1 {
                    font-size: 6rem;
                    color: #dc3545;
                }
                p {
                    font-size: 1.2rem;
                    margin: 20px 0;
                }
                a.btn-home {
                    text-decoration: none;
                    font-weight: bold;
                    padding: 10px 20px;
                    border-radius: 8px;
                    background-color: #0d6efd;
                    color: white;
                    transition: background-color 0.3s ease;
                }
                a.btn-home:hover {
                    background-color: #0b5ed7;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>404</h1>
                <p>Oops! The page you are looking for <strong><?= htmlspecialchars($url) ?></strong> does not exist.</p>
                <p>Don't worry, you can go back to the home page.</p>
                <a href="/home" class="btn-home">Go to Home</a>
            </div>
        </body>
        </html>
        <?php
    }
}
