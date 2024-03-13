# 2DO Aggregator

![Version 0.1.5](https://badgen.net/badge/Version/0.1.5/FFaa00)
![Stable 0.1.5](https://badgen.net/badge/0.1.5/None/00aa00)
![Requires PHP 7.3](https://badgen.net/badge/PHP/7.3/7884bf)
![License AGPLv3](https://badgen.net/badge/License/AGPLv3/552b55)

The PHP port of python [2do-server](https://github.com/GuduleLapointe/2do-server).

_Before download or install_ : the easiest way to use this is to get the teleport board in-world (hop://speculoos.world:8002:Lab) and ask us to include your calendar in [2do.directory](https://2do.directory/).

Aggregator is a tool to fetch events from various sources and export them 
on several format, for use by 2do Board and other applications related with
2do Events project.

Import formats:

- [x] iCal (ics, iCalendar)
- Custom web parsers:
  - opensimworld
  - islandoasis
  - thirdrock
  - gridtalk (?)

Export formats:

- [x] HYPEvent (legacy 2do/HYPEvent format)
  - [x] events.lsl2 (for current versions of 2do-board)
  - [x] events.lsl  (old format, now includes only a deprecation notice)
- [x] JSON (events.json) compatible with events parsers (provided by w4os or Flexible Helper Scripts)
- [x] iCal (events.ics) iCalendar format, compatible with web, mobile and desktop calendars
- [x] Light html web calendar page for standalone use

This is a side PHP application intended to provide the same functionality as 2do-server, the original events fetcher of 2do project and the original HYPEvents code, but in a more modular and maintainable way, and with better integration with other tools of 2do Events, w4os and Flexiple Helper Scripts projects.

- 2do-board: the in-world board to display the calendar

For grid owners :
- [W4OS](https://w4os.org): a WordPress plugin to manage OpenSimulator grids and provide external helpers, including 2do Events
- [Flexible Helper Scripts](https://github.com/GuduleLapointe/flexible_helper_scripts): a standalione collection of scripts providing the same helpers, without the web interface


## Getting started (recommended)

Don't install this app. Seriously. In most cases you don't need to install it, you can use ours.

- as a parcel owner, you only need the the in-world board, which is intended to be used on any grid. It is available here:

  - Speculoos grid, Lab region [speculoos.world:8002:Lab](hop://speculoos.world:8002/Lab/128/128/22)
  - or for scripters/builders: [2DO board Github repository](https://git.magiiic.com/opensimulator/2do-board),

- as grid or region owner, you can use 2do.directory (<https://2do.directory>) service to enable events search on your grid with a simple straight-forward configuration

- 2do.directory service is also included by [w4os WordPress Interface for OpenSimulator plugin](https://wordpress.org/plugins/w4os-opensimulator-web-interface/)

Jump directly to "Calendar conventions" below for the events format.

## Installation

_Installation of this server is only relevant if you want to provide a custom-curated list calendar. If you really need to manage your own calendars collection, follow these instructions._

Clone this repository and put it in a convenient place like /opt/2do-aggregator (not inside the website root folder).

Install libraries.
  ```bash
  cd /opt/2do-aggregator
  composer install --no-dev
  ```

_Why outside the website directory? This is a script intended to be run from terminal or via a cron job, there is no point allowing random users or bots to run it from outside and risk overloading the server._

Copy config/ical.gfg.example and add your calendar sources.
  ```bash
  cp config/sources.csv.example config/sources.csv
  ```

## Calendar conventions

Events must have
- A title
- A start and end date/time
- A location composed of the region HG url (e.g. yourgrid.org:8002:My_Region)

They might also include
- A description (optional but recommended)
- A category (optional), following standards recognized by the viewers:
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
  - These aliases are also recognized as variants of standard categories:
    - art (art and culture)
    - lecture (art and culture)
    - litterature (art and culture)
    - fair (nightlife/entertainment)
    - music (life music)
    - roleplay (games/contests)
    - social (charity/support groups)

### How to export Google Calendar as .ics

To get the url of your calendar in iCal format, move your mouse above the calendar you want to share, a three dots icon appears, select "Settings and Sharing" and scroll the page down to find Public iCal format adress. This is the value you need to copy as calendar ics url.

## Running

Run the script manually
  ```bash
  /opt/2do-aggregator/aggregator.php /var/www/html/events/
  ```

Create a cronjob to run automatically (below example would run it every 4 hours)
  ```
  0 */4 * * * /opt/2do-aggregator/aggregator.php /var/www/html/events/
  ```

Assuming `/var/www/html` is your website root directory, and `http://www.yourgrid.org/`, this would create:
- `http://www.yourgrid.org/events/` a basic web calendar page
- `http://www.yourgrid.org/events/events.lsl2` the source url for 2do Board
- `http://www.yourgrid.org/events/events.json` the source url for events parsers
  - [w4os Web interface for OpenSimulator](https://w4os.org) (wordpress plugin + parsers)
  - [Flexible Helper Scripts](https://github.com/GuduleLapointe/flexible_helper_scripts) (standalone parsers)

## Related projects

Events parsers for in-world search:
- [w4os Web interface for OpenSimulator](https://w4os.org) WordPress plugin for OpenSim grid management, providing also a collection of tools and helpers, including 2do services, It uses 2do.directory (see below) as default events source.
- [Flexible Helper Scripts](https://github.com/GuduleLapointe/flexible_helper_scripts) standalone collection of helpers included in w4os, without web management interface, including in-world search engine, currency, events, offline messaging. It uses 2do.directory as default events source.

Public calendars to use without installing this app:
- [2do.directory](https://2do.directory), the public 2do Events hypergrid directory.
- [OutWorldz OpensimEvents](https://github.com/Outworldz/OpensimEvents) another calendar based on HYPEvents/2do Events.
