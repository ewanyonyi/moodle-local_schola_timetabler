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

namespace local_schola_timetabler;

defined('MOODLE_INTERNAL') || die();

/**
 * Institutional Schedule Profile Manager for local_schola_timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_manager {
    /**
     * Get built-in default institutional schedule profiles.
     *
     * @return array
     */
    public static function get_default_profiles(): array {
        return [
            'univ_60' => [
                'key'            => 'univ_60',
                'name'           => 'University Standard',
                'badge'          => '60-Min Periods',
                'theme'          => 'primary',
                'icon'           => 'fa-graduation-cap',
                'description'    => 'Standard 60-minute university lecture blocks with morning tea break and lunch break.',
                'day_start'      => '08:00',
                'day_end'        => '17:00',
                'period_minutes' => 60,
                'tea_start'      => '10:00',
                'tea_end'        => '10:30',
                'lunch_start'    => '12:30',
                'lunch_end'      => '13:30',
                'days'           => [1, 2, 3, 4, 5],
                'is_default'     => true,
            ],
            'univ_180' => [
                'key'            => 'univ_180',
                'name'           => 'University 3-Hour Block Lectures',
                'badge'          => '180-Min Blocks',
                'theme'          => 'success',
                'icon'           => 'fa-building-columns',
                'description'    => 'Intensive 3-hour lecture blocks designed for postgraduate, executive, or lab-heavy programs.',
                'day_start'      => '08:00',
                'day_end'        => '19:00',
                'period_minutes' => 180,
                'tea_start'      => '',
                'tea_end'        => '',
                'lunch_start'    => '11:00',
                'lunch_end'      => '13:00',
                'days'           => [1, 2, 3, 4, 5],
                'is_default'     => true,
            ],
            'highschool_45' => [
                'key'            => 'highschool_45',
                'name'           => 'High School Timetable',
                'badge'          => '45-Min Periods',
                'theme'          => 'info',
                'icon'           => 'fa-school',
                'description'    => 'Structured 45-minute periods ideal for secondary education, with morning tea and lunch break windows.',
                'day_start'      => '08:00',
                'day_end'        => '15:30',
                'period_minutes' => 45,
                'tea_start'      => '10:15',
                'tea_end'        => '10:45',
                'lunch_start'    => '12:15',
                'lunch_end'      => '13:15',
                'days'           => [1, 2, 3, 4, 5],
                'is_default'     => true,
            ],
            'univ_90' => [
                'key'            => 'univ_90',
                'name'           => 'Modular University',
                'badge'          => '90-Min Periods',
                'theme'          => 'purple',
                'icon'           => 'fa-cubes',
                'description'    => 'Extended 90-minute modular lecture blocks optimized for interactive workshops and seminars.',
                'day_start'      => '08:00',
                'day_end'        => '17:00',
                'period_minutes' => 90,
                'tea_start'      => '11:00',
                'tea_end'        => '11:30',
                'lunch_start'    => '13:00',
                'lunch_end'      => '14:00',
                'days'           => [1, 2, 3, 4, 5],
                'is_default'     => true,
            ],
            'exam_3h' => [
                'key'            => 'exam_3h',
                'name'           => 'Examination Season',
                'badge'          => '3-Hour Exam Blocks',
                'theme'          => 'warning',
                'icon'           => 'fa-file-signature',
                'description'    => 'Dedicated morning and afternoon 3-hour exam windows for institutional examination weeks.',
                'day_start'      => '08:30',
                'day_end'        => '16:30',
                'period_minutes' => 180,
                'tea_start'      => '',
                'tea_end'        => '',
                'lunch_start'    => '11:30',
                'lunch_end'      => '13:30',
                'days'           => [1, 2, 3, 4, 5],
                'is_default'     => true,
            ],
            'evening' => [
                'key'            => 'evening',
                'name'           => 'Evening / Part-Time Program',
                'badge'          => '90-Min Evening',
                'theme'          => 'dark',
                'icon'           => 'fa-moon',
                'description'    => 'After-hours schedule structure tailored for evening classes and part-time professional students.',
                'day_start'      => '17:30',
                'day_end'        => '21:00',
                'period_minutes' => 90,
                'tea_start'      => '',
                'tea_end'        => '',
                'lunch_start'    => '19:00',
                'lunch_end'      => '19:30',
                'days'           => [1, 2, 3, 4, 5],
                'is_default'     => true,
            ],
        ];
    }

    /**
     * Get all active schedule profiles.
     *
     * @return array
     */
    public static function get_profiles(): array {
        $defaults = self::get_default_profiles();
        $customjson = get_config('local_schola_timetabler', 'schedule_profiles');
        if (empty($customjson)) {
            return $defaults;
        }

        $custom = json_decode($customjson, true);
        if (!is_array($custom) || empty($custom)) {
            return $defaults;
        }

        return $custom;
    }

    /**
     * Save active profiles to plugin configuration.
     *
     * @param array $profiles
     * @return bool
     */
    public static function save_profiles(array $profiles): bool {
        return set_config('schedule_profiles', json_encode($profiles), 'local_schola_timetabler');
    }

    /**
     * Reset profiles back to system defaults.
     *
     * @return bool
     */
    public static function reset_defaults(): bool {
        return self::save_profiles(self::get_default_profiles());
    }

    /**
     * Get a single profile by key.
     *
     * @param string $key
     * @return array|null
     */
    public static function get_profile(string $key): ?array {
        $profiles = self::get_profiles();
        return $profiles[$key] ?? null;
    }

    /**
     * Save or update a single profile.
     *
     * @param string $key
     * @param array $data
     * @return bool
     */
    public static function save_profile(string $key, array $data): bool {
        $profiles = self::get_profiles();
        $data['key'] = $key;
        $profiles[$key] = array_merge($profiles[$key] ?? [], $data);
        return self::save_profiles($profiles);
    }

    /**
     * Delete a custom schedule profile.
     *
     * @param string $key
     * @return bool
     */
    public static function delete_profile(string $key): bool {
        $profiles = self::get_profiles();
        if (isset($profiles[$key])) {
            unset($profiles[$key]);
            return self::save_profiles($profiles);
        }
        return false;
    }

    /**
     * Generate slot records array for a given profile structure.
     *
     * @param array $profile
     * @return array
     */
    public static function generate_slots_for_profile(array $profile): array {
        $daystart     = $profile['day_start'] ?? '08:00';
        $dayend       = $profile['day_end'] ?? '17:00';
        $periodmins   = (int)($profile['period_minutes'] ?? 60);
        $teastart     = $profile['tea_start'] ?? '';
        $teaend       = $profile['tea_end'] ?? '';
        $lunchstart   = $profile['lunch_start'] ?? '';
        $lunchend     = $profile['lunch_end'] ?? '';
        $activedays   = $profile['days'] ?? [1, 2, 3, 4, 5];
        $slottype     = ($profile['key'] === 'exam_3h') ? 'exam' : 'class';

        $slots = [];

        foreach ($activedays as $day) {
            $currsec = strtotime("2026-01-01 " . $daystart);
            $endsec  = strtotime("2026-01-01 " . $dayend);

            $tstartsec = (!empty($teastart)) ? strtotime("2026-01-01 " . $teastart) : 0;
            $tendsec   = (!empty($teaend)) ? strtotime("2026-01-01 " . $teaend) : 0;
            $lstartsec = (!empty($lunchstart)) ? strtotime("2026-01-01 " . $lunchstart) : 0;
            $lendsec   = (!empty($lunchend)) ? strtotime("2026-01-01 " . $lunchend) : 0;

            while ($currsec < $endsec) {
                // Check Morning Tea Break
                if ($tstartsec && $tendsec && $currsec >= $tstartsec && $currsec < $tendsec) {
                    $slots[] = [
                        'dayofweek' => (int)$day,
                        'starttime' => date('H:i', $tstartsec),
                        'endtime'   => date('H:i', $tendsec),
                        'type'      => 'break',
                    ];
                    $currsec = $tendsec;
                    continue;
                }

                // Check Lunch Break
                if ($lstartsec && $lendsec && $currsec >= $lstartsec && $currsec < $lendsec) {
                    $slots[] = [
                        'dayofweek' => (int)$day,
                        'starttime' => date('H:i', $lstartsec),
                        'endtime'   => date('H:i', $lendsec),
                        'type'      => 'break',
                    ];
                    $currsec = $lendsec;
                    continue;
                }

                // Standard Period
                $nextsec = $currsec + ($periodmins * 60);
                if ($nextsec > $endsec) {
                    break;
                }

                if ($tstartsec && $currsec < $tstartsec && $nextsec > $tstartsec) {
                    $nextsec = $tstartsec;
                }
                if ($lstartsec && $currsec < $lstartsec && $nextsec > $lstartsec) {
                    $nextsec = $lstartsec;
                }

                $slots[] = [
                    'dayofweek' => (int)$day,
                    'starttime' => date('H:i', $currsec),
                    'endtime'   => date('H:i', $nextsec),
                    'type'      => $slottype,
                ];
                $currsec = $nextsec;
            }
        }

        return $slots;
    }

    /**
     * Apply profile time windows to active time slots in database (`local_schola_timetabler_slots`).
     *
     * @param string $key
     * @return int Number of inserted time slots
     */
    public static function apply_profile(string $key): int {
        global $DB;

        $profile = self::get_profile($key);
        if (!$profile) {
            return 0;
        }

        $slots = self::generate_slots_for_profile($profile);

        $DB->delete_records('local_schola_timetabler_slots');
        $inserted = 0;
        foreach ($slots as $s) {
            $DB->insert_record('local_schola_timetabler_slots', (object)$s);
            $inserted++;
        }

        return $inserted;
    }

    /**
     * Format active days array into a readable label (e.g., 'Mon–Fri (5d)', 'Mon–Sat (6d)').
     *
     * @param array $days
     * @return string
     */
    public static function format_days_label(array $days): string {
        if (empty($days)) {
            return 'No Days';
        }
        sort($days, SORT_NUMERIC);
        $daynames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $count = count($days);

        $min = min($days);
        $max = max($days);
        if ($count > 1 && ($max - $min + 1) === $count) {
            return ($daynames[$min] ?? 'Day ' . $min) . '&ndash;' . ($daynames[$max] ?? 'Day ' . $max) . ' (' . $count . 'd)';
        }

        $labels = array_map(fn($d) => $daynames[$d] ?? ('Day ' . $d), $days);
        return implode(', ', $labels) . ' (' . $count . 'd)';
    }
}
