# Impostazioni del repository

Configurazione del repository su GitHub. La strategia di merge è verificata il 2026-10-30 con GitHub CLI. La protezione del branch è configurata dalle impostazioni di GitHub e verificata sia dalle impostazioni sia con GitHub CLI il 2026-11-01.
Se il repository viene ricreato, queste impostazioni vanno riapplicate.

## Protezione del branch `main`

**Stato: attiva dal 2026-11-01.** La protezione è un ruleset (`protect-main`), attivo sul branch predefinito, che:
- richiede una pull request per ogni modifica a `main`;
- blocca l'eliminazione del branch e i force-push;
- richiede che passino i controlli `frontend` e `backend` (nomi dei job dei workflow GitHub Actions, vedi D-008 e D-009) prima del merge.

Non richiede che il branch sia aggiornato rispetto a `main`: il progetto è gestito da una persona sola e le PR sono piccole, quindi il rischio di regressioni da integrazione è basso.

Non richiede approvazioni, perché il progetto è gestito da una persona sola.

Il 2026-11-02 ho verificato che il blocco funziona: nella PR #48, un test fallito di proposito ha reso rosso il controllo `backend` (`frontend` è passato) e il pulsante «Squash and merge» era disabilitato. La PR è stata chiusa senza merge.

## Strategia di merge

**Stato: attiva.**
- Solo **Squash and merge** è consentito: merge commit e rebase sono disabilitati a livello di repository.
- Il titolo della PR diventa il messaggio del commit.
- I branch vengono eliminati automaticamente dopo il merge.

## Etichette e milestone

**Stato: attive.**
- Le etichette di progetto (`type:*`, `area:*`, `size:*`, `blocked`, `needs-review`) sono elencate in [WORKFLOW.md](WORKFLOW.md#etichette-labels).
- Restano le etichette predefinite di GitHub (`bug`, `enhancement`, `question`, ...). Il flusso di lavoro non le usa e possono essere eliminate.
- Milestone: `L00 Foundations`, `L01 Ingredients`.

## Pagina del repository

**Stato: attiva dal 2026-11-03.**
- Descrizione: «Pianificazione di produzione e scorte per laboratori alimentari, costruita un livello alla volta con Laravel e React, con CI, controlli di qualità e misure di prestazioni.»
- Argomenti (topics): `docker`, `github-actions`, `k6`, `laravel`, `mysql`, `performance`, `php`, `react`, `redis`, `vite`.
- Il campo Website è vuoto: non c'è ancora un sito.
