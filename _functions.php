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


// General functions to filter out all <, >, ", and ' symbols
function filterArray($arrValues) {
	$newArray = array();
	foreach($arrValues as $key => $value) {
		$temp = str_replace("<", "&lt;", $value);
		$value = str_replace(">", "&gt;", $temp);
		$temp = str_replace("'", "&#39;", $value);
		$value = str_replace('"', '&quot;', $temp);
		$temp = str_replace("&middot;", "&#38;middot;", $value);
		$temp = str_replace("&raquo;", "&#38;raquo;", $temp);
		$temp = str_replace("&laquo;", "&#38;laquo;", $temp);
		
		$newArray[$key] = $temp;
	}
	return $newArray;
}

function filterText($strText) {
	$temp = str_replace("<", "&lt;", $strText);
	$value = str_replace(">", "&gt;", $temp);
	$temp = str_replace("'", "&#39;", $value);
	$value = str_replace('"', '&quot;', $temp);
	$temp = str_replace("&middot;", "&#38;middot;", $value);
	$temp = str_replace("&raquo;", "&#38;raquo;", $temp);
	$temp = str_replace("&laquo;", "&#38;laquo;", $temp);
	
	

	return $temp;
}

function getPreciseTime($intTime, $timeFormat="") {

	$timeDiff = time() - $intTime;

	if($timeDiff < 3) {
		$dispLastDate = "just now";
	}
	elseif($timeDiff < 60) {
		$dispLastDate = "$timeDiff seconds ago";
	}
	elseif($timeDiff < 3600) {
		$minDiff = round($timeDiff/60);
		$dispMinute = "minutes";
		if($minDiff == 1) {
			$dispMinute = "minute";
		}

		$dispLastDate = "$minDiff $dispMinute ago";
	}
	elseif($timeDiff < 86400) {
		$hourDiff = round($timeDiff/3600);
		$dispHour = "hours";
		if($hourDiff == 1) {
			$dispHour = "hour";
		}

		$dispLastDate = "$hourDiff $dispHour ago";
	}
	else {

		if($timeFormat == "") {
			$timeFormat = "D M j, Y g:i a";
		}


		$dispLastDate = date($timeFormat, $intTime);
	}

	return $dispLastDate;

}

function parseBBCode($strText) {
global $MAIN_ROOT;

	// Basic Codes

	$arrBBCodes['Bold'] = array("bbOpenTag" => "[b]", "bbCloseTag" => "[/b]", "htmlOpenTag" => "<span style='font-weight: bold'>", "htmlCloseTag" => "</span>");
	$arrBBCodes['Italic'] = array("bbOpenTag" => "[i]", "bbCloseTag" => "[/i]", "htmlOpenTag" => "<span style='font-style: italic'>", "htmlCloseTag" => "</span>");
	$arrBBCodes['Underline'] = array("bbOpenTag" => "[u]", "bbCloseTag" => "[/u]", "htmlOpenTag" => "<span style='text-decoration: underline'>", "htmlCloseTag" => "</span>");
	$arrBBCodes['Image'] = array("bbOpenTag" => "[img]", "bbCloseTag" => "[/img]", "htmlOpenTag" => "<img src='", "htmlCloseTag" => "'>");
	$arrBBCodes['CenterAlign'] = array("bbOpenTag" => "[center]", "bbCloseTag" => "[/center]", "htmlOpenTag" => "<p align='center'>", "htmlCloseTag" => "</p>");
	$arrBBCodes['LeftAlign'] = array("bbOpenTag" => "[left]", "bbCloseTag" => "[/left]", "htmlOpenTag" => "<p align='left'>", "htmlCloseTag" => "</p>");
	$arrBBCodes['RightAlign'] = array("bbOpenTag" => "[right]", "bbCloseTag" => "[/right]", "htmlOpenTag" => "<p align='right'>", "htmlCloseTag" => "</p>");
	$arrBBCodes['Quote'] = array("bbOpenTag" => "[quote]", "bbCloseTag" => "[/quote]", "htmlOpenTag" => "<div class='forumQuote'>", "htmlCloseTag" => "</div>");
	$arrBBCodes['Code'] = array("bbOpenTag" => "[code]", "bbCloseTag" => "[/code]", "htmlOpenTag" => "<div class='forumCode'>", "htmlCloseTag" => "</div>");
	
	
	


	foreach($arrBBCodes as $bbCode) {

		$strText = str_replace($bbCode['bbOpenTag'],$bbCode['htmlOpenTag'],$strText);
		$strText = str_replace($bbCode['bbCloseTag'],$bbCode['htmlCloseTag'],$strText);

	}
	
	// Emoticons
	
	$arrEmoticonCodes = array(":)", ":(", ":D", ";)", ":p");
	$arrEmoticonImg = array("smile.png", "sad.png", "grin.png", "wink.png", "cheeky.png");
	
	foreach($arrEmoticonCodes as $key => $value) {
		
		$imgURL = "<img src='".$MAIN_ROOT."images/emoticons/".$arrEmoticonImg[$key]."' width='15' height='15'>";
		$strText = str_replace($value, $imgURL, $strText);
		
	}
	

	// Complex Codes, ex. Links, colors...

	$strText = preg_replace("/\[url\](.*)\[\/url\]/", "<a href='$1' target='_blank'>$1</a>", $strText); // Links no Titles
	$strText = preg_replace("/\[url=(.*)\](.*)\[\/url\]/", "<a href='$1' target='_blank'>$2</a>", $strText); // Links with Titles
	$strText = preg_replace("/\[color=(.*)\](.*)\[\/color\]/", "<span style='color: $1'>$2</span>", $strText); // Text Color
	
	$strText = str_replace("[/youtube]", "[/youtube]\n", $strText);
	$strText = preg_replace("/\[youtube\](http|https)(\:\/\/www\.youtube\.com\/watch\?v\=)(.*)\[\/youtube\]/", "<iframe class='youtubeEmbed' src='http://www.youtube.com/embed/$3?wmode=opaque' frameborder='0' allowfullscreen></iframe>", $strText);
	$strText = preg_replace("/\[\youtube\](http|https)(\:\/\/youtu\.be\/)(.*)\[\/youtube\]/", "<iframe class='youtubeEmbed' src='http://www.youtube.com/embed/$3?wmode=opaque' frameborder='0' allowfullscreen></iframe>", $strText);
	
	$strText = str_replace("[/twitch]", "[/twitch]\n", $strText);
	$strText = preg_replace("/\[twitch\](http|https)(\:\/\/www\.twitch\.tv\/)(.*)\[\/twitch\]/", "<object class='youtubeEmbed' type='application/x-shockwave-flash' id='live_embed_player_flash' data='http://www.twitch.tv/widgets/live_embed_player.swf?channel=$3' bgcolor='#000000'><param name='allowFullScreen' value='true' /><param name='wmode' value='opaque' /><param name='allowScriptAccess' value='always' /><param name='allowNetworking' value='all' /><param name='movie' value='http://www.twitch.tv/widgets/live_embed_player.swf' /><param name='flashvars' value='hostname=www.twitch.tv&channel=$3&auto_play=false&start_volume=25' /></object>", $strText);
	

	return $strText;


}




?>