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

Alpine.start();
