import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import {
    createIcons,
    AlertTriangle, ArrowLeft, CalendarDays, Check, ChevronDown, ChevronUp, CircleCheck,
    GripVertical, Info, Keyboard, ListChecks, LogOut, Moon, MoreHorizontal, Pencil, Plus,
    Sun, Trash2, User, X, Clock, Flag, Kanban, Search, Mail, KeyRound, Eye, EyeOff,
    Menu, ListOrdered, ChevronLeft, ChevronRight, MessageSquare, Send, StickyNote,
} from 'lucide';

window.Alpine = Alpine;
window.Sortable = Sortable;

/* Uma família de ícones (Lucide), strokeWidth padronizado. <i data-lucide="check"> vira <svg>. */
const ICONS = {
    AlertTriangle, ArrowLeft, CalendarDays, Check, ChevronDown, ChevronUp, CircleCheck,
    GripVertical, Info, Keyboard, ListChecks, LogOut, Moon, MoreHorizontal, Pencil, Plus,
    Sun, Trash2, User, X, Clock, Flag, Kanban, Search, Mail, KeyRound, Eye, EyeOff,
    Menu, ListOrdered, ChevronLeft, ChevronRight, MessageSquare, Send, StickyNote,
};
window.vixIcons = () => createIcons({ icons: ICONS, attrs: { 'stroke-width': 2, 'aria-hidden': 'true' } });

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

/** POST JSON com CSRF. Retorna o JSON da resposta ou lança erro. */
window.vixPost = async (url, body = {}, method = 'POST') => {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'XMLHttpRequest',
    };
    // PUT/DELETE via override: o Laravel lê o header e trata como o método real.
    if (method !== 'POST') headers['X-HTTP-Method-Override'] = method;
    const res = await fetch(url, { method: 'POST', headers, body: JSON.stringify(body) });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
};

/** GET JSON. */
window.vixGet = async (url) => {
    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
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
        onEnd: async (evt) => {
            if (evt.oldIndex === evt.newIndex) return;
            const order = [...el.querySelectorAll(':scope > [data-id]')].map((n) => Number(n.dataset.id));
            try {
                await window.vixPost(url, { order });
                options.onSaved?.(order);
                Alpine.store('toast').add(options.savedMessage ?? 'Ordem salva.');
            } catch (e) {
                console.error(e);
                Alpine.store('toast').add('Não consegui salvar a nova ordem.', 'error', {
                    label: 'Recarregar', action: () => location.reload(),
                });
            }
        },
        ...options.sortable,
    });
};

/* Tema claro/escuro. A classe é aplicada antes do paint por um script inline no <head>. */
Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        try { localStorage.setItem('vix-theme', this.dark ? 'dark' : 'light'); } catch {}
    },
});

/* Toast: feedback imediato de toda ação. Erro de rede é acionável (botão), não some sozinho. */
Alpine.store('toast', {
    items: [],
    add(message, type = 'success', action = null) {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type, action });
        Alpine.nextTick(() => window.vixIcons());
        if (type !== 'error') setTimeout(() => this.remove(id), 3500);
    },
    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

/* Atalhos globais. Ignorados quando o foco está num campo de texto. */
document.addEventListener('keydown', (e) => {
    const tag = (e.target?.tagName ?? '').toLowerCase();
    if (['input', 'textarea', 'select'].includes(tag) || e.target?.isContentEditable) return;
    if (e.metaKey || e.ctrlKey || e.altKey) return;

    const target = document.querySelector(`[data-shortcut="${e.key.toLowerCase()}"]`);
    if (target) {
        e.preventDefault();
        target.click();
    }
});

document.addEventListener('alpine:initialized', () => window.vixIcons());
Alpine.start();
