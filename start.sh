#!/bin/bash

echo "🚀 Avvio dei servizi..."

echo "🔧 Avvio Apache..."
sudo service apache2 start

echo "🗄️  Avvio MariaDB..."
sudo service mariadb start

echo ""
echo "✅ Servizi avviati!"
echo ""
echo "📝 phpMyAdmin è disponibile su:"
echo "   http://localhost/phpmyadmin"
echo ""
echo "👤 Credenziali:"
echo "   Utente: utente_phpmyadmin"
echo "   Password: ringraziandoPENNETTA"
echo ""
