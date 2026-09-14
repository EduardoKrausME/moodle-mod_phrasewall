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
 * Post submitted event.
 *
 * @package mod_feedbackwall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_feedbackwall\event;

/**
 * Event fired after a student creates or updates a sentence.
 */
class post_submitted extends \core\event\base {

    /**
     * Initializes event data.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'feedbackwall_posts';
    }

    /**
     * Returns the event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventpostsubmitted', 'feedbackwall');
    }

    /**
     * Returns a localized description.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' submitted or updated post '{$this->objectid}' " .
            "in the Feedback wall activity with id '{$this->other['feedbackwallid']}'.";
    }

    /**
     * Returns the related activity URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/feedbackwall/view.php', ['id' => $this->contextinstanceid]);
    }
}
