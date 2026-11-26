# Livello 1 — Ingredienti

## Obiettivo

Con questo livello voglio avere la prima funzione vera di Orarium, l'elenco degli ingredienti con ricerca, e misurarla. Prima una versione semplice, marcata dal tag `level-01-naive`, con le misure di partenza. Poi i miglioramenti, uno alla volta, ognuno con la misura prima e dopo, fino alla versione ottimizzata.

## Cosa ho fatto

Le pull request più importanti, tutte fuse su `main`.

**Versione semplice**

- Tabella e modello `Ingredient`, con l'enum `Unit` e il prezzo in centesimi (D-011): #53.
- Endpoint per leggere gli ingredienti (#54) e per crearli, modificarli e cancellarli (#55).
- Seeder riproducibile (#61) e poi reso coerente nelle unità e nelle varianti (#67).
- Bootstrap con SCSS, come deciso in D-002 (#63), e pagina dell'elenco (#64). Le unità a pezzi si mostrano come «pz» (#69).
- Test del backend su MySQL, con un database dedicato (#34, D-010).
- Misure di partenza dell'endpoint (#71) e della pagina (#76).

**Misure e indagini**

- Indagine sul server di sviluppo che rispondeva a due velocità (#78) e misure rifatte con la regola di controllo (#80).
- Dove va il tempo nell'endpoint dell'elenco (#82).

**Miglioramenti**

- Paginazione dell'API (#84, D-012) e paginatore nella pagina (#86).
- Attesa prima della ricerca, così non parte una richiesta a ogni lettera (#93, D-013).
- Test diretti di `fetchIngredients` (#91).
- Correzione del pulsante Avanti oltre l'ultima pagina (#96).

**Processo e documenti**

- Allineamento dei documenti del livello (#73, #75).
- Docker nel README del backend: volumi, prefisso e stati di salute (#87, #88, #89).
- Dependabot, prima settimanale (#98) e poi mensile con gruppi (#126), e politica delle versioni (D-014).
- Decisioni D-012, D-013 e D-014 registrate in `DECISIONS.md` (#131).

## Decisioni

Tutte le decisioni sono in [DECISIONS.md](../DECISIONS.md). Quelle di questo livello:

- D-010: i test del backend usano MySQL, in un database dedicato.
- D-011: il denaro si salva in centesimi interi.
- D-012: l'elenco si pagina con `paginate()`, 50 righe per pagina di default, `per_page` da 1 a 100.
- D-013: la ricerca aspetta 300 ms dopo l'ultima lettera prima di chiamare l'API.
- D-014: Dependabot con aggiornamenti mensili e a gruppi, e le versioni di MySQL e Redis che restano sulla linea scelta in D-004.

## Misure

I numeri non sono ricopiati qui: stanno nei due documenti delle prestazioni, che spiegano anche ambiente e metodo.

- Endpoint dell'elenco: [PERFORMANCE.md](../PERFORMANCE.md), sezioni «Livello 1», «Dopo la paginazione dell'elenco» e «Indagine: il server di sviluppo a due velocità».
- Pagina dell'elenco: [FRONTEND-PERF.md](../FRONTEND-PERF.md), sezioni «Livello 1» (versione semplice, paginatore e attesa prima della ricerca).

In breve: la paginazione ha ridotto sia il tempo dell'elenco sia i dati per richiesta, e l'attesa prima della ricerca ha ridotto il numero di richieste mentre si scrive. Le misure del 5 novembre, prese prima della regola di controllo del server, non sono una baseline: sono state rifatte il 6 novembre.

## Test

Questa mappa è stata fatta leggendo i nomi dei test in `main`, non eseguendoli. Ogni comportamento del livello con il test che lo copre.

| Comportamento | Test |
|---|---|
| Elenco vuoto e pieno | `IngredientApiTest`: «returns an empty list when there are no ingredients», «returns every ingredient when there are some» |
| Ricerca per nome | `IngredientApiTest`: «returns only the ingredients whose name contains the search text», «returns an empty list when no ingredient matches the search text» |
| Un ingrediente, o 404 in JSON | `IngredientApiTest`: «returns one ingredient with its fields», «returns a JSON 404 when the ingredient does not exist» |
| Creare, modificare, cancellare | `IngredientWriteApiTest`: creazione, modifica di ogni campo, cancellazione |
| Dati non validi, ingrediente inesistente | `IngredientWriteApiTest`: «rejects invalid ingredient data on create» e «on update», 404 in modifica e in cancellazione |
| Modello, enum `Unit`, prezzo non negativo | `IngredientTest` |
| Paginazione dell'API | `IngredientPaginationTest`: prima pagina da 50, `links` e `meta`, seconda pagina, nessuna riga ripetuta o saltata con ordine per `id`, `per_page`, limiti di `per_page`, `per_page` non valido, pagina oltre l'ultima, ricerca paginata, totale zero, `search` e `per_page` nei link |
| Seeder | `IngredientSeederTest`: numero di righe, stessi dati a ogni esecuzione, rifiuto in produzione, solo nomi e unità della lista, ultimo gruppo più piccolo |
| Database dei test | `TestDatabaseTest`: MySQL 8.4 e database dedicato |
| Chiamata all'API | `ingredients.test.js`: parametri `search` e `page`, pagina 1 di default, testo con spazio codificato, solo `data` e `meta` restituiti, errore con lo stato |
| Elenco nella pagina | `IngredientList.test.jsx`: tabella, «pz», caricamento, errore, ricerca |
| Paginatore | `IngredientList.test.jsx`: pagina corrente e ultima, Indietro e Avanti disabilitati ai bordi, richiesta della pagina successiva e di quella precedente, ritorno a pagina 1 quando cambia la ricerca; `Pagination.test.jsx`: Avanti disabilitato oltre l'ultima pagina |
| Attesa prima della ricerca | `IngredientList.test.jsx`: nessuna richiesta mentre si scrive, una sola richiesta per il testo finale, attesa anche dalla pagina 2, pagina mantenuta dopo il timer avviato al montaggio |

Non ho trovato test per:

- il comportamento nel browser vero: i test del frontend girano con Vitest e jsdom, e Playwright è nel repository solo per le misure (`benchmarks/page.mjs`), non per i test;
- un elenco con una sola pagina e l'etichetta «Pagina 1 di 1».

## Cosa ho imparato

**Senza l'attesa, ogni lettera digitata faceva una richiesta.** Non ci avevo mai pensato. Scrivendo una parola partiva una richiesta per lettera, e con l'attesa (D-013) ne parte una sola, per il testo finale. Le misure sono in [FRONTEND-PERF.md](../FRONTEND-PERF.md).

**Una misura vale solo se si controlla che l'ambiente sia nello stato di riferimento.** Il server di sviluppo rispondeva a due velocità, una veloce e una circa tre volte più lenta, con lo stesso codice. Per questo prima e dopo ogni serie misuro `/api/v1/health`: se il controllo è nello stato veloce la serie vale, altrimenti la scarto. Con questa regola ho rifatto le misure del 5 novembre. La regola completa è in [PERFORMANCE.md](../PERFORMANCE.md).

## Limiti e cose rimaste aperte

- **Il meccanismo della doppia velocità non l'ho capito**, e nemmeno le prove lo hanno trovato. Ho escluso il codice, Docker, OPcache, la porta, la cartella, la priorità del processo e il terminale. Il passaggio da uno stato all'altro è reversibile e può avvenire in pochi minuti. Le prove sono in [PERFORMANCE.md](../PERFORMANCE.md).
- Le misure sono fatte su un solo computer, con Windows, e non rappresentano un ambiente di produzione.
- Nella ricerca, la misura con k6 non cambia prima e dopo la paginazione, mentre senza server web cambia. Questa differenza non è spiegata (vedi [PERFORMANCE.md](../PERFORMANCE.md)).
- I 300 ms dell'attesa sono una scelta, non un risultato misurato (D-013).
- `per_page=100` non è stato misurato, e il costo della query di conteggio è misurato solo sulla tabella del seeder.
- I test non coprono il browser vero e l'elenco con una sola pagina (vedi «Test»).
- Il pulsante Indietro si disabilita con `=== 1`: da valutare come fa Avanti dopo la #96.
- Il paginatore non è in un elemento `<nav>` accessibile e i pulsanti non hanno uno stile mio.
- Prima di un deploy vanno ancora verificati il CORS e la licenza, e non ci sono `composer audit` e `pnpm audit` nei workflow.
- Il linguaggio del frontend resta JavaScript: D-006 (TypeScript) è da rivalutare.

## Prossimo livello

Il Livello 2 non è ancora pianificato. Questo livello è chiuso, con il tag `level-01-optimized` e il rilascio `v0.1.0`. Prima di cominciare il Livello 2 va rifatta la misura finale del Livello 1 con il nuovo Chromium di Playwright 1.65.1, perché le misure dei due livelli siano confrontabili (D-014).
