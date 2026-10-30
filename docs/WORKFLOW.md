# Flusso di lavoro

Lavoro da solo, ma come se fossi in un team: ogni modifica parte da una issue, passa da un branch,
una pull request e la CI, e arriva su `main` solo se è verde. Il motivo è semplice: in azienda si lavora così,
e la cronologia del repository diventa la prova del mio metodo.

**Lingua**: codice, commit, issue, pull request e nomi dei branch in **inglese** (standard di team).
La documentazione in `docs/` è in italiano.

## 1. Ciclo di una modifica

1. **Issue**: scelgo dal backlog una issue del milestone corrente e la sposto in "In progress".
2. **Branch** da `main` aggiornato.
3. **Commit piccoli** (Conventional Commits), uno per cambiamento logico.
4. **Pull request** appena c'è qualcosa da vedere (anche come bozza). Il titolo segue le stesse regole dei commit.
5. **CI verde** (test, lint, analisi statica di backend e frontend).
6. **Revisione**: auto-revisione con la checklist del modello di PR; quando possibile, la leggo a un'altra persona.
7. **Squash and merge** su `main`, con il titolo della PR come messaggio. Elimino il branch.
8. La issue si chiude da sola grazie a `Closes #N` nella descrizione della PR.

Una regola: **`main` è sempre in uno stato funzionante e rilasciabile**.

## 2. Nomi dei branch

Formato: `<type>/<issue-number>-<short-description>`, in minuscolo con trattini.

```
feat/14-ingredient-list-endpoint
fix/31-ingredient-price-rounding
chore/4-configure-pest-pint-larastan
docs/23-complete-level-01-document
```

## 3. Conventional Commits

Formato: `<type>(<scope>): <description>`

- Descrizione all'imperativo, in minuscolo, senza punto finale, massimo 72 caratteri.
- Corpo opzionale per spiegare il *perché*, non il *cosa*.
- Riferimento alla issue nel piè di pagina: `Refs #14` (nei commit) e `Closes #14` (nella PR).

| Type | Quando |
|---|---|
| `feat` | nuova funzionalità |
| `fix` | correzione di un bug |
| `perf` | miglioramento delle prestazioni (sempre con numeri nella PR) |
| `refactor` | riorganizzazione senza cambiare il comportamento |
| `test` | aggiunta o modifica di test |
| `docs` | documentazione |
| `build` | dipendenze e strumenti di build |
| `ci` | pipeline di integrazione continua |
| `chore` | manutenzione che non rientra negli altri |
| `style` | formattazione, senza cambi di logica |

Scope usati: `backend`, `frontend`, `benchmarks`, `docs`, `ci`, `levels` (per i documenti di livello), e il nome
della funzionalità su cui si lavora (ad esempio `ingredients`).

Esempi:
```
feat(ingredients): add measurement unit enum
fix(ingredients): store prices as integer cents
perf(ingredients): add index on name column
test(ingredients): cover validation rules for create endpoint
docs(levels): record level 01 baseline measurements
ci: run frontend tests on pull requests
```

Un cambiamento incompatibile si segnala con `!` (`feat(api)!: rename price field`) e un paragrafo `BREAKING CHANGE:` nel corpo.

## 4. Issue

- Titolo nello stesso formato dei commit: `feat(ingredients): add ingredient list endpoint with pagination`.
- Ogni issue ha criteri di accettazione verificabili (checklist) e una stima di dimensione (`size:S`, `size:M`, `size:L`).
- Una issue si chiude in una PR. Se è troppo grande, la divido.
- Si usano i modelli in `.github/ISSUE_TEMPLATE/`.

### Etichette (labels)

| Gruppo | Etichette |
|---|---|
| Tipo | `type:feature`, `type:bug`, `type:perf`, `type:docs`, `type:chore`, `type:test` |
| Ambito | `area:backend`, `area:frontend`, `area:infra`, `area:docs` |
| Dimensione | `size:S`, `size:M`, `size:L` |
| Stato | `blocked`, `needs-review`, `wontfix` |

`area:infra` riguarda ciò che sta fuori da backend e frontend: Docker Compose, pipeline GitHub Actions, script di
misura e configurazione del repository. Lo scope del commit (`ci`, `benchmarks`, ...) descrive il codice toccato;
l'etichetta descrive l'ambito della issue.

### Milestone

Uno per livello: `L00 Foundations`, `L01 Ingredients`, `L02 Authentication`, ...
Il livello è finito quando il milestone è chiuso, il documento di livello compilato e i tag creati.

## 5. Pull request

- Piccole: idealmente sotto le 400 righe modificate. Una PR, un'idea.
- Descrizione con: cosa cambia, perché, come l'ho verificato, schermate o numeri se serve.
- Se è una `perf`, la descrizione contiene **la tabella prima/dopo** con il comando usato.
- Merge solo con CI verde e checklist spuntata.

## 6. Definition of Done

Una issue è finita quando:
- [ ] i criteri di accettazione sono soddisfatti;
- [ ] ci sono test per il comportamento nuovo, inclusi i casi limite;
- [ ] CI verde (test, Pint, Larastan, ESLint, Vitest);
- [ ] nessun dato sensibile nel codice o nella cronologia;
- [ ] la documentazione è aggiornata (README, `DECISIONS.md` se c'è una scelta);
- [ ] se tocca le prestazioni: misura prima e dopo registrata nella documentazione delle prestazioni in `docs/`.

## 7. Versioni e tag

- **Livelli**: `level-NN-naive` e `level-NN-optimized`.
- **Rilasci**: versionamento semantico (`v0.1.0`, `v0.2.0`, ...), un rilascio per livello completato, con note generate dai Conventional Commits.
- I tag sono annotati: `git tag -a v0.1.0 -m "Level 01: ingredients"`.

## 8. Routine di ogni sessione

**Inizio** (5 minuti): aggiorno `main`, scelgo la issue, creo il branch, rileggo i criteri di accettazione.
**Fine** (10 minuti): commit e push, PR aperta o aggiornata, una nota nel diario di apprendimento, scrivo il prossimo passo nella issue.
