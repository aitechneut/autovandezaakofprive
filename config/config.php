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

// Error Messages
define('ERROR_KENTEKEN_INVALID', 'Ongeldig kenteken formaat');
define('ERROR_RDW_API_DOWN', 'RDW API is momenteel niet beschikbaar');
define('ERROR_NO_DATA', 'Geen gegevens gevonden voor dit kenteken');
define('ERROR_CALCULATION_FAILED', 'Berekening mislukt, controleer de invoer');

// Debug Settings
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Europe/Amsterdam');
?>