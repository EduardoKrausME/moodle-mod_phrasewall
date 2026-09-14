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
 * Privacy provider for Feedback wall.
 *
 * @package mod_phrasewall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_phrasewall\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Describes, exports and deletes user data stored by the plugin.
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describes stored personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('phrasewall_posts', [
            'phrasewallid' => 'privacy:metadata:phrasewall_posts:phrasewallid',
            'userid' => 'privacy:metadata:phrasewall_posts:userid',
            'message' => 'privacy:metadata:phrasewall_posts:message',
            'timecreated' => 'privacy:metadata:phrasewall_posts:timecreated',
            'timemodified' => 'privacy:metadata:phrasewall_posts:timemodified',
        ], 'privacy:metadata:phrasewall_posts');

        return $collection;
    }

    /**
     * Finds module contexts containing data for a user.
     *
     * @param int $userid User ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {phrasewall} f ON f.id = cm.instance
                  JOIN {phrasewall_posts} p ON p.phrasewallid = f.id
                 WHERE ctx.contextlevel = :contextlevel
                   AND p.userid = :userid";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'modname' => 'phrasewall',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Exports a user's post data.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('phrasewall', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $post = $DB->get_record('phrasewall_posts', [
                'phrasewallid' => $cm->instance,
                'userid' => $contextlist->get_user()->id,
            ]);
            if (!$post) {
                continue;
            }

            $data = (object) [
                'message' => $post->message,
                'timecreated' => transform::datetime($post->timecreated),
                'timemodified' => transform::datetime($post->timemodified),
            ];
            writer::with_context($context)->export_data([get_string('privacy:submissionpath', 'phrasewall')], $data);
        }
    }

    /**
     * Deletes all plugin user data in a module context.
     *
     * @param \context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('phrasewall', $context->instanceid);
        if ($cm) {
            $DB->delete_records('phrasewall_posts', ['phrasewallid' => $cm->instance]);
        }
    }

    /**
     * Deletes plugin data for one user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('phrasewall', $context->instanceid);
            if ($cm) {
                $DB->delete_records('phrasewall_posts', [
                    'phrasewallid' => $cm->instance,
                    'userid' => $userid,
                ]);
            }
        }
    }

    /**
     * Adds users with data in the supplied context.
     *
     * @param userlist $userlist User list.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('phrasewall', $context->instanceid);
        if (!$cm) {
            return;
        }

        $sql = "SELECT p.userid
                  FROM {phrasewall_posts} p
                 WHERE p.phrasewallid = :phrasewallid";
        $userlist->add_from_sql('userid', $sql, ['phrasewallid' => $cm->instance]);
    }

    /**
     * Deletes data for selected users in a context.
     *
     * @param approved_userlist $userlist Approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('phrasewall', $context->instanceid);
        $userids = $userlist->get_userids();
        if (!$cm || empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['phrasewallid'] = $cm->instance;
        $DB->delete_records_select(
            'phrasewall_posts',
            "phrasewallid = :phrasewallid AND userid {$insql}",
            $params
        );
    }
}
