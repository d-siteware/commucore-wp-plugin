# CommuCore Wordpress Plugin

![Version](https://img.shields.io/github/v/release/d-siteware/commucore-wp-plugin)
![Lizenz](https://img.shields.io/badge/Lizenz-MIT%20v2%2B-green)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)

Contributors: d-siteware

Tags: community, events, posts, api, organization, club

Display events and posts from your CommuCore instance on your WordPress site.

## Description

This plugin connects your WordPress site to your CommuCore instance via its API. Display events and posts using simple shortcodes.

## Features

- Event list with images and detail view
- Post list with a detail view
- Customizable URL slugs for pretty permalinks
- Responsive templates
- Shortcode-based integration
- Multi-language ready (DE, EN, HU)

## Installation

1. Upload the plugin and activate it. At the first activation, the required pages are created automatically using the default slug values.
2. Go to Settings → CommuCore and enter your instance URL and API key.
3. Use the `[commucore_events]` or `[commucore_posts]` shortcodes on your pages.

## Frequently Asked Questions

***Where do I find my API key?***

In CommuCore in the user menu at API keys.

***Which permalink settings does the plugin require?***

The plugin is agnostic and generates the required link structure. To use your on slugs in the URLs (e.g. /event/42/), the pretty permalinks (Settings → Permalinks → "Post name") have to be selected.

## Changelog

**1.0.3**
- Minor fixes of version id's

**1.0.2**
- Add mandatory files to meet WP.org requirements for plugins

**1.0.0**
- Initial release
- Event list and detail view
- Post list and detail view
- Customizable URL slugs
- Shortcode integration
- Multi-language support (DE, EN, HU)
