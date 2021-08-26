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
//
/**
 * Strings for component 'local_envbar', language 'en'.
 *
 * @package   local_envbar
 * @author    Grigory Baleevskiy (grigory@catalyst-au.net)
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addfields'] = 'Add another environment';
$string['bgcolour'] = 'Background colour';
$string['cachedef_records'] = 'The environment bar database records';
$string['colourerror'] = 'Invalid HTML color code specified.';
$string['colourplaceholder'] = 'HTML color code. e.g. #fff OR #000000';
$string['configureinprod'] = 'Edit config';
$string['configurehere'] = 'Edit envbar';
$string['dividerselector'] = 'Divider selector';
$string['dividerselector_desc'] = 'This is a css class that is used for the menu divider element. If your theme uses different html then you may need to adjust this selector.<br />This setting is only used if the environment swapper menu is enabled.';
$string['emailheading'] = 'Email prefix';
$string['enableemailprefix'] = 'Enable email subject prefixing';
$string['enableemailprefix_desc'] = 'This setting controls if email subject fields will be prefixed with the first 4 letters of the environment\'s name or not.';
$string['enablefaviconcolorize'] = 'Colorize favicon';
$string['enablefaviconcolorize_desc'] = 'This setting controls if the page favicon will be colorized according to the environment\'s color or not.';
$string['enablemenu'] = 'Enable environment swapper menu';
$string['enablemenu_desc'] = 'This setting controls if the environment swapper menu is added to the user menu or not.';
$string['enabletitleprefix'] = 'Enable page title prefixing';
$string['enabletitleprefix_desc'] = 'This setting controls if the page title will be prefixed with the first 4 letters of the environment\'s name or not.';
$string['envbarheading'] = 'Environment bar';
$string['extracss'] = 'Extra CSS';
$string['extracss_desc'] = 'This css is added only when the bar is visible and pinned to the top of the page. Your theme may also have fixed headers which may clash so this extra css should resolve the clash and move your main theme header down. If your header is fixed only at certain breakpoints then the media query in this extra css should be identical to the one in your theme.';
$string['faviconheading'] = 'Page favicon';
$string['header_envbar'] = 'Environment bar';
$string['help'] = '<p><b>WARNING:</b> These settings should generally only be configured once in the production system.</p><p>If you are NOT in the production system, and these values are empty, then ideally go and fill them out in production and then refresh your database back to here.</p>';
$string['highlightlinks'] = 'Highlight links';
$string['highlightlinks_desc'] = 'This setting controls if outgoing links to other environments, especially the production environment, are highlighted with a colored outline.';
$string['highlightlinksenvbar'] = 'Highlight links (Envbar)';
$string['highlightlinksenvbar_desc'] = 'This setting controls if links within the envbar are also highlighted.';
$string['lastrefresh'] = 'Last refresh';
$string['lastrefresh_success'] = 'The lastrefresh time has been updated.';
$string['linksheading'] = 'Environment links';
$string['menuenvsettings'] = 'Environments';
$string['menuheading'] = 'Environment swapper menu';
$string['menulastrefresh'] = 'Last refresh';
$string['menupresentation'] = 'Presentation';
$string['menuselector'] = 'Menu selector';
$string['menuselector_desc'] = 'This is a css or xpath selector to find the menu ul element for injecting the env swapper menu. If your theme uses different html then you may need to adjust this selector.<br />This setting is only used if the environment swapper menu is enabled.';
$string['missing_required_parameter'] = 'A required parameter was missing. Required params are wwwroot and lastrefresh.';
$string['nextrefreshin'] = 'Reset in {$a}';
$string['notconfigured'] = 'UNKNOWN';
$string['pingprod'] = 'Update production';
$string['pingprod_help'] = 'When this option is selected, the production server will be pinged to update the lastrefresh time for this environment.';
$string['pingprodverbose'] = 'Verbose mode';
$string['pingprodverbose_help'] = 'When this and the update production options are selected, a full debug of the curl response will be printed on the screen.';
$string['pluginname'] = 'Environment bar';
$string['privacy:metadata'] = 'The local envbar plugin does not store any personal data.';
$string['prod'] = 'PROD';
$string['prodbgcolour'] = 'Production background colour';
$string['prodbgcolour_help'] = 'The background colour which will be used to present the production environment, especially when highlighting links from non-production instances to the production instance.';
$string['prodtextcolour'] = 'Production foreground colour';
$string['prodtextcolour_help'] = 'The foreground colour which will be used to present the production environment, especially when highlighting links from non-production instances to the production instance.';
$string['prodwwwroottext'] = 'Production wwwroot';
$string['prodwwwrootplaceholder'] = '$CFG->wwwroot on production';
$string['prodwwwrootautobutton'] = 'Autofill';
$string['prodlasttimestamp'] = '<p>Production timestamp last updated {$a} ago</p>';
$string['refreshedago'] = '{$a} old';
$string['refreshednever'] = 'Never been refreshed';
$string['secretkeygenbutton'] = 'Generate';
$string['secretkeyplaceholder'] = 'SomeRandomAlphanumericalString';
$string['secretkey'] = 'Secret key';
$string['secretkey_help'] = 'The secret key is needed to let the environments talk to each other. Please set it to some random alphanumeric string of your choice or press the \'Generate\' button. If no secret key is set, the non production environments won\'t be able to detect their last reset time.';
$string['secretkey_invalid'] = 'The secret key provided was missing or invalid.';
$string['setenabled'] = 'Enable';
$string['setdeleted'] = 'Delete';
$string['showconfiglink'] = 'Show config link';
$string['showconfiglink_desc'] = 'This setting controls if the "Edit config" link is added to the envbar for users who are allowed to edit the envbar config.';
$string['showtext'] = 'Text to show';
$string['showtextplaceholder'] = 'eg: You are on staging environment';
$string['stringseparator'] = 'String separator';
$string['stringseparator_desc'] = 'This setting controls how the strings in the envbar will be separated. It defaults to a hyphen.';
$string['textcolour'] = 'Foreground colour';
$string['titleheading'] = 'Page title';
$string['urlmatch'] = 'Non Production URL';
$string['urlmatch_help'] = 'Add the address for your non prod site.<br />Note: You can use Regular Expressions to match your URL.<br />E.g. http://stage[1,2,3].example.com to match http://stage2.example.com<br />Note that following special characters will be escaped:<br /> / \ - . ? * ^ $';
$string['urlmatchplaceholder'] = 'eg. staging';
$string['debuggingon'] = 'Debugging On';
$string['debuggingoff'] = 'Debugging Off';
$string['debugtogglelinkon'] = 'Turn On';
$string['debugtogglelinkoff'] = 'Turn Off';
$string['showdebugging'] = 'Show debugging';
$string['showdebugging_desc'] = 'This setting controls if the debugging status and toggle link is added to the envbar for users who are allowed to edit the envbar config.';
$string['debuggingdefinedinconfig'] = '(Defined in config.php)';
