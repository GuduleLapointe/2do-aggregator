## Changelog

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
