<?php

// For the plugin's panel on the ACP Plugins page.
$l['autorsz_name'                    ] = 'Uploaded Image Auto-Resizer';
$l['autorsz_desc'                    ] = 'Automatically resizes uploaded images to fit within configurable width and height limits.<br /><br />After plugin installation followed by configuration of maximum width and height in its <a href="index.php?module=config-settings&action=change&gid={1}">settings</a>, preexisting uploaded images can be resized by clicking "Go" beside <em>Tools & Maintenance</em> => <em><a href="index.php?module=tools-recount_rebuild">Recount & Rebuild</a></em> => <em>Resize Uploaded Images</em> in the ACP.<br /><br /><em>Credits:</em> Originally authored by <a href="https://github.com/lairdshaw/">Laird</a> to be maintained by the unofficial <a href="https://mybb.group/">MyBB Group</a>, as inspired by <a href="https://community.mybb.com/user-119243.html">azalea4va</a> in the MyBB Community Forums thread <a href="https://community.mybb.com/thread-217961.html">Resize image attachments</a>, and as suggested by <a href="https://community.mybb.com/user-84065.html">Eldenroot</a> after he read that thread.<br />';

// For the plugin's settings in the ACP.
$l['autorsz_settings_title'          ] = 'Uploaded Image Auto-Resizer Settings';
$l['autorsz_settings_desc'           ] = 'Settings to customise the Uploaded Image Auto-Resizer plugin';
$l['autorsz_setting_max_width_title' ] = 'Maximum image width';
$l['autorsz_setting_max_width_desc'  ] = 'Set a value in pixels. Uploaded images with a width greater than this value will be resized to fit.';
$l['autorsz_setting_max_height_title'] = 'Maximum image height';
$l['autorsz_setting_max_height_desc' ] = 'Set a value in pixels. Uploaded images with a height greater than this value will be resized to fit.';

$l['autorsz_rebuild_lbl'             ] = 'Resize Uploaded Images';
$l['autorsz_rebuild_do_desc'         ] = 'When this is run, existing uploaded images are resized to fit the maximum width and height specified in the Uploaded Image Auto-Resizer plugin\'s settings.';
$l['autorsz_admin_log_rebuild'       ] = 'Uploaded image resize rebuild/recount run.';
$l['autorsz_rebuild_success'         ] = 'All uploaded images have been successfully resized.';