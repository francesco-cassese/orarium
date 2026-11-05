import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, beforeEach, expect, it, vi } from 'vitest'
import IngredientList from './IngredientList'

const ingredients = [
  { id: 1, name: 'farina 00', unit: 'kg', price_cents: 120 },
  { id: 2, name: 'burro fresco', unit: 'kg', price_cents: 950 },
]

// Risposta simulata dell'API, nella stessa forma del backend: { data: [...] }.
function respondWith(data) {
  return Promise.resolve({ ok: true, json: () => Promise.resolve({ data }) })
}

beforeEach(() => {
  vi.stubEnv('VITE_API_BASE_URL', 'http://api.test/api/v1')
  vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
  vi.unstubAllEnvs()
  vi.unstubAllGlobals()
})

// La pagina mostra ogni ingrediente in una riga della tabella.
it('shows the ingredients in a table', async () => {
  fetch.mockReturnValue(respondWith(ingredients))

  render(<IngredientList />)

  expect(
    await screen.findByRole('cell', { name: 'farina 00' }),
  ).toBeInTheDocument()
  expect(screen.getByRole('cell', { name: 'burro fresco' })).toBeInTheDocument()
  expect(screen.getByRole('cell', { name: /1,20/ })).toBeInTheDocument()
})

// I pezzi si mostrano come «pz»: `pc` è solo il codice dell'API. Le altre unità restano uguali.
it('shows pieces as pz and keeps the other units', async () => {
  fetch.mockReturnValue(
    respondWith([
      { id: 3, name: 'uova', unit: 'pc', price_cents: 30 },
      { id: 4, name: 'farina 00', unit: 'kg', price_cents: 120 },
    ]),
  )

  render(<IngredientList />)

  expect(await screen.findByRole('cell', { name: 'pz' })).toBeInTheDocument()
  expect(screen.queryByRole('cell', { name: 'pc' })).not.toBeInTheDocument()
  expect(screen.getByRole('cell', { name: 'kg' })).toBeInTheDocument()
})

// Mentre la richiesta è in corso, la pagina mostra uno stato di caricamento.
it('shows a loading state while the list is loading', () => {
  fetch.mockReturnValue(new Promise(() => {}))

  render(<IngredientList />)

  expect(screen.getByRole('status')).toBeInTheDocument()
})

// Se la richiesta fallisce, la pagina mostra un messaggio di errore.
it('shows an error when the request fails', async () => {
  fetch.mockRejectedValue(new Error('rete'))

  render(<IngredientList />)

  expect(await screen.findByRole('alert')).toBeInTheDocument()
})

// Digitando nella ricerca, la pagina chiede all'API solo gli ingredienti con quel testo.
it('asks the API with the search text', async () => {
  fetch.mockReturnValue(respondWith([]))

  render(<IngredientList />)
  await userEvent.type(
    screen.getByRole('searchbox', { name: 'Cerca' }),
    'farina 00',
  )

  expect(fetch).toHaveBeenLastCalledWith(
    'http://api.test/api/v1/ingredients?search=farina%2000',
  )
})
