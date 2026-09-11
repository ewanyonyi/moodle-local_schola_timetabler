define([], function() {
    'use strict';

    /**
     * AMD module for dynamic slot row builder in templates.php.
     *
     * @exports local_schola_timetabler/slot_builder
     */
    return {
        /**
         * Add a new slot row to the template builder table.
         *
         * @param {number|string} day Day of week (0=All, 1=Mon, ..., 7=Sun)
         * @param {string} start Start time (HH:MM)
         * @param {string} end End time (HH:MM)
         * @param {string} type Slot category type (class, lab, break, exam)
         */
        addSlotRow: function(day, start, end, type) {
            day = (day !== undefined) ? day : 0;
            start = start || '08:00';
            end = end || '09:30';
            type = type || 'class';

            const container = document.getElementById('slot-rows-container');
            if (!container) {
                return;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = ''
                + '<td><select name="slot_day[]" class="form-select form-select-sm">'
                + '<option value="0" ' + (day == 0 ? 'selected' : '') + '>All Weekdays (Mon-Fri)</option>'
                + '<option value="1" ' + (day == 1 ? 'selected' : '') + '>Monday</option>'
                + '<option value="2" ' + (day == 2 ? 'selected' : '') + '>Tuesday</option>'
                + '<option value="3" ' + (day == 3 ? 'selected' : '') + '>Wednesday</option>'
                + '<option value="4" ' + (day == 4 ? 'selected' : '') + '>Thursday</option>'
                + '<option value="5" ' + (day == 5 ? 'selected' : '') + '>Friday</option>'
                + '<option value="6" ' + (day == 6 ? 'selected' : '') + '>Saturday</option>'
                + '<option value="7" ' + (day == 7 ? 'selected' : '') + '>Sunday</option></select></td>'
                + '<td><input type="time" name="slot_start[]" value="' + start + '" class="form-control form-control-sm" required></td>'
                + '<td><input type="time" name="slot_end[]" value="' + end + '" class="form-control form-control-sm" required></td>'
                + '<td><select name="slot_type[]" class="form-select form-select-sm">'
                + '<option value="class" ' + (type === 'class' ? 'selected' : '') + '>Class Lecture (Standard Teaching Window)</option>'
                + '<option value="lab" ' + (type === 'lab' ? 'selected' : '') + '>Laboratory Practical (Extended Block)</option>'
                + '<option value="break" ' + (type === 'break' ? 'selected' : '') + '>Break / Blockout (Lunch, Tea Break, Assembly)</option>'
                + '<option value="exam" ' + (type === 'exam' ? 'selected' : '') + '>Examination Period (Dedicated Exam Block)</option></select></td>'
                + '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-row" title="Remove slot window">&times;</button></td>';

            const removeBtn = tr.querySelector('.btn-remove-row');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    tr.remove();
                });
            }

            container.appendChild(tr);
        },

        /**
         * Initialize slot builder with initial data rows.
         *
         * @param {Array} initialRows Data array of initial slots
         */
        init: function(initialRows) {
            const self = this;
            const addBtn = document.getElementById('schola-add-slot-row');
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    self.addSlotRow(0, '08:00', '09:30', 'class');
                });
            }

            if (initialRows && initialRows.length > 0) {
                initialRows.forEach(function(r) {
                    self.addSlotRow(r.day, r.start, r.end, r.type);
                });
            } else {
                self.addSlotRow(0, '07:00', '08:30', 'class');
                self.addSlotRow(0, '08:30', '10:00', 'class');
                self.addSlotRow(0, '10:00', '10:30', 'break');
                self.addSlotRow(0, '10:30', '12:00', 'class');
                self.addSlotRow(0, '12:00', '13:30', 'break');
                self.addSlotRow(0, '13:30', '15:00', 'class');
            }
        }
    };
});
