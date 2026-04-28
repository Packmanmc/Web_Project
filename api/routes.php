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
        'endpoint' => '/stats',
        'handler' => 'GetStats',
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
    [
        'method' => 'GET',
        'endpoint' => '/especes',
        'handler' => 'GetAllEspeces',
    ],
    [
        'method' => 'GET',
        'endpoint' => '/quartiers',
        'handler' => 'GetAllQuartiers',
    ],
    [
        'method' => 'GET',
        'endpoint' => '/stades',
        'handler' => 'GetAllStadeDev',
    ],
    [
        'method' => 'GET',
        'endpoint' => '/situations',
        'handler' => 'GetAllSituations',
    ],
    [
        'method' => 'GET',
        'endpoint' => '/etats',
        'handler' => 'GetAllEtat',
    ],
    [
        'method' => 'POST',
        'endpoint' => '/predict',
        'handler' => 'PredictTreeSize',
    ],
    [
        'method' => 'POST',
        'endpoint' => '/predict-clusters',
        'handler' => 'PredictClusters',
    ],
];
