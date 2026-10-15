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

Alpine.data('notificationBell', (config) => ({
    open: false,
    notifications: config.notifications,
    unreadCount: config.unreadCount,
    knownIds: new Set(config.notifications.map((notification) => notification.id)),
    audioContext: null,
    refreshTimer: null,
    visibilityHandler: null,
    refreshing: false,

    init() {
        const unlockSound = async () => {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (! AudioContext) {
                return;
            }

            this.audioContext ??= new AudioContext();
            if (this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }
        };

        window.addEventListener('pointerdown', unlockSound, { once: true });
        window.addEventListener('keydown', unlockSound, { once: true });
        this.visibilityHandler = () => {
            if (document.visibilityState === 'visible') {
                this.refresh();
            }
        };
        document.addEventListener('visibilitychange', this.visibilityHandler);
        this.refreshTimer = window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                this.refresh();
            }
        }, 30000);
    },

    destroy() {
        window.clearInterval(this.refreshTimer);
        document.removeEventListener('visibilitychange', this.visibilityHandler);
        this.audioContext?.close();
    },

    async refresh() {
        if (this.refreshing || document.visibilityState === 'hidden') {
            return;
        }

        this.refreshing = true;
        try {
            const { data } = await window.axios.get(config.indexUrl);
            const hasNewNotification = data.notifications.some((notification) => ! this.knownIds.has(notification.id));
            data.notifications.forEach((notification) => this.knownIds.add(notification.id));
            this.notifications = data.notifications;
            this.unreadCount = data.unread_count;
            if (hasNewNotification) {
                this.playSound();
            }
        } catch (error) {
            console.error('Impossible de rafraîchir les notifications.', error);
        } finally {
            this.refreshing = false;
        }
    },

    playSound() {
        if (! this.audioContext || this.audioContext.state !== 'running') {
            return;
        }

        const oscillator = this.audioContext.createOscillator();
        const gain = this.audioContext.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, this.audioContext.currentTime);
        gain.gain.setValueAtTime(0.12, this.audioContext.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + 0.22);
        oscillator.connect(gain);
        gain.connect(this.audioContext.destination);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.22);
    },

    async openNotification(notification) {
        try {
            const url = config.readUrl.replace('__ID__', encodeURIComponent(notification.id));
            const { data } = await window.axios.post(url);
            window.location.assign(data.url || notification.url);
        } catch (error) {
            console.error('Impossible de marquer la notification comme lue.', error);
        }
    },

    async markAllRead() {
        try {
            await window.axios.post(config.readAllUrl);
            this.notifications = [];
            this.unreadCount = 0;
            this.open = false;
        } catch (error) {
            console.error('Impossible de marquer les notifications comme lues.', error);
        }
    },
}));

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
