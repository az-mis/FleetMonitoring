import Alpine from 'alpinejs';
import {
    Chart,
    ArcElement,
    BarElement,
    LineElement,
    PointElement,
    DoughnutController,
    BarController,
    LineController,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    ArcElement,
    BarElement,
    LineElement,
    PointElement,
    DoughnutController,
    BarController,
    LineController,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
);

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.data('notificationBellComp', (allNotifIds) => ({
    open: false,
    activeOpt: null,
    readNotifs: JSON.parse(localStorage.getItem('fleet_read_notifs') || '[]'),
    allNotifIds: allNotifIds || [],
    isRead(id) {
        return this.readNotifs.includes(id);
    },
    markRead(id) {
        if (!this.readNotifs.includes(id)) {
            this.readNotifs.push(id);
            localStorage.setItem('fleet_read_notifs', JSON.stringify(this.readNotifs));
        }
    },
    markUnread(id) {
        this.readNotifs = this.readNotifs.filter(i => i !== id);
        localStorage.setItem('fleet_read_notifs', JSON.stringify(this.readNotifs));
    },
    toggleRead(id) {
        if (this.isRead(id)) {
            this.markUnread(id);
        } else {
            this.markRead(id);
        }
        this.activeOpt = null;
    },
    markAllAsRead() {
        this.readNotifs = Array.from(new Set([...this.readNotifs, ...this.allNotifIds]));
        localStorage.setItem('fleet_read_notifs', JSON.stringify(this.readNotifs));
    },
    get unreadCount() {
        return this.allNotifIds.filter(id => !this.readNotifs.includes(id)).length;
    }
}));

Alpine.start();
