import IngredientList from './IngredientList'

function App() {
  return (
    <main>
      <div className="container py-4">
        <h1>Orarium</h1>
        <p>Pianificazione di produzione e scorte per laboratori alimentari.</p>
        <IngredientList />
      </div>
    </main>
  )
}

export default App
