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
 * Deletes a post from Feedback wall.
 *
 * @package mod_phrasewall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$postid = required_param('postid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('phrasewall', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$phrasewall = $DB->get_record('phrasewall', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/phrasewall:manageposts', $context);

$post = $DB->get_record('phrasewall_posts', [
    'id' => $postid,
    'phrasewallid' => $phrasewall->id,
], '*', MUST_EXIST);

$reporturl = new moodle_url('/mod/phrasewall/report.php', ['id' => $cm->id]);

if ($confirm) {
    require_sesskey();
    $manager = new \mod_phrasewall\manager($phrasewall, $cm, $course, $context);
    $manager->delete_post($post->id);
    redirect(
        $reporturl,
        get_string('postdeleted', 'phrasewall'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_url('/mod/phrasewall/delete.php', ['id' => $cm->id, 'postid' => $post->id]);
$PAGE->set_title(get_string('delete', 'phrasewall'));
$PAGE->set_heading(format_string($course->fullname));

$yesurl = new moodle_url('/mod/phrasewall/delete.php', [
    'id' => $cm->id,
    'postid' => $post->id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('delete', 'phrasewall'));
echo $OUTPUT->confirm(get_string('deleteconfirm', 'phrasewall'), $yesurl, $reporturl);
echo $OUTPUT->footer();
