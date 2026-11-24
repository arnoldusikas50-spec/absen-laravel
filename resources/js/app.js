import Alpine from 'alpinejs';

/*
 * Interaksi backend dikelola state Alpine.js: sidebar (drawer mobile +
 * collapse desktop), menu pengguna, modal, tab, dropdown, dan collapse.
 *
 * API lama tetap tersedia — AdminUI.*, bootstrap.Modal, jQuery.modal(),
 * toggleSidebar(), closeSidebar(), toggleUserMenu() — karena puluhan
 * halaman Blade masih memanggilnya. Semua jalur itu kini menuju state
 * Alpine yang sama, jadi tidak ada dua sumber kebenaran.
 */

window.Alpine = Alpine;

const SIDEBAR_COLLAPSE_KEY = 'absen.sidebar.collapsed';
const desktopQuery = window.matchMedia('(min-width: 1024px)');

/* ════════════════════════════════════════════════════════════════════
   SIDEBAR
   ════════════════════════════════════════════════════════════════════ */
Alpine.store('shell', {
    open: false,
    collapsed: window.localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === '1',

    /** Mobile: buka/tutup drawer. Desktop: ciutkan/lebarkan sidebar. */
    toggle() {
        if (desktopQuery.matches) {
            this.setCollapsed(!this.collapsed);

            return;
        }

        this.open = !this.open;
    },

    setCollapsed(state) {
        this.collapsed = Boolean(state);
        window.localStorage.setItem(SIDEBAR_COLLAPSE_KEY, this.collapsed ? '1' : '0');
    },

    close() {
        this.open = false;
    },
});

/* ════════════════════════════════════════════════════════════════════
   MENU PENGGUNA
   ════════════════════════════════════════════════════════════════════ */
Alpine.store('userMenu', {
    open: false,

    toggle() {
        this.open = !this.open;
    },

    close() {
        this.open = false;
    },
});

/* ════════════════════════════════════════════════════════════════════
   MODAL
   ════════════════════════════════════════============================ */
const openModals = new Set();
const modalFocus = new Map();

const resolveElement = (target) => {
    if (!target) {
        return null;
    }

    if (target instanceof Element) {
        return target;
    }

    if (target.jquery) {
        return target.get(0);
    }

    if (typeof target === 'string') {
        const selector = target.trim();

        if (!selector) {
            return null;
        }

        return selector.startsWith('#')
            ? document.getElementById(selector.slice(1))
            : document.querySelector(selector);
    }

    return null;
};

const targetSelector = (trigger) =>
    trigger?.getAttribute('data-ui-target') ||
    trigger?.getAttribute('data-bs-target') ||
    trigger?.getAttribute('href');

const syncBodyScroll = () => {
    document.body.style.overflow = openModals.size > 0 ? 'hidden' : '';

    const modals = window.Alpine?.store('modals');

    if (modals) {
        modals.count = openModals.size;
    }
};

const showModal = (target) => {
    const modal = resolveElement(target);

    if (!modal) {
        return;
    }

    if (!openModals.has(modal)) {
        modalFocus.set(modal, document.activeElement);
        openModals.add(modal);
    }

    modal.classList.add('is-open', 'show');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    modal.setAttribute('role', modal.getAttribute('role') || 'dialog');
    modal.setAttribute('aria-modal', 'true');
    syncBodyScroll();
    modal.dispatchEvent(new CustomEvent('app:modal-shown', { bubbles: true }));
    window.jQuery?.(modal).trigger('shown');
};

const hideModal = (target) => {
    const modal = resolveElement(target);

    if (!modal) {
        return;
    }

    modal.classList.remove('is-open', 'show', 'active');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    openModals.delete(modal);
    syncBodyScroll();
    modal.dispatchEvent(new CustomEvent('app:modal-hidden', { bubbles: true }));
    window.jQuery?.(modal).trigger('hidden');

    const previous = modalFocus.get(modal);
    modalFocus.delete(modal);

    if (previous?.isConnected) {
        previous.focus({ preventScroll: true });
    }
};

const toggleModal = (target) => {
    const modal = resolveElement(target);

    if (!modal) {
        return;
    }

    if (openModals.has(modal)) {
        hideModal(modal);
    } else {
        showModal(modal);
    }
};

Alpine.store('modals', {
    count: 0,

    open(target) {
        showModal(target);
    },

    close(target) {
        hideModal(target);
    },

    toggle(target) {
        toggleModal(target);
    },
});

/* ════════════════════════════════════════════════════════════════════
   TAB / DROPDOWN / COLLAPSE / TOOLTIP (markup lama berbasis data-attribute)
   ════════════════════════════════════════════════════════════════════ */
const activateTab = (trigger) => {
    const target = targetSelector(trigger);

    if (!target) {
        return;
    }

    const pane = resolveElement(target);

    if (!pane) {
        return;
    }

    const tablist = trigger.closest(
        '[role="tablist"], .nav-tabs, .nav-pills, .nav-pills-tw, .card-tw-header ul',
    );

    if (tablist) {
        tablist
            .querySelectorAll(
                '[data-ui-toggle="tab"], [data-ui-toggle="pill"], [data-bs-toggle="tab"], [data-bs-toggle="pill"]',
            )
            .forEach((button) => {
                button.classList.remove('active');
                button.setAttribute('aria-selected', 'false');
                button.setAttribute('tabindex', '-1');
            });
    }

    trigger.classList.add('active');
    trigger.setAttribute('aria-selected', 'true');
    trigger.setAttribute('tabindex', '0');

    const tabContent = pane.closest('.tab-content, .tab-content-tw');

    if (tabContent) {
        tabContent.querySelectorAll('.tab-pane, .tab-pane-tw').forEach((tabPane) => {
            tabPane.classList.remove('show', 'active');
            tabPane.removeAttribute('tabindex');
        });
    }

    pane.classList.add('show', 'active');
};

const closeDropdowns = (except = null) => {
    document.querySelectorAll('.dropdown-menu.show').forEach((menu) => {
        if (menu !== except) {
            menu.classList.remove('show');
        }
    });
};

const toggleDropdown = (trigger) => {
    const menu =
        trigger.nextElementSibling || trigger.parentElement?.querySelector('.dropdown-menu');

    if (!menu) {
        return;
    }

    const willOpen = !menu.classList.contains('show');

    closeDropdowns(menu);
    menu.classList.toggle('show', willOpen);

    const expanded = willOpen ? 'true' : 'false';

    trigger.setAttribute('aria-expanded', expanded);

    if (menu.id) {
        trigger.setAttribute('aria-controls', menu.id);
    }
};

const toggleCollapse = (trigger) => {
    const target = targetSelector(trigger);

    if (!target) {
        return;
    }

    const panel = resolveElement(target);

    if (!panel) {
        return;
    }

    panel.classList.toggle('show');

    const expanded = panel.classList.contains('show');
    trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    panel.setAttribute('aria-hidden', expanded ? 'false' : 'true');

    const icon = trigger.querySelector('.bi-chevron-down, .bi-chevron-up');

    if (icon) {
        icon.classList.toggle('bi-chevron-down', !expanded);
        icon.classList.toggle('bi-chevron-up', expanded);
    }
};

const attachTooltip = (element) => {
    if (!element) {
        return;
    }

    const title = element.getAttribute('title');

    if (title && !element.getAttribute('aria-label')) {
        element.setAttribute('aria-label', title);
    }
};

const initTooltips = (scope) => {
    (scope || document)
        .querySelectorAll('[data-ui-toggle="tooltip"], [data-bs-toggle="tooltip"]')
        .forEach(attachTooltip);
};

/* ════════════════════════════════════════════════════════════════════
   EVENT DELEGATION
   ════════════════════════════════════════════════════════════════════ */
document.addEventListener('click', (event) => {
    const modalTrigger = event.target.closest(
        '[data-ui-toggle="modal"], [data-bs-toggle="modal"]',
    );

    if (modalTrigger) {
        event.preventDefault();
        toggleModal(targetSelector(modalTrigger));

        return;
    }

    const dismissTrigger = event.target.closest(
        '[data-ui-dismiss="modal"], [data-bs-dismiss="modal"]',
    );

    if (dismissTrigger) {
        event.preventDefault();

        const modal = dismissTrigger.closest('.modal');

        if (modal) {
            hideModal(modal);
        }

        return;
    }

    const tabTrigger = event.target.closest(
        '[data-ui-toggle="tab"], [data-ui-toggle="pill"], [data-bs-toggle="tab"], [data-bs-toggle="pill"]',
    );

    if (tabTrigger) {
        event.preventDefault();
        activateTab(tabTrigger);

        return;
    }

    const dropdownTrigger = event.target.closest(
        '[data-ui-toggle="dropdown"], [data-bs-toggle="dropdown"]',
    );

    if (dropdownTrigger) {
        event.preventDefault();
        event.stopPropagation();
        toggleDropdown(dropdownTrigger);

        return;
    }

    const collapseTrigger = event.target.closest(
        '[data-ui-toggle="collapse"], [data-bs-toggle="collapse"]',
    );

    if (collapseTrigger) {
        event.preventDefault();
        toggleCollapse(collapseTrigger);

        return;
    }

    closeDropdowns();

    const clickedModal = event.target.closest('.modal');

    if (clickedModal && event.target === clickedModal) {
        const isStatic =
            clickedModal.getAttribute('data-ui-backdrop') === 'static' ||
            clickedModal.getAttribute('data-bs-backdrop') === 'static';

        if (!isStatic) {
            hideModal(clickedModal);
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    if (openModals.size > 0) {
        const stack = Array.from(openModals);
        hideModal(stack[stack.length - 1]);

        return;
    }

    closeDropdowns();

    window.Alpine?.store('userMenu')?.close();
    window.Alpine?.store('shell')?.close();
});

/* ════════════════════════════════════════════════════════════════════
   KOMPONEN ALPINE
   ════════════════════════════════════════════════════════════════════ */
Alpine.data('adminShell', () => ({
    init() {
        this.$watch(
            () => this.$store.shell.open,
            (open) => document.body.classList.toggle('shell-drawer-open', Boolean(open)),
        );

        desktopQuery.addEventListener('change', (event) => {
            if (event.matches) {
                this.$store.shell.close();
            }
        });
    },
}));

/* ════════════════════════════════════════════════════════════════════
   TABEL DATA MASTER — cari, filter, urut, dan paginasi tanpa
   jQuery/DataTables. Baris tetap dirender Blade; komponen ini hanya
   menyembunyikan, mengurutkan ulang, dan menomori ulang <tr>.

   x-data="dataTable({ perPage: 10, sort: 1 })"
   - input pencarian        : x-model="query"
   - filter per kolom       : x-model="filters.status" (cocok dengan
                              data-status pada <tr>; nilai __empty__ /
                              __filled__ mencocokkan atribut kosong/terisi)
   - header yang bisa urut  : @click="sortBy(index)"
   - nomor urut             : beri data-rownum pada sel nomor
   ════════════════════════════════════════════════════════════════════ */
Alpine.data('dataTable', (options = {}) => {
    /* Baris tabel adalah node DOM — disimpan di closure, bukan di state
       reaktif, supaya Alpine tidak memproksikan elemen DOM. */
    const state = { body: null, rows: [], visible: [] };
    const searchText = new WeakMap();

    return {
        perPage: Number(options.perPage) || 10,
        page: 1,
        query: '',
        filters: { ...(options.filters || {}) },
        sortKey: Object.prototype.hasOwnProperty.call(options, 'sort') ? options.sort : null,
        sortDir: options.dir === 'desc' ? 'desc' : 'asc',
        total: 0,
        pages: 1,
        from: 0,
        to: 0,

        init() {
            state.body = this.$root.querySelector('tbody');
            state.rows = state.body ? Array.from(state.body.rows) : [];

            state.rows.forEach((row) => {
                searchText.set(
                    row,
                    (row.dataset.search || row.textContent || '')
                        .replace(/\s+/g, ' ')
                        .trim()
                        .toLowerCase(),
                );
            });

            if (this.sortKey !== null) {
                this.applySort();
            }

            this.refresh();

            this.$watch('query', () => {
                this.page = 1;
                this.refresh();
            });

            this.$watch('filters', () => {
                this.page = 1;
                this.refresh();
            }, { deep: true });
        },

        matches(row) {
        const query = this.query.trim().toLowerCase();

        if (query !== '' && !(searchText.get(row) || '').includes(query)) {
            return false;
        }

        return Object.keys(this.filters).every((key) => {
            const expected = this.filters[key];

            if (expected === null || expected === undefined || expected === '') {
                return true;
            }

            const actual = (row.dataset[key] || '').trim().toLowerCase();

            if (expected === '__empty__') {
                return actual === '';
            }

            if (expected === '__filled__') {
                return actual !== '';
            }

            return actual === String(expected).trim().toLowerCase();
        });
    },

        refresh() {
            state.visible = state.rows.filter((row) => this.matches(row));

            this.total = state.visible.length;
            this.pages = Math.max(1, Math.ceil(this.total / this.perPage));

            if (this.page > this.pages) {
                this.page = this.pages;
            }

            const start = (this.page - 1) * this.perPage;
            const shown = state.visible.slice(start, start + this.perPage);

            state.rows.forEach((row) => row.classList.add('hidden'));

            shown.forEach((row, index) => {
                row.classList.remove('hidden');

                const numbering = row.querySelector('[data-rownum]');

                if (numbering) {
                    numbering.textContent = start + index + 1;
                }
            });

            this.from = shown.length ? start + 1 : 0;
            this.to = start + shown.length;
        },

        applySort() {
            const column = Number(this.sortKey);

            if (Number.isNaN(column) || !state.rows.length || !state.body) {
                return;
            }

            const direction = this.sortDir === 'desc' ? -1 : 1;
            const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
            const value = (row) => {
                const cell = row.cells[column];

                if (!cell) {
                    return '';
                }

                return (cell.dataset.order ?? cell.textContent ?? '').trim();
            };

            state.rows.sort((a, b) => collator.compare(value(a), value(b)) * direction);

            state.rows.forEach((row) => state.body.appendChild(row));
        },

        sortBy(column) {
            if (this.sortKey === column) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = column;
                this.sortDir = 'asc';
            }

            this.applySort();
            this.page = 1;
            this.refresh();
        },

        indicator(column) {
            if (this.sortKey !== column) {
                return '';
            }

            return this.sortDir === 'asc' ? '↑' : '↓';
        },

        goto(target) {
            this.page = Math.min(Math.max(1, target), this.pages);
            this.refresh();
        },

        prev() {
            this.goto(this.page - 1);
        },

        next() {
            this.goto(this.page + 1);
        },
    };
});

/* ════════════════════════════════════════════════════════════════════
   JEMBATAN API LAMA
   ════════════════════════════════════════════════════════════════════ */
window.AdminUI = {
    modal: {
        open: showModal,
        close: hideModal,
        toggle: toggleModal,
    },
    tabs: {
        activate: activateTab,
    },
    dropdown: {
        toggle: toggleDropdown,
        closeAll: closeDropdowns,
    },
    collapse: {
        toggle: toggleCollapse,
    },
    tooltip: {
        attach: attachTooltip,
        initAll: initTooltips,
    },
};

window.toggleSidebar = () => window.Alpine.store('shell').toggle();
window.closeSidebar = () => window.Alpine.store('shell').close();
window.toggleUserMenu = () => window.Alpine.store('userMenu').toggle();

/* Bootstrap 5 API compatibility shim. */
window.bootstrap = window.bootstrap || {};
window.bootstrap.Modal = function (element) {
    this.el = resolveElement(element);
};
window.bootstrap.Modal.prototype.show = function () {
    showModal(this.el);
};
window.bootstrap.Modal.prototype.hide = function () {
    hideModal(this.el);
};
window.bootstrap.Modal.prototype.toggle = function () {
    toggleModal(this.el);
};
window.bootstrap.Modal.getInstance = function (element) {
    return new window.bootstrap.Modal(element);
};
window.bootstrap.Modal.getOrCreateInstance = function (element) {
    return new window.bootstrap.Modal(element);
};

if (window.jQuery) {
    window.jQuery.fn.modal = function (action) {
        // function() biasa (bukan arrow) agar `this` = elemen DOM di each().
        return this.each(function () {
            if (action === 'show') {
                showModal(this);
            } else if (action === 'toggle') {
                toggleModal(this);
            } else {
                hideModal(this);
            }
        });
    };

    if (!window.jQuery.fn.appModal) {
        window.jQuery.fn.appModal = window.jQuery.fn.modal;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.modal').forEach((modal) => {
        const isOpen = ['is-open', 'show', 'active'].some((state) =>
            modal.classList.contains(state),
        );

        if (!isOpen) {
            modal.style.display = 'none';
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
    });

    initTooltips(document);
});

Alpine.start();
