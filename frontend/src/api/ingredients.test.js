import { afterEach, beforeEach, expect, it, vi } from 'vitest'
import fetchIngredients from './ingredients.js'

// Risposta simulata dell'API, nella stessa forma del backend: { data: [...], meta: { ... } }.
function respondWith(data, meta = { current_page: 1, last_page: 1 }) {
  return Promise.resolve({
    ok: true,
    json: () => Promise.resolve({ data, meta }),
  })
}

beforeEach(() => {
  vi.stubEnv('VITE_API_BASE_URL', 'http://api.test/api/v1')
  vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
  vi.unstubAllEnvs()
  vi.unstubAllGlobals()
})

// Il testo cercato e il numero di pagina finiscono nell'indirizzo della richiesta.
it('requests the page with the search text and the page number', async () => {
  fetch.mockReturnValue(respondWith([]))

  await fetchIngredients('farina', 2)

  expect(fetch).toHaveBeenCalledWith(
    'http://api.test/api/v1/ingredients?search=farina&page=2',
  )
})

// Senza testo cercato, l'indirizzo non ha il parametro search e chiede la prima pagina.
it('omits the search parameter and requests page 1 by default', async () => {
  fetch.mockReturnValue(respondWith([]))

  await fetchIngredients('')

  expect(fetch).toHaveBeenCalledWith(
    'http://api.test/api/v1/ingredients?page=1',
  )
})

// Lo spazio nel testo cercato finisce nell'indirizzo in forma codificata.
it('encodes a search text with a space in the URL', async () => {
  fetch.mockReturnValue(respondWith([]))

  await fetchIngredients('farina 00')

  expect(fetch).toHaveBeenCalledWith(
    'http://api.test/api/v1/ingredients?search=farina+00&page=1',
  )
})

// Dalla risposta della API il risultato contiene solo data e meta.
it('returns only data and meta from the response body', async () => {
  const data = [{ id: 1, name: 'farina 00', unit: 'kg', price_cents: 120 }]
  const meta = { current_page: 1, last_page: 1 }
  const links = { next: null }
  fetch.mockReturnValue(
    Promise.resolve({
      ok: true,
      json: () => Promise.resolve({ data, meta, links }),
    }),
  )

  const result = await fetchIngredients('')

  expect(result).toEqual({ data, meta })
})

// Se la API risponde con un errore, la funzione lancia un errore con lo stato.
it('rejects with the status when the response is not ok', async () => {
  fetch.mockReturnValue(Promise.resolve({ ok: false, status: 500 }))

  await expect(fetchIngredients('')).rejects.toThrow(
    'Request failed with status 500',
  )
})
