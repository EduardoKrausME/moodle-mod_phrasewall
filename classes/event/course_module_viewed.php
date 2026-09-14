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
 * Course module viewed event.
 *
 * @package mod_feedbackwall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_feedbackwall\event;

/**
 * Event fired when the wall is viewed.
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Initializes event data.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'feedbackwall';
        parent::init();
    }

    /**
     * Returns the event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcoursemoduleviewed', 'feedbackwall');
    }

    /**
     * Returns the object URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/feedbackwall/view.php', ['id' => $this->contextinstanceid]);
    }
}
