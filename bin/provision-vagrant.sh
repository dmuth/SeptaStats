#!/bin/bash
#
# This script is used to provision a Vagrant instance when set up.
#


#
# Restart syslog so that it gets the name hostname
#
service rsyslog restart

#
# TODO: I need to add in code to install PHP and Nginx and set up Splunk.
# This setup is currently all down with a custom ansible setup because it has some private
# things that I don't want to release to the public.  But I should eventually
# factor it out.
#


