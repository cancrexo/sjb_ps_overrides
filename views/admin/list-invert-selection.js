/**
 * HelperList SJB (clientes / direcciones):
 * - Clona «Bulk actions» arriba del listado
 * - Añade «Invertir selección» en ambos menús
 */
document.addEventListener('DOMContentLoaded', () => {
    const label = (typeof sjbListInvertSelection !== 'undefined' && sjbListInvertSelection.label)
        ? sjbListInvertSelection.label
        : 'Invertir selección';

    // 1. Clonar el menú de acciones agrupadas arriba de la tabla
    document.querySelectorAll('.panel .bulk-actions.dropup').forEach((bulk) => {
        if (bulk.classList.contains('sjb-bulk-actions-top')) {
            return;
        }

        const panel = bulk.closest('.panel');
        if (!panel || panel.querySelector('.sjb-bulk-actions-top')) {
            return;
        }

        const tableWrap = panel.querySelector('.table-responsive-row');
        if (!tableWrap) {
            return;
        }

        const cloneWrap = document.createElement('div');
        cloneWrap.className = 'row sjb-bulk-actions-top-row';
        cloneWrap.style.marginBottom = '10px';

        const col = document.createElement('div');
        col.className = 'col-lg-6';

        const clone = bulk.cloneNode(true);
        clone.classList.remove('dropup');
        clone.classList.add('dropdown', 'sjb-bulk-actions-top');

        const btn = clone.querySelector('[id^="bulk_action_menu_"]');
        if (btn && btn.id) {
            btn.id = btn.id + '_top';
        }

        col.appendChild(clone);
        cloneWrap.appendChild(col);
        tableWrap.parentNode.insertBefore(cloneWrap, tableWrap);
    });

    // 2. Invertir selección en todos los menús (original + clon)
    document.querySelectorAll('.bulk-actions .dropdown-menu').forEach((menu) => {
        if (menu.querySelector('.sjb-invert-selection')) {
            return;
        }

        const li = document.createElement('li');
        li.className = 'sjb-invert-selection';

        const a = document.createElement('a');
        a.href = '#';
        a.innerHTML = '<i class="icon-random"></i>&nbsp;' + label;
        a.addEventListener('click', (event) => {
            event.preventDefault();
            const form = a.closest('form');
            if (!form) {
                return;
            }
            form.querySelectorAll('input[type="checkbox"][name$="Box[]"]').forEach((cb) => {
                cb.checked = !cb.checked;
            });
        });

        li.appendChild(a);

        const divider = menu.querySelector('li.divider');
        if (divider) {
            menu.insertBefore(li, divider);
        } else {
            menu.insertBefore(li, menu.firstChild);
        }
    });
});
