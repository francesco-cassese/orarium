# Orarium — backend

API JSON in Laravel per Orarium. Il database MySQL e Redis girano in Docker, con il file `compose.yaml`
nella radice del repository.

## Requisiti

- PHP 8.4-8.5 (testato con 8.4)
- Composer
- Docker Desktop

## Primo avvio

### 1. Nella radice del repository: database e Redis

1. Copia il file di esempio:
   ```
   cp .env.example .env
   ```
2. Apri `.env` e scegli le password per `MYSQL_ROOT_PASSWORD` e `MYSQL_PASSWORD`. Le scegli tu: servono solo sul tuo computer e non vanno mai committate.
3. Avvia i servizi:
   ```
   docker compose up -d
   ```
4. Attendi che `db` e `redis` diventino `healthy`:
   ```
   docker compose ps
   ```

### 2. Nella cartella `backend/`: applicazione

1. Installa le dipendenze PHP:
   ```
   composer install
   ```
2. Crea il file di configurazione:
   ```
   cp .env.example .env
   ```
3. Genera la chiave dell'applicazione:
   ```
   php artisan key:generate
   ```
4. Apri `backend/.env` e imposta `DB_PASSWORD` con lo stesso valore di `MYSQL_PASSWORD` nel `.env` della radice.
   Se hai cambiato `MYSQL_USER` o `MYSQL_DATABASE`, aggiorna anche `DB_USERNAME` e `DB_DATABASE` con gli stessi valori.
5. Crea le tabelle:
   ```
   php artisan migrate
   ```
   Si può rieseguire: applica solo le migrazioni non ancora eseguite.
6. Avvia il server:
   ```
   php artisan serve
   ```
   Poi apri [http://localhost:8000](http://localhost:8000): devi vedere la pagina di benvenuto.

## Comandi utili

Dalla radice:
- `docker compose ps`: mostra lo stato dei servizi.
- `docker compose down`: ferma i servizi. I dati restano.
- `docker compose down -v`: ferma i servizi **e cancella anche i dati del database**. Usalo solo per ripartire da zero.

Dalla cartella `backend/`:
- `php artisan migrate:status`: mostra le migrazioni eseguite e verifica il collegamento al database.
- `php artisan serve`: avvia il server di sviluppo su `http://localhost:8000`.
- `composer check`: esegue in sequenza formattazione, analisi statica e test. È il controllo da lanciare prima di aprire una pull request: la CI lo rifarà.
- `composer test`: esegue i test con Pest, dopo aver pulito la cache della configurazione.
- `composer format`: formatta il codice con Pint.
- `composer format:check`: verifica la formattazione senza modificare i file. Se fallisce, lancia `composer format` e riprova.
- `composer analyse`: esegue l'analisi statica con Larastan.

## Collegarsi al database con un client

Con MySQL Workbench o un altro client usa:
- host: `127.0.0.1`
- porta: `3307`
- utente: il valore di `MYSQL_USER`
- password: il valore di `MYSQL_PASSWORD`
- database: il valore di `MYSQL_DATABASE`

## Documentazione

- [Processo di lavoro](../docs/WORKFLOW.md): issue, branch, commit, pull request.
- [Decisioni tecniche](../docs/DECISIONS.md): perché il progetto è fatto così.
