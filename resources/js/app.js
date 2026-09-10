import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Alpine = Alpine;
window.Sortable = Sortable;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

/** POST JSON com CSRF. Retorna o JSON da resposta ou lança erro. */
window.vixPost = async (url, body = {}) => {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
};

/** Liga o drag-and-drop de uma lista e envia a nova ordem para a URL. */
window.vixSortable = (el, url, options = {}) => {
    if (!el) return null;
    return Sortable.create(el, {
        animation: 150,
        handle: options.handle ?? '[data-handle]',
        ghostClass: 'vix-ghost',
        dragClass: 'vix-drag',
        onEnd: async () => {
            const order = [...el.querySelectorAll('[data-id]')].map((n) => Number(n.dataset.id));
            try {
                await window.vixPost(url, { order });
                options.onSaved?.(order);
            } catch (e) {
                console.error(e);
                alert('Não consegui salvar a nova ordem. Recarregue a página.');
            }
        },
        ...options.sortable,
    });
};

Alpine.start();
