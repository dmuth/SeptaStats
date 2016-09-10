# Septa Stats

Source code for http://www.SeptaStats.com/

This is a Splunk app which downloads Regional Rail train stats from SEPTA's public API
once per minute and contains dashboards and reports for visualizing that data.

<img src="https://raw.githubusercontent.com/dmuth/SeptaStats/master/img/septa-stats-1.jpg" width="260" />
<img src="https://raw.githubusercontent.com/dmuth/SeptaStats/master/img/septa-stats-2.jpg" width="260" />
<img src="https://raw.githubusercontent.com/dmuth/SeptaStats/master/img/septa-stats-3.jpg" width="260" />


## Installation Instructions

- Clone this repo into $SPLUNK_HOME/etc/apps/
    - If on a production instance:
        - `cd /var/splunk/etc/apps && git clone git@bitbucket.org:dmuth/septa-analytics.git`
    - If on a Vagrant instance
        - Do nothing, you already have the code.
- Install timewrap
    - `tar xfvz septa-analytics/timewrap_24.tgz`
    - For more information about Timewrap, visit it's webpage: https://splunkbase.splunk.com/app/1645/
- Restart Splunk
    - `/var/splunk/bin/splunk restart`

At this point, the app will begin gathering statistics via SEPTA's Train API.

## To Serve This App Over the web

Set up your webserver of choice and point the DocumentRoot to $SPLUNK_HOME/etc/apps/septa-analytics/htdocs/.

Dependences:
- PHP.  5.5 or higher should work.
- Redis. This is used for caching the results from queries made on the back end.
- A running Splunk instance. :-)  A free/trial copy can be obtained at http://www.splunk.com/

Questions?  Email me at **dmuth@dmuth.org** or open an issue here.



