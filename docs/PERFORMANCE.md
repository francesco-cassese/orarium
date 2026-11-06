# Prestazioni del backend: baseline

Misura di partenza del Livello 0, prima di qualsiasi ottimizzazione. Serve come confronto per i livelli successivi, quindi deve poter essere ripetuta con lo stesso metodo.

## Ambiente

- Sistema operativo: Windows 11 Home 10.0.26200
- CPU: Intel Core 5 120U, 12 thread; RAM 15.6 GB
- Piano di alimentazione: Bilanciato. Programmi aperti e altre condizioni del computer: non registrati.
- PHP: 8.4.27 NTS (`php -v`)
- Laravel: 13.39.0 (`php artisan --version`)
- `backend/.env`: `APP_ENV=local`, `APP_DEBUG=true`
- Server: `php artisan serve` nella cartella `backend/`, porta 8000. Usa il server integrato di PHP con `server.php` come router.
- Commit misurato: `97dd24d` su `main`
- Strumento: k6 v2.3.0
- Data delle misure: rotte di Laravel il 2026-11-02, alle 00:52–00:53; controlli il 2026-11-02, alle 01:07–01:09

## Metodo

### Rotte di Laravel

- Rotte: `/up` e `/api/v1/health`.
- Corse alternate: `up`, `health`, `up`, `health`…, per evitare che un cambio di condizioni favorisca una rotta.
- Un utente virtuale senza pause: ogni richiesta parte appena finisce la precedente. Durata di ogni corsa: 10 secondi.
- Corse: cinque per rotta. La corsa 1 è scartata come riscaldamento, quindi restano quattro misurate (corse 2–5).

### Controlli

Servono a separare il costo del server da quello di Laravel:

- `/ok.html`: file statico con il contenuto `ok`.
- `/index.php`: script PHP minimo, con `<?php echo 'ok';`.

Entrambi sono serviti da `php -S 127.0.0.1:8000 -t <cartella>`, con la cartella di controllo fuori dal repository. Per riprodurre la misura, crea i due file in una cartella a parte e usa quel comando.

- Corse: sei per file, alternate, con la corsa 1 scartata (cinque misurate).
- Pausa: `PAUSE=0.1`, cioè 0.1 secondi dopo ogni risposta, circa 10 richieste al secondo.

**Perché la pausa.** Senza pausa un utente virtuale produce più di mille richieste al secondo. Il server integrato di PHP chiude ogni connessione, e Windows esaurisce le porte locali (connessioni in `TIME_WAIT`): le corse successive alla prima falliscono. Le corse senza pausa non valide sono conservate localmente in `benchmarks/results/invalid-ctrl-no-pause/`, non versionate, e non vanno usate.

### Comandi

Dalla cartella `benchmarks/`, con il server Laravel avviato:

```powershell
k6 run -e ENDPOINT=/up --summary-export=results/level-00/up-runN.json smoke.js
k6 run -e ENDPOINT=/api/v1/health --summary-export=results/level-00/health-runN.json smoke.js
```

Per i controlli, con il server di controllo avviato, aggiungi `-e PAUSE=0.1`.

Lancia da PowerShell, oppure da Git Bash con `MSYS_NO_PATHCONV=1`. Senza questa variabile Git Bash trasforma `/up` in un percorso Windows: l'URL non è valido, la richiesta non parte e la corsa, con la sola soglia sull'errore, esce con 0. La soglia `http_reqs: ['count>0']` fa uscire la corsa con 99 in quel caso.

### Calcoli

- Mediana e p95 di ogni corsa: campi `med` e `p(95)` di `http_req_duration`.
- Errori: campo `passes` di `http_req_failed`, cioè le richieste fallite. Nei file, `fails` conta invece le richieste riuscite.
- Riepilogo: mediana tra le corse misurate, con minimo e massimo.

## Risultati

Corse misurate: quattro per le rotte di Laravel, cinque per i controlli. Nessuna richiesta fallita in nessuna corsa misurata.

| Livello | Mediana tra le corse | Min–max tra le corse | Mediana dei p95 |
|---|---|---|---|
| File statico | 1.73 ms | 1.69–1.76 ms | 3.01 ms |
| Script PHP minimo | 2.29 ms | 1.97–2.41 ms | 3.97 ms |
| Laravel, `/api/v1/health` | 101.8 ms | 101.6–102.6 ms | 109.5 ms |
| Laravel, `/up` | 104.2 ms | 103.8–105.3 ms | 110.9 ms |

Lettura preliminare: in questo ambiente lo script PHP minimo costa circa 2 ms e Laravel circa 100 ms per richiesta. La differenza è di oltre un ordine di grandezza. Il tempo sta in ciò che Laravel fa a ogni richiesta, non nel server né nel semplice avvio di PHP. Una parte delle 100 ms può essere PHP che compila molti file a ogni richiesta, se OPcache non è attiva (non verificato, vedi sotto). Il risultato si basa su poche corse e va confermato.

## Limiti e osservazioni

- **Metodo non uniforme.** Le corse di Laravel non hanno pausa; i controlli sì (0.1 secondi). Il ritmo è simile (circa 10 richieste al secondo), ma il comportamento no.
- **Numero di corse.** Per Laravel sono state misurate quattro corse, non cinque come per i controlli. Non corretto in questa baseline.
- **Momento delle misure.** Le rotte di Laravel sono state misurate alle 00:52–00:53 e i controlli alle 01:07–01:09, circa quindici minuti dopo e non alternati. A questa scala la conclusione non cambia, ma sono condizioni diverse.
- **Valore anomalo.** `up-run3` ha un p95 di 132.3 ms, molto sopra le altre corse misurate, e 92 richieste contro 95 delle altre. La corsa più lenta completa meno richieste, ma la causa del valore non è spiegata.
- **Differenza tra rotte.** `/up` è circa 2.5 ms più lenta di `/api/v1/health`. Cause possibili, tutte non verificate:
  - `/up` renderizza una vista Blade (`health-up.blade.php`), mentre `/api/v1/health` restituisce JSON;
  - `/up` lancia l'evento `DiagnosingHealth`, `/api/v1/health` no;
  - la dimensione della risposta: 1829 byte di HTML contro 15 byte di JSON.
- **Un solo computer, una sola sessione.** Non è una misura di un ambiente di produzione. k6 e il server girano sullo stesso computer e si contendono la CPU: i numeri contengono anche un po' del costo di k6.

## Ipotesi da verificare nei livelli successivi

Non sono risultati, ma domande da porre quando si ottimizza:
- OPcache: non è stata verificata la sua configurazione (nel Livello 1 risulta non caricata: vedi «Ambiente»).
- Configurazione: Laravel legge i file di configurazione a ogni richiesta, se la cache non è attiva.
- `APP_DEBUG=true`: il debug può costare tempo a ogni richiesta.
- Server: `php artisan serve` è pensato per lo sviluppo, non per la produzione.

## Livello 1: endpoint dell'elenco degli ingredienti (versione semplice)

Misura di `GET /api/v1/ingredients` con il database di sviluppo pieno. È la versione semplice dell'endpoint: restituisce tutte le righe, senza paginazione. Questa misura è stata rifatta il 2026-11-06 con la regola di controllo descritta nella sezione «Indagine»; la prima misura (2026-11-05) è riassunta in «Limiti e osservazioni» e non va usata come baseline.

### Ambiente

- Data delle corse: 2026-11-06, dalle 01:40 alle 01:57 (ora italiana).
- Computer, sistema e piano di alimentazione: gli stessi del Livello 0 (Windows 11 Home 10.0.26200, Intel Core 5 120U, RAM 15.6 GB, piano Bilanciato), con il portatile collegato alla corrente (dichiarato da chi ha misurato, non verificato con uno strumento). I programmi aperti non sono stati registrati.
- PHP 8.4.27 NTS, Laravel 13.39.0, `APP_ENV=local`, `APP_DEBUG=true`.
- OPcache non è caricata: `php -m` non la elenca.
- Server di Laravel: `php artisan serve` nella cartella `backend/`, porta 8000, avviato dallo script in una finestra nascosta e nuovo a ogni giro.
- Server di controllo: `php -S 127.0.0.1:8001 -t <cartella temporanea fuori dal repository>`, nuovo a ogni giro. La cartella contiene `ok.html` (il testo `ok`) e `index.php` (`<?php echo "ok";`). È stata ricreata il 2026-11-06: l'idea è quella del Livello 0, i file non sono gli stessi.
- Strumento: k6 v2.3.0. Database: MySQL 8.4.13 in Docker.
- Codice misurato: il backend di `main` (`b0a5fbd`). Lo script `benchmarks/measure.ps1` è quello del commit `2491f66`.

### Dataset

- Database ricreato con `php artisan migrate:fresh --seed` (seeder `IngredientSeeder`, seme fisso: a ogni ricostruzione il dataset è lo stesso).
- Righe verificate prima delle misure: 20.000, con 80 nomi distinti.
- Risposta dell'elenco: 20.000 righe, 1.356.661 byte. Ricerca `?search=farina`: 485 righe, 32.319 byte. Questi due valori sono quelli della prima misura (stesso seme), non sono stati rimisurati.

### Metodo

- Serie misurate, in quest'ordine in ogni giro:
  - `/api/v1/health` (controllo prima);
  - elenco, `/api/v1/ingredients`;
  - ricerca, `/api/v1/ingredients?search=farina`;
  - `/api/v1/health` (controllo dopo);
  - i due controlli del Livello 0: `/ok.html` e `/index.php`.
- Giri: sei. Il giro 1 è scartato come riscaldamento: restano cinque corse misurate per ogni serie.
- Regola di controllo: ogni giro parte da server nuovi (Laravel e controllo). Il giro vale solo se la mediana dei due `health` è sotto i 150 ms; altrimenti i suoi file sono rinominati con `-scartato` e il giro si ripete, fino a tre tentativi. In questa misura nessun giro è stato scartato.
- Un utente virtuale. Durata: 60 secondi per elenco e ricerca, 10 secondi per le altre serie. Pausa di 0.1 secondi solo sui due controlli.
- Comando, dalla radice del progetto, con il database acceso e nessun server sulle porte 8000 e 8001:

```powershell
.\benchmarks\measure.ps1 -ControlDir <cartella con ok.html e index.php>
```

- I risultati di ogni corsa sono in `benchmarks/results/level-01/rerun/` (36 file).
- Calcoli, come nel Livello 0: mediana e p95 di ogni corsa dai campi `med` e `p(95)` di `http_req_duration`; la mediana tra le corse è la mediana delle cinque mediane dei giri 2–6; il min–max sono il minimo e il massimo di quelle cinque; la mediana dei p95 è la mediana dei cinque p95.
- Errori: nessuna richiesta è fallita in nessuna delle 30 corse misurate.

### Risultati

Cinque corse misurate per serie, trenta in totale.

| Serie | Mediana tra le corse | Min–max tra le corse | Mediana dei p95 |
|---|---|---|---|
| Elenco | 573.7 ms | 571.6–576.8 ms | 609.7 ms |
| Ricerca `farina` | 141.2 ms | 140.6–141.4 ms | 167.6 ms |
| Laravel, `/api/v1/health` prima | 98.3 ms | 97.9–99.1 ms | 102.5 ms |
| Laravel, `/api/v1/health` dopo | 98.6 ms | 98.2–99.1 ms | 102.7 ms |
| Script PHP minimo | 2.3 ms | 2.2–2.5 ms | 5.1 ms |
| File statico | 1.5 ms | 1.5–1.7 ms | 3.8 ms |

### Lettura

Lettura preliminare, non verificata con un profiler.

- I due `health` stanno intorno a 98 ms in tutti i giri: il server è rimasto nello stato veloce. I controlli (script PHP 2.3 ms, file statico 1.5 ms) sono in linea con il Livello 0 (2.29 e 1.73 ms).
- `health` è 98.3 ms contro i 101.8 ms del Livello 0: ora i due livelli sono confrontabili.
- L'elenco costa circa 475 ms più di `health` (573.7 contro 98.3) e la ricerca circa 43 ms più (141.2 contro 98.3). Dove va il tempo è misurato nella sezione «Diagnosi» qui sotto: il costo dipende dalle righe restituite, non dalla ricerca nel database.
- La variazione tra giri è piccola: le mediane dell'elenco stanno in 5 ms (571.6–576.8). Nella prima misura stavano in 837 ms (1567.6–2404.7). È coerente con un server rimasto nello stato veloce, ma non è stato verificato.

### Limiti e osservazioni

- **Prima misura, da non usare come baseline.** Il 2026-11-05, dalle 14:24 alle 14:39, la stessa procedura senza controlli aveva dato elenco 2064.8 ms, ricerca 454.5 ms, `health` 354.7 ms (circa 3.5 volte quello del Livello 0), script PHP 2.04 ms e file statico 1.44 ms. Era probabilmente il server nello stato lento descritto in «Indagine». I file di quella misura sono ancora in `benchmarks/results/level-01/` (`list-run*.json` e simili).
- Un solo computer e una sola sessione. k6 e i server girano sullo stesso computer e si contendono la CPU.
- Il controllo vede lo stato del server solo prima e dopo elenco e ricerca: un passaggio di stato nel mezzo che si risolve prima del secondo controllo non verrebbe notato.
- Le serie da 10 secondi hanno poche richieste, quindi il loro p95 è approssimativo.
- Laravel e controlli non hanno lo stesso ritmo (nessuna pausa contro 0.1 s), come nel Livello 0.
- OPcache non caricata e `APP_DEBUG=true`: l'effetto sulle misure non è stato verificato.
- Una sola ricerca (`farina`) e una sola dimensione del dataset (20.000 righe): altri testi o altre dimensioni possono dare numeri diversi.
- I riepiloghi sono calcolati dai file JSON con un comando che non è nel repository.

### Diagnosi: dove va il tempo (issue #81)

Misura fatta con `benchmarks/profile-list.php`, che esegue l'applicazione in un solo processo PHP, senza server web, sul database di sviluppo. Questa sezione dice dove va il tempo; non cambia nessun codice dell'applicazione.

**Ambiente e dataset.** Come sopra: PHP 8.4.27, Laravel 13.39.0, `APP_ENV=local`, OPcache non caricata, MySQL 8.4.13 in Docker, 20.000 righe. Data: 2026-11-06, intorno alle 02:53 (ora italiana). Script del commit `2688d87`.

**Metodo.**

- Sei processi di seguito, uno per corsa; il primo è scartato come riscaldamento e restano cinque corse.
- In ogni processo l'avvio dell'applicazione è misurato una volta. Ogni passo ha un riscaldamento scartato e cinque ripetizioni, di cui si registra la mediana, con il picco di memoria (`memory_reset_peak_usage`).
- Passi:
  - query grezza (`DB::select`);
  - query con creazione dei modelli (`Ingredient::query()->get()`);
  - trasformazione in risorse (`IngredientResource::collection(...)->resolve()`);
  - `json_encode`;
  - richiesta completa passata dal kernel HTTP.
  - Come controllo, senza database: `/api/v1/health` passata dal kernel.
- Il costo di creare i modelli è la differenza tra «query con modelli» e «query grezza». Il «resto» è la differenza tra la richiesta completa e la somma di query con modelli, risorse e JSON. Sono differenze, non misure dirette.
- Comando, dalla radice del progetto: `php benchmarks/profile-list.php --out=<file>`. I risultati sono in `benchmarks/results/level-01/diagnosis/`.
- Il riepilogo è la mediana tra le cinque corse delle mediane dei passi, con minimo e massimo.

**Risultati.** Avvio dell'applicazione (una volta per processo): 131.08 ms (min–max 130.69–143.90). Richiesta `health` dentro il processo: 0.30 ms (0.28–0.34).

Elenco, 20.000 righe, risposta di 1.356.661 byte:

| Passo | Mediana | Min–max | Quota della richiesta | Picco di memoria |
|---|---|---|---|---|
| Query grezza | 26.94 ms | 24.61–28.80 ms | 5.7% | 13.43 MB |
| Creazione dei modelli (differenza) | 240.47 ms | — | 50.6% | — |
| Trasformazione in risorse | 189.26 ms | 189.12–190.90 ms | 39.8% | 11.33 MB |
| Codifica JSON | 2.88 ms | 2.75–2.92 ms | 0.6% | 1.30 MB |
| Resto (differenza) | 15.53 ms | — | 3.3% | — |
| **Richiesta completa** | **475.08 ms** | 466.30–483.85 ms | 100% | 41.79 MB |

La «query con modelli» misurata direttamente è 267.41 ms (249.76–269.19 ms), con 31.94 MB.

Ricerca `farina`, 485 righe, risposta di 32.319 byte:

| Passo | Mediana | Min–max | Quota della richiesta | Picco di memoria |
|---|---|---|---|---|
| Query grezza | 6.18 ms | 6.05–6.84 ms | 34.1% | 0.36 MB |
| Creazione dei modelli (differenza) | 6.06 ms | — | 33.4% | — |
| Trasformazione in risorse | 4.61 ms | 4.50–4.66 ms | 25.4% | 0.28 MB |
| Codifica JSON | 0.06 ms | 0.06–0.06 ms | 0.3% | 0.03 MB |
| Resto (differenza) | 1.22 ms | — | 6.7% | — |
| **Richiesta completa** | **18.13 ms** | 17.75–32.83 ms | 100% | 1.03 MB |

La «query con modelli» misurata direttamente è 12.24 ms (11.97–12.44 ms), con 0.79 MB.

**Piano di esecuzione della ricerca.** `EXPLAIN`: `type` `ALL`, nessuna chiave (`key` `NULL`), stima di 19588 righe, `filtered` 11.11%, `Using where`. `EXPLAIN ANALYZE`: scansione della tabella di 20.000 righe in 2.83 ms e filtro in 5.84 ms in totale, con 485 righe restituite. La documentazione di MySQL 8.4 dice che un indice B-tree si usa con `LIKE` solo se la stringa costante non inizia con un carattere jolly (esempio che non lo usa: `LIKE '%Patrick%'`) e che per `LIKE '%stringa%'` con una stringa di più di tre caratteri MySQL usa l'algoritmo Turbo Boyer-Moore ([MySQL 8.4, Comparison of B-Tree and Hash Indexes](https://dev.mysql.com/doc/refman/8.4/en/index-btree-hash.html)).

**Lettura.**

- Circa il 90% del tempo dell'elenco è lavoro di PHP per riga: creare i modelli (50.6%, circa 12.0 µs a riga) e trasformarli in risorse (39.8%, circa 9.5 µs a riga). La query è il 5.7% e la codifica JSON lo 0.6%.
- Il costo per riga è quasi lo stesso nella ricerca (circa 12.5 µs per i modelli e 9.5 µs per le risorse): cresce in modo lineare con le righe restituite.
- Nella ricerca il database costa circa 6 ms anche con la scansione completa della tabella. Con `LIKE '%testo%'` un indice sul nome non verrebbe usato (documentazione citata sopra), quindi a questa dimensione non è dove cercare un miglioramento.
- Coerenza con k6: `health` (98.3 ms) più la richiesta completa dell'elenco (475.1 ms) dà 573.4 ms, contro i 573.7 ms misurati sul server. Per la ricerca la somma (98.3 più 18.1, cioè 116.4 ms) è invece sotto i 141.2 ms misurati: restano circa 25 ms non spiegati. Un sospetto sono i costi a freddo di una richiesta vera (connessione al database, caricamento delle classi), che qui sono scartati dal riscaldamento; non è stato verificato.
- L'avvio dell'applicazione costa 131 ms in questo processo da riga di comando; sul server `health` costa 98 ms. Non sono la stessa misura e non vanno confrontati.
- Il picco di memoria dell'elenco è 41.79 MB per 20.000 righe.
- Il costo fisso di una richiesta non dipende dalle righe: `health` costa 98.3 ms sul server. La parte che dipende dalle righe dell'elenco, misurata nel processo, è di 475.1 ms.

**Limiti della diagnosi.**

- Un processo da riga di comando non è il server web: non c'è l'avvio dell'applicazione per ogni richiesta e le ripetizioni dentro un processo usano una cache calda.
- Un solo computer e una sola sessione. Cinque ripetizioni per corsa e cinque corse. Nella richiesta completa della ricerca una corsa ha un massimo di 32.83 ms contro una mediana di 18.13 ms: non indagato.
- Creazione dei modelli e «resto» sono differenze, non misure dirette.
- Il riepilogo è calcolato dai file JSON con un comando che non è nel repository.

### Dopo la paginazione dell'elenco (issue #83)

L'API restituisce 50 ingredienti per pagina (`page`, `per_page` da 1 a 100), ordinati per `id`, con `data`, `links` e `meta`. La ricerca funziona insieme alla paginazione e i link di pagina mantengono `search` e `per_page`. L'indirizzo `/api/v1/ingredients` senza parametri ora restituisce 50 righe invece di 20.000.

Ambiente, dataset, metodo e regola di validità sono quelli del Level 1: stesso script (`benchmarks/measure.ps1`), 6 giri validi al primo tentativo, health prima e dopo tra 98,4 e 99,2 ms. Risultati in `benchmarks/results/level-01/optimized/`.

| Richiesta | Prima | Dopo |
|---|---|---|
| Elenco, mediana | 574,3 ms | 139,1 ms |
| Elenco, p95 | 608,5 ms | 159,6 ms |
| Elenco, dati per richiesta | 1.325,1 KB | 5,2 KB |
| Ricerca "farina", mediana | 141,1 ms | 141,1 ms |
| Ricerca "farina", p95 | 168,2 ms | 163,5 ms |
| Ricerca "farina", dati per richiesta | 31,8 KB | 5,1 KB |

Le mediane sono la mediana delle 6 mediane di giro; il costo fisso del server (health) è circa 99 ms.

Misura senza server web (`benchmarks/profile-list.php`, 3 corse, mediane in ms):

| Passo | Elenco | Ricerca |
|---|---|---|
| Query di conteggio | 2,12 | 4,74 |
| Query delle prime 50 righe | 1,99 | 2,78 |
| Richiesta completa, prima | 475,0 | 18,2 |
| Richiesta completa, dopo | 5,89 | 8,79 |

Il piano di esecuzione della ricerca è una scansione completa della tabella (`type: ALL`, 19.588 righe stimate): il conteggio della ricerca costa più delle 50 righe.

**Limiti.** La ricerca non cambia nelle misure con k6 (141,1 ms prima e dopo), mentre nella misura senza server web passa da 18,2 a 8,79 ms. Questa differenza non è spiegata. Anche i 139 ms dell'elenco restano sopra la somma del costo fisso (circa 99 ms) e del tempo senza server web (5,89 ms). Il conteggio completo della tabella ad ogni richiesta e la paginazione di tipo `paginate()` (con `meta.total`) sono una scelta dell'API.

## Indagine: il server di sviluppo a due velocità (issue #77)

Nella baseline del Livello 0 l'endpoint `/api/v1/health` rispondeva con una mediana di circa 98 ms. Nelle sessioni successive la stessa rotta, con lo stesso codice, rispondeva in circa 340 ms. Questa sezione riassume cosa è stato escluso, cosa è stato osservato e quale regola ne segue.

### Cosa è stato osservato

- **Il codice non c'entra.** Il codice del Livello 0, rimesso in esecuzione, ha dato circa 332 ms nello stesso periodo.
- **Gli altri sospetti sono esclusi come causa:** Docker, OPcache, porta, cartella di lavoro, maiuscole nel percorso, priorità del processo, moduli PHP caricati, reindirizzamento dell'output e variabili d'ambiente passate al processo.
- **Il rallentamento riguarda tutto il lavoro di PHP, non solo `health`.** Confrontando la prima misura del Livello 1 (stato lento) con quella rifatta (stato veloce): `health` 354.7 contro 98.3 ms (3.6 volte), ricerca 454.5 contro 141.2 ms (3.2 volte), elenco 2064.8 contro 573.7 ms (3.6 volte). Sono sessioni diverse, quindi è un confronto indicativo e non una misura del meccanismo.
- **Il passaggio è reversibile e può essere rapido.** Nella prova `i2-artisan-windows-terminal` una corsa su sei è passata da 100,8 ms a 346,2 ms ed è tornata a 97,9 ms. Nella prova `o1-three` un `artisan serve` avviato da meno di tre minuti è stato veloce, mentre uno avviato circa quindici minuti prima, sullo stesso computer e nello stesso minuto, è stato lento.
- **Il terminale di avvio non è la causa.** Nella prova `m1-pair` il server avviato da VS Code era lento e quello avviato dal Windows Terminal era veloce. Sei minuti dopo, nella prova `n1-direct`, i ruoli si erano invertiti.

### Cosa non si sa

Il meccanismo che fa passare un processo da uno stato all'altro. Non è stato trovato con le prove fatte e non è stato trattato come un problema del progetto.

### Regola di misura

Prima di ogni serie di misure:

1. si avvia un server nuovo;
2. si misura `/api/v1/health` prima e dopo la serie;
3. se la mediana del controllo è intorno a 100 ms, il server è nello stato veloce e la serie vale; se è intorno a 340 ms, la serie si scarta e si riparte da un server nuovo.

I numeri del Livello 1 del 2026-11-05, presi prima di questa regola, sono stati rifatti il 2026-11-06 con la regola (issue #79). Quelli vecchi restano nei file e sono descritti come limite nelle sezioni del Livello 1; non sono una baseline.

### Dati

Le mediane di ogni corsa delle 21 prove valide sono in `benchmarks/results/level-01/health-investigation.json`.