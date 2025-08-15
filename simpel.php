<?php
/**
 * AutoKosten Calculator - SIMPELE VERSIE
 * 
 * @author Richard Surie
 * @version 2.0.0 - Simplified
 * @website https://www.pianomanontour.nl/autovandezaakofprive
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'includes/bijtelling_database.php';
date_default_timezone_set('Europe/Amsterdam');

$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoKosten Calculator SIMPEL - Snel & Makkelijk | PianoManOnTour.nl</title>
    
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
        }
        
        .container {
            max-width: 900px;
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
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.95;
        }
        
        .version-toggle {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .toggle-btn {
            padding: 12px 24px;
            border: 2px solid white;
            background: rgba(255,255,255,0.2);
            color: white;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .toggle-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .toggle-btn.active {
            background: white;
            color: #764ba2;
        }
        
        .main-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .progress-bar::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .progress-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #999;
            transition: all 0.3s;
        }
        
        .progress-step.active .progress-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #764ba2;
            color: white;
        }
        
        .progress-step.completed .progress-circle {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }
        
        .progress-label {
            margin-top: 8px;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .form-hint {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .form-input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #764ba2;
            box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
        }
        
        .kenteken-group {
            position: relative;
        }
        
        .lookup-btn {
            position: absolute;
            right: 10px;
            top: 42px;
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .lookup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
        }
        
        .lookup-btn.loading {
            background: #999;
            pointer-events: none;
        }
        
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(118, 75, 162, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }
        
        /* Results Section */
        .results-container {
            margin-top: 30px;
        }
        
        .result-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .result-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 25px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .result-card.winner {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        
        .result-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .result-amount {
            font-size: 32px;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 20px;
        }
        
        .result-breakdown {
            list-style: none;
        }
        
        .result-breakdown li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .result-breakdown li:last-child {
            border-bottom: none;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            min-height: 300px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid white;
            border-top-color: #764ba2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease-out;
            z-index: 10000;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .notification.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .notification.info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        /* Auto-gevulde velden styling */
        .auto-filled {
            background: #f0fdf4 !important;
            border-color: #10b981 !important;
        }
        
        .field-status {
            display: inline-block;
            margin-left: 8px;
            font-size: 14px;
            font-weight: normal;
            color: #10b981;
        }
        
        @media (max-width: 768px) {
            .result-cards {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .main-card {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🚗 AutoKosten Calculator</h1>
            <p>Simpele Versie - Snel resultaat in 3 stappen!</p>
        </div>

        <!-- Version Toggle -->
        <div class="version-toggle">
            <a href="index.php" class="toggle-btn">
                <span>📊</span> Uitgebreide Versie
            </a>
            <button class="toggle-btn active">
                <span>⚡</span> Simpele Versie
            </button>
        </div>

        <!-- Main Card -->
        <div class="main-card">
            <!-- Progress Bar -->
            <div class="progress-bar">
                <div class="progress-step active" data-step="1">
                    <div class="progress-circle">1</div>
                    <span class="progress-label">Voertuig</span>
                </div>
                <div class="progress-step" data-step="2">
                    <div class="progress-circle">2</div>
                    <span class="progress-label">Gebruik</span>
                </div>
                <div class="progress-step" data-step="3">
                    <div class="progress-circle">3</div>
                    <span class="progress-label">Financieel</span>
                </div>
            </div>

            <!-- Form -->
            <form id="simple-calculator">
                <!-- Step 1: Vehicle -->
                <div class="form-section active" data-section="1">
                    <h2>Stap 1: Voertuig Gegevens</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        Vul het kenteken in en wij halen automatisch alle gegevens op!
                    </p>

                    <div class="form-group kenteken-group">
                        <label class="form-label">
                            Kenteken *
                        </label>
                        <input type="text" 
                               id="kenteken" 
                               class="form-input" 
                               placeholder="XX-XXX-X" 
                               maxlength="8"
                               required>
                        <button type="button" class="lookup-btn" onclick="lookupKenteken()">
                            Ophalen
                        </button>
                        <div class="form-hint">Bijvoorbeeld: 99-ZZH-3</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Kilometerstand *
                        </label>
                        <input type="number" 
                               id="kilometerstand" 
                               class="form-input" 
                               placeholder="75000"
                               min="0"
                               required>
                        <div class="form-hint">Huidige kilometerstand van de auto</div>
                    </div>

                    <!-- Auto-filled fields (readonly) -->
                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <h3 style="font-size: 18px; margin-bottom: 15px; color: #666;">
                            ✅ Automatisch ingevuld na kenteken lookup:
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <strong>Merk:</strong> <span id="display-merk">-</span>
                            </div>
                            <div>
                                <strong>Model:</strong> <span id="display-model">-</span>
                            </div>
                            <div>
                                <strong>Bouwjaar:</strong> <span id="display-bouwjaar">-</span>
                            </div>
                            <div>
                                <strong>Brandstof:</strong> <span id="display-brandstof">-</span>
                            </div>
                            <div>
                                <strong>Gewicht:</strong> <span id="display-gewicht">-</span> kg
                            </div>
                            <div>
                                <strong>Cataloguswaarde:</strong> €<span id="display-catalogus">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" disabled>
                            ← Vorige
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                            Volgende →
                        </button>
                    </div>
                </div>

                <!-- Step 2: Usage -->
                <div class="form-section" data-section="2">
                    <h2>Stap 2: Gebruik & Kosten</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        Nog maar een paar velden! De rest berekenen wij.
                    </p>

                    <div class="form-group">
                        <label class="form-label">
                            Kilometers per maand *
                        </label>
                        <input type="number" 
                               id="km_per_maand" 
                               class="form-input" 
                               placeholder="1500"
                               value="1500"
                               min="0"
                               max="10000"
                               required>
                        <div class="form-hint">
                            Dat is <span id="km-per-jaar">18.000</span> km per jaar
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Aankoopprijs (€) *
                        </label>
                        <input type="number" 
                               id="aankoopprijs" 
                               class="form-input" 
                               placeholder="25000"
                               min="0"
                               step="100"
                               required>
                        <div class="form-hint">Wat heeft u betaald of gaat u betalen?</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Afschrijving periode (jaren) *
                        </label>
                        <input type="number" 
                               id="afschrijving_jaren" 
                               class="form-input" 
                               value="5"
                               min="1"
                               max="10"
                               required>
                        <div class="form-hint">Meestal 5 jaar voor een auto</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Brandstofprijs (€ per liter/kWh) *
                            <span class="field-status" id="fuel-price-status"></span>
                        </label>
                        <input type="number" 
                               id="brandstofprijs" 
                               class="form-input" 
                               placeholder="2.10"
                               value="2.10"
                               min="0"
                               max="5"
                               step="0.01"
                               required>
                        <div class="form-hint">Huidige prijs aan de pomp</div>
                    </div>

                    <!-- Auto-calculated fields -->
                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <h3 style="font-size: 18px; margin-bottom: 15px; color: #666;">
                            🤖 Automatisch berekend:
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <strong>Verbruik:</strong> <span id="display-verbruik">-</span> L/100km
                            </div>
                            <div>
                                <strong>MRB:</strong> €<span id="display-mrb">-</span>/maand
                            </div>
                            <div>
                                <strong>Verzekering:</strong> €<span id="display-verzekering">-</span>/maand
                            </div>
                            <div>
                                <strong>Onderhoud:</strong> €<span id="display-onderhoud">-</span>/maand
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="previousStep(1)">
                            ← Vorige
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                            Volgende →
                        </button>
                    </div>
                </div>

                <!-- Step 3: Financial -->
                <div class="form-section" data-section="3">
                    <h2>Stap 3: Financiële Situatie</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        Laatste stap! Daarna krijgt u direct het resultaat.
                    </p>

                    <div class="form-group">
                        <label class="form-label">
                            Bruto jaarinkomen (€) *
                        </label>
                        <input type="number" 
                               id="bruto_inkomen" 
                               class="form-input" 
                               placeholder="45000"
                               min="0"
                               step="1000"
                               required>
                        <div class="form-hint">Voor het berekenen van uw belastingschijf</div>
                    </div>

                    <!-- Auto-calculated tax -->
                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <h3 style="font-size: 18px; margin-bottom: 15px; color: #666;">
                            📊 Belasting Berekening:
                        </h3>
                        <div style="display: grid; gap: 15px;">
                            <div>
                                <strong>Belastingschijf 2025:</strong> 
                                <span id="display-schijf">-</span>
                            </div>
                            <div>
                                <strong>Belastingpercentage:</strong> 
                                <span id="display-belasting">-</span>%
                            </div>
                            <div>
                                <strong>Bijtelling percentage:</strong> 
                                <span id="display-bijtelling">-</span>%
                            </div>
                            <div style="font-size: 18px; color: #764ba2;">
                                <strong>Netto bijtelling kosten:</strong> 
                                €<span id="display-netto-bijtelling">-</span>/maand
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="previousStep(2)">
                            ← Vorige
                        </button>
                        <button type="button" class="btn btn-success" onclick="calculateResults()">
                            🧮 Bereken Resultaat
                        </button>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="form-section" data-section="results">
                    <h2>📊 Uw Resultaten</h2>
                    
                    <div class="results-container">
                        <!-- Result Cards -->
                        <div class="result-cards">
                            <div class="result-card" id="prive-card">
                                <h3>🚗 Privé Auto</h3>
                                <div class="result-amount" id="prive-total">€ -</div>
                                <ul class="result-breakdown" id="prive-breakdown">
                                    <!-- Filled by JS -->
                                </ul>
                            </div>
                            
                            <div class="result-card" id="zakelijk-card">
                                <h3>💼 Auto van de Zaak</h3>
                                <div class="result-amount" id="zakelijk-total">€ -</div>
                                <ul class="result-breakdown" id="zakelijk-breakdown">
                                    <!-- Filled by JS -->
                                </ul>
                            </div>
                        </div>

                        <!-- Winner announcement -->
                        <div id="winner-announcement" style="padding: 20px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 15px; text-align: center; margin: 20px 0;">
                            <!-- Filled by JS -->
                        </div>

                        <!-- Charts -->
                        <div class="charts-grid">
                            <div class="chart-container">
                                <canvas id="comparison-chart"></canvas>
                            </div>
                            <div class="chart-container">
                                <canvas id="breakdown-chart"></canvas>
                            </div>
                            <div class="chart-container">
                                <canvas id="yearly-chart"></canvas>
                            </div>
                            <div class="chart-container">
                                <canvas id="savings-chart"></canvas>
                            </div>
                        </div>

                        <div class="button-group">
                            <button type="button" class="btn btn-secondary" onclick="resetCalculator()">
                                🔄 Opnieuw
                            </button>
                            <button type="button" class="btn btn-primary" onclick="window.print()">
                                🖨️ Print Resultaat
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading">
        <div class="loader"></div>
    </div>

    <script>
        // Global state
        let vehicleData = {};
        let calculatedData = {};
        let charts = {};

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Format kenteken input
            document.getElementById('kenteken').addEventListener('input', formatKenteken);
            
            // Update km per jaar
            document.getElementById('km_per_maand').addEventListener('input', updateKmPerJaar);
            
            // Update belasting when inkomen changes
            document.getElementById('bruto_inkomen').addEventListener('input', calculateBelasting);
            
            // Initialize tooltips
            initializeTooltips();
        });

        // Format kenteken
        function formatKenteken(e) {
            let value = e.target.value.toUpperCase();
            value = value.replace(/[^A-Z0-9]/g, '');
            
            if (value.length >= 6) {
                if (/^\d/.test(value)) {
                    value = value.slice(0, 2) + '-' + value.slice(2, 5) + '-' + value.slice(5, 6);
                } else {
                    value = value.slice(0, 2) + '-' + value.slice(2, 5) + '-' + value.slice(5, 6);
                }
            }
            
            e.target.value = value;
        }

        // RDW Lookup
        async function lookupKenteken() {
            const kenteken = document.getElementById('kenteken').value.replace(/-/g, '');
            
            if (kenteken.length < 6) {
                showNotification('Vul een geldig kenteken in', 'error');
                return;
            }
            
            const btn = document.querySelector('.lookup-btn');
            btn.textContent = 'Bezig...';
            btn.classList.add('loading');
            showLoading(true);
            
            try {
                const response = await fetch(`api/rdw-lookup.php?kenteken=${kenteken}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    vehicleData = result.data;
                    fillVehicleData(result.data);
                    calculateAutoValues();
                    showNotification('✅ Voertuiggegevens opgehaald!', 'success');
                } else {
                    showNotification('❌ Kenteken niet gevonden', 'error');
                }
            } catch (error) {
                console.error('Lookup error:', error);
                showNotification('❌ Er ging iets mis', 'error');
            } finally {
                btn.textContent = 'Ophalen';
                btn.classList.remove('loading');
                showLoading(false);
            }
        }

        // Fill vehicle data
        function fillVehicleData(data) {
            document.getElementById('display-merk').textContent = data.merk || '-';
            document.getElementById('display-model').textContent = data.model || data.handelsbenaming || '-';
            document.getElementById('display-bouwjaar').textContent = data.bouwjaar || '-';
            document.getElementById('display-gewicht').textContent = data.massa_ledig_voertuig || '-';
            document.getElementById('display-catalogus').textContent = (data.catalogusprijs || 0).toLocaleString('nl-NL');
            
            // Brandstof
            let brandstof = 'Onbekend';
            if (data.brandstof_type) {
                brandstof = data.brandstof_type;
            } else if (data.brandstof && data.brandstof.length > 0) {
                brandstof = data.brandstof[0];
            }
            document.getElementById('display-brandstof').textContent = brandstof;
            
            // Update fuel price based on type
            updateFuelPrice(brandstof);
        }

        // Calculate auto values (smart estimates)
        function calculateAutoValues() {
            const bouwjaar = vehicleData.bouwjaar || 2020;
            const gewicht = vehicleData.massa_ledig_voertuig || 1500;
            const catalogus = vehicleData.catalogusprijs || 30000;
            const kilometerstand = parseInt(document.getElementById('kilometerstand').value) || 0;
            const brandstof = vehicleData.brandstof_type || 'benzine';
            const age = new Date().getFullYear() - bouwjaar;
            
            // Estimate verbruik based on weight and type
            let verbruik = 7; // default
            if (brandstof.toLowerCase().includes('elektr')) {
                verbruik = 18; // kWh/100km
            } else if (brandstof.toLowerCase().includes('diesel')) {
                verbruik = 5.5 + (gewicht / 1000); // Diesel more efficient
            } else if (brandstof.toLowerCase().includes('hybrid')) {
                verbruik = 4.5 + (gewicht / 1500);
            } else {
                verbruik = 6 + (gewicht / 1000); // Benzine
            }
            document.getElementById('display-verbruik').textContent = verbruik.toFixed(1);
            
            // Calculate MRB (road tax)
            let mrbPerMonth = 0;
            if (brandstof.toLowerCase().includes('elektr')) {
                mrbPerMonth = 0; // Electric cars exempt until 2025
            } else if (brandstof.toLowerCase().includes('diesel')) {
                mrbPerMonth = Math.round((gewicht / 100) * 12 / 3); // Diesel higher tax
            } else {
                mrbPerMonth = Math.round((gewicht / 100) * 8 / 3); // Benzine
            }
            document.getElementById('display-mrb').textContent = mrbPerMonth;
            
            // Estimate verzekering based on catalog value and age
            let verzekeringPerMonth = 50;
            if (catalogus < 15000) {
                verzekeringPerMonth = 40 + (age > 10 ? 10 : 0);
            } else if (catalogus < 30000) {
                verzekeringPerMonth = 60 + (age > 10 ? 15 : 0);
            } else if (catalogus < 50000) {
                verzekeringPerMonth = 90 + (age > 10 ? 20 : 0);
            } else {
                verzekeringPerMonth = 120 + (catalogus - 50000) / 500;
            }
            
            // Adjust for electric (usually higher insurance)
            if (brandstof.toLowerCase().includes('elektr')) {
                verzekeringPerMonth *= 1.2;
            }
            
            document.getElementById('display-verzekering').textContent = Math.round(verzekeringPerMonth);
            
            // Estimate onderhoud based on age and mileage
            let onderhoudPerMonth = 50;
            if (age > 5) onderhoudPerMonth += 30;
            if (age > 10) onderhoudPerMonth += 40;
            if (kilometerstand > 100000) onderhoudPerMonth += 30;
            if (kilometerstand > 200000) onderhoudPerMonth += 50;
            
            // Electric cars have lower maintenance
            if (brandstof.toLowerCase().includes('elektr')) {
                onderhoudPerMonth *= 0.6;
            }
            
            document.getElementById('display-onderhoud').textContent = Math.round(onderhoudPerMonth);
            
            // Store calculated values
            calculatedData = {
                verbruik: verbruik,
                mrb: mrbPerMonth,
                verzekering: verzekeringPerMonth,
                onderhoud: onderhoudPerMonth
            };
        }

        // Update fuel price
        function updateFuelPrice(brandstof) {
            const brandstofInput = document.getElementById('brandstofprijs');
            const statusSpan = document.getElementById('fuel-price-status');
            
            if (brandstof.toLowerCase().includes('elektr')) {
                brandstofInput.value = '0.40';
                brandstofInput.placeholder = '0.40';
                statusSpan.textContent = '(€/kWh)';
            } else if (brandstof.toLowerCase().includes('diesel')) {
                brandstofInput.value = '1.85';
                brandstofInput.placeholder = '1.85';
                statusSpan.textContent = '(€/liter)';
            } else if (brandstof.toLowerCase().includes('lpg')) {
                brandstofInput.value = '0.95';
                brandstofInput.placeholder = '0.95';
                statusSpan.textContent = '(€/liter)';
            } else {
                brandstofInput.value = '2.10';
                brandstofInput.placeholder = '2.10';
                statusSpan.textContent = '(€/liter)';
            }
        }

        // Update km per jaar
        function updateKmPerJaar() {
            const kmPerMaand = parseInt(document.getElementById('km_per_maand').value) || 0;
            const kmPerJaar = kmPerMaand * 12;
            document.getElementById('km-per-jaar').textContent = kmPerJaar.toLocaleString('nl-NL');
        }

        // Calculate belasting
        function calculateBelasting() {
            const inkomen = parseFloat(document.getElementById('bruto_inkomen').value) || 0;
            
            // 2025 tax brackets Netherlands
            let percentage = 0;
            let schijf = '';
            
            if (inkomen <= 38441) {
                percentage = 35.82;
                schijf = 'Schijf 1';
            } else if (inkomen <= 76817) {
                percentage = 37.48;
                schijf = 'Schijf 2';
            } else {
                percentage = 49.50;
                schijf = 'Schijf 3';
            }
            
            document.getElementById('display-schijf').textContent = schijf;
            document.getElementById('display-belasting').textContent = percentage.toFixed(2);
            
            // Calculate bijtelling
            calculateBijtelling(percentage);
        }

        // Calculate bijtelling
        function calculateBijtelling(belastingPercentage) {
            const bouwjaar = vehicleData.bouwjaar || 2020;
            const catalogus = vehicleData.catalogusprijs || 30000;
            const brandstof = vehicleData.brandstof_type || 'benzine';
            const currentYear = new Date().getFullYear();
            const age = currentYear - bouwjaar;
            
            let bijtellingPercentage = 22; // Default
            
            // Youngtimer (15+ years)
            if (age >= 15) {
                bijtellingPercentage = 35;
            }
            // Pre-2017 cars
            else if (bouwjaar < 2017) {
                bijtellingPercentage = 25;
            }
            // Electric cars 2025
            else if (brandstof.toLowerCase().includes('elektr')) {
                if (catalogus <= 30000) {
                    bijtellingPercentage = 17;
                } else {
                    // Weighted average
                    const lowPart = 30000 * 0.17;
                    const highPart = (catalogus - 30000) * 0.22;
                    bijtellingPercentage = ((lowPart + highPart) / catalogus * 100);
                }
            }
            
            document.getElementById('display-bijtelling').textContent = bijtellingPercentage.toFixed(1);
            
            // Calculate netto kosten
            const jaarlijkseBijtelling = catalogus * (bijtellingPercentage / 100);
            const maandelijkseBijtelling = jaarlijkseBijtelling / 12;
            const nettoKosten = maandelijkseBijtelling * (belastingPercentage / 100);
            
            document.getElementById('display-netto-bijtelling').textContent = nettoKosten.toFixed(2);
        }

        // Navigation
        function nextStep(step) {
            // Validate current step
            const currentSection = document.querySelector('.form-section.active');
            const requiredFields = currentSection.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value) {
                    field.style.borderColor = '#ef4444';
                    valid = false;
                }
            });
            
            if (!valid) {
                showNotification('Vul alle verplichte velden in', 'error');
                return;
            }
            
            // Update progress
            document.querySelectorAll('.progress-step').forEach(s => {
                const stepNum = parseInt(s.dataset.step);
                if (stepNum < step) {
                    s.classList.add('completed');
                    s.classList.remove('active');
                } else if (stepNum === step) {
                    s.classList.add('active');
                    s.classList.remove('completed');
                } else {
                    s.classList.remove('active', 'completed');
                }
            });
            
            // Show section
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            document.querySelector(`[data-section="${step}"]`).classList.add('active');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function previousStep(step) {
            nextStep(step);
        }

        // Calculate Results
        function calculateResults() {
            // Validate final step
            const inkomen = document.getElementById('bruto_inkomen').value;
            if (!inkomen) {
                showNotification('Vul uw bruto jaarinkomen in', 'error');
                return;
            }
            
            showLoading(true);
            
            // Gather all data
            const data = {
                // Vehicle
                merk: vehicleData.merk || 'Onbekend',
                model: vehicleData.model || vehicleData.handelsbenaming || 'Onbekend',
                bouwjaar: vehicleData.bouwjaar || 2020,
                brandstof: vehicleData.brandstof_type || 'benzine',
                gewicht: vehicleData.massa_ledig_voertuig || 1500,
                cataloguswaarde: vehicleData.catalogusprijs || 30000,
                
                // Usage
                kilometerstand: parseInt(document.getElementById('kilometerstand').value) || 0,
                kmPerMaand: parseInt(document.getElementById('km_per_maand').value) || 1500,
                aankoopprijs: parseFloat(document.getElementById('aankoopprijs').value) || 25000,
                afschrijvingJaren: parseInt(document.getElementById('afschrijving_jaren').value) || 5,
                brandstofprijs: parseFloat(document.getElementById('brandstofprijs').value) || 2.10,
                
                // Calculated
                verbruik: calculatedData.verbruik || 7,
                mrb: calculatedData.mrb || 50,
                verzekering: calculatedData.verzekering || 80,
                onderhoud: calculatedData.onderhoud || 60,
                
                // Financial
                brutoInkomen: parseFloat(inkomen)
            };
            
            // Calculate private costs
            const privateCosts = calculatePrivateCosts(data);
            
            // Calculate business costs
            const businessCosts = calculateBusinessCosts(data);
            
            // Display results
            displayResults(privateCosts, businessCosts);
            
            // Create charts
            createCharts(privateCosts, businessCosts);
            
            // Show results section
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            document.querySelector('[data-section="results"]').classList.add('active');
            
            // Update progress to completed
            document.querySelectorAll('.progress-step').forEach(s => {
                s.classList.add('completed');
                s.classList.remove('active');
            });
            
            showLoading(false);
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Calculate private costs
        function calculatePrivateCosts(data) {
            const brandstofKosten = (data.kmPerMaand / 100) * data.verbruik * data.brandstofprijs;
            const afschrijving = (data.aankoopprijs * 0.8) / (data.afschrijvingJaren * 12); // 20% restwaarde
            const totaal = brandstofKosten + data.mrb + data.verzekering + data.onderhoud + afschrijving;
            
            return {
                brandstof: brandstofKosten,
                mrb: data.mrb,
                verzekering: data.verzekering,
                onderhoud: data.onderhoud,
                afschrijving: afschrijving,
                totaal: totaal
            };
        }

        // Calculate business costs
        function calculateBusinessCosts(data) {
            const age = new Date().getFullYear() - data.bouwjaar;
            let bijtellingPercentage = 22;
            
            if (age >= 15) {
                bijtellingPercentage = 35;
            } else if (data.bouwjaar < 2017) {
                bijtellingPercentage = 25;
            } else if (data.brandstof.toLowerCase().includes('elektr')) {
                if (data.cataloguswaarde <= 30000) {
                    bijtellingPercentage = 17;
                } else {
                    const lowPart = 30000 * 0.17;
                    const highPart = (data.cataloguswaarde - 30000) * 0.22;
                    bijtellingPercentage = ((lowPart + highPart) / data.cataloguswaarde * 100);
                }
            }
            
            // Tax percentage
            let belastingPercentage = 35.82;
            if (data.brutoInkomen > 76817) {
                belastingPercentage = 49.50;
            } else if (data.brutoInkomen > 38441) {
                belastingPercentage = 37.48;
            }
            
            const jaarlijkseBijtelling = data.cataloguswaarde * (bijtellingPercentage / 100);
            const maandelijkseBijtelling = jaarlijkseBijtelling / 12;
            const nettoKosten = maandelijkseBijtelling * (belastingPercentage / 100);
            
            return {
                bijtellingBedrag: maandelijkseBijtelling,
                belastingPercentage: belastingPercentage,
                bijtellingPercentage: bijtellingPercentage,
                nettoKosten: nettoKosten
            };
        }

        // Display results
        function displayResults(privateCosts, businessCosts) {
            // Private breakdown
            document.getElementById('prive-total').textContent = `€ ${privateCosts.totaal.toFixed(2)}/mnd`;
            document.getElementById('prive-breakdown').innerHTML = `
                <li><span>Brandstof</span><span>€ ${privateCosts.brandstof.toFixed(2)}</span></li>
                <li><span>MRB</span><span>€ ${privateCosts.mrb.toFixed(2)}</span></li>
                <li><span>Verzekering</span><span>€ ${privateCosts.verzekering.toFixed(2)}</span></li>
                <li><span>Onderhoud</span><span>€ ${privateCosts.onderhoud.toFixed(2)}</span></li>
                <li><span>Afschrijving</span><span>€ ${privateCosts.afschrijving.toFixed(2)}</span></li>
                <li style="font-weight: bold; border-top: 2px solid #333; padding-top: 10px; margin-top: 10px;">
                    <span>Totaal per jaar</span><span>€ ${(privateCosts.totaal * 12).toFixed(2)}</span>
                </li>
            `;
            
            // Business breakdown
            document.getElementById('zakelijk-total').textContent = `€ ${businessCosts.nettoKosten.toFixed(2)}/mnd`;
            document.getElementById('zakelijk-breakdown').innerHTML = `
                <li><span>Cataloguswaarde</span><span>€ ${vehicleData.catalogusprijs?.toLocaleString('nl-NL') || '-'}</span></li>
                <li><span>Bijtelling %</span><span>${businessCosts.bijtellingPercentage.toFixed(1)}%</span></li>
                <li><span>Bijtelling bedrag</span><span>€ ${businessCosts.bijtellingBedrag.toFixed(2)}</span></li>
                <li><span>Belasting %</span><span>${businessCosts.belastingPercentage.toFixed(2)}%</span></li>
                <li><span>Netto kosten</span><span>€ ${businessCosts.nettoKosten.toFixed(2)}</span></li>
                <li style="font-weight: bold; border-top: 2px solid #333; padding-top: 10px; margin-top: 10px;">
                    <span>Totaal per jaar</span><span>€ ${(businessCosts.nettoKosten * 12).toFixed(2)}</span>
                </li>
            `;
            
            // Winner
            const savings = privateCosts.totaal - businessCosts.nettoKosten;
            const winner = savings > 0 ? 'zakelijk' : 'prive';
            
            if (winner === 'zakelijk') {
                document.getElementById('zakelijk-card').classList.add('winner');
                document.getElementById('prive-card').classList.remove('winner');
            } else {
                document.getElementById('prive-card').classList.add('winner');
                document.getElementById('zakelijk-card').classList.remove('winner');
            }
            
            document.getElementById('winner-announcement').innerHTML = `
                <h2 style="font-size: 28px; color: #10b981; margin-bottom: 10px;">
                    ${winner === 'zakelijk' ? '✅ Auto van de Zaak is voordeliger!' : '⚠️ Privé rijden is voordeliger!'}
                </h2>
                <p style="font-size: 20px; color: #333;">
                    ${winner === 'zakelijk' ? 
                        `U bespaart <strong>€ ${Math.abs(savings).toFixed(2)}</strong> per maand` :
                        `U betaalt <strong>€ ${Math.abs(savings).toFixed(2)}</strong> extra per maand`
                    }
                </p>
                <p style="font-size: 18px; color: #666; margin-top: 10px;">
                    Dat is een verschil van <strong>€ ${Math.abs(savings * 12).toFixed(2)}</strong> per jaar
                </p>
            `;
        }

        // Create charts
        function createCharts(privateCosts, businessCosts) {
            // Comparison chart
            const ctx1 = document.getElementById('comparison-chart').getContext('2d');
            charts.comparison = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: ['Privé Auto', 'Auto van de Zaak'],
                    datasets: [{
                        label: 'Kosten per maand',
                        data: [privateCosts.totaal, businessCosts.nettoKosten],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(16, 185, 129, 0.8)'
                        ],
                        borderColor: [
                            'rgb(239, 68, 68)',
                            'rgb(16, 185, 129)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Kostenvergelijking per Maand'
                        }
                    }
                }
            });
            
            // Breakdown chart
            const ctx2 = document.getElementById('breakdown-chart').getContext('2d');
            charts.breakdown = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Brandstof', 'MRB', 'Verzekering', 'Onderhoud', 'Afschrijving'],
                    datasets: [{
                        data: [
                            privateCosts.brandstof,
                            privateCosts.mrb,
                            privateCosts.verzekering,
                            privateCosts.onderhoud,
                            privateCosts.afschrijving
                        ],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(139, 92, 246, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Kostenverdeling Privé Auto'
                        }
                    }
                }
            });
            
            // Yearly comparison
            const ctx3 = document.getElementById('yearly-chart').getContext('2d');
            const years = [1, 2, 3, 4, 5];
            charts.yearly = new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: years.map(y => `Jaar ${y}`),
                    datasets: [
                        {
                            label: 'Privé Auto',
                            data: years.map(y => privateCosts.totaal * 12 * y),
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.1
                        },
                        {
                            label: 'Auto van de Zaak',
                            data: years.map(y => businessCosts.nettoKosten * 12 * y),
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: '5 Jaar Kostenvergelijking'
                        }
                    }
                }
            });
            
            // Savings over time
            const ctx4 = document.getElementById('savings-chart').getContext('2d');
            const savings = privateCosts.totaal - businessCosts.nettoKosten;
            charts.savings = new Chart(ctx4, {
                type: 'bar',
                data: {
                    labels: years.map(y => `Jaar ${y}`),
                    datasets: [{
                        label: savings > 0 ? 'Besparing met Zaak' : 'Extra kosten Zaak',
                        data: years.map(y => Math.abs(savings) * 12 * y),
                        backgroundColor: savings > 0 ? 
                            'rgba(16, 185, 129, 0.8)' : 
                            'rgba(239, 68, 68, 0.8)',
                        borderColor: savings > 0 ? 
                            'rgb(16, 185, 129)' : 
                            'rgb(239, 68, 68)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Cumulatieve Besparing/Kosten'
                        }
                    }
                }
            });
        }

        // Reset calculator
        function resetCalculator() {
            if (confirm('Weet u zeker dat u opnieuw wilt beginnen?')) {
                document.getElementById('simple-calculator').reset();
                vehicleData = {};
                calculatedData = {};
                
                // Clear displays
                document.querySelectorAll('[id^="display-"]').forEach(el => {
                    el.textContent = '-';
                });
                
                // Reset progress
                document.querySelectorAll('.progress-step').forEach(s => {
                    s.classList.remove('completed');
                });
                document.querySelector('[data-step="1"]').classList.add('active');
                
                // Show first section
                document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
                document.querySelector('[data-section="1"]').classList.add('active');
                
                // Destroy charts
                Object.values(charts).forEach(chart => {
                    if (chart) chart.destroy();
                });
                charts = {};
                
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Show/hide loading
        function showLoading(show) {
            document.getElementById('loading').classList.toggle('active', show);
        }

        // Initialize tooltips
        function initializeTooltips() {
            // Simple tooltip implementation
            // Can be enhanced with a library if needed
        }
    </script>
</body>
</html>