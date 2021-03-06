<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('recording_add') || permission_exists('recording_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get recording id
	if (is_uuid($_REQUEST["id"])) {
		$recording_uuid = $_REQUEST["id"];
	}

//get the form value and set to php variables
	if (count($_POST) > 0) {
		$recording_filename = $_POST["recording_filename"];
		$recording_filename_original = $_POST["recording_filename_original"];
		$recording_name = $_POST["recording_name"];
		$recording_description = $_POST["recording_description"];

		//clean the recording filename and name
		$recording_filename = str_replace(" ", "_", $recording_filename);
		$recording_filename = str_replace("'", "", $recording_filename);
		$recording_name = str_replace("'", "", $recording_name);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
	//get recording uuid to edit
		$recording_uuid = $_POST["recording_uuid"];

	//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: recordings.php');
			exit;
		}

	//check for all required data
		$msg = '';
		if (strlen($recording_filename) == 0) { $msg .= $text['label-edit-file']."<br>\n"; }
		if (strlen($recording_name) == 0) { $msg .= $text['label-edit-recording']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
			return;
		}

	//update the database
	if ($_POST["persistformvar"] != "true") {
		if (permission_exists('recording_edit')) {
			//if file name is not the same then rename the file
				if ($recording_filename != $recording_filename_original) {
					rename($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$recording_filename_original, $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$recording_filename);
				}

			//build array
				$array['recordings'][0]['domain_uuid'] = $domain_uuid;
				$array['recordings'][0]['recording_filename'] = $recording_filename;
				$array['recordings'][0]['recording_name'] = $recording_name;
				$array['recordings'][0]['recording_description'] = $recording_description;
				$array['recordings'][0]['domain_uuid'] = $domain_uuid;
				$array['recordings'][0]['recording_uuid'] = $recording_uuid;

			//execute update
				$database = new database;
				$database->app_name = 'recordings';
				$database->app_uuid = '83913217-c7a2-9e90-925d-a866eb40b60e';
				$database->save($array);
				unset($array);
			//set message
				message::add($text['message-update']);

			//redirect
				header("Location: recordings.php");
				exit;
		}
	}
}

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$recording_uuid = $_GET["id"];
		$sql = "select * from v_recordings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and recording_uuid = :recording_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['recording_uuid'] = $recording_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$recording_filename = $row["recording_filename"];
			$recording_name = $row["recording_name"];
			$recording_description = $row["recording_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-edit'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";

	echo "<table border='0' cellpadding='0' cellspacing='0' align='right'>\n";
	echo "<tr>\n";
	echo "<td nowrap='nowrap'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='recordings.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<b>".$text['title-edit']."</b>\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-recording_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='recording_name' maxlength='255' value=\"".escape($recording_name)."\">\n";
	echo "<br />\n";
	echo $text['description-recording']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-file_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='recording_filename' maxlength='255' value=\"".escape($recording_filename)."\">\n";
	echo "    <input type='hidden' name='recording_filename_original' value=\"".escape($recording_filename)."\">\n";
	echo "<br />\n";
	echo $text['message-file']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Description\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='recording_description' maxlength='255' value=\"".escape($recording_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='recording_uuid' value='".escape($recording_uuid)."'>\n";
	echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>
