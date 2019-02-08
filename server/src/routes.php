<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/chat', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/chat' route");

    // Render index view
    return $this->renderer->render($response, 'chat.phtml', $args);
});
$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
