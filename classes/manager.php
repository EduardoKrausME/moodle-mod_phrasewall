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
 * Domain operations for Feedback wall.
 *
 * @package mod_phrasewall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_phrasewall;

/**
 * Handles posts and completion state for one activity instance.
 */
class manager {

    /** @var \stdClass Activity record. */
    private $phrasewall;

    /** @var \cm_info|\stdClass Course module. */
    private $cm;

    /** @var \stdClass Course record. */
    private $course;

    /** @var \context_module Module context. */
    private $context;

    /**
     * Constructor.
     *
     * @param \stdClass $phrasewall Activity record.
     * @param \cm_info|\stdClass $cm Course module.
     * @param \stdClass $course Course record.
     * @param \context_module $context Module context.
     */
    public function __construct($phrasewall, $cm, $course, $context) {
        $this->phrasewall = $phrasewall;
        $this->cm = $cm;
        $this->course = $course;
        $this->context = $context;
    }

    /**
     * Returns a user's existing post.
     *
     * @param int $userid User ID.
     * @return \stdClass|false
     */
    public function get_user_post(int $userid) {
        global $DB;

        return $DB->get_record('phrasewall_posts', [
            'phrasewallid' => $this->phrasewall->id,
            'userid' => $userid,
        ]);
    }

    /**
     * Creates or updates the current user's post.
     *
     * @param int $userid User ID.
     * @param string $message Sentence.
     * @return int Post ID.
     */
    public function save_user_post(int $userid, string $message): int {
        global $DB;

        require_capability('mod/phrasewall:submit', $this->context, $userid);

        $message = trim(clean_param($message, PARAM_TEXT));
        if ($message === '') {
            throw new \moodle_exception('errorempty', 'phrasewall');
        }
        if (\core_text::strlen($message) > (int) $this->phrasewall->maxchars) {
            throw new \moodle_exception('errormaxchars', 'phrasewall', '', $this->phrasewall->maxchars);
        }

        $existing = $this->get_user_post($userid);
        $now = time();

        if ($existing) {
            if (empty($this->phrasewall->allowedit)) {
                throw new \moodle_exception('cannotedit', 'phrasewall');
            }
            $existing->message = $message;
            $existing->timemodified = $now;
            $DB->update_record('phrasewall_posts', $existing);
            $postid = (int) $existing->id;
        } else {
            $post = (object) [
                'phrasewallid' => $this->phrasewall->id,
                'userid' => $userid,
                'message' => $message,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $postid = $DB->insert_record('phrasewall_posts', $post);
        }

        $event = \mod_phrasewall\event\post_submitted::create([
            'objectid' => $postid,
            'context' => $this->context,
            'relateduserid' => $userid,
            'other' => ['phrasewallid' => $this->phrasewall->id],
        ]);
        $event->trigger();

        $this->refresh_completion($userid);
        return $postid;
    }

    /**
     * Returns all wall posts with user name fields.
     *
     * @return array
     */
    public function get_wall_posts(): array {
        global $DB;

        $namefields = \core_user\fields::for_name()->get_sql('u', false, '', '', true)->selects;
        $sql = "SELECT p.id, p.userid, p.message, p.timecreated, p.timemodified {$namefields}
                  FROM {phrasewall_posts} p
                  JOIN {user} u ON u.id = p.userid
                 WHERE p.phrasewallid = :phrasewallid
              ORDER BY p.timecreated ASC";

        return $DB->get_records_sql($sql, ['phrasewallid' => $this->phrasewall->id]);
    }

    /**
     * Deletes one post and updates completion for its owner.
     *
     * @param int $postid Post ID.
     * @return void
     */
    public function delete_post(int $postid): void {
        global $DB;

        require_capability('mod/phrasewall:manageposts', $this->context);
        $post = $DB->get_record('phrasewall_posts', [
            'id' => $postid,
            'phrasewallid' => $this->phrasewall->id,
        ], '*', MUST_EXIST);

        $DB->delete_records('phrasewall_posts', ['id' => $post->id]);
        $this->refresh_completion((int) $post->userid);
    }

    /**
     * Recalculates activity completion after a post change.
     *
     * @param int $userid User ID.
     * @return void
     */
    private function refresh_completion(int $userid): void {
        $completion = new \completion_info($this->course);
        if (!$completion->is_enabled($this->cm)) {
            return;
        }

        $completion->update_state($this->cm, COMPLETION_UNKNOWN, $userid);
    }
}
