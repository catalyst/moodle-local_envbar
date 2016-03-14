[![Build Status](https://travis-ci.org/nhoobin/moodle-local_envbar.svg?branch=master)](https://travis-ci.org/nhoobin/moodle-local_envbar)

Environment bar - Moodle local plugin
====================

**FOR DEVELOPMENT SERVERS ONLY**

This displays a configurable fixed div across across the top of your Moodle site which can change depending on where it has been deployed.

This is useful with development and production for identifying which server you currently reside on based on the URL.

# Installation

Add the plugin to /local/envbar/

Run the Moodle upgrade.

# Setup

The plugin can be configured via,
    `(Site administration > Development > Environment bar)`

Text, backgound-color and text color can be customised.


# Details 

An extra div will be printed within standard_top_of_body_html function call:
$OUTPUT->standard_top_of_body_html()
