<?php
/*
 * Development router for PHP's built-in web server.
 *
 * Run from this folder:   php -S localhost:8000 server.php
 * Then open:              http://localhost:8000/frontend/
 *
 *  - /frontend/...  -> static files (HTML, CSS, JS)
 *  - /backend/...   -> backend/index.php
 *
 * With XAMPP/MAMP (Apache) you do not need this file – backend/.htaccess does the same job.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/' || $path === '') {
    header('Location: /frontend/');
    return true;
}

if (strpos($path, '/backend') === 0) {
    $file = __DIR__ . $path;

    // Folders with an index.html (e.g. backend/public/v1/docs/ = Swagger UI)
    if (is_dir($file) && is_file(rtrim($file, '/') . '/index.html')) {
        if (substr($path, -1) !== '/') {
            header("Location: $path/");
            return true;
        }
        header('Content-Type: text/html; charset=utf-8');
        readfile(rtrim($file, '/') . '/index.html');
        return true;
    }
    // Existing non-PHP files inside backend/ are served as they are
    if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
        return false;
    }
    // Existing PHP files (e.g. backend/public/v1/docs/swagger.php) are executed directly
    if (is_file($file) && basename($file) !== 'index.php') {
        chdir(dirname($file));
        require $file;
        return true;
    }
    // Everything else goes to backend/index.php
    $_SERVER['SCRIPT_NAME'] = '/backend/index.php';
    chdir(__DIR__ . '/backend');
    require __DIR__ . '/backend/index.php';
    return true;
}

return false; // let the built-in server serve the static file
