(function () {
  const PDFJS_CDN = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs";
  const PDFJS_WORKER = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.worker.min.mjs";

  async function ensurePdfJs() {
    if (window.pdfjsLib) {
      return window.pdfjsLib;
    }

    if (!window.__pulsePdfJsLoading) {
      window.__pulsePdfJsLoading = import(PDFJS_CDN).then((module) => {
        const lib = module && module.default ? module.default : module;
        lib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER;
        window.pdfjsLib = lib;
        return lib;
      });
    }

    return window.__pulsePdfJsLoading;
  }

  async function setupFlipbook(root) {
    const pdfUrl = root.getAttribute("data-pdf-url");
    const canvas = root.querySelector('[data-role="canvas"]');
    const loading = root.querySelector('[data-role="loading"]');
    const error = root.querySelector('[data-role="error"]');
    const status = root.querySelector('[data-role="status"]');

    if (!pdfUrl || !canvas || !loading || !error || !status) {
      return;
    }

    root.setAttribute("tabindex", "-1");
    canvas.setAttribute("tabindex", "-1");
    root.addEventListener(
      "pointerdown",
      (e) => {
        if (root.contains(e.target)) {
          root.focus({ preventScroll: true });
        }
      },
      true
    );

    try {
      const pdfjsLib = await ensurePdfJs();
      const docTask = pdfjsLib.getDocument({ url: pdfUrl });
      const pdf = await docTask.promise;

      let pageNumber = 1;
      let scale = 1.05;
      const ctx = canvas.getContext("2d");

      function setStatus() {
        status.textContent = pageNumber + " / " + pdf.numPages;
      }

      async function renderPage() {
        loading.hidden = false;
        error.hidden = true;

        const page = await pdf.getPage(pageNumber);
        const viewport = page.getViewport({ scale });
        const ratio = window.devicePixelRatio || 1;

        canvas.width = Math.floor(viewport.width * ratio);
        canvas.height = Math.floor(viewport.height * ratio);
        canvas.style.width = Math.floor(viewport.width) + "px";
        canvas.style.height = Math.floor(viewport.height) + "px";
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

        await page.render({ canvasContext: ctx, viewport }).promise;
        setStatus();
        loading.hidden = true;
      }

      root.addEventListener("click", async (event) => {
        const button = event.target.closest("[data-action]");
        if (!button) {
          return;
        }

        const action = button.getAttribute("data-action");
        if (action === "next" && pageNumber < pdf.numPages) {
          pageNumber += 1;
          await renderPage();
        } else if (action === "prev" && pageNumber > 1) {
          pageNumber -= 1;
          await renderPage();
        } else if (action === "zoom-in") {
          scale = Math.min(scale + 0.15, 2.4);
          await renderPage();
        } else if (action === "zoom-out") {
          scale = Math.max(scale - 0.15, 0.7);
          await renderPage();
        } else if (action === "fullscreen") {
          const viewport = root.querySelector(".pulse-flipbook__viewport");
          if (!viewport) {
            return;
          }
          if (!document.fullscreenElement) {
            await viewport.requestFullscreen();
          } else {
            await document.exitFullscreen();
          }
        }
      });

      root.addEventListener("keydown", async (event) => {
        if (!root.isConnected) {
          return;
        }
        if (!root.contains(event.target)) {
          return;
        }
        if (event.key === "ArrowRight" && pageNumber < pdf.numPages) {
          event.preventDefault();
          pageNumber += 1;
          await renderPage();
        } else if (event.key === "ArrowLeft" && pageNumber > 1) {
          event.preventDefault();
          pageNumber -= 1;
          await renderPage();
        }
      });

      await renderPage();
    } catch (err) {
      loading.hidden = true;
      error.hidden = false;
      status.textContent = "0 / 0";
      console.error("Pulse Flipbook failed to load", err);
    }
  }

  function init() {
    const flipbooks = document.querySelectorAll(".pulse-flipbook");
    flipbooks.forEach((item) => setupFlipbook(item));
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
