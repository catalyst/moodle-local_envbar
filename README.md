Environment bar - Moodle local plugin
====================

This displays a prominment header across across the top of your NON PROD Moodle
environments which can be configured to have different colors and messages for
each environent, and also automatically detects and show you when the DB was
last refreshed.

It's very useful when working with lots of different environments to avoid
confusion around where you are, especially when env's can contain hard coded
links and you accidentally jump between environments.

Principals
----------

Showing what environment you are in needs to be reliable and fail safe.

If it doesn't work for any reason then you may as well not have it. The way
this plugin works is that in your production system you specify what your
different environments are. Then after a refresh of production data back to a
staging environment it can auto detect that it is no longer in production and
warn the end user. Further more if there isn't any config at all, then it will
assume you are in a fresh development environment that hasn't been refreshed
and show a default fail safe warning.

It will also automatically detect and show you when the environment was last
refreshed from production, which is a common question testers ask.

Branches
--------
| Moodle version    | Totara version   | Branch   | PHP  |
| ----------------- | ---------------- | -------- | ---- |
| Moodle 2.7 to 3.2 | Totara 2.7 to 11 | VERSION1 | 5.5+ |
| Moodle 3.3 to 3.9 | Totara 12        | VERSION2 | 7.0+ |
| Moodle 3.10+      | Totara 13        | VERSION3 | 7.3+ |

Installation
------------

Add the plugin to /local/envbar/

Run the Moodle upgrade.

# Configuration

Upon first installation you will see a notification across the screen that prodwwwroot has not been set. There is a convenient link in the bar to:

 Site administration > Plugins > Local Plugins > Environment bar

Please set this value to be exactly what your production ```$CFG->wwwroot``` is.  If you are on the production box then you can click on the 'autofill' button.

Or you can define the environments and prodwwwroot in config.php:

```php
    $CFG->local_envbar_prodwwwroot = 'http://moodle.prod';
    $CFG->local_envbar_items = array(
        array(
            'matchpattern' => 'https://staging.moodle.edu',
            'showtext'     => 'Staging environment',
            'colourbg'     => 'orange',
            'colourtext'   => 'white',
        ),
        array(
            'matchpattern' => 'https://qa.moodle.edu',
            'showtext'     => 'QA environment',
            'colourbg'     => 'purple',
            'colourtext'   => 'white',
        ),
        array(
            'matchpattern' => 'http://moodle.local',
            'showtext'     => 'Localhost environment',
            'colourbg'     => 'black',
            'colourtext'   => 'white',
        ),
    );
```

The colours available are,

    black
    white
    red
    green
    seagreen
    yellow
    brown
    blue
    slateblue
    chocolate
    crimson
    orange
    darkorange
