#!/bin/bash

echo "🚀 Avvio dei servizi..."

echo "🔧 Avvio Apache..."
sudo service apache2 start

echo "🗄️  Avvio MariaDB..."
sudo service mariadb start

echo ""
echo "✅ Seruvizi avviati!"
echo ""
echo "📝 phpMyAdmin è disponibile su:"
echo "   http://localhost/phpmyadmin"
echo ""
echo "👤 Credenziali:"
echo "   Utente: utente_phpmyadmin"
echo "   Password: ringraziandoPENNETTA"
echo ""



#!CREATE VIEW vista_ordini_clienti AS
#!SELECT 
#!    clienti.id,
#!    clienti.nome,
#!    clienti.email,
 #!   ordini.id AS id_ordine,
#!    ordini.data,
#!    ordini.totale
#! FROM clienti
#! JOIN ordini 
#!     ON clienti.id = ordini.id_cliente;