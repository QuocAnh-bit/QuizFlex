import katex from 'katex'

const escapeHtml = (value) => String(value)
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;')
  .replaceAll("'", '&#039;')

const delimiters = [
  { open: '$$', close: '$$', displayMode: true },
  { open: '\\[', close: '\\]', displayMode: true },
  { open: '\\(', close: '\\)', displayMode: false },
  { open: '$', close: '$', displayMode: false },
]

const rawLatexPattern = /^\\(?:frac|dfrac|tfrac|sqrt|sum|prod|int|lim|begin|left|vec|overline|underline|mathrm|mathbf|mathbb)(?:\b|\{|\[|_)/

const findClosingDelimiter = (content, close, fromIndex) => {
  let index = content.indexOf(close, fromIndex)
  while (index !== -1) {
    let slashCount = 0
    for (let cursor = index - 1; cursor >= 0 && content[cursor] === '\\'; cursor -= 1) slashCount += 1
    if (slashCount % 2 === 0) return index
    index = content.indexOf(close, index + close.length)
  }
  return -1
}

const renderFormula = (formula, displayMode, fallback) => {
  try {
    return katex.renderToString(formula.trim(), {
      displayMode,
      throwOnError: true,
      strict: 'ignore',
      trust: false,
      output: 'htmlAndMathml',
      macros: {
        '\\exponentialE': '\\mathrm{e}',
        '\\imaginaryI': '\\mathrm{i}',
        '\\differentialD': '\\mathrm{d}',
      },
    })
  } catch {
    return escapeHtml(fallback)
  }
}

export const containsMathMarkup = (content = '') => /\$|\\\(|\\\[/.test(String(content)) || rawLatexPattern.test(String(content).trim())

export const renderMathContent = (content = '', { compact = false, mathOnly = false } = {}) => {
  const source = String(content)
  if (!source || (mathOnly && !containsMathMarkup(source))) return ''

  const trimmed = source.trim()
  if (rawLatexPattern.test(trimmed) && !/[\$]|\\\(|\\\[/.test(trimmed)) return renderFormula(trimmed, false, source)

  let output = ''
  let plainStart = 0
  let cursor = 0
  let renderedAnyMath = false

  while (cursor < source.length) {
    const delimiter = delimiters.find(({ open }) => source.startsWith(open, cursor))
    if (!delimiter || (delimiter.open === '$' && source.startsWith('$$', cursor))) {
      cursor += 1
      continue
    }

    const formulaStart = cursor + delimiter.open.length
    const formulaEnd = findClosingDelimiter(source, delimiter.close, formulaStart)
    if (formulaEnd === -1 || formulaEnd === formulaStart) {
      cursor += delimiter.open.length
      continue
    }

    output += escapeHtml(source.slice(plainStart, cursor))
    const rawExpression = source.slice(cursor, formulaEnd + delimiter.close.length)
    const formula = source.slice(formulaStart, formulaEnd)
    const rendered = renderFormula(formula, delimiter.displayMode && !compact, rawExpression)
    output += delimiter.displayMode && !compact ? `<span class="math-content__block">${rendered}</span>` : rendered
    renderedAnyMath = true
    cursor = formulaEnd + delimiter.close.length
    plainStart = cursor
  }

  output += escapeHtml(source.slice(plainStart))
  if (mathOnly && !renderedAnyMath) return ''
  return output.replace(/\r?\n/g, '<br>')
}
