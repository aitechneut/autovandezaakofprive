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
            bijtellingRules: <?php echo json_encode([
                'elektrisch_2025' => getElektrischPercentage(2025, 30000),
                'elektrisch_2024' => getElektrischPercentage(2024, 30000),
                'standaard' => 22,
                'pre_2017' => 25,
                'youngtimer' => 35
            ]); ?>
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

            <!-- Navigation Tabs - Nu met 5 tabs -->
            <nav class="tabs-navigation">
                <button class="tab-btn active" data-tab="vehicle">
                    <span class="tab-icon">🚙</span>
                    <span class="tab-text">Voertuig</span>
                </button>
                <button class="tab-btn" data-tab="usage">
                    <span class="tab-icon">📍</span>
                    <span class="tab-text">Gebruik</span>
                </button>
                <button class="tab-btn" data-tab="costs">
                    <span class="tab-icon">💰</span>
                    <span class="tab-text">Kosten</span>
                </button>
                <button class="tab-btn" data-tab="results">
                    <span class="tab-icon">📊</span>
                    <span class="tab-text">Resultaten</span>
                </button>
                <button class="tab-btn" data-tab="analyse">
                    <span class="tab-icon">📈</span>
                    <span class="tab-text">Analyse</span>
                </button>
            </nav>

            <!-- Tab Content -->
            <form id="calculator-form" class="calculator-form">
                
                <!-- Tab 1: Vehicle Information -->
                <div class="tab-content active" id="vehicle-tab">
                    <h2>🚙 Voertuig Informatie</h2>
                    
                    <!-- RDW Kenteken Lookup -->
                    <div class="form-section">
                        <h3>Kenteken Opzoeken</h3>
                        <div class="input-group">
                            <input type="text" 
                                   id="kenteken" 
                                   name="kenteken" 
                                   placeholder="XX-XX-XX" 
                                   maxlength="8"
                                   class="input-field kenteken-input">
                            <button type="button" class="btn btn-primary" onclick="lookupKenteken()">
                                <span class="btn-icon">🔍</span> Ophalen
                            </button>
                        </div>
                        <div id="kenteken-status" class="status-message"></div>
                    </div>

                    <!-- Auto Details -->
                    <div class="form-section">
                        <h3>Auto Details</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="merk">Merk</label>
                                <input type="text" id="merk" name="merk" required>
                            </div>
                            <div class="form-group">
                                <label for="model">Model</label>
                                <input type="text" id="model" name="model" required>
                            </div>
                            <div class="form-group">
                                <label for="bouwjaar">Bouwjaar</label>
                                <input type="number" id="bouwjaar" name="bouwjaar" min="1980" max="<?php echo $currentYear; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="brandstof">Brandstof</label>
                                <select id="brandstof" name="brandstof" required>
                                    <option value="">Selecteer...</option>
                                    <option value="Benzine">Benzine</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Elektrisch">Elektrisch</option>
                                    <option value="PHEV">Plug-in Hybride</option>
                                    <option value="Hybride">Hybride</option>
                                    <option value="Waterstof">Waterstof</option>
                                    <option value="LPG">LPG</option>
                                    <option value="CNG">CNG</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Waarde Informatie -->
                    <div class="form-section">
                        <h3>Waarde Informatie</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cataloguswaarde">
                                    Cataloguswaarde (€)
                                    <span class="tooltip" title="Nieuwprijs inclusief BTW en BPM">ℹ️</span>
                                </label>
                                <input type="number" id="cataloguswaarde" name="cataloguswaarde" min="0" step="100" required>
                            </div>
                            <div class="form-group">
                                <label for="dagwaarde">
                                    Dagwaarde (€)
                                    <span class="tooltip" title="Huidige marktwaarde (voor youngtimers)">ℹ️</span>
                                </label>
                                <input type="number" id="dagwaarde" name="dagwaarde" min="0" step="100">
                            </div>
                            <div class="form-group">
                                <label for="aankoopprijs">Aankoopprijs (€)</label>
                                <input type="number" id="aankoopprijs" name="aankoopprijs" min="0" step="100">
                            </div>
                            <div class="form-group">
                                <label for="gewicht">Gewicht (kg)</label>
                                <input type="number" id="gewicht" name="gewicht" min="500" max="5000" step="10">
                            </div>
                        </div>
                    </div>

                    <!-- Youngtimer Status -->
                    <div id="youngtimer-alert" class="alert alert-info" style="display: none;">
                        <h4>🎉 Youngtimer Gedetecteerd!</h4>
                        <p>Deze auto is <span id="auto-age"></span> jaar oud en komt in aanmerking voor de youngtimer regeling.</p>
                        <p>Bijtelling: <strong>35% over de dagwaarde</strong> in plaats van cataloguswaarde.</p>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-primary" onclick="nextTab('usage')">
                            Volgende: Gebruik <span class="btn-icon">→</span>
                        </button>
                    </div>
                </div>

                <!-- Tab 2: Usage -->
                <div class="tab-content" id="usage-tab">
                    <h2>📍 Gebruik & Kilometers</h2>
                    
                    <div class="form-section">
                        <h3>Kilometrage</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="km_per_maand">
                                    Kilometers per maand
                                    <span class="tooltip" title="Gemiddeld aantal kilometers per maand">ℹ️</span>
                                </label>
                                <input type="number" id="km_per_maand" name="km_per_maand" min="0" max="10000" step="50" required>
                                <small>Per jaar: <span id="km_per_jaar">0</span> km</small>
                            </div>
                            <div class="form-group">
                                <label for="km_prive">
                                    Waarvan privé (%)
                                    <span class="tooltip" title="Percentage voor privé gebruik">ℹ️</span>
                                </label>
                                <input type="range" id="km_prive" name="km_prive" min="0" max="100" value="50">
                                <span id="km_prive_value">50%</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Brandstof Verbruik</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="verbruik">
                                    Verbruik (l/100km of kWh/100km)
                                    <span class="tooltip" title="Gemiddeld verbruik">ℹ️</span>
                                </label>
                                <input type="number" id="verbruik" name="verbruik" min="0" max="50" step="0.1" required>
                            </div>
                            <div class="form-group">
                                <label for="brandstofprijs">
                                    Brandstofprijs (€/l of €/kWh)
                                    <span class="tooltip" title="Huidige prijs per liter of kWh">ℹ️</span>
                                </label>
                                <input type="number" id="brandstofprijs" name="brandstofprijs" min="0" max="5" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-secondary" onclick="previousTab('vehicle')">
                            <span class="btn-icon">←</span> Vorige
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextTab('costs')">
                            Volgende: Kosten <span class="btn-icon">→</span>
                        </button>
                    </div>
                </div>

                <!-- Tab 3: Costs -->
                <div class="tab-content" id="costs-tab">
                    <h2>💰 Kosten & Belasting</h2>
                    
                    <div class="form-section">
                        <h3>Vaste Kosten (per maand)</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="mrb">
                                    MRB (€/maand)
                                    <span class="tooltip" title="Motor Rijtuigen Belasting">ℹ️</span>
                                </label>
                                <input type="number" id="mrb" name="mrb" min="0" max="500" step="1" required>
                            </div>
                            <div class="form-group">
                                <label for="verzekering">Verzekering (€/maand)</label>
                                <input type="number" id="verzekering" name="verzekering" min="0" max="500" step="1" required>
                            </div>
                            <div class="form-group">
                                <label for="onderhoud">Onderhoud (€/maand)</label>
                                <input type="number" id="onderhoud" name="onderhoud" min="0" max="500" step="1" required>
                            </div>
                            <div class="form-group">
                                <label for="afschrijving_jaren">Afschrijving (jaren)</label>
                                <input type="number" id="afschrijving_jaren" name="afschrijving_jaren" min="1" max="10" value="5" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Belasting Informatie</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="bruto_salaris">
                                    Bruto Salaris/Winst (€/jaar)
                                    <span class="tooltip" title="Voor bepaling belastingschijf">ℹ️</span>
                                </label>
                                <input type="number" id="bruto_salaris" name="bruto_salaris" min="0" step="1000" required>
                            </div>
                            <div class="form-group">
                                <label for="belasting_percentage">
                                    Belasting % (automatisch)
                                    <span class="tooltip" title="Wordt automatisch berekend">ℹ️</span>
                                </label>
                                <input type="number" id="belasting_percentage" name="belasting_percentage" min="0" max="52" step="0.1" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Bijtelling Preview -->
                    <div class="bijtelling-preview">
                        <h3>Bijtelling Preview</h3>
                        <div class="preview-grid">
                            <div class="preview-item">
                                <span class="label">Bijtelling %:</span>
                                <span class="value" id="preview-bijtelling-percentage">-</span>
                            </div>
                            <div class="preview-item">
                                <span class="label">Basis:</span>
                                <span class="value" id="preview-bijtelling-basis">-</span>
                            </div>
                            <div class="preview-item">
                                <span class="label">Per jaar:</span>
                                <span class="value" id="preview-bijtelling-jaar">-</span>
                            </div>
                            <div class="preview-item">
                                <span class="label">Netto kosten/maand:</span>
                                <span class="value" id="preview-bijtelling-netto">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-secondary" onclick="previousTab('usage')">
                            <span class="btn-icon">←</span> Vorige
                        </button>
                        <button type="button" class="btn btn-success" onclick="performCalculation()">
                            <span class="btn-icon">🧮</span> Bereken Resultaten
                        </button>
                    </div>
                </div>

                <!-- Tab 4: Results -->
                <div class="tab-content" id="results-tab">
                    <h2>📊 Resultaten</h2>
                    
                    <div id="results-container">
                        <!-- Resultaten worden hier dynamisch geladen via JavaScript -->
                        <div class="empty-state">
                            <p>📊 Vul eerst de gegevens in en klik op "Bereken Resultaten"</p>
                        </div>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-secondary" onclick="previousTab('costs')">
                            <span class="btn-icon">←</span> Terug naar Kosten
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextTab('analyse')">
                            Bekijk Analyse <span class="btn-icon">→</span>
                        </button>
                    </div>
                </div>

                <!-- Tab 5: Analyse -->
                <div class="tab-content" id="analyse-tab">
                    <h2>📈 Gedetailleerde Analyse</h2>
                    
                    <div id="analyse-container">
                        <!-- Grafieken Container -->
                        <div class="charts-grid">
                            <!-- Kostenvergelijking -->
                            <div class="chart-container">
                                <h3>Maandelijkse Kostenvergelijking</h3>
                                <canvas id="comparison-chart"></canvas>
                            </div>
                            
                            <!-- 5-Jaars Verloop -->
                            <div class="chart-container">
                                <h3>5-Jaars Kostenverloop</h3>
                                <canvas id="timeline-chart"></canvas>
                            </div>
                            
                            <!-- Kostenverdeling Zakelijk -->
                            <div class="chart-container">
                                <h3>Kostenverdeling Zakelijk</h3>
                                <canvas id="breakdown-business-chart"></canvas>
                            </div>
                            
                            <!-- Kostenverdeling Privé -->
                            <div class="chart-container">
                                <h3>Kostenverdeling Privé</h3>
                                <canvas id="breakdown-private-chart"></canvas>
                            </div>
                        </div>

                        <!-- Auto Vergelijking -->
                        <div class="comparison-section">
                            <h3>Vergelijk met andere auto's</h3>
                            <div id="auto-comparison-list">
                                <p class="empty-state">Sla eerst auto's op om te vergelijken</p>
                            </div>
                        </div>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-secondary" onclick="previousTab('results')">
                            <span class="btn-icon">←</span> Terug naar Resultaten
                        </button>
                        <button type="button" class="btn btn-info" onclick="exportResults()">
                            <span class="btn-icon">📥</span> Exporteer Analyse
                        </button>
                        <button type="button" class="btn btn-warning" onclick="resetCalculator()">
                            <span class="btn-icon">🔄</span> Nieuwe Berekening
                        </button>
                    </div>
                </div>

            </form>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> PianoManOnTour.nl - AutoKosten Calculator v1.0</p>
            <p>
                <a href="https://www.pianomanontour.nl" target="_blank">Website</a> |
                <a href="https://www.belastingdienst.nl" target="_blank">Belastingdienst</a> |
                <a href="https://opendata.rdw.nl" target="_blank">RDW Open Data</a>
            </p>
        </footer>
    </div>

    <!-- Auto Manager Modal -->
    <div id="auto-manager-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📚 Mijn Opgeslagen Auto's</h2>
                <button class="modal-close" onclick="closeAutoManager()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="saved-autos-list">
                    <!-- Dynamisch geladen -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeAutoManager()">Sluiten</button>
                <button class="btn btn-danger" onclick="clearAllAutos()">
                    <span class="btn-icon">🗑️</span> Alles Wissen
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification-container"></div>

    <!-- JavaScript met PHP integratie -->
    <script src="assets/autovandezaakofprive.js"></script>
    <script>
        // Initialize met PHP data
        document.addEventListener('DOMContentLoaded', function() {
            // Update jaar ranges
            document.getElementById('bouwjaar').max = phpConfig.currentYear;
            
            // Initialize calculator
            initializeCalculator();
        });

        // RDW Lookup functie met PHP endpoint
        async function lookupKenteken() {
            const kenteken = document.getElementById('kenteken').value.trim();
            const statusDiv = document.getElementById('kenteken-status');
            
            if (!kenteken) {
                showNotification('Vul eerst een kenteken in', 'error');
                return;
            }
            
            statusDiv.innerHTML = '<span class="loading">🔄 Gegevens ophalen...</span>';
            
            try {
                const response = await fetch(`${phpConfig.apiEndpoint}?kenteken=${encodeURIComponent(kenteken)}`);
                const data = await response.json();
                
                if (data.success) {
                    // Vul formulier met data
                    fillFormWithRDWData(data.data);
                    statusDiv.innerHTML = '<span class="success">✅ Voertuiggegevens gevonden!</span>';
                    showNotification('Voertuiggegevens succesvol opgehaald', 'success');
                } else {
                    statusDiv.innerHTML = '<span class="error">❌ ' + data.error + '</span>';
                    showNotification(data.error, 'error');
                }
            } catch (error) {
                statusDiv.innerHTML = '<span class="error">❌ Fout bij ophalen gegevens</span>';
                showNotification('Kon geen verbinding maken met RDW', 'error');
                console.error('RDW Lookup error:', error);
            }
        }

        // Vul formulier met RDW data
        function fillFormWithRDWData(data) {
            // Basis gegevens
            if (data.merk) document.getElementById('merk').value = data.merk;
            if (data.model) document.getElementById('model').value = data.model;
            if (data.bouwjaar) document.getElementById('bouwjaar').value = data.bouwjaar;
            if (data.brandstof_type) document.getElementById('brandstof').value = data.brandstof_type;
            if (data.catalogusprijs) document.getElementById('cataloguswaarde').value = data.catalogusprijs;
            if (data.massa_ledig_voertuig) document.getElementById('gewicht').value = data.massa_ledig_voertuig;
            
            // Check youngtimer status
            if (data.is_youngtimer) {
                document.getElementById('youngtimer-alert').style.display = 'block';
                document.getElementById('auto-age').textContent = data.age;
            }
            
            // Trigger bijtelling preview update
            updateBijtellingPreview();
        }

        // Update bijtelling preview
        function updateBijtellingPreview() {
            const bouwjaar = parseInt(document.getElementById('bouwjaar').value);
            const brandstof = document.getElementById('brandstof').value;
            const cataloguswaarde = parseFloat(document.getElementById('cataloguswaarde').value) || 0;
            const dagwaarde = parseFloat(document.getElementById('dagwaarde').value) || 0;
            
            if (!bouwjaar || !brandstof || cataloguswaarde === 0) {
                return;
            }
            
            // Bereken bijtelling via AJAX naar PHP backend
            // Voor nu gebruiken we JavaScript berekening
            const currentYear = phpConfig.currentYear;
            const age = currentYear - bouwjaar;
            
            let percentage = 22;
            let basis = cataloguswaarde;
            let uitleg = '';
            
            if (age >= 15 && age <= 30) {
                percentage = 35;
                basis = dagwaarde || cataloguswaarde * 0.15;
                uitleg = 'Youngtimer regeling';
            } else if (brandstof === 'Elektrisch') {
                const elektrischRules = phpConfig.bijtellingRules.elektrisch_2025;
                percentage = elektrischRules.percentage;
                if (cataloguswaarde > 30000) {
                    basis = 30000;
                }
                uitleg = elektrischRules.uitleg;
            } else if (bouwjaar < 2017) {
                percentage = 25;
                uitleg = 'Pre-2017 auto';
            }
            
            // Update preview
            document.getElementById('preview-bijtelling-percentage').textContent = percentage + '%';
            document.getElementById('preview-bijtelling-basis').textContent = '€ ' + basis.toLocaleString('nl-NL');
            document.getElementById('preview-bijtelling-jaar').textContent = '€ ' + ((basis * percentage) / 100).toLocaleString('nl-NL');
            
            const belastingPercentage = parseFloat(document.getElementById('belasting_percentage').value) || 37;
            const nettoMaand = ((basis * percentage) / 100 * belastingPercentage / 100 / 12);
            document.getElementById('preview-bijtelling-netto').textContent = '€ ' + nettoMaand.toLocaleString('nl-NL', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Auto update handlers
        document.getElementById('bouwjaar').addEventListener('change', updateBijtellingPreview);
        document.getElementById('brandstof').addEventListener('change', updateBijtellingPreview);
        document.getElementById('cataloguswaarde').addEventListener('change', updateBijtellingPreview);
        document.getElementById('dagwaarde').addEventListener('change', updateBijtellingPreview);
        document.getElementById('bruto_salaris').addEventListener('change', function() {
            // Bereken belasting percentage
            const salaris = parseFloat(this.value) || 0;
            let percentage = 37.07; // Basis tarief
            
            if (salaris > 73031) {
                percentage = 49.5;
            } else if (salaris > 37000) {
                percentage = 37.07;
            }
            
            document.getElementById('belasting_percentage').value = percentage;
            updateBijtellingPreview();
        });
    </script>
</body>
</html>