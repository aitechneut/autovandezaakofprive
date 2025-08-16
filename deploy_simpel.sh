#!/bin/bash
# Deploy Simpele Versie naar live site

echo "🚀 Deploy Simpele Versie AutoKosten Calculator"
echo "=============================================="

# Ga naar project directory
cd /Users/richardsurie/Documents/Development/Projects/autovandezaakofprive/

# Kopieer files naar GitHub folder
echo "📋 Kopieer bestanden naar GitHub folder..."
cp simpel.php ../GitHub/autovandezaakofprive/
cp index.php ../GitHub/autovandezaakofprive/
cp rdw_debug.html ../GitHub/autovandezaakofprive/

# Ga naar GitHub folder
cd ../GitHub/autovandezaakofprive/

# Git status
echo ""
echo "📊 Git status:"
git status

# Deploy
echo ""
echo "📤 Deploy naar GitHub..."
git add simpel.php index.php rdw_debug.html
git commit -m "Add Simpele Versie - Quick calculator with only essential fields"
git push origin main

echo ""
echo "✅ Deploy compleet!"
echo ""
echo "🌐 De Simpele Versie is nu live op:"
echo "   https://www.pianomanontour.nl/autovandezaakofprive/simpel.php"
echo ""
echo "📝 Features van de Simpele Versie:"
echo "✓ Alleen 7 velden invullen"
echo "✓ Automatische RDW lookup"
echo "✓ Slimme schattingen voor alle andere waarden"
echo "✓ Automatische belasting berekening"
echo "✓ Mooie grafieken en duidelijk resultaat"
echo ""
echo "💡 Test het met kenteken: 99-ZZH-3"
