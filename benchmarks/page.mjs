import { chromium } from 'playwright'
import { mkdirSync, writeFileSync } from 'node:fs'
import { dirname } from 'node:path'

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:4173'
const RUNS = Number(process.env.RUNS || 6)
const OUT = process.env.OUT || 'results/level-01/page.json'
const TIMEOUT = 60_000

if (!/^http:\/\/127\.0\.0\.1:\d+$/.test(BASE_URL)) {
  throw new Error(
    'BASE_URL non valido: usa un indirizzo locale, per esempio BASE_URL=http://127.0.0.1:4173',
  )
}
if (!Number.isInteger(RUNS) || RUNS < 2) {
  throw new Error(
    'RUNS non valido: serve un numero intero maggiore o uguale a 2',
  )
}

// La lista è pronta quando ci sono righe e non c'è più il caricamento.
const listReady = () =>
  document.querySelectorAll('tbody tr').length > 0 &&
  !document.querySelector('[role="status"]')

// Aspetta due frame di disegno: il browser ha disegnato quello che c'è nel DOM.
const nextPaint = (page) =>
  page.evaluate(
    () =>
      new Promise((resolve) =>
        requestAnimationFrame(() => requestAnimationFrame(resolve)),
      ),
  )

const waitForList = (page) =>
  page.waitForFunction(listReady, undefined, { timeout: TIMEOUT })

function median(values) {
  const sorted = [...values].sort((a, b) => a - b)
  const middle = Math.floor(sorted.length / 2)
  return sorted.length % 2
    ? sorted[middle]
    : (sorted[middle - 1] + sorted[middle]) / 2
}

async function measureRun(browser) {
  const context = await browser.newContext()

  // 1. Tempo fino alla lista a schermo, nodi DOM e righe.
  const page = await context.newPage()
  const t0 = performance.now()
  await page.goto(BASE_URL)
  await waitForList(page)
  await nextPaint(page)
  const listMs = performance.now() - t0
  const domNodes = await page.evaluate(
    () => document.querySelectorAll('*').length,
  )
  const rows = await page.evaluate(
    () => document.querySelectorAll('tbody tr').length,
  )
  const listApiMs = await page.evaluate(
    () =>
      performance
        .getEntriesByType('resource')
        .find((entry) => entry.name.endsWith('/ingredients')).duration,
  )

  // 2. Tempo dopo un tasto (f) nel campo di ricerca, con la lista già a schermo.
  await page.locator('#search').focus()
  const keyResponse = page.waitForResponse(
    (r) => r.url().includes('search=f'),
    {
      timeout: TIMEOUT,
    },
  )
  const t1 = performance.now()
  await page.keyboard.press('f')
  await keyResponse
  await waitForList(page)
  await nextPaint(page)
  const keyMs = performance.now() - t1
  const keyApiMs = await page.evaluate(
    () =>
      performance
        .getEntriesByType('resource')
        .find((entry) => entry.name.endsWith('search=f')).duration,
  )
  const keyRows = await page.evaluate(
    () => document.querySelectorAll('tbody tr').length,
  )

  // 3. Richieste fatte scrivendo "farina", in una pagina nuova.
  const typing = await context.newPage()
  const requests = []
  typing.on('request', (r) => {
    if (r.url().includes('/ingredients')) requests.push(r.url())
  })
  await typing.goto(BASE_URL)
  await waitForList(typing)
  requests.length = 0
  await typing.locator('#search').focus()
  const typingResponse = typing.waitForResponse(
    (r) => r.url().includes('search=farina'),
    { timeout: TIMEOUT },
  )
  await typing.keyboard.type('farina')
  await typingResponse
  await waitForList(typing)

  await context.close()

  return {
    listMs,
    listApiMs,
    keyMs,
    keyApiMs,
    keyRows,
    domNodes,
    rows,
    requestsTypingFarina: requests.length,
  }
}

const browser = await chromium.launch()

try {
  const runs = []
  for (let n = 1; n <= RUNS; n++) {
    const run = await measureRun(browser)
    runs.push(run)
    console.log(`run ${n}${n === 1 ? ' (discarded)' : ''}:`, run)
  }

  // La prima corsa è di riscaldamento e si scarta.
  const counted = runs.slice(1)
  const summary = {}
  for (const key of Object.keys(counted[0])) {
    const values = counted.map((run) => run[key])
    summary[key] = {
      median: median(values),
      min: Math.min(...values),
      max: Math.max(...values),
    }
  }

  const result = {
    date: new Date().toISOString(),
    baseUrl: BASE_URL,
    chromium: browser.version(),
    node: process.version,
    runs,
    discarded: 'run 1',
    summary,
  }

  mkdirSync(dirname(OUT), { recursive: true })
  writeFileSync(OUT, JSON.stringify(result, null, 2) + '\n')
  console.log('summary:', summary)
} finally {
  await browser.close()
}
