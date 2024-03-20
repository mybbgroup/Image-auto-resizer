<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 * If not, see <http://www.gnu.org/licenses/>.
 */

// Disallow direct access to this file for security reasons.
if (!defined('IN_MYBB')) {
	die('Direct access to this file is not allowed.');
}

if (defined('IN_ADMINCP')) {
	$plugins->add_hook('admin_config_plugins_activate_commit'   , 'autorsz_hookin__admin_config_plugins_activate_commit');
	$plugins->add_hook('admin_tools_recount_rebuild'            , 'autorsz_hookin__admin_tools_recount_rebuild'            );
	$plugins->add_hook('admin_tools_recount_rebuild_output_list', 'autorsz_hookin__admin_tools_recount_rebuild_output_list');
} else {
	$plugins->add_hook('attachment_end'                         , 'autorsz_hookin__attachment_end'                         );
	$plugins->add_hook('upload_attachment_thumb_start'          , 'autorsz_hookin__upload_attachment_thumb_start'          );
}

function auto_resizer_info() {
	global $lang, $db, $plugins_cache, $admin_session;
	$prefix = 'autorsz_';

	$lang->load('auto_resizer');

	$desc = $lang->autorsz_desc.PHP_EOL;
	$litems = '';

	if (!empty($plugins_cache) && !empty($plugins_cache['active']) && !empty($plugins_cache['active']['auto_resizer'])) {
		if (!empty($admin_session['data']['autorsz_upgrade_success_info'])) {
			$msg_upgrade = $admin_session['data']['autorsz_upgrade_success_info'];
			$litems .= '<li style="list-style-image: url(styles/default/images/icons/success.png)"><div class="success">'.$msg_upgrade.'</div></li>'.PHP_EOL;
			update_admin_session('autorsz_upgrade_success_info', '');
		}

		if (!function_exists('exif_read_data')) {
			$litems .= '<li style="list-style-image: url(styles/default/images/icons/warning.png);">'.$lang->autorsz_desc_no_exif.'</li>'.PHP_EOL;
		}

		$gid = autorsz_get_gid();
		if (!empty($gid)) {
			$litems .= '<li style="list-style-image: url(styles/default/images/icons/custom.png)"><a href="index.php?module=config-settings&amp;action=change&amp;gid='.$gid.'">'.$lang->autorsz_desc_config_settings.'</a></li>'.PHP_EOL;
		}

		$litems .= '<li style="list-style-image: url(styles/default/images/icons/custom.png)">'.$lang->autorsz_desc_resize_existing.'</li>'.PHP_EOL;
	}

	if (!empty($litems)) {
		$desc .= '<ul>'.PHP_EOL.$litems.'</ul>'.PHP_EOL;
	}

	$query = $db->simple_select('settinggroups', 'gid', "name = '{$prefix}settings'", array('limit' => 1));
	$gid   = $db->fetch_field($query, 'gid');
	if ($gid) {}
	$ret = array(
		'name'          => $lang->autorsz_name,
		'description'   => $desc,
		'website'       => 'https://mybb.group/Thread-Image-Auto-Resizer',
		'author'        => 'Laird as a member of the unofficial MyBB Group',
		'authorsite'    => 'https://mybb.group/User-Laird',
		'version'       => '1.1.0-dev',
		'guid'          => '',
		'codename'      => 'auto_resizer',
		'compatibility' => '18*'
	);

	return $ret;
}

function auto_resizer_install() {
	// We don't do anything here. Given that a plugin cannot be installed
	// without being simultaneously activated, it is sufficient to call
	// autorsz_install_or_upgrade() from autorsz_activate().
}

function auto_resizer_uninstall() {
	global $cache;

	autorsz_remove_settings();

	// Remove the plugin's entry from the persistent cache.
	$mybbgrp_plugins = $cache->read('mybbgrp_plugins');
	unset($mybbgrp_plugins['codename']);
	$cache->update('mybbgrp_plugins', $mybbgrp_plugins);
}

function auto_resizer_activate() {
	global $lang, $autorsz_upgrd_msg;

	$info         = auto_resizer_info();
	$from_version = autorsz_get_installed_version();
	$to_version   = $info['version'];
	autorsz_install_or_upgrade($from_version, $to_version);
	if ($from_version !== $to_version) {
		autorsz_set_installed_version($to_version);
		if ($from_version) {
			$autorsz_upgrd_msg = $lang->sprintf($lang->autorsz_upgrade_success_hdr, $lang->autorsz_name, $to_version);
			update_admin_session('autorsz_upgrade_success_info', $lang->sprintf($lang->autorsz_upgrade_success_info, $to_version));
		}
	}
}

function auto_resizer_is_installed() {
	return autorsz_get_gid() != false;
}

/**
 * Performs all tasks required to install or upgrade this plugin.
 *
 * @param string $from_version The version, as a "PHP-standardized" version
 *                             number string, from which we are upgrading, or
 *                             false if we are installing rather than upgrading.
 * @param string $to_version   The version, as a "PHP-standardized" version
 *                             number string, to which we are upgrading or at
 *                             which we are installing.
 */
function autorsz_install_or_upgrade($from_version = null, $to_version = null) {
	global $db;

	if (empty($to_version)) {
		$info = codename_info();
		$to_version = $info['version'];
	}

	// Create or update this plugin's settings.
	autorsz_create_or_update_settings();
}

/**
 * Retrieves from the persistent cache the installed version of this plugin.
 *
 * @return string $version The installed version of this plugin as a "PHP-
 *                         standardized" version number string, or false if the
 *                         plugin is not yet installed (in that case, we are
 *                         presumably in the process of doing so).
 */
function autorsz_get_installed_version() {
	global $cache;

	$mybbgrp_plugins = $cache->read('mybbgrp_plugins');

	return !empty($mybbgrp_plugins['auto_resizer']['version'])
	         ? $mybbgrp_plugins['auto_resizer']['version']
	         : false;
}

/**
 * Sets and stores to the persistent cache the installed version of this plugin.
 *
 * @param string $version This plugin's current version, as a "PHP-standardized"
 *                        version number string.
 */
function autorsz_set_installed_version($version) {
	global $cache;

	$mybbgrp_plugins = $cache->read('mybbgrp_plugins');
	if (!isset($mybbgrp_plugins['auto_resizer'])) {
		$mybbgrp_plugins['auto_resizer'] = array();
	}
	$mybbgrp_plugins['auto_resizer']['version'] = $version;

	$cache->update('mybbgrp_plugins', $mybbgrp_plugins);
}

/**
 * Gets the gid of this plugin's setting group, if any.
 *
 * @return The gid or false if the setting group does not exist.
 */
function autorsz_get_gid() {
	global $db;
	$prefix = 'autorsz_';

	$query = $db->simple_select('settinggroups', 'gid', "name = '{$prefix}settings'", array(
		'order_by' => 'gid',
		'order_dir' => 'DESC',
		'limit' => 1
	));

	return $db->fetch_field($query, 'gid');
}

/**
 * Creates or updates this plugin's settings.
 */
function autorsz_create_or_update_settings() {
	global $db, $lang;
	$prefix = 'autorsz_';

	$lang->load('auto_resizer');

	$setting_group = array(
		'name'         => $prefix.'settings',
		'title'        => $db->escape_string($lang->autorsz_settings_title),
		'description'  => $db->escape_string($lang->autorsz_settings_desc),
		'isdefault'    => 0
	);
	$gid = autorsz_get_gid();
	if (empty($gid)) {
		// Insert the plugin's settings group into the database.
		$query = $db->query('SELECT MAX(disporder) as max_disporder FROM '.TABLE_PREFIX.'settinggroups');
		$setting_group['disporder'] = intval($db->fetch_field($query, 'max_disporder')) + 1;
		$db->insert_query('settinggroups', $setting_group);
		$gid = $db->insert_id();
	} else {
		// Update the plugin's settings group in the database.
		$db->update_query('settinggroups', $setting_group, "gid='{$gid}'");
	}

	// Get the plugin's existing settings, if any.
	$existing_settings = array();
	$query = $db->simple_select('settings', 'name', "gid='{$gid}'");
	while ($setting = $db->fetch_array($query)) {
		$existing_settings[] = $setting['name'];
	}

	// Define the plugin's (new/updated) settings.
	$settings = array(
		'max_width' => array(
			'title'       => $lang->autorsz_setting_max_width_title,
			'description' => $lang->autorsz_setting_max_width_desc,
			'optionscode' => 'numeric',
			'value'       => '900'
		),
		'max_height' => array(
			'title'       => $lang->autorsz_setting_max_height_title,
			'description' => $lang->autorsz_setting_max_height_desc,
			'optionscode' => 'numeric',
			'value'       => '900'
		),
		'oversize_agif_policy' => array(
			'title'       => $lang->autorsz_setting_oversize_agif_policy_title,
			'description' => $lang->autorsz_setting_oversize_agif_policy_desc,
			'optionscode' => "select\nshrink_anim_if_fs_reduced={$lang->autorsz_policy_shrink_anim_if_fs_reduced}\nshrink_anim_uncond={$lang->autorsz_policy_shrink_anim_uncond}\nshrink_static={$lang->autorsz_policy_shrink_static}\nstatic={$lang->autorsz_policy_static}\nignore={$lang->autorsz_policy_ignore}",
			'value'       => 'shrink_anim_if_fs_reduced',
		),
		'withinsize_agif_policy' => array(
			'title'       => $lang->autorsz_setting_withinsize_agif_policy_title,
			'description' => $lang->autorsz_setting_withinsize_agif_policy_desc,
			'optionscode' => "select\nignore={$lang->autorsz_policy_ignore}\nstatic={$lang->autorsz_policy_static}",
			'value'       => 'ignore',
		),
		'max_agif_width' => array(
			'title'       => $lang->autorsz_setting_max_agif_width_title,
			'description' => $lang->autorsz_setting_max_agif_width_desc,
			'optionscode' => 'numeric',
			'value'       => '900'
		),
		'max_agif_height' => array(
			'title'       => $lang->autorsz_setting_max_agif_height_title,
			'description' => $lang->autorsz_setting_max_agif_height_desc,
			'optionscode' => 'numeric',
			'value'       => '900'
		),
		'coalesceandoptimise_agif' => array(
			'title'       => $lang->autorsz_setting_coalesceandoptimise_agif_title,
			'description' => $lang->autorsz_setting_coalesceandoptimise_agif_desc,
			'optionscode' => 'yesno',
			'value'       => '1'
		),
		'oversize_apng_policy' => array(
			'title'       => $lang->autorsz_setting_oversize_apng_policy_title,
			'description' => $lang->autorsz_setting_oversize_apng_policy_desc,
			'optionscode' => "select\nshrink_anim_if_fs_reduced={$lang->autorsz_policy_shrink_anim_if_fs_reduced}\nshrink_anim_uncond={$lang->autorsz_policy_shrink_anim_uncond}\nshrink_static={$lang->autorsz_policy_shrink_static}\nstatic={$lang->autorsz_policy_static}\nignore={$lang->autorsz_policy_ignore}",
			'value'       => 'shrink_anim_if_fs_reduced',
		),
		'withinsize_apng_policy' => array(
			'title'       => $lang->autorsz_setting_withinsize_apng_policy_title,
			'description' => $lang->autorsz_setting_withinsize_apng_policy_desc,
			'optionscode' => "select\nignore={$lang->autorsz_policy_ignore}\nstatic={$lang->autorsz_policy_static}",
			'value'       => 'ignore',
		),
		'max_apng_width' => array(
			'title'       => $lang->autorsz_setting_max_apng_width_title,
			'description' => $lang->autorsz_setting_max_apng_width_desc,
			'optionscode' => 'numeric',
			'value'       => '900'
		),
		'max_apng_height' => array(
			'title'       => $lang->autorsz_setting_max_apng_height_title,
			'description' => $lang->autorsz_setting_max_apng_height_desc,
			'optionscode' => 'numeric',
			'value'       => '900'
		),
		'coalesceandoptimise_apng' => array(
			'title'       => $lang->autorsz_setting_coalesceandoptimise_apng_title,
			'description' => $lang->autorsz_setting_coalesceandoptimise_apng_desc,
			'optionscode' => 'yesno',
			'value'       => '1'
		),
		'excluded_groups' => array(
			'title'       => $lang->autorsz_setting_excluded_groups_title,
			'description' => $lang->autorsz_setting_excluded_groups_desc,
			'optionscode' => 'groupselect',
			'value'       => ''
		),
		'excluded_forums' => array(
			'title'       => $lang->autorsz_setting_excluded_forums_title,
			'description' => $lang->autorsz_setting_excluded_forums_desc,
			'optionscode' => 'forumselect',
			'value'       => ''
		),
		'watermark_filepath' => array(
			'title'       => $lang->autorsz_setting_watermark_filepath_title,
			'description' => $lang->autorsz_setting_watermark_filepath_desc,
			'optionscode' => 'text',
			'value'       => ''
		),
		'watermark_valign' => array(
			'title'       => $lang->autorsz_setting_watermark_valign_title,
			'description' => $lang->autorsz_setting_watermark_valign_desc,
			'optionscode' => "radio\ntop={$lang->autorsz_setting_valign_top}\nmiddle={$lang->autorsz_setting_valign_middle}\nbottom={$lang->autorsz_setting_valign_bottom}",
			'value'       => 'top',
		),
		'watermark_save_org' => array(
			'title'       => $lang->autorsz_setting_watermark_save_org_title,
			'description' => $lang->autorsz_setting_watermark_save_org_desc,
			'optionscode' => 'yesno',
			'value'       => '0',
		),
	);

	// Delete existing settings no longer present in the plugin's current version.
	$new_settings = array_map(function($e) use ($prefix) {return $prefix.$e;}, array_keys($settings));
	$to_delete = array_diff($existing_settings, $new_settings);
	if ($to_delete) {
		$names_esc_cs_qt = "'".implode("', '", array_map([$db, 'escape_string'], $to_delete))."'";
		$db->delete_query('settings', "name IN ({$names_esc_cs_qt}) AND gid='{$gid}'");
	}

	// Insert into, or update in, the database each of this plugin's settings.
	$disporder = 1;
	$inserts = [];
	foreach ($settings as $name => $setting) {
		$fields = array(
			'name'        => $db->escape_string($prefix.$name),
			'title'       => $db->escape_string($setting['title']),
			'description' => $db->escape_string($setting['description']),
			'optionscode' => $db->escape_string($setting['optionscode']),
			'value'       => $db->escape_string($setting['value']),
			'disporder'   => $disporder,
			'gid'         => $gid,
			'isdefault'   => 0
		);
		if (in_array($prefix.$name, $existing_settings)) {
			// Update the already-existing setting while retaining its value.
			unset($fields['value']);
			$db->update_query('settings', $fields, "name='{$prefix}{$name}' AND gid='{$gid}'");
		} else {
			// Queue the new setting for insertion.
			$inserts[] = $fields;
		}
		$disporder++;
	}

	if ($inserts) {
		// Insert the queued new settings.
		$db->insert_query_multiple('settings', $inserts);
	}

	rebuild_settings();
}

function autorsz_remove_settings() {
	global $db;
	$prefix = 'autorsz_';

	$rebuild = false;
	$query = $db->simple_select('settinggroups', 'gid', "name = '{$prefix}settings'");
	while ($gid = $db->fetch_field($query, 'gid')) {
		$db->delete_query('settinggroups', "gid='{$gid}'");
		$db->delete_query('settings', "gid='{$gid}'");
		$rebuild = true;
	}
	if ($rebuild) {
		rebuild_settings();
	}
}

/**
 * Assigns any value of this plugin's "upgrade success" message global variable
 * (conditionally set in autorsz_activate()) to core's global $message
 * variable, which core then displays at the top of the post-activation reload
 * of the ACP Plugins page. With this, we effectively replace the default
 * message "The selected plugin has been activated successfully." with our
 * custom message "Image Auto-Resizer has been activated successfully and
 * upgraded to version [x.y.z]."
 */
function autorsz_hookin__admin_config_plugins_activate_commit() {
	global $message, $autorsz_upgrd_msg;

	if (!empty($autorsz_upgrd_msg)) {
		$message = $autorsz_upgrd_msg;
	}
}

function autorsz_hookin__admin_tools_recount_rebuild() {
	global $db, $mybb, $lang;
	$prefix = 'autorsz_';

	$lang->load('auto_resizer');

	if ($mybb->request_method == 'post') {
		if (!isset($mybb->input['page']) || $mybb->get_input('page', MyBB::INPUT_INT) < 1) {
			$mybb->input['page'] = 1;
		}

		if (isset($mybb->input['do_autorsz_imgs'])) {
			if ($mybb->input['page'] == 1) {
				// Log admin action
				log_admin_action($lang->autorsz_admin_log_rebuild);
			}

			if (!$mybb->get_input('num_autorsz_imgs', MyBB::INPUT_INT)) {
				$mybb->input['num_autorsz_imgs'] = 40;
			}

			$page = $mybb->get_input('page', MyBB::INPUT_INT);
			$per_page = $mybb->get_input('num_autorsz_imgs', MyBB::INPUT_INT);
			if ($per_page <= 0) {
				$per_page = 40;
			}
			$start = ($page-1) * $per_page;
			$end = $start + $per_page;

			$query = $db->simple_select('attachments', 'COUNT(*) AS num_imgs');
			$num_imgs = $db->fetch_field($query, 'num_imgs');

			$query2 = $db->query(<<<EOF
SELECT          aid, attachname, filename, filesize, a.uid, fid
FROM            {$db->table_prefix}attachments a
LEFT OUTER JOIN {$db->table_prefix}posts p
ON              a.pid = p.pid
ORDER BY        aid ASC
LIMIT           $start, $per_page
EOF
			);
			while ($row = $db->fetch_array($query2)) {
				// Don't try to resize files that aren't images, or at least that
				// are not images of a type that we *can* resize.
				// Also, don't resize images that have been attached by members of
				// excluded groups or in threads in excluded forums.
				if (in_array(get_extension($row['filename']), array('gif', 'png', 'jpg', 'jpeg', 'jpe'))
				    &&
				    !is_member($mybb->settings[$prefix.'excluded_groups'], $row['uid'])
				    &&
				    !($mybb->settings[$prefix.'excluded_forums'] == -1
				      ||
				      in_array($row['fid'], explode(',', $mybb->settings[$prefix.'excluded_forums']))
				     )
				) {
					$ret = autorsz_resize_file($row['attachname']);
					if ($ret !== false && $ret != $row['filesize']) {
						$db->update_query('attachments', array('filesize' => $ret), "aid='{$row['aid']}'");
					}
				}
			}

			check_proceed($num_imgs, $end, ++$page, $per_page, 'num_autorsz_imgs', 'do_autorsz_imgs', $lang->autorsz_rebuild_success);
		}
	}
}

function autorsz_hookin__admin_tools_recount_rebuild_output_list() {
	global $lang, $form_container, $form;
	$lang->load('auto_resizer');

	$form_container->output_cell("<label>{$lang->autorsz_rebuild_lbl}</label><div class=\"description\">{$lang->autorsz_rebuild_do_desc}</div>");
	$form_container->output_cell($form->generate_numeric_field('num_autorsz_imgs', 40, array('style' => 'width: 150px;', 'min' => 0)));
	$form_container->output_cell($form->generate_submit_button($lang->go, array('name' => 'do_autorsz_imgs')));
	$form_container->construct_row();
}

function autorsz_hookin__upload_attachment_thumb_start($attacharray) {
	global $plugins_cache, $cache, $mybb, $forum;
	$prefix = 'autorsz_';

	if (!is_array($plugins_cache)) {
		$plugins_cache = $cache->read('plugins');
	}
	$active_plugins = !empty($plugins_cache['active']) ? $plugins_cache['active'] : [];
	if ($active_plugins
	    &&
	    !empty($active_plugins['auto_resizer'])
	    &&
	    // Don't resize images that have been attached by members of excluded groups
	    // or in threads in excluded forums.
	    !is_member($mybb->settings[$prefix.'excluded_groups'], $attacharray['uid'])
	    &&
	    !($mybb->settings[$prefix.'excluded_forums'] == -1
	      ||
	      in_array($forum['fid'], explode(',', $mybb->settings[$prefix.'excluded_forums']))
	     )
	) {
		$res = autorsz_resize_file($attacharray['attachname']);
		if ($res !== false) {
			$attacharray['filesize'] = $res;
		}
	}

	return $attacharray;
}

function autorsz_fix_image_orientation($filepath) {
	if (!function_exists('exif_read_data')) {
		return false;
	}

	$ret = false;
	$filepath_rot = str_replace('.attach', '.rotated', $filepath);
	$imgsz = getimagesize($filepath);
	if ($imgsz) {
		$imgtype = image_type_to_extension($imgsz[2], /*$include_dot = */false);
		if ($imgtype) {
			if ($imgtype == 'jpg') {
				$imgtype = 'jpeg';
			}
			$create_func = 'imagecreatefrom'.$imgtype;
			$save_func = 'image'.$imgtype;
			if (@function_exists($create_func) && @function_exists($save_func)) {
				if (($image = @$create_func($filepath))) {
					// Inspired by https://stackoverflow.com/a/13963783
					$exif_data = @exif_read_data($filepath);
					if (isset($exif_data['Orientation']) && $exif_data['Orientation'] > 1) {
						$flip_type = array_values([0, 0, IMG_FLIP_HORIZONTAL,   0, IMG_FLIP_VERTICAL, IMG_FLIP_HORIZONTAL,   0, IMG_FLIP_HORIZONTAL,  0])[$exif_data['Orientation']];
						$rot_angle = array_values([0, 0,                   0, 180,                 0,                  90, -90,                 -90, 90])[$exif_data['Orientation']];
						$flipped = false;
						if ($flip_type != 0) {
							$flipped = imageflip($image, $flip_type);
						}
						if ($rot_angle != 0) {
							$rot_image = imagerotate($image, $rot_angle, 0);
							if ($rot_image) {
								if ($save_func($rot_image, $filepath_rot)) {
									if (!@rename($filepath_rot, $filepath)) {
										@unlink($filepath_rot);
									} else {
										clearstatcache();
										$ret = filesize($filepath);
									}
								}
								imagedestroy($rot_image);
							}
						} else if ($flipped) {
							if ($save_func($image, $filepath_rot)) {
								if (!@rename($filepath_rot, $filepath)) {
									@unlink($filepath_rot);
								} else {
									clearstatcache();
									$ret = filesize($filepath);
								}
							}
						}
					}
					imagedestroy($image);
				}
			}
		}
	}

	return $ret;
}

/**
 * Resizes and/or watermarks an image file according to this plugin's settings, all of which can be
 * overridden. The image file is generally an attachment under the core uploads directory, but this
 * directory too can be overridden (see below). On success, the file is overwritten.
 *
 * @param string $attachname The path to the image file relative to the path specified in MyBB
 *                           core's `uploadspath` setting (which can be overridden - see below).
 * @param array  $settings_overrides An array each key of which is either the name of one of this
 *                                   plugin's applicable settings (but excluding the `autorsz_`
 *                                   prefix) or `uploadspath`, (referencing the MyBB core setting).
 *                                   Each value associated with a key is the corresponding value
 *                                   to override the setting value with. If no key-value pair exists
 *                                   for a setting then it is not overridden. Valid keys are all
 *                                   those of the array assigned to `$settings` in
 *                                   `autorsz_create_or_update_settings()` except for
 *                                   'excluded_groups' and 'excluded_forums'.
 *
 * @return Mixed Boolean false if resizing and/or watermarking failed or were inapplicable,
 *               otherwise an integer indicating the number of bytes in the resized file.
 */
function autorsz_resize_file($attachname, $settings_overrides = []) {
	$fn_get_arg_val = function($arg_name, $omit_prefix = false) use ($settings_overrides) {
		global $mybb;
		$prefix = 'autorsz_';

		return isset($settings_overrides[$arg_name]) ? $settings_overrides[$arg_name] : $mybb->settings[($omit_prefix ? '' : $prefix).$arg_name];
	};

	$uploadspath = mk_path_abs($fn_get_arg_val('uploadspath', /*$omit_prefix = */true));
	$ret = false;
	$filepath_org = $uploadspath.'/'.$attachname;
	$filename_rsz = $attachname.'.resized';
	$filepath_rsz = $uploadspath.'/'.$filename_rsz;
	if (file_exists($filepath_org)) {
		$atype = $fix_orientation = $did_shrink = $do_static_shrink = false;
		if (autorsz_is_agif($filepath_org)) {
			$atype = 'gif';
		} else if (autorsz_is_apng($filepath_org)) {
			$atype = 'png';
		}
		if (!$atype) {
			$fix_orientation = $do_static_shrink = true;
			$max_height = $fn_get_arg_val('max_height');
			$max_width = $fn_get_arg_val('max_width');
		} else {
			$max_width = $fn_get_arg_val("max_a{$atype}_width");
			if (empty($max_width)) {
				$max_width = $fn_get_arg_val('max_width');
			}
			$max_height = $fn_get_arg_val("max_a{$atype}_height");
			if (empty($max_height)) {
				$max_height = $fn_get_arg_val('max_height');
			}

			// This call is slightly inefficient given that getimagesize()
			// is potentially called again at the start of generate_thumbnail(),
			// and that, otherwise, we potentially unnecessarily iterate over
			// the first animation frame at the beginning of autorsz_shrink_anim_img()
			// to again get the width+height of the image, but it's a very fast
			// function, so this is not a huge problem.
			$imgdesc = getimagesize($filepath_org);

			$img_width  = $imgdesc[0];
			$img_height = $imgdesc[1];

			$is_oversize = ($img_width > $max_width || $img_height > $max_height);
			$sz_cmp = $is_oversize ? 'oversize' : 'withinsize';

			// Determine the applicable policy setting out of the four possibilities.
			$policy = $fn_get_arg_val("{$sz_cmp}_a{$atype}_policy");

			// Implement that policy.
			switch ($policy) {
			case 'shrink_anim_if_fs_reduced':
			case 'shrink_anim_uncond':
				$rsz_func = "autorsz_shrink_a{$atype}";
				if ($atype == 'png') {
					// Imagick refuses to save animated PNGs *unless* the saved
					// filename ends in '.apng' ('.png' is insufficient).
					$filename_rsz .= '.apng';
					$filepath_rsz = $uploadspath.'/'.$filename_rsz;
				}
				$coalesce_and_optimise = ($fn_get_arg_val('coalesceandoptimise_a'.$atype) == 1);
				$result = $rsz_func($filepath_org, $filepath_rsz, $max_height, $max_width, $coalesce_and_optimise);
				if ($result <= 0) {
					return false;
				} else if ($policy == 'shrink_anim_if_fs_reduced'
				           &&
				           filesize($filepath_rsz) >= filesize($filepath_org)
				          ) {
					@unlink($filepath_rsz);
					return false;
				} else	$did_shrink = true;
				break;
			case 'shrink_static':
				$do_static_shrink = true;
				break;
			case 'static':
				// We want to keep the current size, so set the maximums to the current
				// dimensions for when they're passed to generate_thumbnail()
				$max_width  = $img_width;
				$max_height = $img_height;
				$do_static_shrink = true;
				break;
			case 'ignore':
			default:
				// Do nothing, because both $fix_orientation and $do_static_shrink
				// are already false.
				break;
			}
		}

		if ($do_static_shrink) {
			if ($fix_orientation) {
				$ret = autorsz_fix_image_orientation($filepath_org);
			}
			require_once MYBB_ROOT.'inc/functions_image.php';
			$resized = generate_thumbnail($filepath_org, $uploadspath, $filename_rsz, $max_height, $max_width);
			$did_shrink = !empty($resized['filename']);
			if ($did_shrink) {
				$filepath_rsz = $uploadspath.'/'.$resized['filename'];
			}
		}

		if ($did_shrink && !@rename($filepath_rsz, $filepath_org)) {
			@unlink($filepath_rsz);
		}

		$did_wmk = false;
		// Watermark the uploaded image if the preconditions are met
		if (!empty($fn_get_arg_val('watermark_filepath'))
		    &&
		    (!$atype || $do_static_shrink)
		    &&
		    in_array(($type = getimagesize($filepath_org)[2]), array(IMAGETYPE_GIF, IMAGETYPE_PNG, IMAGETYPE_JPEG))
		    &&
		    is_readable(($wmk_filepath = autorsz_mk_path_abs($fn_get_arg_val('watermark_filepath'))))
		    &&
		    in_array(($wm_ext = get_extension($wmk_filepath)), array('gif', 'png', 'jpg', 'jpeg', 'jpe'))
		) {
			$save_filepath = "{$filepath_org}.wm";
			$wm_type       = ($wm_ext == 'gif' ? 'gif' : ($wm_ext == 'png' ? 'png' : 'jpeg'));
			$type          = ($type == IMAGETYPE_GIF ? 'gif' : ($type == IMAGETYPE_PNG ? 'png' : 'jpeg'));
			$valign_set    = $fn_get_arg_val('watermark_valign');
			$valign        = ($valign_set == 'bottom' ? AUTORSZ_WMK_VALIGN_BOTTOM : ($valign_set == 'middle' ? AUTORSZ_WMK_VALIGN_MIDDLE : AUTORSZ_WMK_VALIGN_TOP));
			// It would be more efficient to combine this function's functionality with
			// that of generate_thumbnail() above, so we don't have to open, manipulate,
			// and save the image (file) twice, but generate_thumbnail() is a core
			// function that we can't modify, and doing things this way, separately,
			// avoids the need to duplicate its functionality.
			$did_wmk       = autorsz_watermark($filepath_org, $type, $wmk_filepath, $wm_type, $save_filepath, $valign);
			if ($did_wmk && file_exists($save_filepath)) {
				if ($fn_get_arg_val('watermark_save_org') == 1) {
					rename($filepath_org, "{$filepath_org}.org.pre-wmk");
				}
				rename($save_filepath, $filepath_org);
			} else	$did_wmk = false;
		}

		if ($did_shrink || $did_wmk) {
			clearstatcache();
			$ret = filesize($filepath_org);
		}
	}

	// Early return possible
	return $ret;
}

// Version 1.0.3 of this plugin had a bug which sometimes caused the wrong file
// size to be stored in the DB, resulting in the wrong size being sent to
// browsers when viewing an image, causing display issues.
//
// Here, we correct for that historical bug.
function autorsz_hookin__attachment_end() {
	global $attachment, $uploadspath_abs, $db;

	$true_size = @filesize($uploadspath_abs.'/'.$attachment['attachname']);
	if ($true_size !== false && $attachment['filesize'] != $true_size) {
		$attachment['filesize'] = $true_size;
		$fields = $attachment;
		$aid = $attachment['aid'];
		unset($fields['aid']);
		$db->update_query('attachments', $fields, "aid='$aid'");
	}
}

/**
 * Shrink the animated image (generally, in GIF/APNG format) in a file if the image is oversize.
 *
 * Maintain aspect ratio.
 *
 * Use (and require) the Imagick PHP (PECL) extension; return -1 if the extension is missing.
 *
 * The file at $filepath_rsz will only have been written to with *valid* data when a positive integer is returned.
 *
 * @param string $filepath_org The path to the image file.
 * @param string $filepath_rsz The path to which to save the resized image as a file.
 * @param integer $max_height The maximum image height in pixels. Images with a greater height are resized to fit.
 * @param integer $max_width The maximum image width in pixels. Images with a greater width are resized to fit.
 * @param boolean $coalesce_and_optimise Whether to coalesce image frames prior to resizing,
 *                                       and optimise them after resizing.
 * @param string $prefix Any prefix with which to prepend $filepath_org when opening the file with Imagick.
 * @return integer Positive if the file was resized; zero if the file was not oversize; negative if an error occurred.
 */
function autorsz_shrink_anim_img($filepath_org, $filepath_rsz, $max_height, $max_width, $coalesce_and_optimise, $prefix) {
	if (!class_exists('Imagick')) {
		return -1;
	}

	try {
		$animation = new Imagick($prefix.$filepath_org);
		foreach ($animation as $frame) {
			$pg = $frame->getImagePage();
			$img_height = $pg['height'];
			$img_width  = $pg['width' ];
			break;
		}

		if ($max_width >= $img_width && $max_height >= $img_height) {
			return 0;
		}

		$scale_h = $max_height/$img_height;
		$scale_w = $max_width /$img_width;
		$scale = min($scale_h, $scale_w);

		$h = (int)round($img_height * $scale);
		$w = (int)round($img_width  * $scale);

		if ($coalesce_and_optimise) {
			$animation = $animation->coalesceImages();
		}
		foreach ($animation as $frame) {
			$frame->scaleImage($w, $h);
		}
		if ($coalesce_and_optimise) {
			$animation->optimizeImageLayers();
		}
		$animation->writeImages($filepath_rsz, true);
	} catch (Throwable $t) {
		@unlink($filepath_rsz);
		return -1;
	}

	// Earlier returns possible.
	return 1;
}

/**
 * Shrink the animated GIF in a file if the image is oversize.
 *
 * Maintain aspect ratio.
 *
 * Use (and require) the Imagick PHP (PECL) extension; return -1 if the extension is missing.
 *
 * The file at $filepath_rsz will only have been written to with *valid* data when a positive integer is returned.
 *
 * @param string $filepath_org The path to the image file.
 * @param string $filepath_rsz The path to which to save the resized image as a file.
 * @param integer $max_height The maximum image height in pixels. Images with a greater height are resized to fit.
 * @param integer $max_width The maximum image width in pixels. Images with a greater width are resized to fit.
 * @return integer Positive if the file was resized; zero if the file was not oversize; negative if an error occurred.
 */
function autorsz_shrink_agif($filepath_org, $filepath_rsz, $max_height, $max_width, $coalesce_and_optimise) {
	return autorsz_shrink_anim_img($filepath_org, $filepath_rsz, $max_height, $max_width, $coalesce_and_optimise, /*$prefix = */'');
}

/**
 * Shrink the animated PNG in a file if the image is oversize.
 *
 * Maintain aspect ratio.
 *
 * Use (and require) the Imagick PHP (PECL) extension; return -1 if the extension is missing.
 *
 * The file at $filepath_rsz will only have been written to with *valid* data when a positive integer is returned.
 *
 * @param string $filepath_org The path to the image file.
 * @param string $filepath_rsz The path to which to save the resized image as a file.
 *                              !!! N.B. The file extension of this file MUST be 'apng', NOT 'png'. !!!
 *                              This is a quirk/requirement of the Imagick extension.
 *                              Failure to heed this requirement will result in failure to write to this file.
 * @param integer $max_height The maximum image height. Images with a greater height are resized to fit.
 * @param integer $max_width The maximum image width. Images with a greater width are resized to fit.
 * @return integer Positive if the file was resized; zero if the file was not oversize; negative if an error occurred.
 */
function autorsz_shrink_apng($filepath_org, $filepath_rsz, $max_height, $max_width, $coalesce_and_optimise) {
	return autorsz_shrink_anim_img($filepath_org, $filepath_rsz, $max_height, $max_width, $coalesce_and_optimise, /*$prefix = */'apng:');
}

/**
 * Determine whether or not a file, if a valid PNG, is an animated PNG.
 *
 * Note that validation is only partial, so the return of this function is only
 * meaningful and definitive if the file otherwise validates as a PNG.
 *
 * Heavily based on: https://stackoverflow.com/a/68618296
 *
 * @param string $filepath The path to the file potentially containing an animated PNG.
 * @return boolean True if the file is an animated PNG; false if not.
 */
function autorsz_is_apng($filepath) {
	$fh = fopen($filepath, 'rb');
	if (!$fh) {
		return false;
	}

	// Validate header
	$header = fread($fh, 8);
	if ($header !== "\x89PNG\r\n\x1A\n") {
		fclose($fh);
		return false;
	}

	while (!feof($fh)) {
		$bytes = fread($fh, 8);
		if ($bytes === false || strlen($bytes) < 8) {
			fclose($fh);
			return false;
		}
		$chunk = unpack('Nlength/a4name', $bytes);
		if ($chunk === false) {
			fclose($fh);
			return false;
		}
		switch ($chunk['name']) {
		case 'acTL':
			fclose($fh);
			return true;
		case 'IDAT':
			fclose($fh);
			return false;
		}
		$len = $chunk['length'] + 4;
		$bytes = fread($fh, $len);
		if (!$bytes || strlen($bytes) < $len) {
			fclose($fh);
			return false;
		}
	}

	fclose($fh);
	// Earlier returns possible
	return false;
}

/**
 * Determine whether or not a file, if a valid GIF, is an animated GIF.
 *
 * Note that validation is only partial, so the return of this function is only
 * meaningful and definitive if the file otherwise validates as a GIF.
 *
 * This function was created by massively stripping down and refactoring the
 * Decoder class from the repository:
 *
 * https://github.com/stil/gif-endec
 *
 * which seems to be MIT-licensed:
 *
 * https://github.com/stil/gif-endec/blob/9b9b7d4957a2283bdbd25d8a667a41c419c125f5/composer.json#L5
 *
 * @param string $filepath The path to the file potentially containing an animated GIF.
 * @return boolean True if the file is an animated GIF; false if not.
 */
function autorsz_is_agif($filepath) {
	$fh = fopen($filepath, 'rb');
	if (!$fh) {
		return false;
	}

	// Validate header
	$header = fread($fh, 6);
	if ($header !== 'GIF89a' && $header !== 'GIF87a') {
		fclose($fh);
		return false;
	}

	// Read/validate logical screen descriptor
	$bytes = fread($fh, 7);
	if (!$bytes || strlen($bytes) < 7) {
		fclose($fh);
		return false;
	}
	$buffer = array_values(unpack('C*', $bytes));
	$_gct_flag = $buffer[4] & 0x80 ? 1 : 0; // 1000 0000
	$_gctSize  = $buffer[4] & 0x07;         // 0000 0111

	// Read (seek over) global colour table (if applicable)
	if ($_gct_flag == 1) {
		$len = 3 * (2 << $_gctSize);
		$bytes = fread($fh, $len);
		if (!$bytes || strlen($bytes) < $len) {
			fclose($fh);
			return false;
		}
	}

	$frame_count = 0;
	$cycle = true;
	do {
		if (($c = fgetc($fh)) === false) {
			fclose($fh);
			return false;
		}
		$ord = ord($c);
		switch ($ord) {
		case 0x21:
			// Read/validate graphic control extension
			if (($c = fgetc($fh)) === false) {
				fclose($fh);
				return false;
			}
			while (true) {
				$c = fread($fh, 1);
				if ($c === false) {
					fclose($fh);
					return false;
				}
				if (($u = ord($c)) == 0x00) {
					break;
				}
				$bytes = fread($fh, $u);
				if (!$bytes || strlen($bytes) < $u) {
					fclose($fh);
					return false;
				}
			}
			break;
		case 0x2C:
			// Read/validate image descriptor
			$bytes = fread($fh, 9);
			if (!$bytes || strlen($bytes) < 9) {
				fclose($fh);
				return false;
			}
			$screen = array_values(unpack('C*', $bytes));
			$gct_flag = ($screen[8] & 0x80) == 0x80;
			$code = $gct_flag ? ($screen[8] & 0x07) : $_gctSize;
			$size = 2 << $code;
			/**
			* GIF Data Begin
			*/
			if ($gct_flag) {
				$len = 3 * $size;
				$bytes = fread($fh, $len);
				if (!$bytes || strlen($bytes) < $len) {
					fclose($fh);
					return false;
				}
			}
			if (fgetc($fh) === false) {
				fclose($fh);
				return false;
			}
			while (true) {
				$block_size_raw = fread($fh, 1);
				if ($block_size_raw === false) {
					fclose($fh);
					return false;
				}
				$block_size = ord($block_size_raw);
				if ($block_size == 0x00) {
					break;
				}
				$bytes = fread($fh, $block_size);
				if (!$bytes || strlen($bytes) < $block_size) {
					fclose($fh);
					return false;
				}
			}

			$frame_count++;
			if ($frame_count >= 2) {
				$cycle = false;
			}
			break;
		case 0x3B:
			$cycle = false;
			break;
		}
	} while ($cycle);

	fclose($fh);
	// Earlier returns possible
	return $frame_count >= 2;
}

define('AUTORSZ_WMK_VALIGN_TOP'   , 1);
define('AUTORSZ_WMK_VALIGN_MIDDLE', 2);
define('AUTORSZ_WMK_VALIGN_BOTTOM', 3);
/**
 * Apply a watermark image to an original image.
 * @param string $org_filepath The path to the file containing the image to which to apply the watermark.
 * @param string $org_type The type of image at $org_filepath (one of jpeg, gif, and png).
 * @param string $wmk_filepath The path to the file containing the watermark image to apply.
 * @param string $wmk_type The type of image at $wmk_filepath (one of jpeg, gif, and png).
 * @param string $save_filepath The path to the file to which to save the watermarked image.
 * @param int $vert_align The vertical location to which to apply the watermark (the watermark
 *                        is resized horizontally to fit the width of the original image).
 *                        If not one of AUTORSZ_WMK_VALIGN_TOP, AUTORSZ_WMK_VALIGN_MIDDLE,
 *                        AUTORSZ_WMK_VALIGN_BOTTOM, then top alignment (AUTORSZ_WMK_VALIGN_TOP)
 *                        is assumed.
 * @return boolean True if and only if the watermark was successfully applied, in which case
 *                 $save_filepath references a valid file containing the watermarked image.
 */
function autorsz_watermark($org_filepath, $org_type, $wmk_filepath, $wmk_type, $save_filepath, $vert_align = AUTORSZ_WMK_VALIGN_TOP) {
	// Load the stamp and the photo to apply the watermark to
	$create_func = "imagecreatefrom{$org_type}";
	$im = $create_func($org_filepath);
	if ($im === false) {
		return false;
	}
	$wmk_create_func = "imagecreatefrom{$wmk_type}";
	$stamp = $wmk_create_func($wmk_filepath);

	$imgw = imagesx($im);
	$imgh = imagesy($im);
	$stampw = imagesx($stamp);
	$stamph = imagesy($stamp);

	$new_width = $imgw;
	$new_height = $stamph * $new_width / $stampw;
	if ($new_height > $imgh) {
		$new_height = $imgh;
		$new_width = $stampw * $new_height / $stamph;
	}
	$destx = 0;
	$desty = max(0, $vert_align == AUTORSZ_WMK_VALIGN_MIDDLE
	           ? round($imgh/2) - round($new_height/2)
		   : (
		      $vert_align == AUTORSZ_WMK_VALIGN_BOTTOM
		        ? $imgh - $new_height
		        : 0
		     )
		 );

	// The following code is heavily based on a StackOverflow answer,
	// but I have lost track of which one, and can't seem to dredge it
	// up by googling.

	// Convert $im to truecolor
	if (!imageistruecolor($im)) {
		$original_transparency = imagecolortransparent($im);
		// We have a transparent color
		if ($original_transparency >= 0 && $original_transparency < imagecolorstotal($im)) {
			// Get the actual transparent color
			$rgb = imagecolorsforindex($im, $original_transparency);
			$original_transparency = ($rgb['red'] << 16) | ($rgb['green'] << 8) | $rgb['blue'];
			// Change the transparent color to black, since transparent goes to black anyways (no way to remove transparency in GIF)
			$col_alloced = imagecolorallocate($im, 0, 0, 0);
			if ($col_alloced !== false) {
				imagecolortransparent($im, $col_alloced);
			}
		}
		// Create truecolor image and transfer
		$truecolor = imagecreatetruecolor($imgw, $imgh);
		if ($truecolor !== false) {
			imagealphablending($im, false);
			imagesavealpha($im, true);
			if (imagecopy($truecolor, $im, 0, 0, 0, 0, $imgw, $imgh)) {
				imagedestroy($im);
				$im = $truecolor;
				// Remake transparency (if there was transparency)
				if ($original_transparency >= 0 && $original_transparency < imagecolorstotal($truecolor)) {
					imagealphablending($im, false);
					imagesavealpha($im, true);
					for ($x = 0; $x < $imgw; $x++) {
						for ($y = 0; $y < $imgh; $y++) {
							$colorat = imagecolorat($im, $x, $y);
							if ($colorat !== false && $colorat == $original_transparency) {
								imagesetpixel($im, $x, $y, 127 << 24);
							}
						}
					}
				}
			}
		}
	}

	$success = imagecopyresampled($im, $stamp, $destx, $desty, 0, 0, $new_width, $new_height, $stampw, $stamph);
	if ($success) {
		imagealphablending($im, false);
		imagesavealpha($im, true);
		$image_func = "image{$org_type}";
		$success = $image_func($im, $save_filepath);
	}
	imagedestroy($im);
	imagedestroy($stamp);

	// Earlier return possible
	return $success;
}

// Handle the fact that older MyBB versions don't have the mk_path_abs() function.
function autorsz_mk_path_abs($path) {
	if (function_exists('mk_path_abs')) {
		return mk_path_abs($path);
	} else {
		$iswin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
		$char1 = my_substr($path, 0, 1);
		if ($char1 != '/' && !($iswin && ($char1 == '\\' || preg_match('(^[a-zA-Z]:\\\\)', $path)))) {
			$path = MYBB_ROOT.$path;
		}

		return $path;
	}
}
