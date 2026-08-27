/**
 * Hover layer for the server-rendered SVG trend chart.
 *
 * The <svg> is drawn by Blade; this only adds a crosshair + tooltip.
 * Expects:
 *   <figure data-trend-chart data-series='[{name,color,points:[{x,y,date,value}]}]'
 *           data-plot='{x0,x1,y0,y1}'>
 *     <svg>…</svg>
 *     <div data-trend-tooltip hidden></div>
 *   </figure>
 * CSP-safe: bundled module, one delegated pointer listener per chart.
 */
function initTrendChart(figure) {
    if (figure.dataset.trendInit) return;
    figure.dataset.trendInit = '1';

    const svg = figure.querySelector('svg');
    const tip = figure.querySelector('[data-trend-tooltip]');
    if (!svg || !tip) return;

    let series;
    let plot;
    try {
        series = JSON.parse(figure.dataset.series || '[]');
        plot = JSON.parse(figure.dataset.plot || '{}');
    } catch {
        return;
    }
    if (!series.length || !series[0].points?.length) return;

    const ns = 'http://www.w3.org/2000/svg';
    const crosshair = document.createElementNS(ns, 'line');
    crosshair.setAttribute('stroke', 'currentColor');
    crosshair.setAttribute('stroke-width', '1');
    crosshair.setAttribute('opacity', '0.25');
    crosshair.setAttribute('y1', plot.y1);
    crosshair.setAttribute('y2', plot.y0);
    crosshair.setAttribute('visibility', 'hidden');
    svg.appendChild(crosshair);

    const dots = series.map((s) => {
        const c = document.createElementNS(ns, 'circle');
        c.setAttribute('r', '3.5');
        c.setAttribute('fill', s.color);
        c.setAttribute('stroke', 'var(--chart-surface, #fff)');
        c.setAttribute('stroke-width', '1.5');
        c.setAttribute('visibility', 'hidden');
        svg.appendChild(c);
        return c;
    });

    const points = series[0].points;
    const rect = () => svg.getBoundingClientRect();

    function nearest(clientX) {
        const box = rect();
        const ratio = (clientX - box.left) / box.width;
        const px = plot.x0 + ratio * (plot.x1 - plot.x0);
        let best = 0;
        let bestD = Infinity;
        points.forEach((p, i) => {
            const d = Math.abs(p.x - px);
            if (d < bestD) { bestD = d; best = i; }
        });
        return best;
    }

    function move(evt) {
        const i = nearest(evt.clientX);
        const x = points[i].x;
        crosshair.setAttribute('x1', x);
        crosshair.setAttribute('x2', x);
        crosshair.setAttribute('visibility', 'visible');

        const box = rect();
        const scaleX = box.width / (svg.viewBox.baseVal.width || box.width);

        series.forEach((s, si) => {
            const p = s.points[i];
            if (!p) return;
            dots[si].setAttribute('cx', p.x);
            dots[si].setAttribute('cy', p.y);
            dots[si].setAttribute('visibility', 'visible');
        });

        const rows = series
            .map((s) => `<span class="inline-block w-2 h-2 rounded-full mr-1" style="background:${s.color}"></span>${s.name}: <strong>${s.points[i]?.value ?? 0}</strong>`)
            .join('<br>');
        tip.innerHTML = `<div class="font-medium mb-0.5">${points[i].date}</div>${rows}`;
        tip.hidden = false;
        tip.style.left = `${Math.min(box.width - tip.offsetWidth - 8, x * scaleX + 8)}px`;
        tip.style.top = '8px';
    }

    function leave() {
        crosshair.setAttribute('visibility', 'hidden');
        dots.forEach((d) => d.setAttribute('visibility', 'hidden'));
        tip.hidden = true;
    }

    svg.addEventListener('pointermove', move);
    svg.addEventListener('pointerleave', leave);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-trend-chart]').forEach(initTrendChart);
});
