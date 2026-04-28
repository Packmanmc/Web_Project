<?php

declare(strict_types=1);


function Health(PDO $pdo, array $params = []): array
{
    $pdo->query('SELECT 1');

    return [
        'status' => 200,
        'body' => ['status' => 'ok'],
    ];
}

function GetStats(PDO $pdo, array $params = []): array
{
    $sql = <<<SQL
        SELECT 
            COUNT(*) AS total, 
            SUM(remarquable) AS remarquables, 
            COUNT(DISTINCT id_especes) AS especes,
            ROUND(AVG(haut_tot), 2) AS hauteur_moyenne,
            ROUND(AVG(age_estime), 2) AS age_moyen
        FROM arbre
    SQL;
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'status' => 200,
        'body' => [
            'total' => (int) ($row['total'] ?? 0),
            'remarquables' => (int) ($row['remarquables'] ?? 0),
            'especes' => (int) ($row['especes'] ?? 0),
            'hauteur_moyenne' => (float) ($row['hauteur_moyenne'] ?? 0),
            'age_moyen' => (float) ($row['age_moyen'] ?? 0),
        ],
    ];
}

function GetAllArbres(PDO $pdo, array $params = []): array
{
    $sql = <<<SQL
        SELECT
            a.id_arbre,
            a.X,
            a.Y,
            a.haut_tot,
            a.haut_tronc,
            a.diam_tronc,
            a.age_estime,
            a.nb_diagnostic,
            a.date_plantage,
            a.date_abattage,
            a.remarquable,
            a.id_stade_dev,
            sd.libelle AS stade_dev,
            a.id_especes,
            e.nom AS espece_nom,
            e.Feuillage AS espece_feuillage,
            a.id_etat,
            et.libelle AS etat,
            a.id_quartiers,
            q.quartier,
            q.secteur,
            a.id_situations,
            s.libelle AS situation
        FROM arbre a
        INNER JOIN stade_dev sd ON sd.id = a.id_stade_dev
        INNER JOIN especes e ON e.id = a.id_especes
        INNER JOIN etat et ON et.id = a.id_etat
        INNER JOIN quartiers q ON q.id = a.id_quartiers
        INNER JOIN situations s ON s.id = a.id_situations
        ORDER BY a.id_arbre ASC
    SQL;

    $stmt = $pdo->query($sql);

    return [
        'status' => 200,
        'body' => ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)],
    ];
}

function GetArbreById(PDO $pdo, array $params = []): array
{
    $idArbre = (int) ($params['id'] ?? 0);

    if ($idArbre <= 0) {
        return [
            'status' => 400,
            'body' => ['error' => 'Identifiant invalide'],
        ];
    }

     $sql = <<<SQL
        SELECT
            a.id_arbre,
            a.X,
            a.Y,
            a.haut_tot,
            a.haut_tronc,
            a.diam_tronc,
            a.age_estime,
            a.nb_diagnostic,
            a.date_plantage,
            a.date_abattage,
            a.remarquable,
            a.id_stade_dev,
            sd.libelle AS stade_dev,
            a.id_especes,
            e.nom AS espece_nom,
            e.Feuillage AS espece_feuillage,
            a.id_etat,
            et.libelle AS etat,
            a.id_quartiers,
            q.quartier,
            q.secteur,
            a.id_situations,
            s.libelle AS situation
        FROM arbre a
        INNER JOIN stade_dev sd ON sd.id = a.id_stade_dev
        INNER JOIN especes e ON e.id = a.id_especes
        INNER JOIN etat et ON et.id = a.id_etat
        INNER JOIN quartiers q ON q.id = a.id_quartiers
        INNER JOIN situations s ON s.id = a.id_situations
        WHERE a.id_arbre = :idArbre
        ORDER BY a.id_arbre ASC
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['idArbre' => $idArbre]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row === false) {
        return [
            'status' => 404,
            'body' => ['error' => 'Arbre non trouvé'],
        ];
    }

    return [
        'status' => 200,
        'body' => ['data' => $row],
    ];
}

function CreateArbre(PDO $pdo, array $params = []): array
{
    $body = $params['body'] ?? [];

    if (!is_array($body)) {
        return [
            'status' => 400,
            'body' => ['error' => 'Corps JSON invalide'],
        ];
    }

    $missingFields = getMissingArbreFields($body);

    if ($missingFields !== []) {
        return [
            'status' => 400,
            'body' => [
                'error' => 'Champs obligatoires manquants',
                'missing' => $missingFields,
            ],
        ];
    }

    $payload = buildArbrePayload($body);

    $SQL = <<<SQL
        INSERT INTO arbre (X, Y, haut_tot, haut_tronc, diam_tronc, age_estime, nb_diagnostic, date_plantage, date_abattage, remarquable, id_stade_dev, id_especes, id_etat, id_quartiers, id_situations)
        VALUES (:X, :Y, :haut_tot, :haut_tronc, :diam_tronc, :age_estime, :nb_diagnostic, :date_plantage, :date_abattage, :remarquable, :id_stade_dev, :id_especes, :id_etat, :id_quartiers, :id_situations)
    SQL;

    $stmt = $pdo->prepare($SQL);
    $stmt->execute($payload);

    return [
        'status' => 201,
        'body' => [
            'message' => 'Arbre crée',
            'data' => ['id_arbre' => (int) $pdo->lastInsertId()],
        ],
    ];
}

function UpdateArbre(PDO $pdo, array $params = []): array
{
    $idArbre = (int) ($params['id'] ?? 0);
    $body = $params['body'] ?? [];

    if (!is_array($body)) {
        return [
            'status' => 400,
            'body' => ['error' => 'Corps JSON invalide'],
        ];
    }

    if ($idArbre <= 0) {
        return [
            'status' => 400,
            'body' => ['error' => 'Identifiant invalide'],
        ];
    }

    $missingFields = getMissingArbreFields($body);

    if ($missingFields !== []) {
        return [
            'status' => 400,
            'body' => [
                'error' => 'Champs obligatoires manquants',
                'missing' => $missingFields,
            ],
        ];
    }

    $payload = buildArbrePayload($body);

    $SQL = <<<SQL
        UPDATE arbre
        SET X = :X, Y = :Y, haut_tot = :haut_tot, haut_tronc = :haut_tronc, diam_tronc = :diam_tronc, age_estime = :age_estime, nb_diagnostic = :nb_diagnostic, date_plantage = :date_plantage, date_abattage = :date_abattage, remarquable = :remarquable, id_stade_dev = :id_stade_dev, id_especes = :id_especes, id_etat = :id_etat, id_quartiers = :id_quartiers, id_situations = :id_situations
        WHERE id_arbre = :idArbre
    SQL;

    $stmt = $pdo->prepare($SQL);
    $stmt->execute(array_merge($payload, ['idArbre' => $idArbre]));

    if ($stmt->rowCount() === 0) {
        return [
            'status' => 404,
            'body' => ['error' => 'Arbre non trouve ou donnees inchangees'],
        ];
    }

    return [
        'status' => 200,
        'body' => ['message' => 'Arbre mis a jour'],
    ];
}

function DeleteArbre(PDO $pdo, array $params = []): array
{
    $idArbre = (int) ($params['id'] ?? 0);

    if ($idArbre <= 0) {
        return [
            'status' => 400,
            'body' => ['error' => 'Identifiant invalide'],
        ];
    }

    $SQL = 'DELETE FROM arbre WHERE id_arbre = :idArbre';

    $stmt = $pdo->prepare($SQL);
    $stmt->execute(['idArbre' => $idArbre]);

    return [
        'status' => 200,
        'body' => ['message' => 'Arbre supprimé'],
    ];
}

function getMissingArbreFields(array $body): array
{
    $required = [
        'X',
        'Y',
        'haut_tot',
        'haut_tronc',
        'diam_tronc',
        'age_estime',
        'remarquable',
        'id_stade_dev',
        'id_especes',
        'id_etat',
        'id_quartiers',
        'id_situations',
    ];

    $missing = [];

    foreach ($required as $field) {
        if (!array_key_exists($field, $body)) {
            $missing[] = $field;
        }
    }

    return $missing;
}


function getAllEspeces(PDO $pdo): array
{
    $sql = 'SELECT id, nom FROM especes ORDER BY nom ASC';
    $stmt = $pdo->query($sql);

    return [
        'status' => 200,
        'body' => ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)],
    ];
}


function getAllQuartiers(PDO $pdo): array
{
    $sql = 'SELECT id, quartier, secteur FROM quartiers ORDER BY quartier ASC';
    $stmt = $pdo->query($sql);

    return [
        'status' => 200,
        'body' => ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)],
    ];
}

function getAllStadeDev(PDO $pdo): array
{
    $sql = 'SELECT id, libelle FROM stade_dev ORDER BY libelle ASC';
    $stmt = $pdo->query($sql);

    return [
        'status' => 200,
        'body' => ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)],
    ];
}

function getAllSituations(PDO $pdo): array
{
    $sql = 'SELECT id, libelle FROM situations ORDER BY libelle ASC';
    $stmt = $pdo->query($sql);

    return [
        'status' => 200,
        'body' => ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)],
    ];
}

function getAllEtat(PDO $pdo): array
{
    $sql = 'SELECT id, libelle FROM etat ORDER BY libelle ASC';
    $stmt = $pdo->query($sql);

    return [
        'status' => 200,
        'body' => ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)],
    ];
}

function PredictTreeSize(PDO $pdo, array $params = []): array
{

    $body = $params['body'] ?? [];
    $haut_tot = isset($body['haut_tot']) ? (float) $body['haut_tot'] : null;
    $diam_tronc = isset($body['diam_tronc']) ? (float) $body['diam_tronc'] : null;
    $k = isset($body['k']) ? (int) $body['k'] : 3;

    $scriptPath = 'client_1/script1.py';
    $output = [];
    $returnCode = 0;

    if (!file_exists($scriptPath)) {
        return ['status' => 404, 'body' => ['error' => 'Script de prédiction non trouvé']];
    }

    if ($haut_tot === null || $diam_tronc === null || $haut_tot <= 0 || $diam_tronc <= 0) {
        return ['status' => 400, 'body' => ['error' => 'Hauteur et diamètre doivent être des nombres positifs']];
    }

    if ($k !== 2 && $k !== 3) {
        $k = 3;
    }

    $cmd = 'python3 ' . escapeshellarg($scriptPath) . ' ' .
           escapeshellarg((string) $haut_tot) . ' ' .
           escapeshellarg((string) $diam_tronc) . ' ' .
           escapeshellarg((string) $k);

    exec($cmd, $output, $returnCode);


    if ($returnCode !== 0) {
        return ['status' => 500, 'body' => ['error' => 'Erreur lors de l\'exécution du script (code: ' . $returnCode . ')']];
    }

    $resultJson = implode('', $output);
    $result = json_decode($resultJson, true);

    if ($result === null) {
        return ['status' => 500, 'body' => ['error' => 'Erreur de parsing JSON: ' . $resultJson]];
    }

    if (($result['status'] ?? null) === 'error') {
        return ['status' => 500, 'body' => $result];
    }

    return [
        'status' => 200, 
        'body' => $result
    ];
    
}

function PredictClusters(PDO $pdo, array $params = []): array
{
    $body = $params['body'] ?? [];
    $k = isset($body['k']) ? (int) $body['k'] : 3;

    $arbres = getAllArbres($pdo)['body']['data'] ?? [];

    $results = [];
    
    foreach ($arbres as $arbre) {
        try {
            $haut = (float) ($arbre['haut_tot'] ?? 0);
            $diam = (float) ($arbre['diam_tronc'] ?? 0);
            
            if ($haut > 0 && $diam > 0) {
                $prediction = predictTreeSize($pdo, ['haut_tot' => $haut, 'diam_tronc' => $diam, 'k' => $k]);
                
                if ($prediction && ($prediction['status'] ?? null) === 'success') {
                    $arbre['cluster'] = $prediction['cluster'] ?? null;
                    $arbre['cluster_label'] = $prediction['categorie'] ?? null;
                }
            }
            
            $results[] = $arbre;
        } catch (Exception $e) {
            $results[] = $arbre;
        }
    }
    
    return [
        'status' => 200, 
        'body' => $results
    ];
}





function buildArbrePayload(array $body): array
{
    return [
        'X' => (float) $body['X'],
        'Y' => (float) $body['Y'],
        'haut_tot' => (float) $body['haut_tot'],
        'haut_tronc' => (float) $body['haut_tronc'],
        'diam_tronc' => (float) $body['diam_tronc'],
        'age_estime' => (int) ($body['age_estime'] ?? 0),
        'nb_diagnostic' => (int) ($body['nb_diagnostic'] ?? 0),
        'date_plantage' => normalizeNullableDate($body['date_plantage'] ?? null),
        'date_abattage' => normalizeNullableDate($body['date_abattage'] ?? null),
        'remarquable' => (int) ((bool) $body['remarquable']),
        'id_stade_dev' => (int) $body['id_stade_dev'],
        'id_especes' => (int) $body['id_especes'],
        'id_etat' => (int) $body['id_etat'],
        'id_quartiers' => (int) $body['id_quartiers'],
        'id_situations' => (int) $body['id_situations'],
    ];
}

function normalizeNullableDate(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    $stringValue = trim((string) $value);

    if ($stringValue === '') {
        return null;
    }

    return $stringValue;
}

