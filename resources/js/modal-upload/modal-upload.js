/*
laravelModalUploader - Vanilla JS Migration
Version: 2.1
Author: Adaptado por Elessandro
*/

document.addEventListener('DOMContentLoaded', () => {
    //=========================================================================
    // Elementos do DOM
    //=========================================================================
    const uploadModalEl = document.getElementById('upload-file');
    const uploadViewModalEl = document.getElementById('upload-view');

    // Instâncias do Modal Bootstrap (Supondo BS5)
    const bsUploadModal = uploadModalEl ? new bootstrap.Modal(uploadModalEl) : null;
    const bsViewModal = uploadViewModalEl ? new bootstrap.Modal(uploadViewModalEl) : null;

    const sendImageHolder = uploadModalEl?.querySelector(".image-holder");
    const viewImageHolder = uploadViewModalEl?.querySelector(".image-holder");

    const uploadForm = document.getElementById("uploadForm");
    const fileInput = document.getElementById("fileUpload");
    const uploadError = document.getElementById("upload-error");
    const progressBarContainer = document.getElementById('progress-bar');
    const progressBar = document.querySelector(".progress-bar");

    //=========================================================================
    // Métodos Auxiliares
    //=========================================================================
    const uploadBeauty = (percent) => {
        if (!progressBar) return;
        progressBar.setAttribute('aria-valuenow', percent);
        progressBar.style.width = `${percent}%`;
        progressBar.textContent = `${percent}%`;
    };

    const toggleModal = (modalInstance) => {
        modalInstance?.toggle();
    };

    //=========================================================================
    // Lógica do Input de Arquivo
    //=========================================================================
    fileInput?.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;

        const ext = file.name.split('.').pop().toLowerCase();
        
        if (sendImageHolder) sendImageHolder.innerHTML = '';
        if (uploadError) uploadError.style.display = 'none';

        const validExtensions = ["gif", "png", "jpg", "jpeg"];

        if (validExtensions.includes(ext)) {
            if (window.FileReader) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'thumb-image';
                    sendImageHolder?.appendChild(img);
                };
                
                if (sendImageHolder) sendImageHolder.style.display = 'block';
                reader.readAsDataURL(file);

                toggleModal(bsUploadModal);
            } else {
                alert("Navegador incompatível com FileReader.");
            }
        } else {
            alert("Por enquanto o sistema só aceita imagens!");
        }
    });

    //=========================================================================
    // Envio do Arquivo (AJAX Nativo com XHR para Progress Bar)
    //=========================================================================
    document.addEventListener('click', (e) => {
        if (e.target.closest('#send-file')) {
            const formData = new FormData(uploadForm);
            const url = uploadForm.dataset.action;
            const method = uploadForm.dataset.request || 'POST';

            formData.append('file', fileInput.files[0]);

            // Usamos XMLHttpRequest em vez de Fetch para monitorar o progresso de upload
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    const percentComplete = Math.floor((event.loaded / event.total) * 100);
                    if (progressBarContainer) progressBarContainer.style.display = 'block';
                    progressBar?.classList.remove("notransition");
                    uploadBeauty(percentComplete);
                }
            });

            xhr.onreadystatechange = () => {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        // Sucesso
                        bsUploadModal?.hide();
                        progressBar?.classList.add("notransition");
                        if (progressBar) progressBar.style.width = '0%';
                        if (progressBarContainer) progressBarContainer.style.display = 'none';
                    } else if (xhr.status === 422) {
                        // Erro de Validação
                        const response = JSON.parse(xhr.responseText);
                        if (uploadError) {
                            uploadError.style.display = 'block';
                            uploadError.textContent = response.file || "Erro no arquivo";
                        }
                        if (progressBarContainer) progressBarContainer.style.display = 'none';
                    }
                }
            };

            xhr.open(method, url);
            xhr.send(formData);
        }

        //=========================================================================
        // Visualização de Arquivo (Delegation)
        //=========================================================================
        const viewTrigger = e.target.closest('.image-upload');
        if (viewTrigger) {
            const imageUrl = viewTrigger.getAttribute('src');
            
            if (viewImageHolder) {
                viewImageHolder.innerHTML = `<img src="${imageUrl}">`;
            }

            const downloadBtn = document.getElementById('download-file');
            if (downloadBtn) {
                downloadBtn.setAttribute('href', imageUrl);
            }

            bsViewModal?.show();
        }
    });

    //=========================================================================
    // Quickfix: Resetar input ao fechar
    //=========================================================================
    uploadModalEl?.addEventListener('hidden.bs.modal', () => {
        fileInput.value = "";
    });
});