<?php
/**
 * AutoKosten Calculator - VERSIE 3 - Multi-Auto Vergelijker
 * 
 * @author Richard Surie
 * @version 3.1.0 - Tesla Fix & Enhanced Electric Detection
 * @website https://www.pianomanontour.nl/autovandezaakofprive
 * 
 * Features:
 * - Minimale input (8 velden)
 * - Automatische berekening van alle andere waarden
 * - Youngtimer dagwaarde support
 * - Multi-auto vergelijking
 * - Uitgebreide uitleg per berekening
 * - Bewerkbare resultaten
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'includes/bijtelling_database.php';
require_once 'includes/functions.php';
date_default_timezone_set('Europe/Amsterdam');

$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoKosten Calculator V3 - Multi-Auto Vergelijker | PianoManOnTour.nl</title>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .version-info {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
        .input-panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .comparison-panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .panel-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .input-group input, .input-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .input-group input:focus, .input-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-group.auto-filled input {
            background: #f0f9ff;
            border-color: #0ea5e9;
        }
        
        .input-group.auto-filled label::after {
            content: " (automatisch)";
            color: #0ea5e9;
            font-size: 0.85rem;
            font-weight: normal;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .auto-item {
            background: #f8fafc;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .auto-item:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .auto-item.selected {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .auto-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .auto-title {
            font-weight: 600;
            color: #333;
        }
        
        .auto-details {
            font-size: 0.9rem;
            color: #666;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .cost-summary {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-top: 15px;
            border-radius: 5px;
        }
        
        .cost-summary.winning {
            background: #dcfce7;
            border-left-color: #10b981;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-top: 30px;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-item {
            height: 300px;
        }
        
        .explanation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .explanation-content {
            background: white;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            margin: 5% auto;
            border-radius: 15px;
            padding: 30px;
            overflow-y: auto;
        }
        
        .explanation-section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .explanation-section h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .calculation-detail {
            font-family: monospace;
            background: #1f2937;
            color: #f9fafb;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            overflow-x: auto;
        }
        
        .navigation-buttons {
            text-align: center;
            margin: 30px 0;
        }
        
        .navigation-buttons .btn {
            margin: 0 10px;
        }
        
        .youngtimer-notice {
            background: #fffbeb;
            border: 2px solid #f59e0b;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .youngtimer-notice .icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 AutoKosten Calculator V3</h1>
            <p class="subtitle">Multi-Auto Vergelijker met Uitgebreide Analyse</p>
            <div class="version-info">
                <strong>Versie 3.1</strong> - Tesla fix & verbeterde elektrische detectie. Vergelijk onbeperkt auto's!
            </div>
        </div>

        <div class="main-content">
            <!-- Input Panel -->
            <div class="input-panel">
                <h2 class="panel-title">🔧 Auto Toevoegen</h2>
                
                <form id="autoForm">
                    <!-- Basis invoer velden -->
                    <div class="input-group">
                        <label for="kenteken">Kenteken</label>
                        <input type="text" id="kenteken" name="kenteken" placeholder="XX-XXX-X" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="kilometerstand">Huidige Kilometerstand</label>
                        <input type="number" id="kilometerstand" name="kilometerstand" placeholder="150000" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="km_per_maand">Kilometers per Maand</label>
                        <input type="number" id="km_per_maand" name="km_per_maand" placeholder="2000" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="aankoopprijs">Aankoopprijs (€)</label>
                        <input type="number" id="aankoopprijs" name="aankoopprijs" placeholder="25000" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="restwaarde">Verwachte Restwaarde (€)</label>
                        <input type="number" id="restwaarde" name="restwaarde" placeholder="10000" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="afschrijving_jaren">Afschrijving Periode (jaren)</label>
                        <input type="number" id="afschrijving_jaren" name="afschrijving_jaren" value="5" min="1" max="10" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="bruto_inkomen">Bruto Jaarinkomen (€)</label>
                        <input type="number" id="bruto_inkomen" name="bruto_inkomen" placeholder="60000" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="brandstofprijs">Brandstofprijs (€/liter of €/kWh)</label>
                        <input type="number" id="brandstofprijs" name="brandstofprijs" step="0.01" placeholder="1.65" required>
                        <small style="color: #666; font-size: 0.85rem; margin-top: 5px; display: block;">💡 Tip: ~€0.25/kWh voor elektrisch, ~€1.65/l voor benzine</small>
                    </div>

                    <!-- Automatisch ingevulde velden -->
                    <div id="auto-fields" style="display:none;">
                        <h3 style="margin: 30px 0 15px 0; color: #333;">📋 Automatisch Ingevuld</h3>
                        
                        <div class="input-group auto-filled">
                            <label for="merk">Merk</label>
                            <input type="text" id="merk" name="merk" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="model">Model</label>
                            <input type="text" id="model" name="model" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="bouwjaar">Bouwjaar</label>
                            <input type="text" id="bouwjaar" name="bouwjaar" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="eerste_toelating">Datum Eerste Toelating</label>
                            <input type="text" id="eerste_toelating" name="eerste_toelating" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="brandstof">Brandstoftype</label>
                            <input type="text" id="brandstof" name="brandstof" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="gewicht">Gewicht (kg)</label>
                            <input type="text" id="gewicht" name="gewicht" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="verbruik">Geschat Verbruik</label>
                            <input type="text" id="verbruik" name="verbruik" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="mrb">MRB per Maand (€)</label>
                            <input type="text" id="mrb" name="mrb" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="verzekering">Verzekering per Maand (€)</label>
                            <input type="text" id="verzekering" name="verzekering" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="onderhoud">Onderhoud per Maand (€)</label>
                            <input type="text" id="onderhoud" name="onderhoud" readonly>
                        </div>
                        
                        <div class="input-group auto-filled">
                            <label for="belasting_percentage">Inkomstenbelasting %</label>
                            <input type="text" id="belasting_percentage" name="belasting_percentage" readonly>
                        </div>

                        <!-- Youngtimer specifiek -->
                        <div id="youngtimer-section" style="display:none;">
                            <div class="youngtimer-notice">
                                <span class="icon">🏆</span>
                                <strong>Youngtimer Gedetecteerd!</strong><br>
                                Deze auto is 15+ jaar oud en valt onder de youngtimer regeling (35% bijtelling over dagwaarde).
                            </div>
                            <div class="input-group">
                                <label for="dagwaarde">Dagwaarde (€) - Voor Youngtimers</label>
                                <input type="number" id="dagwaarde" name="dagwaarde" placeholder="Wordt automatisch geschat">
                            </div>
                        </div>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" id="lookup-btn" class="btn btn-primary">
                            <span>🔍</span> Voertuiggegevens Ophalen
                        </button>
                        <button type="button" id="calculate-btn" class="btn btn-success" style="display:none;">
                            <span>🧮</span> Berekenen & Toevoegen
                        </button>
                        <button type="button" id="reset-btn" class="btn btn-warning">
                            <span>🔄</span> Reset Formulier
                        </button>
                    </div>
                </form>
            </div>

            <!-- Comparison Panel -->
            <div class="comparison-panel">
                <h2 class="panel-title">📊 Auto Vergelijking</h2>
                
                <div id="no-cars" class="loading">
                    <p>👆 Voeg een auto toe om te beginnen met vergelijken</p>
                </div>
                
                <div id="cars-list" style="display:none;"></div>
                
                <div class="navigation-buttons">
                    <button type="button" id="explain-btn" class="btn btn-primary" style="display:none;">
                        <span>📖</span> Uitleg Berekeningen
                    </button>
                    <button type="button" id="clear-all-btn" class="btn btn-danger" style="display:none;">
                        <span>🗑️</span> Alle Auto's Verwijderen
                    </button>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div id="charts-section" class="chart-container" style="display:none;">
            <h2 class="panel-title">📈 Kosten Vergelijking</h2>
            <div class="charts-grid">
                <div class="chart-item">
                    <canvas id="totalCostChart"></canvas>
                </div>
                <div class="chart-item">
                    <canvas id="monthlyCostChart"></canvas>
                </div>
                <div class="chart-item">
                    <canvas id="costBreakdownChart"></canvas>
                </div>
                <div class="chart-item">
                    <canvas id="savingsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Explanation Modal -->
    <div id="explanationModal" class="explanation-modal">
        <div class="explanation-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>📖 Uitleg Berekeningen</h2>
                <button type="button" onclick="closeExplanation()" class="btn btn-danger">✕ Sluiten</button>
            </div>
            <div id="explanationDetails"></div>
        </div>
    </div>

    <script>
        // Global variables
        let cars = [];
        let currentCarId = 0;
        let autoFieldsVisible = false;
        
        // RDW API base URL
        const RDW_API_BASE = 'https://opendata.rdw.nl/resource/m9d7-ebf2.json';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            loadSavedCars();
        });

        function setupEventListeners() {
            document.getElementById('lookup-btn').addEventListener('click', lookupVehicle);
            document.getElementById('calculate-btn').addEventListener('click', calculateAndAdd);
            document.getElementById('reset-btn').addEventListener('click', resetForm);
            document.getElementById('explain-btn').addEventListener('click', showExplanation);
            document.getElementById('clear-all-btn').addEventListener('click', clearAllCars);
            
            // Kenteken formatting
            document.getElementById('kenteken').addEventListener('input', formatKenteken);
        }

        function formatKenteken(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            
            // Add dashes based on Dutch license plate format
            if (value.length > 2 && value.length <= 5) {
                value = value.slice(0, 2) + '-' + value.slice(2);
            } else if (value.length > 5) {
                value = value.slice(0, 2) + '-' + value.slice(2, 5) + '-' + value.slice(5, 6);
            }
            
            e.target.value = value;
        }

        async function lookupVehicle() {
            const kenteken = document.getElementById('kenteken').value.replace(/[-\s]/g, '');
            
            if (!kenteken) {
                alert('Voer een kenteken in');
                return;
            }

            const lookupBtn = document.getElementById('lookup-btn');
            lookupBtn.innerHTML = '<span>⏳</span> Ophalen...';
            lookupBtn.disabled = true;

            try {
                // First get basic vehicle data
                const basicResponse = await fetch(`${RDW_API_BASE}?kenteken=${kenteken}`);
                const basicData = await basicResponse.json();
                
                if (!basicData || basicData.length === 0) {
                    throw new Error('Kenteken niet gevonden');
                }

                const vehicle = basicData[0];
                
                // Fill in the basic fields
                document.getElementById('merk').value = vehicle.merk || '';
                document.getElementById('model').value = vehicle.handelsbenaming || '';
                document.getElementById('bouwjaar').value = vehicle.datum_eerste_toelating ? vehicle.datum_eerste_toelating.split('-')[0] : '';
                document.getElementById('eerste_toelating').value = vehicle.datum_eerste_toelating || '';
                
                // Enhanced brandstof detection for electric vehicles
                let brandstof = vehicle.brandstof_omschrijving || '';
                if (!brandstof && vehicle.merk && vehicle.merk.toUpperCase() === 'TESLA') {
                    brandstof = 'Elektriciteit';
                }
                // Fallback detection for other electric brands
                if (!brandstof && vehicle.handelsbenaming) {
                    const model = vehicle.handelsbenaming.toLowerCase();
                    if (model.includes('electric') || model.includes('ev') || model.includes('e-') || 
                        model.includes('ioniq') || model.includes('leaf') || model.includes('zoe')) {
                        brandstof = 'Elektriciteit';
                    }
                }
                document.getElementById('brandstof').value = brandstof;
                
                document.getElementById('gewicht').value = vehicle.massa_ledig_voertuig || '';

                // Get additional technical data
                const techResponse = await fetch(`https://opendata.rdw.nl/resource/8ys7-d773.json?kenteken=${kenteken}`);
                const techData = await techResponse.json();
                
                // Calculate estimates
                calculateEstimates(vehicle, techData);
                
                // Show auto fields and calculate button
                document.getElementById('auto-fields').style.display = 'block';
                document.getElementById('calculate-btn').style.display = 'inline-flex';
                autoFieldsVisible = true;
                
                // Check for youngtimer
                checkYoungtimer(vehicle);
                
            } catch (error) {
                alert('Fout bij ophalen voertuiggegevens: ' + error.message);
            } finally {
                lookupBtn.innerHTML = '<span>🔍</span> Voertuiggegevens Ophalen';
                lookupBtn.disabled = false;
            }
        }

        function calculateEstimates(vehicle, techData) {
            const gewicht = parseInt(vehicle.massa_ledig_voertuig) || 1500;
            const bouwjaar = parseInt(vehicle.datum_eerste_toelating?.split('-')[0]) || 2020;
            const brandstof = vehicle.brandstof_omschrijving || 'Benzine';
            const aankoopprijs = parseFloat(document.getElementById('aankoopprijs').value) || 25000;
            const brutoInkomen = parseFloat(document.getElementById('bruto_inkomen').value) || 60000;

            // Estimate fuel consumption with improved electric detection
            let verbruik = '';
            const isElektrisch = brandstof.toLowerCase().includes('elektriciteit') || 
                                 brandstof.toLowerCase().includes('electric') ||
                                 vehicle.merk === 'TESLA';
            
            if (isElektrisch) {
                // More accurate electric consumption: Tesla Model 3 ~15-18 kWh/100km
                let baseConsumption = 16; // Base for efficient electric cars
                if (gewicht > 2000) baseConsumption += 4; // Heavy EVs
                if (gewicht > 2500) baseConsumption += 4; // Very heavy EVs  
                verbruik = baseConsumption + ' kWh/100km';
            } else if (brandstof.toLowerCase().includes('diesel')) {
                verbruik = Math.round((gewicht / 250) + 4) + ' l/100km';
            } else {
                verbruik = Math.round((gewicht / 200) + 5) + ' l/100km';
            }
            document.getElementById('verbruik').value = verbruik;

            // Calculate MRB with consistent electric detection
            let mrb = 0;
            if (isElektrisch) {
                // Electric cars: €0 in 2024, 25% in 2025, full tariff from 2026
                const year = <?php echo $currentYear; ?>;
                if (year <= 2024) {
                    mrb = 0;
                } else if (year === 2025) {
                    mrb = Math.round((gewicht / 100) * 8 * 0.25);
                } else {
                    mrb = Math.round((gewicht / 100) * 8); // Full tariff from 2026
                }
            } else {
                mrb = Math.round((gewicht / 100) * 8);
            }
            document.getElementById('mrb').value = mrb;

            // Estimate insurance
            const leeftijd = <?php echo $currentYear; ?> - bouwjaar;
            let verzekering = 50; // Base insurance
            if (aankoopprijs > 40000) verzekering += 30;
            if (aankoopprijs > 70000) verzekering += 50;
            if (leeftijd < 5) verzekering += 20;
            document.getElementById('verzekering').value = Math.round(verzekering);

            // Estimate maintenance with electric vehicle benefits
            let onderhoud = 75; // Base maintenance
            if (leeftijd > 10) onderhoud += 25;
            if (leeftijd > 15) onderhoud += 50;
            
            // Electric vehicles have lower maintenance costs
            if (isElektrisch) {
                onderhoud -= 25; // No oil changes, brake pads last longer, fewer moving parts
                if (leeftijd < 5) onderhoud -= 10; // Modern EVs very reliable when new
            }
            
            document.getElementById('onderhoud').value = Math.max(25, Math.round(onderhoud)); // Minimum €25/month

            // Calculate tax percentage
            const belastingPercentage = calculateTaxPercentage(brutoInkomen);
            document.getElementById('belasting_percentage').value = belastingPercentage + '%';
        }

        function calculateTaxPercentage(brutoInkomen) {
            // Nederlandse belastingschijven 2025 (vereenvoudigd)
            if (brutoInkomen <= 38441) return 36.55;
            if (brutoInkomen <= 76817) return 37.10;
            return 49.50;
        }

        function checkYoungtimer(vehicle) {
            const bouwjaar = parseInt(vehicle.datum_eerste_toelating?.split('-')[0]) || 2020;
            const leeftijd = <?php echo $currentYear; ?> - bouwjaar;
            
            if (leeftijd >= 15 && leeftijd <= 30) {
                document.getElementById('youngtimer-section').style.display = 'block';
                
                // Estimate market value for youngtimer
                const aankoopprijs = parseFloat(document.getElementById('aankoopprijs').value) || 25000;
                const geschatteDagwaarde = Math.round(aankoopprijs * 0.7); // Rough estimate
                document.getElementById('dagwaarde').placeholder = `Geschat: €${geschatteDagwaarde.toLocaleString()}`;
                document.getElementById('dagwaarde').value = geschatteDagwaarde;
            } else {
                document.getElementById('youngtimer-section').style.display = 'none';
            }
        }

        function calculateAndAdd() {
            if (!validateForm()) return;
            
            const carData = gatherFormData();
            const calculations = performCalculations(carData);
            
            // Add to cars array
            const newCar = {
                id: currentCarId++,
                data: carData,
                calculations: calculations
            };
            
            cars.push(newCar);
            
            // Update UI
            updateCarsDisplay();
            updateCharts();
            saveCars();
            
            // Show success message
            alert(`✅ ${carData.merk} ${carData.model} toegevoegd aan vergelijking!`);
            
            // Optionally reset form
            if (confirm('Wilt u nog een auto toevoegen?')) {
                resetForm();
            }
        }

        function validateForm() {
            const required = ['kenteken', 'kilometerstand', 'km_per_maand', 'aankoopprijs', 'restwaarde', 'afschrijving_jaren', 'bruto_inkomen', 'brandstofprijs'];
            
            for (let field of required) {
                const value = document.getElementById(field).value;
                if (!value || value.trim() === '') {
                    alert(`Veld "${field}" is verplicht`);
                    document.getElementById(field).focus();
                    return false;
                }
            }
            
            if (!autoFieldsVisible) {
                alert('Haal eerst de voertuiggegevens op met de "Voertuiggegevens Ophalen" knop');
                return false;
            }
            
            return true;
        }

        function gatherFormData() {
            return {
                kenteken: document.getElementById('kenteken').value,
                merk: document.getElementById('merk').value,
                model: document.getElementById('model').value,
                bouwjaar: parseInt(document.getElementById('bouwjaar').value),
                eerste_toelating: document.getElementById('eerste_toelating').value,
                brandstof: document.getElementById('brandstof').value,
                gewicht: parseInt(document.getElementById('gewicht').value),
                verbruik: document.getElementById('verbruik').value,
                kilometerstand: parseInt(document.getElementById('kilometerstand').value),
                km_per_maand: parseInt(document.getElementById('km_per_maand').value),
                aankoopprijs: parseFloat(document.getElementById('aankoopprijs').value),
                restwaarde: parseFloat(document.getElementById('restwaarde').value),
                afschrijving_jaren: parseInt(document.getElementById('afschrijving_jaren').value),
                bruto_inkomen: parseFloat(document.getElementById('bruto_inkomen').value),
                brandstofprijs: parseFloat(document.getElementById('brandstofprijs').value),
                mrb: parseFloat(document.getElementById('mrb').value),
                verzekering: parseFloat(document.getElementById('verzekering').value),
                onderhoud: parseFloat(document.getElementById('onderhoud').value),
                belasting_percentage: parseFloat(document.getElementById('belasting_percentage').value.replace('%', '')),
                dagwaarde: document.getElementById('dagwaarde').value ? parseFloat(document.getElementById('dagwaarde').value) : null
            };
        }

        function performCalculations(data) {
            // Calculate bijtelling
            const bijtellingInfo = calculateBijtelling(data);
            
            // Monthly costs
            const afschrijving = (data.aankoopprijs - data.restwaarde) / (data.afschrijving_jaren * 12);
            
            // Fuel consumption calculation
            const verbruikNummer = parseFloat(data.verbruik);
            const brandstofkosten = (data.km_per_maand / 100) * verbruikNummer * data.brandstofprijs;
            
            // Business costs (bijtelling)
            const bijtellingBedrag = bijtellingInfo.percentage * bijtellingInfo.basis / 100;
            const bijtellingBelasting = bijtellingBedrag * (data.belasting_percentage / 100) / 12;
            
            // Private costs
            const privateCosts = {
                afschrijving: afschrijving,
                brandstof: brandstofkosten,
                verzekering: data.verzekering,
                onderhoud: data.onderhoud,
                mrb: data.mrb,
                apk: data.bouwjaar < (<?php echo $currentYear; ?> - 3) ? 50/12 : 0
            };
            
            const totalPrivate = Object.values(privateCosts).reduce((a, b) => a + b, 0);
            const totalBusiness = bijtellingBelasting;
            
            return {
                bijtelling: bijtellingInfo,
                bijtellingBedrag: bijtellingBedrag,
                bijtellingBelasting: bijtellingBelasting,
                privateCosts: privateCosts,
                totalPrivate: totalPrivate,
                totalBusiness: totalBusiness,
                saving: totalPrivate - totalBusiness,
                recommendation: totalBusiness < totalPrivate ? 'zakelijk' : 'privé'
            };
        }

        function calculateBijtelling(data) {
            const leeftijd = <?php echo $currentYear; ?> - data.bouwjaar;
            const isYoungtimer = leeftijd >= 15 && leeftijd <= 30;
            const isPre2017 = data.eerste_toelating && data.eerste_toelating < '2017-01-01';
            const isElektrisch = data.brandstof.toLowerCase().includes('elektriciteit');
            
            let percentage, basis, uitleg;
            
            if (isYoungtimer) {
                percentage = 35;
                basis = data.dagwaarde || data.aankoopprijs;
                uitleg = `Youngtimer (${leeftijd} jaar): 35% over dagwaarde`;
            } else if (isElektrisch) {
                if (data.aankoopprijs <= 30000) {
                    percentage = <?php echo $currentYear; ?> >= 2025 ? 17 : 16;
                    basis = data.aankoopprijs;
                    uitleg = `Elektrisch ${<?php echo $currentYear; ?>}: ${percentage}% tot €30.000`;
                } else {
                    percentage = 22;
                    basis = data.aankoopprijs;
                    uitleg = `Elektrisch boven €30.000: 22%`;
                }
            } else if (isPre2017) {
                percentage = 25;
                basis = data.aankoopprijs;
                uitleg = 'Pre-2017 auto: 25% (behouden)';
            } else {
                percentage = 22;
                basis = data.aankoopprijs;
                uitleg = 'Standaard tarief: 22%';
            }
            
            return { percentage, basis, uitleg };
        }

        function updateCarsDisplay() {
            const container = document.getElementById('cars-list');
            const noCars = document.getElementById('no-cars');
            
            if (cars.length === 0) {
                container.style.display = 'none';
                noCars.style.display = 'block';
                document.getElementById('explain-btn').style.display = 'none';
                document.getElementById('clear-all-btn').style.display = 'none';
                document.getElementById('charts-section').style.display = 'none';
                return;
            }
            
            noCars.style.display = 'none';
            container.style.display = 'block';
            document.getElementById('explain-btn').style.display = 'inline-flex';
            document.getElementById('clear-all-btn').style.display = 'inline-flex';
            document.getElementById('charts-section').style.display = 'block';
            
            container.innerHTML = cars.map(car => createCarDisplay(car)).join('');
        }

        function createCarDisplay(car) {
            const isWinner = cars.length > 1 && car.calculations.totalBusiness === Math.min(...cars.map(c => c.calculations.totalBusiness));
            
            return `
                <div class="auto-item ${isWinner ? 'selected' : ''}">
                    <div class="auto-header">
                        <div class="auto-title">
                            ${car.data.merk} ${car.data.model}
                            ${isWinner ? ' 🏆' : ''}
                        </div>
                        <button onclick="removeCar(${car.id})" class="btn btn-danger" style="padding: 5px 10px; font-size: 14px;">
                            ✕
                        </button>
                    </div>
                    <div class="auto-details">
                        <div><strong>Kenteken:</strong> ${car.data.kenteken}</div>
                        <div><strong>Bouwjaar:</strong> ${car.data.bouwjaar}</div>
                        <div><strong>Bijtelling:</strong> ${car.calculations.bijtelling.uitleg}</div>
                        <div><strong>Kosten/maand:</strong> €${car.calculations.totalBusiness.toFixed(2)} (zakelijk)</div>
                    </div>
                    <div class="cost-summary ${car.calculations.recommendation === 'zakelijk' ? 'winning' : ''}">
                        <strong>Advies:</strong> ${car.calculations.recommendation === 'zakelijk' ? 'Auto van de zaak' : 'Privé auto'}
                        ${car.calculations.saving > 0 ? 
                            `<br><strong>Besparing:</strong> €${car.calculations.saving.toFixed(2)}/maand` : 
                            `<br><strong>Extra kosten:</strong> €${Math.abs(car.calculations.saving).toFixed(2)}/maand`
                        }
                    </div>
                </div>
            `;
        }

        function removeCar(carId) {
            cars = cars.filter(car => car.id !== carId);
            updateCarsDisplay();
            updateCharts();
            saveCars();
        }

        function clearAllCars() {
            if (confirm('Weet u zeker dat u alle auto\'s wilt verwijderen?')) {
                cars = [];
                updateCarsDisplay();
                localStorage.removeItem('autokosten_cars_v3');
            }
        }

        function updateCharts() {
            if (cars.length === 0) return;
            
            // Data for charts
            const labels = cars.map(car => `${car.data.merk} ${car.data.model}`);
            const businessCosts = cars.map(car => car.calculations.totalBusiness);
            const privateCosts = cars.map(car => car.calculations.totalPrivate);
            
            // Total Cost Comparison
            updateChart('totalCostChart', {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Zakelijk (€/maand)',
                        data: businessCosts,
                        backgroundColor: '#667eea'
                    }, {
                        label: 'Privé (€/maand)',
                        data: privateCosts,
                        backgroundColor: '#f59e0b'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Totale Kosten Vergelijking'
                        }
                    }
                }
            });
            
            // Monthly savings
            const savings = cars.map(car => car.calculations.saving);
            updateChart('savingsChart', {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Besparing (€/maand)',
                        data: savings,
                        backgroundColor: savings.map(s => s > 0 ? '#10b981' : '#ef4444')
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Maandelijkse Besparing/Extra kosten'
                        }
                    }
                }
            });
            
            // If we have at least one car, show cost breakdown for the first car
            if (cars.length > 0) {
                const firstCar = cars[0];
                const breakdownLabels = ['Afschrijving', 'Brandstof', 'Verzekering', 'Onderhoud', 'MRB', 'APK'];
                const breakdownData = [
                    firstCar.calculations.privateCosts.afschrijving,
                    firstCar.calculations.privateCosts.brandstof,
                    firstCar.calculations.privateCosts.verzekering,
                    firstCar.calculations.privateCosts.onderhoud,
                    firstCar.calculations.privateCosts.mrb,
                    firstCar.calculations.privateCosts.apk
                ];
                
                updateChart('costBreakdownChart', {
                    type: 'doughnut',
                    data: {
                        labels: breakdownLabels,
                        datasets: [{
                            data: breakdownData,
                            backgroundColor: [
                                '#667eea',
                                '#f59e0b',
                                '#10b981',
                                '#ef4444',
                                '#8b5cf6',
                                '#06b6d4'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: `Kosten Breakdown - ${firstCar.data.merk} ${firstCar.data.model}`
                            }
                        }
                    }
                });
            }
            
            // Monthly cost trend (if multiple cars)
            updateChart('monthlyCostChart', {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Zakelijke Kosten',
                        data: businessCosts,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true
                    }, {
                        label: 'Privé Kosten',
                        data: privateCosts,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Kosten Trend'
                        }
                    }
                }
            });
        }

        function updateChart(canvasId, config) {
            const canvas = document.getElementById(canvasId);
            const ctx = canvas.getContext('2d');
            
            // Destroy existing chart if it exists
            if (canvas.chart) {
                canvas.chart.destroy();
            }
            
            // Create new chart
            canvas.chart = new Chart(ctx, config);
        }

        function showExplanation() {
            if (cars.length === 0) {
                alert('Voeg eerst een auto toe om uitleg te zien');
                return;
            }
            
            const modal = document.getElementById('explanationModal');
            const details = document.getElementById('explanationDetails');
            
            let explanationHtml = '';
            
            cars.forEach((car, index) => {
                explanationHtml += generateCarExplanation(car, index + 1);
            });
            
            details.innerHTML = explanationHtml;
            modal.style.display = 'block';
        }

        function generateCarExplanation(car, carNumber) {
            const data = car.data;
            const calc = car.calculations;
            
            return `
                <div class="explanation-section">
                    <h3>🚗 Auto ${carNumber}: ${data.merk} ${data.model}</h3>
                    
                    <h4>📊 Bijtelling Berekening</h4>
                    <p><strong>${calc.bijtelling.uitleg}</strong></p>
                    <div class="calculation-detail">
Bijtelling per jaar: ${calc.bijtelling.percentage}% × €${calc.bijtelling.basis.toLocaleString()} = €${calc.bijtellingBedrag.toLocaleString()}
Bijtelling per maand: €${calc.bijtellingBedrag.toLocaleString()} ÷ 12 = €${(calc.bijtellingBedrag/12).toFixed(2)}
Extra belasting per maand: €${(calc.bijtellingBedrag/12).toFixed(2)} × ${data.belasting_percentage}% = €${calc.bijtellingBelasting.toFixed(2)}
                    </div>
                    
                    <h4>💰 Privé Kosten Breakdown</h4>
                    <div class="calculation-detail">
Afschrijving: (€${data.aankoopprijs.toLocaleString()} - €${data.restwaarde.toLocaleString()}) ÷ ${data.afschrijving_jaren} ÷ 12 = €${calc.privateCosts.afschrijving.toFixed(2)}/maand
Brandstof: ${data.km_per_maand} km ÷ 100 × ${data.verbruik} × €${data.brandstofprijs} = €${calc.privateCosts.brandstof.toFixed(2)}/maand
Verzekering: €${calc.privateCosts.verzekering.toFixed(2)}/maand (geschat op basis van waarde en leeftijd)
Onderhoud: €${calc.privateCosts.onderhoud.toFixed(2)}/maand (geschat op basis van leeftijd)
MRB: €${calc.privateCosts.mrb.toFixed(2)}/maand (op basis van gewicht: ${data.gewicht}kg)
APK: €${calc.privateCosts.apk.toFixed(2)}/maand (${data.bouwjaar < (<?php echo $currentYear; ?> - 3) ? '€50/jaar' : 'Niet van toepassing'})

Totaal Privé: €${calc.totalPrivate.toFixed(2)}/maand
                    </div>
                    
                    <h4>🎯 Conclusie</h4>
                    <p><strong>Zakelijk:</strong> €${calc.totalBusiness.toFixed(2)}/maand</p>
                    <p><strong>Privé:</strong> €${calc.totalPrivate.toFixed(2)}/maand</p>
                    <p><strong>Verschil:</strong> €${Math.abs(calc.saving).toFixed(2)}/maand ${calc.saving > 0 ? 'voordeel zakelijk' : 'voordeel privé'}</p>
                    <p><strong>Advies:</strong> ${calc.recommendation === 'zakelijk' ? '🏢 Auto van de zaak' : '🏠 Privé auto'}</p>
                </div>
            `;
        }

        function closeExplanation() {
            document.getElementById('explanationModal').style.display = 'none';
        }

        function resetForm() {
            document.getElementById('autoForm').reset();
            document.getElementById('auto-fields').style.display = 'none';
            document.getElementById('calculate-btn').style.display = 'none';
            document.getElementById('youngtimer-section').style.display = 'none';
            autoFieldsVisible = false;
        }

        function saveCars() {
            localStorage.setItem('autokosten_cars_v3', JSON.stringify(cars));
        }

        function loadSavedCars() {
            const saved = localStorage.getItem('autokosten_cars_v3');
            if (saved) {
                cars = JSON.parse(saved);
                if (cars.length > 0) {
                    currentCarId = Math.max(...cars.map(c => c.id)) + 1;
                    updateCarsDisplay();
                    updateCharts();
                }
            }
        }

        // Click outside modal to close
        window.onclick = function(event) {
            const modal = document.getElementById('explanationModal');
            if (event.target === modal) {
                closeExplanation();
            }
        }
    </script>
</body>
</html>