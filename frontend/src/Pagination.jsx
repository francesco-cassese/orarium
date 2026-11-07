/**
 * @param {Object} props
 * @param {number} props.currentPage Pagina corrente (da 1).
 * @param {number} props.lastPage Numero dell'ultima pagina disponibile.
 * @param {(page: number) => void} props.onPageChange Chiamata al clic su Indietro con il numero
 *   della pagina precedente (currentPage - 1) e al clic su Avanti con il numero della pagina
 *   successiva (currentPage + 1).
 */
function Pagination({ currentPage, lastPage, onPageChange }) {
  return (
    <>
      <p>
        Pagina {currentPage} di {lastPage}
      </p>
      <button
        type="button"
        disabled={currentPage === 1}
        onClick={() => onPageChange(currentPage - 1)}
      >
        Indietro
      </button>
      <button
        type="button"
        disabled={currentPage === lastPage}
        onClick={() => onPageChange(currentPage + 1)}
      >
        Avanti
      </button>
    </>
  )
}
export default Pagination
