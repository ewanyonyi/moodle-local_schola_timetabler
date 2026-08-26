<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_schola_timetabler\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy API provider implementation for local_schola_timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\core_user_data_provider, \core_privacy\local\request\plugin\provider {
    /**
     * Document database tables and external disclosures.
     *
     * @param collection $collection Privacy metadata collection.
     * @return collection Updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        // Table local_schola_timetabler_schedules
        $collection->add_database_table(
            'local_schola_timetabler_schedules',
            [
                'courseid'  => 'privacy:metadata:schedules:courseid',
                'quizid'    => 'privacy:metadata:schedules:quizid',
                'roomid'    => 'privacy:metadata:schedules:roomid',
                'slotid'    => 'privacy:metadata:schedules:slotid',
                'teacherid' => 'privacy:metadata:schedules:teacherid',
            ],
            'privacy:metadata:schedules'
        );

        // External disclosure: LemonSqueezy License Validation API
        $collection->add_external_location_link(
            'lemonsqueezy',
            [
                'license_key' => 'privacy:metadata:lemonsqueezy:license_key',
            ],
            'privacy:metadata:lemonsqueezy'
        );

        // External disclosure: Schola Timetabler Cloud Solver Engine
        $collection->add_external_location_link(
            'solver_service',
            [
                'lecturer_id' => 'privacy:metadata:solver_service:lecturer_id',
                'courseid'    => 'privacy:metadata:solver_service:courseid',
            ],
            'privacy:metadata:solver_service'
        );

        return $collection;
    }

    /**
     * Get list of contexts containing personal user data for given user ID.
     *
     * @param int $userid Target user ID.
     * @return contextlist Contexts list.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course} cr ON cr.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {local_schola_timetabler_schedules} s ON s.courseid = cr.id
                 WHERE s.teacherid = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid'       => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Get list of users stored in given context.
     *
     * @param userlist $userlist Userlist object.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $sql = "SELECT teacherid AS userid
                  FROM {local_schola_timetabler_schedules}
                 WHERE courseid = :courseid AND teacherid > 0";
        $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
    }

    /**
     * Export personal user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            $schedules = $DB->get_records('local_schola_timetabler_schedules', [
                'courseid'  => $context->instanceid,
                'teacherid' => $userid,
            ]);

            if (!empty($schedules)) {
                $exportdata = [];
                foreach ($schedules as $sched) {
                    $exportdata[] = (object)[
                        'schedule_type' => $sched->schedule_type,
                        'room_id'       => $sched->roomid,
                        'slot_id'       => $sched->slotid,
                    ];
                }

                \core_privacy\local\request\writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_schola_timetabler')],
                    (object)['schedules' => $exportdata]
                );
            }
        }
    }

    /**
     * Delete all personal user data in target context.
     *
     * @param \context $context Context object.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel == CONTEXT_COURSE) {
            $DB->delete_records('local_schola_timetabler_schedules', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete personal user data for user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $DB->delete_records('local_schola_timetabler_schedules', [
                    'courseid'  => $context->instanceid,
                    'teacherid' => $userid,
                ]);
            }
        }
    }

    /**
     * Delete personal user data for list of users in approved context.
     *
     * @param approved_userlist $userlist Approved user list.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $inparams['courseid'] = $context->instanceid;

        $DB->delete_records_select('local_schola_timetabler_schedules', "courseid = :courseid AND teacherid {$insql}", $inparams);
    }
}
