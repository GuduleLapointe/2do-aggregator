## Changelog

Unreleased changes
* new exclusion list
* added color differentiation for events happening today on the user time zone
* added dark theme
* fix date and week display according to selected timezone
* fix wrong date in html day block title
* fix section menu disabling timezone selector
* removed obsolete fetcher.cfg.example
* revert bug introduced in 9c0771cc93b74c2d54188d052db2b68428852bbf
* Merge branch 'master' of github.com:GuduleLapointe/2do-aggregator
* balance the layout by adjusting day columns width based on the number of events
* remove rss link until it is implemented
* removed debug output
* detect event hgurl from description if location is not set
* updated styles header padding
* updated FAQ

0.1.4
* added sections About (readme), FAQ and Changelog to web page
* added menu
* fix responsive display and text wrapping for small devices
* better checkbox display in lists
* use minified json, css and js

0.1.3
* pretty functional version
* import iCal (.ics) calendars
* export json (for events parser helper and web page)
* export hypevents format (extra light for 2do boards and lsl scripts)
* export iCal (.ics) for use with any calendar software
* export HTML, a basic web page with the calendar, with adjustable timezone
* responsive layout

0.1.3-dev-3
* added disclaimer to the web page
* added teleport links on the web page
* added real time SLTime clock
* added timezone selector
* added disclaimer to the web page
* added teleport links on the web page
* added real time SLTime clock
* added basic HTML export
* added timezone selector
* fix new lines in description
* renamed events.js as script.js
* sticky header
* updated disclaimer
* responive events block display grouped by week and day

0.1.3-dev-2
* added json format export
* added matthiasmullie/minify library
* fix wrong type for empty tags
* fix iconv(): Detected an illegal character in input string
* fix wrong end date in json export
* fix $array typo
* fix version
* include saved files in output
* don't include source in event tags
* disambiguation, renamed  categories as tags, to differientiate from OS/SL events category
* mv src/2do-server src/helpers dev/
* don't encode  to utf8 if it is already the good format
* minified json

0.1.2-dev
* added iCal export format

0.1.1-dev
* added json format export

0.1.0-dev-2
* more a proof of concept than a producion-ready app
* functional ics import
* functional hypevents export
* base classes for general import/export tasks
