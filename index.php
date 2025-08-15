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
                            <label for="cataloguswaarde" class="form-label">
                                Cataloguswaarde (€)
                                <span class="tooltip" data-tooltip="Nieuwprijs incl. BTW en BPM">ⓘ</span>
                            </label>
                            <input type="number" id="cataloguswaarde" name="cataloguswaarde" 
                                   class="form-input" min="0" step="100" required>
                        </div>

                        <div class="form-group">
                            <label for="dagwaarde" class="form-label">
                                Huidige Dagwaarde (€)
                                <span class="tooltip" data-tooltip="Voor youngtimers (15+ jaar)">ⓘ</span>
                            </label>
                            <input type="number" id="dagwaarde" name="dagwaarde" 
                                   class="form-input" min="0" step="100">
                        </div>

                        <div class="form-group">
                            <label for="co2_uitstoot" class="form-label">
                                CO2 Uitstoot (g/km)
                                <span class="tooltip" data-tooltip="Voor historische bijtelling">ⓘ</span>
                            </label>
                            <input type="number" id="co2_uitstoot" name="co2_uitstoot" 
                                   class="form-input" min="0" max="500">
                        </div>

                        <div class="form-group">
                            <label for="kilometerstand" class="form-label">Kilometerstand</label>
                            <input type="number" id="kilometerstand" name="kilometerstand" 
                                   class="form-input" min="0">
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
                </section>

                <!-- Section 2: Usage & Costs -->
                <section class="form-section" id="usage-section">
                    <h2 class="section-title">🚙 Gebruik & Kosten</h2>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="km_per_maand" class="form-label">
                                Kilometers per maand
                                <span class="tooltip" data-tooltip="Schat uw maandelijkse kilometers">ⓘ</span>
                            </label>
                            <input type="number" id="km_per_maand" name="km_per_maand" 
                                   class="form-input" min="0" max="10000" value="1500" required>
                            <small class="form-hint">Per jaar: <span id="km_per_jaar">18.000</span> km</small>
                        </div>

                        <div class="form-group">
                            <label for="verbruik" class="form-label">
                                Verbruik
                                <span class="tooltip" data-tooltip="l/100km of kWh/100km">ⓘ</span>
                            </label>
                            <input type="number" id="verbruik" name="verbruik" 
                                   class="form-input" min="0" max="50" step="0.1" required>
                        </div>

                        <div class="form-group">
                            <label for="brandstofprijs" class="form-label">
                                Brandstofprijs (€)
                                <span class="tooltip" data-tooltip="Per liter of per kWh">ⓘ</span>
                            </label>
                            <input type="number" id="brandstofprijs" name="brandstofprijs" 
                                   class="form-input" min="0" max="5" step="0.01" value="2.10" required>
                        </div>

                        <div class="form-group">
                            <label for="mrb" class="form-label">
                                MRB per maand (€)
                                <span class="tooltip" data-tooltip="Motor Rijtuigen Belasting">ⓘ</span>
                            </label>
                            <input type="number" id="mrb" name="mrb" 
                                   class="form-input" min="0" max="500" step="1" required>
                        </div>

                        <div class="form-group">
                            <label for="verzekering" class="form-label">
                                Verzekering per maand (€)
                                <span class="tooltip" data-tooltip="All-risk, WA+ of WA">ⓘ</span>
                            </label>
                            <input type="number" id="verzekering" name="verzekering" 
                                   class="form-input" min="0" max="500" step="1" value="120" required>
                        </div>

                        <div class="form-group">
                            <label for="onderhoud" class="form-label">
                                Onderhoud per maand (€)
                                <span class="tooltip" data-tooltip="Service, banden, reparaties">ⓘ</span>
                            </label>
                            <input type="number" id="onderhoud" name="onderhoud" 
                                   class="form-input" min="0" max="500" step="1" value="80" required>
                        </div>

                        <div class="form-group">
                            <label for="aankoopprijs" class="form-label">
                                Aankoopprijs (€)
                                <span class="tooltip" data-tooltip="Voor privé afschrijving">ⓘ</span>
                            </label>
                            <input type="number" id="aankoopprijs" name="aankoopprijs" 
                                   class="form-input" min="0" step="100">
                        </div>

                        <div class="form-group">
                            <label for="afschrijving_jaren" class="form-label">
                                Afschrijving periode (jaren)
                                <span class="tooltip" data-tooltip="Meestal 5 jaar">ⓘ</span>
                            </label>
                            <input type="number" id="afschrijving_jaren" name="afschrijving_jaren" 
                                   class="form-input" min="1" max="10" value="5" required>
                        </div>
                    </div>

                    <!-- Kosten Preview -->
                    <div class="cost-preview">
                        <h3>💰 Geschatte maandkosten</h3>
                        <div class="preview-grid">
                            <div class="preview-item">
                                <span class="label">Brandstof:</span>
                                <span class="value" id="preview-brandstof">€ 0</span>
                            </div>
                            <div class="preview-item">
                                <span class="label">Vaste lasten:</span>
                                <span class="value" id="preview-vaste-lasten">€ 0</span>
                            </div>
                            <div class="preview-item">
                                <span class="label">Afschrijving:</span>
                                <span class="value" id="preview-afschrijving">€ 0</span>
                            </div>
                            <div class="preview-item">
                                <span class="label">Totaal privé:</span>
                                <span class="value highlight" id="preview-totaal">€ 0</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 3: Financial Situation -->
                <section class="form-section" id="financial-section">
                    <h2 class="section-title">💼 Financiële Situatie</h2>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="bruto_salaris" class="form-label">
                                Bruto jaarinkomen (€)
                                <span class="tooltip" data-tooltip="Voor belastingschijf bepaling">ⓘ</span>
                            </label>
                            <input type="number" id="bruto_salaris" name="bruto_salaris" 
                                   class="form-input" min="0" step="1000" required>
                        </div>

                        <div class="form-group">
                            <label for="belasting_percentage" class="form-label">
                                Belastingpercentage (%)
                                <span class="tooltip" data-tooltip="Wordt automatisch berekend">ⓘ</span>
                            </label>
                            <input type="number" id="belasting_percentage" name="belasting_percentage" 
                                   class="form-input" min="0" max="52" step="0.1" readonly>
                        </div>

                        <div class="form-group">
                            <label for="werkgever_bijdrage" class="form-label">
                                Werkgever bijdrage auto (€/maand)
                                <span class="tooltip" data-tooltip="Eventuele eigen bijdrage">ⓘ</span>
                            </label>
                            <input type="number" id="werkgever_bijdrage" name="werkgever_bijdrage" 
                                   class="form-input" min="0" step="10" value="0">
                        </div>

                        <div class="form-group">
                            <label for="lease_kosten" class="form-label">
                                Lease kosten werkgever (€/maand)
                                <span class="tooltip" data-tooltip="Voor info, niet in berekening">ⓘ</span>
                            </label>
                            <input type="number" id="lease_kosten" name="lease_kosten" 
                                   class="form-input" min="0" step="10" value="0">
                        </div>
                    </div>

                    <!-- Belasting Info Cards -->
                    <div class="tax-info-cards">
                        <div class="info-card">
                            <h3 class="info-card-title">📊 Belastingschijf 2025</h3>
                            <div class="info-card-value" id="tax-bracket">-</div>
                            <p class="info-card-description" id="tax-bracket-desc">
                                Vul inkomen in voor berekening
                            </p>
                        </div>

                        <div class="info-card">
                            <h3 class="info-card-title">💰 Bijtelling Impact</h3>
                            <div class="info-card-value" id="bijtelling-impact">€ 0</div>
                            <p class="info-card-description">
                                Extra belasting per maand
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Section 4: Results -->
                <section class="form-section" id="results-section">
                    <h2 class="section-title">📊 Resultaten</h2>
                    
                    <div id="results-container">
                        <!-- Resultaten worden hier dynamisch geladen -->
                        <div class="empty-state">
                            <div class="empty-icon">📊</div>
                            <h3>Geen resultaten beschikbaar</h3>
                            <p>Vul eerst alle gegevens in en klik op berekenen.</p>
                            <button type="button" class="btn btn-primary" onclick="calculateResults()">
                                <span class="btn-icon">🧮</span> Bereken nu
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Section 5: Analysis -->
                <section class="form-section" id="analyse-section">
                    <h2 class="section-title">📈 Analyse & Grafieken</h2>
                    
                    <div id="analyse-container">
                        <!-- Grafieken worden hier geladen -->
                        <div class="charts-grid">
                            <div class="chart-container">
                                <canvas id="comparison-chart"></canvas>
                            </div>
                            <div class="chart-container">
                                <canvas id="breakdown-chart"></canvas>
                            </div>
                            <div class="chart-container">
                                <canvas id="timeline-chart"></canvas>
                            </div>
                            <div class="chart-container">
                                <canvas id="savings-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </section>
            </form>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    <span class="btn-icon">🔄</span> Reset
                </button>
                <button type="button" class="btn btn-primary" onclick="exportResults()">
                    <span class="btn-icon">📥</span> Exporteer
                </button>
                <button type="button" class="btn btn-success btn-large" onclick="calculateResults()">
                    <span class="btn-icon">🧮</span> Bereken Resultaten
                </button>
            </div>
        </div>
    </div>

    <!-- Auto Manager Modal -->
    <div id="auto-manager-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📚 Opgeslagen Auto's</h2>
                <button class="modal-close" onclick="closeAutoManager()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="saved-autos-list">
                    <!-- Dynamisch geladen -->
                </div>
            </div>
        </div>
    </div>

    <!-- Notification System -->
    <div id="notification-container"></div>

    <!-- JavaScript -->
    <script src="assets/autovandezaakofprive.js"></script>
</body>
</html>