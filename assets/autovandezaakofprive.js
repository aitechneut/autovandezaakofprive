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
    const brutoInkomen = document.getElementById('bruto_inkomen');
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
    const basisElement = document.getElementById('bijtelling-basis');
    const basisDesc = document.getElementById('basis-desc');
    
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
    basisElement.textContent = `€${basis.toLocaleString('nl-NL')}`;
    basisDesc.textContent = basisType;
    
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
    const brutoInkomen = parseFloat(document.getElementById('bruto_inkomen').value) || 0;
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
    
    // Update displays
    document.getElementById('effectief-tarief').textContent = `${percentage}%`;
    
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
    
    // Update UI
    document.getElementById('maandelijkse-bijtelling').textContent = 
        `€${maandelijkseBijtelling.toFixed(2)}`;
    document.getElementById('netto-bijtelling').textContent = 
        `€${nettoKostenMaand.toFixed(2)}`;
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
    const mrbElement = document.getElementById('mrb_per_maand');
    
    if (!gewicht || mrbElement.value) return; // Don't override user input
    
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
    const verzekerElement = document.getElementById('verzekering_per_maand');
    
    if (!cataloguswaarde || verzekerElement.value) return; // Don't override
    
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
    const onderhoudElement = document.getElementById('onderhoud_per_maand');
    
    if (!bouwjaar || onderhoudElement.value) return; // Don't override
    
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

function gatherFormData() {
    const fields = [
        'kenteken', 'merk', 'model', 'bouwjaar', 'datum_eerste_toelating',
        'brandstof', 'gewicht', 'cataloguswaarde', 'dagwaarde', 'co2_uitstoot',
        'kilometerstand', 'km_per_maand', 'verbruik', 'verbruik_eenheid',
        'brandstofprijs', 'mrb_per_maand', 'verzekering_per_maand',
        'onderhoud_per_maand', 'aankoopprijs', 'verwachte_restwaarde',
        'afschrijving_jaren', 'gebruiker_type', 'bruto_inkomen',
        'belasting_percentage', 'zakelijk_gebruik'
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
    const verbruik = parseFloat(data.verbruik) || 0;
    const brandstofprijs = parseFloat(data.brandstofprijs) || 0;
    const mrb = parseFloat(data.mrb_per_maand) || 0;
    const verzekering = parseFloat(data.verzekering_per_maand) || 0;
    const onderhoud = parseFloat(data.onderhoud_per_maand) || 0;
    const aankoopprijs = parseFloat(data.aankoopprijs) || 0;
    const restwaarde = parseFloat(data.verwachte_restwaarde) || 0;
    const jaren = parseFloat(data.afschrijving_jaren) || 5;
    
    // Convert consumption to L/100km if needed
    let verbruikPer100km = verbruik;
    if (data.verbruik_eenheid === 'km_l') {
        verbruikPer100km = 100 / verbruik;
    }
    
    // Calculate monthly costs
    const brandstofKosten = (kmPerMaand / 100) * verbruikPer100km * brandstofprijs;
    const afschrijving = (aankoopprijs - restwaarde) / (jaren * 12);
    
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
    const belastingPercentage = parseFloat(data.belasting_percentage) || 0;
    
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

        <!-- Additional Info -->
        <div style="margin-top: 2rem; padding: 1rem; background: var(--light-gray); border-radius: var(--radius-md);">
            <h4>📊 Berekening Details</h4>
            <p style="margin-top: 0.5rem; font-size: 0.875rem;">
                <strong>Voertuig:</strong> ${data.merk} ${data.model} (${data.bouwjaar})<br>
                <strong>Kenteken:</strong> ${data.kenteken}<br>
                <strong>Type:</strong> ${data.bijtellingInfo.basisType === 'Dagwaarde' ? 'Youngtimer' : 'Moderne auto'}<br>
                <strong>Kilometers/maand:</strong> ${data.km_per_maand}<br>
                <strong>Berekend op:</strong> ${new Date().toLocaleString('nl-NL')}
            </p>
        </div>
    `;
}

// ===========================
// Validation
// ===========================
function initializeFormValidation() {
    const form = document.getElementById('calculator-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        calculateResults();
    });
}

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
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

function updateBrandstofUI() {
    const brandstof = document.getElementById('brandstof').value;
    const verbruikEenheid = document.getElementById('verbruik_eenheid');
    const brandstofprijsLabel = document.querySelector('[for="brandstofprijs"]');
    
    if (brandstof === 'elektrisch') {
        verbruikEenheid.value = 'kwh_100km';
        brandstofprijsLabel.innerHTML = 'Stroomprijs (€/kWh) <span class="tooltip" data-tooltip="Per kWh">ⓘ</span>';
        document.getElementById('brandstofprijs').value = '0.40';
    } else {
        verbruikEenheid.value = 'l_100km';
        brandstofprijsLabel.innerHTML = 'Brandstofprijs (€) <span class="tooltip" data-tooltip="Per liter">ⓘ</span>';
        document.getElementById('brandstofprijs').value = brandstof === 'diesel' ? '1.85' : '2.10';
    }
}

// ===========================
// State Management
// ===========================
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
            calculatorState = parsed.state;
            
            // Update calculations
            updateLiveCalculations();
            
            showNotification('ℹ️ Vorige sessie hersteld', 'info');
        }
    } catch (error) {
        console.error('Error loading saved state:', error);
    }
}

// ===========================
// Export & Utility Functions
// ===========================
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

function resetCalculator() {
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

// ===========================
// Animation Styles (add to page)
// ===========================
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
`;
document.head.appendChild(style);

// ===========================
// Debug Mode (for development)
// ===========================
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    console.log('🔧 Debug mode enabled');
    window.calculatorDebug = {
        state: calculatorState,
        fillTestData: function() {
            document.getElementById('kenteken').value = '99-ZZH-3';
            document.getElementById('merk').value = 'Tesla';
            document.getElementById('model').value = 'Model 3';
            document.getElementById('bouwjaar').value = '2022';
            document.getElementById('brandstof').value = 'elektrisch';
            document.getElementById('cataloguswaarde').value = '45000';
            document.getElementById('km_per_maand').value = '1500';
            document.getElementById('bruto_inkomen').value = '50000';
            updateLiveCalculations();
            calculateTaxPercentage();
        }
    };
}



// ===========================
// Form Data Collection
// ===========================
function collectFormData() {
    const data = {
        // Vehicle info
        kenteken: document.getElementById('kenteken')?.value || '',
        merk: document.getElementById('merk')?.value || '',
        model: document.getElementById('model')?.value || '',
        bouwjaar: parseInt(document.getElementById('bouwjaar')?.value) || 0,
        brandstof: document.getElementById('brandstof')?.value || 'benzine',
        cataloguswaarde: parseFloat(document.getElementById('cataloguswaarde')?.value) || 0,
        dagwaarde: parseFloat(document.getElementById('dagwaarde')?.value) || 0,
        
        // Usage
        km_per_maand: parseFloat(document.getElementById('km_per_maand')?.value) || 0,
        km_per_jaar: parseFloat(document.getElementById('km_per_jaar')?.value) || 0,
        
        // Costs
        brandstofprijs: parseFloat(document.getElementById('brandstofprijs')?.value) || 2.10,
        verbruik: parseFloat(document.getElementById('verbruik')?.value) || 7.0,
        verzekering: parseFloat(document.getElementById('verzekering')?.value) || 0,
        onderhoud: parseFloat(document.getElementById('onderhoud')?.value) || 0,
        mrb: parseFloat(document.getElementById('mrb')?.value) || 0,
        
        // Financial
        bruto_inkomen: parseFloat(document.getElementById('bruto_inkomen')?.value) || 0,
        belasting_percentage: parseFloat(document.getElementById('belasting_percentage')?.value) || 0,
        bijtelling_percentage: parseFloat(document.getElementById('bijtelling_percentage')?.value) || 22,
        aankoopprijs: parseFloat(document.getElementById('aankoopprijs')?.value) || 0,
        restwaarde: parseFloat(document.getElementById('restwaarde')?.value) || 0,
        afschrijving_jaren: parseInt(document.getElementById('afschrijving_jaren')?.value) || 5
    };
    
    // Auto naam voor opslaan
    data.autoNaam = document.getElementById('auto-naam')?.value || 
                    `${data.merk} ${data.model}`.trim() || 
                    data.kenteken || 
                    'Naamloze Auto';
    
    return data;
}

// ===========================
// Chart Update Functions
// ===========================
function updateCharts() {
    if (!calculatorState.results || !calculatorState.results.maandKosten) {
        console.log('Geen resultaten om grafieken te updaten');
        return;
    }
    
    const results = calculatorState.results;
    
    // Update kostenvergelijking chart
    if (charts.kostenChart) {
        charts.kostenChart.data.datasets[0].data = [
            results.maandKosten.zakelijk,
            results.maandKosten.prive
        ];
        charts.kostenChart.update();
    }
    
    // Update 5-jaar verloop chart
    if (charts.verloopChart) {
        const jaren = [1, 2, 3, 4, 5];
        charts.verloopChart.data.datasets[0].data = jaren.map(j => results.maandKosten.zakelijk * 12 * j);
        charts.verloopChart.data.datasets[1].data = jaren.map(j => results.maandKosten.prive * 12 * j);
        charts.verloopChart.update();
    }
    
    // Update verdeling zakelijk chart
    if (charts.verdelingZakelijkChart && results.breakdown) {
        charts.verdelingZakelijkChart.data.datasets[0].data = [
            results.breakdown.zakelijk.bijtelling || 0,
            results.breakdown.zakelijk.brandstof || 0,
            results.breakdown.zakelijk.onderhoud || 0
        ];
        charts.verdelingZakelijkChart.update();
    }
    
    // Update verdeling privé chart
    if (charts.verdelingPriveChart && results.breakdown) {
        charts.verdelingPriveChart.data.datasets[0].data = [
            results.breakdown.prive.afschrijving || 0,
            results.breakdown.prive.brandstof || 0,
            results.breakdown.prive.verzekering || 0,
            results.breakdown.prive.onderhoud || 0,
            results.breakdown.prive.mrb || 0
        ];
        charts.verdelingPriveChart.update();
    }
    
    // Update besparing display
    const besparingElement = document.getElementById('besparing-display');
    if (besparingElement) {
        const besparing = results.maandKosten.prive - results.maandKosten.zakelijk;
        const isZakelijkVoordeliger = besparing > 0;
        
        besparingElement.innerHTML = `
            <div class="besparing-header">${isZakelijkVoordeliger ? 'Zakelijk Voordeliger' : 'Privé Voordeliger'}</div>
            <div class="besparing-amount">€ ${Math.abs(besparing).toFixed(2)}</div>
            <div class="besparing-period">per maand</div>
            <div class="besparing-year">€ ${Math.abs(besparing * 12).toFixed(2)} per jaar</div>
        `;
        
        besparingElement.className = isZakelijkVoordeliger ? 'besparing-block voordelig' : 'besparing-block nadelig';
    }
}

// ===========================
// Auto Manager Functions
// ===========================
function saveAuto() {
    const formData = collectFormData();
    
    if (!formData.autoNaam || formData.autoNaam === 'Naamloze Auto') {
        showNotification('⚠️ Geef eerst een naam aan de auto', 'warning');
        document.getElementById('auto-naam')?.focus();
        return;
    }
    
    // Generate unique ID
    const autoId = Date.now().toString();
    
    // Create auto object
    const auto = {
        id: autoId,
        naam: formData.autoNaam,
        data: formData,
        results: calculatorState.results,
        savedAt: new Date().toISOString(),
        color: autoColors[calculatorState.savedAutos.length % autoColors.length]
    };
    
    // Add to saved autos (max 5)
    if (calculatorState.savedAutos.length >= 5) {
        showNotification('⚠️ Maximum 5 auto\'s. Verwijder eerst een auto.', 'warning');
        openAutoManager();
        return;
    }
    
    calculatorState.savedAutos.push(auto);
    calculatorState.currentAutoId = autoId;
    
    // Save to localStorage
    localStorage.setItem('savedAutos', JSON.stringify(calculatorState.savedAutos));
    
    showNotification(`✅ ${formData.autoNaam} opgeslagen!`, 'success');
    
    // Update UI
    updateAutoManagerList();
}

function loadAuto(autoId) {
    const auto = calculatorState.savedAutos.find(a => a.id === autoId);
    
    if (!auto) {
        showNotification('❌ Auto niet gevonden', 'error');
        return;
    }
    
    // Load data into form
    const data = auto.data;
    Object.keys(data).forEach(key => {
        const element = document.getElementById(key);
        if (element) {
            element.value = data[key];
        }
    });
    
    // Update state
    calculatorState.currentAutoId = autoId;
    calculatorState.results = auto.results;
    
    // Update displays
    updateLiveCalculations();
    displayResults();
    updateCharts();
    
    // Close modal
    closeAutoManager();
    
    showNotification(`✅ ${auto.naam} geladen`, 'success');
}

function deleteAuto(autoId) {
    const auto = calculatorState.savedAutos.find(a => a.id === autoId);
    
    if (!auto) return;
    
    if (confirm(`Weet u zeker dat u "${auto.naam}" wilt verwijderen?`)) {
        // Remove from array
        calculatorState.savedAutos = calculatorState.savedAutos.filter(a => a.id !== autoId);
        
        // Update localStorage
        localStorage.setItem('savedAutos', JSON.stringify(calculatorState.savedAutos));
        
        // Clear current if deleted
        if (calculatorState.currentAutoId === autoId) {
            calculatorState.currentAutoId = null;
        }
        
        // Update UI
        updateAutoManagerList();
        
        showNotification(`✅ ${auto.naam} verwijderd`, 'info');
    }
}

function openAutoManager() {
    const modal = document.getElementById('auto-manager-modal');
    if (modal) {
        modal.style.display = 'flex';
        updateAutoManagerList();
    }
}

function closeAutoManager() {
    const modal = document.getElementById('auto-manager-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function updateAutoManagerList() {
    const listContainer = document.getElementById('saved-autos-list');
    if (!listContainer) return;
    
    if (calculatorState.savedAutos.length === 0) {
        listContainer.innerHTML = '<p class="no-autos">Nog geen auto\'s opgeslagen</p>';
        return;
    }
    
    listContainer.innerHTML = calculatorState.savedAutos.map(auto => `
        <div class="saved-auto-item" data-id="${auto.id}">
            <div class="auto-color" style="background: ${auto.color}"></div>
            <div class="auto-info">
                <div class="auto-name">${auto.naam}</div>
                <div class="auto-details">
                    ${auto.data.kenteken || 'Geen kenteken'} • 
                    ${auto.data.bouwjaar || '?'} • 
                    €${(auto.results?.maandKosten?.zakelijk || 0).toFixed(0)}/mnd
                </div>
            </div>
            <div class="auto-actions">
                <label class="compare-check">
                    <input type="checkbox" 
                           data-auto-id="${auto.id}" 
                           onchange="toggleAutoCompare('${auto.id}')"
                           ${auto.compare ? 'checked' : ''}>
                    <span>Vergelijk</span>
                </label>
                <button onclick="loadAuto('${auto.id}')" class="btn-load">Laden</button>
                <button onclick="deleteAuto('${auto.id}')" class="btn-delete">🗑</button>
            </div>
        </div>
    `).join('');
}

function toggleAutoCompare(autoId) {
    const auto = calculatorState.savedAutos.find(a => a.id === autoId);
    if (auto) {
        auto.compare = !auto.compare;
        localStorage.setItem('savedAutos', JSON.stringify(calculatorState.savedAutos));
        
        // Update comparison chart if on analyse tab
        if (calculatorState.currentTab === 'analyse') {
            updateComparisonChart();
        }
    }
}

function compareAutos() {
    const selectedAutos = calculatorState.savedAutos.filter(a => a.compare);
    
    if (selectedAutos.length < 2) {
        showNotification('⚠️ Selecteer minimaal 2 auto\'s om te vergelijken', 'warning');
        openAutoManager();
        return;
    }
    
    // Switch to analyse tab
    switchTab('analyse');
    
    // Create comparison chart
    createMultiAutoChart(selectedAutos);
    
    showNotification(`📊 ${selectedAutos.length} auto\'s worden vergeleken`, 'info');
}

// ===========================
// Multi-Auto Comparison Charts
// ===========================
function createMultiAutoChart(selectedAutos) {
    const chartContainer = document.getElementById('multi-auto-chart');
    if (!chartContainer) return;
    
    // Destroy existing chart
    if (charts.multiAutoChart) {
        charts.multiAutoChart.destroy();
    }
    
    // Prepare data
    const labels = selectedAutos.map(a => a.naam);
    const zakelijkData = selectedAutos.map(a => a.results?.maandKosten?.zakelijk || 0);
    const priveData = selectedAutos.map(a => a.results?.maandKosten?.prive || 0);
    const colors = selectedAutos.map(a => a.color);
    
    // Create chart
    const ctx = chartContainer.getContext('2d');
    charts.multiAutoChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Zakelijk (per maand)',
                    data: zakelijkData,
                    backgroundColor: colors.map(c => c + '80'), // Add transparency
                    borderColor: colors,
                    borderWidth: 2
                },
                {
                    label: 'Privé (per maand)',
                    data: priveData,
                    backgroundColor: colors.map(c => c + '40'), // More transparency
                    borderColor: colors,
                    borderWidth: 2,
                    borderDash: [5, 5]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Auto Vergelijking - Maandelijkse Kosten',
                    font: { size: 16, weight: 'bold' }
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': € ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '€ ' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });
    
    // Update comparison summary
    updateComparisonSummary(selectedAutos);
}

function updateComparisonChart() {
    const selectedAutos = calculatorState.savedAutos.filter(a => a.compare);
    
    if (selectedAutos.length >= 2) {
        createMultiAutoChart(selectedAutos);
    } else if (charts.multiAutoChart) {
        charts.multiAutoChart.destroy();
        charts.multiAutoChart = null;
        
        const chartContainer = document.getElementById('multi-auto-chart');
        if (chartContainer) {
            const ctx = chartContainer.getContext('2d');
            ctx.clearRect(0, 0, chartContainer.width, chartContainer.height);
        }
    }
}

function updateComparisonSummary(selectedAutos) {
    const summaryContainer = document.getElementById('comparison-summary');
    if (!summaryContainer) return;
    
    // Find best options
    const zakelijkBest = selectedAutos.reduce((min, auto) => 
        (auto.results?.maandKosten?.zakelijk || Infinity) < (min.results?.maandKosten?.zakelijk || Infinity) ? auto : min
    );
    
    const priveBest = selectedAutos.reduce((min, auto) => 
        (auto.results?.maandKosten?.prive || Infinity) < (min.results?.maandKosten?.prive || Infinity) ? auto : min
    );
    
    summaryContainer.innerHTML = `
        <div class="comparison-summary">
            <h3>Vergelijking Resultaten</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Beste Zakelijk:</div>
                    <div class="summary-value">
                        <span class="auto-color" style="background: ${zakelijkBest.color}"></span>
                        ${zakelijkBest.naam} - €${(zakelijkBest.results?.maandKosten?.zakelijk || 0).toFixed(2)}/mnd
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Beste Privé:</div>
                    <div class="summary-value">
                        <span class="auto-color" style="background: ${priveBest.color}"></span>
                        ${priveBest.naam} - €${(priveBest.results?.maandKosten?.prive || 0).toFixed(2)}/mnd
                    </div>
                </div>
                <div class="summary-item full-width">
                    <div class="summary-label">Grootste Besparing:</div>
                    <div class="summary-value">
                        ${selectedAutos.map(auto => {
                            const diff = (auto.results?.maandKosten?.prive || 0) - (auto.results?.maandKosten?.zakelijk || 0);
                            const isZakelijkBeter = diff > 0;
                            return `
                                <div class="besparing-row">
                                    <span class="auto-color" style="background: ${auto.color}"></span>
                                    ${auto.naam}: 
                                    <span class="${isZakelijkBeter ? 'text-green' : 'text-red'}">
                                        ${isZakelijkBeter ? 'Zakelijk' : 'Privé'} €${Math.abs(diff).toFixed(2)} voordeliger
                                    </span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ===========================
// Initialize Charts on Tab Switch
// ===========================
function initializeAnalyseTab() {
    // Only initialize if we have results
    if (!calculatorState.results || !calculatorState.results.maandKosten) {
        console.log('Geen resultaten beschikbaar voor grafieken');
        return;
    }
    
    // Initialize all charts
    setTimeout(() => {
        initializeCharts();
        updateCharts();
        
        // Check for auto comparison
        const selectedAutos = calculatorState.savedAutos.filter(a => a.compare);
        if (selectedAutos.length >= 2) {
            createMultiAutoChart(selectedAutos);
        }
    }, 100); // Small delay to ensure DOM is ready
}

// ===========================
// Load Saved State on Page Load
// ===========================
function loadSavedState() {
    // Load saved autos from localStorage
    const savedAutosJson = localStorage.getItem('savedAutos');
    if (savedAutosJson) {
        try {
            calculatorState.savedAutos = JSON.parse(savedAutosJson);
            console.log(`📦 ${calculatorState.savedAutos.length} auto's geladen uit opslag`);
        } catch (e) {
            console.error('Error loading saved autos:', e);
            calculatorState.savedAutos = [];
        }
    }
    
    // Load last calculator state if exists
    const lastState = localStorage.getItem('autokosten_calculator_state');
    if (lastState) {
        try {
            const state = JSON.parse(lastState);
            // Restore form values if needed
            console.log('🔄 Laatste calculator staat hersteld');
        } catch (e) {
            console.error('Error loading calculator state:', e);
        }
    }
}

// ===========================
// Initialize Chart Functions
// ===========================
function initializeCharts() {
    // Kostenvergelijking Chart
    const kostenCtx = document.getElementById('kosten-chart');
    if (kostenCtx) {
        charts.kostenChart = new Chart(kostenCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Zakelijk', 'Privé'],
                datasets: [{
                    label: 'Kosten per maand',
                    data: [0, 0],
                    backgroundColor: ['rgba(139, 92, 246, 0.8)', 'rgba(239, 68, 68, 0.8)'],
                    borderColor: ['rgb(139, 92, 246)', 'rgb(239, 68, 68)'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Kostenvergelijking per Maand',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€ ' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 5-jaar Verloop Chart
    const verloopCtx = document.getElementById('verloop-chart');
    if (verloopCtx) {
        charts.verloopChart = new Chart(verloopCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jaar 1', 'Jaar 2', 'Jaar 3', 'Jaar 4', 'Jaar 5'],
                datasets: [
                    {
                        label: 'Zakelijk',
                        data: [0, 0, 0, 0, 0],
                        borderColor: 'rgb(139, 92, 246)',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Privé',
                        data: [0, 0, 0, 0, 0],
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '5-Jaars Kostenprojectie',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€ ' + (value/1000).toFixed(0) + 'k';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Verdeling Zakelijk Chart
    const verdelingZakelijkCtx = document.getElementById('verdeling-zakelijk-chart');
    if (verdelingZakelijkCtx) {
        charts.verdelingZakelijkChart = new Chart(verdelingZakelijkCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Bijtelling', 'Brandstof', 'Onderhoud'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: [
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)'
                    ],
                    borderColor: [
                        'rgb(139, 92, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)'
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
                        text: 'Kostenverdeling Zakelijk',
                        font: { size: 14, weight: 'bold' }
                    },
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': € ' + context.parsed.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Verdeling Privé Chart
    const verdelingPriveCtx = document.getElementById('verdeling-prive-chart');
    if (verdelingPriveCtx) {
        charts.verdelingPriveChart = new Chart(verdelingPriveCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Afschrijving', 'Brandstof', 'Verzekering', 'Onderhoud', 'MRB'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
                    ],
                    borderColor: [
                        'rgb(239, 68, 68)',
                        'rgb(16, 185, 129)',
                        'rgb(59, 130, 246)',
                        'rgb(245, 158, 11)',
                        'rgb(236, 72, 153)'
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
                        text: 'Kostenverdeling Privé',
                        font: { size: 14, weight: 'bold' }
                    },
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': € ' + context.parsed.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
}

// ===========================
// Display Results Function
// ===========================
function displayResults() {
    if (!calculatorState.results || !calculatorState.results.data) {
        console.log('Geen resultaten om weer te geven');
        return;
    }
    
    const results = calculatorState.results;
    const resultsContainer = document.getElementById('results-container');
    
    if (resultsContainer && results.private && results.business) {
        resultsContainer.innerHTML = generateResultsHTML(
            results.private,
            results.business,
            results.data
        );
    }
}

// ===========================
// Complete Calculate Function
// ===========================
function performCalculation() {
    // Validate required fields
    if (!validateAllTabs()) {
        showNotification('⚠️ Vul eerst alle verplichte velden in', 'warning');
        return;
    }
    
    // Collect all form data
    const formData = collectFormData();
    
    // Calculate private costs
    const privateCosts = calculatePrivateCosts(formData);
    
    // Calculate business costs
    const businessCosts = calculateBusinessCosts(formData);
    
    // Store results in state
    calculatorState.results = {
        private: privateCosts,
        business: businessCosts,
        data: formData,
        maandKosten: {
            prive: privateCosts.totaal,
            zakelijk: businessCosts.nettoKosten
        },
        breakdown: {
            prive: {
                afschrijving: privateCosts.afschrijving,
                brandstof: privateCosts.brandstof,
                verzekering: privateCosts.verzekering,
                onderhoud: privateCosts.onderhoud,
                mrb: privateCosts.mrb
            },
            zakelijk: {
                bijtelling: businessCosts.nettoKosten,
                brandstof: 0, // Included in business arrangement
                onderhoud: 0  // Included in business arrangement
            }
        },
        timestamp: new Date().toISOString()
    };
    
    // Display results
    displayResults();
    
    // Update charts if on analyse tab
    if (calculatorState.currentTab === 'analyse') {
        updateCharts();
    }
    
    // Save state
    saveState();
    
    // Show success
    showNotification('✅ Berekening voltooid!', 'success');
    
    // Switch to results tab
    switchTab('results');
}

// ===========================
// Event Listener Updates
// ===========================
// Add these event listeners in the initializeEventListeners function
function addAnalyseTabListeners() {
    // Analyse tab click
    const analyseTab = document.querySelector('[data-tab="analyse"]');
    if (analyseTab) {
        analyseTab.addEventListener('click', function() {
            setTimeout(() => initializeAnalyseTab(), 100);
        });
    }
    
    // Auto Manager button
    const autoManagerBtn = document.getElementById('auto-manager-btn');
    if (autoManagerBtn) {
        autoManagerBtn.addEventListener('click', openAutoManager);
    }
    
    // Save Auto button
    const saveAutoBtn = document.getElementById('save-auto-btn');
    if (saveAutoBtn) {
        saveAutoBtn.addEventListener('click', saveAuto);
    }
    
    // Modal close button
    const modalClose = document.querySelector('.modal-close');
    if (modalClose) {
        modalClose.addEventListener('click', closeAutoManager);
    }
    
    // Modal background click
    const modal = document.getElementById('auto-manager-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAutoManager();
            }
        });
    }
    
    // Calculate button
    const calculateBtn = document.getElementById('calculate-btn');
    if (calculateBtn) {
        calculateBtn.addEventListener('click', performCalculation);
    }
    
    // Export button
    const exportBtn = document.getElementById('export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportResults);
    }
    
    // Reset button
    const resetBtn = document.getElementById('reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', resetCalculator);
    }
}

// Call this in the main initializeEventListeners function
document.addEventListener('DOMContentLoaded', function() {
    // Add to existing initialization
    addAnalyseTabListeners();
});

console.log('✅ AutoKosten Calculator loaded successfully!');
