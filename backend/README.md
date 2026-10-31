# Orarium — backend

API JSON in Laravel per Orarium. Il database MySQL e Redis girano in Docker, con il file `compose.yaml`
nella radice del repository.

## Requisiti

- PHP 8.3 o superiore
- Composer
- Docker Desktop

## Primo avvio

1. Installa le dipendenze PHP:
   ```
   composer install
   ```
2. Crea il file di configurazione (in PowerShell e Git Bash):
   ```
   cp .env.example .env
   ```
3. Genera la chiave dell'applicazione:
   ```
   php artisan key:generate
   ```
4. Crea il file `.env` nella **radice** del repository, copiando il `.env.example` della radice, e inserisci
   le password: le scegli tu, sono solo per il tuo computer e non vanno mai committate.
5. Avvia il database dalla radice del repository:
   ```
   docker compose up -d db
   ```
   Attendi che lo stato diventi `healthy` con `docker compose ps`.
6. Nel file `backend/.env`, imposta `DB_PASSWORD` con lo stesso valore di `MYSQL_PASSWORD` nel `.env` della radice.
7. Crea le tabelle:
   ```
   php artisan migrate
   ```

Il `migrate` va eseguito una sola volta su un database vuoto.

## Comandi utili

- `php artisan serve`: avvia il server di sviluppo su `http://localhost:8000`.
- `php artisan migrate:status`: mostra le migrazioni eseguite e verifica il collegamento al database.
- `docker compose ps`, dalla radice: mostra lo stato dei servizi.

## Documentazione

- [Processo di lavoro](../docs/WORKFLOW.md): issue, branch, commit, pull request.
- [Decisioni tecniche](../docs/DECISIONS.md): perché il progetto è fatto così.
