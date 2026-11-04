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
2. Avvia il server di sviluppo:
   ```
   pnpm dev
   ```
3. Apri [http://localhost:5173](http://localhost:5173) nel browser.

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
- `public/`: file statici serviti così come sono.
- `eslint.config.js`: configurazione di ESLint.
- `vite.config.js`: configurazione di Vite. Contiene anche la configurazione di Vitest, nella sezione `test`.
- `.prettierrc`: regole di formattazione di Prettier.
- `.prettierignore`: file che Prettier non formatta.

## Backend

Per ora il frontend non chiama il backend. Per avviare l'API e il database, segui [`../backend/README.md`](../backend/README.md).

## Documentazione

- [Processo di lavoro](../docs/WORKFLOW.md): issue, branch, commit, pull request.
- [Convenzioni di nome e di dati](../docs/CONVENTIONS.md): database, API, codice.
- [Decisioni tecniche](../docs/DECISIONS.md): perché il progetto è fatto così.
