document.addEventListener('DOMContentLoaded', () => {
    const signatureForm = document.getElementById('signature-form');
    const previewArea = document.getElementById('preview-area');
    const copyBtn = document.getElementById('copy-btn');

    signatureForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const name = document.getElementById('name').value;
        const sector = document.getElementById('sector').value;
        const extension = document.getElementById('extension').value;

        const signatureHTML = `
            <p style="margin: 0; font-weight: bold;">${name}</p>
            <p style="margin: 0;">${sector}</p>
            <p style="margin: 0;">Ramal: ${extension}</p>
        `;

        previewArea.innerHTML = signatureHTML;
    });

    copyBtn.addEventListener('click', () => {
        const signatureHTML = previewArea.innerHTML;
        if (signatureHTML) {
            navigator.clipboard.writeText(signatureHTML)
                .then(() => {
                    alert('Assinatura copiada para a área de transferência!');
                })
                .catch(err => {
                    console.error('Erro ao copiar a assinatura: ', err);
                });
        }
    });
});
