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

**Conseguenze.** La CI ha due job distinti, uno per cartella (D-008, D-009). Non serve ancora nessuno strumento di gestione del monorepo (Nx, Turborepo).

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

**Aggiornamento (4 novembre 2026).** Bootstrap 5.3.8 si importa con `@import` e usa ancora funzioni globali di Sass, quindi la regola «`@use` al posto di `@import`» vale per i nostri moduli e non per Bootstrap. In `frontend/src/styles/main.scss` si importano solo le parti usate; in `vite.config.js` sono silenziati gli avvisi di deprecazione `import`, `global-builtin`, `color-functions` e `if-function`, perché vengono dal codice di Bootstrap e rendevano illeggibile l'output della build (oltre 270 avvisi). Dart Sass 3.0 toglierà `@import`: `sass` è con `^1.x`, quindi non passa da solo alla 3.0. **Rivalutare se** esce una versione di Bootstrap che usa `@use`, oppure se `sass` deve salire alla 3.0.

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

**Punto aperto (chiuso da D-010).** Il database dei test era da decidere al Livello 1. D-010 sceglie MySQL dedicato.

**Rivalutare se.** Pest o Larastan cambiano linea di versione in modo incompatibile, oppure il progetto deve supportare PHP 8.3: in quel caso Pest tornerebbe alla linea 4.

## D-006 — Linguaggio del frontend (livello 0, frontend)

**Contesto.** Il frontend è stato creato con la variante JavaScript del template React di Vite. L'obiettivo del progetto è approfondire React: rendering, prestazioni, gestione dello stato. Introdurre TypeScript nello stesso momento sposterebbe l'attenzione dal funzionamento di React al linguaggio.

**Opzioni valutate.**
- TypeScript fin da subito: scartata per ora, aggiunge configurazione e tipi prima che ci siano funzionalità da tipizzare.
- JavaScript fino alla fine del Livello 1, poi migrazione a TypeScript: **scelta**.
- JavaScript senza migrazione prevista: scartata, perderemmo i controlli sui tipi quando il progetto cresce.

**Decisione.** Il frontend è in JavaScript. La migrazione a TypeScript avviene quando le funzionalità di base del Livello 1 sono complete, prima di iniziare il Livello 2, come lavoro dedicato con una sua issue.

**Conseguenze.** Fino a quella data il compilatore non controlla i tipi. Per non perdere la documentazione dei dati, la forma delle risposte dell'API va descritta con commenti JSDoc vicino al codice che la usa: alla migrazione quei commenti diventano tipi. Le componenti nuove restano semplici, così la migrazione a TypeScript richiede meno lavoro.

**Rivalutare se.** Il frontend cresce più in fretta del previsto, oppure emergono errori di tipo che TypeScript avrebbe evitato.

## D-007 — Strumenti di qualità e test del frontend (livello 0, frontend)

**Contesto.** Il frontend ha bisogno di formattazione uniforme, test automatici e fini di riga coerenti tra le macchine di sviluppo e la CI. Il template Vite non fornisce nessuno di questi strumenti.

**Opzioni valutate.**
- Vitest per i test: **scelta**. Jest scartata: con Jest si configurano due pipeline distinte, una per Vite e una per i test, con molte duplicazioni. Vitest usa la stessa configurazione di `vite.config.js` e la stessa pipeline di trasformazione ([documentazione](https://vitest.dev/guide/why)).
- Testing Library con jsdom per i componenti: **scelta**.

**Decisione.**
- **Prettier** alla versione esatta `3.9.10`, senza `^`. La [guida di installazione](https://prettier.io/docs/install) consiglia una versione esatta, perché anche una patch di Prettier può cambiare la formattazione. È l'unica dipendenza con versione esatta: le altre usano `^`, perché il lockfile garantisce la riproducibilità.
- **Regole di `frontend/.prettierrc`**, confrontate con i [valori di default](https://prettier.io/docs/options):
  - `semi: false` (default `true`) e `singleQuote: true` (default `false`) cambiano il comportamento predefinito.
  - `trailingComma: "all"`, `printWidth: 80` ed `endOfLine: "lf"` coincidono con il default.
  - `endOfLine: "lf"` corrisponde ai fini di riga che `.gitattributes` impone nel repository.
- **Vitest** con `vitest run` per il comando `test` (una sola esecuzione) e `vitest watch` per `test:watch`. Configurazione in `frontend/vite.config.js`: `environment: 'jsdom'` e `setupFiles` per `src/test/setup.js`.
- **Testing Library**: i test cercano gli elementi per ruolo con `getByRole`, che la [documentazione](https://testing-library.com/docs/queries/byrole) indica come query preferita perché somiglia all'esperienza dell'utente. Si verifica ciò che l'utente vede, non i dettagli interni dei componenti. Il `cleanup` dopo ogni test sta in `frontend/src/test/setup.js`.
- **Fini di riga LF**: `.gitattributes` alla radice con `* text=auto eol=lf` e `.editorconfig` con `end_of_line = lf`. La regola era in `backend/.gitattributes` ed è stata spostata alla radice, così vale per tutto il monorepo. Con `text=auto` Git normalizza i file testuali a LF nel repository ([manuale gitattributes](https://git-scm.com/docs/gitattributes)); con `eol=lf` il working tree li riceve con LF.

**Conseguenze.** Aggiornare Prettier richiede una modifica esplicita della versione e una riformattazione deliberata. Sui file già esistenti con CRLF la normalizzazione si applica con `git add --renormalize .`, come indica il manuale di Git. `pnpm check` esegue lint, controllo di formattazione e test in sequenza.

**Rivalutare se.** Aggiornare a mano la versione esatta di Prettier diventa un peso, oppure Vitest smette di condividere la configurazione di Vite e diventa conveniente tornare a Jest.

## D-008 — Workflow di CI del frontend (livello 0, frontend)

**Decisione.**
- Azioni bloccate per commit SHA, con il tag come commento (`actions/checkout` v7.0.1, `pnpm/setup` v3.1.2).
- Nessun filtro `paths`: il workflow parte su ogni PR. Quando il controllo sarà obbligatorio nel ruleset, una PR senza modifiche a `frontend/` non produrrebbe il controllo richiesto e bloccherebbe il merge. Costo: un run in più sulle PR di sola documentazione.
- Node 22, major LTS (`runtime: node@22`). Il log di `node --version` mostra la patch installata.
- Job `frontend`: nome del controllo da richiedere nel ruleset quando sarà obbligatorio. Un nome distinto da quello del backend evita ambiguità in quel momento.

## D-009 — Workflow di CI del backend (livello 0, backend)

**Decisione.**
- Azioni bloccate per commit SHA, come in D-008.
- Nessun filtro `paths`, per lo stesso motivo di D-008.
- PHP 8.4, come richiede `composer.json` e Pest 5 (D-005). Coverage disattivato con `coverage: none`: la CI non genera report di copertura.
- Test su SQLite in memoria (superata da D-010: i test usano MySQL e l'estensione `pdo_mysql`).
- APP_KEY di test in `phpunit.xml`: i test non la leggono dal `.env`. Il file `.env` deve comunque esistere in CI, perché senza di esso phpdotenv emette un avviso che Pest riporta come warning. Per questo lo step `cp .env.example .env` lo crea prima di `composer check`.
- Cache di Composer con chiave sull'hash di `composer.lock`.
- `composer validate --strict` come primo controllo: verifica che `composer.json` sia valido e che il lockfile sia allineato, l'equivalente di `--frozen-lockfile` nel frontend.
- Job `backend`, distinto da quello del frontend (D-008): nomi unici evitano ambiguità quando i controlli diventano obbligatori nel ruleset.

## D-010 — Database dei test: MySQL (livello 1)

**Contesto.** Il template Laravel esegue i test su SQLite in memoria (D-009). Il progetto usa MySQL 8.4 in sviluppo
(D-004), ed è il motore previsto anche in produzione. Tipi di dati, collation, vincoli e funzioni SQL non coincidono tra i due motori: un test che
passa su SQLite può fallire su MySQL. La scelta del database dei test era rimandata al Livello 1 (D-005).

**Opzioni valutate.**
- SQLite in memoria, come in `phpunit.xml` del template: scartata, il motore è diverso da quello usato davvero.
- MySQL 8.4 dedicato ai test (database separato, stesso motore dello sviluppo): **scelta**.
- SQLite nei test locali e MySQL solo in CI: scartata. Tornerebbero due motori, e un test verde in locale potrebbe diventare rosso in CI.
- Stesso database di sviluppo: scartata, i test ricreano le tabelle e cancellerebbero i dati locali.

**Decisione.** I test usano MySQL 8.4 con un database separato da quello di sviluppo (ad esempio `orarium_test`): in locale sullo stesso servizio Docker, in CI su un servizio MySQL equivalente.

**Conseguenze.**
- `phpunit.xml` punta a MySQL, e D-009 è aggiornata di conseguenza.
- La CI avvia un servizio MySQL 8.4 con healthcheck.
- Il container deve creare `orarium_test` e concedere i permessi all'utente applicativo: in `compose.yaml`, `MYSQL_USER` ha permessi solo su `MYSQL_DATABASE`, quindi senza questo passo i test falliscono con "Access denied".
- I test sono più lenti di SQLite in memoria. Va misurato con i primi test di integrazione.

**Rivalutare se.** Il tempo di esecuzione in CI su MySQL rallenta in modo sensibile il ciclo di PR, oppure emergono
test che richiedono proprio SQLite.

## D-011 — Denaro in centesimi interi (livello 1)

**Contesto.** Il progetto salva prezzi: ingredienti ora, ricette e ordini dopo. Un errore di arrotondamento nei soldi si somma a ogni calcolo.

**Opzioni valutate.**
- Numero in virgola mobile (`float`, `double`): scartata, non è esatto (`0,1 + 0,2` non dà `0,3`).
- Colonna `DECIMAL(10,2)`: l'alternativa seria, esatta e leggibile in SQL. Scartata: il valore arriva a PHP come stringa e va convertito a ogni lettura, mentre un intero si usa così com'è.
- Intero in centesimi (`price_cents`): **scelta**. È esatto e viaggia uguale nel database, in PHP e nel JSON. Svantaggio: va convertito ai bordi (vedi Conseguenze).
- Una libreria per il denaro: scartata per ora, perché non serve con una sola valuta.

**Decisione.** Il prezzo è un intero non negativo in centesimi, nella colonna `price_cents`, di tipo intero senza segno a 32 bit (fino a circa 42,9 milioni di euro). Il valore è il prezzo di una unità (`unit`) dell'ingrediente: per la farina venduta a chili, `120` vale 1,20 € al chilo. Una sola valuta: l'euro.

**Conseguenze.**
- Conversione solo ai bordi: all'ingresso (euro inseriti → centesimi) e nella visualizzazione (centesimi → euro). Nel database il valore non è leggibile a occhio, e ogni punto di ingresso e di uscita deve convertire: dimenticare la conversione dà un prezzo cento volte sbagliato.
- Validazione come intero non negativo, nelle Form Request (#40).
- Formato di visualizzazione nel frontend: virgola come separatore decimale e due cifre dopo la virgola.
- **Limite**: un prezzo inferiore al centesimo non si può rappresentare. La farina a 120 centesimi al chilo costa circa 0,12 centesimi al grammo, quindi non si può salvare un prezzo al grammo. Il prezzo resta per unità di `unit`; il costo di una ricetta, che calcola la quantità usata, dovrà definire la regola di arrotondamento. Quella regola si scrive quando arriverà la tabella delle ricette, non qui.

**Rivalutare se.** Servono più valute, oppure serve usare direttamente prezzi inferiori al centesimo per unità. In quel caso si valuta una precisione più fine, per esempio millesimi di centesimo in una colonna `price_milli_cents`: più scomoda, ma senza perdita.

## Registro

| ID    | Data       | Decisione                               | Livello |
|-------|------------|-----------------------------------------|---------|
| D-001 | 2026-10-30 | Monorepo                                 | 0       |
| D-002 | 2026-10-30 | Stile: Bootstrap 5 + SCSS                | 0       |
| D-003 | 2026-10-30 | Gestore pacchetti: pnpm                  | 0       |
| D-004 | 2026-10-31 | Ambiente di sviluppo: servizi in Docker  | 0       |
| D-005 | 2026-10-31 | Strumenti di qualità del backend         | 0       |
| D-006 | 2026-11-01 | Linguaggio frontend: JavaScript ora      | 0       |
| D-007 | 2026-11-01 | Strumenti di qualità e test del frontend | 0       |
| D-008 | 2026-11-01 | Workflow di CI del frontend              | 0       |
| D-009 | 2026-11-01 | Workflow di CI del backend               | 0       |
| D-010 | 2026-11-02 | Database dei test: MySQL                 | 1       |
| D-011 | 2026-11-02 | Denaro in centesimi interi               | 1       |
