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
 * Theme almondb dynamic "HTML + image" blocks.
 *
 * The administrator chooses how many blocks to create. Each block has an
 * uploadable image and a free HTML editor; the image is inserted wherever the
 * [[image]] placeholder is used inside the HTML. Each block can be positioned
 * independently from the drag and drop block order list.
 *
 * @package   theme_almondb
 * @copyright 2022 ThemesAlmond  - http://themesalmond.com
 * @author    ThemesAlmond - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/theme/almondb/settings/htmllayout_setting.php');
// Section info.
$name = 'theme_almondb/blockhtmlinfo';
$heading = get_string('blockhtmlinfo', 'theme_almondb');
$information = get_string('blockhtmlinfodesc', 'theme_almondb');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
// How many HTML + image blocks.
$name = 'theme_almondb/blockhtmlcount';
$title = get_string('blockhtmlcount', 'theme_almondb');
$description = get_string('blockhtmlcountdesc', 'theme_almondb');
$default = 0;
$options = [];
for ($i = 0; $i <= 10; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Image, text and drag-and-drop layout per configured block.
$count = (int)get_config('theme_almondb', 'blockhtmlcount');
for ($i = 1; $i <= $count; $i++) {
    // Per-block sub heading so each block reads as its own card.
    $name = 'theme_almondb/blockhtmlheading' . $i;
    $heading = get_string('blockhtmlblockheading', 'theme_almondb', $i);
    $setting = new admin_setting_heading($name, $heading, '');
    $page->add($setting);
    // Block image (one tile).
    $name = 'theme_almondb/blockhtmlimg' . $i;
    $title = get_string('blockhtmlimg', 'theme_almondb', $i);
    $description = get_string('blockhtmlimgdesc', 'theme_almondb');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'blockhtmlimg' . $i);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Block text (the other tile).
    $name = 'theme_almondb/blockhtmltext' . $i;
    $title = get_string('blockhtmltext', 'theme_almondb', $i);
    $description = get_string('blockhtmltextdesc', 'theme_almondb');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Drag-and-drop tile layout (image left/right/top/bottom).
    $name = 'theme_almondb/blockhtmllayout' . $i;
    $title = get_string('blockhtmllayout', 'theme_almondb', $i);
    $description = get_string('blockhtmllayoutdesc', 'theme_almondb');
    $setting = new admin_setting_almondb_htmllayout($name, $title, $description, 'image-left');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
}
