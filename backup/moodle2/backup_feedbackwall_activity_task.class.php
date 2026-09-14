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
 * Backup task for Feedback wall.
 *
 * @package mod_feedbackwall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/feedbackwall/backup/moodle2/backup_feedbackwall_stepslib.php');

/**
 * Defines the backup task.
 */
class backup_feedbackwall_activity_task extends backup_activity_task {

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
        $this->add_step(new backup_feedbackwall_activity_structure_step('feedbackwall_structure', 'feedbackwall.xml'));
    }

    /**
     * Encodes content links.
     *
     * @param string $content Content.
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '#');
        $content = preg_replace(
            "#{$base}/mod/feedbackwall/index.php\?id=([0-9]+)#",
            '$@FEEDBACKWALLINDEX*$1@$',
            $content
        );
        return preg_replace(
            "#{$base}/mod/feedbackwall/view.php\?id=([0-9]+)#",
            '$@FEEDBACKWALLVIEWBYID*$1@$',
            $content
        );
    }
}
