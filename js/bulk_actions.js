// bulk_actions.js
// Allow selecting and editing multiple records at once (no <form> dependency)

// --- Helpers ---
function getCheckboxes() {
    // Always query fresh in case rows are re-rendered
    return Array.from(document.querySelectorAll('input[type="checkbox"].bulk-select'));
}

function getSelectedIds() {
    return getCheckboxes()
        .filter(cb => cb.checked)
        .map(cb => cb.value);
}

function updateSelectedCount() {
    const count = getSelectedIds().length;
    const selectedCountEl = document.getElementById('selectedCount');
    if (selectedCountEl) {
        selectedCountEl.textContent = count;
    }

    const bulkBtn = document.getElementById('bulkActionButton');
    if (bulkBtn) {
        bulkBtn.hidden = count === 0;
    }
}

// --- Select All Handling ---
function checkAll(source) {
    getCheckboxes().forEach(cb => {
        cb.checked = source.checked;
    });
    updateSelectedCount();
}

// Wire select-all checkbox if present
const selectAllCheckbox = document.getElementById('selectAllCheckbox');
if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('click', function () {
        checkAll(this);
    });
}

// --- Per-row Checkbox Handling ---
document.addEventListener('click', function (e) {
    const cb = e.target.closest('input[type="checkbox"].bulk-select');
    if (!cb) return;
    updateSelectedCount();
});

// --- Initialize count on page load ---
document.addEventListener('DOMContentLoaded', updateSelectedCount);

// ------------------------------------------------------
// Generic bulk handler driven by data-bulk / data-bulk-names
// ------------------------------------------------------
// Behavior:
//
// 1) If NO data-bulk-names:
//      - all checked .bulk-select => ?selected_ids[]=1&selected_ids[]=2...
//
// 2) If data-bulk-names="file_ids[],document_ids[]":
//      - checked name="file_ids[]"  => ?file_ids[]=1&file_ids[]=2...
//      - checked name="document_ids[]" => ?document_ids[]=5...
//
// Works with either data-modal-url or href.
// Does NOT open modal or prevent default; it just rewrites the URL.
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-bulk="true"]');
    if (!trigger) return;

    // Base URL: prefer data-modal-url (ajax-modal style), fallback to href
    const baseUrl = trigger.getAttribute('data-modal-url') || trigger.getAttribute('href');
    if (!baseUrl || baseUrl === '#') {
        return;
    }

    const url    = new URL(baseUrl, window.location.origin);
    const params = url.searchParams;

    const bulkNamesAttr = trigger.getAttribute('data-bulk-names');
    const checkboxes    = getCheckboxes().filter(cb => cb.checked);

    // Clear previous ids (in case link is reused)
    params.delete('selected_ids[]');

    if (bulkNamesAttr && bulkNamesAttr.trim() !== '') {
        // New behavior: group by checkbox name
        const bulkNames = bulkNamesAttr
            .split(',')
            .map(s => s.trim())
            .filter(Boolean);

        // Clear specific names first
        bulkNames.forEach(name => params.delete(name));

        // Append values by name
        bulkNames.forEach(name => {
            checkboxes.forEach(cb => {
                if (cb.name === name) {
                    params.append(name, cb.value);
                }
            });
        });
    } else {
        // Old behavior: everything as selected_ids[]
        checkboxes.forEach(cb => {
            params.append('selected_ids[]', cb.value);
        });
    }

    const finalUrl = url.pathname + '?' + params.toString();

    // Write back to data-modal-url if present, else to href
    if (trigger.hasAttribute('data-modal-url')) {
        trigger.setAttribute('data-modal-url', finalUrl);
    } else {
        trigger.setAttribute('href', finalUrl);
    }
    // NOTE: we do NOT call preventDefault(), we do NOT open modals here.
}, true); // use capture so this runs before other click handlers
