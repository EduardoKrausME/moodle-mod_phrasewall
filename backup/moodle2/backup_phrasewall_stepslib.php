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
 * Backup structure for Feedback wall.
 *
 * @package mod_phrasewall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines activity data included in backups.
 */
class backup_phrasewall_activity_structure_step extends backup_activity_structure_step {

    /**
     * Builds the backup structure tree.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $phrasewall = new backup_nested_element('phrasewall', ['id'], [
            'name', 'intro', 'introformat', 'anonymous', 'maxchars', 'allowedit', 'completionsubmit',
            'timecreated', 'timemodified',
        ]);
        $posts = new backup_nested_element('posts');
        $post = new backup_nested_element('post', ['id'], [
            'userid', 'message', 'timecreated', 'timemodified',
        ]);

        $phrasewall->add_child($posts);
        $posts->add_child($post);

        $phrasewall->set_source_table('phrasewall', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $post->set_source_table('phrasewall_posts', ['phrasewallid' => backup::VAR_PARENTID]);
        }

        $post->annotate_ids('user', 'userid');
        $phrasewall->annotate_files('mod_phrasewall', 'intro', null);

        return $this->prepare_activity_structure($phrasewall);
    }
}
