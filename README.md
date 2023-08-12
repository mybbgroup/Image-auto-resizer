## About

Image Auto-Resizer is a plugin for [MyBB](https://mybb.com/) 1.8. It auto-resizes uploaded images so that they do not exceed a stipulated maximum width and height.

## Installing

1. *Download*.

   Download an archive of the plugin's files.

2. *Copy files*.

   Extract the files in that archive to a temporary location, and then copy the files in "root" into the root of your MyBB installation. That is to say that `root/inc/plugins/auto_resizer.php` should be copied to your MyBB root directory's `inc/plugins/` directory, and `root/inc/languages/english/admin/auto_resizer.lang.php` should be copied to your MyBB root's `inc/languages/english/admin` directory.

3. *Install via the ACP*.

   In a web browser, open the "Plugins" module in the ACP of your MyBB installation. You should see "Image Auto-Resizer" under "Inactive Plugins". Click "Install & Activate" next to it. You should then see the plugin listed under "Active Plugins" on the reloaded page.

4. *Configure settings*.

   Configure the plugin's settings via the ACP's "Settings" module.

## Upgrading

1. *Deactivate*.

   In a web browser, open the "Plugins" module in the ACP and click "Deactivate" beside the "Image Auto-Resizer" plugin.

2. *Download and Copy files*.

   As in steps one and two for installing above.

3. *Reactivate*.

   As for step one but clicking "Activate" this time.

4. *Configure settings*.

   Configure any new settings for this plugin via the ACP's "Settings" module.

## Resizing preexisting uploaded images

Image Auto-Resizer resizes images upon their being uploaded. To resize preexisting uploaded images, click "Go" beside _Tools & Maintenance_ => _Recount & Rebuild_ => _Resize Uploaded Images_ in the ACP. You probably also want to do the same if you change the plugin's settings to specify either a smaller width or a smaller height than when this _Resize Uploaded Images_ recount/rebuild was last run.

## Licence

Image Auto-Resizer is licensed under the GPL version 3.

## Maintainers and credits

Image Auto-Resizer was originally authored by [Laird](https://github.com/lairdshaw/) to be maintained by the unofficial [MyBB Group](https://mybb.group/), as inspired by [azalea4va](https://community.mybb.com/user-119243.html) in the MyBB Community Forums thread [Resize image attachments](https://community.mybb.com/thread-217961.html), and as suggested by [Eldenroot](https://community.mybb.com/user-84065.html) after he read that thread.