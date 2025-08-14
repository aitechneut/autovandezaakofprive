<?php
/**
 * Configuration file for AutoKosten Calculator
 * 
 * @author Richard Surie
 * @version 1.0.0
 */

// Environment (development, staging, production)
define('ENVIRONMENT', 'production');

// Application Settings
define('APP_NAME', 'AutoKosten Calculator');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://www.pianomanontour.nl/autovandezaakofprive');

// API Settings
define('RDW_API_BASE', 'https://opendata.rdw.nl');
define('RDW_API_TIMEOUT', 10); // seconds
define('RDW_CACHE_ENABLED', false); // Set to true when cache is implemented
define('RDW_CACHE_TTL', 3600); // 1 hour cache lifetime

// Default Values
define('DEFAULT_BRANDSTOF_PRIJS_BENZINE', 2.10);
define('DEFAULT_BRANDSTOF_PRIJS_DIESEL', 1.85);
define('DEFAULT_BRANDSTOF_PRIJS_LPG', 1.05);
define('DEFAULT_STROOM_PRIJS', 0.35); // per kWh

// Calculation Settings
define('DEFAULT_AFSCHRIJVING_JAREN', 5);
define('DEFAULT_INFLATIE_PERCENTAGE', 2.5);
define('DEFAULT_KM_PER_JAAR', 15000);

// Tax Settings (2025)
define('BELASTING_SCHIJF_1_MAX', 38441);
define('BELASTING_SCHIJF_1_PERCENTAGE', 36.97);
define('BELASTING_SCHIJF_2_MAX', 76817);
define('BELASTING_SCHIJF_2_PERCENTAGE', 37.48);
define('BELASTING_SCHIJF_3_PERCENTAGE', 49.50);

// Bijtelling Settings (2025)
define('BIJTELLING_ELEKTRISCH_PERCENTAGE', 17);
define('BIJTELLING_ELEKTRISCH_CAP', 30000);
define('BIJTELLING_STANDAARD_PERCENTAGE', 22);
define('BIJTELLING_PRE_2017_PERCENTAGE', 25);
define('BIJTELLING_YOUNGTIMER_PERCENTAGE', 35);
define('BIJTELLING_YOUNGTIMER_MIN_AGE', 15);
define('BIJTELLING_YOUNGTIMER_MAX_AGE', 30);

// MRB Settings (2025)
define('MRB_ELEKTRISCH_KORTING', 0.75); // 25% korting in 2025
define('MRB_PROVINCIE_OPCENTEN', [
    'noord-holland' => 67.0,
    'zuid-holland' => 95.5,
    'utrecht' => 77.5,
    'noord-brabant' => 79.3,
    'gelderland' => 88.9,
    'limburg' => 77.9,
    'overijssel' => 79.9,
    'flevoland' => 65.3,
    'groningen' => 92.0,
    'friesland' => 87.0,
    'drenthe' => 92.0,
    'zeeland' => 80.8
]);

// Error Messages
define('ERROR_KENTEKEN_INVALID', 'Ongeldig kenteken formaat');
define('ERROR_RDW_API_DOWN', 'RDW API is momenteel niet beschikbaar');
define('ERROR_NO_DATA', 'Geen gegevens gevonden voor dit kenteken');
define('ERROR_CALCULATION_FAILED', 'Berekening mislukt, controleer de invoer');

// Debug Settings
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Session Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Only on HTTPS

// Timezone
date_default_timezone_set('Europe/Amsterdam');

// Helper function to get config value
function getConfig($key, $default = null) {
    if (defined($key)) {
        return constant($key);
    }
    return $default;
}

// Autoload function for future class loading
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
