define([], function() {
    'use strict';

    /**
     * AMD module for schedule matrix interaction, filtering, edit mode, and modal popups.
     *
     * @exports local_schola_timetabler/schedule_matrix
     */
    return {
        /**
         * Initialize timetable studio matrix controls.
         *
         * @param {Object} config Options object containing modal flags
         */
        init: function(config) {
            config = config || {};

            // Search filter listener
            const searchInput = document.getElementById('scholaLiveSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const query = searchInput.value.toLowerCase().trim();
                    const cards = document.querySelectorAll('.schola-entry-card');
                    cards.forEach(function(card) {
                        const text = card.innerText.toLowerCase();
                        if (!query || text.indexOf(query) !== -1) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Edit mode toggle listener
            const editBtn = document.getElementById('enableEditModeBtn');
            if (editBtn) {
                editBtn.addEventListener('click', function(e) {
                    if (e) {
                        e.preventDefault();
                    }
                    const container = document.getElementById('institutionalGridContainer');
                    if (!container) {
                        return;
                    }
                    if (container.classList.contains('edit-mode-active')) {
                        container.classList.remove('edit-mode-active');
                        editBtn.className = 'btn btn-outline-emerald d-inline-flex align-items-center';
                        editBtn.innerHTML = '<i class="fa fa-pencil me-1"></i> Enable Edit Mode';
                    } else {
                        container.classList.add('edit-mode-active');
                        editBtn.className = 'btn btn-emerald d-inline-flex align-items-center';
                        editBtn.innerHTML = '<i class="fa fa-check me-1"></i> Disable Edit Mode';
                    }
                });
            }

            // Auto-open modals if requested
            if (config.openModal) {
                const genModalElem = document.getElementById('generateTimetableModal');
                if (genModalElem && typeof bootstrap !== 'undefined') {
                    const myGenModal = new bootstrap.Modal(genModalElem);
                    myGenModal.show();
                }
            }
            if (config.openBreaks) {
                const modalElem = document.getElementById('manageBreaksModal');
                if (modalElem && typeof bootstrap !== 'undefined') {
                    const myModal = new bootstrap.Modal(modalElem);
                    myModal.show();
                }
            }
        }
    };
});
