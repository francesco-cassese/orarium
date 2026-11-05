# Convenzioni

Regole di nome e di dati usate nel progetto. Le regole su rami, commit e pull request sono in [WORKFLOW.md](WORKFLOW.md); le scelte con un compromesso sono in [DECISIONS.md](DECISIONS.md).

## Database

Le tabelle create dal framework, come `cache` e `jobs`, mantengono i nomi di Laravel. Le regole sotto valgono per le tabelle del progetto. Gli esempi vengono dalle migrazioni del template o del progetto quando esistono; altrimenti sono ipotetici e lo dicono.

- **Tabelle**: plurale, in snake_case. Esempio: `password_reset_tokens` (template).
- **Colonne**: in snake_case. Esempio: `email_verified_at` (tabella `users`, template).
- **Chiave primaria**: `id`, numerica e auto-incrementale. Esempio: `users.id` (`$table->id()`, template).
- **Chiavi esterne**: `<singolare>_id`, che punta a `id` della tabella al plurale. Esempio: `sessions.user_id` (template).
- **Timestamp**: `created_at` e `updated_at` in ogni tabella, con `$table->timestamps()`. Esempio: tabella `users` (template).
- **Denaro**: si salva in centesimi interi, nella colonna `price_cents`, mai in un valore decimale. Esempio: `ingredients.price_cents` (tabella `ingredients`, [#38](https://github.com/francesco-cassese/orarium/issues/38)), dove `500` vale 5,00 €. La regola completa e il motivo sono in [D-011](DECISIONS.md).
- **Unità di misura**: stringa breve, nella colonna `unit`. Esempio: `ingredients.unit` con i valori `g`, `kg`, `ml`, `l`, `pc` (`pc` = pezzi, dall'inglese *piece*). Nell'interfaccia il valore `pc` si mostra all'utente come «pz».
- **Non ancora deciso**: le quantità con decimali, per esempio 0,5 kg. Si decide quando arriveranno le prime tabelle con quantità.

## API

Le rotte sono in `backend/routes/api.php`. Gli esempi con «(route)» vengono dal codice; quelli «previsti» vengono dalle issue indicate e non esistono ancora.

- **Prefisso e versione**: `/api/v1`, con la versione nel prefisso. Esempio: `GET /api/v1/health` (route).
- **Nomi delle risorse**: plurale, minuscolo, con i trattini per le parole composte. Esempio: `GET /api/v1/ingredients` (route). Non c'è ancora un esempio con i trattini.
- **Nomi dei campi JSON**: snake_case, come le colonne. Esempio: `price_cents` in `IngredientResource` (codice).
- **Codici di risposta**: `200` per una lettura riuscita, `201` per una creazione, `204` per una cancellazione, `404` per un id che non esiste, `422` per dati non validi. `200` è nelle route `health` e `ingredients`; `201` è in `POST /api/v1/ingredients` (route); `204` è in `DELETE /api/v1/ingredients/{id}` (route); `404` è in `GET /api/v1/ingredients/{id}` (route); `422` è negli errori di validazione (route).
- **Errori di validazione**: la forma standard di Laravel, senza inventarne un'altra. È un oggetto con `message` e `errors`, dove `errors` contiene per ogni campo la lista dei messaggi. Esempio, con stato `422` (route): `{"message": "The name field is required. (and 2 more errors)", "errors": {"name": ["The name field is required."], "unit": ["The unit field is required."], "price_cents": ["The price cents field is required."]}}`. `message` mostra il primo errore e il numero degli altri.
- **Date**: ISO 8601 in UTC, con microsecondi e la `Z` finale. Esempio di formato: `2026-11-02T10:00:00.000000Z`. È il formato che Laravel produce con `toJSON()` di Carbon; non c'è ancora una risposta con date.
- **Risposte in JSON**: tutte le rotte `api/*` rispondono in JSON, anche senza l'intestazione `Accept` (`shouldRenderJsonWhen` in `backend/bootstrap/app.php`). Esempio: `GET /api/v1/inesistente` risponde `404` in JSON; una rotta fuori da `api/*` risponde in HTML.
- **Non ancora deciso**: il formato della paginazione. Si decide con il miglioramento, dopo la misura.

## Codice backend

- **Controller**: singolare, `<Nome>Controller`, nella cartella della versione. Esempio: `HealthController` in `App\Http\Controllers\Api\V1` (codice).
- **Form Request**: `Store<Nome>Request` e `Update<Nome>Request`. Esempio: `StoreIngredientRequest` in `App\Http\Requests` (codice).
- **Resource**: `<Nome>Resource`. Esempio: `IngredientResource` in `App\Http\Resources` (codice).
- **Enum**: nome singolare, in `App\Enums`. Esempio: `Unit`, in `backend/app/Enums/Unit.php` (codice).
- **Modelli**: singolare, PascalCase, corrispondente alla tabella al plurale. Esempio: `User` per la tabella `users` (`app/Models/User.php`, codice).
- **Test**: in `tests/Feature/<Tema>/`, con il nome `<Cosa>Test.php`, e descrizioni `it(...)` in inglese. Esempi: `HealthCheckTest` in `Health/`, `TestDatabaseTest` in `Database/` (codice).
- **File PHP**: `declare(strict_types=1)` in ogni file, imposto da Pint con la regola `declare_strict_types` (D-005).

## Codice frontend

- **Componenti**: PascalCase, un componente per file, con file `.jsx` dello stesso nome. Esempio: `App.jsx` in `frontend/src/` (codice).
- **Test**: accanto al file, con nome `<Nome>.test.jsx`, con Vitest (D-007). Esempio: `App.test.jsx` (codice).
- **Hook**: `use<Nome>`. Nessun esempio ancora.
- **Stili**: file `*.module.scss` (D-002). Nessun esempio ancora.
- **Campi dell'API**: nel frontend si scrivono con gli stessi nomi con cui li manda l'API, in snake_case, senza convertirli in camelCase. Esempio: `ingredient.price_cents` in `frontend/src/IngredientList.jsx` (codice). Una conversione è codice in più da mantenere e da misurare. Il costo: nel JSX compaiono nomi che non seguono lo stile abituale di JavaScript.
- **Forma delle risposte**: descritta con commenti JSDoc vicino al codice che la usa, finché non c'è TypeScript (D-006).
- **Cartelle**: per tipo, dentro `frontend/src/`. Le chiamate all'API stanno in `src/api/` (esempio: `ingredients.js`), le funzioni semplici condivise in `src/utils/` (esempi: `formatPrice.js`, `formatUnit.js`). Le cartelle per funzionalità (`features/`) si rivalutano dal Livello 2.
