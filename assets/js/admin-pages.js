(function () {
    // Usado nas paginas admin do plugin via class-admin-menus.php.
    // Ativa apenas os comportamentos da tela atual.
    var data = window.tpsAdminData || {};

    // Usado na montagem das tabelas AJAX de clientes, itens e documentos.
    function esc(v) {
        return String(v || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function getNodes(scope, selector) {
        if (!scope) {
            return [];
        }

        if (scope.matches && scope.matches(selector)) {
            return [scope];
        }

        if (!scope.querySelectorAll) {
            return [];
        }

        return Array.from(scope.querySelectorAll(selector));
    }

    function upgradeSubmitInputs(scope) {
        getNodes(scope, '.submit input[type="submit"]').forEach(function (input) {
            if (!input.parentNode || input.dataset.tpsButtonized === '1') {
                return;
            }

            var button = document.createElement('button');
            Array.from(input.attributes).forEach(function (attribute) {
                if ('type' === attribute.name || 'value' === attribute.name) {
                    return;
                }

                button.setAttribute(attribute.name, attribute.value);
            });

            button.type = 'submit';
            button.textContent = input.value || input.getAttribute('value') || '';
            button.dataset.tpsButtonized = '1';
            input.parentNode.replaceChild(button, input);
        });
    }

    function enhanceSharedUi(scope) {
        upgradeSubmitInputs(scope);

        getNodes(scope, '.submit').forEach(function (wrapper) {
            wrapper.classList.add('tps-submit');
        });
    }

    function initUiEnhancements() {
        var root = document.querySelector('.tps-frontend-app') || document.body;
        if (!root) {
            return;
        }

        enhanceSharedUi(root);

        if (window.MutationObserver) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node && node.nodeType === 1) {
                            enhanceSharedUi(node);
                        }
                    });
                });
            });

            observer.observe(root, { childList: true, subtree: true });
        }
    }

    function initMobileShell() {
        var app = document.querySelector('.tps-frontend-app');
        var toggle = document.querySelector('.tps-app-menu-toggle');
        var sidebar = document.querySelector('.tps-app-sidebar');
        var backdrop = document.querySelector('.tps-app-sidebar-backdrop');

        if (!app || !toggle || !sidebar || !backdrop) {
            return;
        }

        function setSidebarState(isOpen) {
            app.classList.toggle('is-sidebar-open', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            backdrop.hidden = !isOpen;
            document.body.classList.toggle('tps-sidebar-open', isOpen);
        }

        function closeSidebar() {
            setSidebarState(false);
        }

        toggle.addEventListener('click', function () {
            setSidebarState(!app.classList.contains('is-sidebar-open'));
        });

        backdrop.addEventListener('click', closeSidebar);

        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 900) {
                    closeSidebar();
                }
            });
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) {
                closeSidebar();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && app.classList.contains('is-sidebar-open')) {
                closeSidebar();
            }
        });
    }

    // Usado nas telas com cabecalho moderno para encaixar notices personalizados.
    function initNotices() {
        if (!data.noticeActive) {
            return;
        }

        function dismissNotice(notice) {
            if (!notice) {
                return;
            }

            var parent = notice.parentNode;
            notice.remove();

            if (parent && parent.classList && parent.classList.contains('tps-app-notice-bar') && !parent.querySelector('.tps-top-notice')) {
                parent.remove();
            }

            document.querySelectorAll('.tps-app-content.has-top-notice').forEach(function (content) {
                content.classList.remove('has-top-notice');
            });
        }

        function placeNoticeAboveModuleHeader() {
            var notice = document.querySelector('.tps-top-notice');
            var content = document.getElementById('wpbody-content');
            var wrap = content ? content.querySelector('.wrap') : document.querySelector('.tps-frontend-app .wrap');
            if (!notice || !wrap || !wrap.parentNode) {
                return false;
            }

            var header = wrap.querySelector('.tps-header');
            if (header && header.parentNode) {
                if (header.previousElementSibling !== notice) {
                    header.parentNode.insertBefore(notice, header);
                }
                return true;
            }

            var title = wrap.querySelector('h1');
            if (title && title.parentNode) {
                if (title.previousElementSibling !== notice) {
                    title.parentNode.insertBefore(notice, title);
                }
                return true;
            }

            if (wrap.firstChild !== notice) {
                wrap.insertBefore(notice, wrap.firstChild);
            }
            return true;
        }

        placeNoticeAboveModuleHeader();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', placeNoticeAboveModuleHeader);
        } else {
            placeNoticeAboveModuleHeader();
        }

        var content = document.getElementById('wpbody-content') || document.querySelector('.tps-frontend-app');
        if (content && window.MutationObserver) {
            var observer = new MutationObserver(function () {
                placeNoticeAboveModuleHeader();
            });
            observer.observe(content, { childList: true, subtree: true });
            setTimeout(function () { observer.disconnect(); }, 5000);
        }

        document.addEventListener('click', function (event) {
            var closeBtn = event.target.closest('.tps-notice-close');
            if (!closeBtn) {
                return;
            }

            var notice = closeBtn.closest('.tps-top-notice');
            if (!notice) {
                return;
            }

            event.preventDefault();
            dismissNotice(notice);
        });
    }

    // Usado na pagina "Clientes" para pesquisa, filtros, paginacao e exportacao.
    function initCustomersList() {
        if (data.page !== 'tps-customers' || !data.customersList) {
            return;
        }

        var pageWrap = document.querySelector('.tps-customers-modern');
        var tbody = document.getElementById('tps-customers-tbody');
        var empty = document.getElementById('tps-customers-empty');
        var search = document.getElementById('tps-customers-search');
        var sortSelect = document.getElementById('tps-customers-sort');
        var prevBtn = document.getElementById('tps-customers-prev');
        var nextBtn = document.getElementById('tps-customers-next');
        var pageLabel = document.getElementById('tps-customers-page');
        var filters = Array.from(document.querySelectorAll('.tps-customers-modern .tps-filter'));
        var exportBtn = document.getElementById('tps-customers-export');
        if (!tbody || !empty || !search || !sortSelect || !prevBtn || !nextBtn || !pageLabel || !pageWrap) {
            return;
        }

        var ajaxUrl = data.customersList.ajaxUrl || '';
        var nonce = data.customersList.nonce || '';
        var exportBaseUrl = (data.customersList.exportBaseUrl || '').replace(/&amp;/g, '&');
        var state = { paged: 1, type: '', search: '', sort: 'name' };
        var timer = null;
        var totalPages = 1;

        function renderPageNotice(type, message) {
            var current = pageWrap.querySelector('.tps-top-notice');
            if (current) {
                current.remove();
            }

            var notice = document.createElement('div');
            notice.className = 'tps-notice tps-top-notice tps-notice--' + (type || 'info');
            notice.setAttribute('role', 'status');
            notice.setAttribute('aria-live', 'polite');
            notice.innerHTML = '<p class="tps-notice__message">' + esc(message || '') + '</p><button type="button" class="tps-notice-close" aria-label="Fechar aviso">&times;</button>';

            var header = pageWrap.querySelector('.tps-header');
            if (header) {
                pageWrap.insertBefore(notice, header);
            } else {
                pageWrap.insertBefore(notice, pageWrap.firstChild);
            }
        }

        function typeLabel(type) {
            if (type === 'individual') return 'Particular';
            if (type === 'company') return 'Empresa';
            return type;
        }

        function typeIcon(type) {
            if (type === 'individual') return 'dashicons-admin-users';
            if (type === 'company') return 'dashicons-building';
            return 'dashicons-id';
        }

        function updateExportUrl() {
            if (!exportBtn || !exportBaseUrl) return;
            var url = new URL(exportBaseUrl, window.location.origin);
            if (state.type) url.searchParams.set('type', state.type);
            if (state.search) url.searchParams.set('s', state.search);
            if (state.sort) url.searchParams.set('sort', state.sort);
            exportBtn.href = url.pathname + url.search;
        }

        // Usado ao atualizar a grade principal de clientes sem recarregar a pagina.
        async function loadRows() {
            var params = new URLSearchParams({
                action: 'tps_ajax_customers_list',
                nonce: nonce,
                paged: String(state.paged),
                type: state.type,
                search: state.search,
                sort: state.sort
            });
            var res = await fetch(ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' });
            if (!res.ok) return;
            var payload = await res.json();
            if (!payload || !payload.success) return;

            var listData = payload.data || {};
            var rows = listData.rows || [];
            totalPages = listData.total_pages || 1;
            state.paged = listData.current_page || 1;

            tbody.innerHTML = rows.map(function (r) {
                var iconClass = typeIcon(r.type);
                return '<tr>'
                    + '<td><span class="tps-cell-with-icon"><span class="dashicons ' + esc(iconClass) + '" aria-hidden="true"></span><strong>' + esc(r.name) + '</strong></span></td>'
                    + '<td>' + esc(typeLabel(r.type)) + '</td>'
                    + '<td>' + esc(r.nuit) + '</td>'
                    + '<td>' + esc(r.phone) + '</td>'
                    + '<td>' + esc(r.city) + '</td>'
                    + '<td class="tps-actions-col">'
                        + '<a class="tps-row-btn" href="' + esc(r.edit_url) + '"><span class="dashicons dashicons-edit" aria-hidden="true"></span>Editar</a>'
                    + '<a class="tps-row-btn" href="' + esc(r.export_url) + '"><span class="dashicons dashicons-download" aria-hidden="true"></span>Exportar</a>'
                    + '<a class="tps-row-btn tps-row-btn-danger" href="' + esc(r.delete_url) + '" onclick="return confirm(\'Eliminar este cliente?\');"><span class="dashicons dashicons-trash" aria-hidden="true"></span>Eliminar</a>'
                    + '</td>'
                    + '</tr>';
            }).join('');

            empty.hidden = rows.length > 0;
            pageLabel.textContent = 'Pagina ' + state.paged + ' de ' + totalPages;
            prevBtn.disabled = state.paged <= 1;
            nextBtn.disabled = state.paged >= totalPages;
            updateExportUrl();
        }

        search.addEventListener('input', function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(function () {
                state.search = search.value.trim();
                state.paged = 1;
                loadRows();
            }, 250);
        });

        sortSelect.addEventListener('change', function () {
            state.sort = sortSelect.value || 'name';
            state.paged = 1;
            loadRows();
        });

        prevBtn.addEventListener('click', function () {
            if (state.paged > 1) {
                state.paged -= 1;
                loadRows();
            }
        });

        nextBtn.addEventListener('click', function () {
            if (state.paged < totalPages) {
                state.paged += 1;
                loadRows();
            }
        });

        filters.forEach(function (btn) {
            btn.addEventListener('click', function () {
                filters.forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                state.type = btn.getAttribute('data-type') || '';
                state.paged = 1;
                loadRows();
            });
        });

        loadRows();
    }

    // Usado na pagina "Importar Clientes" para validar e selecionar o CSV.
    function initCustomersImport() {
        if (data.page !== 'tps-customers-import') {
            return;
        }

        var dropZone = document.getElementById('tps-drop-zone');
        var fileInput = document.getElementById('tps_csv_file');
        var fileName = document.getElementById('tps-file-name');
        var fileError = document.getElementById('tps-file-error');
        var submitBtn = document.getElementById('tps-import-submit');

        if (!dropZone || !fileInput || !fileName) {
            return;
        }

        if (submitBtn) submitBtn.disabled = true;

        function showError(msg) {
            if (!fileError) return;
            fileError.textContent = msg;
            fileError.hidden = false;
        }

        function hideError() {
            if (!fileError) return;
            fileError.textContent = '';
            fileError.hidden = true;
        }

        function resetFile() {
            fileInput.value = '';
            fileName.textContent = '';
            if (submitBtn) submitBtn.disabled = true;
        }

        function validateFile(file) {
            if (!file) return false;
            return file.name.toLowerCase().endsWith('.csv');
        }

        function handleFile(file) {
            hideError();

            if (!validateFile(file)) {
                showError('Tipo de ficheiro invalido. Envie um ficheiro .csv.');
                resetFile();
                return;
            }

            fileName.textContent = 'Ficheiro selecionado: ' + file.name;
            if (submitBtn) submitBtn.disabled = false;
        }

        dropZone.addEventListener('click', function () {
            fileInput.click();
        });

        dropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropZone.classList.add('is-dragging');
        });

        dropZone.addEventListener('dragleave', function () {
            dropZone.classList.remove('is-dragging');
        });

        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropZone.classList.remove('is-dragging');
            if (!e.dataTransfer.files.length) return;

            var file = e.dataTransfer.files[0];
            fileInput.files = e.dataTransfer.files;
            handleFile(file);
        });

        fileInput.addEventListener('change', function () {
            if (!fileInput.files.length) {
                resetFile();
                return;
            }
            handleFile(fileInput.files[0]);
        });
    }

    // Usado na pagina "Produtos/Servicos" com filtros e paginacao AJAX.
    function initProductsServicesList() {
        if (data.page !== 'tps-products-services' || !data.productsServicesList) {
            return;
        }

        var tbody = document.getElementById('tps-ps-tbody');
        var empty = document.getElementById('tps-ps-empty');
        var search = document.getElementById('tps-ps-search');
        var sortSelect = document.getElementById('tps-ps-sort');
        var prevBtn = document.getElementById('tps-ps-prev');
        var nextBtn = document.getElementById('tps-ps-next');
        var pageLabel = document.getElementById('tps-ps-page');
        var filters = Array.from(document.querySelectorAll('.tps-products-modern .tps-filter'));
        if (!tbody || !empty || !search || !sortSelect || !prevBtn || !nextBtn || !pageLabel) {
            return;
        }

        var ajaxUrl = data.productsServicesList.ajaxUrl || '';
        var nonce = data.productsServicesList.nonce || '';
        var state = { paged: 1, type: '', search: '', sort: 'name' };
        var totalPages = 1;
        var timer = null;

        function typeLabel(type) {
            if (type === 'product') return 'Produto';
            if (type === 'service') return 'Servico';
            return type;
        }

        function typeIcon(type) {
            if (type === 'product') return 'dashicons-products';
            if (type === 'service') return 'dashicons-admin-tools';
            return 'dashicons-category';
        }

        function stockBadge(row) {
            if (!row.track_stock || row.type !== 'product') {
                return '<span class="tps-muted">N/A</span>';
            }

            if (row.is_critical) {
                return '<span class="tps-badge tps-badge-cancelled">Critico</span>';
            }

            return '<span class="tps-badge tps-badge-issued">OK</span>';
        }

        // Usado para reconstruir a tabela de itens apos pesquisa ou troca de filtro.
        async function loadRows() {
            var params = new URLSearchParams({
                action: 'tps_ajax_products_services_list',
                nonce: nonce,
                paged: String(state.paged),
                type: state.type,
                search: state.search,
                sort: state.sort
            });

            var res = await fetch(ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' });
            if (!res.ok) return;

            var payload = await res.json();
            if (!payload || !payload.success) return;

            var listData = payload.data || {};
            var rows = listData.rows || [];
            totalPages = listData.total_pages || 1;
            state.paged = listData.current_page || 1;

            tbody.innerHTML = rows.map(function (r) {
                var iconClass = typeIcon(r.type);
                return '<tr>'
                    + '<td><span class="tps-cell-with-icon"><span class="dashicons ' + esc(iconClass) + '" aria-hidden="true"></span><strong>' + esc(r.name) + '</strong></span></td>'
                    + '<td>' + esc(typeLabel(r.type)) + '</td>'
                    + '<td>' + esc(r.sku) + '</td>'
                    + '<td>' + esc(r.unit) + '</td>'
                    + '<td>' + esc(r.price) + '</td>'
                    + '<td>' + (r.track_stock && r.type === 'product' ? esc(r.stock_qty) : '<span class="tps-muted">N/A</span>') + '</td>'
                    + '<td>' + (r.track_stock && r.type === 'product' ? esc(r.min_stock) : '<span class="tps-muted">N/A</span>') + '</td>'
                    + '<td>' + (r.track_stock && r.type === 'product' ? esc(r.cost_price) + ' ' + stockBadge(r) : '<span class="tps-muted">N/A</span>') + '</td>'
                    + '<td class="tps-actions-col">'
                    + '<a class="tps-row-btn" href="' + esc(r.edit_url) + '"><span class="dashicons dashicons-edit" aria-hidden="true"></span>Editar</a>'
                    + '<a class="tps-row-btn tps-row-btn-danger" href="' + esc(r.delete_url) + '" onclick="return confirm(\'Eliminar este item?\');"><span class="dashicons dashicons-trash" aria-hidden="true"></span>Eliminar</a>'
                    + '</td>'
                    + '</tr>';
            }).join('');

            empty.hidden = rows.length > 0;
            pageLabel.textContent = 'Pagina ' + state.paged + ' de ' + totalPages;
            prevBtn.disabled = state.paged <= 1;
            nextBtn.disabled = state.paged >= totalPages;
        }

        search.addEventListener('input', function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(function () {
                state.search = search.value.trim();
                state.paged = 1;
                loadRows();
            }, 250);
        });

        sortSelect.addEventListener('change', function () {
            state.sort = sortSelect.value || 'name';
            state.paged = 1;
            loadRows();
        });

        prevBtn.addEventListener('click', function () {
            if (state.paged > 1) {
                state.paged -= 1;
                loadRows();
            }
        });

        nextBtn.addEventListener('click', function () {
            if (state.paged < totalPages) {
                state.paged += 1;
                loadRows();
            }
        });

        filters.forEach(function (btn) {
            btn.addEventListener('click', function () {
                filters.forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                state.type = btn.getAttribute('data-type') || '';
                state.paged = 1;
                loadRows();
            });
        });

        loadRows();
    }

    function initInventoryForms() {
        var typeSelect = document.getElementById('tps-ps-type');
        var trackStock = document.getElementById('tps-ps-track-stock');
        var trackStockCard = document.querySelector('.tps-stock-toggle-card');
        var stockFields = Array.from(document.querySelectorAll('.tps-stock-field'));
        var inventoryType = document.getElementById('tps-inventory-type');
        var quantityField = document.getElementById('tps-inventory-quantity');
        var targetField = document.getElementById('tps-inventory-target');
        var inventoryForm = document.querySelector('.tps-inventory-form');
        var inventoryProductSearch = document.getElementById('tps-inventory-product-search');
        var inventoryProductInput = document.getElementById('tps-inventory-product');
        var inventoryProductResults = document.getElementById('tps-inventory-product-results');
        var inventoryAjaxUrl = data.inventoryModule ? (data.inventoryModule.ajaxUrl || '') : '';
        var inventoryNonce = data.inventoryModule ? (data.inventoryModule.nonce || '') : '';
        var inventoryTimer = null;

        function syncProductStockFields() {
            if (!typeSelect || !trackStock) {
                return;
            }

            var isProduct = typeSelect.value === 'product';
            if (!isProduct) {
                trackStock.checked = false;
            }
            trackStock.disabled = !isProduct;
            if (trackStockCard) {
                trackStockCard.classList.toggle('is-disabled', !isProduct);
            }

            stockFields.forEach(function (field) {
                field.hidden = !isProduct || !trackStock.checked;
                field.querySelectorAll('input').forEach(function (input) {
                    input.disabled = !isProduct || !trackStock.checked;
                });
            });
        }

        function syncInventoryMovementFields() {
            if (!inventoryType || !quantityField || !targetField) {
                return;
            }

            var isAdjustment = inventoryType.value === 'adjustment';
            quantityField.disabled = isAdjustment;
            targetField.disabled = !isAdjustment;
        }

        function clearInventoryResults() {
            if (!inventoryProductResults) {
                return;
            }

            inventoryProductResults.innerHTML = '';
            inventoryProductResults.hidden = true;
        }

        function renderInventoryResults(items) {
            if (!inventoryProductResults || !inventoryProductInput || !inventoryProductSearch) {
                return;
            }

            if (!items.length) {
                inventoryProductResults.innerHTML = '<div class="tps-search-empty">Nenhum produto encontrado.</div>';
                inventoryProductResults.hidden = false;
                return;
            }

            inventoryProductResults.innerHTML = '';
            items.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tps-search-item';
                btn.textContent = item.label;
                btn.addEventListener('click', function () {
                    inventoryProductInput.value = String(item.id);
                    inventoryProductSearch.value = item.label;
                    clearInventoryResults();
                });
                inventoryProductResults.appendChild(btn);
            });
            inventoryProductResults.hidden = false;
        }

        async function fetchInventoryProducts(term) {
            var params = new URLSearchParams({
                action: 'tps_search_inventory_products',
                nonce: inventoryNonce,
                term: term
            });

            var response = await fetch(inventoryAjaxUrl + '?' + params.toString(), {
                method: 'GET',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('A pesquisa falhou');
            }

            return response.json();
        }

        if (typeSelect && trackStock) {
            typeSelect.addEventListener('change', syncProductStockFields);
            trackStock.addEventListener('change', syncProductStockFields);
            syncProductStockFields();
        }

        if (inventoryType && quantityField && targetField) {
            inventoryType.addEventListener('change', syncInventoryMovementFields);
            syncInventoryMovementFields();
        }

        if (inventoryProductSearch && inventoryProductInput && inventoryProductResults && inventoryAjaxUrl && inventoryNonce) {
            inventoryProductSearch.addEventListener('input', function () {
                var term = inventoryProductSearch.value.trim();
                inventoryProductInput.value = '';
            inventoryProductSearch.setCustomValidity('');

                if (inventoryTimer) {
                    clearTimeout(inventoryTimer);
                }

                if (term.length < 2) {
                    clearInventoryResults();
                    return;
                }

                inventoryTimer = setTimeout(async function () {
                    try {
                        var payload = await fetchInventoryProducts(term);
                        if (!payload || !payload.success) {
                            clearInventoryResults();
                            return;
                        }
                        renderInventoryResults(payload.data || []);
                    } catch (e) {
                        clearInventoryResults();
                    }
                }, 250);
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('#tps-inventory-product-search') && !event.target.closest('#tps-inventory-product-results')) {
                    clearInventoryResults();
                }
            });

            if (inventoryForm) {
                inventoryForm.addEventListener('submit', function (event) {
                    if (!inventoryProductInput.value) {
                        event.preventDefault();
                        inventoryProductSearch.setCustomValidity('Selecione um produto nos resultados da pesquisa.');
                        inventoryProductSearch.reportValidity();
                        inventoryProductSearch.focus();
                    }
                });
            }
        }
    }

    // Usado na pagina "Documentos" para manter filtros e acoes sincronizados.
    function initDocumentsList() {
        if (data.page !== 'tps-documents' || !data.documentsList) {
            return;
        }

        var tbody = document.getElementById('tps-doc-tbody');
        var empty = document.getElementById('tps-doc-empty');
        var prevBtn = document.getElementById('tps-doc-prev');
        var nextBtn = document.getElementById('tps-doc-next');
        var pageLabel = document.getElementById('tps-doc-page');
        var searchInput = document.getElementById('tps-doc-search');
        var typeSelect = document.getElementById('tps-doc-type');
        var statusSelect = document.getElementById('tps-doc-status');
        var sortSelect = document.getElementById('tps-doc-sort');
        var exportBtn = document.getElementById('tps-doc-export');
        var exportFiscalBtn = document.getElementById('tps-doc-export-at');
        if (!tbody || !empty || !prevBtn || !nextBtn || !pageLabel || !searchInput || !typeSelect || !statusSelect || !sortSelect) {
            return;
        }

        var ajaxUrl = data.documentsList.ajaxUrl || '';
        var nonce = data.documentsList.nonce || '';
        var exportBaseUrl = (data.documentsList.exportBaseUrl || '').replace(/&amp;/g, '&');
        var exportFiscalBaseUrl = (data.documentsList.exportFiscalBaseUrl || '').replace(/&amp;/g, '&');
        var state = { paged: 1, search: '', doc_type: '', status: '', sort: 'date' };
        var totalPages = 1;
        var timer = null;

        function badge(status) {
            if (status === 'issued') return '<span class="tps-badge tps-badge-issued">Emitido</span>';
            if (status === 'cancelled') return '<span class="tps-badge tps-badge-cancelled">Cancelado</span>';
            return '<span class="tps-badge tps-badge-draft">Rascunho</span>';
        }

        function typeLabel(type) {
            if (type === 'invoice') return 'Fatura';
            if (type === 'vd') return 'Venda a Dinheiro';
            if (type === 'quotation') return 'Cotacao';
            if (type === 'credit_note') return 'Nota de Credito';
            if (type === 'debit_note') return 'Nota de Debito';
            return type;
        }

        function typeIcon(type) {
            if (type === 'invoice') return 'dashicons-media-spreadsheet';
            if (type === 'vd') return 'dashicons-tickets-alt';
            if (type === 'quotation') return 'dashicons-media-text';
            if (type === 'credit_note') return 'dashicons-minus';
            if (type === 'debit_note') return 'dashicons-plus';
            return 'dashicons-media-document';
        }

        function updateExportUrl() {
            if (!exportBtn || !exportBaseUrl) return;
            var url = new URL(exportBaseUrl, window.location.origin);
            if (state.search) url.searchParams.set('search', state.search);
            if (state.doc_type) url.searchParams.set('doc_type', state.doc_type);
            if (state.status) url.searchParams.set('status', state.status);
            if (state.sort) url.searchParams.set('sort', state.sort);
            exportBtn.href = url.pathname + url.search;

            if (exportFiscalBtn && exportFiscalBaseUrl) {
                var fiscalUrl = new URL(exportFiscalBaseUrl, window.location.origin);
                if (state.search) fiscalUrl.searchParams.set('search', state.search);
                if (state.doc_type) fiscalUrl.searchParams.set('doc_type', state.doc_type);
                if (state.status) fiscalUrl.searchParams.set('status', state.status);
                if (state.sort) fiscalUrl.searchParams.set('sort', state.sort);
                exportFiscalBtn.href = fiscalUrl.pathname + fiscalUrl.search;
            }
        }

        // Usado na listagem principal de documentos e no link de exportacao.
        async function loadRows() {
            var params = new URLSearchParams({
                action: 'tps_ajax_documents_list',
                nonce: nonce,
                paged: String(state.paged),
                search: state.search,
                doc_type: state.doc_type,
                status: state.status,
                sort: state.sort
            });

            var res = await fetch(ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' });
            if (!res.ok) return;

            var payload = await res.json();
            if (!payload || !payload.success) return;

            var listData = payload.data || {};
            var rows = listData.rows || [];
            totalPages = listData.total_pages || 1;
            state.paged = listData.current_page || 1;

            tbody.innerHTML = rows.map(function (r) {
                var customer = r.customer_name ? r.customer_name : ('#' + r.customer_id);
                var iconClass = typeIcon(r.type);
                var actions = '<a class="tps-row-btn" href="' + esc(r.edit_url) + '"><span class="dashicons dashicons-edit" aria-hidden="true"></span>Editar</a>';
                if (r.status === 'draft' && r.issue_url) {
                    actions += '<a class="tps-row-btn" href="' + esc(r.issue_url) + '"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>Emitir</a>';
                }

                return '<tr>'
                    + '<td><strong>' + esc(r.number) + '</strong></td>'
                    + '<td><span class="tps-cell-with-icon"><span class="dashicons ' + esc(iconClass) + '" aria-hidden="true"></span>' + esc(typeLabel(r.type)) + '</span></td>'
                    + '<td>' + esc(customer) + '</td>'
                    + '<td>' + badge(r.status) + '</td>'
                    + '<td>' + esc(r.issue_date) + '</td>'
                    + '<td>' + esc(r.subtotal) + '</td>'
                    + '<td>' + esc(r.iva) + '</td>'
                    + '<td>' + esc(r.total) + '</td>'
                    + '<td>' + actions + '</td>'
                    + '</tr>';
            }).join('');

            empty.hidden = rows.length > 0;
            pageLabel.textContent = 'Pagina ' + state.paged + ' de ' + totalPages;
            prevBtn.disabled = state.paged <= 1;
            nextBtn.disabled = state.paged >= totalPages;
            updateExportUrl();
        }

        searchInput.addEventListener('input', function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(function () {
                state.search = searchInput.value.trim();
                state.paged = 1;
                loadRows();
            }, 250);
        });

        typeSelect.addEventListener('change', function () {
            state.doc_type = typeSelect.value;
            state.paged = 1;
            loadRows();
        });

        statusSelect.addEventListener('change', function () {
            state.status = statusSelect.value;
            state.paged = 1;
            loadRows();
        });

        sortSelect.addEventListener('change', function () {
            state.sort = sortSelect.value || 'date';
            state.paged = 1;
            loadRows();
        });

        prevBtn.addEventListener('click', function () {
            if (state.paged > 1) {
                state.paged -= 1;
                loadRows();
            }
        });

        nextBtn.addEventListener('click', function () {
            if (state.paged < totalPages) {
                state.paged += 1;
                loadRows();
            }
        });

        loadRows();
        updateExportUrl();
    }

    // Usado no formulario de criacao de documento antes de salvar o registo.
    function initDocumentsFormCreate() {
        if (data.page !== 'tps-documents-add' || !data.documentsForm || data.documentsForm.mode !== 'create' || !data.documentsForm.hasCustomers) {
            return;
        }

        var form = document.querySelector('.tps-document-form form');
        var searchInput = document.getElementById('tps-customer-search');
        var hiddenInput = document.getElementById('tps-customer-id');
        var typeInput = document.getElementById('tps-document-type');
        var originalInput = document.getElementById('tps-original-document-id');
        var resultsBox = document.getElementById('tps-customer-results');
        var ajaxUrl = data.documentsForm.ajaxUrl || '';
        var nonce = data.documentsForm.nonce || '';
        var timer = null;

        if (!searchInput || !hiddenInput || !resultsBox) {
            return;
        }

        function clearResults() {
            resultsBox.innerHTML = '';
            resultsBox.hidden = true;
        }

        function renderResults(items) {
            if (!items.length) {
                resultsBox.innerHTML = '<div class="tps-search-empty">Nenhum cliente encontrado.</div>';
                resultsBox.hidden = false;
                return;
            }

            resultsBox.innerHTML = '';
            items.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tps-search-item';
                btn.textContent = item.label;
                btn.addEventListener('click', function () {
                    hiddenInput.value = String(item.id);
                    searchInput.value = item.label;
                    searchInput.setCustomValidity('');
                    clearResults();
                });
                resultsBox.appendChild(btn);
            });
            resultsBox.hidden = false;
        }

        // Usado no campo de pesquisa de cliente da tela "Adicionar Documento".
        async function fetchCustomers(term) {
            var params = new URLSearchParams({
                action: 'tps_search_customers',
                nonce: nonce,
                term: term
            });

            var response = await fetch(ajaxUrl + '?' + params.toString(), {
                method: 'GET',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('A pesquisa falhou');
            }

            return response.json();
        }

        searchInput.addEventListener('input', function () {
            var term = searchInput.value.trim();
            hiddenInput.value = '';
            searchInput.setCustomValidity('');

            if (timer) {
                clearTimeout(timer);
            }

            if (term.length < 2) {
                clearResults();
                return;
            }

            timer = setTimeout(async function () {
                try {
                    var payload = await fetchCustomers(term);
                    if (!payload || !payload.success) {
                        clearResults();
                        return;
                    }
                    renderResults(payload.data || []);
                } catch (e) {
                    clearResults();
                }
            }, 250);
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.tps-search-wrap')) {
                clearResults();
            }
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                if (!hiddenInput.value) {
                    var isAdjustmentType = typeInput && (typeInput.value === 'credit_note' || typeInput.value === 'debit_note');
                    var hasOriginal = originalInput && originalInput.value;

                    if (!isAdjustmentType || !hasOriginal) {
                        event.preventDefault();
                        searchInput.setCustomValidity('Selecione um cliente nos resultados da pesquisa.');
                        searchInput.reportValidity();
                        searchInput.focus();
                    }
                }

                if (typeInput && (typeInput.value === 'credit_note' || typeInput.value === 'debit_note') && (!originalInput || !originalInput.value)) {
                    event.preventDefault();
                    if (originalInput) {
                        originalInput.setCustomValidity('Informe o ID do documento original para criar nota de ajuste.');
                        originalInput.reportValidity();
                        originalInput.focus();
                    }
                }
            });
        }

        if (originalInput) {
            originalInput.addEventListener('input', function () {
                originalInput.setCustomValidity('');
            });
        }
    }

    // Usado no modo de edicao do documento para escolher itens do catalogo.
    function initDocumentsFormEditCatalog() {
        if (data.page !== 'tps-documents-add' || !data.documentsForm || data.documentsForm.mode !== 'edit') {
            return;
        }

        var itemSearchInput = document.getElementById('tps-line-catalog-search');
        var itemResultsBox = document.getElementById('tps-line-catalog-results');
        var itemIdInput = document.getElementById('tps-line-product-service-id');
        var descriptionInput = document.getElementById('tps-line-description');
        var unitPriceInput = document.getElementById('tps-line-unit-price');
        var catalogItems = data.documentsForm.catalogItems || [];

        if (!itemSearchInput || !itemResultsBox || !itemIdInput || !descriptionInput || !unitPriceInput) {
            return;
        }

        function clearResults() {
            itemResultsBox.innerHTML = '';
            itemResultsBox.hidden = true;
        }

        function renderResults(items) {
            if (!items.length) {
                itemResultsBox.innerHTML = '<div class="tps-search-empty">Nenhum item encontrado.</div>';
                itemResultsBox.hidden = false;
                return;
            }

            itemResultsBox.innerHTML = '';
            items.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tps-search-item';
                btn.textContent = item.label + ' (' + Number(item.price).toFixed(2) + ')';
                btn.addEventListener('click', function () {
                    itemIdInput.value = String(item.id);
                    itemSearchInput.value = item.label;
                    descriptionInput.value = item.name || '';
                    unitPriceInput.value = Number(item.price).toFixed(2);
                    clearResults();
                });
                itemResultsBox.appendChild(btn);
            });
            itemResultsBox.hidden = false;
        }

        // Usado no autocomplete local das linhas do documento em edicao.
        function filterItems(term) {
            var q = term.toLowerCase();
            return catalogItems.filter(function (item) {
                return String(item.label || '').toLowerCase().includes(q)
                    || String(item.name || '').toLowerCase().includes(q);
            }).slice(0, 20);
        }

        itemSearchInput.addEventListener('input', function () {
            var term = itemSearchInput.value.trim();
            itemIdInput.value = '0';

            if (term.length < 1) {
                clearResults();
                return;
            }

            renderResults(filterItems(term));
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('#tps-line-catalog-search') && !event.target.closest('#tps-line-catalog-results')) {
                clearResults();
            }
        });
    }

    // Usado na pagina "Utilizadores" para pesquisa, filtro e paginacao via AJAX.
    function initUsersList() {
        if (data.page !== 'tps-users' || !data.usersList) {
            return;
        }

        var tbody = document.getElementById('tps-users-tbody');
        var empty = document.getElementById('tps-users-empty');
        var searchInput = document.getElementById('tps-users-search');
        var roleSelect = document.getElementById('tps-users-role');
        var resetBtn = document.getElementById('tps-users-reset');
        var prevBtn = document.getElementById('tps-users-prev');
        var nextBtn = document.getElementById('tps-users-next');
        var pageLabel = document.getElementById('tps-users-page');
        var currentCount = document.getElementById('tps-users-current-count');
        var toolbar = document.getElementById('tps-users-toolbar');
        if (!tbody || !empty || !searchInput || !roleSelect || !resetBtn || !prevBtn || !nextBtn || !pageLabel) {
            return;
        }

        var ajaxUrl = data.usersList.ajaxUrl || '';
        var nonce = data.usersList.nonce || '';
        var state = {
            paged: 1,
            search: searchInput.value.trim(),
            role: roleSelect.value || ''
        };
        var totalPages = 1;
        var timer = null;

        function avatarLetter(name) {
            var value = String(name || '').trim();
            return value ? value.charAt(0).toUpperCase() : 'U';
        }

        async function loadRows() {
            var params = new URLSearchParams({
                action: 'tps_ajax_users_list',
                nonce: nonce,
                paged: String(state.paged),
                search: state.search,
                role: state.role
            });

            var res = await fetch(ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' });
            if (!res.ok) return;

            var payload = await res.json();
            if (!payload || !payload.success) return;

            var listData = payload.data || {};
            var rows = listData.rows || [];
            totalPages = listData.total_pages || 1;
            state.paged = listData.current_page || 1;

            tbody.innerHTML = rows.map(function (r) {
                var actions = '<a class="tps-row-btn" href="' + esc(r.edit_url) + '"><span class="dashicons dashicons-edit" aria-hidden="true"></span>Editar</a>';
                if (r.is_current) {
                    actions += '<a class="tps-row-btn" href="' + esc(r.edit_url) + '"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>Perfil</a>';
                } else {
                    actions += '<a class="tps-row-btn tps-row-btn-danger" href="' + esc(r.delete_url) + '" onclick="return confirm(\'Eliminar este utilizador?\');"><span class="dashicons dashicons-trash" aria-hidden="true"></span>Eliminar</a>';
                }

                return '<tr>'
                    + '<td><div class="tps-user-cell"><span class="tps-user-avatar">' + esc(avatarLetter(r.display_name)) + '</span><div><strong>' + esc(r.display_name) + '</strong><div class="tps-inline-meta">@' + esc(r.user_login) + '</div></div></div></td>'
                    + '<td>' + esc(r.user_email) + '</td>'
                    + '<td><span class="tps-badge ' + esc(r.role_class) + '">' + esc(r.role) + '</span></td>'
                    + '<td>' + esc(r.registered_at) + '</td>'
                    + '<td><span class="tps-badge ' + esc(r.status_class) + '">' + esc(r.status_label) + '</span></td>'
                    + '<td class="tps-actions-col">' + actions + '</td>'
                    + '</tr>';
            }).join('');

            empty.hidden = rows.length > 0;
            pageLabel.textContent = 'Pagina ' + state.paged + ' de ' + totalPages;
            prevBtn.disabled = state.paged <= 1;
            nextBtn.disabled = state.paged >= totalPages;

            if (currentCount) {
                currentCount.textContent = String(listData.current_count || rows.length);
            }
        }

        if (toolbar) {
            toolbar.addEventListener('submit', function (event) {
                event.preventDefault();
            });
        }

        searchInput.addEventListener('input', function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(function () {
                state.search = searchInput.value.trim();
                state.paged = 1;
                loadRows();
            }, 250);
        });

        roleSelect.addEventListener('change', function () {
            state.role = roleSelect.value || '';
            state.paged = 1;
            loadRows();
        });

        resetBtn.addEventListener('click', function () {
            searchInput.value = '';
            roleSelect.value = '';
            state.search = '';
            state.role = '';
            state.paged = 1;
            loadRows();
        });

        prevBtn.addEventListener('click', function () {
            if (state.paged > 1) {
                state.paged -= 1;
                loadRows();
            }
        });

        nextBtn.addEventListener('click', function () {
            if (state.paged < totalPages) {
                state.paged += 1;
                loadRows();
            }
        });

        loadRows();
    }

    // Usado no Dashboard para desenhar o grafico de receita mensal.
    function initDashboardChart() {
        if (data.page !== 'tps-dashboard' || !data.dashboard || !data.dashboard.monthlyRevenue) {
            return;
        }

        var revenueData = data.dashboard.monthlyRevenue;
        var svg = document.getElementById('tps-revenue-chart');
        if (!svg || !revenueData || !revenueData.length) {
            return;
        }

        var width = 760;
        var height = 260;
        var padding = { top: 20, right: 18, bottom: 36, left: 48 };
        var innerW = width - padding.left - padding.right;
        var innerH = height - padding.top - padding.bottom;

        var values = revenueData.map(function (d) { return Number(d.total || 0); });
        var max = Math.max.apply(null, values);
        if (max <= 0) max = 1;

        function xAt(i) {
            if (revenueData.length === 1) return padding.left + innerW / 2;
            return padding.left + (innerW * i / (revenueData.length - 1));
        }

        function yAt(v) {
            return padding.top + (innerH - ((v / max) * innerH));
        }

        var points = values.map(function (v, i) {
            return xAt(i).toFixed(2) + ',' + yAt(v).toFixed(2);
        }).join(' ');

        var areaPath = 'M ' + xAt(0).toFixed(2) + ' ' + (padding.top + innerH).toFixed(2) + ' L '
            + values.map(function (v, i) { return xAt(i).toFixed(2) + ' ' + yAt(v).toFixed(2); }).join(' L ')
            + ' L ' + xAt(values.length - 1).toFixed(2) + ' ' + (padding.top + innerH).toFixed(2) + ' Z';

        var ns = 'http://www.w3.org/2000/svg';
        function el(name, attrs) {
            var node = document.createElementNS(ns, name);
            Object.keys(attrs).forEach(function (k) { node.setAttribute(k, attrs[k]); });
            return node;
        }

        svg.innerHTML = '';
        svg.appendChild(el('rect', { x: 0, y: 0, width: width, height: height, fill: '#f6f8fc' }));

        for (var g = 0; g <= 4; g++) {
            var gy = padding.top + (innerH * g / 4);
            svg.appendChild(el('line', {
                x1: padding.left,
                y1: gy,
                x2: width - padding.right,
                y2: gy,
                stroke: '#dde8f5',
                'stroke-width': 1
            }));
        }

        svg.appendChild(el('path', { d: areaPath, fill: 'rgba(15, 94, 168, 0.16)' }));
        svg.appendChild(el('polyline', {
            points: points,
            fill: 'none',
            stroke: '#0f5ea8',
            'stroke-width': 3,
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round'
        }));

        values.forEach(function (v, i) {
            svg.appendChild(el('circle', { cx: xAt(i), cy: yAt(v), r: 3.8, fill: '#0f5ea8' }));
            svg.appendChild(el('text', {
                x: xAt(i),
                y: height - 12,
                'text-anchor': 'middle',
                'font-size': '11',
                fill: '#62758f'
            })).textContent = String(revenueData[i].label || '');
        });
    }

    // Usado nos cards do Dashboard que exibem barras percentuais simples.
    function initDashboardBars() {
        if (data.page !== 'tps-dashboard') {
            return;
        }

        document.querySelectorAll('.tps-dashboard-modern .tps-bar-fill[data-fill]').forEach(function (bar) {
            var fill = Number(bar.getAttribute('data-fill') || 0);
            if (!Number.isFinite(fill)) {
                fill = 0;
            }

            fill = Math.max(0, Math.min(100, fill));
            bar.style.width = fill.toFixed(2) + '%';
            bar.setAttribute('aria-hidden', 'true');
        });
    }

    // Usado na pagina "Auditoria" para abrir modal com before/after/meta do evento.
    function initAuditDetails() {
        if (data.page !== 'tps-audit') {
            return;
        }

        var page = document.querySelector('.tps-audit-modern');
        var modal = document.getElementById('tps-audit-modal');
        var modalJson = document.getElementById('tps-audit-modal-json');

        if (!page || !modal || !modalJson) {
            return;
        }

        function openModal(rawJson) {
            var formatted = String(rawJson || '{}');

            try {
                var parsed = JSON.parse(formatted);
                formatted = JSON.stringify(parsed, null, 2);
            } catch (e) {
                // Mantem o conteudo original quando nao for JSON valido.
            }

            modalJson.textContent = formatted;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('tps-modal-open');
        }

        function closeModal() {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('tps-modal-open');
        }

        page.addEventListener('click', function (event) {
            var openBtn = event.target.closest('[data-tps-audit-open="1"]');
            if (openBtn) {
                var payloadId = openBtn.getAttribute('data-tps-audit-payload') || '';
                if (!payloadId) {
                    return;
                }

                var payloadNode = document.getElementById(payloadId);
                if (!payloadNode) {
                    return;
                }

                openModal(payloadNode.textContent || '{}');
                return;
            }

            if (event.target.closest('[data-tps-audit-close="1"]')) {
                closeModal();
            }
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    }

    initUiEnhancements();
    initMobileShell();
    initNotices();
    initCustomersList();
    initCustomersImport();
    initProductsServicesList();
    initUsersList();
    initDocumentsList();
    initDocumentsFormCreate();
    initDocumentsFormEditCatalog();
    initInventoryForms();
    initDashboardBars();
    initDashboardChart();
    initAuditDetails();
})();
