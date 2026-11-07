/**
 * @param {Object} props
 * @param {number} props.currentPage Pagina corrente (da 1).
 * @param {number} props.lastPage Numero dell'ultima pagina disponibile.
 */
function Pagination({ currentPage, lastPage }) {
  return (
    <>
      <p>
        Pagina {currentPage} di {lastPage}
      </p>
      <button type="button" disabled={currentPage === 1}>
        Indietro
      </button>
      <button type="button">Avanti</button>
    </>
  )
}
export default Pagination
