#!/usr/bin/env php
<?php

error_reporting(E_ALL);

$home_dir = getenv('HOME_DIR');
$arma3_dir = getenv('ARMA3_DIR');
$steamcmd_dir = getenv('STEAMCMD_DIR');
$mod_names_arr = [];

// Load server config.
if (file_exists($home_dir.'/server-config.json')) {
	$config = json_decode(file_get_contents($home_dir.'/server-config.json'), false);
} else {
	echo "server-config.json file not found \n";
	exit;
}

// Check server config exists.
if (!file_exists($arma3_dir.'/configs/'.$config->cfg)) {
	echo "Arma config file not found \n";
	exit;
}

// Download Arma3 Server.
if ($config->steam_user && $config->steam_pass) {
	$steamcmd = "$steamcmd_dir/steamcmd.sh"
		." +login $config->steam_user $config->steam_pass"
		." +force_install_dir $arma3_dir"
		." +app_update 233780"
		." validate"
		." +quit";

	echo "Installing Arma3 Server \n";
	passthru($steamcmd);
	// TODO: Handle failed connections.
} else {
	echo "Steam username or password not set! \n";
	exit;
}

// Download Workshop items.
if (!empty($config->workshop->mods)) {
	if (!is_dir($arma3_dir.'/mods')) {
		mkdir($arma3_dir.'/mods');
	}

	foreach ($config->workshop->mods as $mod_name => $mod_id) {
		echo "\nDownloading workshop item: $mod_name \n";

		$steamcmd = "$steamcmd_dir/steamcmd.sh"
			." +login $config->steam_user $config->steam_pass"
			." +workshop_download_item 107410 $mod_id"
			." validate"
			." +quit";

		passthru($steamcmd, $return_code);
		// TODO: Somehow check the download failed.

		$mod_dir = "$home_dir/Steam/steamapps/workshop/content/107410/$mod_id";

		// Symlink the workshop items to the mods folder.
		if (!is_dir($arma3_dir.'/mods/@'.$mod_name)) {
			symlink($mod_dir, $arma3_dir.'/mods/@'.$mod_name);
		}

		// Find all keys in mod and copy to keys folder.
		if ($h = opendir($mod_dir)) {
			while (($folder_name = readdir($h)) !== false) {
				if (preg_match('/key/i', $folder_name)) {
					shell_exec("cp $mod_dir/$folder_name/* $arma3_dir/keys");
					break;
				}
			}

			closedir($h);
		}
		
		// Add mod name to list for server command params.
		$mod_names_arr[] = 'mods/@'.$mod_name;
	}
}

// Download MP missions.
if (!empty($config->workshop->mp_missions)) {
	if (!is_dir($arma3_dir.'/mpmissions')) {
		mkdir($arma3_dir.'/mpmissions');
	}

	foreach ($config->workshop->mp_missions as $mission_name => $mission_id) {
		echo "\nDownloading workshop item: $mission_name \n";

		$steamcmd = "$steamcmd_dir/steamcmd.sh"
			." +login $config->steam_user $config->steam_pass"
			." +workshop_download_item 107410 $mission_id"
			." validate"
			." +quit";

		passthru($steamcmd, $return_code);
		// TODO: Somehow check the download failed.
		$mod_dir = "$home_dir/Steam/steamapps/workshop/content/107410/$mission_id";

		// Copy and rename mission to missions folder.
		$files = scandir($mod_dir);
		if (isset($files[2])) {
			copy("$mod_dir/$files[2]", "$arma3_dir/mpmissions/$mission_name.pbo");
		}
	}
}

// Run headless clients.
if ($config->headless_clients != 0) {
	// Find password in arma config.
	preg_match_all(
		"/(.+?)(?:\s+)?=(?:\s+)?(.+?)(?:$|\/|;)/m"
		, file_get_contents($arma3_dir.'/configs/'.$config->cfg)
		, $matches
	);

	$password = '';
	foreach ($matches[1] as $key => $match) {
		if ($match == 'password') {
			$password = trim($matches[2][$key], '"\'');
			break;
		}
	}

	$cmd = "$arma3_dir/arma3server_x64 -client -connect=127.0.0.1"
		.($password != ''? " -password=$password" : '')
		.(!empty($mod_names_arr)? " -mod=\"".implode(';', $mod_names_arr)."\"" : '');

	for ($i = 0; $i < $config->headless_clients; $i++) {
		echo "Launching headless client ".($i+1).": ".$cmd."\n";
		exec("cd $arma3_dir && $cmd > /dev/null &");
	}
}

// Run server.
$cmd = "$arma3_dir/arma3server_x64"
	." -config=\"$arma3_dir/configs/$config->cfg\""
	.(!empty($config->world)? " -world=$config->world" : '')
	.(!empty($config->profile)? " -name=$config->profile" : '')
	." -profiles=\"$arma3_dir/configs/profiles\""
	.(!empty($mod_names_arr)? " -mod=\"".implode(';', $mod_names_arr)."\"" : '')
	.(!empty($config->additional_params)? ' '.$config->additional_params : '');

echo "Launching server: ".$cmd."\n";
exec("cd $arma3_dir && $cmd");
