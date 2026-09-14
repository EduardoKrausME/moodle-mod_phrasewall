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
 * Main wall page.
 *
 * @package mod_feedbackwall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('feedbackwall', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$feedbackwall = $DB->get_record('feedbackwall', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/feedbackwall:view', $context);

$PAGE->set_url('/mod/feedbackwall/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($feedbackwall->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$event = \mod_feedbackwall\event\course_module_viewed::create([
    'objectid' => $feedbackwall->id,
    'context' => $context,
]);
$event->add_record_snapshot('feedbackwall', $feedbackwall);
$event->trigger();

$manager = new \mod_feedbackwall\manager($feedbackwall, $cm, $course, $context);
$currentpost = $manager->get_user_post($USER->id);
$canpost = has_capability('mod/feedbackwall:submit', $context);
$caneditpost = !$currentpost || !empty($feedbackwall->allowedit);

if ($canpost && $caneditpost) {
    $form = new \mod_feedbackwall\form\post_form(null, [
        'cmid' => $cm->id,
        'feedbackwall' => $feedbackwall,
        'post' => $currentpost,
    ]);

    if ($data = $form->get_data()) {
        $manager->save_user_post($USER->id, $data->message);
        redirect(
            new moodle_url('/mod/feedbackwall/view.php', ['id' => $cm->id]),
            get_string('postsaved', 'feedbackwall'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

$posts = $manager->get_wall_posts();
$templateposts = [];
$showauthors = empty($feedbackwall->anonymous);
foreach ($posts as $post) {
    $author = $showauthors ? fullname($post) : get_string('anonymousauthor', 'feedbackwall');
    $initial = $showauthors ? \core_text::strtoupper(\core_text::substr($author, 0, 1)) : '?';
    $templateposts[] = [
        'message' => $post->message,
        'author' => $author,
        'initial' => $initial,
        'showauthor' => $showauthors,
        'time' => userdate($post->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
    ];
}

$reporturl = new moodle_url('/mod/feedbackwall/report.php', ['id' => $cm->id]);
$templatecontext = [
    'posts' => $templateposts,
    'hasposts' => !empty($templateposts),
    'count' => count($templateposts),
    'countlabel' => get_string('responsescount', 'feedbackwall', count($templateposts)),
    'anonymous' => !empty($feedbackwall->anonymous),
    'anonymousnotice' => get_string('anonymousnotice', 'feedbackwall'),
    'noresponses' => get_string('noresponses', 'feedbackwall'),
    'noresponsesdescription' => get_string('noresponsesdescription', 'feedbackwall'),
    'canviewreport' => has_capability('mod/feedbackwall:viewreport', $context),
    'reporturl' => $reporturl->out(false),
    'reportlabel' => get_string('report', 'feedbackwall'),
];

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($feedbackwall->name));

if (!empty($feedbackwall->intro)) {
    echo $OUTPUT->box(format_module_intro('feedbackwall', $feedbackwall, $cm->id), 'generalbox mod_introbox');
}

if ($canpost) {
    if (!$caneditpost) {
        echo $OUTPUT->notification(get_string('cannotedit', 'feedbackwall'), \core\output\notification::NOTIFY_INFO);
    } else {
        $form->display();
    }
}

echo $OUTPUT->render_from_template('mod_feedbackwall/wall', $templatecontext);
echo $OUTPUT->footer();
