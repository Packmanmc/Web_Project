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
            AVG(haut_tot) AS hauteur_moyenne,
            AVG(age_estime) AS age_moyen
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
            'message' => 'Arbre cree',
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
        'nb_diagnostic',
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


function buildArbrePayload(array $body): array
{
    return [
        'X' => (float) $body['X'],
        'Y' => (float) $body['Y'],
        'haut_tot' => (float) $body['haut_tot'],
        'haut_tronc' => (float) $body['haut_tronc'],
        'diam_tronc' => (float) $body['diam_tronc'],
        'age_estime' => (int) $body['age_estime'],
        'nb_diagnostic' => (int) $body['nb_diagnostic'],
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

