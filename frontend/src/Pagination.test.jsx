import { render, screen } from '@testing-library/react'
import { expect, it, vi } from 'vitest'
import Pagination from './Pagination'

// Se la pagina corrente è oltre l'ultima, il pulsante Avanti non deve essere attivo.
it('disables Next when the current page is past the last page', () => {
  const onPageChange = vi.fn()
  render(
    <Pagination currentPage={5} lastPage={3} onPageChange={onPageChange} />,
  )

  expect(screen.getByRole('button', { name: 'Avanti' })).toBeDisabled()
})

// Se la pagina corrente è prima della prima, il pulsante Indietro non deve essere attivo.
it('disables Previous when the current page is before the first page', () => {
  const onPageChange = vi.fn()
  render(
    <Pagination currentPage={0} lastPage={3} onPageChange={onPageChange} />,
  )

  expect(screen.getByRole('button', { name: 'Indietro' })).toBeDisabled()
})

// I controlli di paginazione stanno in un punto di riferimento con il nome «Paginazione».
it('wraps the pagination controls in a labelled navigation', () => {
  const onPageChange = vi.fn()
  render(
    <Pagination currentPage={2} lastPage={5} onPageChange={onPageChange} />,
  )

  const navigazione = screen.getByRole('navigation', { name: 'Paginazione' })

  expect(navigazione).toContainElement(
    screen.getByRole('button', { name: 'Indietro' }),
  )
  expect(navigazione).toContainElement(
    screen.getByRole('button', { name: 'Avanti' }),
  )
})
