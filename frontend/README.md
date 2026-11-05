# Orarium — frontend

[![Frontend CI](https://github.com/francesco-cassese/orarium/actions/workflows/frontend.yml/badge.svg)](https://github.com/francesco-cassese/orarium/actions/workflows/frontend.yml)

Interfaccia React dell'applicazione Orarium, creata con Vite.

## Requisiti

- Node.js `^20.19.0` oppure `>=22.12.0`, come indica il campo `engines` di `package.json`. La CI usa Node 22 (D-008). Controlla la versione con `node -v`.
- pnpm, nella versione indicata nel campo `packageManager` di `package.json` (`pnpm@11.7.0`). Verifica la versione installata con `pnpm -v`.
  Per installarlo segui la [documentazione ufficiale di pnpm](https://pnpm.io/installation).

## Primo avvio

Dalla cartella `frontend/`:

1. Installa le dipendenze:
   ```
   pnpm install
   ```
2. Crea il file di configurazione locale a partire dall'esempio (il file `.env` non va su Git):

   ```
   Copy-Item .env.example .env
   ```

   Su Mac o Linux il comando è `cp .env.example .env`.

   Il file contiene `VITE_API_BASE_URL`, l'indirizzo dell'API che il browser chiama. Le variabili con prefisso `VITE_` finiscono nel codice che arriva al browser: non metterci segreti.

3. Avvia il server di sviluppo:
   ```
   pnpm dev
   ```
4. Apri [http://localhost:5173](http://localhost:5173) nel browser.

## Comandi utili

- `pnpm dev`: avvia il server di sviluppo, con aggiornamento automatico delle pagine.
- `pnpm build`: crea la versione di produzione nella cartella `dist/`.
- `pnpm preview`: serve in locale la versione di produzione creata da `pnpm build`.
- `pnpm lint`: controlla il codice con ESLint.
- `pnpm format`: formatta i file con Prettier, modificandoli.
- `pnpm format:check`: verifica la formattazione senza modificare i file.
- `pnpm test`: esegue i test una volta sola con Vitest.
- `pnpm test:watch`: esegue i test e li rilancia quando cambiano i file.
- `pnpm check`: esegue in sequenza lint, controllo della formattazione e test. È il controllo da lanciare prima di aprire una pull request.

## Struttura

- `src/`: codice dell'applicazione. I test stanno accanto al file che verificano, con suffisso `.test.jsx` (ad esempio `App.test.jsx` per `App.jsx`). Vitest li riconosce dal nome.
- `src/test/setup.js`: configurazione dei test, eseguita prima di ogni file di test.
- `src/styles/main.scss`: gli stili dell'applicazione. Importa solo le parti di Bootstrap usate (D-002). Gli avvisi di deprecazione di Sass causati da Bootstrap 5.3 sono silenziati in `vite.config.js`, vedi D-002.
- `src/api/`: le chiamate all'API del backend, con la forma delle risposte descritta in JSDoc (D-006).
- `src/utils/`: funzioni semplici condivise, come `formatPrice` (da centesimi a euro) e `formatUnit` (mostra `pc` come `pz`).
- `public/`: file statici serviti così come sono.
- `eslint.config.js`: configurazione di ESLint.
- `vite.config.js`: configurazione di Vite. Contiene anche la configurazione di Vitest, nella sezione `test`.
- `.prettierrc`: regole di formattazione di Prettier.
- `.prettierignore`: file che Prettier non formatta.

## Backend

La pagina principale mostra la lista degli ingredienti chiamando `GET /api/v1/ingredients`: per vederla serve il backend acceso (`php artisan serve`, vedi [`../backend/README.md`](../backend/README.md)) con i dati di esempio (`php artisan migrate:fresh --seed`).

In sviluppo il browser chiama l'API **direttamente**, a un indirizzo diverso da quello della pagina (porta 5173 contro 8000). Per il browser sono due origini diverse, quindi l'API deve autorizzarlo con le intestazioni CORS. Con la configurazione predefinita di Laravel la risposta contiene `Access-Control-Allow-Origin: *` (verificato con `curl`), cioè qualunque sito può leggerla: va bene in sviluppo, e va **ristretto prima del deploy**. Se l'origine autorizzata non coincide con quella della pagina, il browser scarta la risposta anche se il server ha risposto `200`, e la pagina mostra il messaggio di errore.

## Documentazione

- [Processo di lavoro](../docs/WORKFLOW.md): issue, branch, commit, pull request.
- [Convenzioni di nome e di dati](../docs/CONVENTIONS.md): database, API, codice.
- [Decisioni tecniche](../docs/DECISIONS.md): perché il progetto è fatto così.
