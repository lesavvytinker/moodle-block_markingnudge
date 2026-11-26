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

/**
 * Block: a nudge on the Dashboard to the marking page.
 *
 * @package    block_markingnudge
 * @copyright  2026 Daniel Maloney
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Marking nudge: what's waiting across the assessor's courses, with a button to the marking page. It does no work of
 * its own: the numbers come from Evidence Reporter's marking page.
 *
 * @package    block_markingnudge
 * @copyright  2026 Daniel Maloney
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_markingnudge extends block_base {
    /**
     * Init.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_markingnudge');
    }

    /**
     * Only on the Dashboard.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['my' => true, 'all' => false];
    }

    /**
     * One per Dashboard.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * No settings.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * The totals and the button; nothing at all for users who don't mark anywhere.
     *
     * @return stdClass
     */
    public function get_content() {
        global $OUTPUT;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = (object) ['text' => '', 'footer' => ''];
        if (!isloggedin() || isguestuser() || !class_exists('\report_evidencereporter\local\marking')) {
            return $this->content;
        }
        $totals = self::get_totals();
        if ($totals === null) {
            return $this->content;
        }
        $this->content->text = $OUTPUT->render_from_template('block_markingnudge/totals', self::export($totals));
        return $this->content;
    }

    /**
     * The current user's totals across their courses (cached for a few minutes), or null if they mark nowhere.
     *
     * @return stdClass|null tomark, amber, red, feedback, notreleased, toconfirm, ready
     */
    public static function get_totals(): ?stdClass {
        global $USER;
        $cache = cache::make('block_markingnudge', 'totals');
        $key = 'u' . $USER->id;
        $cached = $cache->get($key);
        if ($cached !== false) {
            return $cached ?: null;
        }
        $overview = \report_evidencereporter\local\marking::get_overview();
        $totals = count($overview) > 1 ? $overview[0]->counts : null;
        $cache->set($key, $totals ?? 0);
        return $totals;
    }

    /**
     * Template data.
     *
     * @param stdClass $totals
     * @return array
     */
    public static function export(stdClass $totals): array {
        [$amber, $red] = \report_evidencereporter\local\marking::get_thresholds();
        $items = [];
        foreach (['tomark', 'feedback', 'notreleased', 'toconfirm', 'ready'] as $key) {
            if (!empty($totals->$key)) {
                $items[] = ['count' => $totals->$key, 'label' => get_string('count_' . $key, 'block_markingnudge'),
                    'key' => $key];
            }
        }
        $overdue = [];
        if (!empty($totals->red)) {
            $overdue[] = ['count' => $totals->red, 'level' => 'red',
                'label' => get_string('count_waiting', 'block_markingnudge', $red)];
        }
        if (!empty($totals->amber)) {
            $overdue[] = ['count' => $totals->amber, 'level' => 'amber',
                'label' => get_string('count_waiting', 'block_markingnudge', $amber)];
        }
        return [
            'items' => $items,
            'hasitems' => (bool) $items,
            'overdue' => $overdue,
            'hasoverdue' => (bool) $overdue,
            'url' => (new moodle_url('/report/evidencereporter/marking.php'))->out(false),
        ];
    }
}
