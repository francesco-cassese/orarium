# Registro delle decisioni di progetto

Ogni scelta tecnica con compromessi viene registrata nel momento in cui è presa, con il contesto, le alternative
valutate, la decisione, le sue conseguenze e le condizioni per rivederla.

## D-001 — Repository monorepo (livello 0)

**Contesto.** Backend e frontend evolvono insieme: una funzionalità tocca quasi sempre entrambi. Con due repository
servirebbero due issue, due PR e un ordine di merge da coordinare, e la documentazione trasversale (prestazioni,
decisioni, benchmark) andrebbe duplicata.

**Opzioni valutate.**
- Monorepo con `backend/`, `frontend/`, `benchmarks/` e `docs/`: **scelta**.
- Due repository separati, uno per l'API e uno per l'interfaccia: scartata.

**Decisione.** Monorepo. Ogni funzionalità è una sola PR revisionabile e il contratto API resta allineato al frontend.

**Conseguenze.** La CI ha due job, ciascuno attivato solo dai percorsi della propria cartella (`paths:`). Non serve
ancora nessuno strumento di gestione del monorepo (Nx, Turborepo).

**Rivalutare se.** Frontend e backend hanno cicli di rilascio o team indipendenti.

## D-002 — Stile dell'interfaccia (livello 0, frontend)

**Contesto.** Lo stile determina la dimensione del CSS caricato dal browser, la coerenza visiva e la quantità di CSS
da mantenere a mano.

**Opzioni valutate.**
- CSS scritto a mano: scartata.
- Bootstrap 5 personalizzato con SCSS: **scelta**.
- Libreria di componenti già stilizzati (ad esempio Material UI): scartata.
- Approccio utility-first (ad esempio Tailwind): scartata.

**Decisione.** Bootstrap 5 personalizzato con SCSS:
- variabili Sass sovrascritte per ottenere un tema proprio;
- si importano solo i moduli usati (griglia, bottoni, form, tabelle);
- gli stili dei componenti stanno in moduli `*.module.scss`, con `@use` al posto di `@import`;
- classi Bootstrap dirette come impostazione predefinita, `react-bootstrap` solo per i componenti con comportamento
  (modali, menu a tendina);
- nessun jQuery.

**Conseguenze.** La dimensione del CSS va misurata con Bootstrap completo e con i soli moduli usati. Il risultato
entra nella documentazione delle prestazioni del frontend.

**Rivalutare se.** Il CSS diventa troppo pesante, oppure personalizzare Bootstrap richiede più lavoro che scrivere
stili propri.

## D-003 — Gestore di pacchetti del frontend (livello 0)

**Contesto.** La CI reinstalla le dipendenze a ogni pull request. Velocità di installazione, occupazione su disco e
installazioni riproducibili pesano a ogni ciclo di lavoro.

**Opzioni valutate.**
- npm: scartata.
- pnpm: **scelta**.
- yarn: scartata.

**Decisione.** pnpm. Il file `pnpm-lock.yaml` è versionato; la CI esegue `pnpm install --frozen-lockfile`; il campo
`packageManager` in `package.json` fissa la versione di pnpm.

**Conseguenze.** Sviluppo e CI usano la stessa versione di pnpm, gestita con Corepack. Il lockfile cambia solo con una
modifica esplicita alle dipendenze.

**Rivalutare se.** Il lockfile diventa fonte di conflitti frequenti, oppure serve un gestore con migliore supporto al
monorepo.

## D-004 — Ambiente di sviluppo: servizi in Docker (livello 0)

**Contesto.** Il progetto userà MySQL per i dati e Redis per cache e code. L'ambiente deve essere ripetibile sul
computer di sviluppo e, in seguito, nella CI. Le versioni fluttuanti (`latest`) cambiano senza preavviso.

**Opzioni valutate.**
- Database e Redis installati direttamente sul sistema operativo: scartata, dipende dalla macchina.
- Laravel in un contenitore Docker: scartata per ora, aggiunge un livello tra l'editor e il codice. Si può rivalutare.
- Immagine con tag generico (`redis:8`, `redis:latest`): scartata, la versione cambia nel tempo.
- Immagine con patch fissata (`redis:8.2.6`): **scelta**, come già fatto per MySQL.
- Client PHP `phpredis` (estensione in C): scartata per ora, perché richiede l'installazione dell'estensione, che non è presente sull'ambiente di sviluppo.
- Client PHP `predis` (libreria pura PHP): **scelta**, perché funziona senza compilazioni.

**Decisione.** Redis gira in Docker con l'immagine `redis:8.2.6`, definita in `compose.yaml`, con la porta
`6379` legata a `127.0.0.1`, quindi non raggiungibile dalla rete locale, un volume `redis_data` e un healthcheck con `redis-cli ping`.

Il client PHP usato da Laravel è **predis** (`predis/predis` in `composer.json`, `REDIS_CLIENT=predis` nel `.env`),
perché il template Laravel usa **phpredis** come default (`REDIS_CLIENT=phpredis`), ma l'estensione non è installata
sull'ambiente di sviluppo. Predis è una libreria PHP che va aggiunta al progetto con `composer require predis/predis`:
il template non la include.

MySQL gira in Docker con l'immagine `mysql:8.4.13`, con la porta `3307` legata a `127.0.0.1` (per evitare collisioni con un
MySQL già installato), quindi non raggiungibile dalla rete locale. Ci sono un volume `mysql_data` e un healthcheck con
`mysqladmin ping`. Laravel gira in locale, fuori da Docker, e si collega ai servizi su `127.0.0.1`.

Le credenziali di sviluppo stanno in `.env` (radice e `backend/`), che non sono tracciati da Git. I file `.env.example`
documentano le variabili con valori vuoti per le password.

**Conseguenze.** Ogni aggiornamento di Redis o MySQL richiede una modifica esplicita del tag e una verifica dei test.
Il servizio Redis non ha password: va bene in locale, ma non va esposto in rete.

**Rivalutare se.** Esce una patch di sicurezza per le versioni in uso, il progetto passa a un ambiente non locale,
oppure avviare Laravel in locale diventa troppo complicato da mantenere.

**Nota per il futuro.** phpredis è un'estensione scritta in C e di solito è più veloce di predis. È un candidato per
un livello di ottimizzazione: la misura va fatta prima con predis e poi con phpredis, con lo stesso carico e gli stessi
dati, registrando i risultati nella documentazione delle prestazioni.

## D-005 — Strumenti di qualità del backend (livello 0)

**Contesto.** Il backend deve avere test automatici, formattazione uniforme e analisi statica fin dall'inizio, perché le funzionalità arriveranno su questa base. Il template Laravel porta PHPUnit e Pint, ma nessun analizzatore statico.

**Opzioni valutate.**
- Test con PHPUnit diretto: scartata, più verbosa da scrivere e leggere. Pest 5 esegue comunque i test PHPUnit esistenti.
- Test con Pest 5: **scelta**.
- Formattazione con Pint, preset `laravel`: **scelta**, con la regola `declare_strict_types` aggiunta.
- Analisi statica con Larastan al livello 5: scartata. Il codice di partenza è minimo, quindi il livello massimo passa senza costo, e alzare il livello più tardi costa di più man mano che il codice cresce.
- Analisi statica con Larastan al livello 10: **scelta**.

**Decisione.**
- Pest 5 (`pestphp/pest` e `pestphp/pest-plugin-laravel`, vincolo `^5.0`), con `"php": "^8.4"` in `composer.json`: Pest 5 richiede PHP 8.4, quindi il requisito del backend è PHP 8.4-8.5.
- Pint con preset `laravel` e regola `declare_strict_types`, in `backend/pint.json`.
- Larastan al livello 10 in `backend/phpstan.neon`. Regola del progetto: si corregge il codice, non si abbassa il livello.
- Script Composer `test`, `format`, `format:check`, `analyse` e `check`, che esegue in sequenza formattazione, analisi e test.

**Conseguenze.** La CI (issue #8) userà PHP 8.4. `declare_strict_types` aggiunge `declare(strict_types=1)` a ogni file PHP. Il livello 10 va mantenuto anche quando il codice cresce: un errore si corregge nel codice. Lo script `test` pulisce la cache della configurazione prima di eseguire Pest, così i test non girano per errore sul database di sviluppo. Larastan analizza oggi solo `app/` (`paths` in `phpstan.neon`): `routes/`, `config/` e `database/` non sono ancora coperti.

**Punto aperto.** I test usano per ora la configurazione di `phpunit.xml` del template. Il database dei test (SQLite in memoria o MySQL dedicato) si decide al Livello 1, quando arrivano i primi modelli.

**Rivalutare se.** Pest o Larastan cambiano linea di versione in modo incompatibile, oppure il progetto deve supportare PHP 8.3: in quel caso Pest tornerebbe alla linea 4.

## Registro

| ID    | Data       | Decisione                               | Livello |
|-------|------------|-----------------------------------------|---------|
| D-001 | 2026-10-30 | Monorepo                                | 0       |
| D-002 | 2026-10-30 | Stile: Bootstrap 5 + SCSS               | 0       |
| D-003 | 2026-10-30 | Gestore pacchetti: pnpm                 | 0       |
| D-004 | 2026-10-31 | Ambiente di sviluppo: servizi in Docker | 0       |
| D-005 | 2026-10-31 | Strumenti di qualità del backend        | 0       |
