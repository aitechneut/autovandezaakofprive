/**
 * AutoKosten Calculator - Main JavaScript
 * Created for PianoManOnTour.nl/autovandezaakofprive
 * Version: 1.0.0
 * Author: Richard Surie
 */

// ===========================
// Global Variables & State
// ===========================
let calculatorState = {
    vehicleData: {},
    rdwData: {},
    bijtellingInfo: {},
    results: {},
    currentTab: 'vehicle',
    savedAutos: [],
    currentAutoId: null,
    compareMode: false
};

// Chart instances (global voor updates)
let charts = {
    kostenChart: null,
    verloopChart: null,
    verdelingZakelijkChart: null,
    verdelingPriveChart: null,
    multiAutoChart: null
};

// Auto kleuren voor grafieken
const autoColors = [
    '#8B5CF6', // Paars
    '#10B981', // Groen
    '#F59E0B', // Oranje
    '#EF4444', // Rood
    '#3B82F6', // Blauw
    '#EC4899', // Roze
    '#14B8A6', // Teal
    '#F97316'  // Donker oranje
];

// ===========================
// Initialization
// ===========================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚗 AutoKosten Calculator initialized');
    
    // Initialize event listeners
    initializeEventListeners();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Format kenteken input
    initializeKentekenFormatter();
    
    // Calculate initial values
    updateLiveCalculations();
    
    // Check for saved state
    loadSavedState();
});

// ===========================
// Event Listeners
// ===========================
function initializeEventListeners() {
    // Tab navigation
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });
    
    // Kenteken lookup
    const lookupBtn = document.getElementById('lookup-btn');
    if (lookupBtn) {
        lookupBtn.addEventListener('click', performKentekenLookup);
    }
    
    // Kenteken input - Enter key
    const kentekenInput = document.getElementById('kenteken');
    if (kentekenInput) {
        kentekenInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performKentekenLookup();
            }
        });
    }
    
    // Live calculations on input change
    const inputs = document.querySelectorAll('.form-input, .form-select');
    inputs.forEach(input => {
        input.addEventListener('input', updateLiveCalculations);
        input.addEventListener('change', updateLiveCalculations);
    });
    
    // Bruto inkomen - calculate tax percentage
    const brutoInkomen = document.getElementById('bruto_salaris');
    if (brutoInkomen) {
        brutoInkomen.addEventListener('input', calculateTaxPercentage);
    }
    
    // Bouwjaar change - check youngtimer
    const bouwjaar = document.getElementById('bouwjaar');
    if (bouwjaar) {
        bouwjaar.addEventListener('change', checkYoungtimerStatus);
    }
    
    // Brandstof change - update UI
    const brandstof = document.getElementById('brandstof');
    if (brandstof) {
        brandstof.addEventListener('change', updateBrandstofUI);
    }
    
    // Save state on input
    inputs.forEach(input => {
        input.addEventListener('change', saveState);
    });
}

// ===========================
// Tab Navigation
// ===========================
function switchTab(tabName) {
    // Update nav tabs
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Update sections
    document.querySelectorAll('.form-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(`${tabName}-section`).classList.add('active');
    
    // Update state
    calculatorState.currentTab = tabName;
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nextTab(tabName) {
    // Validate current tab before moving
    if (validateCurrentTab()) {
        switchTab(tabName);
    }
}

function previousTab(tabName) {
    switchTab(tabName);
}

// ===========================
// Kenteken Formatter & Lookup
// ===========================
function initializeKentekenFormatter() {
    const kentekenInput = document.getElementById('kenteken');
    if (!kentekenInput) return;
    
    kentekenInput.addEventListener('input', function(e) {
        let value = e.target.value.toUpperCase();
        // Remove all non-alphanumeric characters
        value = value.replace(/[^A-Z0-9]/g, '');
        
        // Format based on length (XX-XXX-X or XXX-XX-X patterns)
        if (value.length >= 6) {
            // Try to detect pattern
            if (/^\d/.test(value)) {
                // Starts with number: 99-XXX-9 pattern
                value = value.slice(0, 2) + '-' + value.slice(2, 5) + '-' + value.slice(5, 6);
            } else if (/^[A-Z]{2}\d{2}/.test(value)) {
                // XX-99-XX pattern
                value = value.slice(0, 2) + '-' + value.slice(2, 4) + '-' + value.slice(4, 6);
            } else {
                // Default XX-XXX-9 pattern
                value = value.slice(0, 2) + '-' + value.slice(2, 5) + '-' + value.slice(5, 6);
            }
        }
        
        e.target.value = value;
    });
}

async function performKentekenLookup() {
    const kentekenInput = document.getElementById('kenteken');
    const kenteken = kentekenInput.value.replace(/-/g, '').toUpperCase();
    
    if (kenteken.length < 6) {
        showNotification('Vul een geldig kenteken in', 'error');
        return;
    }
    
    const lookupBtn = document.getElementById('lookup-btn');
    lookupBtn.classList.add('loading');
    lookupBtn.textContent = 'Bezig...';
    
    try {
        // Show loading overlay
        showLoading(true);
        
        // Make API call to PHP backend
        const response = await fetch(`api/rdw-lookup.php?kenteken=${kenteken}`);
        const data = await response.json();
        
        if (data.success) {
            // Fill form with RDW data
            fillFormWithRDWData(data.data);
            showNotification('✅ Voertuiggegevens succesvol opgehaald!', 'success');
            
            // Store RDW data
            calculatorState.rdwData = data.data;
            
            // Update calculations
            updateLiveCalculations();
        } else {
            showNotification('❌ Kenteken niet gevonden of RDW API fout', 'error');
        }
    } catch (error) {
        console.error('Lookup error:', error);
        showNotification('❌ Er ging iets mis bij het ophalen van gegevens', 'error');
    } finally {
        showLoading(false);
        lookupBtn.classList.remove('loading');
        lookupBtn.textContent = 'Ophalen';
    }
}

function fillFormWithRDWData(data) {
    console.log('RDW Data received:', data); // Debug log
    
    // Map RDW fields to form fields
    const mappings = {
        'merk': data.merk || '',
        'model': data.model || data.handelsbenaming || '',
        'bouwjaar': data.bouwjaar || '',
        'gewicht': data.massa_ledig_voertuig || '',
        'cataloguswaarde': data.catalogusprijs || '',
        'co2_uitstoot': data.co2_uitstoot || ''
    };
    
    // Handle datum_eerste_toelating separately
    if (data.datum_eerste_toelating) {
        // Format from YYYYMMDD to YYYY-MM-DD
        const dateStr = data.datum_eerste_toelating.toString();
        if (dateStr.length === 8) {
            const formatted = `${dateStr.substr(0,4)}-${dateStr.substr(4,2)}-${dateStr.substr(6,2)}`;
            const dateField = document.getElementById('datum_eerste_toelating');
            if (dateField) {
                dateField.value = formatted;
            }
        }
    }
    
    // Handle brandstof separately - check if it's an array or string
    if (data.brandstof_type) {
        const brandstofField = document.getElementById('brandstof');
        if (brandstofField) {
            brandstofField.value = mapBrandstof(data.brandstof_type);
        }
    } else if (data.brandstof && Array.isArray(data.brandstof) && data.brandstof.length > 0) {
        const brandstofField = document.getElementById('brandstof');
        if (brandstofField) {
            brandstofField.value = mapBrandstof(data.brandstof[0]);
        }
    }
    
    // Fill the form
    for (const [field, value] of Object.entries(mappings)) {
        const element = document.getElementById(field);
        if (element && value) {
            element.value = value;
            // Trigger change event for calculations
            element.dispatchEvent(new Event('change'));
        }
    }
    
    // Check youngtimer status
    checkYoungtimerStatus();
    
    // Estimate additional values if not present
    estimateMissingValues();
}

function mapBrandstof(rdwBrandstof) {
    if (!rdwBrandstof) return '';
    
    const brandstofMap = {
        'Benzine': 'benzine',
        'Diesel': 'diesel',
        'Elektrisch': 'elektrisch',
        'Elektriciteit': 'elektrisch',
        'PHEV': 'plugin_hybride',
        'Waterstof': 'waterstof',
        'LPG': 'lpg',
        'CNG': 'cng',
        'Hybride': 'hybride',
        // Lowercase versions
        'benzine': 'benzine',
        'diesel': 'diesel',
        'elektrisch': 'elektrisch',
        'elektriciteit': 'elektrisch',
        'waterstof': 'waterstof',
        'lpg': 'lpg',
        'cng': 'cng',
        'hybride': 'hybride'
    };
    
    // First try direct mapping
    if (brandstofMap[rdwBrandstof]) {
        return brandstofMap[rdwBrandstof];
    }
    
    // Then try lowercase contains
    const lower = rdwBrandstof.toLowerCase();
    for (const [key, value] of Object.entries(brandstofMap)) {
        if (lower.includes(key.toLowerCase())) {
            return value;
        }
    }
    
    return 'benzine'; // Default
}

// ===========================
// Youngtimer & Bijtelling Logic
// ===========================
function checkYoungtimerStatus() {
    const bouwjaar = parseInt(document.getElementById('bouwjaar').value);
    const currentYear = new Date().getFullYear();
    
    if (!bouwjaar) return;
    
    const age = currentYear - bouwjaar;
    const statusElement = document.getElementById('vehicle-status');
    const statusDesc = document.getElementById('vehicle-status-desc');
    
    if (age >= 30) {
        statusElement.textContent = 'Oldtimer';
        statusDesc.textContent = '30+ jaar oud - Mogelijk vrijstelling';
        statusElement.style.color = '#00b09b';
    } else if (age >= 15) {
        statusElement.textContent = 'Youngtimer';
        statusDesc.textContent = `${age} jaar oud - 35% bijtelling over dagwaarde`;
        statusElement.style.color = '#fa709a';
        
        // Enable dagwaarde field
        document.getElementById('dagwaarde').required = true;
        showNotification('ℹ️ Youngtimer gedetecteerd! Vul de dagwaarde in voor correcte berekening.', 'info');
    } else {
        statusElement.textContent = 'Moderne Auto';
        statusDesc.textContent = `${age} jaar oud - Normale bijtelling`;
        statusElement.style.color = '#667eea';
        
        // Disable dagwaarde requirement
        document.getElementById('dagwaarde').required = false;
    }
    
    // Update bijtelling preview
    updateBijtellingPreview();
}

function updateBijtellingPreview() {
    const bouwjaar = parseInt(document.getElementById('bouwjaar').value);
    const brandstof = document.getElementById('brandstof').value;
    const cataloguswaarde = parseFloat(document.getElementById('cataloguswaarde').value) || 0;
    const dagwaarde = parseFloat(document.getElementById('dagwaarde').value) || 0;
    
    if (!bouwjaar || !brandstof) return;
    
    const currentYear = new Date().getFullYear();
    const age = currentYear - bouwjaar;
    
    const bijtellingElement = document.getElementById('bijtelling-preview');
    const bijtellingDesc = document.getElementById('bijtelling-desc');
    
    let percentage = 22; // Default
    let basis = cataloguswaarde;
    let basisType = 'Cataloguswaarde';
    
    // Youngtimer logic
    if (age >= 15 && age < 30) {
        percentage = 35;
        basis = dagwaarde || cataloguswaarde * 0.1; // Estimate 10% if no dagwaarde
        basisType = 'Dagwaarde';
        bijtellingDesc.textContent = 'Youngtimer tarief';
    }
    // Pre-2017 cars keep 25%
    else if (bouwjaar < 2017 && brandstof !== 'elektrisch') {
        percentage = 25;
        bijtellingDesc.textContent = 'Pre-2017 auto (25% behouden)';
    }
    // Electric cars
    else if (brandstof === 'elektrisch') {
        // Year-specific electric rates
        if (currentYear === 2025) {
            percentage = 17;
            if (cataloguswaarde > 30000) {
                bijtellingDesc.textContent = 'Elektrisch >€30k (22% boven drempel)';
                // Calculate weighted average
                const lowPart = 30000 * 0.17;
                const highPart = (cataloguswaarde - 30000) * 0.22;
                percentage = ((lowPart + highPart) / cataloguswaarde * 100).toFixed(1);
            } else {
                bijtellingDesc.textContent = 'Elektrisch ≤€30k';
            }
        } else if (currentYear === 2024) {
            percentage = 16;
            bijtellingDesc.textContent = 'Elektrisch 2024';
        }
    }
    // Hydrogen
    else if (brandstof === 'waterstof') {
        percentage = 17; // Same as electric in 2025
        bijtellingDesc.textContent = 'Waterstof voertuig';
    }
    // All other fuels
    else {
        percentage = 22;
        bijtellingDesc.textContent = 'Standaard tarief';
    }
    
    // Update UI
    bijtellingElement.textContent = `${percentage}%`;
    
    // Store in state
    calculatorState.bijtellingInfo = {
        percentage: percentage,
        basis: basis,
        basisType: basisType
    };
    
    // Update financial calculations
    updateFinancialCalculations();
}

// ===========================
// Tax & Financial Calculations
// ===========================
function calculateTaxPercentage() {
    const brutoInkomen = parseFloat(document.getElementById('bruto_salaris').value) || 0;
    const belastingElement = document.getElementById('belasting_percentage');
    
    // 2025 Dutch tax brackets
    let percentage = 0;
    
    if (brutoInkomen <= 38441) {
        percentage = 35.82;
    } else if (brutoInkomen <= 76817) {
        percentage = 37.48;
    } else {
        percentage = 49.50;
    }
    
    belastingElement.value = percentage;
    
    // Recalculate bijtelling impact
    updateFinancialCalculations();
}

function updateFinancialCalculations() {
    const bijtellingInfo = calculatorState.bijtellingInfo;
    const belastingPercentage = parseFloat(document.getElementById('belasting_percentage').value) || 0;
    
    if (!bijtellingInfo.basis || !bijtellingInfo.percentage) return;
    
    // Calculate monthly bijtelling
    const jaarlijkseBijtelling = (bijtellingInfo.basis * bijtellingInfo.percentage / 100);
    const maandelijkseBijtelling = jaarlijkseBijtelling / 12;
    
    // Calculate net cost (extra tax)
    const nettoKostenMaand = maandelijkseBijtelling * (belastingPercentage / 100);
    
    // Update UI if elements exist
    const bijtellingImpact = document.getElementById('bijtelling-impact');
    if (bijtellingImpact) {
        bijtellingImpact.textContent = `€ ${nettoKostenMaand.toFixed(2)}`;
    }
}

// ===========================
// Live Calculations
// ===========================
function updateLiveCalculations() {
    checkYoungtimerStatus();
    updateBijtellingPreview();
    estimateMRB();
    estimateInsurance();
    estimateMaintenance();
}

function estimateMRB() {
    const gewicht = parseFloat(document.getElementById('gewicht').value) || 0;
    const brandstof = document.getElementById('brandstof').value;
    const mrbElement = document.getElementById('mrb');
    
    if (!gewicht || !mrbElement || mrbElement.value) return; // Don't override user input
    
    let mrbPerKwartaal = 0;
    
    if (brandstof === 'elektrisch') {
        // Electric cars 0% until 2025, then 25% of normal
        mrbPerKwartaal = 0; // For 2025 would be: (gewicht / 100) * 2;
    } else if (brandstof === 'diesel') {
        mrbPerKwartaal = (gewicht / 100) * 12; // Higher for diesel
    } else {
        mrbPerKwartaal = (gewicht / 100) * 8; // Benzine estimate
    }
    
    mrbElement.value = Math.round(mrbPerKwartaal / 3); // Per month
}

function estimateInsurance() {
    const cataloguswaarde = parseFloat(document.getElementById('cataloguswaarde').value) || 0;
    const bouwjaar = parseInt(document.getElementById('bouwjaar').value);
    const verzekerElement = document.getElementById('verzekering');
    
    if (!cataloguswaarde || !verzekerElement || verzekerElement.value) return; // Don't override
    
    const age = new Date().getFullYear() - bouwjaar;
    let monthlyPremium = 50; // Base
    
    if (cataloguswaarde < 15000) {
        monthlyPremium = 40 + (age > 10 ? -10 : 0);
    } else if (cataloguswaarde < 30000) {
        monthlyPremium = 60 + (age > 10 ? -10 : 0);
    } else if (cataloguswaarde < 50000) {
        monthlyPremium = 90;
    } else {
        monthlyPremium = 120 + (cataloguswaarde - 50000) / 1000;
    }
    
    verzekerElement.value = Math.round(monthlyPremium);
}

function estimateMaintenance() {
    const kilometerstand = parseFloat(document.getElementById('kilometerstand').value) || 0;
    const bouwjaar = parseInt(document.getElementById('bouwjaar').value);
    const onderhoudElement = document.getElementById('onderhoud');
    
    if (!bouwjaar || !onderhoudElement || onderhoudElement.value) return; // Don't override
    
    const age = new Date().getFullYear() - bouwjaar;
    let monthlyMaintenance = 50; // Base
    
    // Age factor
    if (age > 10) monthlyMaintenance += 50;
    if (age > 15) monthlyMaintenance += 50;
    
    // Mileage factor
    if (kilometerstand > 150000) monthlyMaintenance += 30;
    if (kilometerstand > 250000) monthlyMaintenance += 50;
    
    onderhoudElement.value = Math.round(monthlyMaintenance);
}

function estimateMissingValues() {
    // Estimate catalog value if missing
    const catalogusElement = document.getElementById('cataloguswaarde');
    if (!catalogusElement.value) {
        const merk = document.getElementById('merk').value.toLowerCase();
        const bouwjaar = parseInt(document.getElementById('bouwjaar').value);
        
        // Very rough estimates
        let estimate = 25000; // Default
        
        if (merk.includes('mercedes') || merk.includes('bmw') || merk.includes('audi')) {
            estimate = 45000;
        } else if (merk.includes('volkswagen') || merk.includes('ford')) {
            estimate = 30000;
        } else if (merk.includes('tesla')) {
            estimate = 60000;
        }
        
        // Depreciation
        const age = new Date().getFullYear() - bouwjaar;
        catalogusElement.value = Math.round(estimate * Math.pow(0.85, Math.min(age, 10)));
    }
}

// ===========================
// Results Calculation
// ===========================
function calculateResults() {
    // Validate all required fields
    if (!validateAllTabs()) {
        showNotification('⚠️ Vul eerst alle verplichte velden in', 'warning');
        return;
    }
    
    // Gather all data
    const data = gatherFormData();
    
    // Calculate private costs
    const privateCosts = calculatePrivateCosts(data);
    
    // Calculate business costs
    const businessCosts = calculateBusinessCosts(data);
    
    // Generate results HTML
    const resultsHTML = generateResultsHTML(privateCosts, businessCosts, data);
    
    // Display results
    document.getElementById('results-container').innerHTML = resultsHTML;
    
    // Switch to results tab
    switchTab('results');
    
    // Store results
    calculatorState.results = {
        private: privateCosts,
        business: businessCosts,
        data: data,
        timestamp: new Date().toISOString()
    };
    
    // Save state
    saveState();
    
    // Show success notification
    showNotification('✅ Berekening voltooid!', 'success');
}

// Add all other functions from the original file...
// [Continue with the rest of the JavaScript code]

// ===========================
// UI Helpers
// ===========================
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? 'var(--success-gradient)' : 
                     type === 'error' ? 'var(--warning-gradient)' : 
                     'var(--primary-gradient)'};
        color: white;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function showLoading(show = true) {
    // Create loading overlay if it doesn't exist
    let overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        overlay.innerHTML = '<div style="color: white; font-size: 24px;">Loading...</div>';
        document.body.appendChild(overlay);
    }
    
    overlay.style.display = show ? 'flex' : 'none';
}

// Add the rest of the missing functions...
function gatherFormData() {
    const fields = [
        'kenteken', 'merk', 'model', 'bouwjaar', 'datum_eerste_toelating',
        'brandstof', 'gewicht', 'cataloguswaarde', 'dagwaarde', 'co2_uitstoot',
        'kilometerstand', 'km_per_maand', 'verbruik',
        'brandstofprijs', 'mrb', 'verzekering',
        'onderhoud', 'aankoopprijs',
        'afschrijving_jaren', 'bruto_salaris',
        'belasting_percentage'
    ];
    
    const data = {};
    fields.forEach(field => {
        const element = document.getElementById(field);
        if (element) {
            data[field] = element.type === 'checkbox' ? element.checked : element.value;
        }
    });
    
    // Add calculated bijtelling info
    data.bijtellingInfo = calculatorState.bijtellingInfo;
    
    return data;
}

function calculatePrivateCosts(data) {
    const kmPerMaand = parseFloat(data.km_per_maand) || 0;
    const verbruik = parseFloat(data.verbruik) || 7;
    const brandstofprijs = parseFloat(data.brandstofprijs) || 2.10;
    const mrb = parseFloat(data.mrb) || 0;
    const verzekering = parseFloat(data.verzekering) || 0;
    const onderhoud = parseFloat(data.onderhoud) || 0;
    const aankoopprijs = parseFloat(data.aankoopprijs) || parseFloat(data.cataloguswaarde) || 0;
    const jaren = parseFloat(data.afschrijving_jaren) || 5;
    
    // Calculate monthly costs
    const brandstofKosten = (kmPerMaand / 100) * verbruik * brandstofprijs;
    const afschrijving = aankoopprijs / (jaren * 12);
    
    const totaalPerMaand = brandstofKosten + mrb + verzekering + onderhoud + afschrijving;
    
    return {
        brandstof: brandstofKosten,
        mrb: mrb,
        verzekering: verzekering,
        onderhoud: onderhoud,
        afschrijving: afschrijving,
        totaal: totaalPerMaand,
        jaarlijks: totaalPerMaand * 12
    };
}

function calculateBusinessCosts(data) {
    const bijtellingBasis = data.bijtellingInfo.basis || 0;
    const bijtellingPercentage = data.bijtellingInfo.percentage || 22;
    const belastingPercentage = parseFloat(data.belasting_percentage) || 37;
    
    // Annual bijtelling amount
    const jaarlijkseBijtelling = bijtellingBasis * (bijtellingPercentage / 100);
    const maandelijkseBijtelling = jaarlijkseBijtelling / 12;
    
    // Actual tax cost
    const belastingKosten = maandelijkseBijtelling * (belastingPercentage / 100);
    
    return {
        bijtellingBedrag: maandelijkseBijtelling,
        belastingPercentage: belastingPercentage,
        nettoKosten: belastingKosten,
        jaarlijks: belastingKosten * 12
    };
}

function generateResultsHTML(privateCosts, businessCosts, data) {
    const savings = privateCosts.totaal - businessCosts.nettoKosten;
    const winner = savings > 0 ? 'business' : 'private';
    
    return `
        <div class="results-grid">
            <!-- Private Car Costs -->
            <div class="result-card ${winner === 'private' ? 'winner' : ''}">
                <h3 class="result-type">🚗 Privé Auto</h3>
                <div class="result-amount">€${privateCosts.totaal.toFixed(2)}/mnd</div>
                <ul class="result-breakdown">
                    <li>
                        <span>Brandstof:</span>
                        <span>€${privateCosts.brandstof.toFixed(2)}</span>
                    </li>
                    <li>
                        <span>MRB:</span>
                        <span>€${privateCosts.mrb.toFixed(2)}</span>
                    </li>
                    <li>
                        <span>Verzekering:</span>
                        <span>€${privateCosts.verzekering.toFixed(2)}</span>
                    </li>
                    <li>
                        <span>Onderhoud:</span>
                        <span>€${privateCosts.onderhoud.toFixed(2)}</span>
                    </li>
                    <li>
                        <span>Afschrijving:</span>
                        <span>€${privateCosts.afschrijving.toFixed(2)}</span>
                    </li>
                </ul>
                <div style="border-top: 1px solid var(--medium-gray); padding-top: 1rem; margin-top: 1rem;">
                    <strong>Jaarlijks: €${privateCosts.jaarlijks.toFixed(2)}</strong>
                </div>
            </div>

            <!-- Business Car Costs -->
            <div class="result-card ${winner === 'business' ? 'winner' : ''}">
                <h3 class="result-type">💼 Auto van de Zaak</h3>
                <div class="result-amount">€${businessCosts.nettoKosten.toFixed(2)}/mnd</div>
                <ul class="result-breakdown">
                    <li>
                        <span>Bijtelling basis:</span>
                        <span>€${data.bijtellingInfo.basis.toFixed(2)}</span>
                    </li>
                    <li>
                        <span>Bijtelling %:</span>
                        <span>${data.bijtellingInfo.percentage}%</span>
                    </li>
                    <li>
                        <span>Bijtelling bedrag:</span>
                        <span>€${businessCosts.bijtellingBedrag.toFixed(2)}</span>
                    </li>
                    <li>
                        <span>Belasting tarief:</span>
                        <span>${businessCosts.belastingPercentage}%</span>
                    </li>
                    <li>
                        <span>Netto kosten:</span>
                        <span>€${businessCosts.nettoKosten.toFixed(2)}</span>
                    </li>
                </ul>
                <div style="border-top: 1px solid var(--medium-gray); padding-top: 1rem; margin-top: 1rem;">
                    <strong>Jaarlijks: €${businessCosts.jaarlijks.toFixed(2)}</strong>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="info-card" style="margin-top: 2rem; background: ${winner === 'business' ? 'var(--success-gradient)' : 'var(--warning-gradient)'}; color: white;">
            <h3 style="font-size: 1.5rem; margin-bottom: 1rem;">
                ${winner === 'business' ? '✅ Auto van de Zaak is voordeliger!' : '⚠️ Privé rijden is voordeliger!'}
            </h3>
            <p style="font-size: 1.125rem;">
                ${winner === 'business' ? 
                    `U bespaart <strong>€${Math.abs(savings).toFixed(2)}</strong> per maand met een auto van de zaak.` :
                    `U betaalt <strong>€${Math.abs(savings).toFixed(2)}</strong> extra per maand met een auto van de zaak.`
                }
            </p>
            <p style="margin-top: 0.5rem;">
                Dat is een verschil van <strong>€${Math.abs(savings * 12).toFixed(2)}</strong> per jaar.
            </p>
        </div>
    `;
}

// Validation functions
function validateCurrentTab() {
    const currentSection = document.querySelector('.form-section.active');
    const requiredFields = currentSection.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value) {
            field.classList.add('error');
            isValid = false;
            
            // Remove error class after animation
            setTimeout(() => field.classList.remove('error'), 1000);
        }
    });
    
    if (!isValid) {
        showNotification('⚠️ Vul alle verplichte velden in', 'warning');
    }
    
    return isValid;
}

function validateAllTabs() {
    const requiredFields = document.querySelectorAll('[required]');
    let isValid = true;
    let firstError = null;
    
    requiredFields.forEach(field => {
        if (!field.value) {
            field.classList.add('error');
            isValid = false;
            if (!firstError) firstError = field;
        }
    });
    
    if (!isValid && firstError) {
        // Switch to tab with first error
        const section = firstError.closest('.form-section');
        const tabName = section.id.replace('-section', '');
        switchTab(tabName);
        firstError.focus();
    }
    
    return isValid;
}

// Form validation
function initializeFormValidation() {
    const form = document.getElementById('calculator-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        calculateResults();
    });
}

// State management
function saveState() {
    const data = gatherFormData();
    localStorage.setItem('autokosten_calculator_state', JSON.stringify({
        data: data,
        state: calculatorState,
        timestamp: new Date().toISOString()
    }));
}

function loadSavedState() {
    const saved = localStorage.getItem('autokosten_calculator_state');
    if (!saved) return;
    
    try {
        const parsed = JSON.parse(saved);
        
        // Check if data is less than 24 hours old
        const savedTime = new Date(parsed.timestamp);
        const now = new Date();
        const hoursDiff = (now - savedTime) / (1000 * 60 * 60);
        
        if (hoursDiff < 24) {
            // Restore form data
            Object.entries(parsed.data).forEach(([key, value]) => {
                const element = document.getElementById(key);
                if (element && key !== 'bijtellingInfo') {
                    if (element.type === 'checkbox') {
                        element.checked = value;
                    } else {
                        element.value = value;
                    }
                }
            });
            
            // Restore state
            if (parsed.state) {
                calculatorState = parsed.state;
            }
            
            // Update calculations
            updateLiveCalculations();
            
            showNotification('ℹ️ Vorige sessie hersteld', 'info');
        }
    } catch (error) {
        console.error('Error loading saved state:', error);
    }
}

// UI functions
function updateBrandstofUI() {
    const brandstof = document.getElementById('brandstof').value;
    const brandstofprijsLabel = document.querySelector('[for="brandstofprijs"]');
    
    if (brandstof === 'elektrisch') {
        if (brandstofprijsLabel) {
            brandstofprijsLabel.innerHTML = 'Stroomprijs (€/kWh) <span class="tooltip" data-tooltip="Per kWh">ⓘ</span>';
        }
        document.getElementById('brandstofprijs').value = '0.40';
    } else {
        if (brandstofprijsLabel) {
            brandstofprijsLabel.innerHTML = 'Brandstofprijs (€) <span class="tooltip" data-tooltip="Per liter">ⓘ</span>';
        }
        document.getElementById('brandstofprijs').value = brandstof === 'diesel' ? '1.85' : '2.10';
    }
}

// Utility functions
function exportResults() {
    if (!calculatorState.results.data) {
        showNotification('⚠️ Maak eerst een berekening', 'warning');
        return;
    }
    
    const exportData = {
        ...calculatorState.results,
        meta: {
            version: '1.0.0',
            exported: new Date().toISOString(),
            url: window.location.href
        }
    };
    
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
    
    const exportName = `autokosten_${calculatorState.results.data.kenteken}_${new Date().toISOString().split('T')[0]}.json`;
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportName);
    linkElement.click();
    
    showNotification('✅ Resultaten geëxporteerd!', 'success');
}

function resetForm() {
    if (confirm('Weet u zeker dat u alle velden wilt resetten?')) {
        document.getElementById('calculator-form').reset();
        calculatorState = {
            vehicleData: {},
            rdwData: {},
            bijtellingInfo: {},
            results: {},
            currentTab: 'vehicle'
        };
        localStorage.removeItem('autokosten_calculator_state');
        switchTab('vehicle');
        showNotification('🔄 Calculator gereset', 'info');
    }
}

// Auto manager functions (stubs for now)
function openAutoManager() {
    showNotification('Auto Manager komt binnenkort!', 'info');
}

function saveCurrentAuto() {
    showNotification('Auto opslaan komt binnenkort!', 'info');
}

function closeAutoManager() {
    // Stub
}

// Animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .error {
        border-color: #ef4444 !important;
        animation: shake 0.5s;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);

console.log('✅ AutoKosten Calculator loaded successfully!');
