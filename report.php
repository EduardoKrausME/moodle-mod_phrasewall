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
 * Teacher report for Feedback wall.
 *
 * @package mod_feedbackwall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/tablelib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('feedbackwall', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$feedbackwall = $DB->get_record('feedbackwall', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/feedbackwall:viewreport', $context);

$PAGE->set_url('/mod/feedbackwall/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('report', 'feedbackwall'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('report', 'feedbackwall'));

$sql = "SELECT p.id, p.userid, p.message, p.timecreated, p.timemodified,
               u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
               u.middlename, u.alternatename
          FROM {feedbackwall_posts} p
          JOIN {user} u ON u.id = p.userid
         WHERE p.feedbackwallid = :feedbackwallid
      ORDER BY p.timemodified DESC";
$posts = $DB->get_records_sql($sql, ['feedbackwallid' => $feedbackwall->id]);

$table = new flexible_table('mod-feedbackwall-report-' . $feedbackwall->id);
$table->define_columns(['participant', 'phrase', 'created', 'modified', 'actions']);
$table->define_headers([
    get_string('participant', 'feedbackwall'),
    get_string('phrase', 'feedbackwall'),
    get_string('created', 'feedbackwall'),
    get_string('modified', 'feedbackwall'),
    get_string('actions', 'feedbackwall'),
]);
$table->define_baseurl($PAGE->url);
$table->set_attribute('class', 'generaltable generalbox');
$table->setup();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'feedbackwall'));

if (!empty($feedbackwall->anonymous)) {
    echo $OUTPUT->notification(get_string('reportanonymousnotice', 'feedbackwall'), \core\output\notification::NOTIFY_INFO);
}

foreach ($posts as $post) {
    $userurl = new moodle_url('/user/view.php', ['id' => $post->userid, 'course' => $course->id]);
    $participant = html_writer::link($userurl, fullname($post));
    $deleteurl = new moodle_url('/mod/feedbackwall/delete.php', [
        'id' => $cm->id,
        'postid' => $post->id,
    ]);
    $actions = '';
    if (has_capability('mod/feedbackwall:manageposts', $context)) {
        $actions = $OUTPUT->action_icon(
            $deleteurl,
            new pix_icon('t/delete', get_string('delete', 'feedbackwall'))
        );
    }

    $table->add_data([
        $participant,
        s($post->message),
        userdate($post->timecreated),
        userdate($post->timemodified),
        $actions,
    ]);
}

$table->finish_output();

echo $OUTPUT->footer();
