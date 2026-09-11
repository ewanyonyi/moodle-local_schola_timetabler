define([], function() {
    'use strict';

    /**
     * AMD module for auto-triggering print view in export.php.
     *
     * @exports local_schola_timetabler/autoprint
     */
    return {
        /**
         * Trigger window.print() after page load.
         */
        init: function() {
            if (document.readyState === 'complete') {
                window.print();
            } else {
                window.addEventListener('load', function() {
                    window.print();
                });
            }
        }
    };
});
