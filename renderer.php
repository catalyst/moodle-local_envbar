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
 * Renderer
 *
 * @package   local_envbar
 * @author    Brendan Heywoody (brendan@catalyst-au.net)
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_envbar\local\envbarlib;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Renderer for envbar.
 *
 * @copyright  2016 Brendan Heywood (brendan@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_envbar_renderer extends plugin_renderer_base {

    /**
     * Render the envbar
     *
     * @param stdClass $match And environment to show
     * @param bool $fixed should this bar be fixed to the header
     * @param array $envs
     *
     * @return string
     */
    public function render_envbar($match, $fixed = true, $envs = array()) {

        $config = get_config('local_envbar');

        $js = '';
        $css = <<<EOD
.envbar {
    padding: 15px;
    width: 100%;
    height: 50px;
    text-align: center;
    margin-bottom: 10px;
    box-sizing: border-box;
}
.envbar.env{$match->id},
.envbar.env{$match->id} a {
    background: {$match->colourbg};
    color: {$match->colourtext};
}
.envbar.env{$match->id} a {
    text-decoration: underline;
}
.envbar.fixed {
    position: fixed;
    top: 0px;
    left: 0px;
    z-index: 9999;
}
EOD;

        // If passed a list of env's, then for any env in the list which
        // isn't the one we are on, and which isn't production, add some
        // css which highlights broken links which jump between env's.
        if ($config->highlightlinks) {
            foreach ($envs as $env) {
                if ($env->matchpattern != $match->matchpattern) {
                    $css .= <<<EOD

a[href^="{$env->matchpattern}"]:not(.no-envbar-highlight) {
    outline: 2px solid {$env->colourbg};
    padding-right: 4px;
}
a[href^="{$env->matchpattern}"]::before {
    content: '{$env->showtext}';
    background-color: {$env->colourbg};
    color: {$env->colourtext};
    padding: 1px 4px 1px 2px;
    margin-right: 4px;
}
EOD;
                }
            }
        }
        if ($config->highlightlinks && !$config->highlightlinksenvbar) {
            $css .= <<<EOD

/* Restricting the rules above for elements outside the envbar with :not() does not work reliably,
    so we revert the rules here. */
.envbar a[href^="{$env->matchpattern}"] {
    outline: inherit;
}
.envbar a[href^="{$env->matchpattern}"]::before {
    content: '';
    background-color: transparent;
    padding: 0;
}
EOD;
        }

        if ($fixed) {
            $css .= empty($config->extracss) ? envbarlib::get_default_extra_css() : $config->extracss;
        }

        $class = 'env' .  $match->id;
        $class .= $fixed ? ' fixed' : '';

        // Show the configured env message.
        $showtext = format_string(htmlspecialchars($match->showtext));

        // Just show the biggest time unit instead of 2.
        if ($match->lastrefresh > 0) {
            $show = format_time(time() - $match->lastrefresh);
            $num = strtok($show, ' ');
            $unit = strtok(' ');
            $show = "$num $unit";
            $showtext .= ' ' . $config->stringseparator . ' ' . get_string('refreshedago', 'local_envbar', $show);
        } else {
            $showtext .= ' ' . $config->stringseparator . ' ' . get_string('refreshednever', 'local_envbar');
        }

        $nextrefresh = isset($config->nextrefresh) ? $config->nextrefresh : null;
        if (isset($nextrefresh)) {

            if ($nextrefresh == (1 * $nextrefresh)) {
                // Does the value look like a timestamp?
                $nextrefresh = (1 * $nextrefresh);
            } else if ( ($time = strtotime($nextrefresh)) !== false  ) {
                // Does the value look like a date string?
                $nextrefresh = $time;

            } else {
                // Dunno just ignore it.
                $nextrefresh = null;
            }

            if ($nextrefresh) {
                $show = format_time($nextrefresh - time());

                $num = strtok($show, ' ');
                $unit = strtok(' ');
                $show = "$num $unit";
                $showtext .= ' ' . $config->stringseparator . ' ' . get_string('nextrefreshin', 'local_envbar', $show);
            }
        }

        // Optionally also show the config links for admins.
        $produrl = envbarlib::getprodwwwroot();
        $systemcontext = context_system::instance();
        $canedit = has_capability('moodle/site:config', $systemcontext);
        if ($canedit && $config->showconfiglink) {
            if ($produrl) {
                $editlink = html_writer::link($produrl.'/local/envbar/index.php',
                        get_string('configureinprod', 'local_envbar'), array('target' => 'prod'));
            } else {
                $editlink = html_writer::link(new moodle_url('/local/envbar/index.php'),
                        get_string('configurehere', 'local_envbar'));
            }
            $showtext .= '<nobr> ' . $config->stringseparator . ' ' . $editlink . '</nobr>';
        }

        if ($config->showdebugging) {
            $showtext .= $this->get_debug_text($canedit, $config);
        }

        if ($fixed) {
            $js .= local_envbar_favicon_js($match);
            $js .= local_envbar_user_menu($envs, $match);
            $js .= local_envbar_title($match);
        }

        $envclass = strtolower($match->showtext);
        $envclass = preg_replace('/\s+/', '', $envclass);

        if ($fixed) {
            $js .= <<<EOD
    document.body.className += ' local_envbar local_envbar_$envclass';
EOD;
        }

        $html = <<<EOD
<div class="envbar $class">
    $showtext
    <button type="button" class="close" onclick="envbar_close(this);">×</button>
</div>
<style>
$css
</style>
<script>
document.addEventListener("DOMContentLoaded", function(event) {
    $js
});
function envbar_close(el) {
    var envbar = el.parentElement;
    var body = envbar.parentElement;
    if (body.nodeName == 'BODY') {
        body.classList.remove('local_envbar');
        body.removeChild(envbar);
        body.removeChild(document.getElementById('envbar_spacer'));
    } else if (body.getAttribute('id') === 'page-wrapper') {
        var wrapper = body;
        body = body.parentElement;
        body.classList.remove('local_envbar');
        wrapper.removeChild(envbar);
        wrapper.removeChild(document.getElementById('envbar_spacer'));
    }
}
</script>
EOD;
        if ($fixed) {
            $html .= <<<EOD
<div id="envbar_spacer" style="height: 50px;">&nbsp;</div>
EOD;
        }

        // Wrap up the envbar in tokens.
        $ebstart = envbarlib::ENVBAR_START;
        $ebend = envbarlib::ENVBAR_END;
        $html = <<<EOD
$ebstart
$html
$ebend
EOD;

        return $html;
    }

    /**
     * Returns the debug text to be displayed in the envbar.
     *
     * @param boolean $canedit Whether editing is allowed
     * @param object $config Config
     * @return string Debug text
     */
    protected function get_debug_text($canedit = false, $config) {

        global $CFG, $ME;

        $debugtext = '';
        $debugging = envbarlib::get_debugging_status_string();
        if ($canedit) {
            // Get the url of the current page.
            $currentlink = $ME;
            $debugtogglelink = html_writer::link(
                new moodle_url('/local/envbar/toggle_debugging.php?redirect='.$currentlink),
                envbarlib::get_debug_toggle_string()
            );
            $debugtext .= $this->get_debug_text_for_admin($config->stringseparator, $debugging, $debugtogglelink);
        } else {
            $debugtext .= '<nobr> ' . $config->stringseparator . ' ' . $debugging . '</nobr>';
        }

        return $debugtext;
    }

    /**
     * Returns the debug text to be displayed for admin.
     *
     * @param  string $stringseparator String separator
     * @param  string $debugging Debugging text
     * @param  string $debugtogglelink Debug toggle link
     * @return string Debug text
     */
    protected function get_debug_text_for_admin($stringseparator, $debugging, $debugtogglelink) {
        global $CFG;

        $debugtext = '';
        // Check if debug level and debug display is set on config.php.
        if (!isset($CFG->config_php_settings['debug']) && !isset($CFG->config_php_settings['debugdisplay'])) {
            $debugtext .= '<nobr> ' . $stringseparator . ' ' . $debugging. ' ' . $debugtogglelink . '</nobr>';
        } else {
            $debuggingdefinedstr = get_string('debuggingdefinedinconfig', 'local_envbar');
            // Remove link to toggle debugging.
            $debugtext .= '<nobr> ' . $stringseparator . ' ' . $debugging;
            $debugtext .= ' ' . $debuggingdefinedstr . '</nobr>';
        }
        return $debugtext;
    }

}

/**
 * Gets some JS which adds the env to the page title
 *
 * @return string A chunk of JS to set the title
 */
function local_envbar_title($match) {
    $config = get_config('local_envbar');

    if (!$config->enabletitleprefix) {
        return '';
    }

    $prefix = substr($match->showtext, 0, 4);
    $js = <<<EOD

    var title = document.querySelector('title');
    title.innerText = '$prefix: ' + title.innerText;

EOD;
    return $js;

}

/**
 * Gets some JS which colorizes the favicon according to the env
 *
 * @return string A chunk of JS to set the favicon
 */
function local_envbar_favicon_js($match) {
    $config = get_config('local_envbar');

    if (!$config->enablefaviconcolorize) {
        return '';
    }

    $js = <<<EOD
    var favicon;
    var links = document.getElementsByTagName("link");
    for (var i = 0; i < links.length; i++) {
        if ((links[i].getAttribute("rel") == "icon") ||
            (links[i].getAttribute("rel") == "shortcut icon")) {
            favicon = links[i];
        }
    }

    if (!favicon) {
        favicon = document.createElement('link');
        favicon.rel = 'shortcut icon';
        favicon.type = 'image/x-icon';
        favicon.href = '';
        document.getElementsByTagName('head')[0].appendChild(favicon);
    }

    // First we make the whole thing a solid color matching the envbar.
    var canvas = document.createElement('canvas');
    canvas.width = 16;
    canvas.height = 16;
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = "{$match->colourbg}";
    ctx.fillRect(0, 0, 16, 16);

    // And then optionally if there was an existing favicon we add it back
    // but partially transparent so it's still colorised.
    if (favicon.href) {
        var img = new Image();
        img.src = favicon.href;
        img.onload = function() {
            ctx.globalAlpha = 0.6;
            ctx.drawImage(img, 0, 0, 16, 16);
            favicon.href = canvas.toDataURL("image/x-icon");
        }
    }

    favicon.href = canvas.toDataURL("image/x-icon");
EOD;

    return $js;
}

/**
 * Gets some JS which inserts env jump links into the user menu
 *
 * @return string A chunk of JS
 */
function local_envbar_user_menu($envs) {

    global $CFG, $PAGE;

    $config = get_config('local_envbar');

    if (!$config->enablemenu) {
        return '';
    }

    if (isset($config->menuselector)) {
        $menuselector = $config->menuselector;
    } else {
        $menuselector = '.usermenu .menu';
    }

    if (empty($menuselector)) {
        return ''; // Not using user menu, nothing to do.
    }

    $html = '';

    if ($PAGE->has_set_url()) {
        $url = $PAGE->url->out();

        foreach ($envs as $env) {
            $jump = $url;
            $jump = str_replace($CFG->wwwroot, $env->matchpattern, $jump);
            if ($jump == $url) {
                continue;
            }
            $link = <<<EOD
<li role="presentation">
  <a class="icon menu-action no-envbar-highlight" role="menuitem" href="{$jump}">
    <span class="menu-action-text"> </span>
  </a>
</li>
EOD;
            $html .= $link;
        }
    }

    if (!$html) {
        return '';
    }

    if (isset($config->dividerselector)) {
        $divider = $config->dividerselector;
    } else {
        $divider = 'filler';
    }
    $html = '<li role="presentation"><span class="'. $divider .'">&nbsp;</span></li>' . $html;

    $html = str_replace("\n", '', $html);
    $html = str_replace("\"", "\\\"", $html);

    $url = (new moodle_url('/local/envbar/'))->out();
    $isadmin = (is_siteadmin() ) ? '1' : '0';

    $js = <<<EOD

    var menu = document.querySelector('$menuselector');
    var html = "$html";
    if (menu) {
        menu.insertAdjacentHTML('beforeend', html);
    } else {
        $isadmin && console.error(
            "local_envbar: Menu selector is misconfigured '$menuselector' \\n Please configure it here: $url");
    }
EOD;
    return $js;

}

