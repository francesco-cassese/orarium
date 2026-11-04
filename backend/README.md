# Orarium — backend

[![Backend CI](https://github.com/francesco-cassese/orarium/actions/workflows/backend.yml/badge.svg)](https://github.com/francesco-cassese/orarium/actions/workflows/backend.yml)

API JSON in Laravel per Orarium. Il database MySQL e Redis girano in Docker, con il file `compose.yaml`
nella radice del repository.

## Requisiti

- PHP `^8.4`, come indica `composer.json`. La CI usa PHP 8.4.
- L'estensione `pdo_mysql` di PHP, per collegarsi al database MySQL. La CI la abilita nel workflow.
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
   Lo script `docker/mysql/init/01-test-database.sh` crea il database `orarium_test`, usato dai test, solo quando il volume `mysql_data` è nuovo. Se avevi già il volume, vedi la sezione sotto.

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
   Poi verifica che risponda, come descritto in [Endpoint disponibili](#endpoint-disponibili). La radice `http://localhost:8000` non ha una pagina: il progetto è solo API.

### Se hai già un volume MySQL

Lo script del database di test non viene eseguito su un volume già esistente. Hai due strade:

- Ricreare il volume, da zero:
  ```
  docker compose down -v
  docker compose up -d
  ```
  **Cancella i dati di sviluppo.** Poi rilancia `php artisan migrate` dalla cartella `backend/` per ricreare le tabelle.
- Creare il database a mano, senza cancellare nulla. Dalla radice apri il client MySQL con la password di root, cioè il valore di `MYSQL_ROOT_PASSWORD`:
  ```
  docker compose exec db mysql -uroot -p
  ```
  Nel client `mysql>` incolla le due righe, sostituendo solo `'orarium'@'%'` con l'utente scelto in `MYSQL_USER` (lascia `orarium_test` com'è):
  ```
  CREATE DATABASE IF NOT EXISTS orarium_test;
  GRANT ALL PRIVILEGES ON `orarium\_test`.* TO 'orarium'@'%';
  ```

## Endpoint disponibili

- `GET /api/v1/health`: risponde `{"status":"ok"}` se l'applicazione è in funzione. Non usa il database né Redis.
- `GET /api/v1/ingredients`: tutti gli ingredienti, in `data`, senza paginazione.
- `GET /api/v1/ingredients?search=<testo>`: solo quelli il cui nome contiene il testo, senza distinguere maiuscole, con la collation predefinita di MySQL.
- `GET /api/v1/ingredients/{id}`: un ingrediente, oppure `404` in JSON se l'id non esiste.
- `POST /api/v1/ingredients`: crea un ingrediente (`name`, `unit`, `price_cents`) e risponde `201` con l'ingrediente in `data`.
- `PUT /api/v1/ingredients/{id}`: aggiorna tutti i campi e risponde `200`.
- `DELETE /api/v1/ingredients/{id}`: cancella e risponde `204`, senza corpo.

Ogni ingrediente ha i campi `id`, `name`, `unit` e `price_cents`. `price_cents` è il prezzo di una unità, in centesimi (`120` = 1,20 € al chilo): vedi [D-011](../docs/DECISIONS.md).

Regole in scrittura (POST e PUT): `name` è obbligatorio, fino a 255 caratteri; `unit` è uno tra `g`, `kg`, `ml`, `l`, `pc`; `price_cents` è un intero da 0 a 4294967295. Con dati non validi la risposta è `422`; con un id che non esiste, `PUT` e `DELETE` rispondono `404`.

Per provarlo, con `php artisan serve` avviato, apri [http://localhost:8000/api/v1/health](http://localhost:8000/api/v1/health) nel browser. Per la lista, apri [http://localhost:8000/api/v1/ingredients](http://localhost:8000/api/v1/ingredients): con il database appena creato, dopo `php artisan migrate`, risponde `{"data":[]}`. Per caricare gli ingredienti di esempio, lancia `php artisan migrate:fresh --seed`: crea 20.000 righe (`IngredientSeeder::COUNT`). I dati sono sempre gli stessi, perché il seme di Faker è fisso, e sono coerenti: ogni ingrediente ha la sua unità e le sue varianti, i nomi si ripetono e i nomi distinti sono 80.

## Comandi utili

Dalla radice:
- `docker compose ps`: mostra lo stato dei servizi.
- `docker compose down`: ferma i servizi. I dati restano.
- `docker compose down -v`: ferma i servizi **e cancella anche i dati del database**. Usalo solo per ripartire da zero.

Dalla cartella `backend/`:
- `php artisan migrate:status`: mostra le migrazioni eseguite e verifica il collegamento al database.
- `php artisan migrate`: da rilanciare dopo ogni aggiornamento che porta migrazioni nuove. Il database di sviluppo non si aggiorna da solo, e i test usano un database diverso (`orarium_test`).
- `php artisan migrate:fresh --seed`: **cancella tutte le tabelle del database di sviluppo**, le ricrea e carica gli ingredienti di esempio. Rifiuta di girare in produzione. I test provano il seeder solo con poche righe, mai con 20.000.
- `php artisan serve`: avvia il server di sviluppo su `http://localhost:8000`.
- `composer check`: esegue in sequenza formattazione, analisi statica e test. È il controllo da lanciare prima di aprire una pull request: la CI lo rifarà.
- `composer test`: esegue i test con Pest, dopo aver pulito la cache della configurazione. I test usano il database `orarium_test` su MySQL, quindi richiedono il servizio `db` acceso (`docker compose ps`).
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
- [Convenzioni di nome e di dati](../docs/CONVENTIONS.md): database, API, codice.
- [Decisioni tecniche](../docs/DECISIONS.md): perché il progetto è fatto così.
