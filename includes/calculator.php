<?php
/**
 * Calculator Backend - Rekenlogica voor AutoKosten Calculator
 * 
 * Handelt alle berekeningen af voor zakelijke vs privé auto vergelijking
 * 
 * @author Richard Surie
 * @version 1.0.0
 */

require_once 'bijtelling_database.php';

/**
 * Hoofdfunctie voor complete kostenberekening
 */
function calculateTotalCosts($data) {
    $result = [
        'zakelijk' => calculateZakelijkeKosten($data),
        'prive' => calculatePriveKosten($data),
        'verschil' => 0,
        'advies' => '',
        'details' => []
    ];
    
    // Bereken verschil
    $result['verschil'] = $result['prive']['totaal_maand'] - $result['zakelijk']['totaal_maand'];
    
    // Genereer advies
    if ($result['verschil'] > 0) {
        $result['advies'] = "Auto van de zaak is €" . number_format(abs($result['verschil']), 2, ',', '.') . " per maand voordeliger";
    } else {
        $result['advies'] = "Privé auto is €" . number_format(abs($result['verschil']), 2, ',', '.') . " per maand voordeliger";
    }
    
    return $result;
}

/**
 * Bereken zakelijke kosten (auto van de zaak)
 */
function calculateZakelijkeKosten($data) {
    // Haal bijtelling info op
    $bijtelling = getBijtelling(
        $data['bouwjaar'],
        $data['brandstof'],
        $data['cataloguswaarde'],
        $data['dagwaarde'] ?? null,
        $data['co2_uitstoot'] ?? null,
        $data['datum_eerste_toelating'] ?? null
    );
    
    // Bereken netto bijtelling kosten
    $bruto_bijtelling_jaar = $bijtelling['bijtelling_bedrag'];
    $belasting_percentage = $data['belasting_percentage'] / 100;
    $netto_bijtelling_jaar = $bruto_bijtelling_jaar * $belasting_percentage;
    $netto_bijtelling_maand = $netto_bijtelling_jaar / 12;
    
    // Zakelijke kosten (werkgever betaalt meeste kosten)
    $kosten = [
        'bijtelling_bruto_jaar' => $bruto_bijtelling_jaar,
        'bijtelling_netto_jaar' => $netto_bijtelling_jaar,
        'bijtelling_netto_maand' => $netto_bijtelling_maand,
        'eigen_bijdrage' => $data['eigen_bijdrage'] ?? 0,
        'totaal_maand' => $netto_bijtelling_maand + ($data['eigen_bijdrage'] ?? 0),
        'bijtelling_info' => $bijtelling
    ];
    
    return $kosten;
}

/**
 * Bereken privé kosten
 */
function calculatePriveKosten($data) {
    $kosten = [];
    
    // Afschrijving
    $aankoopprijs = $data['aankoopprijs'] ?? $data['cataloguswaarde'];
    $restwaarde = $aankoopprijs * 0.3; // Schat 30% restwaarde
    $afschrijving_jaren = $data['afschrijving_jaren'] ?? 5;
    $kosten['afschrijving_maand'] = ($aankoopprijs - $restwaarde) / ($afschrijving_jaren * 12);
    
    // Brandstof
    $km_per_maand = $data['km_per_maand'];
    $verbruik = $data['verbruik']; // l/100km of kWh/100km
    $brandstofprijs = $data['brandstofprijs'];
    $kosten['brandstof_maand'] = ($km_per_maand / 100) * $verbruik * $brandstofprijs;
    
    // Vaste lasten
    $kosten['mrb_maand'] = $data['mrb'] ?? calculateMRB($data['gewicht'], $data['brandstof'], $data['bouwjaar']);
    $kosten['verzekering_maand'] = $data['verzekering'];
    $kosten['onderhoud_maand'] = $data['onderhoud'];
    
    // APK voor auto's ouder dan 3 jaar
    $auto_leeftijd = date('Y') - $data['bouwjaar'];
    $kosten['apk_maand'] = ($auto_leeftijd > 3) ? (50 / 12) : 0;
    
    // Totaal
    $kosten['totaal_maand'] = 
        $kosten['afschrijving_maand'] +
        $kosten['brandstof_maand'] +
        $kosten['mrb_maand'] +
        $kosten['verzekering_maand'] +
        $kosten['onderhoud_maand'] +
        $kosten['apk_maand'];
    
    $kosten['totaal_jaar'] = $kosten['totaal_maand'] * 12;
    
    return $kosten;
}

/**
 * Bereken 5-jaars projectie
 */
function calculate5YearProjection($data) {
    $projection = [];
    
    for ($year = 1; $year <= 5; $year++) {
        // Pas inflatie toe (2% per jaar)
        $inflatie_factor = pow(1.02, $year - 1);
        
        // Update data voor dit jaar
        $year_data = $data;
        $year_data['brandstofprijs'] *= $inflatie_factor;
        $year_data['verzekering'] *= $inflatie_factor;
        $year_data['onderhoud'] *= $inflatie_factor * 1.05; // Onderhoud stijgt sneller
        
        // Bereken kosten voor dit jaar
        $costs = calculateTotalCosts($year_data);
        
        $projection[] = [
            'jaar' => $year,
            'zakelijk_totaal' => $costs['zakelijk']['totaal_maand'] * 12,
            'prive_totaal' => $costs['prive']['totaal_maand'] * 12,
            'verschil' => $costs['verschil'] * 12
        ];
    }
    
    return $projection;
}

/**
 * API endpoint voor AJAX calls
 */
if (isset($_POST['action']) && $_POST['action'] === 'calculate') {
    header('Content-Type: application/json');
    
    try {
        $data = json_decode($_POST['data'], true);
        
        // Valideer input
        if (!$data || !isset($data['bouwjaar']) || !isset($data['brandstof'])) {
            throw new Exception('Ongeldige input data');
        }
        
        // Bereken alles
        $result = [
            'kosten' => calculateTotalCosts($data),
            'projectie' => calculate5YearProjection($data),
            'timestamp' => date('c')
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
?>