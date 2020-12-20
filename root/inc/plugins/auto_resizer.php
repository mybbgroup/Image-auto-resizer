<?php

// Disallow direct access to this file for security reasons.
if (!defined('IN_MYBB')) {
	die('Direct access to this file is not allowed.');
}

if (defined('IN_ADMINCP')) {
	$plugins->add_hook('admin_tools_recount_rebuild'            , 'autorsz_hookin__admin_tools_recount_rebuild'            );
	$plugins->add_hook('admin_tools_recount_rebuild_output_list', 'autorsz_hookin__admin_tools_recount_rebuild_output_list');
} else	$plugins->add_hook('upload_attachment_thumb_start'          , 'autorsz_hookin__upload_attachment_thumb_start'          );

function auto_resizer_info() {
	global $lang, $db;
	$prefix = 'autorsz_';

	$lang->load('auto_resizer');

	$query = $db->simple_select('settinggroups', 'gid', "name = '{$prefix}settings'", array('limit' => 1));
	$gid   = $db->fetch_field($query, 'gid');
	$ret = array(
		'name'          => $lang->autorsz_name,
		'description'   => $lang->sprintf($lang->autorsz_desc, $gid),
		'website'       => '',
		'author'        => 'MyBB Group',
		'authorsite'    => 'https://mybb.group/',
		'version'       => '1.0.0',
		// Constructed by converting each digit of 'version' above into two digits (zero-padded if necessary),
		// then concatenating them, then removing any leading zero(es) to avoid the value being interpreted as octal.
		'version_code'  => '10000',
		'guid'          => '',
		'codename'      => 'auto_resizer',
		'compatibility' => '18*'
	);

	return $ret;
}

function auto_resizer_install() {
	autorsz_create_settings();
}

function auto_resizer_uninstall() {
	autorsz_remove_settings();
}

function auto_resizer_is_installed() {
	global $db;
	$prefix = 'autorsz_';

	$query = $db->simple_select('settinggroups', 'gid', "name = '{$prefix}settings'", array('limit' => 1));

	return $db->fetch_field($query, 'gid') ? true : false;
}

/**
 * Creates the plugin's settings. Assumes the settings do not already exist,
 * i.e., that they have already been deleted if they were preexisting.
 */
function autorsz_create_settings() {
	global $db, $lang;
	$prefix = 'autorsz_';

	$lang->load('auto_resizer');

	$res = $db->query('SELECT MAX(disporder) as max_disporder FROM '.TABLE_PREFIX.'settinggroups');
	$disporder = intval($db->fetch_field($res, 'max_disporder')) + 1;

	// Insert the plugin's settings group into the database.
	$setting_group = array(
		'name'         => $prefix.'settings',
		'title'        => $db->escape_string($lang->autorsz_settings_title),
		'description'  => $db->escape_string($lang->autorsz_settings_desc),
		'disporder'    => $disporder,
		'isdefault'    => 0
	);
	$db->insert_query('settinggroups', $setting_group);
	$gid = $db->insert_id();

	// Define the plugin's settings.
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
	);

	// Insert each of the plugin's settings into the database.
	$disporder = 1;
	foreach ($settings as $name => $setting) {
		$insert_settings = array(
			'name'        => $db->escape_string($prefix.$name),
			'title'       => $db->escape_string($setting['title']),
			'description' => $db->escape_string($setting['description']),
			'optionscode' => $db->escape_string($setting['optionscode']),
			'value'       => $db->escape_string($setting['value']),
			'disporder'   => $disporder,
			'gid'         => $gid,
			'isdefault'   => 0
		);
		$db->insert_query('settings', $insert_settings);
		$disporder++;
	}

	rebuild_settings();
}

function autorsz_remove_settings() {
	global $db;
	$prefix = 'autorsz_';

	$result = $db->simple_select('settinggroups', 'gid', "name = '{$prefix}settings'", array('limit' => 1));
	$group = $db->fetch_array($result);
	if (!empty($group['gid'])) {
		$db->delete_query('settinggroups', "gid='{$group['gid']}'");
		$db->delete_query('settings', "gid='{$group['gid']}'");
		rebuild_settings();
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
				$mybb->input['num_autorsz_imgs'] = 200;
			}

			$page = $mybb->get_input('page', MyBB::INPUT_INT);
			$per_page = $mybb->get_input('num_autorsz_imgs', MyBB::INPUT_INT);
			if ($per_page <= 0) {
				$per_page = 200;
			}
			$start = ($page-1) * $per_page;
			$end = $start + $per_page;

			$query = $db->simple_select('attachments', 'COUNT(*) AS num_imgs');
			$num_imgs = $db->fetch_field($query, 'num_imgs');

			$query2 = $db->simple_select('attachments', 'aid, attachname, filesize', '', array('order_by' => 'aid', 'order_dir' => 'ASC', 'limit_start' => $start, 'limit' => $per_page));
			while ($row = $db->fetch_array($query2)) {
				$ret = autorsz_resize_file($row['attachname']);
				if ($ret !== false && $ret != $row['filesize']) {
					$db->update_query('attachments', array('filesize' => $ret), "aid='{$row['aid']}'");
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
	$form_container->output_cell($form->generate_numeric_field('num_autorsz_imgs', 200, array('style' => 'width: 150px;', 'min' => 0)));
	$form_container->output_cell($form->generate_submit_button($lang->go, array('name' => 'do_autorsz_imgs')));
	$form_container->construct_row();
}

function autorsz_hookin__upload_attachment_thumb_start($attacharray) {
	global $plugins_cache, $cache;

	if (!is_array($plugins_cache)) {
		$plugins_cache = $cache->read('plugins');
	}
	$active_plugins = $plugins_cache['active'];
	if ($active_plugins && $active_plugins['auto_resizer']) {
		$res = autorsz_resize_file($attacharray['attachname']);
		if ($res !== false) {
			$attacharray['filesize'] = $res;
		}
	}

	return $attacharray;
}

function autorsz_resize_file($attachname) {
	global $mybb;
	$prefix = 'autorsz_';

	$uploadspath = (defined('IN_ADMINCP') ? '../' : '').$mybb->settings['uploadspath'];
	$ret = false;
	$filename_rsz = str_replace('.attach', '.resized', $attachname);
	$filepath_org = $uploadspath.'/'.$attachname;
	if (file_exists($filepath_org)) {
		require_once MYBB_ROOT.'inc/functions_image.php';
		$resized = generate_thumbnail($filepath_org, $uploadspath, $filename_rsz, $mybb->settings[$prefix.'max_height'], $mybb->settings[$prefix.'max_width']);
		if ($resized['filename']) {
			$filepath_rsz = $uploadspath.'/'.$resized['filename'];
			if (!@rename($filepath_rsz, $filepath_org)) {
				@unlink($filepath_rsz);
			} else	$ret = filesize($filepath_org);
		}
	}

	return $ret;
}