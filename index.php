<?php 

// manual route...

$url = $_SERVER['REQUEST_URI'];

$paths = [
    '/' => 'home.php',
    '/home' => 'home.php',
    '/display' => 'display.php',

];

if(array_key_exists($url,$paths)) {
    require $paths[$url];
} else {
    echo "not found";
}

