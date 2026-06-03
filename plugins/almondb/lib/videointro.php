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
 * Theme almondb intro video.
 *
 * @package   theme_almondb
 * @copyright 2022 ThemesAlmond  - http://themesalmond.com
 * @author    ThemesAlmond - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Build the template context for the front page intro video.
 *
 * The video is only meant to be shown to visitors without an active session;
 * that visibility check is done in the template using the "userlogin" flag.
 *
 * @return array
 */
function theme_almondb_videointro() {
    $theme = theme_config::load('almondb');
    $templatecontext = [];

    $templatecontext['videointroenabled'] = !empty($theme->settings->videointroenabled);
    if (empty($templatecontext['videointroenabled'])) {
        return $templatecontext;
    }

    // The uploaded file takes priority over the external URL.
    $videourl = $theme->setting_file_url('videointrofile', 'videointrofile');
    if (empty($videourl) && !empty($theme->settings->videointrourl)) {
        $videourl = $theme->settings->videointrourl;
    }

    // Without a source there is nothing to show.
    if (empty($videourl)) {
        $templatecontext['videointroenabled'] = false;
        return $templatecontext;
    }

    $templatecontext['videointrourl'] = $videourl;

    // Best-effort MIME type from the file extension so the browser knows how to handle the source.
    $ext = strtolower(pathinfo(parse_url((string)$videourl, PHP_URL_PATH), PATHINFO_EXTENSION));
    $mimemap = [
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
        'ogv' => 'video/ogg',
        'ogg' => 'video/ogg',
    ];
    if (isset($mimemap[$ext])) {
        $templatecontext['videointromime'] = $mimemap[$ext];
    }

    $templatecontext['videointroposter'] = $theme->setting_file_url('videointroposter', 'videointroposter');
    $templatecontext['videointroautoplay'] = !empty($theme->settings->videointroautoplay);
    $templatecontext['videointroloop'] = !empty($theme->settings->videointroloop);
    $templatecontext['videointroonce'] = !empty($theme->settings->videointroonce);

    return $templatecontext;
}
