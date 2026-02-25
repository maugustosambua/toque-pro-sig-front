<?php // modules/customers/customers-import-view.php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$error = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';
$col   = isset( $_GET['col'] ) ? sanitize_text_field( $_GET['col'] ) : '';
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Import Customers</h1>
    <hr class="wp-header-end">

    <?php if ( $error ) : ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                if ( $error === 'no_file' ) {
                    echo esc_html( 'No file uploaded.' );
                } elseif ( $error === 'invalid_file_type' ) {
                    echo esc_html( 'Invalid file type. Please upload a .csv file.' );
                } elseif ( $error === 'unable_to_open' ) {
                    echo esc_html( 'Unable to open the file.' );
                } elseif ( $error === 'empty_file' ) {
                    echo esc_html( 'The uploaded file is empty.' );
                } elseif ( $error === 'invalid_header' ) {
                    echo esc_html( 'Invalid CSV header. Please check the first row.' );
                } elseif ( $error === 'missing_column' && $col ) {
                    echo esc_html( 'Missing required column: ' . $col );
                } else {
                    echo esc_html( 'Import failed. Please try again.' );
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="notice notice-info">
        <p><strong>CSV format requirements:</strong></p>
        <ul>
            <li>File must be <code>.csv</code></li>
            <li>First row must contain column headers</li>
            <li>Required columns:</li>
        </ul>
        <p><code>type, name, nuit, email, phone, address, city</code></p>
        <p>
            Allowed values for <code>type</code>:
            <strong>individual</strong>, <strong>company</strong>
        </p>
    </div>

    <div class="card">
        <p>
            Upload a <strong>CSV file</strong> to import customers.
        </p>

        <form method="post"
              enctype="multipart/form-data"
              action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

            <input type="hidden" name="action" value="tps_import_customers">
            <?php wp_nonce_field( 'tps_import_customers' ); ?>

            <div id="tps-drop-zone" class="upload-drag-drop">
                <strong>Click to select a CSV file</strong><br>
                <span class="description">
                    or drag and drop it here
                </span>

                <input type="file"
                       id="tps_csv_file"
                       name="file"
                       accept=".csv"
                       required
                       class="hidden">
            </div>

            <p id="tps-file-name" class="description"></p>

            <p id="tps-file-error" class="description" hidden></p>

            <?php submit_button( 'Import Customers', 'primary large', 'submit', false, array( 'id' => 'tps-import-submit', 'disabled' => 'disabled' ) ); ?>

        </form>
    </div>

</div>

<!-- Drag & Drop do CSV -->
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
            showError('Invalid file type. Please upload a .csv file.');
            resetFile();
            return;
        }

        fileName.textContent = 'Selected file: ' + file.name;
        if (submitBtn) submitBtn.disabled = false;
    }

    dropZone.addEventListener('click', function () {
        fileInput.click();
    });

    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
    });

    dropZone.addEventListener('dragleave', function () {
    });

    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();


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
