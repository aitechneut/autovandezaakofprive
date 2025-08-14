<?php
/**
 * Nederlandse Bijtelling Database 2004-2025
 * 
 * Complete database met alle bijtelling percentages, CO2-grenzen en speciale regelingen
 * Gebaseerd op officiële Belastingdienst documentatie
 * 
 * @author Richard Surie
 * @version 1.0.0
 */

/**
 * Hoofdfunctie voor bijtelling berekening
 * 
 * @param int $bouwjaar - Jaar van eerste toelating
 * @param string $brandstof - Type brandstof (Benzine, Diesel, Elektrisch, PHEV, Waterstof, etc.)
 * @param float $cataloguswaarde - Nieuwprijs inclusief BTW/BPM
 * @param float $dagwaarde - Huidige marktwaarde (voor youngtimers)
 * @param int $co2_uitstoot - CO2 uitstoot in g/km (optioneel)
 * @param string $datum_eerste_toelating - Format: YYYYMMDD
 * @return array - Bijtelling informatie
 */
function getBijtelling($bouwjaar, $brandstof, $cataloguswaarde, $dagwaarde = null, $co2_uitstoot = null, $datum_eerste_toelating = null) {
    $currentYear = date('Y');
    $autoLeeftijd = $currentYear - $bouwjaar;
    
    // Initialize result
    $result = [
        'percentage' => 22,
        'basis' => $cataloguswaarde,
        'is_youngtimer' => false,
        'is_pre_2017' => false,
        'is_elektrisch' => false,
        'bijtelling_bedrag' => 0,
        'regel_uitleg' => '',
        '60_maanden_regel' => false,
        'expiratie_datum' => null
    ];
    
    // Check youngtimer status (15-30 jaar oud)
    if ($autoLeeftijd >= 15 && $autoLeeftijd <= 30) {
        $result['is_youngtimer'] = true;
        $result['percentage'] = 35;
        $result['basis'] = $dagwaarde ?: $cataloguswaarde * 0.15; // Schat dagwaarde op 15% als niet opgegeven
        $result['regel_uitleg'] = "Youngtimer regeling: 35% over dagwaarde voor auto's van 15-30 jaar oud";
        $result['bijtelling_bedrag'] = ($result['basis'] * $result['percentage']) / 100;
        return $result;
    }
    
    // Check of auto voor 2017 is geregistreerd
    $registratieJaar = $bouwjaar; // Default naar bouwjaar
    if ($datum_eerste_toelating) {
        $registratieJaar = intval(substr($datum_eerste_toelating, 0, 4));
    }
    
    if ($registratieJaar < 2017) {
        $result['is_pre_2017'] = true;
    }
    
    // Check of het elektrisch of waterstof is
    $brandstofLower = strtolower($brandstof);
    $isElektrisch = (strpos($brandstofLower, 'elektr') !== false && strpos($brandstofLower, 'hybr') === false);
    $isWaterstof = (strpos($brandstofLower, 'waterstof') !== false);
    $isPHEV = (strpos($brandstofLower, 'phev') !== false || 
               (strpos($brandstofLower, 'hybr') !== false && strpos($brandstofLower, 'plug') !== false));
    
    // Bepaal het bijtelling percentage op basis van jaar en type
    if ($isElektrisch || $isWaterstof) {
        $result['is_elektrisch'] = true;
        $percentage = getElektrischPercentage($currentYear, $cataloguswaarde);
        $result['percentage'] = $percentage['percentage'];
        $result['basis'] = $percentage['basis'];
        $result['regel_uitleg'] = $percentage['uitleg'];
    } elseif ($isPHEV && $co2_uitstoot !== null && $co2_uitstoot <= 50) {
        // Plug-in hybride met lage CO2
        $percentage = getPHEVPercentage($currentYear, $co2_uitstoot);
        $result['percentage'] = $percentage['percentage'];
        $result['regel_uitleg'] = $percentage['uitleg'];
    } else {
        // Normale brandstof auto
        if ($result['is_pre_2017']) {
            $result['percentage'] = 25;
            $result['regel_uitleg'] = "Pre-2017 auto: standaard 25% bijtelling (permanent)";
        } else {
            $result['percentage'] = 22;
            $result['regel_uitleg'] = "Standaard bijtelling 22% voor alle niet-elektrische auto's vanaf 2017";
        }
    }
    
    // Check 60-maanden regel
    if ($datum_eerste_toelating) {
        $result = apply60MaandenRegel($result, $datum_eerste_toelating, $currentYear);
    }
    
    // Bereken bijtelling bedrag
    $result['bijtelling_bedrag'] = ($result['basis'] * $result['percentage']) / 100;
    
    return $result;
}

/**
 * Haal elektrisch voertuig percentage op basis van jaar
 */
function getElektrischPercentage($jaar, $cataloguswaarde) {
    $elektrischRegels = [
        2025 => ['percentage' => 17, 'cap' => 30000],
        2024 => ['percentage' => 16, 'cap' => 30000],
        2023 => ['percentage' => 16, 'cap' => 30000],
        2022 => ['percentage' => 16, 'cap' => 35000],
        2021 => ['percentage' => 12, 'cap' => 40000],
        2020 => ['percentage' => 8, 'cap' => 45000],
        2019 => ['percentage' => 4, 'cap' => 50000],
        2018 => ['percentage' => 4, 'cap' => null],
        2017 => ['percentage' => 4, 'cap' => null],
        2016 => ['percentage' => 4, 'cap' => null],
        2015 => ['percentage' => 4, 'cap' => null],
        2014 => ['percentage' => 4, 'cap' => null],
        2013 => ['percentage' => 0, 'cap' => null],
        2012 => ['percentage' => 0, 'cap' => null]
    ];
    
    // Voor 2026 en later
    if ($jaar >= 2026) {
        return [
            'percentage' => 22,
            'basis' => $cataloguswaarde,
            'uitleg' => "Vanaf 2026: elektrische auto's krijgen standaard 22% bijtelling"
        ];
    }
    
    // Voor jaren voor 2012
    if ($jaar < 2012) {
        return [
            'percentage' => 22,
            'basis' => $cataloguswaarde,
            'uitleg' => "Voor 2012: geen speciale regeling voor elektrische auto's"
        ];
    }
    
    $regel = $elektrischRegels[$jaar];
    $basis = $cataloguswaarde;
    $uitleg = "Elektrisch voertuig $jaar: {$regel['percentage']}% bijtelling";
    
    if ($regel['cap'] !== null) {
        if ($cataloguswaarde > $regel['cap']) {
            // Bijtelling over cap bedrag tegen verlaagd tarief, rest tegen 22%
            $basis = $regel['cap'];
            $uitleg .= " tot €" . number_format($regel['cap'], 0, ',', '.') . " cataloguswaarde";
            $uitleg .= ", daarboven 22%";
        } else {
            $uitleg .= " over volledige cataloguswaarde";
        }
    }
    
    return [
        'percentage' => $regel['percentage'],
        'basis' => $basis,
        'uitleg' => $uitleg
    ];
}

/**
 * Haal PHEV percentage op basis van jaar en CO2
 */
function getPHEVPercentage($jaar, $co2_uitstoot) {
    if ($co2_uitstoot > 50) {
        return [
            'percentage' => 22,
            'uitleg' => "Plug-in hybride met CO2 > 50 g/km: standaard 22% bijtelling"
        ];
    }
    
    // Historische PHEV regels
    if ($jaar >= 2017) {
        return [
            'percentage' => 22,
            'uitleg' => "Vanaf 2017: alle PHEV's krijgen 22% bijtelling"
        ];
    } elseif ($jaar == 2016) {
        return [
            'percentage' => 15,
            'uitleg' => "2016: PHEV met CO2 ≤ 50 g/km krijgt 15% bijtelling"
        ];
    } elseif ($jaar >= 2014) {
        return [
            'percentage' => 7,
            'uitleg' => "2014-2015: PHEV met CO2 ≤ 50 g/km krijgt 7% bijtelling"
        ];
    } elseif ($jaar >= 2012) {
        return [
            'percentage' => 0,
            'uitleg' => "2012-2013: PHEV met CO2 ≤ 50 g/km krijgt 0% bijtelling"
        ];
    }
    
    return [
        'percentage' => 22,
        'uitleg' => "Standaard bijtelling voor PHEV"
    ];
}

/**
 * Pas 60-maanden regel toe
 */
function apply60MaandenRegel($result, $datum_eerste_toelating, $currentYear) {
    // Parse datum
    $year = intval(substr($datum_eerste_toelating, 0, 4));
    $month = intval(substr($datum_eerste_toelating, 4, 2));
    $day = intval(substr($datum_eerste_toelating, 6, 2));
    
    // Bereken start van 60-maanden periode (eerste dag van volgende maand)
    $startDate = new DateTime("$year-$month-$day");
    $startDate->modify('first day of next month');
    
    // Bereken einde van 60-maanden periode
    $endDate = clone $startDate;
    $endDate->add(new DateInterval('P60M'));
    
    $now = new DateTime();
    
    if ($now < $endDate) {
        $result['60_maanden_regel'] = true;
        $result['expiratie_datum'] = $endDate->format('Y-m-d');
        $result['regel_uitleg'] .= " (vastgezet tot " . $endDate->format('d-m-Y') . " volgens 60-maanden regel)";
    } else {
        // 60 maanden zijn verstreken, pas nieuwe regels toe
        if ($result['is_elektrisch']) {
            // Elektrische auto's krijgen huidige jaar percentage
            $nieuwPercentage = getElektrischPercentage($currentYear, $result['basis']);
            $result['percentage'] = $nieuwPercentage['percentage'];
            $result['regel_uitleg'] = "60-maanden periode verstreken. " . $nieuwPercentage['uitleg'];
        } elseif ($result['is_pre_2017']) {
            $result['percentage'] = 25;
            $result['regel_uitleg'] = "60-maanden periode verstreken. Pre-2017 auto: terug naar 25%";
        } else {
            $result['percentage'] = 22;
            $result['regel_uitleg'] = "60-maanden periode verstreken. Standaard 22% bijtelling";
        }
    }
    
    return $result;
}

/**
 * Check of auto een youngtimer is
 */
function isYoungtimerAuto($bouwjaar) {
    $currentYear = date('Y');
    $leeftijd = $currentYear - $bouwjaar;
    return ($leeftijd >= 15 && $leeftijd <= 30);
}

/**
 * Bereken MRB (Motor Rijtuigen Belasting) schatting
 */
function calculateMRB($gewicht, $brandstof, $bouwjaar) {
    $currentYear = date('Y');
    $brandstofLower = strtolower($brandstof);
    
    // Elektrische auto's
    if (strpos($brandstofLower, 'elektr') !== false && strpos($brandstofLower, 'hybr') === false) {
        if ($currentYear <= 2024) {
            return 0; // Vrijstelling t/m 2024
        } elseif ($currentYear == 2025) {
            return ($gewicht / 100) * 2; // 25% korting in 2025
        } else {
            return ($gewicht / 100) * 8; // Vol tarief vanaf 2026
        }
    }
    
    // Waterstof
    if (strpos($brandstofLower, 'waterstof') !== false) {
        return 0; // Vrijstelling voor waterstof
    }
    
    // Benzine/Diesel/Hybride - simplified berekening
    $basisTarief = 8; // Euro per 100kg
    
    // Diesel toeslag
    if (strpos($brandstofLower, 'diesel') !== false) {
        $basisTarief *= 2.5;
    }
    
    // Oldtimer korting
    if ($currentYear - $bouwjaar > 40) {
        $basisTarief *= 0.25; // 75% korting voor oldtimers
    }
    
    return ($gewicht / 100) * $basisTarief;
}

/**
 * Historische bijtelling percentages voor referentie
 */
function getHistorischeBijtelling($jaar, $co2_uitstoot = null, $brandstof = 'Benzine') {
    // 2004-2007: Vlak tarief
    if ($jaar >= 2004 && $jaar <= 2007) {
        return 22;
    }
    
    // 2008-2016: CO2 gedifferentieerd
    if ($jaar >= 2008 && $jaar <= 2016 && $co2_uitstoot !== null) {
        return getCO2Bijtelling($jaar, $co2_uitstoot, $brandstof);
    }
    
    // 2017+: Vereenvoudigd systeem
    if ($jaar >= 2017) {
        return 22; // Standaard voor niet-elektrisch
    }
    
    return 25; // Default oude auto's
}

/**
 * CO2-gebaseerde bijtelling 2008-2016
 */
function getCO2Bijtelling($jaar, $co2_uitstoot, $brandstof) {
    $isDiesel = (strpos(strtolower($brandstof), 'diesel') !== false);
    
    // Simplified CO2 grenzen per jaar
    $co2Grenzen = [
        2016 => [
            ['max' => 0, 'percentage' => 4],
            ['max' => 50, 'percentage' => 15],
            ['max' => 106, 'percentage' => 21],
            ['max' => 999, 'percentage' => 25]
        ],
        2015 => [
            ['max' => 0, 'percentage' => 4],
            ['max' => 50, 'percentage' => 7],
            ['max' => 82, 'percentage' => 14],
            ['max' => 110, 'percentage' => 20],
            ['max' => 999, 'percentage' => 25]
        ],
        2014 => [
            ['max' => 0, 'percentage' => 4],
            ['max' => 50, 'percentage' => 7],
            ['max' => $isDiesel ? 85 : 88, 'percentage' => 14],
            ['max' => $isDiesel ? 111 : 117, 'percentage' => 20],
            ['max' => 999, 'percentage' => 25]
        ],
        2013 => [
            ['max' => 50, 'percentage' => 0],
            ['max' => $isDiesel ? 88 : 95, 'percentage' => 14],
            ['max' => $isDiesel ? 112 : 124, 'percentage' => 20],
            ['max' => 999, 'percentage' => 25]
        ],
        2012 => [
            ['max' => 50, 'percentage' => 0],
            ['max' => $isDiesel ? 95 : 110, 'percentage' => 14],
            ['max' => $isDiesel ? 116 : 140, 'percentage' => 20],
            ['max' => 999, 'percentage' => 25]
        ]
    ];
    
    // Voor jaren zonder specifieke data
    if (!isset($co2Grenzen[$jaar])) {
        if ($jaar >= 2009 && $jaar <= 2011) {
            // 2009-2011 hadden vergelijkbare grenzen als 2012
            $co2Grenzen[$jaar] = $co2Grenzen[2012];
            $co2Grenzen[$jaar][0]['percentage'] = 14; // Geen 0% voor 2012
        } else {
            return 25; // Default
        }
    }
    
    // Vind het juiste percentage
    foreach ($co2Grenzen[$jaar] as $grens) {
        if ($co2_uitstoot <= $grens['max']) {
            return $grens['percentage'];
        }
    }
    
    return 25; // Default hoogste tarief
}

/**
 * Export alle bijtelling data voor een specifiek jaar
 */
function exportBijtellingData($jaar) {
    return [
        'jaar' => $jaar,
        'elektrisch' => getElektrischPercentage($jaar, 50000),
        'standaard' => $jaar >= 2017 ? 22 : 25,
        'youngtimer' => 35,
        'pre_2017_regel' => $jaar >= 2017,
        '60_maanden_actief' => true
    ];
}
?>