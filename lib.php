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
 * Public callbacks for Feedback wall.
 *
 * @package mod_feedbackwall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Declares the features supported by the activity.
 *
 * @param string $feature Feature constant.
 * @return mixed
 */
function feedbackwall_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_COLLABORATION,
        default => null,
    };
}

/**
 * Creates an activity instance.
 *
 * @param stdClass $feedbackwall Form data.
 * @param mod_feedbackwall_mod_form|null $mform Form instance.
 * @return int New instance ID.
 */
function feedbackwall_add_instance($feedbackwall, $mform = null) {
    global $DB;

    $now = time();
    $feedbackwall->timecreated = $now;
    $feedbackwall->timemodified = $now;
    $feedbackwall->completionsubmit = empty($feedbackwall->completionsubmit) ? 0 : 1;

    return $DB->insert_record('feedbackwall', $feedbackwall);
}

/**
 * Updates an activity instance.
 *
 * @param stdClass $feedbackwall Form data.
 * @param mod_feedbackwall_mod_form|null $mform Form instance.
 * @return bool
 */
function feedbackwall_update_instance($feedbackwall, $mform = null) {
    global $DB;

    $feedbackwall->id = $feedbackwall->instance;
    $feedbackwall->timemodified = time();
    $feedbackwall->completionsubmit = empty($feedbackwall->completionsubmit) ? 0 : 1;

    return $DB->update_record('feedbackwall', $feedbackwall);
}

/**
 * Deletes an activity instance and its posts.
 *
 * @param int $id Instance ID.
 * @return bool
 */
function feedbackwall_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('feedbackwall', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('feedbackwall_posts', ['feedbackwallid' => $id]);
    $DB->delete_records('feedbackwall', ['id' => $id]);

    return true;
}

/**
 * Adds cached information used by course listings and completion.
 *
 * @param stdClass $coursemodule Course module record.
 * @return cached_cm_info|false
 */
function feedbackwall_get_coursemodule_info($coursemodule) {
    global $DB;

    $feedbackwall = $DB->get_record(
        'feedbackwall',
        ['id' => $coursemodule->instance],
        'id,name,intro,introformat,completionsubmit'
    );
    if (!$feedbackwall) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $feedbackwall->name;

    if ($coursemodule->showdescription) {
        $result->content = format_module_intro('feedbackwall', $feedbackwall, $coursemodule->id, false);
    }

    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionsubmit'] = $feedbackwall->completionsubmit;
    }

    return $result;
}

/**
 * Returns descriptions for active custom completion rules.
 *
 * @param cm_info|stdClass $cm Course module info.
 * @return array
 */
function feedbackwall_get_completion_active_rule_descriptions($cm) {
    if (empty($cm->customdata['customcompletionrules']) || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    if (!empty($cm->customdata['customcompletionrules']['completionsubmit'])) {
        $descriptions[] = get_string('completionsubmit', 'feedbackwall');
    }

    return $descriptions;
}

/**
 * Adds the report link to module settings navigation.
 *
 * @param settings_navigation $settings Settings navigation.
 * @param navigation_node $navref Module node.
 * @return void
 */
function feedbackwall_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    $cm = $settings->get_page()->cm;
    if (!$cm || !has_capability('mod/feedbackwall:viewreport', $cm->context)) {
        return;
    }

    $url = new moodle_url('/mod/feedbackwall/report.php', ['id' => $cm->id]);
    $navref->add(get_string('report', 'feedbackwall'), $url, navigation_node::TYPE_SETTING);
}
