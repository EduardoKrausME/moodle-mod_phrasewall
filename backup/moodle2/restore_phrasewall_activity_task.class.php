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
 * Restore task for Feedback wall.
 *
 * @package mod_phrasewall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the restore task.
 */
class restore_phrasewall_activity_task extends restore_activity_task {

    /**
     * No module-specific settings are required.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Adds the structure step.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_phrasewall_activity_structure_step('phrasewall_structure', 'phrasewall.xml'));
    }

    /**
     * Decodes content links.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('phrasewall', ['intro'], 'phrasewall')];
    }

    /**
     * Defines link decoding rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('PHRASEWALLVIEWBYID', '/mod/phrasewall/view.php?id=$1', 'course_module'),
            new restore_decode_rule('PHRASEWALLINDEX', '/mod/phrasewall/index.php?id=$1', 'course'),
        ];
    }
}
