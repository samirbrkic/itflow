/**
 * Live Search for Tickets with AJAX
 * Updates table without page reload
 */

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const recordsSelect = document.getElementById('recordsSelect');
    if (!searchInput) {
        return;
    }
    
    const searchForm = searchInput.closest('form');
    const searchSpinner = document.getElementById('searchSpinner');
    const ticketsContent = document.getElementById('ticketsContent');
    let searchTimeout;
    let isLoading = false;
    
    // Function to perform AJAX search
    function performSearch() {
        if (isLoading) return;
        
        isLoading = true;
        if (searchSpinner) {
            searchSpinner.style.display = 'inline-block';
        }
        
        // Build query string from form
        const formData = new FormData(searchForm);
        const params = new URLSearchParams(formData);
        params.append('ajax', '1');
        
        // Fetch results
        fetch('?' + params.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Extract just the tickets content
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.getElementById('ticketsContent');
            
            if (newContent && ticketsContent) {
                ticketsContent.innerHTML = newContent.innerHTML;
            }
            
            isLoading = false;
            if (searchSpinner) {
                searchSpinner.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            isLoading = false;
            if (searchSpinner) {
                searchSpinner.style.display = 'none';
            }
        });
    }
    
    // Auto-submit on input with debounce
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        if (searchSpinner) {
            searchSpinner.style.display = 'inline-block';
        }
        
        searchTimeout = setTimeout(function() {
            performSearch();
        }, 600);
    });
    
    // Change records per page
    if (recordsSelect) {
        recordsSelect.addEventListener('change', function() {
            performSearch();
        });
    }
    
    // Clear on ESC key
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            clearTimeout(searchTimeout);
            performSearch();
        }
    });
    
    // Prevent form submission (we handle it with AJAX)
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch();
    });
});
