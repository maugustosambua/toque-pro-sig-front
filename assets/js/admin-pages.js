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

    // Usado nas telas com cabecalho moderno para encaixar notices do WordPress.
    function initNotices() {
        if (!data.noticeActive) {
            return;
        }

        function placeNoticeBelowTitle() {
            var notice = document.querySelector('.tps-top-notice');
            var content = document.getElementById('wpbody-content');
            var wrap = content ? content.querySelector('.wrap') : null;
            if (!notice || !wrap || !wrap.parentNode) {
                return false;
            }

            var header = wrap.querySelector('.tps-header');
            if (header && header.parentNode) {
                if (header.nextElementSibling !== notice) {
                    header.parentNode.insertBefore(notice, header.nextSibling);
                }
                return true;
            }

            var title = wrap.querySelector('h1');
            if (title && title.parentNode) {
                if (title.nextElementSibling !== notice) {
                    title.parentNode.insertBefore(notice, title.nextSibling);
                }
                return true;
            }

            if (wrap.firstChild !== notice) {
                wrap.insertBefore(notice, wrap.firstChild);
            }
            return true;
        }

        placeNoticeBelowTitle();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', placeNoticeBelowTitle);
        } else {
            placeNoticeBelowTitle();
        }

        var content = document.getElementById('wpbody-content');
        if (content && window.MutationObserver) {
            var observer = new MutationObserver(function () {
                placeNoticeBelowTitle();
            });
            observer.observe(content, { childList: true, subtree: true });
            setTimeout(function () { observer.disconnect(); }, 5000);
        }
    }

    // Usado na pagina "Clientes" para pesquisa, filtros, paginacao e exportacao.
    function initCustomersList() {
        if (data.page !== 'tps-customers' || !data.customersList) {
            return;
        }

        var tbody = document.getElementById('tps-customers-tbody');
        var empty = document.getElementById('tps-customers-empty');
        var search = document.getElementById('tps-customers-search');
        var sortSelect = document.getElementById('tps-customers-sort');
        var prevBtn = document.getElementById('tps-customers-prev');
        var nextBtn = document.getElementById('tps-customers-next');
        var pageLabel = document.getElementById('tps-customers-page');
        var filters = Array.from(document.querySelectorAll('.tps-customers-modern .tps-filter'));
        var exportBtn = document.getElementById('tps-customers-export');
        if (!tbody || !empty || !search || !sortSelect || !prevBtn || !nextBtn || !pageLabel) {
            return;
        }

        var ajaxUrl = data.customersList.ajaxUrl || '';
        var nonce = data.customersList.nonce || '';
        var exportBaseUrl = (data.customersList.exportBaseUrl || '').replace(/&amp;/g, '&');
        var state = { paged: 1, type: '', search: '', sort: 'name' };
        var timer = null;
        var totalPages = 1;

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
        if (!tbody || !empty || !prevBtn || !nextBtn || !pageLabel || !searchInput || !typeSelect || !statusSelect || !sortSelect) {
            return;
        }

        var ajaxUrl = data.documentsList.ajaxUrl || '';
        var nonce = data.documentsList.nonce || '';
        var exportBaseUrl = (data.documentsList.exportBaseUrl || '').replace(/&amp;/g, '&');
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
            return type;
        }

        function typeIcon(type) {
            if (type === 'invoice') return 'dashicons-media-spreadsheet';
            if (type === 'vd') return 'dashicons-tickets-alt';
            if (type === 'quotation') return 'dashicons-media-text';
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
                if (r.status === 'draft') {
                    actions += '<a class="tps-row-btn" href="' + esc(r.issue_url) + '"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>Emitir</a>';
                }
                if (r.status === 'issued') {
                    actions += '<a class="tps-row-btn tps-row-btn-danger" href="' + esc(r.cancel_url) + '" onclick="return confirm(\'Cancelar este documento?\');"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span>Cancelar</a>';
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
        var resultsBox = document.getElementById('tps-customer-results');
        var selectedBox = document.getElementById('tps-selected-customer');
        var ajaxUrl = data.documentsForm.ajaxUrl || '';
        var nonce = data.documentsForm.nonce || '';
        var timer = null;

        if (!searchInput || !hiddenInput || !resultsBox || !selectedBox) {
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
                    selectedBox.textContent = 'Cliente selecionado: ' + item.label;
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
            selectedBox.textContent = '';

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
                    event.preventDefault();
                    selectedBox.textContent = 'Selecione um cliente nos resultados da pesquisa.';
                    searchInput.focus();
                }
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
        var itemSelectedBox = document.getElementById('tps-line-selected-item');
        var itemIdInput = document.getElementById('tps-line-product-service-id');
        var descriptionInput = document.getElementById('tps-line-description');
        var unitPriceInput = document.getElementById('tps-line-unit-price');
        var catalogItems = data.documentsForm.catalogItems || [];

        if (!itemSearchInput || !itemResultsBox || !itemSelectedBox || !itemIdInput || !descriptionInput || !unitPriceInput) {
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
                    itemSelectedBox.textContent = 'Item selecionado: ' + item.label;
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
            itemSelectedBox.textContent = '';

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

    initNotices();
    initCustomersList();
    initCustomersImport();
    initProductsServicesList();
    initDocumentsList();
    initDocumentsFormCreate();
    initDocumentsFormEditCatalog();
    initDashboardBars();
    initDashboardChart();
})();
