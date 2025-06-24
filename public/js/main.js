document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const extensionListContainer = document.getElementById('extensionList');

    // Function to render extensions from data
    function renderExtensions(sectors) {
        extensionListContainer.innerHTML = ''; // Clear current list
        if (sectors.length === 0) {
            extensionListContainer.innerHTML = '<p>Nenhum ramal encontrado para o termo buscado.</p>';
            return;
        }

        sectors.forEach(sector => {
            if (sector.extensions.length > 0) { // Only render sector if it has extensions
                const sectorGroup = document.createElement('div');
                sectorGroup.className = 'sector-group';

                const sectorName = document.createElement('h2');
                sectorName.className = 'sector-name';
                sectorName.textContent = `${sector.name} (${sector.extensions.length})`;
                sectorName.addEventListener('click', () => {
                    const extensionsDiv = sectorGroup.querySelector('.extensions');
                    extensionsDiv.style.display = extensionsDiv.style.display === 'none' ? 'block' : 'none';
                });

                const extensionsDiv = document.createElement('div');
                extensionsDiv.className = 'extensions';

                const ul = document.createElement('ul');
                sector.extensions.forEach(ext => {
                    const li = document.createElement('li');
                    li.dataset.name = ext.person_name ? ext.person_name.toLowerCase() : '';
                    li.dataset.number = ext.number;
                    li.innerHTML = `<strong>${ext.number}</strong> - ${ext.person_name || 'Vago'} (${ext.type})`;
                    ul.appendChild(li);
                });

                extensionsDiv.appendChild(ul);
                sectorGroup.appendChild(sectorName);
                sectorGroup.appendChild(extensionsDiv);
                extensionListContainer.appendChild(sectorGroup);
            }
        });
    }

    // Initial load (all extensions)
    function loadInitialExtensions() {
        // This function re-uses the PHP generated list on first load
        // and sets up the accordion.
        // For AJAX search, it will be replaced by fetch.
        document.querySelectorAll('.sector-group').forEach(sectorGroup => {
            const sectorName = sectorGroup.querySelector('.sector-name');
            const extensionsDiv = sectorGroup.querySelector('.extensions');
            if (sectorName && extensionsDiv) {
                sectorName.addEventListener('click', () => {
                    extensionsDiv.style.display = extensionsDiv.style.display === 'none' ? 'block' : 'none';
                });
            }
        });
    }

    loadInitialExtensions();


    // Live Search
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim().toLowerCase();

            searchTimeout = setTimeout(() => {
                if (searchTerm.length > 1 || searchTerm.length === 0) { // Search if term is empty (reset) or > 1 char
                    fetchExtensions(searchTerm);
                } else if (searchTerm.length === 0 && originalExtensionsHTML) {
                    // If search term is cleared, restore original list if AJAX was used
                    // extensionListContainer.innerHTML = originalExtensionsHTML;
                    // loadInitialExtensions(); // Re-attach event listeners
                    // Or simply fetch all again
                    fetchExtensions("");
                }
            }, 300); // Debounce search
        });
    }

    // Fetch extensions via AJAX
    let originalExtensionsHTML = ''; // To store the initial state if needed for reset without AJAX
    function fetchExtensions(term) {
        // Store initial HTML before first AJAX search if not already stored
        // if (!originalExtensionsHTML && extensionListContainer.innerHTML.length > 0 && !term) {
        // originalExtensionsHTML = extensionListContainer.innerHTML;
        // }

        // Show loading indicator (optional)
        // extensionListContainer.innerHTML = '<p>Buscando...</p>';

        fetch(`src/ajax/get_extensions.php?term=${encodeURIComponent(term)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    extensionListContainer.innerHTML = `<p class="error-message">${data.error}</p>`;
                } else {
                    renderExtensions(data);
                }
            })
            .catch(error => {
                console.error('Error fetching extensions:', error);
                extensionListContainer.innerHTML = `<p class="error-message">Erro ao buscar ramais: ${error.message}. Verifique o console para mais detalhes.</p>`;
            });
    }

    // Clear Search Button
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
            }
            fetchExtensions(''); // Fetch all extensions
        });
    }

    // Initial setup for accordion on server-rendered list
    // This part is crucial if the initial page load contains the full list.
    // The AJAX search will replace the content, so event listeners need to be on static parents or re-delegated.
    // The renderExtensions function now handles re-attaching accordion listeners.
});
