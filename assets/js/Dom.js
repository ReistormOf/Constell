// ============================================================
//  BLOQUEAR MENU DE CONTEXTO
// ============================================================
document.addEventListener(
  "contextmenu",
  function (e) {
    let target = e.target;
    while (target && target !== document) {
      if (target.contentEditable === "true") {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }
      target = target.parentNode;
    }
  },
  true,
);

// ============================================================
//  PASTE COM TEXTO PURO
// ============================================================
document.addEventListener("paste", function (e) {
  const target = e.target;
  if (target && target.contentEditable === "true") {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData(
      "text/plain",
    );
    document.execCommand("insertText", false, text);
  }
});

window.addEventListener(
  "wheel",
  function (e) {
    if (e.ctrlKey) {
      setTimeout(updateAllConnections, 50); // aguarda o zoom ser aplicado
    }
  },
  { passive: true },
);
document.addEventListener("DOMContentLoaded", function () {
  loadHistory();
});

document.addEventListener("paste", function (e) {
  const items = e.clipboardData.items;
  let imageFile = null;
  for (let item of items) {
    if (item.type.startsWith("image/")) {
      imageFile = item.getAsFile();
      break;
    }
  }
  if (!imageFile) return;

  e.preventDefault(); // Impede colagem em outros lugares

  // Verifica se há um card de imagem em foco ou selecionado
  const activeCard = document.querySelector(".editable-item.active-editing");
  const imageCard = activeCard?.querySelector(".editable-item")
    ? activeCard
    : null;

  if (imageCard && imageCard.__loadImageFromFile) {
    // Substitui a imagem no card existente
    imageCard.__loadImageFromFile(imageFile);
  } else {
    // Cria um novo card de imagem
    addImageCard();
    // Aguarda a criação e insere a imagem
    setTimeout(() => {
      const cards = document.querySelectorAll(".editable-item");
      const lastCard = cards[cards.length - 1];
      if (lastCard && lastCard.__loadImageFromFile) {
        lastCard.__loadImageFromFile(imageFile);
      }
    }, 50);
  }
});
