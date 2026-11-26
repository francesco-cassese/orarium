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
