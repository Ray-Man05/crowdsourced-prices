import './bootstrap';

// import Alpine from 'alpinejs';

// window.Alpine = Alpine;

// Alpine.start();

import Chart from 'chart.js/auto';
window.Chart = Chart;

window.toggleTheme = function () {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    const next = isDark ? 'light' : 'dark';

    html.classList.toggle('dark');

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) { console.error('toggleTheme: CSRF token not found'); return; }

    fetch(`/preferences/theme/${next}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token },
    })
    .then(r => { if (!r.ok) console.error(`toggleTheme: server responded ${r.status}`); })
    .catch(e => { console.error('toggleTheme: fetch failed', e); });
};

function getThemeColor(variable) {
    return getComputedStyle(document.documentElement)
        .getPropertyValue(variable)
        .trim();
}