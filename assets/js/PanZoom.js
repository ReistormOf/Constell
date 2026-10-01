// ================================================================
//  PAN & ZOOM – Com throttle fixo a 60fps (16ms)
// ================================================================

(function () {
  const container = document.getElementById("cards-container");
  if (!container) return;

  let scale = 1;
  let translateX = 0;
  let translateY = 0;

  const MIN_SCALE = 0.2;
  const MAX_SCALE = 3.0;
  const UPDATE_INTERVAL = 16; // 16ms ≈ 60fps

  let panThrottleTimer = null;
  let updateFramePending = false;

  function applyTransform() {
    container.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
    container.style.transformOrigin = "0 0";

    // Atualiza conexões e altura com throttle fixo
    if (!updateFramePending) {
      updateFramePending = true;
      setTimeout(() => {
        updateFramePending = false;
        if (typeof updateAllConnections === "function") {
          updateAllConnections();
        }
        if (typeof updateContainerHeight === "function") {
          updateContainerHeight();
        }
      }, UPDATE_INTERVAL);
    }
  }

  function zoomAt(factor, mouseX, mouseY) {
    const oldScale = scale;
    const newScale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, scale * factor));
    if (newScale === oldScale) return;

    const rect = container.getBoundingClientRect();
    const x = mouseX - rect.left;
    const y = mouseY - rect.top;

    translateX = x - (x - translateX) * (newScale / oldScale);
    translateY = y - (y - translateY) * (newScale / oldScale);
    scale = newScale;
    applyTransform();
  }

  function resetView() {
    scale = 1;
    translateX = 0;
    translateY = 0;
    applyTransform();
  }

  // --- Pan com throttle fixo ---
  let isPanning = false;
  let startX = 0,
    startY = 0;
  let startTranslateX = 0,
    startTranslateY = 0;

  function onPanStart(e) {
    const btn = e.button;
    if (btn !== 1 && btn !== 2) return;

    e.preventDefault();
    e.stopPropagation();

    isPanning = true;
    startX = e.clientX;
    startY = e.clientY;
    startTranslateX = translateX;
    startTranslateY = translateY;

    container.style.cursor = "grabbing";
    document.addEventListener("mousemove", onPanMove);
    document.addEventListener("mouseup", onPanEnd);
  }

  function onPanMove(e) {
    if (!isPanning) return;
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;
    translateX = startTranslateX + dx;
    translateY = startTranslateY + dy;

    // 🔥 Throttle fixo de 16ms (60fps)
    if (!panThrottleTimer) {
      panThrottleTimer = setTimeout(() => {
        panThrottleTimer = null;
        applyTransform();
      }, UPDATE_INTERVAL);
    }
  }

  function onPanEnd() {
    isPanning = false;
    container.style.cursor = "default";
    document.removeEventListener("mousemove", onPanMove);
    document.removeEventListener("mouseup", onPanEnd);
    if (panThrottleTimer) {
      clearTimeout(panThrottleTimer);
      panThrottleTimer = null;
    }
    applyTransform();
  }

  // --- Eventos ---
  document.addEventListener(
    "wheel",
    function (e) {
      if (e.ctrlKey) e.preventDefault();
    },
    { passive: false },
  );

  container.addEventListener(
    "wheel",
    function (e) {
      if (!e.ctrlKey) return;
      e.preventDefault();
      const delta = e.deltaY > 0 ? 0.9 : 1.1;
      zoomAt(delta, e.clientX, e.clientY);
    },
    { passive: false },
  );

  container.addEventListener("mousedown", onPanStart);
  container.addEventListener("contextmenu", function (e) {
    e.preventDefault();
  });

  // --- Botões globais ---
  window.zoomIn = function () {
    const rect = container.getBoundingClientRect();
    zoomAt(1.2, rect.left + rect.width / 2, rect.top + rect.height / 2);
  };
  window.zoomOut = function () {
    const rect = container.getBoundingClientRect();
    zoomAt(0.8, rect.left + rect.width / 2, rect.top + rect.height / 2);
  };
  window.resetView = resetView;

  window.panZoom = {
    scale,
    translateX,
    translateY,
    zoomAt,
    resetView,
    applyTransform,
  };

  // --- Inicialização ---
  applyTransform();
  console.log("✅ Pan & Zoom com throttle fixo a 60fps");
})();
