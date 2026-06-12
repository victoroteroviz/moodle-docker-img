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
 * Theme almondb language menu flags.
 *
 * @package   theme_almondb
 * @copyright 2022 ThemesAlmond  - http://themesalmond.com
 * @author    ThemesAlmond - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Detect the language code for a language menu item.
 *
 * It first looks at the link "lang"/"hreflang" attributes (present on non active
 * items) and falls back to the code shown between parentheses in the visible text,
 * e.g. "English (en)" -> "en". This second path also covers the active item, which
 * does not carry the attribute.
 *
 * @param array $item The language menu item.
 * @return string The lowercase language code, or '' when it cannot be determined.
 */
function theme_almondb_detect_lang_code(array $item): string {
    if (!empty($item['attributes'])) {
        foreach ($item['attributes'] as $attr) {
            $attr = (array)$attr;
            $key = $attr['key'] ?? '';
            if (($key === 'lang' || $key === 'hreflang') && !empty($attr['value'])) {
                return strtolower($attr['value']);
            }
        }
    }
    $text = (string)($item['text'] ?? '');
    if ($text !== '' && preg_match_all('/\(([A-Za-z0-9_\-]+)\)/', $text, $matches)) {
        return strtolower(end($matches[1]));
    }
    return '';
}

/**
 * Resolve the flag image URL for a given language code.
 *
 * Flags are shipped with the theme in pix/flags/*.svg. A language variant such as
 * "pt_br" falls back to its base language ("pt"), and anything unknown falls back
 * to a generic globe icon so the menu never shows a broken image.
 *
 * @param string $code The language code.
 * @return string The flag image URL.
 */
function theme_almondb_lang_flag_url(string $code): string {
    global $OUTPUT;

    $code = strtolower($code);
    // Flags currently shipped with the theme (add a matching pix/flags/<code>.svg to extend).
    $available = ['en', 'es', 'fr', 'de', 'it', 'pt'];
    $base = preg_replace('/[_\-].*$/', '', $code);

    if (in_array($code, $available, true)) {
        $pick = $code;
    } else if (in_array($base, $available, true)) {
        $pick = $base;
    } else {
        $pick = '_default';
    }

    return $OUTPUT->image_url('flags/' . $pick, 'theme')->out(false);
}

/**
 * Add a flag image URL to each item of the language selection menu.
 *
 * @param mixed $langmenu The exported language menu (array or object), or empty.
 * @return mixed The language menu with a "flagurl" added to every item.
 */
function theme_almondb_add_lang_flags($langmenu) {
    if (empty($langmenu)) {
        return $langmenu;
    }
    $langmenu = (array)$langmenu;
    if (empty($langmenu['items'])) {
        return $langmenu;
    }

    $items = [];
    foreach ($langmenu['items'] as $item) {
        $item = (array)$item;
        $code = theme_almondb_detect_lang_code($item);
        if ($code !== '') {
            $item['flagurl'] = theme_almondb_lang_flag_url($code);
        }
        $items[] = $item;
    }
    $langmenu['items'] = $items;

    return $langmenu;
}

/**
 * Add a flag image URL to the language selector submenu inside the user menu.
 *
 * When a user is logged in the standalone language menu is empty; Moodle instead
 * injects the language selector as a submenu of the user (profile) menu. This walks
 * every submenu and reuses theme_almondb_add_lang_flags() so the language options
 * shown there get the same flags as the ones on the landing page.
 *
 * @param mixed $usermenu The exported user menu (array or object), or empty.
 * @return mixed The user menu with a "flagurl" added to every language submenu item.
 */
function theme_almondb_add_lang_flags_to_usermenu($usermenu) {
    if (empty($usermenu)) {
        return $usermenu;
    }
    $usermenu = (array)$usermenu;
    if (empty($usermenu['submenus'])) {
        return $usermenu;
    }

    $submenus = [];
    foreach ($usermenu['submenus'] as $submenu) {
        // Only language items resolve a code, so this is a no-op for other submenus.
        $submenus[] = theme_almondb_add_lang_flags($submenu);
    }
    $usermenu['submenus'] = array_values($submenus);

    return $usermenu;
}
