<?php

// Router for PHP built-in server used in integration tests - fake file server and fake MJML API

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/files/report.txt') {
    header('Content-Type: text/plain');
    echo 'remote content';
    return;
}

if ($path === '/v1/render' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Basic ' . base64_encode('app:secret')) {
        http_response_code(401);
        echo json_encode([ 'message' => 'Unauthorized' ]);
        return;
    }

    $mjml = json_decode(file_get_contents('php://input'), true)['mjml'] ?? '';
    $errors = str_contains($mjml, '<mj-invalid')
        ? [ [ 'line' => 1, 'message' => 'Element mj-invalid doesn\'t exist', 'tagName' => 'mj-invalid', 'formattedMessage' => 'Line 1 (mj-invalid) - Element mj-invalid doesn\'t exist' ] ]
        : [];

    echo json_encode([
        'html'          => '<html><body>' . strip_tags($mjml) . '</body></html>',
        'mjml'          => $mjml,
        'mjml_version'  => '4.15.3',
        'errors'        => $errors,
    ]);
    return;
}

http_response_code(404);
echo json_encode([ 'message' => 'Not Found' ]);
