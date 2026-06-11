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
 * Theme almondb frontpage blocks.
 *
 * The frontpage block configuration is split into three separate tabs so the
 * settings are easier to scan instead of one long crowded page:
 *   1. Order   - drag and drop list that controls where every block appears.
 *   2. Content - the fixed numeric content blocks (title box, icons, ...).
 *   3. HTML    - the dynamic "HTML + image" blocks the admin can add/remove.
 *
 * @package   theme_almondb
 * @copyright 2022 ThemesAlmond  - http://themesalmond.com
 * @author    ThemesAlmond - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('blockorder_setting.php');

// ---------------------------------------------------------------------------
// Tab 1: Block order (drag and drop control panel).
// ---------------------------------------------------------------------------
$page = new admin_settingpage('theme_almondb_blockorder',
    get_string('theme_almondb_blockordertab', 'theme_almondb'));
$page->add(
    new admin_setting_heading(
        'theme_almondb_blockorderintro',
        get_string('theme_almondb_blockorderheading', 'theme_almondb'),
        get_string('theme_almondb_blockorderintrodesc', 'theme_almondb')
    )
);
$page->add(
    new admin_setting_almondb_blockorder(
        'theme_almondb/blockorder',
        get_string('blockorder', 'theme_almondb'),
        get_string('blockorderdesc', 'theme_almondb')
    )
);
$settings->add($page);

// ---------------------------------------------------------------------------
// Tab 2: Fixed content blocks.
// ---------------------------------------------------------------------------
$page = new admin_settingpage('theme_almondb_blockcontent',
    get_string('theme_almondb_blockcontenttab', 'theme_almondb'));
$page->add(
    new admin_setting_heading(
        'theme_almondb_blockcontentintro',
        get_string('theme_almondb_blockcontentheading', 'theme_almondb'),
        get_string('theme_almondb_blockcontentintrodesc', 'theme_almondb')
    )
);
require('blocks/block01.php');
require('blocks/block02.php');
require('blocks/block03.php');
require('blocks/block04.php');
require('blocks/block05.php');
require('blocks/block06.php');
require('blocks/block07.php');
require('blocks/block08.php');
require('blocks/block09.php');
require('blocks/block10.php');
require('blocks/block11.php');
require('blocks/block18.php');
require('blocks/block19.php');
require('blocks/block20.php');
$settings->add($page);

// ---------------------------------------------------------------------------
// Tab 3: Dynamic HTML + image blocks (add/remove your own).
// ---------------------------------------------------------------------------
$page = new admin_settingpage('theme_almondb_blockhtml',
    get_string('theme_almondb_blockhtmltab', 'theme_almondb'));
require('blocks/htmlblocks.php');
$settings->add($page);
