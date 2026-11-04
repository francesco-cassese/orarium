import { useState, useEffect } from 'react'
import fetchIngredients from './api/ingredients.js'
import formatPrice from './utils/formatPrice.js'

function IngredientList() {
  const [search, setSearch] = useState('')
  const [ingredients, setIngredients] = useState([])
  const [status, setStatus] = useState('loading')

  useEffect(() => {
    let ignore = false

    async function load() {
      setStatus('loading')
      try {
        const data = await fetchIngredients(search)
        if (!ignore) {
          setIngredients(data)
          setStatus('ready')
        }
      } catch {
        if (!ignore) setStatus('error')
      }
    }

    load()

    return () => {
      ignore = true
    }
  }, [search])

  return (
    <>
      <label htmlFor="search">Cerca</label>
      <input
        type="search"
        id="search"
        className="form-control"
        value={search}
        onChange={(e) => setSearch(e.target.value)}
      />

      {status === 'loading' && (
        <div role="status" className="spinner-border">
          <span className="visually-hidden">Caricamento…</span>
        </div>
      )}

      {status === 'error' && (
        <div role="alert" className="alert alert-danger">
          Errore durante il caricamento...
        </div>
      )}

      {status === 'ready' && (
        <table className="table">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Unità</th>
              <th>Prezzo</th>
            </tr>
          </thead>
          <tbody>
            {ingredients.map((ingredient) => (
              <tr key={ingredient.id}>
                <td>{ingredient.name}</td>
                <td>{ingredient.unit}</td>
                <td>{formatPrice(ingredient.price_cents)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </>
  )
}
export default IngredientList
