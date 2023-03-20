<?php

// For the plugin's panel on the ACP Plugins page.
$l['autorsz_name'                    ] = 'Image Auto-Resizer';
$l['autorsz_desc'                    ] = 'Automatically resizes uploaded images to fit within configurable width and height limits.<br /><br />After plugin installation followed by configuration of maximum width and height in its {1}settings{2}, preexisting uploaded images can be resized by clicking "Go" beside <em>Tools &amp; Maintenance</em> => <em><a href="index.php?module=tools-recount_rebuild">Recount &amp; Rebuild</a></em> => <em>Resize Uploaded Images</em> in the ACP.<br /><br /><em>Credits:</em> Inspired by <a href="https://community.mybb.com/user-119243.html">azalea4va</a> in the MyBB Community Forums thread <a href="https://community.mybb.com/thread-217961.html">Resize image attachments</a>. Suggested by <a href="https://community.mybb.com/user-84065.html">Eldenroot</a> after he read that thread.<br />';
$l['autorsz_desc_settings_link_open' ] = '<a href="index.php?module=config-settings&amp;action=change&amp;gid={1}">';

// Warning message.
$l['autorsz_no_exif'                 ] = 'The exif_read_data() function was not found. Some images might be rotated on resizing. The most likely solution to this problem is to enable PHP\'s <a href="https://www.php.net/manual/en/book.exif.php">exif</a> extension on your server.';

// For the plugin's settings in the ACP.
$l['autorsz_settings_title'          ] = 'Image Auto-Resizer Settings';
$l['autorsz_settings_desc'           ] = 'Settings to customise the Image Auto-Resizer plugin';
$l['autorsz_setting_max_width_title' ] = 'Maximum image width';
$l['autorsz_setting_max_width_desc'  ] = 'Set a value in pixels. Uploaded images with a width greater than this value will be resized to fit.';
$l['autorsz_setting_max_height_title'] = 'Maximum image height';
$l['autorsz_setting_max_height_desc' ] = 'Set a value in pixels. Uploaded images with a height greater than this value will be resized to fit.';

$l['autorsz_rebuild_lbl'             ] = 'Resize Uploaded Images';
$l['autorsz_rebuild_do_desc'         ] = 'When this is run, existing uploaded images are resized to fit the maximum width and height specified in the Image Auto-Resizer plugin\'s settings.';
$l['autorsz_admin_log_rebuild'       ] = 'Uploaded image resize rebuild/recount run.';
$l['autorsz_rebuild_success'         ] = 'All uploaded images have been successfully resized.';