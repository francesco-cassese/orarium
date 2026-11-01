# Orarium — frontend

Interfaccia React dell'applicazione Orarium, creata con Vite.

## Requisiti

- Node.js `^20.19.0` oppure `>=22.12.0`, il requisito di Vite. Controlla la versione con `node -v`.
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

## Struttura

- `src/`: codice dell'applicazione.
- `public/`: file statici serviti così come sono.
- `eslint.config.js`: configurazione di ESLint.
- `vite.config.js`: configurazione di Vite.

## Backend

Per ora il frontend non chiama il backend. Per avviare l'API e il database, segui [`../backend/README.md`](../backend/README.md).

## Documentazione

- [Processo di lavoro](../docs/WORKFLOW.md): issue, branch, commit, pull request.
- [Decisioni tecniche](../docs/DECISIONS.md): perché il progetto è fatto così.
