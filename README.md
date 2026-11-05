# Orarium

[![Frontend CI](https://github.com/francesco-cassese/orarium/actions/workflows/frontend.yml/badge.svg)](https://github.com/francesco-cassese/orarium/actions/workflows/frontend.yml)
[![Backend CI](https://github.com/francesco-cassese/orarium/actions/workflows/backend.yml/badge.svg)](https://github.com/francesco-cassese/orarium/actions/workflows/backend.yml)

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

**Livello 0 — Fondamenta**, completato con il rilascio [`v0.0.1`](https://github.com/francesco-cassese/orarium/releases/tag/v0.0.1). Il documento del livello è in [`docs/levels/00-fondamenta.md`](docs/levels/00-fondamenta.md).

- Completato: struttura del repository, processo di lavoro, modelli di issue e pull request, decisioni tecniche, backend Laravel di base, ambiente di sviluppo con MySQL e Redis in Docker, strumenti di qualità del backend (Pest, Pint, Larastan), frontend React di base (Vite), strumenti di qualità del frontend (ESLint, Prettier, Vitest), CI di frontend e backend (GitHub Actions), con i controlli richiesti prima del merge, baseline di prestazioni del backend e del frontend registrate in [`docs/PERFORMANCE.md`](docs/PERFORMANCE.md) e [`docs/FRONTEND-PERF.md`](docs/FRONTEND-PERF.md).
- In corso: Livello 1 (milestone `L01 Ingredients`), con una versione semplice già fatta: endpoint e pagina dell'elenco degli ingredienti, seeder con dati coerenti e misure di partenza dell'endpoint (`docs/PERFORMANCE.md`) e della pagina (`docs/FRONTEND-PERF.md`). Prossimo passo: il tag `level-01-naive`.

## Cosa guardare

- [Documento del Livello 0](docs/levels/00-fondamenta.md): cosa ho fatto, cosa ho imparato e i limiti delle misure.
- [Decisioni tecniche](docs/DECISIONS.md): perché ogni scelta, con le alternative scartate.
- [Misure di partenza](docs/PERFORMANCE.md): metodo e risultati ripetibili.
- [Pull request chiuse](https://github.com/francesco-cassese/orarium/pulls?q=is%3Apr+is%3Aclosed): il processo di lavoro, una modifica alla volta.

## Stack previsto

Strumenti scelti per il progetto. Non tutti sono ancora installati.

- **Backend**: Laravel (API JSON)
- **Frontend**: React con Vite, stile con Bootstrap e SCSS
- **Database e cache**: MySQL e Redis in Docker
- **Qualità**: Pest, Pint, Larastan, ESLint, Prettier, Vitest
- **Misure**: k6
- **Integrazione continua**: GitHub Actions

## Struttura del repository

```
backend/      API Laravel
frontend/     applicazione React
benchmarks/   script di misura e risultati
docker/       script di inizializzazione dei servizi Docker (MySQL)
docs/         regole di lavoro, decisioni, misure e documenti di livello
.github/      workflow di CI, modelli di issue e di pull request
```

## Come lavoro

Il flusso segue le pratiche di un team: issue, branch, Conventional Commits, pull request
con controlli automatici e squash merge. La documentazione è in [`docs/`](docs/README.md): il processo
in [`WORKFLOW.md`](docs/WORKFLOW.md), le regole di nome e di dati in
[`CONVENTIONS.md`](docs/CONVENTIONS.md), le scelte tecniche con le opzioni valutate in
[`DECISIONS.md`](docs/DECISIONS.md).

## Avvio in locale

Backend: vedi [`backend/README.md`](backend/README.md).

Frontend: vedi [`frontend/README.md`](frontend/README.md).

## Autore

Francesco Cassese — sviluppatore full stack (PHP, Laravel, React). [LinkedIn](https://www.linkedin.com/in/francesco-cassese/)
