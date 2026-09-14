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
 * Activity settings form for Feedback wall.
 *
 * @package mod_phrasewall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity creation and editing form.
 */
class mod_phrasewall_mod_form extends moodleform_mod {

    /**
     * Defines activity fields.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name', 'phrasewall'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement("html", html_writer::tag("h3", get_string('settingsheader', 'phrasewall')));

        $options = [
            0 => get_string('named', 'phrasewall'),
            1 => get_string('anonymous', 'phrasewall'),
        ];
        $mform->addElement('select', 'anonymous', get_string('anonymity', 'phrasewall'), $options);
        $mform->addHelpButton('anonymous', 'anonymity', 'phrasewall');
        $mform->setDefault('anonymous', 0);

        $charoptions = [
            60 => '60',
            100 => '100',
            140 => '140',
            160 => '160',
            200 => '200',
            280 => '280',
        ];
        $mform->addElement('select', 'maxchars', get_string('maxchars', 'phrasewall'), $charoptions);
        $mform->addHelpButton('maxchars', 'maxchars', 'phrasewall');
        $mform->setDefault('maxchars', 160);

        $mform->addElement('advcheckbox', 'allowedit', get_string('allowedit', 'phrasewall'));
        $mform->addHelpButton('allowedit', 'allowedit', 'phrasewall');
        $mform->setDefault('allowedit', 1);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Adds the custom completion rule.
     *
     * @return array
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();
        $elementname = 'completionsubmit' . $suffix;

        $mform->addElement('checkbox', $elementname, '', get_string('completionsubmit', 'phrasewall'));
        $mform->setDefault($elementname, 1);

        return [$elementname];
    }

    /**
     * Indicates whether the custom completion rule is enabled.
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        return !empty($data['completionsubmit' . $suffix]);
    }

    /**
     * Normalizes completion values before save.
     *
     * @param stdClass $data Form data.
     * @return void
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completionfield = 'completion' . $suffix;
            $rulefield = 'completionsubmit' . $suffix;
            $automatic = !empty($data->{$completionfield}) && $data->{$completionfield} == COMPLETION_TRACKING_AUTOMATIC;
            if (!$automatic || empty($data->{$rulefield})) {
                $data->{$rulefield} = 0;
            }
        }
    }
}
