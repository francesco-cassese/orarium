# Prestazioni del frontend: baseline

Misura del bundle di produzione del frontend, dopo la pulizia del template. Le prime tre sezioni sono la baseline del Livello 0; la baseline della pagina dell'elenco è nella sezione del Livello 1, più sotto. Le misure future si confrontano con la baseline del loro livello.

## Bundle di produzione

- Data: 2026-11-01
- Comando: `pnpm build` nella cartella `frontend/`
- Node: v24.16.0 (il progetto dichiara `^20.19.0 || >=22.12.0`; la CI usa Node 22)
- pnpm: 11.7.0
- React: 19.3.1, React DOM 19.3.1
- Vite: 8.3.5
- Commit: `97dd24d` su `main`

| File | Dimensione | Gzip |
|---|---|---|
| `dist/assets/index-*.js` | 219.68 kB | 68.66 kB |
| `dist/index.html` | 0.38 kB | 0.26 kB |

Il build trasforma 15 moduli. Il tempo di build è indicativo: nelle prove è variato tra 100 e 110 ms. Non c'è un file CSS separato.

## Ripetibilità

Due `pnpm build` consecutivi hanno prodotto gli stessi valori: stessa dimensione e stesso nome di file (`index-BPd8qGJc.js`) con l'hash del contenuto.

## Limiti

- Il peso riguarda solo il bundle: non è una misura del tempo di caricamento nel browser.
- Il progetto dichiara Node `^20.19.0 || >=22.12.0`, e la CI usa Node 22. La misura è stata fatta con Node v24.16.0. Non è stato verificato se con Node 22 il bundle cambi.
- Per confrontare, rilancia `pnpm build` nello stesso ambiente e confronta file per file.

## Livello 1: pagina dell'elenco degli ingredienti (versione semplice)

Misura della pagina dell'elenco con il database di sviluppo pieno. È la versione semplice della pagina: scarica tutte le righe, le mostra tutte in una tabella, senza paginazione, e a ogni tasto nel campo di ricerca fa una richiesta all'API, senza attendere. Questa misura è stata rifatta il 2026-11-06 con la regola di controllo descritta in [`PERFORMANCE.md`](PERFORMANCE.md) (sezione «Indagine»); la prima misura (2026-11-05) è riassunta in «Limiti e osservazioni» e non va usata come baseline.

### Ambiente

- Data della serie: 2026-11-06, intorno alle 02:03 (ora italiana).
- Computer, sistema e piano di alimentazione: gli stessi della baseline dell'endpoint in [`PERFORMANCE.md`](PERFORMANCE.md) (Windows 11 Home 10.0.26200, Intel Core 5 120U, RAM 15.6 GB, piano Bilanciato), con il portatile collegato alla corrente (dichiarato da chi ha misurato, non verificato con uno strumento). I programmi aperti non sono stati registrati.
- Browser: Chromium 156.0.8078.4, quello di Playwright 1.64.1, in modalità senza finestra (`headless`, il valore predefinito di Playwright). Dimensione della finestra: quella predefinita di Playwright, non registrata.
- Rete e CPU: nessuna limitazione. Tutto gira su `127.0.0.1`.
- Node v24.16.0 (rilevato dallo script). La versione di pnpm non è stata rilevata in questa serie.
- Pagina: build di produzione (`pnpm build`), servita con `pnpm preview` su `127.0.0.1:4173`, avviata dallo script e nuova a ogni tentativo.
- Backend: Laravel con `php artisan serve` sulla porta 8000, avviato dallo script in una finestra nascosta e nuovo a ogni tentativo. Ambiente come nella baseline dell'endpoint (PHP 8.4.27, Laravel 13.39.0, `APP_ENV=local`, `APP_DEBUG=true`, OPcache non caricata).
- Codice misurato: quello di `main` (`b0a5fbd`). Lo script `benchmarks/measure-page.ps1` è quello del commit `a90aeae`, committato dopo la serie con lo stesso contenuto.

### Dataset

- Database MySQL in Docker, con 20.000 ingredienti dal seeder `IngredientSeeder` (seme fisso): `GET /api/v1/ingredients` restituisce 20.000 righe (verificato nelle misure: `rows` vale 20000).
- La ricerca `f` restituisce 3.983 righe (misurato dallo script).

### Metodo

- Strumento: uno script Playwright, `benchmarks/page.mjs`, che pilota Chromium. È in un pacchetto separato (`benchmarks/package.json`), perché non fa parte del frontend.
- Sei corse nello stesso avvio del browser, ognuna in un contesto nuovo (senza cookie né cache in comune). La prima è scartata come riscaldamento: restano cinque corse misurate.
- Ogni corsa misura, con `performance.now()`:
  - `listMs`: dalla navigazione fino a quando ci sono le righe nella tabella e sono passati due frame di disegno (`requestAnimationFrame` due volte). Dentro c'è anche l'attesa dell'API.
  - `listApiMs`: durata della richiesta dell'elenco, da `PerformanceResourceTiming` (`duration`, da `startTime` a `responseEnd`).
  - `keyMs`: da un tasto (`f`) nel campo di ricerca vuoto, con la lista già a schermo, fino alla tabella aggiornata e a due frame di disegno.
  - `keyApiMs`: durata della richiesta della ricerca `f`.
  - `keyRows`: righe nella tabella dopo il tasto.
  - `domNodes`: `document.querySelectorAll('*').length` a lista pronta; `rows`: righe della tabella.
  - `requestsTypingFarina`: richieste all'API scrivendo `farina` in una pagina nuova, senza pausa tra i tasti.
- Il riepilogo è la mediana tra le corse misurate, con minimo e massimo, come per le misure dell'endpoint.
- Regola di controllo: lo script avvia server nuovi, misura `/api/v1/health` con k6 per 10 secondi prima e dopo la serie di sei corse, e la serie vale solo se le due mediane sono sotto i 150 ms. Altrimenti i file si rinominano con `-scartato` e la serie si ripete, fino a tre tentativi. In questa serie il primo tentativo era valido.
- Comando, dalla radice del progetto, con il database acceso e nessun server sulle porte 8000 e 4173 (la prima volta servono `pnpm install` e `pnpm exec playwright install chromium` nella cartella `benchmarks/`):

```powershell
cd frontend; pnpm build; cd ..
.\benchmarks\measure-page.ps1
```

- Opzioni di `page.mjs`: `BASE_URL` (solo indirizzi locali, predefinito `http://127.0.0.1:4173`), `RUNS` (predefinito 6), `OUT` (impostato dallo script).
- I valori grezzi di tutte le corse e il riepilogo sono in `benchmarks/results/level-01/rerun/page.json`; i due controlli in `page-health-before.json` e `page-health-after.json`, nella stessa cartella.

### Risultati

Bundle di produzione, rifatto con `pnpm build` (stessi nomi e dimensioni della prima misura):

| File | Dimensione | Gzip |
|---|---|---|
| `dist/assets/index-*.css` | 140.73 kB | 20.14 kB |
| `dist/assets/index-*.js` | 221.32 kB | 69.31 kB |
| `dist/index.html` | 0.45 kB | 0.29 kB |

Il build trasforma 20 moduli. Nel Livello 0 erano 15 moduli e nessun file CSS, con un file JavaScript da 219.68 kB (68.66 kB gzip).

Controllo: `/api/v1/health` 98.1 ms prima e 98.4 ms dopo (mediana di una corsa da 10 secondi ciascuna).

Pagina, cinque corse misurate:

| Misura | Mediana | Min–max |
|---|---|---|
| Lista a schermo (`listMs`) | 1142.6 ms | 1132.2–1158.4 ms |
| di cui richiesta dell'elenco (`listApiMs`) | 598.7 ms | 591.0–622.1 ms |
| Dopo un tasto (`keyMs`) | 391.7 ms | 373.9–425.2 ms |
| di cui richiesta della ricerca (`keyApiMs`) | 242.2 ms | 238.6–260.9 ms |
| Nodi DOM | 80023 | 80023–80023 |
| Righe della tabella | 20000 | 20000–20000 |
| Richieste scrivendo `farina` | 6 | 6–6 |

### Lettura

Lettura preliminare, non verificata con un profiler.

- Poco più della metà del tempo per la lista a schermo è l'attesa dell'API (598.7 su 1142.6 ms, differenza tra due mediane). La differenza tra `listMs` e `listApiMs`, calcolata corsa per corsa (corse 2–6), ha mediana 541.2 ms (min–max 530.9–543.9 ms): è il tempo dopo l'arrivo dei dati, con i dati da elaborare e la tabella da disegnare.
- La variazione tra corse è piccola: `listMs` sta in 26 ms (1132.2–1158.4).
- Dopo un tasto la differenza tra `keyMs` e `keyApiMs` ha mediana 139.6 ms (min–max 131.7–164.3 ms, calcolata come sopra) per 3.983 righe.
- La richiesta dell'elenco vista dal browser (598.7 ms) è vicina alla mediana di k6 sull'endpoint (573.7 ms, in [`PERFORMANCE.md`](PERFORMANCE.md)). La differenza di circa 25 ms non è stata indagata.
- Il tempo dopo l'arrivo dei dati è praticamente uguale a quello della prima misura (541 contro 536 ms per la lista, 140 contro 143 ms per il tasto), mentre le richieste all'API sono circa tre volte più veloci (598.7 contro 1797.0 ms, 242.2 contro 711.8 ms). È coerente con un rallentamento solo lato server nella prima misura.
- La pagina ha 80023 nodi DOM, cioè 4 per riga (una riga e tre celle) per 20.000 righe, più 23 nodi fissi.
- Scrivendo `farina` partono 6 richieste, una per lettera: la pagina non aspetta che chi scrive finisca.

### Limiti e osservazioni

- **Prima misura, da non usare come baseline.** Il 2026-11-05, alle 16:21, la stessa procedura senza controlli aveva dato `listMs` 2319.9 ms, `listApiMs` 1797.0 ms, `keyMs` 843.4 ms e `keyApiMs` 711.8 ms. Era probabilmente il server nello stato lento descritto in [`PERFORMANCE.md`](PERFORMANCE.md) (sezione «Indagine»). I valori di quella misura sono ancora in `benchmarks/results/level-01/page.json`.
- Un solo computer, una sola sessione registrata, cinque corse misurate, un solo avvio del browser.
- Il controllo vede lo stato del server solo prima e dopo le sei corse: un passaggio di stato nel mezzo che si risolve prima del secondo controllo non verrebbe notato.
- Il tempo «a schermo» è un'approssimazione: due frame di disegno dopo che le righe sono nel DOM, non una misura dei tempi di disegno del browser. Include il piccolo ritardo dei comandi di Playwright, non misurato.
- La finestra è quella predefinita di Playwright e non è stata registrata. Nessuna limitazione di CPU e di rete: tutto su `127.0.0.1`.
- Una sola ricerca per il tasto (`f`) e una per le richieste (`farina`); altri testi danno numeri diversi.
- Il numero di richieste è stato misurato scrivendo senza pausa tra i tasti.
- Le due differenze (`listMs` meno `listApiMs`, `keyMs` meno `keyApiMs`) sono calcolate con un comando che non è nel repository e non escono dallo script.
- L'ambiente del backend è lo stesso della baseline dell'endpoint: OPcache non caricata e `APP_DEBUG=true`, con effetto non verificato (vedi [`PERFORMANCE.md`](PERFORMANCE.md)).

## Livello 1: pagina dell'elenco con il paginatore

Misura della pagina dell'elenco con il paginatore: l'API restituisce una pagina alla volta (50 righe) e la pagina mostra la pagina corrente con i pulsanti Indietro e Avanti. Le sezioni precedenti restano intatte: il confronto è con la sezione «Livello 1: pagina dell'elenco degli ingredienti (versione semplice)».

### Ambiente

- Data della serie: 2026-11-07, intorno alle 16:19 (ora italiana; in `page.json` è `2026-11-07T15:19:34.789Z`).
- Computer, database e metodo: gli stessi della baseline di questo livello. Build di produzione (`pnpm build`), servita con `pnpm preview` su `127.0.0.1:4173`. Browser e Node registrati in `page.json`: Chromium 156.0.8078.4, Node v24.16.0.
- Condizioni di misura, dichiarate da chi ha misurato e non verificate con uno strumento: portatile collegato alla corrente; durante la serie erano aperti solo Docker (con il database), VS Code e due terminali.
- Codice misurato: frontend alla commit `0f57af9` del branch `feat/85-add-ingredient-pager`; backend `main` alla commit `8921850` (PR #84), da cui parte il branch (`git merge-base main HEAD`). Lo script `benchmarks/page.mjs` aveva una modifica non ancora committata al momento della serie; lo stesso contenuto è stato committato in `88716ef`.
- Cosa è cambiato nel metodo: solo il modo in cui lo script riconosce le richieste. Ora le trova per percorso e parametri dell'indirizzo (con `new URL`), non per come finisce la stringa. Le grandezze misurate sono le stesse.

### Risultati

Misure a confronto. La colonna «Con paginatore» riporta la mediana delle cinque corse misurate; l'ultima colonna riporta minimo e massimo della stessa serie.

| Misura | Baseline | Con paginatore | Min–max con paginatore |
|---|---|---|---|
| Lista a schermo (`listMs`) | 1142.6 ms | 238.5 ms | 226.3–245.2 ms |
| di cui richiesta dell'elenco (`listApiMs`) | 598.7 ms | 154.6 ms | 141.5–158.7 ms |
| Dopo un tasto (`keyMs`) | 391.7 ms | 191.1 ms | 176.8–213.3 ms |
| di cui richiesta della ricerca (`keyApiMs`) | 242.2 ms | 145.0 ms | 141.4–161.0 ms |
| Nodi DOM | 80023 | 226 | 226–226 |
| Righe della tabella | 20000 | 50 | 50–50 |
| Righe dopo il tasto `f` | 3983 | 50 | 50–50 |
| Richieste scrivendo `farina` | 6 | 6 | 6–6 |

- Controllo: `/api/v1/health` 99.3 ms prima e 98.5 ms dopo (mediane di k6, contro 98.1 e 98.4 ms della baseline). In questa serie il primo tentativo era valido.
- Bundle di produzione: 21 moduli; JavaScript 221.93 kB (69.51 kB gzip); CSS 140.73 kB (20.14 kB gzip) e HTML 0.45 kB (0.29 kB gzip), come nella baseline. La baseline aveva 20 moduli e JavaScript da 221.32 kB (69.31 kB gzip).

### Lettura

Solo fatti e confronti tra misure.

- Il tempo dopo l'arrivo dei dati (`listMs` meno `listApiMs`, corse 2–6) ha mediana 84.8 ms (min–max 81.3–86.5 ms), contro 541.2 ms nella baseline.
- Per il tasto, il tempo dopo la risposta (`keyMs` meno `keyApiMs`, corse 2–6) ha mediana 35.4 ms (min–max 34.3–53.9 ms), contro 139.6 ms nella baseline.
- La richiesta dell'elenco pesa ora circa il 65% del tempo a schermo (rapporto tra due mediane: 154.6 su 238.5 ms).
- Le richieste scrivendo `farina` sono rimaste 6: la pagina non ha cambiato il comportamento mentre si scrive.

### Limiti e osservazioni

- Il confronto non è tra lo stesso lavoro: 20.000 righe nella baseline, 50 con il paginatore.
- Il miglioramento vale per la paginazione dell'API (#83) e per il paginatore (#85) insieme e non si può dividere: non c'è una misura intermedia.
- Prima di questa serie, due lanci non sono arrivati a un risultato: il primo si è fermato perché la porta 8000 era occupata; il secondo è fallito perché lo script non riconosceva la richiesta dell'elenco con il parametro `page` (corretto in `88716ef`). Il file incompleto del secondo lancio è stato cancellato. La serie riportata è il primo tentativo valido dello script.
- Una sola sessione, un solo computer. Valgono anche qui gli altri limiti della sezione precedente (stesso metodo).
- Le due differenze (`listMs` meno `listApiMs`, `keyMs` meno `keyApiMs`) sono calcolate con un comando che non è nel repository e non escono dallo script.

### File

- `benchmarks/results/level-01/pager/page.json`: valori grezzi di tutte le corse e riepilogo.
- `benchmarks/results/level-01/pager/page-health-before.json` e `page-health-after.json`: i due controlli su `/api/v1/health`.