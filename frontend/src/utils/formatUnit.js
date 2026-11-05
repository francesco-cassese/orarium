/**
 * @param {string} unit Codice dell'unità come arriva dall'API: g, kg, ml, l, pc.
 * @returns {string} L'unità da mostrare: «pz» per i pezzi, gli altri codici restano uguali.
 */
function formatUnit(unit) {
  return unit === 'pc' ? 'pz' : unit
}

export default formatUnit
