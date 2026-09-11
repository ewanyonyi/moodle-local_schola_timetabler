define([], function() {
    'use strict';

    /**
     * AMD module for filtering institutional breaks table rows.
     *
     * @exports local_schola_timetabler/break_filter
     */
    return {
        /**
         * Initialize break table filter search listener.
         */
        init: function() {
            const searchInput = document.getElementById('breakSearchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const query = searchInput.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#breaksMasterTable tbody tr.break-table-row');
                    rows.forEach(function(row) {
                        const text = row.innerText.toLowerCase();
                        if (!query || text.indexOf(query) !== -1) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        }
    };
});
