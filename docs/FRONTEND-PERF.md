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

Misura della pagina dell'elenco con il database di sviluppo pieno. È la versione semplice della pagina: scarica tutte le righe, le mostra tutte in una tabella, senza paginazione, e a ogni tasto nel campo di ricerca fa una richiesta all'API, senza attendere.

### Ambiente

- Data delle corse: 2026-11-05, alle 16:21 (ora italiana).
- Computer, sistema e piano di alimentazione: gli stessi della baseline dell'endpoint in [`PERFORMANCE.md`](PERFORMANCE.md) (Windows 11 Home 10.0.26200, Intel Core 5 120U, RAM 15.6 GB, piano Bilanciato), con il portatile collegato alla corrente.
- Programmi aperti: browser, Docker Desktop, terminale, VS Code.
- Browser: Chromium 156.0.8078.4, quello di Playwright 1.64.1, in modalità senza finestra (`headless`, il valore predefinito di Playwright). Dimensione della finestra: quella predefinita di Playwright, non registrata.
- Rete e CPU: nessuna limitazione. Tutto gira su `127.0.0.1`.
- Node v24.16.0, pnpm 11.7.0.
- Pagina: build di produzione (`pnpm build`), servita con `pnpm preview` su `127.0.0.1:4173`.
- Backend: Laravel con `php artisan serve` sulla porta 8000, lo stesso ambiente della baseline dell'endpoint (vedi [`PERFORMANCE.md`](PERFORMANCE.md)).
- Commit misurato: `06254cc` su `main`. Lo script `benchmarks/page.mjs` e `benchmarks/package.json` sono quelli di questa modifica: erano già nel repository, in un commit di questa PR, prima della corsa.

### Dataset

- Database MySQL in Docker, con 20.000 ingredienti dal seeder `IngredientSeeder` (seme fisso): `GET /api/v1/ingredients` restituisce 20.000 righe.
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
- Comandi (la prima volta si installano pacchetti e browser; i server di Laravel e del database devono essere accesi):

```powershell
cd benchmarks
pnpm install
pnpm exec playwright install chromium
cd ../frontend
pnpm build
pnpm preview --host 127.0.0.1 --port 4173 --strictPort   # in un secondo terminale
cd ../benchmarks
node page.mjs
```

- Opzioni dello script: `BASE_URL` (solo indirizzi locali, predefinito `http://127.0.0.1:4173`), `RUNS` (predefinito 6), `OUT` (predefinito `results/level-01/page.json`).
- I valori grezzi di tutte le corse e il riepilogo sono in `benchmarks/results/level-01/page.json`.

### Risultati

Bundle di produzione, rifatto con `pnpm build` (due build consecutive hanno dato gli stessi nomi e le stesse dimensioni):

| File | Dimensione | Gzip |
|---|---|---|
| `dist/assets/index-*.css` | 140.73 kB | 20.14 kB |
| `dist/assets/index-*.js` | 221.32 kB | 69.31 kB |
| `dist/index.html` | 0.45 kB | 0.29 kB |

Il build trasforma 20 moduli. Nel Livello 0 erano 15 moduli e nessun file CSS, con un file JavaScript da 219.68 kB (68.66 kB gzip).

Pagina, cinque corse misurate:

| Misura | Mediana | Min–max |
|---|---|---|
| Lista a schermo (`listMs`) | 2319.9 ms | 2092.4–3165.9 ms |
| di cui richiesta dell'elenco (`listApiMs`) | 1797.0 ms | 1556.4–2479.5 ms |
| Dopo un tasto (`keyMs`) | 843.4 ms | 721.9–1217.2 ms |
| di cui richiesta della ricerca (`keyApiMs`) | 711.8 ms | 578.9–839.0 ms |
| Nodi DOM | 80023 | 80023–80023 |
| Righe della tabella | 20000 | 20000–20000 |
| Richieste scrivendo `farina` | 6 | 6–6 |

### Lettura

Lettura preliminare, non verificata con un profiler.

- Circa tre quarti del tempo per la lista a schermo è l'attesa dell'API (1797.0 su 2319.9 ms, differenza tra due mediane). La differenza tra `listMs` e `listApiMs`, calcolata a mano corsa per corsa (corse 2–6), ha mediana 536 ms (min–max 523–686 ms): è il tempo dopo l'arrivo dei dati, con i dati da elaborare e la tabella da disegnare.
- La variazione tra corse sta soprattutto nella risposta dell'API: la corsa più lenta (corsa 2, 3165.9 ms) ha anche la richiesta più lunga (2479.5 ms), mentre la differenza è alta solo in quella corsa (686 ms contro 523–540 ms). La causa della variazione non è stata indagata.
- Dopo un tasto la differenza tra `keyMs` e `keyApiMs` ha mediana 143 ms (min–max 132–378 ms, calcolata a mano come sopra) per 3.983 righe.
- La pagina ha 80023 nodi DOM, cioè 4 per riga (una riga e tre celle) per 20.000 righe, più 23 nodi fissi.
- Scrivendo `farina` partono 6 richieste, una per lettera: la pagina non aspetta che chi scrive finisca.

### Limiti e osservazioni

- Un solo computer, una sola sessione registrata, cinque corse misurate. Due prove precedenti non registrate (stessa configurazione) hanno dato mediane vicine: `listMs` 2522 e 2337 ms, `keyMs` 1074 e 872 ms. Tra sessioni i valori possono cambiare di circa il 10%.
- Il tempo «a schermo» è un'approssimazione: due frame di disegno dopo che le righe sono nel DOM, non una misura dei tempi di disegno del browser. Include il piccolo ritardo dei comandi di Playwright, non misurato.
- La finestra è quella predefinita di Playwright e non è stata registrata. Nessuna limitazione di CPU e di rete: tutto su `127.0.0.1`.
- Una sola ricerca per il tasto (`f`) e una per le richieste (`farina`); altri testi danno numeri diversi.
- Il numero di richieste è stato misurato scrivendo senza pausa tra i tasti.
- Le due differenze (`listMs` meno `listApiMs`, `keyMs` meno `keyApiMs`) sono calcolate a mano sui valori grezzi e non escono dallo script.
- L'ambiente del backend è lo stesso della baseline dell'endpoint: OPcache non caricata, `APP_DEBUG=true` e `curl` di PHP bloccato, con effetto non verificato (vedi [`PERFORMANCE.md`](PERFORMANCE.md)).