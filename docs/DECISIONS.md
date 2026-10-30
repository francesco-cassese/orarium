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

## Registro

| ID    | Data       | Decisione                    | Livello |
|-------|------------|------------------------------|---------|
| D-001 | 2026-10-30 | Monorepo                     | 0       |
| D-002 | 2026-10-30 | Stile: Bootstrap 5 + SCSS    | 0       |
| D-003 | 2026-10-30 | Gestore pacchetti: pnpm      | 0       |
