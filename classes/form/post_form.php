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
 * Student post form.
 *
 * @package mod_phrasewall
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_phrasewall\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form used to create or update the user's sentence.
 */
class post_form extends \moodleform {

    /**
     * Defines form fields.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $phrasewall = $this->_customdata['phrasewall'];
        $post = $this->_customdata['post'] ?? null;

        $mform->addElement('text', 'message', get_string('yourphrase', 'phrasewall'), [
            'maxlength' => (int) $phrasewall->maxchars,
            'size' => min(80, (int) $phrasewall->maxchars),
            'placeholder' => get_string('phraseplaceholder', 'phrasewall'),
            'autocomplete' => 'off',
        ]);
        $mform->setType('message', PARAM_TEXT);
        $mform->addRule('message', null, 'required', null, 'client');
        $mform->addRule(
            'message',
            get_string('errormaxchars', 'phrasewall', $phrasewall->maxchars),
            'maxlength',
            $phrasewall->maxchars,
            'client'
        );
        $mform->addHelpButton('message', 'yourphrase', 'phrasewall');

        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);

        if ($post) {
            $mform->setDefault('message', $post->message);
        }

        $buttonlabel = $post ? get_string('updatephrase', 'phrasewall') : get_string('submitphrase', 'phrasewall');
        $this->add_action_buttons(false, $buttonlabel);
    }

    /**
     * Validates sentence length on the server.
     *
     * @param array $data Submitted values.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $phrasewall = $this->_customdata['phrasewall'];
        $message = trim((string) ($data['message'] ?? ''));

        if ($message === '') {
            $errors['message'] = get_string('errorempty', 'phrasewall');
        } else if (\core_text::strlen($message) > (int) $phrasewall->maxchars) {
            $errors['message'] = get_string('errormaxchars', 'phrasewall', $phrasewall->maxchars);
        }

        return $errors;
    }
}
