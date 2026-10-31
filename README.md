# Orarium

Pianificazione di produzione e scorte per laboratori alimentari.

Orarium aiuta un laboratorio a organizzare il lavoro e a tenere sotto controllo il magazzino:
dagli ordini dei clienti al piano di produzione, e dalle scorte alla tracciabilità dei lotti.

## Come nasce questo progetto

Sto costruendo Orarium **un livello alla volta**, per imparare a fondo
PHP, Laravel e React e, soprattutto, **come si ottimizza un'applicazione**.

Ogni livello segue lo stesso ciclo:

1. una versione semplice che funziona;
2. una misura di partenza, con dati ripetibili;
3. un miglioramento;
4. un test che impedisce di tornare indietro;
5. il confronto prima/dopo, documentato.

La storia del progetto è nella cronologia dei commit, nelle issue e nelle pull request.

## Stato attuale

**Livello 0 — Fondamenta**, in corso.

- Completato: struttura del repository, processo di lavoro, modelli di issue e pull request, decisioni tecniche, backend Laravel di base.
- In corso: ambiente di sviluppo con MySQL e Redis in Docker.
- In arrivo: frontend React, test, CI e strumenti di misura.

## Stack previsto

Strumenti scelti per il progetto. Non tutti sono ancora installati.

- **Backend**: Laravel (API JSON)
- **Frontend**: React con Vite, stile con Bootstrap e SCSS
- **Database e cache**: MySQL e Redis in Docker
- **Qualità**: Pest, Pint, Larastan, ESLint, Vitest
- **Misure**: k6
- **Integrazione continua**: GitHub Actions

## Struttura del repository

```
backend/      API Laravel
frontend/     applicazione React
benchmarks/   script di misura e risultati
docs/         regole di lavoro e decisioni
```

## Come lavoro

Il flusso segue le pratiche di un team: issue, branch, Conventional Commits, pull request
con controlli automatici e squash merge. La documentazione è in [`docs/`](docs/README.md): il processo
in [`WORKFLOW.md`](docs/WORKFLOW.md), le scelte tecniche con le opzioni valutate in
[`DECISIONS.md`](docs/DECISIONS.md).

## Avvio in locale

Le istruzioni complete arriveranno con il Livello 0. Per il backend e il database, vedi [`backend/README.md`](backend/README.md).

## Autore

Francesco Cassese — sviluppatore full stack (PHP, Laravel, React).
