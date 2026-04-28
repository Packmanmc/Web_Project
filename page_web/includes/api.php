<?php
declare(strict_types=1);

define('API_URL', getenv('API_URL'));

function api_get(string $path): mixed
{
    $ch = curl_init(API_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body ?: '[]', true);
}

function api_post(string $path, array $data): mixed
{
    $ch = curl_init(API_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 5,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body ?: '{}', true);
}

function api_put(string $path, array $data): mixed
{
    $ch = curl_init(API_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 5,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body ?: '{}', true);
}

function api_delete(string $path): mixed
{
    $ch = curl_init(API_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_TIMEOUT        => 5,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body ?: '{}', true);
}

function getAllArbres(): array
{
    return api_get('/arbres')['data'] ?? [];
}

function getArbreById(int $id): ?array
{
    $response = api_get('/arbres/' . $id);
    return $response['data'] ?? null;
}

function createArbre(array $payload): ?array
{
    $response = api_post('/arbres', $payload);
    return $response['data'] ?? null;
}

function updateArbre(int $id, array $payload): ?array
{
    $response = api_put('/arbres/' . $id, $payload);
    return $response['data'] ?? null;
}
 
function deleteArbre(int $id): bool
{
    $response = api_delete('/arbres/' . $id);
    return ($response['status'] ?? 0) === 200;
}

function getAllEspeces(): array
{
    return api_get('/especes')['data'] ?? [];
}

function getAllQuartiers(): array
{
    return api_get('/quartiers')['data'] ?? [];
}

function getAllStadeDev(): array
{
    return api_get('/stades')['data'] ?? [];
}

function getAllSituations(): array
{
    return api_get('/situations')['data'] ?? [];
}

function getAllEtat(): array
{
    return api_get('/etats')['data'] ?? [];
}

function getStats(): array
{
    $stats = api_get('/stats');

    return [
        'total' => (int) ($stats['total'] ?? 0),
        'remarquables' => (int) ($stats['remarquables'] ?? 0),
        'especes' => (int) ($stats['especes'] ?? 0),
        'hauteur_moyenne' => (float) ($stats['hauteur_moyenne'] ?? 0),
        'age_moyen' => (float) ($stats['age_moyen'] ?? 0),
    ];
}
