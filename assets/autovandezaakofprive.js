/**
 * AutoKosten Calculator - Main JavaScript
 * Created for PianoManOnTour.nl/autovandezaakofprive
 * Version: 1.0.0
 * Author: Richard Surie
 */

// Global Variables & State
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

// Auto kleuren voor grafieken
const autoColors = [
    '#8B5CF6', '#10B981', '#F59E0B', '#EF4444', 
    '#3B82F6', '#EC4899', '#14B8A6', '#F97316'
];

// Initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚗 AutoKosten Calculator initialized');
    
    initializeEventListeners();
    initializeFormValidation();
    initializeKentekenFormatter();
    updateLiveCalculations();
    loadSavedState();
});

// Event Listeners
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
}

// Tab Navigation
function switchTab(tabName) {
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    document.querySelectorAll('.form-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(`${tabName}-section`).classList.add('active');
    
    calculatorState.currentTab = tabName;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Kenteken Formatter & Lookup
function initializeKentekenFormatter() {
    const kentekenInput = document.getElementById('kenteken');
    if (!kentekenInput) return;
    
    kentekenInput.addEventListener('input', function(e) {
        let value = e.target.value.toUpperCase();
        value = value.replace(/[^A-Z0-9]/g, '');
        
        if (value.length >= 6) {
            if (/^\d/.test(value)) {
                value = value.slice(0, 2) + '-' + value.slice(2, 5) + '-' + value.slice(5, 6);
            } else if (/^[A-Z]{2}\d{2}/.test(value)) {
                value = value.slice(0, 2) + '-' + value.slice(2, 4) + '-' + value.slice(4, 6);
            } else {
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
        showLoading(true);
        
        const response = await fetch(`api/rdw-lookup.php?kenteken=${kenteken}`);
        const data = await response.json();
        
        if (data.success) {
            fillFormWithRDWData(data.data);
            showNotification('✅ Voertuiggegevens succesvol opgehaald!', 'success');
            calculatorState.rdwData = data.data;
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
    const mappings = {
        'merk': data.merk || '',
        'model': data.handelsbenaming || '',
        'bouwjaar': data.bouwjaar || '',
        'brandstof': data.brandstof_type || '',
        'gewicht': data.massa_ledig_voertuig || '',
        'cataloguswaarde': data.catalogusprijs || '',
        'co2_uitstoot': data.co2_uitstoot || ''
    };
    
    for (const [field, value] of Object.entries(mappings)) {
        const element = document.getElementById(field);
        if (element && value) {
            element.value = value;
            element.dispatchEvent(new Event('change'));
        }
    }
    
    checkYoungtimerStatus();
    estimateMissingValues();
}

// Live Calculations
function updateLiveCalculations() {
    checkYoungtimerStatus();
    updateBijtellingPreview();
    estimateMRB();
    estimateInsurance();
    estimateMaintenance();
}

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
        document.getElementById('dagwaarde').required = true;
        showNotification('ℹ️ Youngtimer gedetecteerd! Vul de dagwaarde in.', 'info');
    } else {
        statusElement.textContent = 'Moderne Auto';
        statusDesc.textContent = `${age} jaar oud - Normale bijtelling`;
        statusElement.style.color = '#667eea';
        document.getElementById('dagwaarde').required = false;
    }
    
    updateBijtellingPreview();
}

function updateBijtellingPreview() {
    const bouwjaar = parseInt(document.getElementById('bouwjaar').value);
    const brandstof = document.getElementById('brandstof').value;
    const cataloguswaarde = parseFloat(document.getElementById('cataloguswaarde').value) || 0;
    
    if (!bouwjaar || !brandstof) return;
    
    const currentYear = new Date().getFullYear();
    const age = currentYear - bouwjaar;
    
    let percentage = 22;
    let basis = cataloguswaarde;
    
    if (age >= 15 && age < 30) {
        percentage = 35;
        basis = parseFloat(document.getElementById('dagwaarde').value) || cataloguswaarde * 0.1;
    } else if (bouwjaar < 2017 && brandstof !== 'elektrisch') {
        percentage = 25;
    } else if (brandstof === 'elektrisch') {
        percentage = 17;
        if (cataloguswaarde > 30000) {
            basis = 30000;
        }
    }
    
    document.getElementById('bijtelling-preview').textContent = `${percentage}%`;
    document.getElementById('bijtelling-basis').textContent = `€${basis.toLocaleString('nl-NL')}`;
    
    calculatorState.bijtellingInfo = { percentage, basis };
    updateFinancialCalculations();
}

function calculateTaxPercentage() {
    const brutoInkomen = parseFloat(document.getElementById('bruto_inkomen').value) || 0;
    let percentage = 0;
    
    if (brutoInkomen <= 38441) {
        percentage = 35.82;
    } else if (brutoInkomen <= 76817) {
        percentage = 37.48;
    } else {
        percentage = 49.50;
    }
    
    document.getElementById('belasting_percentage').value = percentage;
    document.getElementById('effectief-tarief').textContent = `${percentage}%`;
    updateFinancialCalculations();
}

function updateFinancialCalculations() {
    const bijtellingInfo = calculatorState.bijtellingInfo;
    const belastingPercentage = parseFloat(document.getElementById('belasting_percentage').value) || 0;
    
    if (!bijtellingInfo.basis || !bijtellingInfo.percentage) return;
    
    const jaarlijkseBijtelling = (bijtellingInfo.basis * bijtellingInfo.percentage / 100);
    const maandelijkseBijtelling = jaarlijkseBijtelling / 12;
    const nettoKostenMaand = maandelijkseBijtelling * (belastingPercentage / 100);
    
    document.getElementById('maandelijkse-bijtelling').textContent = `€${maandelijkseBijtelling.toFixed(2)}`;
    document.getElementById('netto-bijtelling').textContent = `€${nettoKostenMaand.toFixed(2)}`;
}

// Validation
function initializeFormValidation() {
    const form = document.getElementById('calculator-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        calculateResults();
    });
}

// UI Helpers
function showNotification(message, type = 'info') {
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#00b09b' : type === 'error' ? '#f5576c' : '#667eea'};
        color: white; border-radius: 0.5rem; z-index: 10000; animation: slideInRight 0.3s ease-out;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
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

// State Management
function saveState() {
    // Save calculator state to localStorage
    const data = gatherFormData();
    localStorage.setItem('autokosten_calculator_state', JSON.stringify({
        data: data,
        state: calculatorState,
        timestamp: new Date().toISOString()
    }));
}

function loadSavedState() {
    // Load from localStorage if available
    const saved = localStorage.getItem('autokosten_calculator_state');
    if (!saved) return;
    
    try {
        const parsed = JSON.parse(saved);
        // Restore state if less than 24 hours old
        const savedTime = new Date(parsed.timestamp);
        const now = new Date();
        const hoursDiff = (now - savedTime) / (1000 * 60 * 60);
        
        if (hoursDiff < 24) {
            Object.entries(parsed.data).forEach(([key, value]) => {
                const element = document.getElementById(key);
                if (element && key !== 'bijtellingInfo') {
                    element.value = value;
                }
            });
            
            calculatorState = parsed.state;
            updateLiveCalculations();
            showNotification('ℹ️ Vorige sessie hersteld', 'info');
        }
    } catch (error) {
        console.error('Error loading saved state:', error);
    }
}

function gatherFormData() {
    const fields = [
        'kenteken', 'merk', 'model', 'bouwjaar', 'brandstof', 'gewicht',
        'cataloguswaarde', 'dagwaarde', 'km_per_maand', 'verbruik',
        'brandstofprijs', 'bruto_inkomen', 'belasting_percentage'
    ];
    
    const data = {};
    fields.forEach(field => {
        const element = document.getElementById(field);
        if (element) {
            data[field] = element.value;
        }
    });
    
    return data;
}

// Estimation Functions
function estimateMRB() {
    const gewicht = parseFloat(document.getElementById('gewicht').value) || 0;
    const brandstof = document.getElementById('brandstof').value;
    const mrbElement = document.getElementById('mrb_per_maand');
    
    if (!gewicht || mrbElement.value) return;
    
    let mrbPerKwartaal = 0;
    
    if (brandstof === 'elektrisch') {
        mrbPerKwartaal = 0;
    } else if (brandstof === 'diesel') {
        mrbPerKwartaal = (gewicht / 100) * 12;
    } else {
        mrbPerKwartaal = (gewicht / 100) * 8;
    }
    
    mrbElement.value = Math.round(mrbPerKwartaal / 3);
}

function estimateInsurance() {
    const cataloguswaarde = parseFloat(document.getElementById('cataloguswaarde').value) || 0;
    const verzekerElement = document.getElementById('verzekering_per_maand');
    
    if (!cataloguswaarde || verzekerElement.value) return;
    
    let monthlyPremium = 50;
    
    if (cataloguswaarde < 15000) {
        monthlyPremium = 40;
    } else if (cataloguswaarde < 30000) {
        monthlyPremium = 60;
    } else if (cataloguswaarde < 50000) {
        monthlyPremium = 90;
    } else {
        monthlyPremium = 120;
    }
    
    verzekerElement.value = Math.round(monthlyPremium);
}

function estimateMaintenance() {
    const bouwjaar = parseInt(document.getElementById('bouwjaar').value);
    const onderhoudElement = document.getElementById('onderhoud_per_maand');
    
    if (!bouwjaar || onderhoudElement.value) return;
    
    const age = new Date().getFullYear() - bouwjaar;
    let monthlyMaintenance = 50;
    
    if (age > 10) monthlyMaintenance += 50;
    if (age > 15) monthlyMaintenance += 50;
    
    onderhoudElement.value = Math.round(monthlyMaintenance);
}

function estimateMissingValues() {
    const catalogusElement = document.getElementById('cataloguswaarde');
    if (!catalogusElement.value) {
        const merk = document.getElementById('merk').value.toLowerCase();
        const bouwjaar = parseInt(document.getElementById('bouwjaar').value);
        
        let estimate = 25000;
        
        if (merk.includes('mercedes') || merk.includes('bmw') || merk.includes('audi')) {
            estimate = 45000;
        } else if (merk.includes('tesla')) {
            estimate = 60000;
        }
        
        const age = new Date().getFullYear() - bouwjaar;
        catalogusElement.value = Math.round(estimate * Math.pow(0.85, Math.min(age, 10)));
    }
}

console.log('✅ AutoKosten Calculator loaded successfully!');
