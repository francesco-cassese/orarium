# Prestazioni del backend: baseline

Misura di partenza del Livello 0, prima di qualsiasi ottimizzazione. Serve come confronto per i livelli successivi, quindi deve poter essere ripetuta con lo stesso metodo.

## Ambiente

- Sistema operativo: Windows 11 Home 10.0.26200
- CPU: Intel Core 5 120U, 12 thread; RAM 15.6 GB
- Piano di alimentazione: Bilanciato. Programmi aperti e altre condizioni del computer: non registrati.
- PHP: 8.4.27 NTS (`php -v`)
- Laravel: 13.39.0 (`php artisan --version`)
- `backend/.env`: `APP_ENV=local`, `APP_DEBUG=true`
- Server: `php artisan serve` nella cartella `backend/`, porta 8000. Usa il server integrato di PHP con `server.php` come router.
- Commit misurato: `97dd24d` su `main`
- Strumento: k6 v2.3.0
- Data delle misure: rotte di Laravel il 2026-11-02, alle 00:52–00:53; controlli il 2026-11-02, alle 01:07–01:09

## Metodo

### Rotte di Laravel

- Rotte: `/up` e `/api/v1/health`.
- Corse alternate: `up`, `health`, `up`, `health`…, per evitare che un cambio di condizioni favorisca una rotta.
- Un utente virtuale senza pause: ogni richiesta parte appena finisce la precedente. Durata di ogni corsa: 10 secondi.
- Corse: cinque per rotta. La corsa 1 è scartata come riscaldamento, quindi restano quattro misurate (corse 2–5).

### Controlli

Servono a separare il costo del server da quello di Laravel:

- `/ok.html`: file statico con il contenuto `ok`.
- `/index.php`: script PHP minimo, con `<?php echo 'ok';`.

Entrambi sono serviti da `php -S 127.0.0.1:8000 -t <cartella>`, con la cartella di controllo fuori dal repository. Per riprodurre la misura, crea i due file in una cartella a parte e usa quel comando.

- Corse: sei per file, alternate, con la corsa 1 scartata (cinque misurate).
- Pausa: `PAUSE=0.1`, cioè 0.1 secondi dopo ogni risposta, circa 10 richieste al secondo.

**Perché la pausa.** Senza pausa un utente virtuale produce più di mille richieste al secondo. Il server integrato di PHP chiude ogni connessione, e Windows esaurisce le porte locali (connessioni in `TIME_WAIT`): le corse successive alla prima falliscono. Le corse senza pausa non valide sono conservate localmente in `benchmarks/results/invalid-ctrl-no-pause/`, non versionate, e non vanno usate.

### Comandi

Dalla cartella `benchmarks/`, con il server Laravel avviato:

```powershell
k6 run -e ENDPOINT=/up --summary-export=results/up-runN.json smoke.js
k6 run -e ENDPOINT=/api/v1/health --summary-export=results/health-runN.json smoke.js
```

Per i controlli, con il server di controllo avviato, aggiungi `-e PAUSE=0.1`.

Lancia da PowerShell, oppure da Git Bash con `MSYS_NO_PATHCONV=1`. Senza questa variabile Git Bash trasforma `/up` in un percorso Windows: l'URL non è valido, la richiesta non parte e la corsa, con la sola soglia sull'errore, esce con 0. La soglia `http_reqs: ['count>0']` fa uscire la corsa con 99 in quel caso.

### Calcoli

- Mediana e p95 di ogni corsa: campi `med` e `p(95)` di `http_req_duration`.
- Errori: campo `passes` di `http_req_failed`, cioè le richieste fallite. Nei file, `fails` conta invece le richieste riuscite.
- Riepilogo: mediana delle mediane delle corse misurate, con minimo e massimo.

## Risultati

Corse misurate: quattro per le rotte di Laravel, cinque per i controlli. Nessuna richiesta fallita in nessuna corsa misurata.

| Livello | Mediana delle mediane | Min–max delle mediane | Mediana dei p95 |
|---|---|---|---|
| File statico | 1.73 ms | 1.69–1.76 ms | 3.01 ms |
| Script PHP minimo | 2.29 ms | 1.97–2.41 ms | 3.97 ms |
| Laravel, `/api/v1/health` | 101.8 ms | 101.6–102.6 ms | 109.5 ms |
| Laravel, `/up` | 104.2 ms | 103.8–105.3 ms | 110.9 ms |

Lettura preliminare: in questo ambiente lo script PHP minimo costa circa 2 ms e Laravel circa 100 ms per richiesta. La differenza è di oltre un ordine di grandezza. Il tempo sta in ciò che Laravel fa a ogni richiesta, non nel server né nel semplice avvio di PHP. Una parte delle 100 ms può essere PHP che compila molti file a ogni richiesta, se OPcache non è attiva (non verificato, vedi sotto). Il risultato si basa su poche corse e va confermato.

## Limiti e osservazioni

- **Metodo non uniforme.** Le corse di Laravel non hanno pausa; i controlli sì (0.1 secondi). Il ritmo è simile (circa 10 richieste al secondo), ma il comportamento no.
- **Numero di corse.** Per Laravel sono state misurate quattro corse, non cinque come per i controlli. Non corretto in questa baseline.
- **Momento delle misure.** Le rotte di Laravel sono state misurate alle 00:52–00:53 e i controlli alle 01:07–01:09, circa quindici minuti dopo e non alternati. A questa scala la conclusione non cambia, ma sono condizioni diverse.
- **Valore anomalo.** `up-run3` ha un p95 di 132.3 ms, molto sopra le altre corse misurate, e 92 richieste contro 95 delle altre. La corsa più lenta completa meno richieste, ma la causa del valore non è spiegata.
- **Differenza tra rotte.** `/up` è circa 2.5 ms più lenta di `/api/v1/health`. Cause possibili, tutte non verificate:
  - `/up` renderizza una vista Blade (`health-up.blade.php`), mentre `/api/v1/health` restituisce JSON;
  - `/up` lancia l'evento `DiagnosingHealth`, `/api/v1/health` no;
  - la dimensione della risposta: 1829 byte di HTML contro 15 byte di JSON.
- **Un solo computer, una sola sessione.** Non è una misura di un ambiente di produzione. k6 e il server girano sullo stesso computer e si contendono la CPU: i numeri contengono anche un po' del costo di k6.

## Ipotesi da verificare nei livelli successivi

Non sono risultati, ma domande da porre quando si ottimizza:
- OPcache: non è stata verificata la sua configurazione.
- Configurazione: Laravel legge i file di configurazione a ogni richiesta, se la cache non è attiva.
- `APP_DEBUG=true`: il debug può costare tempo a ogni richiesta.
- Server: `php artisan serve` è pensato per lo sviluppo, non per la produzione.
