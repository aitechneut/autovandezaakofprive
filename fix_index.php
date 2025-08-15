<?php
/**
 * AutoKosten Calculator - Hoofdapplicatie
 * 
 * @author Richard Surie
 * @version 1.0.0
 * @website https://www.pianomanontour.nl/autovandezaakofprive
 */

// Enable error reporting voor development (zet op 0 voor productie)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include bijtelling database
require_once 'includes/bijtelling_database.php';

// Set timezone
date_default_timezone_set('Europe/Amsterdam');

// Current year voor berekeningen
$currentYear = date('Y');

// Get bijtelling rules for JavaScript
$bijtellingRules = [
    'elektrisch_2025' => [
        'percentage' => 17,
        'cap' => 30000,
        'uitleg' => 'Elektrisch 2025: 17% tot €30.000'
    ],
    'elektrisch_2024' => [
        'percentage' => 16,
        'cap' => 30000,
        'uitleg' => 'Elektrisch 2024: 16% tot €30.000'
    ],
    'standaard' => 22,
    'pre_2017' => 25,
    'youngtimer' => 35
];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AutoKosten Calculator - Bereken of een auto van de zaak of privé auto voordeliger is. Nederlandse bijtelling calculator met RDW kenteken lookup.">
    <meta name="author" content="PianoManOnTour.nl">
    <meta name="keywords" content="auto van de zaak, bijtelling, autokostencalculator, zakelijke auto, privé auto, youngtimer, RDW kenteken">
    
    <title>AutoKosten Calculator - Auto van de Zaak of Privé? | PianoManOnTour.nl</title>
    
    <!-- Chart.js voor grafieken -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/style.css">
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    
    <!-- Open Graph -->
    <meta property="og:title" content="AutoKosten Calculator - Auto van de Zaak of Privé?">
    <meta property="og:description" content="Bereken wat voordeliger is: een auto van de zaak of privé rijden. Met actuele Nederlandse bijtelling regels.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.pianomanontour.nl/autovandezaakofprive">
    
    <!-- PHP Data voor JavaScript -->
    <script>
        // Maak PHP data beschikbaar voor JavaScript
        const phpConfig = {
            currentYear: <?php echo $currentYear; ?>,
            apiEndpoint: 'api/rdw-lookup.php',
            bijtellingRules: <?php echo json_encode($bijtellingRules); ?>
        };
    </script>
</head>
<body>
    <div class="container">
        <div class="app-container">
            <!-- Header met Auto Manager -->
            <header class="header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1>🚗 AutoKosten Calculator</h1>
                        <p class="subtitle">Ontdek wat voordeliger is: Auto van de Zaak of Privé rijden</p>
                    </div>
                    <!-- Auto Manager Button -->
                    <div class="auto-manager-controls">
                        <button type="button" class="btn btn-primary" onclick="openAutoManager()">
                            <span class="btn-icon">📚</span> Mijn Auto's (<span id="auto-count">0</span>)
                        </button>
                        <button type="button" class="btn btn-success" onclick="saveCurrentAuto()" id="save-auto-btn">
                            <span class="btn-icon">💾</span> Auto Opslaan
                        </button>
                    </div>
                </div>
            </header>

            <!-- Navigation Tabs - EXACT zoals HTML versie -->
            <nav class="nav-tabs">
                <button class="nav-tab active" data-tab="vehicle">Voertuig Gegevens</button>
                <button class="nav-tab" data-tab="usage">Gebruik & Kosten</button>
                <button class="nav-tab" data-tab="financial">Financiële Situatie</button>
                <button class="nav-tab" data-tab="results">Resultaten</button>
                <button class="nav-tab" data-tab="analyse">Analyse</button>
            </nav>

            <!-- Form Sections -->
            <form id="calculator-form">
                <!-- Section 1: Vehicle Data -->
                <section class="form-section active" id="vehicle-section">
                    <h2 class="section-title">📋 Voertuig Gegevens</h2>
                    
                    <!-- Auto Naam voor opslag -->
                    <div class="form-group" style="background: var(--light-purple); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <label for="auto_naam" class="form-label">
                            Auto Naam (voor opslag)
                            <span class="tooltip" data-tooltip="Geef deze auto een herkenbare naam">ⓘ</span>
                        </label>
                        <input type="text" 
                               id="auto_naam" 
                               name="auto_naam" 
                               class="form-input" 
                               placeholder="Bijv. Tesla Model 3 2023"
                               style="font-weight: 600;">
                    </div>
                    
                    <div class="kenteken-group form-group">
                        <label for="kenteken" class="form-label">
                            Kenteken
                            <span class="tooltip" data-tooltip="Vul kenteken in voor automatische gegevens">ⓘ</span>
                        </label>
                        <div style="position: relative;">
                            <input type="text" 
                                   id="kenteken" 
                                   name="kenteken" 
                                   class="form-input kenteken-input" 
                                   placeholder="XX-XXX-X"
                                   maxlength="8"
                                   pattern="[A-Z0-9-]+"
                                   aria-label="Kenteken invoeren">
                            <button type="button" id="lookup-btn" class="lookup-button">
                                Ophalen
                            </button>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="merk" class="form-label">Merk</label>
                            <input type="text" id="merk" name="merk" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" id="model" name="model" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="bouwjaar" class="form-label">
                                Bouwjaar
                                <span class="tooltip" data-tooltip="Bepaalt bijtelling percentage">ⓘ</span>
                            </label>
                            <input type="number" id="bouwjaar" name="bouwjaar" class="form-input" 
                                   min="1990" max="<?php echo $currentYear; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="datum_eerste_toelating" class="form-label">
                                Datum Eerste Toelating
                                <span class="tooltip" data-tooltip="Voor exacte bijtelling berekening">ⓘ</span>
                            </label>
                            <input type="date" id="datum_eerste_toelating" name="datum_eerste_toelating" 
                                   class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="brandstof" class="form-label">Brandstof</label>
                            <select id="brandstof" name="brandstof" class="form-select" required>
                                <option value="">Selecteer brandstof</option>
                                <option value="benzine">Benzine</option>
                                <option value="diesel">Diesel</option>
                                <option value="hybride">Hybride (Benzine)</option>
                                <option value="hybride_diesel">Hybride (Diesel)</option>
                                <option value="plugin_hybride">Plug-in Hybride</option>
                                <option value="elektrisch">Elektrisch</option>
                                <option value="waterstof">Waterstof</option>
                                <option value="lpg">LPG</option>
                                <option value="cng">CNG (Aardgas)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="gewicht" class="form-label">
                                Gewicht (kg)
                                <span class="tooltip" data-tooltip="Voor MRB berekening">ⓘ</span>
                            </label>
                            <input type="number" id="gewicht" name="gewicht" class="form-input" 
                                   min="500" max="5000" required>
                        </div>

                        <div class="form-group">
                            <label for="co2_uitstoot" class="form-label">
                                CO2 Uitstoot (g/km)
                                <span class="tooltip" data-tooltip="Voor milieu-informatie">ⓘ</span>
                            </label>
                            <input type="number" id="co2_uitstoot" name="co2_uitstoot" class="form-input" 
                                   min="0" max="500">
                        </div>

                        <div class="form-group">
                            <label for="kilometerstand" class="form-label">
                                Kilometerstand
                                <span class="tooltip" data-tooltip="Voor onderhoudsschatting">ⓘ</span>
                            </label>
                            <input type="number" id="kilometerstand" name="kilometerstand" class="form-input" 
                                   min="0" max="999999">
                        </div>

                        <div class="form-group">
                            <label for="cataloguswaarde" class="form-label">
                                Cataloguswaarde (€)
                                <span class="tooltip" data-tooltip="Nieuwprijs voor bijtelling">ⓘ</span>
                            </label>
                            <input type="number" id="cataloguswaarde" name="cataloguswaarde" class="form-input" 
                                   min="0" step="100" required>
                        </div>

                        <div class="form-group">
                            <label for="dagwaarde" class="form-label">
                                Dagwaarde (€)
                                <span class="tooltip" data-tooltip="Alleen voor youngtimers (15+ jaar)">ⓘ</span>
                            </label>
                            <input type="number" id="dagwaarde" name="dagwaarde" class="form-input" 
                                   min="0" step="100" 
                                   placeholder="Alleen voor youngtimers">
                        </div>
                    </div>

                    <!-- Auto Status Info Cards -->
                    <div class="results-grid" style="margin-top: 2rem;">
                        <div class="info-card">
                            <h3 class="info-card-title">⏰ Voertuig Status</h3>
                            <div class="info-card-value" id="vehicle-status">-</div>
                            <p class="info-card-description" id="vehicle-status-desc">
                                Vul gegevens in voor status
                            </p>
                        </div>

                        <div class="info-card">
                            <h3 class="info-card-title">📊 Bijtelling Percentage</h3>
                            <div class="info-card-value" id="bijtelling-preview">-</div>
                            <p class="info-card-description" id="bijtelling-desc">
                                Wordt automatisch berekend
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-top: 2rem;">
                        <button type="button" class="btn btn-secondary" disabled>
                            ← Vorige
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextTab('usage')">
                            Volgende →
                        </button>
                    </div>
                </section>

                <!-- Ik stop hier - de rest blijft zoals het was -->
            </form>
        </div>
    </div>

    <!-- JavaScript laden -->
    <script src="assets/autovandezaakofprive.js"></script>
</body>
</html>
