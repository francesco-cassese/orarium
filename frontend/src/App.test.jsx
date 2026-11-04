import { render, screen } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import App from './App'

// La pagina contiene la lista, che chiama l'API: nei test non facciamo richieste vere.
beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn(() => new Promise(() => {})),
  )
})

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('App', () => {
  it('renders the main heading', () => {
    render(<App />)

    expect(
      screen.getByRole('heading', { level: 1, name: 'Orarium' }),
    ).toBeInTheDocument()
  })
})
