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

include_once($prevFolder."classes/member.php");
include_once($prevFolder."classes/forumboard.php");

$consoleObj = new ConsoleOption($mysqli);
$boardObj = new ForumBoard($mysqli);
$member = new Member($mysqli);

$postMemberObj = new Member($mysqli);

$intPostTopicCID = $consoleObj->findConsoleIDByName("Post Topic");

$categoryObj = new BasicOrder($mysqli, "forum_category", "forumcategory_id");
$categoryObj->set_assocTableName("forum_board");
$categoryObj->set_assocTableKey("forumboard_id");


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


if(!$boardObj->select($_GET['bID'])) {
	echo "
		<script type='text/javascript'>window.location = 'index.php';</script>
	";
	exit();
}

$boardInfo = $boardObj->get_info_filtered();


// Start Page
$PAGE_NAME = $boardInfo['name']." - Forum - ";
$dispBreadCrumb = "";
include($prevFolder."themes/".$THEME."/_header.php");

// Check Private Forum

if($websiteInfo['privateforum'] == 1 && !constant("LOGGED_IN")) {
	die("<script type='text/javascript'>window.location = '".$MAIN_ROOT."login.php';</script>");
}

$memberInfo = array();


$LOGGED_IN = false;
$NUM_PER_PAGE = 25;
if($member->select($_SESSION['btUsername']) && $member->authorizeLogin($_SESSION['btPassword'])) {
	$memberInfo = $member->get_info_filtered();
	$LOGGED_IN = true;
	$NUM_PER_PAGE = $memberInfo['topicsperpage'];
}

if($NUM_PER_PAGE == 0) {
	$NUM_PER_PAGE = 25;
}

if(!$boardObj->memberHasAccess($memberInfo)) {
	echo "
	<script type='text/javascript'>window.location = 'index.php';</script>
	";
	exit();
}

$arrTopics = $boardObj->getForumTopics();

if(!isset($_GET['pID']) || !is_numeric($_GET['pID'])) {
	$intOffset = 0;
	$_GET['pID'] = 1;
}
else {
	$intOffset = $NUM_PER_PAGE*($_GET['pID']-1);
}

$blnPageSelect = false;

// Count Pages
$NUM_OF_PAGES = ceil(count($arrTopics)/$NUM_PER_PAGE);

if($NUM_OF_PAGES == 0) {
	$NUM_OF_PAGES = 1;	
}

if($_GET['pID'] > $NUM_OF_PAGES) {

	echo "
	<script type='text/javascript'>window.location = 'viewboard.php?bID=".$_GET['bID']."';</script>
	";
	exit();

}

// Check for Next button
$dispNextPage = "";
if($_GET['pID'] < $NUM_OF_PAGES) {
	$dispNextPage = "<span style='padding-left: 10px'><b><a href='viewboard.php?bID=".$_GET['bID']."&pID=".($_GET['pID']+1)."'>Next</a> &raquo;</b></span>";
	$blnPageSelect = true;
}

// Check for Previous button
$dispPreviousPage = "";
if(($_GET['pID']-1) > 0) {
	$dispPreviousPage = "<b>&laquo; <a href='viewboard.php?bID=".$_GET['bID']."&pID=".($_GET['pID']-1)."'>Previous</a></b>";
	$blnPageSelect = true;
}


for($i=1; $i<=$NUM_OF_PAGES; $i++) {
	$selectPage = "";
	if($i == $_GET['pID']) {
		$selectPage = " selected";	
	}
	$pageoptions .= "<option value='".$i."'".$selectPage.">".$i."</option>";
}

$dispPageSelectTop = "";
$dispPageSelectBottom = "";
if($blnPageSelect) {
	$dispPageSelectTop = "
	<p style='margin-top: 0px'><b>Page:</b> <select id='pageSelectTop' class='textBox'>".$pageoptions."</select> <input type='button' id='btnPageSelectTop' class='submitButton' value='GO' style='width: 40px'></p>
	<p style='margin-top: 0px'>".$dispPreviousPage.$dispNextPage."</p>
	";
	
	$dispPageSelectBottom = "
	<p style='margin-top: 0px'><b>Page:</b> <select id='pageSelectBottom' class='textBox'>".$pageoptions."</select> <input type='button' id='btnPageSelectBottom' class='submitButton' value='GO' style='width: 40px'></p>
	<p style='margin-top: 0px'>".$dispPreviousPage.$dispNextPage."</p>
	";
}

echo "
<div class='breadCrumbTitle'>".$boardInfo['name']."</div>
<div class='breadCrumb' style='padding-top: 0px; margin-top: 0px'>
<a href='".$MAIN_ROOT."'>Home</a> > <a href='index.php'>Forum</a> > ".$boardInfo['name']."
</div>


<table class='forumTable'>
	<tr>
		<td colspan='2' class='main' valign='bottom'>
			"; 
			if(LOGGED_IN) { 
				echo "<p style='margin-top: 0px'><b>&raquo; <a href='".$MAIN_ROOT."members/console.php?cID=".$intPostTopicCID."&bID=".$boardInfo['forumboard_id']."'>NEW TOPIC</a> &laquo;</b></p>"; 
			}
		echo "
		</td>
		<td colspan='2' align='right' class='main'>
			".$dispPageSelectTop."
		</td>
	</tr>
	<tr>
		<td class='boardTitles-Name'>Topic:</td>
		<td class='boardTitles-TopicCount' style='border-left: 0px'>Replies:</td>
		<td class='boardTitles-TopicCount' style='border-left: 0px'>Views:</td>
		<td class='boardTitles-LastPost' style='border-left: 0px'>Last Post:</td>
	</tr>
	<tr>
		<td class='dottedLine' style='padding-top: 5px' colspan='4'></td>
	</tr>
";

$arrPageTopics = $boardObj->getForumTopics(" ft.stickystatus DESC, fp.dateposted DESC", " LIMIT ".$intOffset.", ".$NUM_PER_PAGE);

foreach($arrPageTopics as $postID) {
	
	$boardObj->objPost->select($postID);
	$postInfo = $boardObj->objPost->get_info_filtered();

	$boardObj->objTopic->select($postInfo['forumtopic_id']);
	$topicInfo = $boardObj->objTopic->get_info();
	
	$postMemberObj->select($postInfo['member_id']);
	$dispTopicPoster = $postMemberObj->getMemberLink();
	
	$boardObj->objPost->select($topicInfo['lastpost_id']);
	$lastPostInfo = $boardObj->objPost->get_info_filtered();
	
	$postMemberObj->select($lastPostInfo['member_id']);
	$dispLastPoster = $postMemberObj->getMemberLink();
	
	$dispTopicIconsIMG = "";
	$newTopicBG = "";
	if($LOGGED_IN && !$member->hasSeenTopic($topicInfo['forumtopic_id']) && ($lastPostInfo['dateposted']+(60*60*24*7)) > time()) {
		$newTopicBG = " boardNewPostBG";
		$dispTopicIconsIMG = " <img style='margin-left: 5px' src='".$MAIN_ROOT."themes/".$THEME."/images/forum-new.png' title='New Posts!'>";
	}
	
	if($topicInfo['stickystatus'] == 1) {
		$newTopicBG = " boardNewPostBG";
		$dispTopicIconsIMG .= " <img src='".$MAIN_ROOT."themes/".$THEME."/images/forum-sticky.png' title='Sticky' style='margin-left: 5px'>";
	}
	
	if($topicInfo['lockstatus'] == 1) {
		$newTopicBG = " boardNewPostBG";
		$dispTopicIconsIMG .= " <img src='".$MAIN_ROOT."themes/".$THEME."/images/forum-locked.png' title='Locked' style='margin-left: 5px'>";
	}
	
	
	echo "
		<tr class='boardRows'>
			<td class='boardName dottedLine".$newTopicBG."'><a href='viewtopic.php?tID=".$postInfo['forumtopic_id']."'>".$postInfo['title']."</a>".$dispTopicIconsIMG."<br><span class='boardDescription'>by ".$dispTopicPoster." - ".getPreciseTime($postInfo['dateposted'])."</span></td>
			<td class='boardTopicCount dottedLine".$newTopicBG."' align='center'>".$topicInfo['replies']."</td>
			<td class='boardTopicCount dottedLine".$newTopicBG."' align='center'>".$topicInfo['views']."</td>
			<td class='boardLastPost dottedLine".$newTopicBG."'>by ".$dispLastPoster."<br>".getPreciseTime($lastPostInfo['dateposted'])."</td>
		</tr>
	";
	
}

echo "
	<tr>
		<td colspan='2' style='padding-top: 15px' class='main' valign='top'>
		";

		if(LOGGED_IN) {
			echo "
				<p style='margin-top: 0px'><b>&raquo; <a href='".$MAIN_ROOT."members/console.php?cID=".$intPostTopicCID."&bID=".$boardInfo['forumboard_id']."'>NEW TOPIC</a> &laquo;</b></p>
			";
		}
	echo "
		
		</td>
		<td colspan='2' style='padding-top: 15px' align='right' class='main'>
			".$dispPageSelectBottom."
		</td>
	</tr>
</table>
";

if(count($arrTopics) == 0) {
	
	echo "
		<div class='shadedBox' style='width: 40%; margin: 20px auto'>
			<p class='main' align='center'>
				<i>No Posts Yet!</i><br>
				<a href='".$MAIN_ROOT."members/console.php?cID=".$intPostTopicCID."&bID=".$_GET['bID']."'>Be the first!</a>
			</p>
		</div>
	";	
	
}

if($blnPageSelect) {
	echo "
		<script type='text/javascript'>
			$(document).ready(function() {
				$('#btnPageSelectTop, #btnPageSelectBottom').click(function() {
					
					var jqPageSelect = \"#pageSelectBottom\";
					var intNewPage = 0;
					
					if($(this).attr('id') == \"btnPageSelectTop\") {
						jqPageSelect = \"#pageSelectTop\";
					}
					
					intNewPage = $(jqPageSelect).val();
					
					window.location = 'viewboard.php?bID=".$_GET['bID']."&pID='+intNewPage;
					
				});
			});
		</script>
	";
}

include($prevFolder."themes/".$THEME."/_footer.php");


?>