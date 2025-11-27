# ProgettoInformaticaRicomoto

Progetto Lovable:  https://ricomoto.lovable.app

NOME E COGNOME:
Ouajidi Ilyas

TITOLO:
Ricomoto

TAGLINE:
Tra utenti, per utenti: il mercato dei ricambi usati e nuovi per i motoveicoli.

DESCRIZIONE:
Una piattaforma dedicata ai motociclisti in cerca di pezzi di ricambio ed alle officine, su questa piattaforma le officine avranno la possibilità di caricare i propri pezzi di ricambio ad un certo prezzo in modo che gli utenti oppure altre officine possano prenotarlo per poi andare a comprarlo, con la possibilità di ordinarlo direttamente per farselo arrivare a casa e anche quella di proporre altri prezzi, inoltre le officine avranno il proprio shop con la raccolta dei propri prodotti.

TARGET:
Motociclisti e Meccanici

COMPETITOR:
i siti di compravendita come subito e facebook marketplace 

TECNOLOGIE:
html, php, js, css





CASI D'USO:

1. Login (email + password)

Attore primario: Utente / Officina
Precondizioni: Account già registrato; l'utente conosce email e password.
Flusso principale:

L'attore inserisce email e password.

Il sistema autentica le credenziali.

Il sistema apre la sessione e reindirizza alla dashboard appropriata (utente/officina).
Estensioni:

Credenziali errate → messaggio di errore, possibilità di recupero password.

Account non verificato → richiesta verifica email.

2. Registrazione — Utente

Attore primario: Potenziale utente (privato)
Dati richiesti: Nome, Email, Password.
Precondizioni: Nessun account esistente con la stessa email.
Flusso principale:

L'utente compila il form (nome, email, password).

Il sistema crea l'account e invia email di verifica (opzionale).

Utente verifica email e può effettuare il login.
Estensioni: email già usata, password non sufficiente → messaggi di errore.

3. Registrazione — Officina

Attore primario: Officina (manager)
Dati richiesti: Nome officina, Email, Password, Partita IVA, Indirizzo.
Precondizioni: Nessun account officina con la stessa email/partita IVA.
Flusso principale:

L'officina compila il form con i dati richiesti.

Il sistema crea l'account officina e, se richiesto, invia verifica/validazione (es. controllo partita IVA).

L'officina può configurare lo shop (logo, descrizione, orari).
Estensioni: Partita IVA non valida → richiesta correzione; approvazione manuale se prevista.

4. Caricamento prodotto (officina)

Attore primario: Officina
Dati prodotto: Nome, Descrizione, Prezzo, Opzione spedizione/solo ritiro, Foto, Disponibilità, Categoria.
Precondizioni: Officina autenticata e con shop attivo.
Flusso principale:

Officina apre form “Nuovo prodotto”.

Compila dati e carica foto.

Salva: il prodotto viene pubblicato nello shop.
Estensioni: Campo mancante → validazione; possibilità di bozza prima pubblicazione; inserimento variante (es. colore, condizione usato/nuovo).

5. Visualizza/catalogo prodotto

Attore primario: Utente / Altra officina
Precondizioni: Prodotti pubblicati nello shop.
Flusso principale:

L'attore naviga o cerca un prodotto.

Il sistema mostra dettagli (foto, descrizione, prezzo, venditore, modalità ritiro/spedizione).
Estensioni: Filtri/ordinamenti; prodotti simili suggeriti.

6. Prenota prodotto (ritiro in negozio)

Attore primario: Utente / Officina acquirente
Precondizioni: Prodotto disponibile e contrassegnato come ritirabile. Utente autenticato (o guest con dati).
Flusso principale:

L'utente seleziona “Prenota” e sceglie data/ora (se richiesto).

Il sistema invia notifica all’officina e crea una prenotazione.

Officina conferma la prenotazione (o la modifica).
Estensioni: Officina rifiuta → utente riceve notifica e opzioni alternative.

7. Acquisto / Ordine con spedizione

Attore primario: Utente / Officina acquirente
Precondizioni: Prodotto disponibile per vendita; metodo di pagamento configurato.
Flusso principale:

L'attore avvia checkout, inserisce indirizzo di spedizione e paga.

Il sistema crea ordine, addebita pagamento e notifica l’officina.

Officina prepara e spedisce il prodotto; tracking/order status aggiornati.
Estensioni: Pagamento fallito → ordine annullato; richiesta rimborso/reso.

8. Offerta prezzo su un prodotto (negoziazione)

Attore primario: Utente / Officina acquirente
Precondizioni: Prodotto che permette offerte; venditore disponibile a negoziare.
Flusso principale:

L'acquirente invia un'offerta indicando prezzo proposto.

Venditore riceve notifica e può accettare, rifiutare o contro-offrire.

Se accettata, si crea un ordine/prenotazione al prezzo concordato.
Estensioni: Scadenza offerta; storico offerte; messaggistica privata per trattativa.

9. Gestione account utente

Attore primario: Utente
Funzionalità: Modifica profilo (nome, email), gestione indirizzi, cronologia ordini, wishlist, impostazioni notifiche.
Precondizioni: Utente autenticato.
Flusso principale: l’utente aggiorna i dati e salva; sistema valida e applica modifiche.
Estensioni: Verifica email se cambia; controllo dati sensibili.

10. Gestione account officina (shop)

Attore primario: Officina
Funzionalità: Modifica dati officina, gestione prodotti (crea/modifica/rimuovi), visualizzazione ordini, statistiche shop, gestione spedizioni/ritiri, impostazioni shop.
Precondizioni: Officina autenticata e autorizzata.
Flusso principale: L’officina accede al pannello e compie azioni amministrative; sistema registra modifiche e aggiorna visibilità.
Estensioni: Ruoli multipli (es. dipendenti con permessi limitati).

11. Gestione ordini (venditore + acquirente)

Attore primario: Officina (venditore), Utente (acquirente)
Funzionalità venditore: Conferma, evasione, cambio stato, comunicazione spedizione, emissione fattura.
Funzionalità acquirente: Vedere stato ordine, tracking, richiedere reso.
Precondizioni: Ordine creato.
Flusso principale: Aggiornamento stato dall’officina → notifica all’acquirente.
Estensioni: Resi, rimborsi, controversie.

12. Recensisci prodotto / shop

Attore primario: Utente autenticato che ha acquistato/prenotato (idealmente)
Precondizioni: L'utente ha ricevuto il prodotto o completato la transazione.
Flusso principale:

Utente lascia valutazione (stelle) e testo della recensione per prodotto o shop.

Il sistema verifica (opzionale) che l’utente abbia comprato e pubblica la recensione.
Estensioni: Segnalazione review, moderazione, risposta officina.

13. Supporto chat con l'officina (messaggistica)

Attore primario: Utente, Officina
Precondizioni: Utente autenticato; opzionale possibilità di chat anonima pre-acquisto.
Flusso principale:

L’utente apre chat dalla pagina prodotto o profilo officina.

Messaggi inviati in tempo reale / asincrono; notifiche push/email.

Possibilità di inviare foto, proposte d’offerta, coordinate per ritiro/spedizione.
Estensioni: Messaggistica legata a un ordine; trasferimento chat a un altro membro dello staff; blocco/ban se abuso.
