(function () {
    // Usado na pagina oculta de impressao do documento.
    // O botao dispara o print nativo do navegador.
    var printBtn = document.getElementById('tps-print-btn');
    if (!printBtn) {
        return;
    }

    printBtn.addEventListener('click', function () {
        window.print();
    });
})();
