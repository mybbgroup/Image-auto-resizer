<?php

// For the plugin's panel on the ACP Plugins page.
$l['autorsz_name'                    ] = 'Image Auto-Resizer';
$l['autorsz_desc'                    ] = 'Automatically resizes uploaded images to fit within configurable width and height limits.';
$l['autorsz_desc_resize_existing'    ] = 'Resize preexisting uploaded images: click "Go" beside <em>Tools &amp; Maintenance</em> » <em><a href="index.php?module=tools-recount_rebuild">Recount &amp; Rebuild</a></em> » <em>Resize Uploaded Images</em>.';
$l['autorsz_desc_config_settings'    ] = 'Configure settings';
$l['autorsz_desc_settings_link_open' ] = '<a href="index.php?module=config-settings&amp;action=change&amp;gid={1}">';
$l['autorsz_desc_no_exif'            ] = 'The exif_read_data() function was not found. Some images might be rotated on resizing. The most likely solution to this problem is to enable PHP\'s <a href="https://www.php.net/manual/en/book.exif.php">exif</a> extension on your server.';
$l['autorsz_desc_credits'            ] = '<em>Credits:</em> Inspired by <a href="https://community.mybb.com/user-119243.html">azalea4va</a> in the MyBB Community Forums thread <a href="https://community.mybb.com/thread-217961.html">Resize image attachments</a>. Suggested by <a href="https://community.mybb.com/user-84065.html">Eldenroot</a> after he read that thread.<br />';
$l['autorsz_upgrade_success_hdr'     ] = '{1} has been activated successfully and upgraded to version {2}.';
$l['autorsz_upgrade_success_info'    ] = 'Successfully upgraded to version {1}.';

// For the plugin's settings in the ACP.
$l['autorsz_settings_title'          ] = 'Image Auto-Resizer Settings';
$l['autorsz_settings_desc'           ] = 'Settings to customise the Image Auto-Resizer plugin';
$l['autorsz_setting_max_width_title' ] = 'Maximum image width';
$l['autorsz_setting_max_width_desc'  ] = 'Set a value in pixels. Uploaded images with a width greater than this value will be resized to fit (if, however, they are animated, then the below policies apply instead).';
$l['autorsz_setting_max_height_title'] = 'Maximum image height';
$l['autorsz_setting_max_height_desc' ] = 'Set a value in pixels. Uploaded images with a height greater than this value will be resized to fit (if, however, they are animated, then the below policies apply instead).';
$l['autorsz_setting_oversize_agif_policy_title'] = 'Oversized animated GIF policy';
$l['autorsz_setting_oversize_agif_policy_desc' ] = 'What should be done with animated GIFs which exceed the maximum dimensions below? <strong>Note well</strong>:<ol><li>Resizing while retaining animation requires the PHP <a href="https://www.php.net/manual/en/book.imagick.php">Imagick</a> PECL module to be installed. In its absence, the two shrink-while-retaining-animation options will be treated as "Ignore".</li><li>Those two options are <strong>experimental</strong>, and the Imagick module is sometimes glitchy. In particular, it (1) sometimes outputs resized images with visual glitches, such as black areas (which changing the coalesce-and-optimise settings below can sometimes avoid), and (2) does not always replicate the frame rate of original images in the resized images, especially for animated PNGs, such that the animation of the resized images is faster or slower than the originals.</li><li>Resizing while retaining animation, especially for large images, can be very time-consuming, and <strong>might expose your board to DoS attacks</strong> by malicious members. You should in any case ensure that your <a href="https://www.php.net/manual/en/info.configuration.php#ini.max-execution-time">max_execution_time</a> setting is large enough to avoid timeouts while resizing animated images.</li><li>Resizing while retaining animation, especially for large images, can be memory-intensive, and this plugin does no checking for sufficient available memory: thus, make sure your <a href="https://www.php.net/manual/en/ini.core.php#ini.memory-limit">memory_limit</a> setting is large enough to avoid crashes due to running out of memory while resizing animated images.</li></ol>';
$l['autorsz_setting_withinsize_agif_policy_title'] = 'Within-size animated GIF policy';
$l['autorsz_setting_withinsize_agif_policy_desc' ] = 'What should be done with animated GIFs which DO NOT exceed the maximum dimensions below?';
$l['autorsz_setting_max_agif_width_title' ] = 'Maximum animated GIF width';
$l['autorsz_setting_max_agif_width_desc'  ] = 'Set a value in pixels. Zero indicates to use the general "Maximum image width" setting above. The above policies will be applied to uploaded animated GIFs with a width greater than (or not, respectively) this value.';
$l['autorsz_setting_max_agif_height_title'] = 'Maximum animated GIF height';
$l['autorsz_setting_max_agif_height_desc' ] = 'Set a value in pixels. Zero indicates to use the general "Maximum image height" setting above. The above policies will be applied to uploaded animated GIFs with a height greater than (or not, respectively) this value.';
$l['autorsz_setting_coalesceandoptimise_agif_title'] = 'Coalesce-and-optimise animated GIFs?';
$l['autorsz_setting_coalesceandoptimise_agif_desc'] = 'Coalesce before, and optimise after, resizing animated GIFs? Depending on your Imagick version, changing the value of this setting might avoid glitches in resized images.';
$l['autorsz_setting_oversize_apng_policy_title'] = 'Oversized animated PNG policy';
$l['autorsz_setting_oversize_apng_policy_desc' ] = 'What should be done with animated PNGs which exceed the maximum dimensions below? The points below "<strong>Note well</strong>" of the above "Oversized animated GIF policy" setting also apply here.';
$l['autorsz_setting_withinsize_apng_policy_title'] = 'Within-size animated PNG policy';
$l['autorsz_setting_withinsize_apng_policy_desc' ] = 'What should be done with animated PNGs which DO NOT exceed the maximum dimensions below?';
$l['autorsz_setting_max_apng_width_title' ] = 'Maximum animated PNG width';
$l['autorsz_setting_max_apng_width_desc'  ] = 'Set a value in pixels. Zero indicates to use the general "Maximum image width" setting above. The above policies will be applied to uploaded animated PNGs with a width greater than (or not, respectively) this value.';
$l['autorsz_setting_max_apng_height_title'] = 'Maximum animated PNG image height';
$l['autorsz_setting_max_apng_height_desc' ] = 'Set a value in pixels. Zero indicates to use the general "Maximum image height" setting above. The above policies will be applied to uploaded animated PNGs with a height greater (or not, respectively) than this value.';
$l['autorsz_setting_coalesceandoptimise_apng_title'] = 'Coalesce-and-optimise animated PNGs?';
$l['autorsz_setting_coalesceandoptimise_apng_desc'] = 'Coalesce before, and optimise after, resizing animated PNGs? Depending on your Imagick version, changing the value of this setting might avoid glitches in resized images.';
$l['autorsz_setting_excluded_groups_title'] = 'Excluded groups';
$l['autorsz_setting_excluded_groups_desc'] = 'The images attached by members of any groups selected here will <em>not</em> be resized (or altered in any way).';
$l['autorsz_setting_excluded_forums_title'] = 'Excluded forums';
$l['autorsz_setting_excluded_forums_desc'] = 'The images attached in threads in any forums selected here will <em>not</em> be resized (or altered in any way).';
$l['autorsz_setting_watermark_filepath_title'] = 'Watermark file';
$l['autorsz_setting_watermark_filepath_desc' ] = 'The path to a file containing an image to apply as a watermark to all uploaded images (except for animated images when they are not being converted to a static image). May be an absolute path, otherwise, relative paths are taken to be relative to the MyBB root directory. If this setting is empty, or the file does not exist, is unreadable, or is not a valid image, then no watermark is applied.';
$l['autorsz_setting_watermark_valign_title'  ] = 'Watermark vertical alignment';
$l['autorsz_setting_watermark_valign_desc'   ] = 'How to vertically align the watermark file within the watermarked image. (The watermark is resized horizontally to fit the width of the watermarked image).';
$l['autorsz_setting_watermark_save_org_title'] = 'Backup pre-watermark image?';
$l['autorsz_setting_watermark_save_org_desc' ] = 'Choose "Yes" to save a copy of the original, pre-watermarked image (after resizing, if applicable). It will be stored in your uploads directory with the same name as the watermarked image except appended with ".org.pre-wmk". The backup image is only created when a watermark is successfully applied. N.B. Backup images will neither be detected nor deleted by the core Find Orphaned Attachments module.';
$l['autorsz_policy_shrink_anim_if_fs_reduced'] = 'Shrink to fit, retaining animation, if filesize reduced (else: ignore)';
$l['autorsz_policy_shrink_anim_uncond'       ] = 'Shrink to fit, retaining animation, regardless of resulting filesize';
$l['autorsz_policy_shrink_static'            ] = 'Shrink to fit, converting to a static image';
$l['autorsz_policy_static'                   ] = 'Convert to a static image without resizing';
$l['autorsz_policy_ignore'                   ] = 'Ignore: leave as animated, at uploaded dimensions';
$l['autorsz_setting_valign_top'              ] = 'Top';
$l['autorsz_setting_valign_middle'           ] = 'Middle';
$l['autorsz_setting_valign_bottom'           ] = 'Bottom';

// For the recount and rebuild tool.
$l['autorsz_rebuild_lbl'             ] = 'Resize Uploaded Images';
$l['autorsz_rebuild_do_desc'         ] = 'When this is run, existing uploaded images are resized to fit the maximum width and height specified in the Image Auto-Resizer plugin\'s settings.';
$l['autorsz_admin_log_rebuild'       ] = 'Uploaded image resize rebuild/recount run.';
$l['autorsz_rebuild_success'         ] = 'All uploaded images have been successfully resized.';