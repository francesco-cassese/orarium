# Impostazioni del repository

Configurazione del repository su GitHub, verificata il 2026-10-30 con GitHub CLI.
Se il repository viene ricreato, queste impostazioni vanno riapplicate.

## Protezione del branch `main`

**Stato: attiva in parte.** La protezione è un ruleset (`protect-main`), che è attivo sul branch predefinito e:
- richiede una pull request per ogni modifica a `main`;
- blocca l'eliminazione del branch e i force-push.

Non richiede approvazioni, perché il progetto è gestito da una persona sola.

**Da completare** quando esiste la pipeline di CI:
- richiedere che i controlli della CI passino prima del merge;
- richiedere che il branch sia aggiornato rispetto a `main` prima del merge.

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
