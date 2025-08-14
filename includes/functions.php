<?php
/**
 * Helper Functions - Algemene hulpfuncties
 * 
 * @author Richard Surie
 * @version 1.0.0
 */

/**
 * Format kenteken voor weergave
 */
function formatKenteken($kenteken) {
    $kenteken = strtoupper(preg_replace('/[^A-Z0-9]/', '', $kenteken));
    
    // Nederlandse kenteken formatting
    if (strlen($kenteken) == 6) {
        // Patterns: XX-XX-XX, XX-XXX-X, X-XXX-XX, etc.
        if (preg_match('/^[A-Z]{2}[0-9]{2}[A-Z]{2}$/', $kenteken)) {
            return substr($kenteken, 0, 2) . '-' . substr($kenteken, 2, 2) . '-' . substr($kenteken, 4, 2);
        }
        if (preg_match('/^[A-Z]{2}[A-Z]{3}[0-9]$/', $kenteken)) {
            return substr($kenteken, 0, 2) . '-' . substr($kenteken, 2, 3) . '-' . substr($kenteken, 5, 1);
        }
        if (preg_match('/^[0-9][A-Z]{3}[0-9]{2}$/', $kenteken)) {
            return substr($kenteken, 0, 1) . '-' . substr($kenteken, 1, 3) . '-' . substr($kenteken, 4, 2);
        }
    }
    
    return $kenteken;
}

/**
 * Valideer email adres
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Format bedrag in euro's
 */
function formatEuro($amount, $decimals = 2) {
    return '€ ' . number_format($amount, $decimals, ',', '.');
}

/**
 * Bereken belastingschijf percentage
 */
function getBelastingPercentage($brutoJaarinkomen) {
    // 2024/2025 schijven
    if ($brutoJaarinkomen <= 37149) {
        return 36.97;
    } elseif ($brutoJaarinkomen <= 73031) {
        return 37.07;
    } else {
        return 49.50;
    }
}

/**
 * Schat cataloguswaarde op basis van merk/model/bouwjaar
 */
function estimateCataloguswaarde($merk, $model, $bouwjaar) {
    // Basis schatting - in productie zou dit een database lookup zijn
    $basisWaarde = 25000;
    
    // Premium merken
    $premiumMerken = ['BMW', 'Mercedes-Benz', 'Audi', 'Porsche', 'Tesla', 'Jaguar', 'Land Rover'];
    if (in_array($merk, $premiumMerken)) {
        $basisWaarde *= 2;
    }
    
    // Afschrijving per jaar (ongeveer 15% per jaar)
    $jaren = date('Y') - $bouwjaar;
    $waarde = $basisWaarde * pow(0.85, min($jaren, 10));
    
    return round($waarde, -2); // Rond af op 100
}

/**
 * Schat verzekering premie
 */
function estimateVerzekering($cataloguswaarde, $bouwjaar, $brandstof) {
    $basePremie = 50; // Basis €50 per maand
    
    // Waarde factor
    $waardeFactor = $cataloguswaarde / 25000;
    $basePremie *= $waardeFactor;
    
    // Leeftijd factor
    $leeftijd = date('Y') - $bouwjaar;
    if ($leeftijd > 10) {
        $basePremie *= 0.8; // 20% korting voor oudere auto's
    }
    
    // Brandstof factor
    if (strpos(strtolower($brandstof), 'elektr') !== false) {
        $basePremie *= 1.1; // 10% duurder voor elektrisch
    }
    
    return round($basePremie);
}

/**
 * Schat onderhoud kosten
 */
function estimateOnderhoud($bouwjaar, $km_per_jaar, $brandstof) {
    $basisOnderhoud = 50; // €50 per maand basis
    
    // Leeftijd factor
    $leeftijd = date('Y') - $bouwjaar;
    if ($leeftijd > 5) {
        $basisOnderhoud *= 1.5;
    }
    if ($leeftijd > 10) {
        $basisOnderhoud *= 2;
    }
    
    // Kilometer factor
    if ($km_per_jaar > 20000) {
        $basisOnderhoud *= 1.3;
    }
    
    // Elektrisch = minder onderhoud
    if (strpos(strtolower($brandstof), 'elektr') !== false) {
        $basisOnderhoud *= 0.6;
    }
    
    return round($basisOnderhoud);
}

/**
 * Valideer Nederlandse postcode
 */
function validatePostcode($postcode) {
    $postcode = preg_replace('/\s+/', '', strtoupper($postcode));
    return preg_match('/^[1-9][0-9]{3}[A-Z]{2}$/', $postcode);
}

/**
 * Log voor debugging (development only)
 */
function debugLog($message, $data = null) {
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $log = date('Y-m-d H:i:s') . ' - ' . $message;
        if ($data !== null) {
            $log .= ' - ' . json_encode($data);
        }
        error_log($log . PHP_EOL, 3, '../logs/debug.log');
    }
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Generate unique ID
 */
function generateUniqueId($prefix = 'auto') {
    return $prefix . '_' . uniqid() . '_' . time();
}

/**
 * Check of auto elektrisch is
 */
function isElektrisch($brandstof) {
    $brandstofLower = strtolower($brandstof);
    return (strpos($brandstofLower, 'elektr') !== false && strpos($brandstofLower, 'hybr') === false);
}

/**
 * Check of auto een hybride is
 */
function isHybride($brandstof) {
    $brandstofLower = strtolower($brandstof);
    return (strpos($brandstofLower, 'hybr') !== false || strpos($brandstofLower, 'phev') !== false);
}

/**
 * Bereken totale kosten over X jaar
 */
function calculateTotalCostOverYears($maandKosten, $jaren, $inflatie = 0.02) {
    $totaal = 0;
    for ($jaar = 1; $jaar <= $jaren; $jaar++) {
        $jaarKosten = $maandKosten * 12 * pow(1 + $inflatie, $jaar - 1);
        $totaal += $jaarKosten;
    }
    return $totaal;
}

/**
 * Format datum voor weergave
 */
function formatDatum($datum, $format = 'd-m-Y') {
    if (is_numeric($datum) && strlen($datum) == 8) {
        // RDW format YYYYMMDD
        $jaar = substr($datum, 0, 4);
        $maand = substr($datum, 4, 2);
        $dag = substr($datum, 6, 2);
        $datum = "$jaar-$maand-$dag";
    }
    
    $dateTime = new DateTime($datum);
    return $dateTime->format($format);
}

/**
 * Verkrijg provincie uit postcode
 */
function getProvincieFromPostcode($postcode) {
    $postcode = intval(substr($postcode, 0, 4));
    
    // Simplified provincie mapping
    if ($postcode >= 1000 && $postcode <= 1299) return 'Noord-Holland';
    if ($postcode >= 1300 && $postcode <= 1379) return 'Flevoland';
    if ($postcode >= 1380 && $postcode <= 2199) return 'Noord-Holland';
    if ($postcode >= 2200 && $postcode <= 3299) return 'Zuid-Holland';
    if ($postcode >= 3300 && $postcode <= 3499) return 'Zuid-Holland';
    if ($postcode >= 3500 && $postcode <= 4199) return 'Utrecht';
    if ($postcode >= 4200 && $postcode <= 4699) return 'Zeeland';
    if ($postcode >= 4700 && $postcode <= 5799) return 'Noord-Brabant';
    if ($postcode >= 5800 && $postcode <= 6499) return 'Limburg';
    if ($postcode >= 6500 && $postcode <= 6999) return 'Gelderland';
    if ($postcode >= 7000 && $postcode <= 7399) return 'Gelderland';
    if ($postcode >= 7400 && $postcode <= 7799) return 'Overijssel';
    if ($postcode >= 7800 && $postcode <= 7999) return 'Drenthe';
    if ($postcode >= 8000 && $postcode <= 8299) return 'Overijssel';
    if ($postcode >= 8300 && $postcode <= 8399) return 'Flevoland';
    if ($postcode >= 8400 && $postcode <= 8999) return 'Friesland';
    if ($postcode >= 9000 && $postcode <= 9999) return 'Groningen';
    
    return 'Onbekend';
}
?>