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
    public function render_envbar($match, $fixed = true, $envs = []) {

        $config = get_config('local_envbar');

        $js = '';
        $matchcolourbg = envbarlib::clean_css_string($match->colourbg);
        $matchcolourtext = envbarlib::clean_css_string($match->colourtext);
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
    background: {$matchcolourbg};
    color: {$matchcolourtext};
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

@media screen and (max-width: 700px) {
    .envbar {
        font-size: 12px;
        line-height: 12px;
        padding: 10px;
    }
}

EOD;

        // If passed a list of env's, then for any env in the list which
        // isn't the one we are on, and which isn't production, add some
        // css which highlights broken links which jump between env's.
        if (isset($config->highlightlinks) && $config->highlightlinks) {
            foreach ($envs as $env) {
                if ($env->matchpattern != $match->matchpattern) {
                    $envmatchpattern = envbarlib::clean_css_string($env->matchpattern);
                    $envcolourbg = envbarlib::clean_css_string($env->colourbg);
                    $envshowtext = envbarlib::clean_css_string($env->showtext);
                    $envcolourtext = envbarlib::clean_css_string($env->colourtext);
                    $css .= <<<EOD

a[href^="{$envmatchpattern}"]:not(.no-envbar-highlight) {
    outline: 2px solid {$envcolourbg};
    padding-right: 4px;
}
a[href^="{$envmatchpattern}"]:not(.no-envbar-highlight)::before {
    content: '{$envshowtext}';
    background-color: {$envcolourbg};
    color: {$envcolourtext};
    padding: 1px 4px 1px 2px;
    margin-right: 4px;
}
EOD;
                }
            }
        }
        if (isset($config->highlightlinks) && !empty($config->highlightlinks) && empty($config->highlightlinksenvbar)) {
            $matchmatchpattern = envbarlib::clean_css_string($match->matchpattern);
            $css .= <<<EOD

/* Restricting the rules above for elements outside the envbar with :not() does not work reliably,
    so we revert the rules here. */
.envbar a[href^="{$matchmatchpattern}"] {
    outline: inherit;
}
.envbar a[href^="{$matchmatchpattern}"]::before {
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
        $showtext = format_string($match->showtext);

        // Just show the biggest time unit instead of 2.
        if (!isset($config->stringseparator)) {
            $config->stringseparator = '-'; // Set default.
        }

        if (isset($config->showrefresh) && $config->showrefresh) {
            if (property_exists($match, 'lastrefresh') && $match->lastrefresh > 0) {
                $show = format_time(time() - $match->lastrefresh);
                $title = userdate($match->lastrefresh, get_string('refreshedagoformat', 'local_envbar'));
                $title = get_string('refreshedagotitle', 'local_envbar', $title);

                $num = strtok($show, ' ');
                $unit = strtok(' ');
                $show = html_writer::tag('span', "$num $unit", ['title' => $title ]);
                $showtext .= ' ' . $config->stringseparator . ' ' . get_string('refreshedago', 'local_envbar', $show);
            } else {
                $showtext .= ' ' . $config->stringseparator . ' ' . get_string('refreshednever', 'local_envbar');
            }
        }

        // Display the next expected refresh time, if any.
        envbarlib::check_refresh_timestamp($match);
        // Get it fresh from the database as it may have been updated.
        $nextrefresh = get_config('local_envbar', 'nextrefreshasts');
        if ($nextrefresh) {
            $title = userdate($nextrefresh, get_string('refreshedagoformat', 'local_envbar'));
            $title = get_string('nextrefreshtitle', 'local_envbar', $title);

            $show = envbarlib::get_next_refresh_as_text($nextrefresh);
            $show = html_writer::tag('span', $show, ['title' => $title ]);
            $showtext .= ' ' . $config->stringseparator . ' ' . $show;
        }

        // Optionally also show the config links for admins.
        $produrl = envbarlib::getprodwwwroot();
        $systemcontext = context_system::instance();
        $canedit = has_capability('moodle/site:config', $systemcontext);
        if ($canedit && !empty($config->showconfiglink)) {
            if ($produrl) {
                $editlink = html_writer::link(
                    $produrl . '/local/envbar/index.php',
                    get_string('configureinprod', 'local_envbar'),
                    ['target' => 'prod', 'class' => 'no-envbar-highlight']
                );
            } else {
                $editlink = html_writer::link(
                    new moodle_url('/local/envbar/index.php'),
                    get_string('configurehere', 'local_envbar')
                );
            }
            $showtext .= '<nobr> ' . $config->stringseparator . ' ' . $editlink . '</nobr>';
        }

        if (!empty($config->showdebugging)) {
            $showtext .= $this->get_debug_text($config, $canedit);
        }

        if (!empty($config->showemail)) {
            $showtext .= ' ' . $config->stringseparator . ' ';
            $showtext .= $this->get_email_text();
        }

        if ($fixed) {
            $js .= local_envbar_favicon_js($match);
            $js .= local_envbar_title($match);
        }

        $envclass = strtolower($match->showtext);
        $envclass = s(preg_replace('/\s+/', '', $envclass));

        if ($fixed) {
            $js .= <<<EOD
    document.body.className += ' local_envbar local_envbar_$envclass';
EOD;
        }

        $html = <<<EOD
<div class="envbar $class">
    <button type="button" class="close" onclick="envbar_close(this);">×</button>
    $showtext
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
    var parent = envbar.parentElement;
    var body = document.body;
    body.classList.remove('local_envbar');
    parent.removeChild(envbar);
    parent.removeChild(document.getElementById('envbar_spacer'));
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
     * Returns the email text to be displayed in the envbar.
     *
     * @return string Email status
     */
    protected function get_email_text(): string {
        return envbarlib::get_email_status_string();
    }

    /**
     * Returns the debug text to be displayed in the envbar.
     *
     * @param stdClass $config Config
     * @param bool $canedit Whether editing is allowed
     * @return string Debug text
     */
    protected function get_debug_text(stdClass $config, bool $canedit): string {
        global $ME;

        $debugtext = '';
        $debugging = envbarlib::get_debugging_status_string();
        if ($canedit) {
            // Get the url of the current page.
            $currentlink = $ME ?? '/';
            // Use a POST form rather than a GET link so the sesskey is not exposed in the
            // URL (access logs, browser history, Referer headers).
            $debugtogglelink = html_writer::tag(
                'form',
                html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
                html_writer::empty_tag('input', [
                    'type' => 'hidden',
                    'name' => 'redirect',
                    'value' => base64_encode($currentlink),
                ]) .
                html_writer::tag(
                    'button',
                    envbarlib::get_debug_toggle_string(),
                    [
                        'type' => 'submit',
                        'style' => 'background:none;border:none;padding:0;margin:0;' .
                            'color:inherit;text-decoration:underline;cursor:pointer;font:inherit;',
                    ]
                ),
                [
                    'method' => 'post',
                    'action' => (new moodle_url('/local/envbar/toggle_debugging.php'))->out(false),
                    'style' => 'display:inline;margin:0;padding:0;',
                ]
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
            $debugtext .= '<nobr> ' . $stringseparator . ' ' . $debugging . ' ' . $debugtogglelink . '</nobr>';
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
 * @param object $match
 * @return string A chunk of JS to set the title
 */
function local_envbar_title($match) {
    $config = get_config('local_envbar');

    if (empty($config->enabletitleprefix)) {
        return '';
    }

    $prefix = s(substr($match->showtext, 0, 4));
    $js = <<<EOD

    var title = document.querySelector('title');
    title.innerText = '$prefix: ' + title.innerText;

EOD;
    return $js;
}

/**
 * Gets some JS which colorizes the favicon according to the env
 *
 * @param object $match
 * @return string A chunk of JS to set the favicon
 */
function local_envbar_favicon_js($match) {
    $config = get_config('local_envbar');

    if (empty($config->enablefaviconcolorize)) {
        return '';
    }

    $colourbgjs = json_encode($match->colourbg);
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
    ctx.fillStyle = {$colourbgjs};
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
