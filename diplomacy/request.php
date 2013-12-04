<?php

/*
 * Bluethrust Clan Scripts v4
 * Copyright 2012
 *
 * Author: Bluethrust Web Development
 * E-mail: support@bluethrust.com
 * Website: http://www.bluethrust.com
 *
 * License: http://www.bluethrust.com/license.php
 *
 */


// Config File
$prevFolder = "../";

include($prevFolder."_setup.php");
include($prevFolder."classes/member.php");


$ipbanObj = new Basic($mysqli, "ipban", "ipaddress");

if($ipbanObj->select($IP_ADDRESS, false)) {
	$ipbanInfo = $ipbanObj->get_info();

	if(time() < $ipbanInfo['exptime'] OR $ipbanInfo['exptime'] == 0) {
		die("<script type='text/javascript'>window.location = '".$MAIN_ROOT."banned.php';</script>");
	}
	else {
		$ipbanObj->delete();
	}

}

// Start Page
$PAGE_NAME = "Diplomacy Request - ";
$dispBreadCrumb = "";
include($prevFolder."themes/".$THEME."/_header.php");

$result = $mysqli->query("SELECT ipaddress FROM ".$dbprefix."diplomacy_request WHERE ipaddress = '".$IP_ADDRESS."'");
if($result->num_rows >= $websiteInfo['maxdiplomacy']) {

	
	echo "
		<div id='maxRequestDialog' style='display: none'>
			<p class='main' align='center'>
				You have already sent the maximum diplomacy requests!
			</p>
		</div>
	
		<script type='text/javascript'>
			popupDialog('Diplomacy Request', '".$MAIN_ROOT."', 'maxRequestDialog');
		</script>
	";
	
	$_POST['submit'] = "block";
}


$result = $mysqli->query("SELECT * FROM ".$dbprefix."diplomacy_status ORDER BY ordernum DESC");
while($row = $result->fetch_assoc()) {

	$statusoptions .= "<option value='".$row['diplomacystatus_id']."'>".filterText($row['name'])."</option>";	
	$arrStatuses[] = $row['diplomacystatus_id'];
}
?>

<div class='breadCrumbTitle'>Diplomacy Request</div>
<div class='breadCrumb' style='padding-top: 0px; margin-top: 0px'>
	<a href='<?php echo $MAIN_ROOT; ?>'>Home</a> > Diplomacy Request
</div>


<div style='margin: 25px auto; '>


	<?php
	
	$countErrors = 0;
	$dispError = "";
	if($_POST['submit'] && $_POST['submit'] != "block") {
	
		// Check Required Fields not Blank
		
		$arrRequiredFields = array("Your Name"=>"requestername", "Your E-mail"=>"requesteremail", "Clan Name"=>"clanname", "Diplomacy Status"=>"diplomacystatus", "Games Played"=>"gamesplayed", "Clan Leaders"=>"clanleaders");
		
		foreach($_POST as $key => $value) {
			if(in_array($key, $arrRequiredFields) && trim($value) == "") {
				$fieldTitle = array_search($key, $arrRequiredFields);
				
				$dispError .= "&nbsp;&nbsp;&nbsp;<b>&middot;</b> ".$fieldTitle." may not be blank!<br>";
				$countErrors++;
				
			}
		}
		
		
		// Check valid e-mail
		
		if(strpos($_POST['requesteremail'], "@") === false || strpos($_POST['requesteremail'], ".") === false) {
			$dispError .= "&nbsp;&nbsp;&nbsp;<b>&middot;</b> You entered an invalid e-mail address.<br>";
			$countErrors++;			
		}
		
		if($countErrors == 0) {
			$result = $mysqli->query("SELECT email FROM ".$dbprefix."diplomacy_request WHERE email = '".$mysqli->real_escape_string($_POST['requesteremail'])."'");
			if($result->num_rows > 0) {
				$dispError .= "&nbsp;&nbsp;&nbsp;<b>&middot;</b> A diplomacy request has already sent with this e-mail address.<br>";
				$countErrors++;
			}
		}
		
		
		// Check Diplomacy Status
		
		if(!in_array($_POST['diplomacystatus'], $arrStatuses)) {
			$dispError .= "&nbsp;&nbsp;&nbsp;<b>&middot;</b> You selected an invalid diplomacy status.<br>";
			$countErrors++;
		}
		
		
		if($countErrors == 0) {
			
			$emailCode = md5(time().uniqid());
			
			
			
			
			
			
			// Send E-mail Confirmation
			$emailTo = $_POST['requesteremail'];
			$emailFrom = "confirmemail@bluethrust.com";
			$emailSubject = $websiteInfo['clanname']." - Diplomacy Request: Email Confirmation";
			$emailMessage = "
Hi ".$_POST['requestername'].",\n\n
			
You must confirm your e-mail address before the diplomacy request can go through.  Click the link below.\n\n
			
http://".$_SERVER['SERVER_NAME'].$MAIN_ROOT."diplomacy/confirm-email.php?code=".$emailCode."\n\n	
Thanks,\n
".$websiteInfo['clanname'];

			$sendMail = mail($emailTo, $emailSubject, $emailMessage, "From: ".$emailFrom);

			if(!$sendMail) {
				$emailCode = 1;
				$sendMail = true;
			}
			
			if($sendMail) {
				
				$diplomacyRequestObj = new Basic($mysqli, "diplomacy_request", "diplomacyrequest_id");
				
				$arrColumns = array("ipaddress", "dateadded", "diplomacystatus_id", "email", "name", "clanname", "clantag", "clansize", "gamesplayed", "website", "leaders", "message", "confirmemail");
				$arrValues = array($IP_ADDRESS, time(), $_POST['diplomacystatus'], $_POST['requesteremail'], $_POST['requestername'], $_POST['clanname'], $_POST['clantag'], $_POST['clansize'], $_POST['gamesplayed'], $_POST['website'], $_POST['clanleaders'], $_POST['message'], $emailCode);
				
				if($emailCode == 1) {
					$dispConfirmMessage = "A request has been sent to the diplomacy managers.  Please wait while a decision is made.";
				}
				else {
					$dispConfirmMessage = "Almost Done!  You need to first confirm your e-mail address before the diplomacy request can go through.  Check your spam!";
				}
				
				if($diplomacyRequestObj->addNew($arrColumns, $arrValues)) {
					echo "
					
						<div style='display: none' id='successBox'>
							<p align='center'>
								".$dispConfirmMessage."
							</p>
						</div>
						
						<script type='text/javascript'>
							popupDialog('Diplomacy Request', '".$MAIN_ROOT."', 'successBox');
						</script>
					
					";
				}
				else {
					$countErrors++;
					$dispError .= "&nbsp;&nbsp;&nbsp;<b>&middot;</b> Unable to save information to the database.  Please contact the website administrator.<br>";
				}
				
				
			}
			else {
				$dispError .= "&nbsp;&nbsp;&nbsp;<b>&middot;</b> Unable to send confirmation e-mail.<br>";
				$countErrors++;
			}
			
			
		}
		
		
		
		if($countErrors > 0) {
			$_POST = filterArray($_POST);
			$_POST['submit'] = false;	
		}
		
		
	}
	
	
	if(!$_POST['submit']) {
		echo "
	
		<div class='formDiv'>
			<form action='request.php' method='post'>
		";
		
		if($dispError != "") {
			echo "
			<div class='errorDiv'>
			<strong>Unable to send diplomacy request because the following errors occurred:</strong><br><br>
			$dispError
			</div>
			";
		}

		
		echo "
				Use the form below to send a diplomacy request.  A notification will be sent to the clan diplomacy managers and they will review your request.  Your e-mail address must be confirmed before the request goes through.  An e-mail will be sent to you when a decision is made regarding your request.
				<br><br>
				Fields with marked with a (<span class='failedFont'>*</span>) are required
				<table class='formTable'>
					<tr>
						<td class='formLabel'>Your Name: <span class='failedFont'>*</span></td>
						<td class='main'><input type='text' name='requestername' value='".$_POST['requestername']."' class='textBox' style='width: 200px'></td>
					</tr>
					<tr>
						<td class='formLabel'>E-mail: <span class='failedFont'>*</span></td>
						<td class='main'><input type='text' name='requesteremail' value='".$_POST['requesteremail']."' class='textBox' style='width: 200px'></td>
					</tr>
					<tr>
						<td class='formLabel'>Clan Name: <span class='failedFont'>*</span></td>
						<td class='main'><input type='text' name='clanname' value='".$_POST['clanname']."' class='textBox' style='width: 200px'></td>
					</tr>
					<tr>
						<td class='formLabel'>Clan Leader(s): <span class='failedFont'>*</span></td>
						<td class='main'><input type='text' name='clanleaders' value='".$_POST['clanleaders']."' class='textBox' style='width: 200px'></td>
					</tr>
					<tr>
						<td class='formLabel'>Diplomacy Status: <span class='failedFont'>*</span></td>
						<td class='main'>
							<select name='diplomacystatus' class='textBox'>
								<option value=''>[Select]</option>".$statusoptions."
							</select>
						</td>
					</tr>	
					<tr>
						<td class='formLabel'>Clan Tag:</td>
						<td class='main'><input type='text' name='clantag' value='".$_POST['clantag']."' class='textBox' style='width: 50px'></td>
					</tr>
					<tr>
						<td class='formLabel'>Games Played: <span class='failedFont'>*</span></td>
						<td class='main'><input type='text' name='gamesplayed' value='".$_POST['gamesplayed']."' class='textBox' style='width: 200px'></td>
					</tr>
					<tr>
						<td class='formLabel'>Website:</td>
						<td class='main'><input type='text' name='website' value='".$_POST['website']."' class='textBox' style='width: 200px'></td>
					</tr>
					<tr>
						<td class='formLabel'>Clan Size:</td>
						<td class='main'>
							<select name='clansize' class='textBox'>
								<option value=''>[Select]</option><option value='small'>Small</option><option value='medium'>Medium</option><option value='large'>Large</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class='formLabel' valign='top'>Message:</td>
						<td class='main' valign='top'>
							<textarea rows='5' cols='50' class='textBox' name='message' style='width: 300px; height: 100px'>".$_POST['message']."</textarea>
						</td>
					</tr>
					<tr>
						<td class='main' colspan='2' align='center'><br>
							<input type='submit' name='submit' value='Send Request' class='submitButton' style='width: 125px'>
						</td>
					</tr>
				</table>
			</form>
		</div>
		";
	}
?>
</div>

<?php
	include($prevFolder."themes/".$THEME."/_footer.php");
?>