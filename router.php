<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$docRoot = __DIR__;
$file = $docRoot . $uri;

// Serve static files directly
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Handle .htaccess-style rewrites for PHP built-in server
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$queryStr = $query ? '?' . $query : '';

// Strip leading slash
$path = ltrim($uri, '/');

// administrator/editor/theme routes
if (preg_match('#^administrator/editor/theme/([A-Za-z0-9-]+)/([A-Za-z0-9-]+)/?$#', $path, $m)) {
    $_GET['theme'] = $m[1];
    $_GET['preset'] = $m[2];
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/administrator/editor/index.php';
    return;
}

// administrator routes
if (preg_match('#^administrator/([A-Za-z0-9-]+)/?$#', $path, $m)) {
    $_GET['page'] = 'admin';
    $_GET['p'] = $m[1];
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/index.php';
    return;
}

// profile routes
if (preg_match('#^profile/([0-9]+)/([A-Za-z0-9-]+)/?$#', $path, $m)) {
    $_GET['page'] = 'profile';
    $_GET['id'] = $m[1];
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/index.php';
    return;
}

// @username routes
if (preg_match('#^@([A-Za-z0-9_.]+)/?$#', $path, $m)) {
    $_GET['page'] = 'profile';
    $_GET['id'] = $m[1];
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/index.php';
    return;
}

// @username/section routes
if (preg_match('#^@([A-Za-z0-9-]+)/([A-Za-z0-9-]+)?$#', $path, $m)) {
    $_GET['page'] = 'profile';
    $_GET['id'] = $m[1];
    $_GET['s'] = $m[2] ?? '';
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/index.php';
    return;
}

// m_profile routes
if (preg_match('#^m_profile/([0-9]+)/([A-Za-z0-9-]+)/?$#', $path, $m)) {
    $_GET['page'] = 'profile';
    $_GET['id'] = $m[1];
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/mobile.php';
    return;
}

// mobile app routes — serve mobile/index.php for all /mobile paths (AngularJS HTML5 mode)
if ($path === 'mobile' || $path === 'mobile/' || strpos($path, 'mobile/') === 0) {
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/mobile/index.php';
    return;
}

// chat routes
if (preg_match('#^chat/([0-9]+)/([A-Za-z0-9-]+)/?$#', $path, $m)) {
    $_GET['page'] = 'chat';
    $_GET['id'] = $m[1];
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/index.php';
    return;
}

// search routes -> logout
if (preg_match('#^search/#', $path)) {
    $_GET['page'] = 'logout';
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/index.php';
    return;
}

// Single segment routes
if (preg_match('#^([A-Za-z0-9-]+)/?$#', $path, $m) && $path !== '') {
    $_GET['page'] = $m[1];
    parse_str($query ?? '', $extra); $_GET = array_merge($_GET, $extra);
    require $docRoot . '/index.php';
    return;
}

// Default: serve index.php
require $docRoot . '/index.php';
