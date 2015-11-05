# README #

This is a Splunk app which downloads Regional Rail train stats from SEPTA's public API
once per minute and contains dashboards and reports for visualizing that data.

## Installation Instructions

- Clone this repo into $SPLUNK_HOME/etc/apps/
- Install timewrap
    - `cd ..; tar xfvz septa-analytics/timewrap_24.tgz`
    - For more information about Timewrap, visit it's webpage: https://splunkbase.splunk.com/app/1645/
- Restart Splunk

