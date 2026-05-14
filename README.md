# 🏍️ Ricomoto - Marketplace Multitenant per Ricambi Moto

## 📋 Descrizione del Progetto

**Ricomoto** è una piattaforma e-commerce multitenant progettata per il settore dei ricambi e accessori per motociclette. Il sistema consente a più officine e negozi di gestire il proprio catalogo prodotti indipendentemente, mantenendo una struttura centralizzata e scalabile.

### 🎯 Obiettivi Principali
- Creare un marketplace dove le officine possono vendere ricambi e accessori moto
- Gestire ordini, offerte e comunicazioni tra clienti e venditori
- Fornire un sistema centralizzato di amministrazione per monitorare tutti i tenant
- Supportare una struttura multitenant con isolamento dei dati

## 👥 Profili Utente

Il progetto supporta tre tipologie di utenti:

### 👤 Cliente
- Naviga il catalogo prodotti globale
- Acquista da diversi shop (tenant)
- Comunica con le officine tramite chat
- Lascia recensioni
- Crea offerte personalizzate


### 🔧 Officina/Shop Owner
- Crea e gestisce il proprio tenant shop
- Carica e gestisce il catalogo prodotti
- Gestisce ordini ricevuti
- Visualizza notifiche di ordini e messaggi
- Accede alla propria dashboard

### 👨‍💼 Admin
- Accede alla SuperDashboard centralizzata
- Gestisce tutti i tenant della piattaforma
- Monitora attività globale e statistiche
- Gestisce utenti amministratori

## 🏗️ Architettura Multitenant

Il progetto utilizza una architettura multitenant con le seguenti caratteristiche:

- **Multitenancy**: Ogni officina è un tenant separato con i propri dati
- **Routing Tenant-Aware**: 
  - Via host: `http://nomeoffcina.localhost/ricomoto/`
  - Fallback: `http://localhost/ricomoto/?tenant=nomeoffcina`
- **Isolamento Dati**: I dati di ogni tenant sono mantenuti separati nel database
- **API JWT**: Autenticazione token-based per le operazioni API

## 🚀 Tecnologie Utilizzate

- **Backend**: PHP 7+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Autenticazione**: JWT (JSON Web Tokens)
- **Architettura**: Multitenant con routing dinamico

## 📦 Struttura del Progetto

```
ricomoto/
├── api/                    # Endpoint API
│   ├── jwt.php            # Gestione token JWT
│   ├── permissions.php    # Controllo permessi
│   ├── refresh.php        # Refresh token
│   └── token.php          # Generazione token
├── assets/                # Risorse statiche (logo, immagini)
├── uploads/               # Caricamento prodotti e loghi
├── index.php              # Homepage
├── login.php              # Login utente
├── register.php           # Registrazione
├── dashboard.php          # Dashboard cliente
├── superdashboard.php     # SuperDashboard admin
├── crea_shop.php          # Creazione nuovo shop
├── gestisci_shop.php      # Gestione shop e prodotti
├── shop.php               # Storefront tenant
├── ordini.php             # Ordini cliente
├── gestisci_ordine.php    # Gestione ordini
├── offerta.php            # Sistema offerte
├── chat_ordine.php        # Chat tra clienti e officine
├── db.php                 # Configurazione database
├── auth.php               # Logica autenticazione
└── ...altri file PHP
```

## ⚙️ Setup e Installazione

### Prerequisiti
- PHP 7.0 o superiore
- MySQL 5.7 o MariaDB 10.3+
- XAMPP o server locale equivalente

PER IL RESTO C'E IN ALLEGATO NELLA REPO UN VIDEO TUTORIAL SULL'UTILIZZO CON XAMPP

### 🔑 Credenziali Admin di Prova
- **Email**: `admin@ricomoto.local`
- **Password**: `admin123`



## 🔍 Funzionalità Principali

### Per i Clienti
- ✅ Registrazione e login
- ✅ Visualizzazione catalogo globale
- ✅ Navigazione shop individuali
- ✅ Comunicazione via chat con officine
- ✅ Lascia recensioni per i shop

### Per le Officine
- ✅ Creazione shop personalizzato
- ✅ Gestione catalogo prodotti
- ✅ Gestione ordini ricevuti
- ✅ Sistema di offerte personalizzate
- ✅ Chat con clienti
- ✅ Dashboard statistiche vendite
- ✅ Notifiche in tempo reale

### Per gli Admin
- ✅ SuperDashboard centralizzata
- ✅ Gestione tenant
- ✅ Monitora utenti
- ✅ Statistiche globali
- ✅ Gestione permessi

## 🔐 Sicurezza

- Autenticazione tramite JWT
- Password hashate con algoritmi sicuri
- Validazione input su tutte le form
- Isolamento dati per tenant


**Ultima modifica**: Maggio 2026
