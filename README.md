# 2DO Aggregator

![Version 0.0.1-dev](https://badgen.net/badge/Version/0.0.1-dev/FFaa00)
![Stable None](https://badgen.net/badge/Stable/None/00aa00)
![Requires PHP 7.3](https://badgen.net/badge/PHP/7.3/7884bf)
![License AGPLv3](https://badgen.net/badge/License/AGPLv3/552b55)

The PHP port from python [2do-server](https://github.com/GuduleLapointe/2do-server).

**This is a work in progress. As of writing, it is not even really working yet.**

Fetch hypergrid calendars from different sources and export them on different formats (iCal, json, HYPEvents, HYPEvents2...).

To use the events board on your grid, the easiest way is to ask us to include your calendar in [2do.directory](https://2do.directory/).

## Getting started (recommended)

Don't install this app. Seriously. In most cases you don't need to install it, you can use ours.

- as a parcel owner, you only need the the in-world board, which is intended to be used on any grid. It is available here:

  - Speculoos grid, Lab region [speculoos.world:8002:Lab](hop://speculoos.world:8002/Lab/128/128/22)
  - or for scripters/builders: [2DO board Github repository](https://git.magiiic.com/opensimulator/2do-board),

- as grid or region owner, you can use 2do.directory (<https://2do.directory>) service to enable events search on your grid with a simple straight-forward configuration

- 2do.directory service is also included by [w4os WordPress Interface for OpenSimulator plugin](https://wordpress.org/plugins/w4os-opensimulator-web-interface/)

Jump directly to "Calendar conventions" below for the events format.

## Installation

Installation of this server is only relevant if you want to provide a custom-curated list calendar.
If you really need to manage your own calendars collection, follow these instructions.

Clone this repository and put it in a convenient place like /opt/2do-aggregator (not inside the website root folder).

Install libraries.
  ```bash
  cd /opt/2do-aggregator
  composer install --no-dev
  ```

_Why outside the website directory? This is a script intended to be run from terminal or via a cron job, there is no point allowing random users or bots to run it from outside and risk overloading the server._

- Copy config/ical.gfg.example and adjust to your need.
  ```bash
  cp config/ical.cfg.example config/ical.cfg
  ```

## Calendar conventions

Events must have
- A title
- A start and end date/time
- A location composed of the region HG url (e.g. yourgrid.org:8002:My_Region)

They might also include
- A description (optional but recommended)
- A category (optional)

### Categories

Standard categories (recognized by SL/OpenSimulator viewer)
- discussion
- sports
- live music
- commercial
- nightlife/entertainment
- games/contests
- pageants
- education
- arts and culture
- charity/support groups
- miscellaneous

These aliases are also recognized as variants of standard categories:
- art (art and culture)
- lecture (art and culture)
- litterature (art and culture)
- fair (nightlife/entertainment)
- music (life music)
- roleplay (games/contests)
- social (charity/support groups)

### Note for Google Calendar users

To get the url of your calendar in iCal format, move your mouse above the calendar you want to share, a three dots icon appears, select "Settings and Sharing" and scroll the page down to find Public iCal format adress. This is the value you need to copy as calendar ics url.

## Running

_Not implemented. Currently the script is not finished and does not export the necessary files._

- Run the script manually
  ```bash
  php /opt/2do-aggregator/fetcher.php
  ```

- Create a cronjob to run automatically (below example would run it every 4 hours)
  ```
  0 */4 * * * /usr/bin/env php /opt/2do-aggregator/fetcher.php
  ```

- launch `http://www.yourgrid.org/events/` to check the result
- to add events to OpenSimulator inworld search, install and configure an event parser, e.g.:
  - [w4os Web interface for OpenSimulator](https://w4os.org) (wordpress plugin + parsers)
  - [Flexible Helper Scripts](https://github.com/GuduleLapointe/flexible_helper_scripts) (standalone parsers)

## Related projects

- [2do.directory](https://2do.directory) is a public hypergrid search engine based on 2do HYPEvents and allowing to implement in-world search in any grid, without installing this stuff.
- [w4os Web interface for OpenSimulator](https://w4os.org) is a collection of tools and helpers, including 2do services, for grid management in a WordPress website. It uses 2do.directory by default.
- [Flexible Helper Scripts](https://github.com/GuduleLapointe/flexible_helper_scripts) a collation of helpers, including in-world search engine, currency, events, offline messaging, uses 2do.directory by default for events.
- [OutWorldz OpensimEvents](https://github.com/Outworldz/OpensimEvents) uses 2do directory. Our own [fork](https://github.com/GuduleLapointe/2do-search) is also useable as a web service and fixes relative path issues.
