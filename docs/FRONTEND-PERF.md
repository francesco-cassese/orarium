# Prestazioni del frontend: baseline

Misura del bundle di produzione del frontend, dopo la pulizia del template. È la baseline del frontend: le misure future si confrontano con questi numeri.

## Bundle di produzione

- Data: 2026-11-01
- Comando: `pnpm build` nella cartella `frontend/`
- Node: v24.16.0 (il progetto dichiara `^20.19.0 || >=22.12.0`; la CI usa Node 22)
- pnpm: 11.7.0
- React: 19.3.1, React DOM 19.3.1
- Vite: 8.3.5
- Commit: `97dd24d` su `main`

| File | Dimensione | Gzip |
|---|---|---|
| `dist/assets/index-*.js` | 219.68 kB | 68.66 kB |
| `dist/index.html` | 0.38 kB | 0.26 kB |

Il build trasforma 15 moduli. Il tempo di build è indicativo: nelle prove è variato tra 100 e 110 ms. Non c'è un file CSS separato.

## Ripetibilità

Due `pnpm build` consecutivi hanno prodotto gli stessi valori: stessa dimensione e stesso nome di file (`index-BPd8qGJc.js`) con l'hash del contenuto.

## Limiti

- Il peso riguarda solo il bundle: non è una misura del tempo di caricamento nel browser.
- Il progetto dichiara Node `^20.19.0 || >=22.12.0`, e la CI usa Node 22. La misura è stata fatta con Node v24.16.0. Non è stato verificato se con Node 22 il bundle cambi.
- Per confrontare, rilancia `pnpm build` nello stesso ambiente e confronta file per file.
