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
 * Theme almondb "HTML + image" block layout setting.
 *
 * Custom admin setting that lets the administrator arrange the image and text
 * tiles of a dynamic HTML block by dragging them, and choose whether they are
 * split horizontally (side by side) or vertically (stacked). The result is
 * stored as one of the tokens returned by theme_almondb_htmllayout_options():
 * image-left, image-right, image-top, image-bottom.
 *
 * @package   theme_almondb
 * @copyright 2022 ThemesAlmond  - http://themesalmond.com
 * @author    ThemesAlmond - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Only declare the admin setting class when the base class is available, i.e.
// when included from the theme settings pages (not from the frontpage layout).
if (!class_exists('admin_setting')) {
    return;
}

/**
 * Admin setting: drag and drop arrangement of an HTML block's image/text tiles.
 */
class admin_setting_almondb_htmllayout extends admin_setting {

    /**
     * Constructor.
     *
     * @param string $name Unqualified setting name (stored under theme_almondb).
     * @param string $visiblename Localised label.
     * @param string $description Localised help text.
     * @param string $defaultsetting Default layout token.
     */
    public function __construct($name, $visiblename, $description, $defaultsetting = 'image-left') {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Return the current, validated setting value.
     *
     * @return string|null Layout token, or null if unset.
     */
    public function get_setting() {
        $value = $this->config_read($this->name);
        if (is_null($value)) {
            return null;
        }
        return theme_almondb_htmllayout_normalise($value);
    }

    /**
     * Store a submitted layout token.
     *
     * @param string $data Layout token from the form.
     * @return string Empty string on success.
     */
    public function write_setting($data) {
        $normalised = theme_almondb_htmllayout_normalise($data);
        return ($this->config_write($this->name, $normalised) ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Render the draggable image/text tiles, the split toggle and a live preview.
     *
     * @param string $data Current setting value.
     * @param string $query Search query (unused).
     * @return string HTML to embed in the settings form.
     */
    public function output_html($data, $query = '') {
        $layout = theme_almondb_htmllayout_normalise($data);
        $inputid = $this->get_id();
        $gridid = $inputid . '_grid';
        $toggleid = $inputid . '_vertical';

        // Derive the initial tile order and orientation from the stored token.
        $imagefirst = ($layout === 'image-left' || $layout === 'image-top');
        $vertical = ($layout === 'image-top' || $layout === 'image-bottom');

        $imglabel = get_string('blockhtmllayout_image', 'theme_almondb');
        $txtlabel = get_string('blockhtmllayout_text', 'theme_almondb');

        $imgtile = html_writer::tag('div',
            html_writer::tag('i', '', ['class' => 'fa fa-image', 'aria-hidden' => 'true']) .
            html_writer::tag('span', s($imglabel)),
            ['class' => 'hb-layout-tile hb-tile-image', 'draggable' => 'true', 'data-tile' => 'image']);
        $txttile = html_writer::tag('div',
            html_writer::tag('i', '', ['class' => 'fa fa-align-left', 'aria-hidden' => 'true']) .
            html_writer::tag('span', s($txtlabel)),
            ['class' => 'hb-layout-tile hb-tile-text', 'draggable' => 'true', 'data-tile' => 'text']);

        $tiles = $imagefirst ? ($imgtile . $txttile) : ($txttile . $imgtile);
        $grid = html_writer::tag('div', $tiles, [
            'id' => $gridid,
            'class' => 'hb-layout-grid' . ($vertical ? ' is-vertical' : ''),
        ]);

        $toggle = html_writer::tag('label',
            html_writer::empty_tag('input', [
                'type' => 'checkbox',
                'id' => $toggleid,
                'class' => 'me-2',
            ] + ($vertical ? ['checked' => 'checked'] : [])) .
            html_writer::tag('span', s(get_string('blockhtmllayout_vertical', 'theme_almondb'))),
            ['class' => 'hb-layout-toggle d-inline-flex align-items-center mt-2', 'for' => $toggleid]);

        $hidden = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'id' => $inputid,
            'name' => $this->get_full_name(),
            'value' => $layout,
        ]);

        $css = <<<CSS
<style>
.hb-layout-grid { display: flex; gap: .5rem; max-width: 420px; }
.hb-layout-grid.is-vertical { flex-direction: column; }
.hb-layout-tile { flex: 1 1 0; display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: .35rem; min-height: 78px; padding: .75rem; border-radius: .5rem; cursor: grab; user-select: none;
    color: #fff; font-weight: 600; text-align: center; box-shadow: inset 0 0 0 1px rgba(0,0,0,.08); }
.hb-layout-tile:active { cursor: grabbing; }
.hb-layout-tile i { font-size: 1.25rem; }
.hb-tile-image { background: #6f42c1; }
.hb-tile-text { background: #0d6efd; }
.hb-layout-tile.dragging { opacity: .4; }
.hb-layout-toggle { cursor: pointer; }
</style>
CSS;

        $js = <<<JS
<script>
(function() {
    var grid = document.getElementById('{$gridid}');
    var input = document.getElementById('{$inputid}');
    var toggle = document.getElementById('{$toggleid}');
    if (!grid || !input || !toggle) { return; }
    var dragged = null;
    function sync() {
        var first = grid.querySelector('.hb-layout-tile');
        var imagefirst = first && first.getAttribute('data-tile') === 'image';
        var vertical = toggle.checked;
        var value;
        if (vertical) {
            value = imagefirst ? 'image-top' : 'image-bottom';
        } else {
            value = imagefirst ? 'image-left' : 'image-right';
        }
        input.value = value;
    }
    grid.addEventListener('dragstart', function(e) {
        var tile = e.target.closest('.hb-layout-tile');
        if (!tile) { return; }
        dragged = tile;
        tile.classList.add('dragging');
    });
    grid.addEventListener('dragend', function() {
        if (dragged) { dragged.classList.remove('dragging'); }
        dragged = null;
        sync();
    });
    grid.addEventListener('dragover', function(e) {
        e.preventDefault();
        var target = e.target.closest('.hb-layout-tile');
        if (!target || target === dragged || !dragged) { return; }
        var rect = target.getBoundingClientRect();
        var vertical = toggle.checked;
        var after = vertical
            ? (e.clientY - rect.top) > (rect.height / 2)
            : (e.clientX - rect.left) > (rect.width / 2);
        grid.insertBefore(dragged, after ? target.nextSibling : target);
        sync();
    });
    toggle.addEventListener('change', function() {
        grid.classList.toggle('is-vertical', toggle.checked);
        sync();
    });
})();
</script>
JS;

        $control = $css . $grid . html_writer::tag('div', $toggle) . $hidden . $js;

        return format_admin_setting($this, $this->visiblename, $control,
            $this->description, true, '', $this->get_defaultsetting(), $query);
    }
}
