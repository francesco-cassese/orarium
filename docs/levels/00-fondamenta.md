# Livello 0 — Fondamenta

## Obiettivo

Con questo livello voglio avere un repository ordinato dal primo giorno: un processo di lavoro chiaro, un ambiente di sviluppo che si riproduce su un'altra macchina, controlli automatici su backend e frontend, e una prima misura di partenza. Viene prima di qualsiasi funzione perché ogni miglioramento dei livelli successivi va confrontato con questa misura, e senza i controlli non c'è modo di accorgersi se una modifica peggiora le cose.

## Cosa ho fatto

Le issue e le pull request più importanti, tutte fuse su `main`:

- Struttura del repository e processo di lavoro: #1 (PR #12), con le regole per le modifiche piccole senza issue (PR #22).
- Backend Laravel di base: #2 (PR #13), e ambiente di sviluppo con MySQL e Redis in Docker: #3 (PR #14).
- Strumenti di qualità del backend, Pest, Pint e Larastan: #4 (PR #17).
- Endpoint `/api/v1/health`: #5 (PR #19), e una modifica successiva alla classe `HealthController`, resa `final`, senza issue (PR #20).
- Frontend React con Vite: #6 (PR #21).
- Strumenti di qualità del frontend, ESLint, Prettier e Vitest: #7 (PR #23).
- CI del backend e del frontend con GitHub Actions: #8 (PR #26) e #9 (PR #25). I due controlli si chiamano `frontend` e `backend` (PR #27) e sono richiesti dal ruleset `protect-main`, un'impostazione fatta su GitHub e registrata in [REPOSITORY-SETUP.md](../REPOSITORY-SETUP.md) (PR #28).
- Rimozione dei residui del template Laravel, cioè Vite, la welcome page e gli script `setup` e `dev`: #15 (PR #29).
- Prima misura di prestazioni con k6, per il backend e per il frontend: #10 (PR #30).

## Decisioni

Tutte le decisioni sono in [DECISIONS.md](../DECISIONS.md). Le tre che contano di più in questo livello:

- D-001: un monorepo, con backend, frontend, benchmarks e documentazione nello stesso repository.
- D-004: MySQL e Redis in Docker, con versioni fissate e porte legate a `127.0.0.1`.
- D-009: la CI del backend, con i test su SQLite in memoria e una chiave di test in `phpunit.xml`.

## Misure

Sul mio computer, con `php artisan serve`, `APP_DEBUG=true` e OPcache non verificata, una richiesta a Laravel impiega circa 100 ms. Uno script PHP minimo, servito dal server integrato di PHP, ne impiega circa 2. Questa è una misura di partenza, non un limite di Laravel: il tempo è in quello che il framework fa a ogni richiesta, e il prossimo passo è capire cosa.

Il bundle di produzione del frontend pesa 219.68 kB, 68.66 kB compressi con gzip.

Metodo, ambiente e risultati completi sono in [PERFORMANCE.md](../PERFORMANCE.md) per il backend e in [FRONTEND-PERF.md](../FRONTEND-PERF.md) per il frontend.

## Cosa ho imparato

Ho imparato più dagli errori che dalle cose andate bene. Questi sono gli episodi che mi hanno cambiato il modo di lavorare.

**Una corsa rotta poteva sembrare riuscita.** Lanciando k6 da Git Bash, Git ha trasformato `/up` in un percorso Windows. La richiesta non partiva, e la corsa usciva con codice 0, come se fosse andata bene. Ho aggiunto una soglia sul numero di richieste, e ora la stessa corsa esce con 99. Quando aggiungo un controllo, lo provo prima su un caso che deve fallire.

**Il ritmo della misura fa parte del risultato.** Un utente virtuale senza pause produce più di mille richieste al secondo. Su un server che chiude ogni connessione, come il server di PHP, Windows esaurisce le porte locali e le corse successive falliscono. Ho dovuto aggiungere una pausa per i controlli, e ho scritto il ritmo nel metodo, perché senza non si può confrontare nulla.

**Un warning va letto prima di ignorarlo.** In CI il file `.env` non c'era, e Pest mostrava due warning ([run](https://github.com/francesco-cassese/orarium/actions/runs/41333626162)): il test passava lo stesso. Il warning diceva che qualcosa mancava. Ho aggiunto lo step che crea `.env` da `.env.example` (PR #26), e il motivo è scritto in D-009.

**Due controlli con lo stesso nome creano ambiguità.** Nel ruleset i controlli si riconoscono dal nome del job, e i due workflow avevano entrambi un job chiamato `check`. L'ho visto nei check run del commit. Ho rinominato i job in `frontend` e `backend` prima di richiederli.

**Un numero senza metodo non serve a nessuno.** Nei documenti e nelle PR ho scritto più volte affermazioni che non avevo verificato: un hash, una data, il numero di richieste. Ogni valore ora ha dietro il comando o il file da cui viene, e lo controllo prima di scriverlo.

## Limiti e cose rimaste aperte

- Le misure sono fatte su un solo computer e in una sola sessione. Non rappresentano un ambiente di produzione.
- Alla chiusura del livello il database dei test era ancora da decidere (SQLite in memoria oppure MySQL dedicato). Deciso poi al Livello 1: MySQL dedicato (D-010).
- Prima di un deploy vanno verificati due punti del backend. Il CORS è da verificare: nel repository non c'è un `config/cors.php`, quindi vale il default del framework. L'header `X-Powered-By` è da verificare con `curl -I`: se mostra la versione di PHP, va disattivato con `expose_php = Off`. In `backend/.env.example` `APP_DEBUG` è `true`.
- Le azioni della CI sono fissate a SHA, ma nel repository non c'è un `.github/dependabot.yml` che le aggiorni. Anche `composer audit` e `pnpm audit` non sono nei workflow.

## Prossimo livello

Il Livello 1 è la milestone `L01 Ingredients`: parte con una versione semplice e la sua misura di partenza.
