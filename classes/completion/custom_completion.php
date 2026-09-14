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
 * Custom completion rules for Feedback wall.
 *
 * @package mod_feedbackwall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_feedbackwall\completion;

use core_completion\activity_custom_completion;

/**
 * Marks the activity complete after the user posts a sentence.
 */
class custom_completion extends activity_custom_completion {

    /**
     * Returns the state for a completion rule.
     *
     * @param string $rule Rule name.
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        if ($rule !== 'completionsubmit') {
            return COMPLETION_UNKNOWN;
        }

        $submitted = $DB->record_exists('feedbackwall_posts', [
            'feedbackwallid' => $this->cm->instance,
            'userid' => $this->userid,
        ]);

        return $submitted ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Returns custom rule identifiers.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionsubmit'];
    }

    /**
     * Returns descriptions for custom rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return ['completionsubmit' => get_string('completionsubmit', 'feedbackwall')];
    }

    /**
     * Returns rule display order.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return ['completionsubmit'];
    }
}
