import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

GlobalWorkerOptions.workerSrc = workerUrl;

export async function openPdf(url) {
    return getDocument({ url, withCredentials: true }).promise;
}

export async function renderPdfPage(document, pageNumber, canvas) {
    const page = await document.getPage(pageNumber);
    const naturalViewport = page.getViewport({ scale: 1 });
    const availableWidth = Math.max(1, canvas.parentElement.clientWidth - 24);
    const scale = Math.min(2, availableWidth / naturalViewport.width);
    const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
    const viewport = page.getViewport({ scale: scale * pixelRatio });

    canvas.width = Math.floor(viewport.width);
    canvas.height = Math.floor(viewport.height);
    canvas.style.width = `${Math.floor(viewport.width / pixelRatio)}px`;
    canvas.style.height = `${Math.floor(viewport.height / pixelRatio)}px`;

    await page.render({
        canvas,
        canvasContext: canvas.getContext('2d'),
        viewport,
    }).promise;
}
