/**
 * Filament panel global JS enhancements.
 *
 * Feature 4: Drag-to-scroll for Filament table horizontal scrollbar.
 * Feature 5: Shake animation on disabled button click.
 */

// ─── Feature 4: Drag-to-scroll ───────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', initTableScrollDrag);
document.addEventListener('livewire:navigated', initTableScrollDrag);

function initTableScrollDrag() {
    document.querySelectorAll('.fi-ta-content').forEach(attachDragScroll);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((m) => {
            m.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) return;
                node.querySelectorAll?.('.fi-ta-content').forEach(attachDragScroll);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

function attachDragScroll(el) {
    if (el.dataset.dragScrollInit) return;
    el.dataset.dragScrollInit = '1';

    let isDown = false;
    let startX;
    let scrollLeft;

    el.addEventListener('mousedown', (e) => {
        if (e.target.closest('button, a, input, select, [role="button"]')) return;
        isDown = true;
        el.style.cursor = 'grabbing';
        startX = e.pageX - el.offsetLeft;
        scrollLeft = el.scrollLeft;
    });

    document.addEventListener('mouseup', () => {
        if (!isDown) return;
        isDown = false;
        el.style.cursor = '';
    });

    el.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        el.scrollLeft = scrollLeft - (e.pageX - el.offsetLeft - startX);
    });

    el.addEventListener('mouseleave', () => {
        if (!isDown) return;
        isDown = false;
        el.style.cursor = '';
    });
}

// ─── Feature 5: Shake on disabled button click ───────────────────────────────
// Filament applies pointer-events:none to disabled buttons without tooltips,
// which suppresses all mouse events. We override that in CSS (see theme.css)
// and use 'mousedown' for immediate feedback (fires before 'click').

document.addEventListener(
    'mousedown',
    (e) => {
        if (e.button !== 0) return; // left-click only

        const btn = e.target.closest('[disabled], [aria-disabled="true"], .fi-disabled, [data-disabled="true"]');

        if (!btn) return;

        btn.classList.remove('fi-btn-shake');
        // Force reflow so animation restarts if button is clicked rapidly.
        void btn.offsetWidth;
        btn.classList.add('fi-btn-shake');

        btn.addEventListener('animationend', () => btn.classList.remove('fi-btn-shake'), {
            once: true,
        });
    },
    true,
);
