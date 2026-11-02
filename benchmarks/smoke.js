import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = 'http://127.0.0.1:8000';

if (!__ENV.ENDPOINT) {
  throw new Error('ENDPOINT non impostato: passa -e ENDPOINT=/up (o un altro endpoint)');
}

const ENDPOINT = __ENV.ENDPOINT;
// Pausa in secondi tra due richieste. Vuota o 0 = nessuna pausa (comportamento di default).
const PAUSE = Number(__ENV.PAUSE || 0);
if (Number.isNaN(PAUSE) || PAUSE < 0) {
  throw new Error('PAUSE non valido: usa un numero di secondi con il punto, per esempio -e PAUSE=0.1');
}

export const options = {
  vus: 1,
  duration: '10s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_reqs: ['count>0'],
  },
};

export default function () {
  const res = http.get(`${BASE_URL}${ENDPOINT}`);
  check(res, { 'status was 200': (r) => r.status === 200 });
  if (PAUSE > 0) {
    sleep(PAUSE);
  }
}
