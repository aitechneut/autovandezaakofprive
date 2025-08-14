<?php
/**
 * RDW Open Data API Handler
 * 
 * Dit bestand handelt kenteken lookups af via de RDW Open Data API
 * Geen API key nodig - gratis toegang tot publieke voertuiggegevens
 * 
 * @author Richard Surie
 * @version 1.0.0
 */

// Enable error reporting voor development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Zet op 1 voor debugging

// Set headers voor JSON response
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Formateer kenteken voor RDW API
 * Verwijder alle streepjes en spaties, converteer naar uppercase
 */
function formatLicensePlate($kenteken) {
    $kenteken = strtoupper($kenteken);
    $kenteken = preg_replace('/[^A-Z0-9]/', '', $kenteken);
    return $kenteken;
}

/**
 * Haal voertuiggegevens op van RDW API
 */
function getVehicleData($kenteken) {
    try {
        // Format kenteken voor API
        $formattedKenteken = formatLicensePlate($kenteken);
        
        if (empty($formattedKenteken)) {
            throw new Exception('Ongeldig kenteken formaat');
        }
        
        // RDW API endpoints
        $endpoints = [
            'basis' => "https://opendata.rdw.nl/resource/m9d7-ebf2.json?kenteken=" . $formattedKenteken,
            'brandstof' => "https://opendata.rdw.nl/resource/8ys7-d773.json?kenteken=" . $formattedKenteken,
            'carrosserie' => "https://opendata.rdw.nl/resource/vezc-m2t6.json?kenteken=" . $formattedKenteken
        ];
        
        $vehicleData = [];
        
        // Haal basisgegevens op
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'AutoKostenCalculator/1.0'
            ]
        ]);
        
        $basisData = @file_get_contents($endpoints['basis'], false, $context);
        if ($basisData === false) {
            throw new Exception('Kan geen verbinding maken met RDW API');
        }
        
        $basisJson = json_decode($basisData, true);
        if (empty($basisJson) || !is_array($basisJson) || count($basisJson) === 0) {
            throw new Exception('Kenteken niet gevonden in RDW database');
        }
        
        $vehicleData['basis'] = $basisJson[0];
        
        // Haal brandstofgegevens op
        $brandstofData = @file_get_contents($endpoints['brandstof'], false, $context);
        if ($brandstofData !== false) {
            $brandstofJson = json_decode($brandstofData, true);
            if (!empty($brandstofJson) && is_array($brandstofJson)) {
                $vehicleData['brandstof'] = $brandstofJson[0] ?? [];
            }
        }
        
        // Haal carrosserie gegevens op
        $carrosserieData = @file_get_contents($endpoints['carrosserie'], false, $context);
        if ($carrosserieData !== false) {
            $carrosserieJson = json_decode($carrosserieData, true);
            if (!empty($carrosserieJson) && is_array($carrosserieJson)) {
                $vehicleData['carrosserie'] = $carrosserieJson[0] ?? [];
            }
        }
        
        return processVehicleData($vehicleData);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Verwerk en format voertuiggegevens voor frontend
 */
function processVehicleData($data) {
    $result = [
        'kenteken' => $data['basis']['kenteken'] ?? '',
        'merk' => ucfirst(strtolower($data['basis']['merk'] ?? '')),
        'handelsbenaming' => $data['basis']['handelsbenaming'] ?? '',
        'model' => '',
        'datum_eerste_toelating' => $data['basis']['datum_eerste_toelating'] ?? '',
        'datum_eerste_toelating_dt' => $data['basis']['datum_eerste_toelating_dt'] ?? '',
        'bouwjaar' => null,
        'catalogusprijs' => $data['basis']['catalogusprijs'] ?? null,
        'brandstof' => [],
        'massa_ledig_voertuig' => $data['basis']['massa_ledig_voertuig'] ?? null,
        'aantal_cilinders' => $data['basis']['aantal_cilinders'] ?? null,
        'cilinderinhoud' => $data['basis']['cilinderinhoud'] ?? null,
        'co2_uitstoot' => null,
        'verbruik_stad' => null,
        'verbruik_snelweg' => null,
        'verbruik_gecombineerd' => null,
        'inrichting' => $data['basis']['inrichting'] ?? '',
        'aantal_zitplaatsen' => $data['basis']['aantal_zitplaatsen'] ?? null,
        'voertuigsoort' => $data['basis']['voertuigsoort'] ?? '',
        'europese_voertuigcategorie' => $data['basis']['europese_voertuigcategorie'] ?? '',
        'variant' => $data['basis']['variant'] ?? '',
        'uitvoering' => $data['basis']['uitvoering'] ?? '',
        'type_goedkeuringsnummer' => $data['basis']['typegoedkeuringsnummer'] ?? '',
        'wam_verzekerd' => $data['basis']['wam_verzekerd'] ?? '',
        'is_youngtimer' => false,
        'age' => null
    ];
    
    // Bepaal model (combinatie van handelsbenaming en variant)
    if (!empty($result['handelsbenaming'])) {
        $result['model'] = $result['handelsbenaming'];
        if (!empty($result['variant'])) {
            $result['model'] .= ' ' . $result['variant'];
        }
    }
    
    // Bepaal bouwjaar uit datum eerste toelating
    if (!empty($result['datum_eerste_toelating'])) {
        // Format: YYYYMMDD
        $dateStr = strval($result['datum_eerste_toelating']);
        if (strlen($dateStr) >= 4) {
            $result['bouwjaar'] = intval(substr($dateStr, 0, 4));
            $result['age'] = date('Y') - $result['bouwjaar'];
            $result['is_youngtimer'] = ($result['age'] >= 15 && $result['age'] <= 30);
        }
    }
    
    // Verwerk brandstofgegevens
    if (!empty($data['brandstof'])) {
        $brandstofTypes = [];
        
        // Hoofdbrandstof
        if (!empty($data['brandstof']['brandstof_omschrijving'])) {
            $brandstofTypes[] = processFuelType($data['brandstof']['brandstof_omschrijving']);
        }
        
        // Secundaire brandstof (hybride)
        if (!empty($data['brandstof']['tweede_brandstof_omschrijving'])) {
            $brandstofTypes[] = processFuelType($data['brandstof']['tweede_brandstof_omschrijving']);
        }
        
        $result['brandstof'] = $brandstofTypes;
        
        // CO2 en verbruik gegevens
        $result['co2_uitstoot'] = $data['brandstof']['co2_uitstoot_gecombineerd'] ?? null;
        $result['verbruik_stad'] = $data['brandstof']['brandstofverbruik_stad'] ?? null;
        $result['verbruik_snelweg'] = $data['brandstof']['brandstofverbruik_buiten'] ?? null;
        $result['verbruik_gecombineerd'] = $data['brandstof']['brandstofverbruik_gecombineerd'] ?? null;
        
        // Elektrisch verbruik voor EV/PHEV
        if (!empty($data['brandstof']['elektrisch_verbruik_wltp'])) {
            $result['elektrisch_verbruik'] = $data['brandstof']['elektrisch_verbruik_wltp'];
        }
    }
    
    // Bepaal hoofdbrandstof voor calculatie
    $result['brandstof_type'] = determineFuelType($result['brandstof']);
    
    return $result;
}

/**
 * Verwerk brandstof omschrijving naar gestandaardiseerd type
 */
function processFuelType($omschrijving) {
    $omschrijving = strtolower($omschrijving);
    
    $mapping = [
        'benzine' => 'Benzine',
        'diesel' => 'Diesel',
        'elektriciteit' => 'Elektrisch',
        'waterstof' => 'Waterstof',
        'lpg' => 'LPG',
        'cng' => 'CNG',
        'lng' => 'LNG',
        'alcohol' => 'Ethanol'
    ];
    
    foreach ($mapping as $key => $value) {
        if (strpos($omschrijving, $key) !== false) {
            return $value;
        }
    }
    
    return ucfirst($omschrijving);
}

/**
 * Bepaal hoofdbrandstof type voor bijtelling calculatie
 */
function determineFuelType($brandstofArray) {
    if (empty($brandstofArray)) {
        return 'Onbekend';
    }
    
    // Check voor volledig elektrisch
    if (in_array('Elektrisch', $brandstofArray) && count($brandstofArray) === 1) {
        return 'Elektrisch';
    }
    
    // Check voor plug-in hybride
    if (in_array('Elektrisch', $brandstofArray) && count($brandstofArray) > 1) {
        return 'PHEV';
    }
    
    // Check voor waterstof
    if (in_array('Waterstof', $brandstofArray)) {
        return 'Waterstof';
    }
    
    // Return eerste brandstof type
    return $brandstofArray[0];
}

// Main execution
try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }
    
    // Get kenteken from request
    $kenteken = $_GET['kenteken'] ?? $_POST['kenteken'] ?? '';
    
    if (empty($kenteken)) {
        throw new Exception('Kenteken parameter is verplicht', 400);
    }
    
    // Get vehicle data
    $vehicleData = getVehicleData($kenteken);
    
    // Success response
    echo json_encode([
        'success' => true,
        'data' => $vehicleData,
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Error response
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>