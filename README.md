# README #

This is a Splunk app which downloads Regional Rail train stats from SEPTA's public API
once per minute and contains dashboards and reports for visualizing that data.

## Installation Instructions

- Clone this repo into $SPLUNK_HOME/etc/apps/
    - `cd /var/splunk/etc/apps && git clone git@bitbucket.org:dmuth/septa-analytics.git`
- Install timewrap
    - `cd ..; tar xfvz septa-analytics/timewrap_24.tgz`
    - For more information about Timewrap, visit it's webpage: https://splunkbase.splunk.com/app/1645/
- Restart Splunk


### To enable the web front-end

Do nothing. :-)  Nginx is pointed to the directory under Splunk. 

