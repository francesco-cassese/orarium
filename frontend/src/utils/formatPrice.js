const priceFormatter = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

/**
 * @param {number} cents Prezzo in centesimi (120 = 1,20 €).
 * @returns {string} Il prezzo in euro, per esempio «1,20 €».
 */
export default function formatPrice(cents) {
  return priceFormatter.format(cents / 100)
}
