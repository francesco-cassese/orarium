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

Misura di `GET /api/v1/ingredients` con il database di sviluppo pieno. È la versione semplice dell'endpoint: restituisce tutte le righe, senza paginazione.

### Ambiente

- Data delle corse: 2026-11-05, dalle 14:24 alle 14:39.
- Computer, sistema e piano di alimentazione: gli stessi del Livello 0 (Windows 11 Home 10.0.26200, Intel Core 5 120U, RAM 15.6 GB, piano Bilanciato), con il portatile collegato alla corrente.
- Programmi aperti: browser, Docker Desktop, terminale, VS Code.
- PHP 8.4.27 NTS, Laravel 13.39.0, `APP_ENV=local`, `APP_DEBUG=true`.
- OPcache non è caricata: `php -m` non la elenca.
- L'estensione `curl` di PHP è bloccata da Windows: a ogni avvio di PHP compare un avviso. Non è stato verificato se incide sulle misure.
- Server di Laravel: `php artisan serve` nella cartella `backend/`, porta 8000.
- Server di controllo: `php -S 127.0.0.1:8001 -t <cartella fuori dal repository>`.
- Strumento: k6 v2.3.0.
- Commit misurato: `73673e8`, con `benchmarks/smoke.js` modificato e non ancora committato durante le misure (opzioni `BASE_URL` e `DURATION`). La versione usata è quella di `7cb0b6b` (PR #71): in git il file ha lo stesso contenuto che aveva subito dopo le misure.

### Dataset

- Database MySQL 8.4.13 in Docker, ricreato prima delle misure con `php artisan migrate:fresh --seed` (seeder `IngredientSeeder`, seme fisso: a ogni ricostruzione il dataset è lo stesso).
- Righe: 20.000, con 80 nomi distinti.
- Risposta dell'elenco (`GET /api/v1/ingredients`): 20.000 righe, 1.356.661 byte.
- Ricerca `?search=farina`: 485 righe, 32.319 byte.

### Metodo

- Serie misurate:
  - elenco, `/api/v1/ingredients`;
  - ricerca, `/api/v1/ingredients?search=farina`;
  - `/api/v1/health`, il «pavimento» di Laravel: serve a separare il costo dell'avvio da quello del lavoro dell'endpoint;
  - i due controlli del Livello 0: `/ok.html` e `/index.php`.
- Giri: sei, alternati nella stessa sessione, sempre nello stesso ordine (elenco, ricerca, health, file statico, script PHP). Il giro 1 è scartato come riscaldamento: restano cinque corse misurate per ogni serie. Nel Livello 0 erano quattro per Laravel e cinque per i controlli; ora sono cinque per tutte.
- Un utente virtuale.
- Durata: 60 secondi per elenco e ricerca, 10 secondi per gli altri. Con 10 secondi l'elenco faceva solo 5 richieste per corsa (corsa di prova: mediana 2.27 s), troppo poche per un p95.
- Pausa di 0.1 secondi solo sui due controlli, come nel Livello 0.
- `smoke.js` ha due opzioni nuove:
  - `DURATION`, predefinita 10s;
  - `BASE_URL`, predefinita la porta 8000, che accetta solo indirizzi locali.
- Le due opzioni le ho provate prima con un valore non valido, che fa fallire la corsa senza richieste.
- Comandi, dalla cartella `benchmarks/`, dove `N` è il numero del giro:

```powershell
k6 run -e ENDPOINT=/api/v1/ingredients -e DURATION=60s --summary-export=results/level-01/list-runN.json smoke.js
k6 run -e "ENDPOINT=/api/v1/ingredients?search=farina" -e DURATION=60s --summary-export=results/level-01/search-runN.json smoke.js
k6 run -e ENDPOINT=/api/v1/health --summary-export=results/level-01/health-runN.json smoke.js
k6 run -e BASE_URL=http://127.0.0.1:8001 -e ENDPOINT=/ok.html -e PAUSE=0.1 --summary-export=results/level-01/ctrl-static-runN.json smoke.js
k6 run -e BASE_URL=http://127.0.0.1:8001 -e ENDPOINT=/index.php -e PAUSE=0.1 --summary-export=results/level-01/ctrl-php-runN.json smoke.js
```

- Calcoli, come nel Livello 0:
  - mediana e p95 di ogni corsa: campi `med` e `p(95)` di `http_req_duration` nei file JSON;
  - La mediana tra le corse è la mediana delle cinque mediane delle singole corse (giro 1 scartato come riscaldamento). Il min–max tra le corse sono il minimo e il massimo di quelle cinque mediane; la mediana dei p95 è la mediana dei cinque p95.
- Errori: nessuna richiesta è fallita in nessuna delle 30 corse.

### Risultati

Cinque corse misurate per serie, trenta in totale.

| Serie | Mediana tra le corse | Min–max tra le corse | Mediana dei p95 |
|---|---|---|---|
| Elenco | 2064.8 ms | 1567.6–2404.7 ms | 2469.4 ms |
| Ricerca `farina` | 454.5 ms | 441.3–494.0 ms | 539.8 ms |
| Laravel, `/api/v1/health` | 354.7 ms | 343.9–402.4 ms | 391.1 ms |
| Script PHP minimo | 2.04 ms | 1.21–2.14 ms | 3.62 ms |
| File statico | 1.44 ms | 0.81–1.60 ms | 3.43 ms |

### Lettura

Lettura preliminare, non verificata con un profiler.

- I controlli di oggi sono veloci come nel Livello 0 (script PHP 2.04 ms contro 2.29 ms, file statico 1.44 ms contro 1.73 ms): il computer e il server di PHP vanno alla stessa velocità.
- Laravel `/api/v1/health` oggi è 354.7 ms contro i 101.8 ms del Livello 0, circa 3.5 volte di più. La causa non è stata indagata. Quindi i numeri di Laravel del Livello 0 e di questo livello non sono confrontabili, e i miglioramenti si confrontano con questa baseline.
- L'elenco costa circa 1710 ms più di `health` (2064.8 contro 354.7). La ricerca costa circa 100 ms più di `health`, anche se, senza un indice sul nome, il database dovrebbe guardare tutte le 20.000 righe (non verificato con `EXPLAIN`). Il costo sembra dipendere dalle righe restituite (20.000 contro 485) più che dalla ricerca nel database. È un'ipotesi: non è stato misurato dove va il tempo (query, creazione dei modelli, JSON).

### Limiti e osservazioni

- Un solo computer e una sola sessione. k6 e i server girano sullo stesso computer e si contendono la CPU.
- L'elenco è molto variabile: le mediane vanno da 1567.6 ms (giro 6, 38 richieste) a 2404.7 ms (giro 5, 26 richieste), senza una causa nota.
- Le serie da 10 secondi hanno poche richieste (`health`: 25–29 per corsa), quindi il loro p95 è approssimativo.
- Laravel e controlli non hanno lo stesso ritmo (nessuna pausa contro 0.1 s), come nel Livello 0.
- OPcache non caricata, `APP_DEBUG=true` e `curl` bloccato: l'effetto sulle misure non è stato verificato.
- Una sola ricerca (`farina`) e una sola dimensione del dataset (20.000 righe): altri testi o altre dimensioni possono dare numeri diversi.
- I riepiloghi sono calcolati dai file JSON con un comando che non è nel repository.

## Indagine: il server di sviluppo a due velocità (issue #77)

Nella baseline del Livello 0 l'endpoint `/api/v1/health` rispondeva con una mediana di circa 98 ms. Nelle sessioni successive la stessa rotta, con lo stesso codice, rispondeva in circa 340 ms. Questa sezione riassume cosa è stato escluso, cosa è stato osservato e quale regola ne segue.

### Cosa è stato osservato

- **Il codice non c'entra.** Il codice del Livello 0, rimesso in esecuzione, ha dato circa 332 ms nello stesso periodo.
- **Gli altri sospetti sono esclusi come causa:** Docker, OPcache, porta, cartella di lavoro, maiuscole nel percorso, priorità del processo, moduli PHP caricati, reindirizzamento dell'output e variabili d'ambiente passate al processo.
- **Il server ha due stati.** Con la stessa rotta e lo stesso codice, un processo risponde o in circa 98 ms o in circa 340 ms, con pochissima variazione dentro lo stesso stato.
- **Il passaggio è reversibile e può essere rapido.** Nella prova `i2-artisan-windows-terminal` una corsa su sei è passata da 100,8 ms a 346,2 ms ed è tornata a 97,9 ms. Nella prova `o1-three` un `artisan serve` avviato da meno di tre minuti è stato veloce, mentre uno avviato circa quindici minuti prima, sullo stesso computer e nello stesso minuto, è stato lento.
- **Il terminale di avvio non è la causa.** Nella prova `m1-pair` il server avviato da VS Code era lento e quello avviato dal Windows Terminal era veloce. Sei minuti dopo, nella prova `n1-direct`, i ruoli si erano invertiti.

### Cosa non si sa

Il meccanismo che fa passare un processo da uno stato all'altro. Non è stato trovato con le prove fatte e non è stato trattato come un problema del progetto.

### Regola di misura

Prima di ogni serie di misure:

1. si avvia un server nuovo;
2. si misura `/api/v1/health` prima e dopo la serie;
3. se la mediana del controllo è intorno a 100 ms, il server è nello stato veloce e la serie vale; se è intorno a 340 ms, la serie si scarta e si riparte da un server nuovo.

I numeri del Livello 1 pubblicati prima di questa regola potrebbero essere stati misurati nello stato lento. Verranno rifatti con la regola e confrontati con quelli vecchi.

### Dati

Le mediane di ogni corsa delle 21 prove valide sono in `benchmarks/results/level-01/health-investigation.json`.