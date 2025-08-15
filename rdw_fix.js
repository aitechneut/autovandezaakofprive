// Verbeterde fillFormWithRDWData functie voor betere RDW data mapping
function fillFormWithRDWData(data) {
    console.log('📥 RDW Data ontvangen:', data);
    
    // Direct mapping voor simpele velden
    const directMappings = {
        'merk': data.merk || '',
        'model': data.model || data.handelsbenaming || '',
        'bouwjaar': data.bouwjaar || '',
        'gewicht': data.massa_ledig_voertuig || '',
        'cataloguswaarde': data.catalogusprijs || '',
        'co2_uitstoot': data.co2_uitstoot || '',
        'kilometerstand': data.kilometerstand || ''
    };
    
    // Vul direct mapped velden in
    for (const [fieldId, value] of Object.entries(directMappings)) {
        const element = document.getElementById(fieldId);
        if (element && value) {
            console.log(`✅ Veld ${fieldId} gevuld met:`, value);
            element.value = value;
            element.dispatchEvent(new Event('change'));
        } else if (!element) {
            console.warn(`⚠️ Veld ${fieldId} bestaat niet in form`);
        }
    }
    
    // Handle datum_eerste_toelating speciaal
    if (data.datum_eerste_toelating) {
        const dateStr = data.datum_eerste_toelating.toString();
        let formattedDate = '';
        
        // Check of het YYYYMMDD format is
        if (dateStr.length === 8 && !dateStr.includes('-')) {
            formattedDate = `${dateStr.substr(0,4)}-${dateStr.substr(4,2)}-${dateStr.substr(6,2)}`;
        } 
        // Check of het al YYYY-MM-DD format is
        else if (dateStr.includes('-')) {
            formattedDate = dateStr;
        }
        
        const dateField = document.getElementById('datum_eerste_toelating');
        if (dateField && formattedDate) {
            console.log(`✅ Datum eerste toelating: ${formattedDate}`);
            dateField.value = formattedDate;
        }
    }
    
    // Handle brandstof met verbeterde mapping
    let brandstofValue = '';
    
    // Check brandstof_type (string)
    if (data.brandstof_type) {
        brandstofValue = mapBrandstofToFormValue(data.brandstof_type);
    }
    // Check brandstof array
    else if (data.brandstof && Array.isArray(data.brandstof) && data.brandstof.length > 0) {
        // Kijk of het plug-in hybride is
        if (data.brandstof.includes('Elektrisch') && data.brandstof.length > 1) {
            brandstofValue = 'plugin_hybride';
        } else {
            brandstofValue = mapBrandstofToFormValue(data.brandstof[0]);
        }
    }
    
    if (brandstofValue) {
        const brandstofField = document.getElementById('brandstof');
        if (brandstofField) {
            console.log(`✅ Brandstof gezet naar: ${brandstofValue}`);
            brandstofField.value = brandstofValue;
            brandstofField.dispatchEvent(new Event('change'));
        }
    }
    
    // Check youngtimer status
    checkYoungtimerStatus();
    
    // Estimate missing values
    estimateMissingValues();
    
    console.log('✅ RDW data verwerking compleet');
}

// Verbeterde brandstof mapping functie
function mapBrandstofToFormValue(rdwBrandstof) {
    if (!rdwBrandstof) return '';
    
    const input = rdwBrandstof.toString().toLowerCase();
    console.log(`🔍 Mapping brandstof: "${rdwBrandstof}" -> "${input}"`);
    
    // Exacte matches eerst
    const exactMappings = {
        'benzine': 'benzine',
        'diesel': 'diesel',
        'elektrisch': 'elektrisch',
        'elektriciteit': 'elektrisch',
        'waterstof': 'waterstof',
        'lpg': 'lpg',
        'cng': 'cng',
        'phev': 'plugin_hybride',
        'plug-in hybride': 'plugin_hybride'
    };
    
    if (exactMappings[input]) {
        return exactMappings[input];
    }
    
    // Contains checks
    if (input.includes('benzin')) return 'benzine';
    if (input.includes('diesel')) return 'diesel';
    if (input.includes('elektr')) return 'elektrisch';
    if (input.includes('waterstof')) return 'waterstof';
    if (input.includes('lpg')) return 'lpg';
    if (input.includes('cng') || input.includes('aardgas')) return 'cng';
    if (input.includes('plug') || input.includes('phev')) return 'plugin_hybride';
    if (input.includes('hybride')) return 'hybride';
    
    console.warn(`⚠️ Onbekende brandstof: ${rdwBrandstof}, default naar benzine`);
    return 'benzine'; // Default
}

// Debug helper voor RDW lookup
async function performKentekenLookup() {
    const kentekenInput = document.getElementById('kenteken');
    const kenteken = kentekenInput.value.replace(/-/g, '').toUpperCase();
    
    if (kenteken.length < 6) {
        showNotification('⚠️ Vul een geldig kenteken in', 'error');
        return;
    }
    
    const lookupBtn = document.getElementById('lookup-btn');
    lookupBtn.classList.add('loading');
    lookupBtn.textContent = 'Bezig...';
    
    console.log(`🔍 Start RDW lookup voor kenteken: ${kenteken}`);
    
    try {
        showLoading(true);
        
        const response = await fetch(`api/rdw-lookup.php?kenteken=${kenteken}`);
        const result = await response.json();
        
        console.log('📡 RDW API response:', result);
        
        if (result.success && result.data) {
            fillFormWithRDWData(result.data);
            showNotification('✅ Voertuiggegevens succesvol opgehaald!', 'success');
            calculatorState.rdwData = result.data;
            updateLiveCalculations();
        } else {
            console.error('❌ RDW lookup mislukt:', result.error);
            showNotification(`❌ ${result.error || 'Kenteken niet gevonden'}`, 'error');
        }
    } catch (error) {
        console.error('❌ Lookup error:', error);
        showNotification('❌ Er ging iets mis bij het ophalen van gegevens', 'error');
    } finally {
        showLoading(false);
        lookupBtn.classList.remove('loading');
        lookupBtn.textContent = 'Ophalen';
    }
}

// Test functie voor development
function testRDWMapping() {
    const testData = {
        merk: 'Tesla',
        model: 'Model 3',
        handelsbenaming: 'Model 3 Long Range',
        bouwjaar: 2022,
        datum_eerste_toelating: '20220315',
        massa_ledig_voertuig: 1844,
        catalogusprijs: 52990,
        co2_uitstoot: 0,
        brandstof: ['Elektrisch'],
        brandstof_type: 'Elektrisch'
    };
    
    console.log('🧪 Test RDW mapping met:', testData);
    fillFormWithRDWData(testData);
}

// Export voor debugging
window.rdwDebug = {
    testMapping: testRDWMapping,
    mapBrandstof: mapBrandstofToFormValue
};

console.log('✅ RDW mapping verbeteringen geladen. Test met: rdwDebug.testMapping()');
