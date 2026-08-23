{{--
    Sparepart searchable combobox.
    Enhances every <select class="sparepart-select"> into a type-to-search widget.
    The original <select> stays in the DOM (hidden) as the source of truth, so all
    existing logic that reads .sparepart-select.value / dispatches 'change' keeps working.
    Include once per page that has the sparepart selector.
--}}
<style>
.sp-combobox { position: relative; }
.sp-combobox .sp-search {
    padding-left: 34px !important;
    padding-right: 30px !important;
    background: #fff !important;
}
.sp-combobox .sp-icon {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: .82rem; pointer-events: none; z-index: 1;
}
.sp-combobox .sp-clear {
    position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; cursor: pointer; font-size: .9rem; padding: 2px 4px;
    display: none; z-index: 1; background: transparent; border: none;
}
.sp-combobox .sp-clear:hover { color: #ef4444; }
.sp-combobox.has-value .sp-clear { display: block; }
.sp-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
    box-shadow: 0 10px 28px rgba(15,23,42,.16); z-index: 999;
    max-height: 260px; overflow-y: auto; display: none;
}
.sp-dropdown.open { display: block; }
.sp-item {
    padding: 9px 12px; cursor: pointer; font-size: .82rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; justify-content: space-between; gap: 8px; align-items: center;
}
.sp-item:last-child { border-bottom: none; }
.sp-item:hover, .sp-item.active { background: #f0fdfa; }
.sp-item .sp-name { font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sp-item .sp-meta { font-size: .72rem; color: #64748b; white-space: nowrap; text-align: right; flex-shrink: 0; }
.sp-item .sp-price { color: var(--primary); font-weight: 700; }
.sp-item .sp-stok-out { color: #dc2626; font-weight: 700; }
.sp-empty { padding: 14px; text-align: center; font-size: .8rem; color: #94a3b8; }
</style>

<script>
(function () {
    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.sp-dropdown.open').forEach(function (d) { d.classList.remove('open'); });
        document.querySelectorAll('.sp-item.active').forEach(function (i) { i.classList.remove('active'); });
    }

    // Build searchable widget around a <select.sparepart-select>.
    function enhanceSparepart(selectEl) {
        if (!selectEl) return;
        if (selectEl.closest('.sp-combobox')) return; // already wrapped

        var options = Array.prototype.slice.call(selectEl.querySelectorAll('option'));
        var dataItems = options.filter(function (o) { return o.value !== ''; }).map(function (o) {
            return {
                value: o.value,
                nama: o.dataset.nama || o.textContent.replace(/\s*\(Stok:.*$/, '').trim(),
                harga: parseFloat(o.dataset.harga || 0) || 0,
                stok: parseInt(o.dataset.stok || 0, 10) || 0,
            };
        });

        // Build DOM
        var wrapper = document.createElement('div');
        wrapper.className = 'sp-combobox';
        selectEl.parentNode.insertBefore(wrapper, selectEl);
        wrapper.appendChild(selectEl);
        selectEl.style.display = 'none';

        var icon = document.createElement('i');
        icon.className = 'fas fa-search sp-icon';

        var search = document.createElement('input');
        search.type = 'text';
        search.className = 'form-input sp-search';
        search.placeholder = 'Ketik nama sparepart...';
        search.setAttribute('autocomplete', 'off');

        var clear = document.createElement('button');
        clear.type = 'button';
        clear.className = 'sp-clear';
        clear.innerHTML = '<i class="fas fa-times"></i>';
        clear.title = 'Hapus pilihan';

        var dropdown = document.createElement('div');
        dropdown.className = 'sp-dropdown';

        wrapper.appendChild(icon);
        wrapper.appendChild(search);
        wrapper.appendChild(clear);
        wrapper.appendChild(dropdown);

        function syncSearch() {
            var v = selectEl.value;
            var item = dataItems.filter(function (d) { return d.value === v; })[0];
            search.value = item ? item.nama : '';
            wrapper.classList.toggle('has-value', !!item);
        }

        function renderItems(filter) {
            var f = (filter || '').toLowerCase().trim();
            dropdown.innerHTML = '';
            var shown = 0;
            var firstItem = null;
            dataItems.forEach(function (it) {
                if (f && it.nama.toLowerCase().indexOf(f) === -1) return;
                var div = document.createElement('div');
                div.className = 'sp-item';
                div.dataset.value = it.value;
                var stokCls = it.stok > 0 ? '' : 'sp-stok-out';
                var stokTxt = it.stok > 0 ? ('Stok: ' + it.stok) : 'Habis';
                div.innerHTML =
                    '<span class="sp-name">' + escapeHtml(it.nama) + '</span>' +
                    '<span class="sp-meta">' + stokTxt + ' • <span class="' + stokCls + ' sp-price">Rp ' +
                    Number(it.harga).toLocaleString('id-ID') + '</span></span>';
                div.addEventListener('mousedown', function (e) {
                    e.preventDefault(); // keep focus on search
                    pick(it);
                });
                dropdown.appendChild(div);
                if (!firstItem) firstItem = div;
                shown++;
            });
            if (shown === 0) {
                dropdown.innerHTML = '<div class="sp-empty"><i class="fas fa-search" style="margin-right:4px;opacity:.5"></i>Sparepart "' + escapeHtml(filter) + '" tidak ditemukan</div>';
            }
            return firstItem;
        }

        function pick(it) {
            selectEl.value = it.value;
            search.value = it.nama;
            wrapper.classList.add('has-value');
            dropdown.classList.remove('open');
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function open() {
            closeAllDropdowns();
            renderItems(search.value);
            dropdown.classList.add('open');
        }
        function close() { dropdown.classList.remove('open'); }

        search.addEventListener('focus', open);
        search.addEventListener('input', function () {
            wrapper.classList.remove('has-value');
            if (search.value === '' && selectEl.value !== '') {
                selectEl.value = '';
                selectEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
            open();
        });
        search.addEventListener('keydown', function (e) {
            var items = dropdown.querySelectorAll('.sp-item');
            var cur = dropdown.querySelector('.sp-item.active');
            var idx = cur ? Array.prototype.indexOf.call(items, cur) : -1;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!dropdown.classList.contains('open')) open();
                if (items.length) {
                    if (cur) cur.classList.remove('active');
                    idx = Math.min(idx + 1, items.length - 1);
                    items[idx].classList.add('active');
                    items[idx].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length) {
                    if (cur) cur.classList.remove('active');
                    idx = Math.max(idx - 1, 0);
                    items[idx].classList.add('active');
                    items[idx].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'Enter') {
                if (dropdown.classList.contains('open')) {
                    e.preventDefault();
                    var target = cur || items[0];
                    if (target) {
                        var it = dataItems.filter(function (d) { return d.value === target.dataset.value; })[0];
                        if (it) pick(it);
                    }
                }
            } else if (e.key === 'Escape') {
                close();
                syncSearch();
            }
        });

        clear.addEventListener('click', function () {
            selectEl.value = '';
            search.value = '';
            wrapper.classList.remove('has-value');
            close();
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
            search.focus();
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) close();
        });

        syncSearch();
    }

    // Teardown widget → restore bare <select> (used before cloning a row).
    function teardownSparepart(selectEl) {
        var wrapper = selectEl.closest('.sp-combobox');
        if (!wrapper) return;
        var parent = wrapper.parentNode;
        parent.insertBefore(selectEl, wrapper);
        parent.removeChild(wrapper);
        selectEl.style.display = '';
    }

    // Expose
    window.enhanceSparepart = enhanceSparepart;
    window.teardownSparepart = teardownSparepart;
    window.enhanceAllSpareparts = function (root) {
        (root || document).querySelectorAll('.sparepart-select').forEach(function (s) {
            teardownSparepart(s); // ensure clean (idempotent)
            enhanceSparepart(s);
        });
    };

    // Initial enhancement once DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        var c = document.getElementById('sparepartContainer');
        if (c) window.enhanceAllSpareparts(c);
    });
})();
</script>
