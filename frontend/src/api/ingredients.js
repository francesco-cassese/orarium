/**
 * Un ingrediente, come lo restituisce l'API nel campo `data`.
 *
 * @typedef {Object} Ingredient
 * @property {number} id
 * @property {string} name
 * @property {'g' | 'kg' | 'ml' | 'l' | 'pc'} unit
 * @property {number} price_cents Prezzo di una unità, in centesimi (120 = 1,20 €).
 */

/**
 * Una pagina di ingredienti: le righe in `data` e i dati di paginazione in `meta`
 * (es. `current_page`, `last_page`), come li restituisce l'API.
 *
 * @typedef {Object} IngredientPage
 * @property {Ingredient[]} data
 * @property {{ current_page: number, last_page: number }} meta
 */

/**
 * @param {string} search Testo da cercare nel nome; vuoto per la lista intera.
 * @param {number} [page=1] Numero della pagina da richiedere.
 * @returns {Promise<IngredientPage>}
 */
async function fetchIngredients(search, page = 1) {
  const params = new URLSearchParams()
  if (search) params.append('search', search)
  params.append('page', page)

  const url = `${import.meta.env.VITE_API_BASE_URL}/ingredients?${params.toString()}`

  const response = await fetch(url)

  if (!response.ok) {
    throw new Error(`Request failed with status ${response.status}`)
  }

  const body = await response.json()

  return { data: body.data, meta: body.meta }
}

export default fetchIngredients
