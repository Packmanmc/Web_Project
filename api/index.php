<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/endpoint_functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$routes = require __DIR__ . '/routes.php';

try {
    $db = new Database();
    $pdo = $db->pdo();
} catch (Throwable $exception) {
    sendJson(500, [
        'error' => 'Connexion à la base de donnees impossible',
        'details' => $exception->getMessage(),
    ]);
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';
$requestBody = parseRequestBody($method);

try {
    foreach ($routes as $route) {
        $routeMethod = strtoupper((string) ($route['method'] ?? ''));
        $routePattern = (string) ($route['endpoint'] ?? '');
        $handler = (string) ($route['handler'] ?? '');

        if ($routeMethod !== $method) {
            continue;
        }

        $params = matchRoute($routePattern, $path);

        if ($params === null) {
            continue;
        }

        if (!function_exists($handler)) {
            sendJson(500, ['error' => 'Handler introuvable']);
        }

        if ($requestBody !== null) {
            $params['body'] = $requestBody;
        }

        $result = $handler($pdo, $params);
        $status = (int) ($result['status'] ?? 200);
        $body = (array) ($result['body'] ?? []);

        sendJson($status, $body);
    }

    sendJson(404, ['error' => 'Route introuvable']);
} catch (PDOException $exception) {
    sendJson(400, [
        'error' => 'Erreur SQL',
        'details' => $exception->getMessage(),
    ]);
} catch (Throwable $exception) {
    sendJson(500, [
        'error' => 'Erreur interne',
        'details' => $exception->getMessage(),
    ]);
}

function sendJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function matchRoute(string $routePattern, string $currentPath): ?array
{
    $patternSegments = splitPath($routePattern);
    $pathSegments = splitPath($currentPath);

    if (count($patternSegments) !== count($pathSegments)) {
        return null;
    }

    $params = [];

    foreach ($patternSegments as $index => $segment) {
        $value = $pathSegments[$index];

        if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $matches) === 1) {
            $params[$matches[1]] = $value;
            continue;
        }

        if ($segment !== $value) {
            return null;
        }
    }

    return $params;
}

function splitPath(string $path): array
{
    $trimmed = trim($path, '/');

    if ($trimmed === '') {
        return [];
    }

    return explode('/', $trimmed);
}

function parseRequestBody(string $method): ?array
{
    if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        return null;
    }

    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);

    if (!is_array($decoded)) {
        sendJson(400, ['error' => 'Corps JSON invalide']);
    }

    return $decoded;
}
