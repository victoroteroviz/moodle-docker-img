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
 * Build an embeddable iframe URL for known video providers (YouTube, Vimeo).
 *
 * Direct video files are not handled here; this only covers links that cannot be
 * played in a plain <video> element and must use an <iframe> instead.
 *
 * @param string $url The external video URL pasted in the settings.
 * @param bool $autoplay Whether the video should start automatically.
 * @param bool $loop Whether the video should loop.
 * @return string|null The iframe src URL, or null when the URL is not a known provider.
 */
function theme_almondb_videointro_embed_url(string $url, bool $autoplay, bool $loop): ?string {
    // YouTube: watch?v=, youtu.be/, embed/, shorts/, v/.
    if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
        $id = $m[1];
        $params = ['rel' => 0, 'playsinline' => 1];
        if ($autoplay) {
            $params['autoplay'] = 1;
            $params['mute'] = 1;
        }
        if ($loop) {
            $params['loop'] = 1;
            $params['playlist'] = $id;
        }
        return 'https://www.youtube.com/embed/' . $id . '?' . http_build_query($params);
    }

    // Vimeo: vimeo.com/ID or player.vimeo.com/video/ID.
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
        $id = $m[1];
        $params = [];
        if ($autoplay) {
            $params['autoplay'] = 1;
            $params['muted'] = 1;
        }
        if ($loop) {
            $params['loop'] = 1;
        }
        $query = $params ? '?' . http_build_query($params) : '';
        return 'https://player.vimeo.com/video/' . $id . $query;
    }

    return null;
}

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

    $autoplay = !empty($theme->settings->videointroautoplay);
    $loop = !empty($theme->settings->videointroloop);

    // The uploaded file always takes priority and is played as a direct file.
    $fileurl = $theme->setting_file_url('videointrofile', 'videointrofile');
    $externalurl = isset($theme->settings->videointrourl) ? trim($theme->settings->videointrourl) : '';

    if (!empty($fileurl)) {
        $templatecontext['videointrourl'] = $fileurl;
        theme_almondb_videointro_set_mime($templatecontext, (string)$fileurl);
    } else if ($externalurl !== '') {
        // External link: embed YouTube/Vimeo via iframe, otherwise treat it as a direct file.
        $embedurl = theme_almondb_videointro_embed_url($externalurl, $autoplay, $loop);
        if ($embedurl !== null) {
            $templatecontext['videointroembed'] = true;
            $templatecontext['videointroembedurl'] = $embedurl;
        } else {
            $templatecontext['videointrourl'] = $externalurl;
            theme_almondb_videointro_set_mime($templatecontext, $externalurl);
        }
    } else {
        // Nothing configured.
        $templatecontext['videointroenabled'] = false;
        return $templatecontext;
    }

    $templatecontext['videointroposter'] = $theme->setting_file_url('videointroposter', 'videointroposter');
    $templatecontext['videointroautoplay'] = $autoplay;
    $templatecontext['videointroloop'] = $loop;
    $templatecontext['videointroonce'] = !empty($theme->settings->videointroonce);

    return $templatecontext;
}

/**
 * Add a best-effort MIME type (from the file extension) for a direct video source.
 *
 * @param array $templatecontext The template context, modified by reference.
 * @param string $url The video URL.
 * @return void
 */
function theme_almondb_videointro_set_mime(array &$templatecontext, string $url) {
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
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
}
