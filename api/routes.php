<?php

declare(strict_types=1);

return [
    [
        'method' => 'GET',
        'endpoint' => '/health',
        'handler' => 'Health',
    ],
    [
        'method' => 'GET',
        'endpoint' => '/arbres',
        'handler' => 'GetAllArbres',
    ],
    [
        'method' => 'GET',
        'endpoint' => '/arbres/{id}',
        'handler' => 'GetArbreById',
    ],
    [
        'method' => 'POST',
        'endpoint' => '/arbres',
        'handler' => 'CreateArbre',
    ],
    [
        'method' => 'PUT',
        'endpoint' => '/arbres/{id}',
        'handler' => 'UpdateArbre',
    ],
    [
        'method' => 'DELETE',
        'endpoint' => '/arbres/{id}',
        'handler' => 'DeleteArbre',
    ],
];
