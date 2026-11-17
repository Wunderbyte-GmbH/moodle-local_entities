# Local Entities #

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/entities

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Webservice token ##

For normal use of the plugin, no webservice token is needed. The plugin's own pages
call its functions via AJAX with the normal login session.

A token is only needed if an external system should access the service
"Wunderbyte entities external" (shortname `local_entities_external`). In this case:

1. Enable webservices:

       {your/moodle/dirroot}/admin/settings.php?section=optionalsubsystems

2. Enable a protocol, e.g. REST:

       {your/moodle/dirroot}/admin/settings.php?section=webserviceprotocols

3. Create a token for the service "Wunderbyte entities external". If possible, restrict
   it to the IP address of the external system and give it an expiry date:

       {your/moodle/dirroot}/admin/webservice/tokens.php


## License ##

2021 Wunderbyte GmbH <info@wunderbyte.at>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
