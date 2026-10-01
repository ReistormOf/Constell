// ===== FUNÇÃO DE MARKDOWN =====
function parseMarkdown(text) {
  let html = text;
  html = html.replace(/^###### (.*$)/gim, "<h6>$1</h6>");
  html = html.replace(/^##### (.*$)/gim, "<h5>$1</h5>");
  html = html.replace(/^#### (.*$)/gim, "<h4>$1</h4>");
  html = html.replace(/^### (.*$)/gim, "<h3>$1</h3>");
  html = html.replace(/^## (.*$)/gim, "<h2>$1</h2>");
  html = html.replace(/^# (.*$)/gim, "<h1>$1</h1>");
  const lines = html.split("\n");
  let inList = false;
  let result = [];
  for (let line of lines) {
    if (line.trim().startsWith("- ")) {
      if (!inList) {
        result.push("<ul>");
        inList = true;
      }
      const content = line.trim().substring(2);
      result.push(`<li>${content}</li>`);
    } else {
      if (inList) {
        result.push("</ul>");
        inList = false;
      }
      result.push(line);
    }
  }
  if (inList) result.push("</ul>");
  html = result.join("\n");
  html = html.replace(/\n/g, "<br>");
  return html;
}

function getWidthFromSize(size) {
  const map = {
    "col-md-3": "25%",
    "col-md-4": "33.33%",
    "col-md-6": "50%",
    "col-md-12": "100%",
  };
  return map[size] || "auto";
}

// ===== APLICAR TÍTULO =====
function applyHeading(element, tagName) {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  const range = sel.getRangeAt(0);
  if (range.collapsed) {
    const textNode = range.startContainer;
    if (textNode.nodeType === Node.TEXT_NODE) {
      const offset = range.startOffset;
      const text = textNode.textContent;
      let lineStart = text.lastIndexOf("\n", offset - 1) + 1;
      if (lineStart === 0 && offset > 0 && text[0] !== "\n") lineStart = 0;
      const lineEnd = text.indexOf("\n", offset);
      const lineContent = text.substring(
        lineStart,
        lineEnd === -1 ? text.length : lineEnd,
      );
      const before = text.substring(0, lineStart);
      const after = text.substring(lineEnd === -1 ? text.length : lineEnd);
      const heading = document.createElement(tagName);
      heading.textContent = lineContent;
      textNode.textContent = before;
      const parent = textNode.parentNode;
      if (parent) parent.insertBefore(heading, textNode.nextSibling);
      if (after) {
        const afterNode = document.createTextNode(after);
        parent.insertBefore(afterNode, heading.nextSibling);
      }
      const newRange = document.createRange();
      newRange.selectNodeContents(heading);
      sel.removeAllRanges();
      sel.addRange(newRange);
      element.focus();
      return;
    }
  }
  const selectedText = range.toString();
  if (selectedText.length > 0) {
    const sizeMap = {
      h1: "2.5rem",
      h2: "2rem",
      h3: "1.75rem",
      h4: "1.5rem",
      h5: "1.25rem",
      h6: "1.1rem",
    };
    const fontSize = sizeMap[tagName] || "1.5rem";
    const span = document.createElement("span");
    span.style.fontSize = fontSize;
    span.style.fontWeight = "bold";
    span.style.display = "inline";
    span.textContent = selectedText;
    range.deleteContents();
    range.insertNode(span);
    const newRange = document.createRange();
    newRange.selectNodeContents(span);
    sel.removeAllRanges();
    sel.addRange(newRange);
    element.focus();
  }
}

// ===== FUNÇÕES DO FILTRO =====
function updateFilterIndicator() {
  const toolbar = currentToolbar || activeToolbar;
  if (!toolbar) return;
  let indicator = toolbar.querySelector(".filter-indicator");
  if (!indicator) {
    indicator = document.createElement("span");
    indicator.className = "filter-indicator";
    indicator.style.cssText =
      "color:#0d6efd; font-weight:bold; margin-left:8px; font-size:12px;";
    toolbar.appendChild(indicator);
  }
  if (filterWord) {
    indicator.textContent = `🔍 "${filterWord}"`;
    indicator.style.display = "inline";
  } else {
    indicator.textContent = "";
    indicator.style.display = "none";
  }
}

function toggleTargetElement(element) {
  const index = targetElements.indexOf(element);
  if (index > -1) {
    targetElements.splice(index, 1);
    element.classList.remove("target-selected");
  } else {
    targetElements.push(element);
    element.classList.add("target-selected");
  }
  if (targetElements.length > 0 && !activeToolbar) {
    createToolbar(targetElements[0]);
  }
  updateFilterIndicator();
}

function applyStyleToTargets(style, value) {
  if (!filterWord || targetElements.length === 0) return;
  targetElements.forEach((el) => {
    applyStyleToWord(el, filterWord, style, value);
  });
  if (style === "color") lastColor = value;
  else if (style === "font-size") lastFontSize = value;
  else if (style === "font-family") lastFontFamily = value;
}

function applyStyleToWord(element, word, style, value) {
  const walker = document.createTreeWalker(
    element,
    NodeFilter.SHOW_TEXT,
    null,
    false,
  );
  const nodes = [];
  let node;
  while ((node = walker.nextNode())) nodes.push(node);

  nodes.forEach((textNode) => {
    const text = textNode.textContent;
    if (text.toLowerCase().includes(word.toLowerCase())) {
      const regex = new RegExp(word, "gi");
      const parts = text.split(regex);
      const fragment = document.createDocumentFragment();
      let matchIndex = 0;
      const matches = text.match(regex) || [];

      parts.forEach((part, index) => {
        if (part) fragment.appendChild(document.createTextNode(part));
        if (index < parts.length - 1) {
          const span = document.createElement("span");
          span.style[style] = value;
          span.textContent = matches[matchIndex] || word;
          fragment.appendChild(span);
          matchIndex++;
        }
      });
      textNode.parentNode.replaceChild(fragment, textNode);
    }
  });
}

function toggleTagOnWord(element, word, tag) {
  const walker = document.createTreeWalker(
    element,
    NodeFilter.SHOW_TEXT,
    null,
    false,
  );
  const nodes = [];
  let node;
  while ((node = walker.nextNode())) nodes.push(node);

  nodes.forEach((textNode) => {
    const text = textNode.textContent;
    if (text.toLowerCase().includes(word.toLowerCase())) {
      const parent = textNode.parentNode;
      if (parent && parent.tagName.toLowerCase() === tag.toLowerCase()) {
        const textNode2 = document.createTextNode(text);
        parent.parentNode.replaceChild(textNode2, parent);
        return;
      }

      const regex = new RegExp(word, "gi");
      const parts = text.split(regex);
      const fragment = document.createDocumentFragment();
      const matches = text.match(regex) || [];
      let matchIndex = 0;

      parts.forEach((part, index) => {
        if (part) fragment.appendChild(document.createTextNode(part));
        if (index < parts.length - 1) {
          const wrapper = document.createElement(tag);
          wrapper.textContent = matches[matchIndex] || word;
          fragment.appendChild(wrapper);
          matchIndex++;
        }
      });
      textNode.parentNode.replaceChild(fragment, textNode);
    }
  });
}

function applyInlineStyleToTargets(tag) {
  pushState();
  if (!filterWord || targetElements.length === 0) return;
  targetElements.forEach((el) => {
    toggleTagOnWord(el, filterWord, tag);
  });
}

function applyHeadingToWord(element, tagName) {
  pushState();
  if (!filterWord || targetElements.length === 0) {
    applyHeading(element, tagName);
    return;
  }

  targetElements.forEach((el) => {
    const walker = document.createTreeWalker(
      el,
      NodeFilter.SHOW_TEXT,
      null,
      false,
    );
    const nodes = [];
    let node;
    while ((node = walker.nextNode())) nodes.push(node);

    nodes.forEach((textNode) => {
      const text = textNode.textContent;
      if (text.toLowerCase().includes(filterWord.toLowerCase())) {
        const parent = textNode.parentNode;
        if (parent && parent.tagName.toLowerCase() === tagName.toLowerCase()) {
          const textNode2 = document.createTextNode(text);
          parent.parentNode.replaceChild(textNode2, parent);
          return;
        }

        const regex = new RegExp(filterWord, "gi");
        const parts = text.split(regex);
        const fragment = document.createDocumentFragment();
        const matches = text.match(regex) || [];
        let matchIndex = 0;

        parts.forEach((part, index) => {
          if (part) fragment.appendChild(document.createTextNode(part));
          if (index < parts.length - 1) {
            const heading = document.createElement(tagName);
            heading.textContent = matches[matchIndex] || filterWord;
            fragment.appendChild(heading);
            matchIndex++;
          }
        });
        textNode.parentNode.replaceChild(fragment, textNode);
      }
    });
  });
}

function removeStyleFromTargets() {
  pushState();
  if (!filterWord || targetElements.length === 0) return;
  const word = filterWord;
  targetElements.forEach((el) => {
    const walker = document.createTreeWalker(
      el,
      NodeFilter.SHOW_TEXT,
      null,
      false,
    );
    const nodes = [];
    let node;
    while ((node = walker.nextNode())) nodes.push(node);
    nodes.forEach((textNode) => {
      const text = textNode.textContent;
      if (text.toLowerCase().includes(word.toLowerCase())) {
        const parent = textNode.parentNode;
        if (
          parent &&
          (parent.tagName === "SPAN" ||
            parent.tagName === "STRONG" ||
            parent.tagName === "EM" ||
            parent.tagName === "U" ||
            parent.tagName === "STRIKE" ||
            parent.tagName === "CODE" ||
            parent.tagName.match(/^H[1-6]$/))
        ) {
          const textNode2 = document.createTextNode(text);
          parent.parentNode.replaceChild(textNode2, parent);
        }
      }
    });
  });
  lastColor = null;
  lastFontSize = null;
  lastFontFamily = null;
}

function applyColorToElement(color, element) {
  pushState();
  if (filterWord && targetElements.length > 0) {
    applyStyleToTargets("color", color);
    colorHistory = colorHistory.filter((c) => c !== color);
    colorHistory.unshift(color);
    if (colorHistory.length > 3) colorHistory.pop();
    updateSwatches(element);
    return;
  }
  if (!element || !element.contentEditable) {
    element = document.activeElement;
    if (!element || !element.contentEditable) {
      const parent = element?.closest?.('[contenteditable="true"]');
      if (parent) element = parent;
      else return;
    }
  }
  const sel = window.getSelection();
  if (sel.rangeCount > 0 && !sel.isCollapsed) {
    saveSelection();
    document.execCommand("foreColor", false, color);
    restoreSelection();
    colorHistory = colorHistory.filter((c) => c !== color);
    colorHistory.unshift(color);
    if (colorHistory.length > 3) colorHistory.pop();
    updateSwatches(element);
    return;
  }
  if (element) {
    element.style.color = color;
    colorHistory = colorHistory.filter((c) => c !== color);
    colorHistory.unshift(color);
    if (colorHistory.length > 3) colorHistory.pop();
    updateSwatches(element);
  }
}

function updateSwatches(element) {
  const toolbar = currentToolbar || activeToolbar;
  if (!toolbar) return;
  const container = toolbar.querySelector(".history-swatches");
  if (!container) return;
  container.innerHTML = "";
  colorHistory.forEach((color) => {
    const swatch = document.createElement("div");
    swatch.style.cssText = `width:22px; height:22px; border-radius:3px; background:${color}; cursor:pointer; border:1px solid #666;`;
    swatch.addEventListener(
      "mouseenter",
      () => (swatch.style.border = "2px solid #fff"),
    );
    swatch.addEventListener(
      "mouseleave",
      () => (swatch.style.border = "1px solid #666"),
    );
    swatch.addEventListener("mousedown", function (e) {
      e.preventDefault();
      e.stopPropagation();
      saveSelection();
      applyColorToElement(color, element);
      restoreSelection();
    });
    container.appendChild(swatch);
  });
}

// ===== SELETOR DE CORES =====
let colorPickerOpen = false;
let colorPickerElement = null;
let savedPickerRange = null;
let savedPickerElement = null;

function closePicker() {
  if (colorPickerElement) {
    colorPickerElement.remove();
    colorPickerElement = null;
    colorPickerOpen = false;
  }
  if (savedPickerElement) savedPickerElement.focus();
  savedPickerRange = null;
  savedPickerElement = null;
  updateSwatches();
}

function createColorPicker() {
  if (colorPickerElement) {
    closePicker();
    return;
  }
  const sel = window.getSelection();
  if (sel.rangeCount > 0) {
    savedPickerRange = sel.getRangeAt(0).cloneRange();
    savedPickerElement = sel.anchorNode
      ? sel.anchorNode.parentElement
      : document.activeElement;
    if (!savedPickerElement) savedPickerElement = document.activeElement;
  } else {
    savedPickerRange = null;
    savedPickerElement = null;
  }
  const picker = document.createElement("div");
  picker.style.cssText = `position:fixed; top:60px; left:50%; transform:translateX(-50%); z-index:10000; background:#2d2d2d; padding:16px 20px; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.7); border:1px solid #444; display:flex; flex-direction:column; gap:12px; align-items:center;`;
  const title = document.createElement("div");
  title.textContent = "🎨 Seletor de cores";
  title.style.cssText = "color:#fff; font-size:14px; font-weight:600;";
  picker.appendChild(title);
  const preview = document.createElement("div");
  preview.style.cssText =
    "width:80px; height:30px; border-radius:4px; border:1px solid #555;";
  preview.style.background = colorHistory[0] || "#ff0000";
  picker.appendChild(preview);
  const colorInput = document.createElement("input");
  colorInput.type = "color";
  colorInput.value = colorHistory[0] || "#ff0000";
  colorInput.style.cssText =
    "width:100px; height:40px; border:none; cursor:pointer; background:transparent;";
  colorInput.addEventListener("input", (e) => {
    preview.style.background = e.target.value;
  });
  picker.appendChild(colorInput);
  const actions = document.createElement("div");
  actions.style.cssText = "display:flex; gap:8px; margin-top:4px;";
  const applyBtn = document.createElement("button");
  applyBtn.textContent = "Aplicar";
  applyBtn.style.cssText =
    "background:#0d6efd; color:#fff; border:none; padding:6px 16px; border-radius:4px; cursor:pointer; font-size:12px;";
  applyBtn.addEventListener("click", (e) => {
    e.preventDefault();
    applyColorToElement(colorInput.value, document.activeElement);
    closePicker();
  });
  actions.appendChild(applyBtn);
  const closeBtn = document.createElement("button");
  closeBtn.textContent = "Fechar";
  closeBtn.style.cssText =
    "background:#666; color:#fff; border:none; padding:6px 16px; border-radius:4px; cursor:pointer; font-size:12px;";
  closeBtn.addEventListener("click", (e) => {
    e.preventDefault();
    closePicker();
  });
  actions.appendChild(closeBtn);
  picker.appendChild(actions);
  document.body.appendChild(picker);
  colorPickerElement = picker;
  colorPickerOpen = true;
  document.addEventListener("mousedown", function handler(e) {
    if (picker.contains(e.target)) return;
    closePicker();
    document.removeEventListener("mousedown", handler);
  });
}

// ===== APLICAR TAMANHO E FONTE =====
function applyFontSize(size, element) {
  pushState();
  if (filterWord && targetElements.length > 0) {
    applyStyleToTargets("font-size", size);
    return;
  }
  if (!element || !element.contentEditable) {
    element = document.activeElement;
    if (!element || !element.contentEditable) {
      const parent = element?.closest?.('[contenteditable="true"]');
      if (parent) element = parent;
      else return;
    }
  }
  const sel = window.getSelection();
  if (sel.rangeCount > 0 && !sel.isCollapsed) {
    const range = sel.getRangeAt(0);
    const selectedText = range.toString();
    if (selectedText) {
      const span = document.createElement("span");
      span.style.fontSize = size;
      span.textContent = selectedText;
      range.deleteContents();
      range.insertNode(span);
      const newRange = document.createRange();
      newRange.selectNodeContents(span);
      sel.removeAllRanges();
      sel.addRange(newRange);
      return;
    }
  }
  if (element) {
    element.style.fontSize = size;
  }
}

function applyFontFamily(family, element) {
  pushState();
  if (filterWord && targetElements.length > 0) {
    applyStyleToTargets("font-family", family);
    return;
  }
  if (!element || !element.contentEditable) {
    element = document.activeElement;
    if (!element || !element.contentEditable) {
      const parent = element?.closest?.('[contenteditable="true"]');
      if (parent) element = parent;
      else return;
    }
  }
  const sel = window.getSelection();
  if (sel.rangeCount > 0 && !sel.isCollapsed) {
    const range = sel.getRangeAt(0);
    const selectedText = range.toString();
    if (selectedText) {
      const span = document.createElement("span");
      span.style.fontFamily = family;
      span.textContent = selectedText;
      range.deleteContents();
      range.insertNode(span);
      const newRange = document.createRange();
      newRange.selectNodeContents(span);
      sel.removeAllRanges();
      sel.addRange(newRange);
      return;
    }
  }
  if (element) {
    element.style.fontFamily = family;
  }
}

// ===== API DE CORREÇÃO E DEFINIÇÃO =====
async function checkSpelling(text, language = "pt-BR") {
  try {
    const params = new URLSearchParams({ text, language });
    const response = await fetch(
      `https://api.languagetool.org/v2/check?${params}`,
    );
    const data = await response.json();
    return data.matches || [];
  } catch (e) {
    console.warn("⚠️ Erro ao verificar ortografia:", e);
    return [];
  }
}

async function getDefinition(word) {
  try {
    const response = await fetch(
      `https://api.dictionaryapi.dev/api/v2/entries/pt_BR/${encodeURIComponent(word)}`,
    );
    if (!response.ok) return null;
    const data = await response.json();
    if (data.length > 0 && data[0].meanings) {
      return (
        data[0].meanings[0]?.definitions[0]?.definition ||
        "Definição não encontrada"
      );
    }
    return null;
  } catch (e) {
    console.warn("⚠️ Erro ao buscar definição:", e);
    return null;
  }
}

// ===== DEBOUNCE GLOBAL PARA EVITAR MÚLTIPLAS EXECUÇÕES =====
let highlightTimer = null;

async function highlightErrors(element, silent = false) {
  // 🔥 NÃO EXECUTA DURANTE EDIÇÃO
  if (isEditing) {
    if (!silent) console.log("⏳ Editando, verificação ortográfica adiada");
    return 0;
  }

  // 🔥 CANCELA EXECUÇÕES ANTERIORES (debounce)
  clearTimeout(highlightTimer);
  await new Promise((resolve) => {
    highlightTimer = setTimeout(resolve, 200);
  });

  // 🔥 VERIFICA SE O ELEMENTO AINDA ESTÁ NO DOM
  if (!element || !document.contains(element)) {
    console.warn("⚠️ Elemento não está mais no DOM, ignorando");
    return 0;
  }

  const text = element.innerText;
  if (!text.trim()) {
    if (!silent) alert("O elemento está vazio.");
    return 0;
  }

  const matches = await checkSpelling(text, "pt-BR");
  if (matches.length === 0) {
    if (!silent) alert("✅ Nenhum erro encontrado.");
    return 0;
  }

  // Remove sublinhados anteriores (span com classe .spell-error)
  element.querySelectorAll(".spell-error").forEach((el) => {
    // 🔥 VERIFICA SE O NÓ AINDA ESTÁ NO DOM
    if (!el.parentNode) return;
    const parent = el.parentNode;
    const textNode = document.createTextNode(el.textContent);
    parent.replaceChild(textNode, el);
    parent.normalize();
  });

  // Prepara a estrutura: lista de nós de texto com suas posições
  const walker = document.createTreeWalker(
    element,
    NodeFilter.SHOW_TEXT,
    null,
    false,
  );
  const textNodes = [];
  let node;
  while ((node = walker.nextNode())) {
    // 🔥 VERIFICA SE O NÓ AINDA ESTÁ NO DOM
    if (node.parentNode && document.contains(node)) {
      textNodes.push(node);
    }
  }

  if (textNodes.length === 0) {
    console.warn("⚠️ Nenhum nó de texto encontrado");
    return 0;
  }

  let absoluteOffset = 0;
  const positions = [];
  textNodes.forEach((textNode) => {
    const length = textNode.textContent.length;
    positions.push({
      start: absoluteOffset,
      end: absoluteOffset + length,
      node: textNode,
    });
    absoluteOffset += length;
  });

  const sortedMatches = [...matches].sort((a, b) => b.offset - a.offset);

  for (const match of sortedMatches) {
    const { offset, length, replacements } = match;
    const errorWord = text.substring(offset, offset + length);

    for (let i = positions.length - 1; i >= 0; i--) {
      const pos = positions[i];
      if (offset >= pos.start && offset < pos.end) {
        const textNode = pos.node;

        // 🔥 VERIFICA SE O NÓ AINDA ESTÁ NO DOM ANTES DE USAR
        if (!textNode.parentNode || !document.contains(textNode)) {
          console.warn("⚠️ Nó de texto removido do DOM, ignorando");
          continue;
        }

        const fullText = textNode.textContent;
        const localStart = offset - pos.start;
        const localEnd = localStart + length;

        if (fullText.substring(localStart, localEnd) !== errorWord) {
          continue;
        }

        const before = fullText.substring(0, localStart);
        const after = fullText.substring(localEnd);

        const fragment = document.createDocumentFragment();
        if (before) fragment.appendChild(document.createTextNode(before));

        const span = document.createElement("span");
        span.className = "spell-error";
        span.textContent = errorWord;
        span.style.textDecoration = "underline wavy #ff6b6b";
        span.style.cursor = "pointer";
        span.title = "Clique para ver sugestões";

        if (replacements && replacements.length > 0) {
          span.dataset.suggestions = JSON.stringify(
            replacements.map((r) => r.value),
          );
        }

        span.addEventListener("click", function (e) {
          e.stopPropagation();
          // 🔥 VERIFICA SE O SPAN AINDA ESTÁ NO DOM
          if (!this.parentNode || !document.contains(this)) {
            console.warn("⚠️ Span já removido do DOM");
            return;
          }
          const suggestions = JSON.parse(this.dataset.suggestions || "[]");
          if (suggestions.length === 0) {
            alert(`Nenhuma sugestão para "${this.textContent}".`);
            return;
          }
          const msg =
            `Sugestões para "${this.textContent}":\n\n` +
            suggestions.map((s, i) => `${i + 1}. ${s}`).join("\n") +
            `\n\nDigite o número da sugestão para substituir, ou clique em Cancelar.`;
          const choice = prompt(msg);
          if (choice === null) return;
          const idx = parseInt(choice) - 1;
          if (idx >= 0 && idx < suggestions.length) {
            const newText = suggestions[idx];
            const parent = this.parentNode;
            // 🔥 VERIFICA SE O PARENT AINDA EXISTE
            if (parent && parent.parentNode) {
              parent.replaceChild(document.createTextNode(newText), this);
              // Reaplica a verificação no mesmo elemento após a correção
              setTimeout(() => highlightErrors(element, true), 300);
            }
          }
        });

        fragment.appendChild(span);
        if (after) fragment.appendChild(document.createTextNode(after));

        // 🔥 VERIFICAÇÃO FINAL ANTES DE SUBSTITUIR
        if (textNode.parentNode) {
          textNode.parentNode.replaceChild(fragment, textNode);
        } else {
          console.warn("⚠️ Nó de texto perdeu o parent, ignorando");
        }
        break;
      }
    }
  }

  if (!silent) {
    alert(`🔎 ${matches.length} erro(s) encontrado(s) e sublinhado(s).`);
  }
  return matches.length;
}

// ===== CORREÇÃO AUTOMÁTICA EM TEMPO REAL =====
let spellCheckTimeout = null;
let lastSpellChecked = "";

document.addEventListener("input", function (e) {
  const target = e.target;
  if (!target.closest('[contenteditable="true"]')) return;

  const text = target.innerText || "";
  if (text.length < 3) return;

  if (text === lastSpellChecked) return;
  lastSpellChecked = text;

  clearTimeout(spellCheckTimeout);
  spellCheckTimeout = setTimeout(() => {
    if (target.isConnected && target.contentEditable === "true") {
      if (typeof window.highlightErrors === "function") {
        window.highlightErrors(target, true);
      } else {
        console.warn("highlightErrors não está definida");
      }
    }
    spellCheckTimeout = null;
  }, 600);
});

// ===== CALCULA POSIÇÃO IDEAL PARA A TOOLBAR =====
function calculateToolbarPosition(element) {
  const rect = element.getBoundingClientRect();
  const savedLayout = localStorage.getItem("toolbar-layout") || "horizontal";
  const isHorizontal = savedLayout === "horizontal";
  const toolbarWidth = isHorizontal ? 500 : 220;
  const toolbarHeight = isHorizontal ? 160 : 300;

  let top = rect.top - toolbarHeight - 10;
  let left = rect.left - 10;

  const vw = window.innerWidth;
  const vh = window.innerHeight;

  // Se não couber acima, coloca abaixo
  if (top < 10) {
    top = rect.bottom + 10;
  }

  // Se não couber nem abaixo, centraliza
  if (top + toolbarHeight > vh - 10) {
    top = (vh - toolbarHeight) / 2;
    left = (vw - toolbarWidth) / 2;
  }

  // Ajusta laterais
  if (left + toolbarWidth > vw - 10) left = vw - toolbarWidth - 10;
  if (left < 10) left = 10;
  if (top < 10) top = 10;

  return { top, left };
}

// ===== RESETA A POSIÇÃO DA TOOLBAR =====
function resetToolbarPosition() {
  localStorage.removeItem("toolbar-top");
  localStorage.removeItem("toolbar-left");

  if (activeToolbar && window.__toolbarElement) {
    const element = window.__toolbarElement;
    const pos = calculateToolbarPosition(element);
    activeToolbar.style.top = pos.top + "px";
    activeToolbar.style.left = pos.left + "px";
    localStorage.setItem("toolbar-top", pos.top + "px");
    localStorage.setItem("toolbar-left", pos.left + "px");
    console.log("🔄 Posição resetada para:", pos);
  } else {
    console.log("🔄 Posição resetada (próxima abertura será recalculada)");
  }
}

// ===== CRIAÇÃO DA TOOLBAR (VERSÃO CORRIGIDA - COM POSIÇÃO SALVA) =====
function createToolbar(element) {
  const card = element.closest?.(".editable-item");
  if (!card) {
    console.warn("createToolbar: card não encontrado");
    return;
  }

  if (activeToolbar) {
    activeToolbar.remove();
    activeToolbar = null;
  }
  if (closeHandler) {
    document.removeEventListener("click", closeHandler);
    closeHandler = null;
  }

  const savedLayout = localStorage.getItem("toolbar-layout") || "horizontal";

  // ===== CRIA A TOOLBAR =====
  const toolbar = document.createElement("div");
  toolbar.className = `edit-toolbar layout-${savedLayout} visible`;
  toolbar.style.position = "fixed";
  toolbar.style.zIndex = "99999";
  toolbar.style.display = "flex";
  toolbar.style.opacity = "1";
  toolbar.style.pointerEvents = "auto";

  // ===== RECUPERA POSIÇÃO SALVA OU CALCULA =====
  const savedTop = localStorage.getItem("toolbar-top");
  const savedLeft = localStorage.getItem("toolbar-left");
  let top, left;

  if (savedTop && savedLeft) {
    top = parseFloat(savedTop);
    left = parseFloat(savedLeft);
    // Garante que fique dentro da viewport
    const maxX = window.innerWidth - toolbar.offsetWidth - 10;
    const maxY = window.innerHeight - toolbar.offsetHeight - 10;
    left = Math.max(10, Math.min(left, maxX));
    top = Math.max(10, Math.min(top, maxY));
  } else {
    // Fallback: calcula posição automática
    const pos = calculateToolbarPosition(element);
    top = pos.top;
    left = pos.left;
  }

  toolbar.style.top = top + "px";
  toolbar.style.left = left + "px";
  window.__toolbarElement = element;

  // ===== DRAG HANDLE COM AÇÕES (TOGGLE + CLOSE + RESET) =====
  const dragHandle = document.createElement("div");
  dragHandle.className = "toolbar-drag-handle";
  dragHandle.title = "Arraste para mover a toolbar";

  const gripIcon = document.createElement("i");
  gripIcon.className = "bi bi-grip-horizontal";
  dragHandle.appendChild(gripIcon);

  const titleSpan = document.createElement("span");
  titleSpan.className = "toolbar-title";
  titleSpan.textContent = "✎ Editor";
  dragHandle.appendChild(titleSpan);

  // Container para ações (toggle + reset + close)
  const actionsContainer = document.createElement("div");
  actionsContainer.className = "toolbar-header-actions";

  // Botão de toggle layout (horizontal/vertical)
  const toggleBtn = document.createElement("button");
  toggleBtn.className = "toolbar-layout-toggle";
  const isHorizontal = savedLayout === "horizontal";
  toggleBtn.innerHTML = isHorizontal
    ? `<i class="bi bi-arrow-down-up"></i>`
    : `<i class="bi bi-arrows-expand"></i>`;
  toggleBtn.title = "Alternar orientação (Horizontal / Vertical)";
  let currentIsHorizontal = isHorizontal;

  toggleBtn.addEventListener("mousedown", function (e) {
    e.preventDefault();
    e.stopPropagation();
    currentIsHorizontal = !currentIsHorizontal;
    const newLayout = currentIsHorizontal ? "horizontal" : "vertical";

    toolbar.className = `edit-toolbar layout-${newLayout} visible`;
    toggleBtn.innerHTML = currentIsHorizontal
      ? `<i class="bi bi-arrow-down-up"></i>`
      : `<i class="bi bi-arrows-expand"></i>`;

    localStorage.setItem("toolbar-layout", newLayout);

    const handle = toolbar.querySelector(".toolbar-drag-handle");
    if (handle) toolbar.prepend(handle);
  });
  actionsContainer.appendChild(toggleBtn);

  // 🔥 BOTÃO DE RESETAR POSIÇÃO
  const resetBtn = document.createElement("button");
  resetBtn.className = "toolbar-reset-btn";
  resetBtn.innerHTML = "↺";
  resetBtn.title = "Resetar posição da toolbar";
  resetBtn.style.cssText = `
    background: transparent;
    border: none;
    color: #888;
    font-size: 18px;
    cursor: pointer;
    padding: 2px 6px;
    transition: color 0.2s, transform 0.2s;
    line-height: 1;
  `;
  resetBtn.addEventListener("mouseenter", () => {
    resetBtn.style.color = "#fff";
    resetBtn.style.transform = "rotate(60deg)";
  });
  resetBtn.addEventListener("mouseleave", () => {
    resetBtn.style.color = "#888";
    resetBtn.style.transform = "rotate(0deg)";
  });
  resetBtn.addEventListener("mousedown", function (e) {
    e.preventDefault();
    e.stopPropagation();
    resetToolbarPosition();
  });
  actionsContainer.appendChild(resetBtn);

  // Botão de fechar
  const closeBtn = document.createElement("button");
  closeBtn.className = "toolbar-close-btn";
  closeBtn.textContent = "✕";
  closeBtn.title = "Fechar edição";
  closeBtn.addEventListener("mousedown", function (e) {
    e.preventDefault();
    e.stopPropagation();
    removeHighlightSpans();
    toolbar.remove();
    activeToolbar = null;
    currentToolbar = null;
    element.classList.remove("active-editing");
    saveEditableContent();
    const tableCell = element.closest("td, th");
    if (tableCell) {
      const tableWrapper = tableCell.closest(".editable-item");
      if (tableWrapper) {
        const tableToolbar = tableWrapper.querySelector(".table-toolbar");
        if (tableToolbar) {
          tableToolbar.classList.add("hidden");
          tableToolbar.classList.remove("visible");
        }
      }
    }
    if (closeHandler) {
      document.removeEventListener("click", closeHandler);
      closeHandler = null;
    }
    targetElements.forEach((el) => el.classList.remove("target-selected"));
    targetElements = [];
    filterWord = "";
    element.contentEditable = false;
    element.blur();
  });
  actionsContainer.appendChild(closeBtn);

  dragHandle.appendChild(actionsContainer);

  // ===== LÓGICA DE ARRASTE COM SALVAMENTO =====
  let isDragging = false;
  let dragOffsetX = 0;
  let dragOffsetY = 0;

  function startDrag(e) {
    if (!e.target.closest(".toolbar-drag-handle")) return;
    if (e.button !== 0) return;
    e.preventDefault();

    isDragging = true;
    const rect = toolbar.getBoundingClientRect();
    dragOffsetX = e.clientX - rect.left;
    dragOffsetY = e.clientY - rect.top;

    toolbar.classList.add("dragging");

    document.addEventListener("mousemove", onDrag);
    document.addEventListener("mouseup", stopDrag);
  }

  function onDrag(e) {
    if (!isDragging) return;
    e.preventDefault();
    let newLeft = e.clientX - dragOffsetX;
    let newTop = e.clientY - dragOffsetY;

    const maxX = window.innerWidth - toolbar.offsetWidth - 10;
    const maxY = window.innerHeight - toolbar.offsetHeight - 10;
    newLeft = Math.max(10, Math.min(newLeft, maxX));
    newTop = Math.max(10, Math.min(newTop, maxY));

    toolbar.style.left = newLeft + "px";
    toolbar.style.top = newTop + "px";

    // SALVA A POSIÇÃO EM TEMPO REAL DURANTE O ARRASTE
    localStorage.setItem("toolbar-left", newLeft + "px");
    localStorage.setItem("toolbar-top", newTop + "px");
  }

  function stopDrag() {
    if (!isDragging) return;
    isDragging = false;
    toolbar.classList.remove("dragging");
    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
  }

  dragHandle.addEventListener("mousedown", startDrag);

  // ===== ADICIONA O HANDLE AO INÍCIO DA TOOLBAR =====
  toolbar.appendChild(dragHandle);

  // Função auxiliar para criar botões
  function createSideButton(label, action, title = "", extraClass = "") {
    const btn = document.createElement("button");
    btn.className = `toolbar-btn ${extraClass}`;
    btn.textContent = label;
    if (title) btn.title = title;
    btn.addEventListener("mousedown", function (e) {
      e.preventDefault();
      e.stopPropagation();
      saveSelection();
      action();
      restoreSelection();
      element.focus();
    });
    return btn;
  }

  // ===== SEÇÃO FILTRO =====
  const filterSection = document.createElement("div");
  filterSection.className = "toolbar-section";
  const filterLabel = document.createElement("div");
  filterLabel.className = "toolbar-section-label";
  filterLabel.textContent = "🔎 Palavra‑chave";
  filterSection.appendChild(filterLabel);
  const filterInput = document.createElement("input");
  filterInput.className = "toolbar-input";
  filterInput.type = "text";
  filterInput.placeholder = "Digite a palavra...";
  filterInput.value = filterWord || "";
  filterInput.addEventListener("input", function (e) {
    const word = this.value.trim();
    filterWord = word;
    updateHighlights();
    updateFilterIndicator();
  });
  filterSection.appendChild(filterInput);
  toolbar.appendChild(filterSection);

  // ===== SEÇÃO CORES =====
  const colorSection = document.createElement("div");
  colorSection.className = "toolbar-section";
  const colorLabel = document.createElement("div");
  colorLabel.className = "toolbar-section-label";
  colorLabel.textContent = "🎨 Cores";
  colorSection.appendChild(colorLabel);
  const swatchContainer = document.createElement("div");
  swatchContainer.className = "toolbar-swatches";
  colorSection.appendChild(swatchContainer);
  const pickerBtn = createSideButton(
    "🎨 Seletor",
    () => {
      if (colorPickerOpen) closePicker();
      else createColorPicker();
    },
    "",
    "primary",
  );
  colorSection.appendChild(pickerBtn);
  toolbar.appendChild(colorSection);
  updateSwatches(element);

  // ===== SEÇÃO TAMANHO =====
  const sizeSection = document.createElement("div");
  sizeSection.className = "toolbar-section";
  const sizeLabel = document.createElement("div");
  sizeLabel.className = "toolbar-section-label";
  sizeLabel.textContent = "📏 Tamanho";
  sizeSection.appendChild(sizeLabel);

  const sizeRow = document.createElement("div");
  sizeRow.className = "size-row";

  const sizeSelect = document.createElement("select");
  sizeSelect.className = "toolbar-select";
  const sizes = [
    "12px",
    "14px",
    "16px",
    "18px",
    "20px",
    "24px",
    "28px",
    "32px",
    "40px",
    "48px",
  ];
  sizes.forEach((s) => {
    const opt = document.createElement("option");
    opt.value = s;
    opt.textContent = s;
    sizeSelect.appendChild(opt);
  });
  sizeSelect.value = "16px";
  sizeSelect.addEventListener("change", function (e) {
    e.stopPropagation();
    applyFontSize(this.value, element);
  });
  sizeSelect.addEventListener("mousedown", (e) => e.stopPropagation());
  sizeRow.appendChild(sizeSelect);

  const btnWrapper = document.createElement("div");
  btnWrapper.className = "toolbar-buttons";

  const sizesMap = ["12px", "16px", "24px", "32px"];
  ["S", "M", "L", "XL"].forEach((label, idx) => {
    const btn = createSideButton(label, () =>
      applyFontSize(sizesMap[idx], element),
    );
    btnWrapper.appendChild(btn);
  });

  sizeRow.appendChild(btnWrapper);
  sizeSection.appendChild(sizeRow);
  toolbar.appendChild(sizeSection);

  // ===== SEÇÃO FONTE =====
  const fontSection = document.createElement("div");
  fontSection.className = "toolbar-section";
  const fontLabel = document.createElement("div");
  fontLabel.className = "toolbar-section-label";
  fontLabel.textContent = "🔤 Fonte";
  fontSection.appendChild(fontLabel);
  const fontSelect = document.createElement("select");
  fontSelect.className = "toolbar-select";
  const fonts = [
    "Share Tech Mono, Courier, monospace",
    "Arial, sans-serif",
    "Helvetica, sans-serif",
    "Georgia, serif",
    "Times New Roman, serif",
    "Courier New, monospace",
    "Verdana, sans-serif",
    "Tahoma, sans-serif",
    "Trebuchet MS, sans-serif",
    "Impact, sans-serif",
    "Comic Sans MS, cursive",
  ];
  fonts.forEach((f) => {
    const opt = document.createElement("option");
    opt.value = f;
    opt.textContent = f.split(",")[0];
    fontSelect.appendChild(opt);
  });
  fontSelect.value = fonts[0];
  fontSelect.addEventListener("change", function (e) {
    e.stopPropagation();
    applyFontFamily(this.value, element);
  });
  fontSelect.addEventListener("mousedown", (e) => e.stopPropagation());
  fontSection.appendChild(fontSelect);
  toolbar.appendChild(fontSection);

  // ===== SEÇÃO ESTILOS =====
  const inlineSection = document.createElement("div");
  inlineSection.className = "toolbar-section";
  const inlineLabel = document.createElement("div");
  inlineLabel.className = "toolbar-section-label";
  inlineLabel.textContent = "✏️ Estilos";
  inlineSection.appendChild(inlineLabel);
  const inlineRow = document.createElement("div");
  inlineRow.className = "toolbar-row";
  const inlineMap = [
    { label: "B", cmd: "bold" },
    { label: "I", cmd: "italic" },
    { label: "U", cmd: "underline" },
    { label: "S", cmd: "strikeThrough" },
    { label: "Code", cmd: null, tag: "code" },
    { label: "🚫", cmd: null, action: removeStyleFromTargets, extra: "danger" },
  ];
  inlineMap.forEach((item) => {
    const btn = document.createElement("button");
    btn.className = `toolbar-btn ${item.extra || ""}`;
    btn.textContent = item.label;
    btn.addEventListener("mousedown", function (e) {
      e.preventDefault();
      e.stopPropagation();
      saveSelection();
      const sel = window.getSelection();
      const hasSelection = sel.rangeCount > 0 && !sel.isCollapsed;
      if (filterWord && targetElements.length > 0) {
        if (item.cmd) applyInlineStyleToTargets(item.cmd);
        else if (item.tag === "code") applyInlineStyleToTargets("code");
        else if (item.action) item.action();
      } else if (hasSelection) {
        if (item.cmd) document.execCommand(item.cmd, false, null);
        else if (item.tag === "code") {
          const text = sel.toString();
          document.execCommand("insertHTML", false, `<code>${text}</code>`);
        } else if (item.action) item.action();
      } else {
        let targetElement = element;
        if (!targetElement || !targetElement.contentEditable) {
          targetElement = document.activeElement;
          if (!targetElement || !targetElement.contentEditable) {
            const parent = targetElement?.closest?.('[contenteditable="true"]');
            if (parent) targetElement = parent;
            else return;
          }
        }
        if (item.cmd) {
          const tagMap = {
            bold: "strong",
            italic: "em",
            underline: "u",
            strikeThrough: "strike",
          };
          const tag = tagMap[item.cmd];
          if (tag) {
            const alreadyApplied = targetElement.querySelector(tag);
            if (alreadyApplied) {
              const parent = alreadyApplied.parentNode;
              while (alreadyApplied.firstChild)
                parent.insertBefore(alreadyApplied.firstChild, alreadyApplied);
              parent.removeChild(alreadyApplied);
            } else {
              const wrapper = document.createElement(tag);
              while (targetElement.firstChild)
                wrapper.appendChild(targetElement.firstChild);
              targetElement.appendChild(wrapper);
            }
          }
        } else if (item.tag === "code") {
          const wrapper = document.createElement("code");
          while (targetElement.firstChild)
            wrapper.appendChild(targetElement.firstChild);
          targetElement.appendChild(wrapper);
        } else if (item.action) item.action();
      }
      restoreSelection();
      element.focus();
    });
    inlineRow.appendChild(btn);
  });
  inlineSection.appendChild(inlineRow);
  toolbar.appendChild(inlineSection);

  // ===== SEÇÃO TÍTULOS =====
  const headingSection = document.createElement("div");
  headingSection.className = "toolbar-section";
  const headingLabel = document.createElement("div");
  headingLabel.className = "toolbar-section-label";
  headingLabel.textContent = "📐 Títulos";
  headingSection.appendChild(headingLabel);
  const headingRow = document.createElement("div");
  headingRow.className = "toolbar-row";
  ["H1", "H2", "H3", "H4", "H5", "H6"].forEach((h, idx) => {
    const tag = `h${idx + 1}`;
    const btn = createSideButton(h, () => applyHeadingToWord(element, tag));
    btn.style.flex = "1";
    headingRow.appendChild(btn);
  });
  headingSection.appendChild(headingRow);
  toolbar.appendChild(headingSection);

  // ===== SEÇÃO ALINHAMENTO =====
  const alignSection = document.createElement("div");
  alignSection.className = "toolbar-section";
  const alignLabel = document.createElement("div");
  alignLabel.className = "toolbar-section-label";
  alignLabel.textContent = "↔ Alinhamento";
  alignSection.appendChild(alignLabel);
  const alignRow = document.createElement("div");
  alignRow.className = "toolbar-row";
  const alignMap = [
    { label: "←", align: "left" },
    { label: "↔", align: "center" },
    { label: "→", align: "right" },
    { label: "↕", align: "justify" },
  ];
  alignMap.forEach((item) => {
    const btn = createSideButton(item.label, () => {
      const activeEl = document.activeElement;
      if (activeEl && activeEl.closest('[contenteditable="true"]')) {
        activeEl.style.textAlign = item.align;
        activeEl.style.backgroundColor = "";
        activeEl
          .querySelectorAll("*")
          .forEach((el) => (el.style.backgroundColor = ""));
      } else {
        const sel = window.getSelection();
        if (sel.rangeCount > 0 && !sel.isCollapsed) {
          const range = sel.getRangeAt(0);
          const container = range.commonAncestorContainer;
          const editable =
            container.nodeType === Node.TEXT_NODE
              ? container.parentElement.closest('[contenteditable="true"]')
              : container.closest('[contenteditable="true"]');
          if (editable) {
            editable.style.textAlign = item.align;
            editable.style.backgroundColor = "";
            editable
              .querySelectorAll("*")
              .forEach((el) => (el.style.backgroundColor = ""));
          }
        }
      }
      saveEditableContent();
    });
    btn.style.flex = "1";
    alignRow.appendChild(btn);
  });
  alignSection.appendChild(alignRow);
  toolbar.appendChild(alignSection);

  // ===== RODAPÉ =====
  const footer = document.createElement("div");
  footer.className = "toolbar-footer";

  const filterIndicator = document.createElement("span");
  filterIndicator.className = "filter-indicator";
  if (filterWord) filterIndicator.textContent = `🔍 "${filterWord}"`;
  else filterIndicator.textContent = "";
  footer.appendChild(filterIndicator);

  toolbar.appendChild(footer);

  // ===== ADICIONA AO DOM =====
  document.body.appendChild(toolbar);
  currentToolbar = toolbar;
  activeToolbar = toolbar;

  updateSwatches(element);
  updateFilterIndicator();

  // ===== CLOSE HANDLER =====
  closeHandler = function handler(e) {
    if (e.shiftKey) return;
    if (colorPickerOpen) return;
    const target = e.target;
    if (toolbar.contains(target)) return;
    if (element && element.contains(target)) return;
    if (target.closest(".table-toolbar")) return;

    saveEditableContent();
    element.classList.remove("active-editing");
    const tableCell = element.closest("td, th");
    if (tableCell) {
      const tableWrapper = tableCell.closest(".editable-item");
      if (tableWrapper) {
        const tableToolbar = tableWrapper.querySelector(".table-toolbar");
        if (tableToolbar) {
          tableToolbar.classList.add("hidden");
          tableToolbar.classList.remove("visible");
        }
      }
    }
    removeHighlightSpans();
    toolbar.remove();
    activeToolbar = null;
    currentToolbar = null;
    document.removeEventListener("click", closeHandler);
    closeHandler = null;
    targetElements.forEach((el) => el.classList.remove("target-selected"));
    targetElements = [];
    filterWord = "";
    element.contentEditable = false;
    element.blur();
  };

  document.addEventListener("click", closeHandler);
  addResizeHandle(card);
}
// Expor funções globalmente para uso em outros contextos
window.calculateToolbarPosition = calculateToolbarPosition;
window.resetToolbarPosition = resetToolbarPosition;
window.createToolbar = createToolbar;

function removeHighlightSpans() {
  document.querySelectorAll(".filter-highlight").forEach((el) => {
    const parent = el.parentNode;
    while (el.firstChild) {
      parent.insertBefore(el.firstChild, el);
    }
    parent.removeChild(el);
    parent.normalize();
  });
}

function enableEditOnDoubleClick(element, onBlurCallback) {
  element.style.cursor = "pointer";
  element.addEventListener("click", function (e) {
    // 1) Shift+Click → toggle target (selecionar palavra para filtro)
    if (e.shiftKey) {
      e.preventDefault();
      e.stopPropagation();

      toggleTargetElement(this);
      window.getSelection().removeAllRanges();
      return;
    }

    // 2) Ctrl+Click → ativa edição (abre toolbar)
    if (e.ctrlKey) {
      e.preventDefault();
      e.stopPropagation();

      targetElements.forEach((el) => el.classList.remove("target-selected"));
      targetElements = [];
      this.classList.add("active-editing");
      targetElements.push(this);
      this.classList.add("target-selected");

      this.contentEditable = true;
      this.focus();

      const sel = window.getSelection();
      const firstNode = this.firstChild;
      if (firstNode) {
        const range = document.createRange();
        range.setStart(firstNode, 0);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
      } else {
        const textNode = document.createTextNode("");
        this.appendChild(textNode);
        const range = document.createRange();
        range.setStart(textNode, 0);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
      }

      const tableCell = this.closest("td, th");
      if (tableCell) {
        const tableWrapper = tableCell.closest(".editable-item");
        if (tableWrapper) {
          const toolbar = tableWrapper.querySelector(".table-toolbar");
          if (toolbar) {
            toolbar.classList.remove("hidden");
            toolbar.classList.add("visible");
          }
        }
      }
      createToolbar(this);
      updateSizeSelect();
      updateFilterIndicator();
      if (filterWord) {
        updateHighlights();
      }
      return; // ← importante: não executa o resto
    }

    // 3) Se já está em modo de edição, não faz nada (deixa o navegador cuidar do cursor)
    if (this.contentEditable === "true") {
      return;
    }

    // 4) Clique normal (fora da edição) → limpa seleções e destaca o item
    const item = this.closest(".editable-item");
    if (item) item.focus();

    document.querySelectorAll(".table-toolbar").forEach((el) => {
      el.classList.add("hidden");
      el.classList.remove("visible");
    });

    if (targetElements.length > 0) {
      targetElements.forEach((el) => el.classList.remove("target-selected"));
      targetElements = [];
      removeHighlightSpans();
      filterWord = "";
      if (activeToolbar) {
        const indicator = activeToolbar.querySelector(".filter-indicator");
        if (indicator) indicator.textContent = "";
      }
    }
  });
}

// Salva o conteúdo de todos os .editable-item no localStorage
function saveEditableContent() {
  const items = document.querySelectorAll(".editable-item");
  const data = [];
  items.forEach((el) => {
    data.push({
      id: el.id || `item-${Math.random()}`,
      html: el.innerHTML,
      textAlign: el.style.textAlign || "", // <-- salva o alinhamento
    });
  });
  localStorage.setItem("editableContent", JSON.stringify(data));
}
let saveContentTimer;
function debouncedSaveEditableContent() {
  clearTimeout(saveContentTimer);
  saveContentTimer = setTimeout(saveEditableContent, 300);
}

// Restaura o conteúdo salvo
function restoreEditableContent() {
  const saved = localStorage.getItem("editableContent");
  if (!saved) return;
  const data = JSON.parse(saved);
  const items = document.querySelectorAll(".editable-item");
  data.forEach((item, index) => {
    if (items[index]) {
      items[index].innerHTML = item.html;
      if (item.textAlign) {
        items[index].style.textAlign = item.textAlign;
      }
    }
  });
}

function updateHighlights() {
  removeHighlightSpans();
  if (!filterWord || targetElements.length === 0) return;
  const word = filterWord;
  targetElements.forEach((el) => {
    const walker = document.createTreeWalker(
      el,
      NodeFilter.SHOW_TEXT,
      null,
      false,
    );
    const nodes = [];
    let node;
    while ((node = walker.nextNode())) nodes.push(node);
    nodes.forEach((textNode) => {
      const text = textNode.textContent;
      if (text.toLowerCase().includes(word.toLowerCase())) {
        const parts = text.split(new RegExp(`(${word})`, "gi"));
        const fragment = document.createDocumentFragment();
        parts.forEach((part) => {
          if (!part) return;
          if (part.toLowerCase() === word.toLowerCase()) {
            const span = document.createElement("span");
            span.className = "filter-highlight";
            span.textContent = part;
            fragment.appendChild(span);
          } else {
            fragment.appendChild(document.createTextNode(part));
          }
        });
        textNode.parentNode.replaceChild(fragment, textNode);
      }
    });
  });
}

function addResizeHandle(container) {
  const handle = document.createElement("div");
  handle.className = "resize-handle";
  handle.title = "Redimensionar (Shift para desativar snap)";
  handle.style.cssText = `
        position: absolute;
        bottom: 4px;
        right: 4px;
        width: 24px;
        height: 24px;
        cursor: nwse-resize;
        opacity: 0;
        transition: opacity 0.25s ease, color 0.2s, transform 0.2s;
        z-index: 15;
        user-select: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-family: 'Segoe UI', system-ui, sans-serif;
        color: #888;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    `;
  handle.textContent = "";

  container.addEventListener(
    "mouseenter",
    () => (handle.style.opacity = "0.8"),
  );
  container.addEventListener("mouseleave", () => (handle.style.opacity = "0"));

  const ABSOLUTE_MAX_WIDTH = 1200;
  const ABSOLUTE_MAX_HEIGHT = 900;

  let isResizing = false;
  let startX, startY, startWidth, startHeight;

  handle.addEventListener("mousedown", function (e) {
    e.preventDefault();
    e.stopPropagation();
    isResizing = true;
    startX = e.clientX;
    startY = e.clientY;
    startWidth = container.offsetWidth;
    startHeight = container.offsetHeight;

    const parent = container.parentElement;
    const padding = 20;

    document.body.style.userSelect = "none";
    container.style.cursor = "nwse-resize";
    handle.style.opacity = "1";

    function onMouseMove(ev) {
      if (!isResizing) return;
      ev.preventDefault();

      let deltaX = ev.clientX - startX;
      let deltaY = ev.clientY - startY;
      let newWidth = Math.max(80, startWidth + deltaX);
      let newHeight = Math.max(40, startHeight + deltaY);

      let maxWidth = Math.max(
        80,
        parent.clientWidth - padding * 2 - parseInt(container.style.left || 0),
      );
      let maxHeight = Math.max(
        40,
        parent.clientHeight - padding * 2 - parseInt(container.style.top || 0),
      );

      maxWidth = Math.min(maxWidth, ABSOLUTE_MAX_WIDTH);
      maxHeight = Math.min(maxHeight, ABSOLUTE_MAX_HEIGHT);

      newWidth = Math.min(newWidth, maxWidth);
      newHeight = Math.min(newHeight, maxHeight);

      if (!ev.shiftKey) {
        newWidth = Math.round(newWidth / 20) * 20;
        newHeight = Math.round(newHeight / 20) * 20;
      }

      newWidth = Math.min(newWidth, maxWidth);
      newHeight = Math.min(newHeight, maxHeight);

      container.style.width = newWidth + "px";
      container.style.height = newHeight + "px";
      updateAllConnections();
      updateContainerHeight();
    }

    function onMouseUp() {
      isResizing = false;
      document.body.style.userSelect = "";
      container.style.cursor = "";
      handle.style.opacity = "0.8";
      document.removeEventListener("mousemove", onMouseMove);
      document.removeEventListener("mouseup", onMouseUp);
      pushState();
      container.style.height = "auto";
      container.style.overflow = "visible";
      updateContainerHeight();
    }

    document.addEventListener("mousemove", onMouseMove);
    document.addEventListener("mouseup", onMouseUp);
  });

  container.appendChild(handle);
}

// ============================================================
//  CALLBACKS E UTILITÁRIOS
// ============================================================

function parseOnBlur(element) {
  const rawText = element.innerText;
  if (/(^|\n)\s*[#-]\s/.test(rawText)) {
    element.innerHTML = parseMarkdown(rawText);
  }
}

function highlightOnBlur(element) {
  const raw = element.innerText;
  element.innerHTML = highlightCode(raw);
}

function plainTextOnBlur(element) {
  /* mantém texto puro */
}

function parseOnBlurSafe(element) {
  if (element.querySelector("span, strong, em, u, strike, code")) {
    return;
  }
  const rawText = element.innerText;
  if (/(^|\n)\s*[#-]\s/.test(rawText)) {
    element.innerHTML = parseMarkdown(rawText);
  }
}

function toggleInlineStyle(element, tag) {
  pushState();
  const child = element.querySelector(tag);
  if (child) {
    const parent = child.parentNode;
    while (child.firstChild) {
      parent.insertBefore(child.firstChild, child);
    }
    parent.removeChild(child);
  } else {
    const wrapper = document.createElement(tag);
    while (element.firstChild) {
      wrapper.appendChild(element.firstChild);
    }
    element.appendChild(wrapper);
  }
}

// ============================================================
//  ATUALIZAR SELECT DE TAMANHO
// ============================================================
function updateSizeSelect() {
  const sel = window.getSelection();
  if (!sel.rangeCount || sel.isCollapsed) {
    const toolbar = currentToolbar || activeToolbar;
    if (toolbar) {
      const select = toolbar.querySelector("select");
      if (select) select.value = "";
    }
    return;
  }
  const range = sel.getRangeAt(0);
  let textNode = null;
  if (range.startContainer.nodeType === Node.TEXT_NODE) {
    textNode = range.startContainer;
  } else {
    const walker = document.createTreeWalker(
      range.commonAncestorContainer,
      NodeFilter.SHOW_TEXT,
      {
        acceptNode: function (node) {
          if (range.intersectsNode(node)) {
            return NodeFilter.FILTER_ACCEPT;
          }
          return NodeFilter.FILTER_REJECT;
        },
      },
    );
    textNode = walker.nextNode();
  }
  if (!textNode) {
    const toolbar = currentToolbar || activeToolbar;
    if (toolbar) {
      const select = toolbar.querySelector("select");
      if (select) select.value = "";
    }
    return;
  }
  const parentElement = textNode.parentElement;
  if (!parentElement) {
    const toolbar = currentToolbar || activeToolbar;
    if (toolbar) {
      const select = toolbar.querySelector("select");
      if (select) select.value = "";
    }
    return;
  }
  const fontSize = window.getComputedStyle(parentElement).fontSize;
  const toolbar = currentToolbar || activeToolbar;
  if (!toolbar) return;
  const select = toolbar.querySelector("select");
  if (!select) return;
  const optionExists = Array.from(select.options).some(
    (opt) => opt.value === fontSize,
  );
  select.value = optionExists ? fontSize : "";
}

document.addEventListener("mouseup", function () {
  if (activeToolbar) {
    updateSizeSelect();
  }
});
