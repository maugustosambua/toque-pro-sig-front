<?php // modules/customers/customers-import-view.php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

$back_url = admin_url('admin.php?page=tps-customers');
?>

<style>
    .tps-import-modern {
        --tps-panel: #ffffff;
        --tps-border: #dde5ef;
        --tps-text: #1a2433;
        --tps-text-muted: #5f6b7a;
        --tps-accent: #0f5ea8;
        --tps-accent-soft: #e7f2ff;
        margin-top: 14px;
    }
    .tps-import-modern .tps-header {
        background: linear-gradient(115deg, #f8fbff 0%, #edf4fc 55%, #e4edf8 100%);
        border: 1px solid var(--tps-border);
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 16px;
    }
    .tps-import-modern .tps-header-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .tps-import-modern .tps-header-content {
        min-width: 0;
    }
    .tps-import-modern h1 {
        margin: 0;
        color: var(--tps-text);
    }
    .tps-import-modern .tps-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--tps-border);
        border-radius: 999px;
        padding: 6px 12px;
        text-decoration: none;
        color: var(--tps-text);
        background: #fff;
        font-weight: 600;
        line-height: 1;
        transition: all .16s ease;
    }
    .tps-import-modern .tps-back-btn:hover { border-color: #b8cbe3; background: #f6faff; color: #123f67; transform: translateY(-1px); }
    .tps-import-modern .tps-back-btn:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    .tps-import-modern .tps-back-btn .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    .tps-import-modern .tps-subtitle {
        margin: 6px 0 0;
        color: var(--tps-text-muted);
    }
    .tps-import-modern .tps-grid {
        display: grid;
        grid-template-columns: 1.2fr .8fr;
        gap: 14px;
    }
    .tps-import-modern .tps-card {
        background: var(--tps-panel);
        border: 1px solid var(--tps-border);
        border-radius: 12px;
        padding: 14px;
        transition: all .16s ease;
    }
    .tps-import-modern .tps-card:hover { border-color: #c7d5e7; box-shadow: 0 8px 16px rgba(26, 36, 51, .06); }
    .tps-import-modern .tps-title-sm {
        margin: 0 0 10px;
        font-size: 16px;
        color: var(--tps-text);
    }
    .tps-import-modern .tps-list {
        margin: 0;
        padding-left: 18px;
        color: var(--tps-text-muted);
    }
    .tps-import-modern .tps-list li { transition: all .16s ease; border-radius: 6px; padding: 2px 4px; }
    .tps-import-modern .tps-list li:hover { background: #f8fbff; color: #3f5676; }
    .tps-import-modern #tps-drop-zone {
        border: 2px dashed #bdd0e5;
        background: #f8fbff;
        border-radius: 12px;
        padding: 22px;
        text-align: center;
        cursor: pointer;
        transition: all .16s ease;
    }
    .tps-import-modern #tps-drop-zone:hover {
        border-color: #9fb8d6;
        background: #f2f8ff;
    }
    .tps-import-modern #tps-drop-zone.is-dragging {
        border-color: var(--tps-accent);
        background: var(--tps-accent-soft);
    }
    .tps-import-modern #tps-file-name {
        margin-top: 10px;
        color: var(--tps-text);
        font-weight: 600;
    }
    .tps-import-modern #tps-file-error {
        margin-top: 8px;
        color: #a61b1b;
    }
    .tps-import-modern .button-primary {
        border-radius: 8px;
        transition: all .16s ease;
    }
    .tps-import-modern .button-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(26, 36, 51, .10); }
    .tps-import-modern .button-primary:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    @media (max-width: 900px) {
        .tps-import-modern .tps-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="wrap tps-import-modern">
    <section class="tps-header">
        <div class="tps-header-row">
            <a class="tps-back-btn" href="<?php echo esc_url($back_url); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1>Importar Clientes</h1>
                <p class="tps-subtitle">Importe em massa a sua base de clientes usando um ficheiro CSV.</p>
            </div>
        </div>
    </section>

    <section class="tps-grid">
        <article class="tps-card">
            <h2 class="tps-title-sm">Carregar Ficheiro</h2>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tps_import_customers">
                <?php wp_nonce_field('tps_import_customers'); ?>

                <div id="tps-drop-zone">
                    <strong>Clique para selecionar um ficheiro CSV</strong><br>
                    <span class="description">ou arraste e largue aqui</span>
                    <input type="file" id="tps_csv_file" name="file" accept=".csv" required class="hidden">
                </div>

                <p id="tps-file-name" class="description"></p>
                <p id="tps-file-error" class="description" hidden></p>

                <p style="margin-top: 10px;">
                    <?php submit_button('Importar Clientes', 'primary', 'submit', false, array( 'id' => 'tps-import-submit', 'disabled' => 'disabled' )); ?>
                </p>
            </form>
        </article>

        <article class="tps-card">
            <h2 class="tps-title-sm">Requisitos do CSV</h2>
            <ul class="tps-list">
                <li>A extensão do ficheiro deve ser <code>.csv</code>.</li>
                <li>A primeira linha deve conter as colunas de cabeçalho.</li>
                <li>Colunas obrigatórias: <code>type, name, nuit, email, phone, address, city</code>.</li>
                <li>Valores permitidos para <code>type</code>: <strong>individual</strong> ou <strong>company</strong>.</li>
            </ul>
        </article>
    </section>
</div>

<script>
(function () {
    const dropZone = document.getElementById('tps-drop-zone');
    const fileInput = document.getElementById('tps_csv_file');
    const fileName = document.getElementById('tps-file-name');
    const fileError = document.getElementById('tps-file-error');
    const submitBtn = document.getElementById('tps-import-submit');

    if (!dropZone || !fileInput) return;

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
            showError('Tipo de ficheiro inválido. Envie um ficheiro .csv.');
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

        const file = e.dataTransfer.files[0];
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
})();
</script>
