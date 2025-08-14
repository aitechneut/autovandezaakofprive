# 🚗 AutoKosten Calculator - Auto van de Zaak of Privé?

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Live Demo](https://img.shields.io/badge/demo-live-success.svg)](https://www.pianomanontour.nl/autovandezaakofprive)

Een geavanceerde webapplicatie voor Nederlandse ondernemers om de optimale keuze te maken tussen een auto van de zaak of privé rijden. Integreert real-time RDW data en implementeert alle Nederlandse bijtelling regels van 2004 tot 2025+.

## 🌟 Features

### Core Functionaliteit
- **🔍 RDW Kenteken Lookup** - Automatisch voertuiggegevens ophalen via kenteken
- **💰 Bijtelling Calculator** - Complete Nederlandse bijtelling database 2004-2025+
- **👴 Youngtimer Detectie** - Speciale 35% regeling voor 15-30 jaar oude auto's
- **📊 Real-time Vergelijking** - Directe vergelijking zakelijk vs privé kosten
- **📈 5-Jaars Projectie** - Toekomstprojectie met inflatie correctie
- **💾 Auto Manager** - Bewaar en vergelijk meerdere auto's

### Visuele Analyse
- **4 Interactieve Grafieken** via Chart.js
  - Kosten vergelijking staafdiagram
  - 5-jaars kosten projectie
  - Maandelijkse kosten breakdown
  - Jaarlijkse besparingen analyse
- **📱 Responsive Design** - Werkt perfect op alle devices
- **🖨️ Print-vriendelijk** - Optimale print layouts voor rapporten

### Technische Features
- **API Integratie** - RDW Open Data API (geen key nodig)
- **Smart Defaults** - Intelligente schattingen voor alle waarden
- **Offline Capable** - LocalStorage voor data persistentie
- **Export Functionaliteit** - JSON export voor externe verwerking

## 🚀 Quick Start

### Vereisten
- PHP 7.4 of hoger
- Apache/Nginx webserver
- CURL extension voor API calls
- Modern browser (Chrome, Firefox, Safari, Edge)

### Installatie

1. **Clone de repository**
```bash
git clone https://github.com/aitechneut/autovandezaakofprive.git
cd autovandezaakofprive
```

2. **Configureer je webserver**
```bash
# Voor MAMP/XAMPP
cp -r * /Applications/MAMP/htdocs/autovandezaakofprive/

# Voor custom Apache
sudo cp -r * /var/www/html/autovandezaakofprive/
```

3. **Test de installatie**
```bash
# Open in browser
http://localhost/autovandezaakofprive/test-local.php
```

4. **Start de applicatie**
```bash
http://localhost/autovandezaakofprive/
```

## 📖 Gebruikshandleiding

### Stap 1: Voertuig Selecteren
1. Voer een Nederlands kenteken in (bijv. "GB-123-X")
2. Systeem haalt automatisch merk, model, bouwjaar en specificaties op
3. Aanvullende gegevens worden intelligent geschat

### Stap 2: Gebruik Specificeren
1. Geef maandelijkse kilometers op
2. Vul brandstofverbruik in (of gebruik schatting)
3. Specificeer huidige brandstofprijzen

### Stap 3: Kosten Invoeren
1. Cataloguswaarde (automatisch geschat)
2. Verzekering (WA, WA+ of All Risk)
3. Onderhoud (leeftijd-gebaseerde schatting)
4. MRB (automatisch berekend)

### Stap 4: Persoonlijke Situatie
1. Bruto jaarinkomen (voor belastingschijf)
2. Aankoopprijs indien relevant
3. Verwachte restwaarde

### Stap 5: Resultaten Analyseren
- Directe maandelijkse vergelijking
- 5-jaars totaal overzicht
- Grafische weergave van alle componenten
- Exporteer of print het rapport

## 🏗️ Project Structuur

```
autovandezaakofprive/
├── index.php                    # Hoofdapplicatie (PHP versie)
├── index.html                   # Statische HTML versie
├── api/
│   └── rdw-lookup.php          # RDW API handler
├── includes/
│   ├── bijtelling_database.php # Complete bijtelling regels
│   ├── calculator.php          # Backend calculatie logica
│   └── functions.php           # Helper functies
├── assets/
│   ├── style.css              # Styling en responsive design
│   └── autovandezaakofprive.js # Frontend JavaScript
├── test-local.php              # Test suite
├── deploy.sh                   # Deployment script
└── README.md                   # Deze file
```

## 💡 Nederlandse Bijtelling Regels

### 2025 Regels
| Type Auto | Bijtelling | Voorwaarden |
|-----------|------------|-------------|
| Elektrisch | 17% | Tot €30.000 cataloguswaarde |
| Elektrisch | 22% | Boven €30.000 cataloguswaarde |
| Benzine/Diesel/Hybride | 22% | Alle prijsklassen |
| Youngtimer (15-30 jaar) | 35% | Over dagwaarde i.p.v. catalogus |
| Pre-2017 auto's | 25% | Permanent na 60 maanden |

### Belangrijke Regels
- **60-Maanden Vastzetting**: Bijtelling percentage blijft 60 maanden vast
- **Youngtimers**: Auto's van 15-30 jaar oud krijgen 35% over dagwaarde
- **Pre-2017**: Auto's van voor 2017 behouden permanent 25% tarief
- **Waterstof**: Krijgt zelfde behandeling als elektrisch zonder cap

## 🔧 API Documentatie

### RDW Lookup Endpoint
```php
GET /api/rdw-lookup.php?kenteken=XX-XX-XX

Response:
{
  "success": true,
  "data": {
    "kenteken": "GBXYZ1",
    "merk": "VOLKSWAGEN",
    "handelsbenaming": "GOLF",
    "datum_eerste_toelating": "20200315",
    "catalogusprijs": 35000,
    "brandstof": "Benzine",
    "is_youngtimer": false,
    ...
  }
}
```

### Calculator Endpoint
```php
POST /includes/calculator.php
Content-Type: application/json

{
  "action": "calculate",
  "bouwjaar": 2020,
  "brandstof": "Benzine",
  "cataloguswaarde": 30000,
  ...
}
```

## 🚢 Deployment

### Automatische Deployment
```bash
# Maak het script executable
chmod +x deploy.sh

# Deploy naar productie
./deploy.sh "Beschrijving van wijzigingen"
```

### Handmatige Deployment
1. Sync files naar GitHub repository
2. Hostinger haalt automatisch updates op
3. Verificeer op https://www.pianomanontour.nl/autovandezaakofprive

## 🧪 Testing

### Lokaal Testen
```bash
# Start MAMP/XAMPP
# Open test suite
http://localhost/autovandezaakofprive/test-local.php
```

### Test Scenarios
- ✅ Nieuwe elektrische auto 2025
- ✅ Youngtimer detectie (15+ jaar)
- ✅ Pre-2017 auto (25% regel)
- ✅ PHEV met lage CO2
- ✅ Onbekend kenteken fallback

## 📊 Browser Compatibiliteit

| Browser | Versie | Status |
|---------|--------|--------|
| Chrome | 90+ | ✅ Volledig ondersteund |
| Firefox | 88+ | ✅ Volledig ondersteund |
| Safari | 14+ | ✅ Volledig ondersteund |
| Edge | 90+ | ✅ Volledig ondersteund |
| IE | Alle | ❌ Niet ondersteund |

## 🤝 Contributing

Contributions zijn welkom! Please follow these steps:

1. Fork het project
2. Create een feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit je changes (`git commit -m 'Add AmazingFeature'`)
4. Push naar de branch (`git push origin feature/AmazingFeature`)
5. Open een Pull Request

## 📝 Changelog

### v1.0.0 (2025-01-24)
- 🎉 Initiële release
- ✅ Complete frontend implementatie
- ✅ PHP backend met RDW integratie
- ✅ Bijtelling database 2004-2025
- ✅ Youngtimer detectie
- ✅ 5-jaars projectie
- ✅ Export functionaliteit

## 🐛 Known Issues

- RDW API heeft geen rate limiting implemented (TODO: add caching)
- Bijtelling regels 2026+ zijn nog niet definitief
- Print layout kan verbeterd worden voor sommige browsers

## 📜 License

Dit project is gelicenseerd onder de MIT License - zie het [LICENSE](LICENSE) bestand voor details.

## 👨‍💻 Author

**Richard Surie**
- Website: [PianoManOnTour.nl](https://www.pianomanontour.nl)
- GitHub: [@aitechneut](https://github.com/aitechneut)
- Project: [AutoKosten Calculator](https://www.pianomanontour.nl/autovandezaakofprive)

## 🙏 Acknowledgments

- [RDW Open Data](https://opendata.rdw.nl/) voor voertuig data
- [Belastingdienst](https://www.belastingdienst.nl/) voor bijtelling informatie
- [Chart.js](https://www.chartjs.org/) voor grafieken
- Nederlandse ondernemers community voor feedback

## 📞 Support

Voor vragen of ondersteuning:
- Open een [GitHub Issue](https://github.com/aitechneut/autovandezaakofprive/issues)
- Email: via [PianoManOnTour.nl](https://www.pianomanontour.nl/contact)

---

**Live Demo:** [https://www.pianomanontour.nl/autovandezaakofprive](https://www.pianomanontour.nl/autovandezaakofprive)

*Made with ❤️ for Nederlandse Ondernemers*
