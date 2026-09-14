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
 * @package mod_feedbackwall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_feedbackwall;

/**
 * Handles posts and completion state for one activity instance.
 */
class manager {

    /** @var \stdClass Activity record. */
    private $feedbackwall;

    /** @var \cm_info|\stdClass Course module. */
    private $cm;

    /** @var \stdClass Course record. */
    private $course;

    /** @var \context_module Module context. */
    private $context;

    /**
     * Constructor.
     *
     * @param \stdClass $feedbackwall Activity record.
     * @param \cm_info|\stdClass $cm Course module.
     * @param \stdClass $course Course record.
     * @param \context_module $context Module context.
     */
    public function __construct($feedbackwall, $cm, $course, $context) {
        $this->feedbackwall = $feedbackwall;
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

        return $DB->get_record('feedbackwall_posts', [
            'feedbackwallid' => $this->feedbackwall->id,
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

        require_capability('mod/feedbackwall:submit', $this->context, $userid);

        $message = trim(clean_param($message, PARAM_TEXT));
        if ($message === '') {
            throw new \moodle_exception('errorempty', 'feedbackwall');
        }
        if (\core_text::strlen($message) > (int) $this->feedbackwall->maxchars) {
            throw new \moodle_exception('errormaxchars', 'feedbackwall', '', $this->feedbackwall->maxchars);
        }

        $existing = $this->get_user_post($userid);
        $now = time();

        if ($existing) {
            if (empty($this->feedbackwall->allowedit)) {
                throw new \moodle_exception('cannotedit', 'feedbackwall');
            }
            $existing->message = $message;
            $existing->timemodified = $now;
            $DB->update_record('feedbackwall_posts', $existing);
            $postid = (int) $existing->id;
        } else {
            $post = (object) [
                'feedbackwallid' => $this->feedbackwall->id,
                'userid' => $userid,
                'message' => $message,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $postid = $DB->insert_record('feedbackwall_posts', $post);
        }

        $event = \mod_feedbackwall\event\post_submitted::create([
            'objectid' => $postid,
            'context' => $this->context,
            'relateduserid' => $userid,
            'other' => ['feedbackwallid' => $this->feedbackwall->id],
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
                  FROM {feedbackwall_posts} p
                  JOIN {user} u ON u.id = p.userid
                 WHERE p.feedbackwallid = :feedbackwallid
              ORDER BY p.timecreated ASC";

        return $DB->get_records_sql($sql, ['feedbackwallid' => $this->feedbackwall->id]);
    }

    /**
     * Deletes one post and updates completion for its owner.
     *
     * @param int $postid Post ID.
     * @return void
     */
    public function delete_post(int $postid): void {
        global $DB;

        require_capability('mod/feedbackwall:manageposts', $this->context);
        $post = $DB->get_record('feedbackwall_posts', [
            'id' => $postid,
            'feedbackwallid' => $this->feedbackwall->id,
        ], '*', MUST_EXIST);

        $DB->delete_records('feedbackwall_posts', ['id' => $post->id]);
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
