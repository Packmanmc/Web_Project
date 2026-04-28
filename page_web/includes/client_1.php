<?php
function predictTreeSize(float $haut_tot, float $diam_tronc, int $k = 3): array
{
    $scriptPath = dirname(__DIR__) . '/script/client_1/script1.py';
    $output = [];
    $returnCode = 0;
    
    if (!file_exists($scriptPath)) {
        return ['error' => 'Script de prédiction non trouvé', 'status' => 'error'];
    }

    if ($haut_tot <= 0 || $diam_tronc <= 0) {
        return ['error' => 'Hauteur et diamètre doivent être positifs', 'status' => 'error'];
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
        return ['error' => 'Erreur lors de l\'exécution du script (code: ' . $returnCode . ')', 'status' => 'error'];
    }

    $resultJson = implode('', $output);
    $result = json_decode($resultJson, true);
    
    if ($result === null) {
        return ['error' => 'Erreur de parsing JSON: ' . $resultJson, 'status' => 'error'];
    }

    if (($result['status'] ?? null) === 'error') {
        return $result;
    }

    return $result;
}

function predictClusters(array $arbres, int $k = 3): array
{
    $results = [];
    
    foreach ($arbres as $arbre) {
        try {
            $haut = (float) ($arbre['haut_tot'] ?? 0);
            $diam = (float) ($arbre['diam_tronc'] ?? 0);
            
            if ($haut > 0 && $diam > 0) {
                $prediction = predictTreeSize($haut, $diam, $k);
                
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
    
    return $results;
}


