import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/inter/800.css';

window.Alpine = Alpine;
Alpine.plugin(focus);
Alpine.start();

// Chargé à la demande uniquement quand un justificatif PDF est prévisualisé.
window.loadPdfPreview = () => import('./pdf-preview.js');

// Le composant Vue existant n'est monté que sur les pages qui fournissent sa racine.
const root = document.querySelector('#app');
if (root) {
    Promise.all([import('vue'), import('./components/Dashboard.vue'), import('@fortawesome/fontawesome-free/css/all.min.css')]).then(([{ createApp }, { default: Dashboard }]) => {
        createApp(Dashboard).mount(root);
    });
}
