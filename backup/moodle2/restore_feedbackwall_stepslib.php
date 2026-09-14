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
 * Restore structure for Feedback wall.
 *
 * @package mod_feedbackwall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_feedbackwall_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines restore paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [new restore_path_element('feedbackwall', '/activity/feedbackwall')];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('feedbackwall_post', '/activity/feedbackwall/posts/post');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the activity record.
     *
     * @param array $data Restored data.
     * @return void
     */
    protected function process_feedbackwall($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('feedbackwall', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores one student post.
     *
     * @param array $data Restored data.
     * @return void
     */
    protected function process_feedbackwall_post($data) {
        global $DB;

        $data = (object) $data;
        $data->feedbackwallid = $this->get_new_parentid('feedbackwall');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $DB->insert_record('feedbackwall_posts', $data);
    }

    /**
     * Restores introduction files.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_feedbackwall', 'intro', null);
    }
}
