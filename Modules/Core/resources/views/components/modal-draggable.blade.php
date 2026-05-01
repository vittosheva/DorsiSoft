<script>
    (function () {
        const DRAG_ATTR = 'data-draggable-installed';

        function makeDraggable(modal) {
            if (!modal || modal.hasAttribute('data-draggable-installed')) return;

            // Detectar y excluir slideovers de múltiples formas
            const wireKey = modal.getAttribute('wire:key') || '';
            const slideoverClass = modal.classList.contains('fi-modal-slide-over-panel');
            const slideoverAttr = modal.hasAttribute('data-slide-over');
            const parentIsSlideOver = modal.closest('.fi-modal-slide-over');

            // Si es un slideover, no aplicar dragging
            if (wireKey.includes('.slideover.') || slideoverClass || slideoverAttr || parentIsSlideOver) {
                return;
            }

            const header = modal.querySelector('.fi-modal-header') || modal.querySelector('[role="dialog"] > header');
            if (!header) return;

            // Marcar el modal como draggable (esto activará los estilos CSS)
            modal.setAttribute(DRAG_ATTR, '1');
            header.setAttribute(DRAG_ATTR, '1');

            let startX = 0;
            let startY = 0;
            let offsetX = 0;
            let offsetY = 0;
            let dragging = false;
            let prevUserSelect = '';

            const onPointerMove = (e) => {
                if (!dragging) return;
                modal.style.left = (e.clientX - offsetX) + 'px';
                modal.style.top = (e.clientY - offsetY) + 'px';
            };

            const onPointerUp = (e) => {
                if (!dragging) return;
                dragging = false;
                document.removeEventListener('pointermove', onPointerMove);
                document.removeEventListener('pointerup', onPointerUp);
                // restore selection
                document.documentElement.style.userSelect = prevUserSelect || '';
                header.releasePointerCapture?.(e.pointerId);
            };

            const onPointerDown = (e) => {
                // solo boton izquierdo / touch
                if (e.button !== undefined && e.button !== 0) return;

                // evita arrastrar si el click fue sobre un input dentro del header
                const interactive = e.target.closest('button, a, input, textarea, select, label');
                if (interactive) return;

                const rect = modal.getBoundingClientRect();

                // fijamos en viewport (fixed) para evitar offsets relacionados con scroll/ancestros
                modal.style.position = 'fixed';
                modal.style.margin = 0;
                modal.style.left = rect.left + 'px';
                modal.style.top = rect.top + 'px';
                modal.style.width = rect.width + 'px'; // mantener ancho
                modal.style.transform = 'none';
                modal.style.transition = 'none';
                modal.style.zIndex = 9999;
                modal.style.willChange = 'left, top';

                dragging = true;
                startX = e.clientX;
                startY = e.clientY;
                offsetX = startX - rect.left;
                offsetY = startY - rect.top;

                // previene selección de texto al arrastrar
                prevUserSelect = document.documentElement.style.userSelect;
                document.documentElement.style.userSelect = 'none';

                header.setPointerCapture?.(e.pointerId);
                document.addEventListener('pointermove', onPointerMove);
                document.addEventListener('pointerup', onPointerUp);
                e.preventDefault();
            };

            header.addEventListener('pointerdown', onPointerDown);
        }

        // Instala para todos los modales ya existentes
        function installExisting() {
            document.querySelectorAll('.fi-modal-window').forEach(makeDraggable);
        }

        // Observador para cuando Filament inserte/actualice modales (Livewire)
        const mo = new MutationObserver((mutations) => {
            for (const m of mutations) {
                if (m.addedNodes && m.addedNodes.length) {
                    for (const node of m.addedNodes) {
                        if (!(node instanceof HTMLElement)) continue;
                        // si agregaron el overlay o el modal
                        if (node.matches && node.matches('.fi-modal-window')) {
                            makeDraggable(node);
                        } else {
                            // buscar descendientes
                            node.querySelectorAll?.('.fi-modal-window')?.forEach(makeDraggable);
                        }
                    }
                }
            }
        });

        // Arrancar cuando DOM listo
        document.addEventListener('DOMContentLoaded', () => {
            installExisting();
            mo.observe(document.body, { childList: true, subtree: true });
        });

        // Fallback: también intentar al load inmediato (en caso de ya estar montado)
        installExisting();
    })();
</script>
