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
 * Theme almondb frontpage block order setting.
 *
 * Custom admin setting that lets the administrator reorder the frontpage
 * content blocks by dragging them, instead of editing the template by hand.
 * The value is stored as a comma separated list of block ids, e.g.
 * "01,02,09,04,03,07,08,05,06,10,11,18,19,20".
 *
 * @package   theme_almondb
 * @copyright 2022 ThemesAlmond  - http://themesalmond.com
 * @author    ThemesAlmond - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * List of frontpage content block ids in their default order.
 *
 * Centralised here so the setting and the layout renderer agree on the
 * default order and on which blocks are reorderable.
 *
 * @return string[] Ordered list of two digit block ids.
 */
function theme_almondb_default_block_order() {
    return ['01', '02', '09', '04', '03', '07', '08', '05', '06', '10', '11', '18', '19', '20'];
}

/**
 * All reorderable block tokens: the fixed numeric blocks plus the currently
 * configured dynamic "HTML + image" blocks (html1, html2, ...).
 *
 * @return string[] Ordered list of known block tokens.
 */
function theme_almondb_known_block_ids() {
    $ids = theme_almondb_default_block_order();
    $count = (int)get_config('theme_almondb', 'blockhtmlcount');
    for ($i = 1; $i <= $count; $i++) {
        $ids[] = 'html' . $i;
    }
    return $ids;
}

/**
 * Normalise a stored block order string against the known block ids.
 *
 * Keeps only known ids (in the saved order) and appends any missing known
 * ids at the end, so the list stays valid even if blocks are added or
 * removed in a future version.
 *
 * @param string $stored Comma separated list of block ids.
 * @return string[] Ordered, validated list of block ids.
 */
function theme_almondb_normalise_block_order($stored) {
    $known = theme_almondb_known_block_ids();
    $order = [];
    foreach (explode(',', (string)$stored) as $id) {
        $id = trim($id);
        if ($id !== '' && in_array($id, $known, true) && !in_array($id, $order, true)) {
            $order[] = $id;
        }
    }
    foreach ($known as $id) {
        if (!in_array($id, $order, true)) {
            $order[] = $id;
        }
    }
    return $order;
}

// The helper functions above are also required from the frontpage layout,
// where Moodle's admin library (and therefore the admin_setting base class)
// is not loaded. Only declare the admin setting class when that base class
// exists, i.e. when this file is included from the theme settings pages.
if (!class_exists('admin_setting')) {
    return;
}

/**
 * Admin setting: drag and drop ordering of the frontpage content blocks.
 */
class admin_setting_almondb_blockorder extends admin_setting {

    /**
     * Constructor.
     *
     * @param string $name Unqualified setting name (stored under theme_almondb).
     * @param string $visiblename Localised label.
     * @param string $description Localised help text.
     */
    public function __construct($name, $visiblename, $description) {
        $default = implode(',', theme_almondb_default_block_order());
        parent::__construct($name, $visiblename, $description, $default);
    }

    /**
     * Return the current setting value.
     *
     * @return string Comma separated, validated block order.
     */
    public function get_setting() {
        $value = $this->config_read($this->name);
        if (is_null($value)) {
            return null;
        }
        return implode(',', theme_almondb_normalise_block_order($value));
    }

    /**
     * Store a submitted block order.
     *
     * @param string $data Comma separated block ids from the form.
     * @return string Empty string on success.
     */
    public function write_setting($data) {
        $normalised = implode(',', theme_almondb_normalise_block_order($data));
        return ($this->config_write($this->name, $normalised) ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Render the draggable list of blocks plus the hidden value field.
     *
     * @param string $data Current setting value.
     * @param string $query Search query (unused).
     * @return string HTML to embed in the settings form.
     */
    public function output_html($data, $query = '') {
        $order = theme_almondb_normalise_block_order($data);
        $inputid = $this->get_id();
        $listid = $inputid . '_list';

        $items = '';
        foreach ($order as $id) {
            $ishtml = (strpos($id, 'html') === 0);
            if ($ishtml) {
                $label = get_string('blockhtmlorderlabel', 'theme_almondb', (int)substr($id, 4));
                $enabled = true;
                $typeicon = 'fa-code';
                $typelabel = get_string('block_type_html', 'theme_almondb');
            } else {
                $label = get_string('block' . $id . 'info', 'theme_almondb');
                $enabled = (bool)get_config('theme_almondb', 'block' . $id . 'enabled');
                $typeicon = 'fa-th-large';
                $typelabel = get_string('block_type_fixed', 'theme_almondb');
            }
            $statuslabel = $enabled
                ? get_string('block_enabled', 'theme_almondb')
                : get_string('block_disabled', 'theme_almondb');
            $statusclass = $enabled ? 'badge bg-success text-white' : 'badge bg-secondary text-white';
            $itemclass = 'list-group-item d-flex align-items-center theme-almondb-blockorder-item'
                . ($enabled ? '' : ' text-muted')
                . ($ishtml ? ' is-html' : ' is-fixed');

            $items .= html_writer::tag('li',
                html_writer::tag('i', '',
                    ['class' => 'fa fa-bars handle me-3', 'aria-hidden' => 'true']) .
                html_writer::tag('i', '',
                    ['class' => 'fa ' . $typeicon . ' type-icon me-2', 'title' => $typelabel, 'aria-hidden' => 'true']) .
                html_writer::tag('span', s($label), ['class' => 'flex-grow-1 fw-medium']) .
                html_writer::tag('span', s($statuslabel), ['class' => $statusclass . ' ms-2']),
                [
                    'class' => $itemclass,
                    'draggable' => 'true',
                    'data-blockid' => $id,
                ]
            );
        }

        $css = <<<CSS
<style>
.theme-almondb-blockorder { max-width: 560px; border-radius: .5rem; overflow: hidden; }
.theme-almondb-blockorder-item { cursor: grab; padding: .65rem .9rem; border-left: 4px solid transparent; transition: background-color .12s ease, box-shadow .12s ease; }
.theme-almondb-blockorder-item:active { cursor: grabbing; }
.theme-almondb-blockorder-item:hover { background-color: #f4f7fb; box-shadow: inset 0 0 0 1px rgba(13,110,253,.15); }
.theme-almondb-blockorder-item.is-html { border-left-color: #6f42c1; }
.theme-almondb-blockorder-item.is-fixed { border-left-color: #0d6efd; }
.theme-almondb-blockorder-item .handle { color: #adb5bd; }
.theme-almondb-blockorder-item .type-icon { width: 1.1em; text-align: center; }
.theme-almondb-blockorder-item.is-html .type-icon { color: #6f42c1; }
.theme-almondb-blockorder-item.is-fixed .type-icon { color: #0d6efd; }
.theme-almondb-blockorder-item.dragging { opacity: .4; }
</style>
CSS;

        $list = html_writer::tag('ul', $items, [
            'id' => $listid,
            'class' => 'list-group theme-almondb-blockorder',
        ]);
        $list = $css . $list;

        $hidden = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'id' => $inputid,
            'name' => $this->get_full_name(),
            'value' => implode(',', $order),
        ]);

        // Native HTML5 drag-and-drop. No AMD/grunt build is required by this theme.
        $js = <<<JS
<script>
(function() {
    var list = document.getElementById('{$listid}');
    var input = document.getElementById('{$inputid}');
    if (!list || !input) {
        return;
    }
    var dragged = null;
    function sync() {
        var ids = [];
        list.querySelectorAll('li[data-blockid]').forEach(function(li) {
            ids.push(li.getAttribute('data-blockid'));
        });
        input.value = ids.join(',');
    }
    list.addEventListener('dragstart', function(e) {
        var li = e.target.closest('li[data-blockid]');
        if (!li) { return; }
        dragged = li;
        li.classList.add('dragging');
    });
    list.addEventListener('dragend', function() {
        if (dragged) { dragged.classList.remove('dragging'); }
        dragged = null;
        sync();
    });
    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        var target = e.target.closest('li[data-blockid]');
        if (!target || target === dragged || !dragged) { return; }
        var rect = target.getBoundingClientRect();
        var after = (e.clientY - rect.top) > (rect.height / 2);
        list.insertBefore(dragged, after ? target.nextSibling : target);
    });
})();
</script>
JS;

        return format_admin_setting($this, $this->visiblename, $list . $hidden . $js,
            $this->description, true, '', null, $query);
    }
}
