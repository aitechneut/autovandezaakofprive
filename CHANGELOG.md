# Changelog - AutoKosten Calculator

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Planned
- Caching voor RDW API calls
- PDF export functionaliteit via TCPDF
- Bulk import via CSV voor bedrijven
- Multi-language support (Engels)
- Dark mode toggle
- Bijtelling regels 2026 wanneer bekend

## [1.0.0] - 2025-01-24
### Added
- 🎉 **Initial Release** - Complete AutoKosten Calculator

#### Frontend Features
- 5-tab responsive interface (Info, Gebruik, Kosten, Persoonlijk, Resultaten)
- RDW kenteken lookup met auto-complete
- 4 interactieve Chart.js grafieken
- Auto Manager voor opslaan/laden/vergelijken
- LocalStorage data persistentie
- Export naar JSON functionaliteit
- Print-vriendelijke layouts
- Mobile-first responsive design
- Gradient styling met moderne UI

#### Backend Features  
- PHP 7.4+ backend implementatie
- RDW Open Data API integratie (3 endpoints)
- Complete bijtelling database 2004-2025
- Youngtimer detectie (15-30 jaar)
- Pre-2017 auto 25% regel
- 60-maanden vastzetting logica
- Elektrische auto caps per jaar
- MRB berekening op gewicht/brandstof
- Belastingschijf 2025 implementatie
- Intelligente schattingen voor alle waarden

#### Calculations
- Zakelijk vs privé kostenvergelijking
- 5-jaars projectie met inflatie
- Maandelijkse kosten breakdown
- Afschrijving berekeningen
- Brandstofkosten op basis van verbruik
- Verzekering schattingen (WA/WA+/AllRisk)
- Onderhoud leeftijd-gebaseerd

#### Development Tools
- Test suite (test-local.php)
- Deployment script (deploy.sh)
- Git workflow setup
- Comprehensive documentation

### Technical Details
- **Stack:** PHP 7.4+, Vanilla JavaScript ES6+, HTML5, CSS3
- **APIs:** RDW Open Data (no key required)
- **Libraries:** Chart.js 4.4.0 (CDN)
- **Compatibility:** Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Hosting:** Hostinger with auto-deploy from GitHub

## [0.9.0] - 2025-01-23 (Beta)
### Added
- Complete frontend implementation
- JavaScript calculatie engine
- LocalStorage integration
- Chart visualisaties

## [0.5.0] - 2025-01-22 (Alpha)
### Added
- Initial HTML structure
- Basic CSS styling
- Form layouts
- Tab navigation

## [0.1.0] - 2025-01-21 (Concept)
### Added
- Project initialization
- Requirement analysis
- Database research bijtelling regels
- RDW API documentation study

---

## Version History Summary

| Version | Date | Status | Description |
|---------|------|--------|-------------|
| 1.0.0 | 2025-01-24 | Stable | Full release with PHP backend |
| 0.9.0 | 2025-01-23 | Beta | Complete frontend |
| 0.5.0 | 2025-01-22 | Alpha | Basic structure |
| 0.1.0 | 2025-01-21 | Concept | Initial planning |

## Upgrade Notes

### From Frontend-Only to PHP Version
If upgrading from the HTML-only version to PHP:
1. Ensure PHP 7.4+ is installed
2. Enable CURL extension
3. Update file references from .html to .php
4. Clear browser cache
5. Test RDW API connectivity

### Browser Cache
After updates, users should clear browser cache or use:
- Chrome: Ctrl+Shift+R (Cmd+Shift+R on Mac)
- Firefox: Ctrl+F5 (Cmd+Shift+R on Mac)
- Safari: Cmd+Option+R

## Breaking Changes
- None yet (v1.0.0 is initial release)

## Security Updates
- All user inputs are sanitized
- API calls use proper error handling
- No sensitive data stored client-side
- HTTPS enforced on production

## Performance Metrics
- Page Load: < 2 seconds
- RDW API Response: < 1 second
- Calculation Time: < 100ms
- Total Bundle Size: ~100KB (excluding Chart.js)

---

**Repository:** [github.com/aitechneut/autovandezaakofprive](https://github.com/aitechneut/autovandezaakofprive)
**Live Demo:** [pianomanontour.nl/autovandezaakofprive](https://www.pianomanontour.nl/autovandezaakofprive)
**Author:** Richard Surie