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
 * Theme almondb intro video settings.
 *
 * @package   theme_almondb
 * @copyright 2022 ThemesAlmond  - http://themesalmond.com
 * @author    ThemesAlmond - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('theme_almondb_videointro', get_string('videointro', 'theme_almondb'));

// Heading.
$page->add(
    new admin_setting_heading(
        'theme_almondb_videointroheading',
        get_string('videointroheading', 'theme_almondb'),
        format_text(
            get_string('videointroheadingdesc', 'theme_almondb'),
            FORMAT_MARKDOWN
        )
    )
);

// Enable or disable the intro video.
$name = 'theme_almondb/videointroenabled';
$title = get_string('videointroenabled', 'theme_almondb');
$description = get_string('videointroenableddesc', 'theme_almondb');
$setting = new admin_setting_configcheckbox($name, $title, $description, 0);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Uploaded video file (takes priority over the URL).
$name = 'theme_almondb/videointrofile';
$title = get_string('videointrofile', 'theme_almondb');
$description = get_string('videointrofiledesc', 'theme_almondb');
$opts = ['accepted_types' => ['.mp4', '.webm', '.ogg', '.ogv', '.mov'], 'maxfiles' => 1];
$setting = new admin_setting_configstoredfile($name, $title, $description, 'videointrofile', 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// External video URL (used only when no file is uploaded).
$name = 'theme_almondb/videointrourl';
$title = get_string('videointrourl', 'theme_almondb');
$description = get_string('videointrourldesc', 'theme_almondb');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$page->add($setting);

// Optional poster image.
$name = 'theme_almondb/videointroposter';
$title = get_string('videointroposter', 'theme_almondb');
$description = get_string('videointroposterdesc', 'theme_almondb');
$opts = ['accepted_types' => ['.png', '.jpg', '.jpeg', '.gif', '.webp'], 'maxfiles' => 1];
$setting = new admin_setting_configstoredfile($name, $title, $description, 'videointroposter', 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Autoplay.
$name = 'theme_almondb/videointroautoplay';
$title = get_string('videointroautoplay', 'theme_almondb');
$description = get_string('videointroautoplaydesc', 'theme_almondb');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$page->add($setting);

// Loop.
$name = 'theme_almondb/videointroloop';
$title = get_string('videointroloop', 'theme_almondb');
$description = get_string('videointroloopdesc', 'theme_almondb');
$setting = new admin_setting_configcheckbox($name, $title, $description, 0);
$page->add($setting);

// Show only once per browser session.
$name = 'theme_almondb/videointroonce';
$title = get_string('videointroonce', 'theme_almondb');
$description = get_string('videointrooncedesc', 'theme_almondb');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$page->add($setting);

$settings->add($page);
