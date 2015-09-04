<?php
/*
	LICHEN2 Handler for saving transcripts from Transcription tool for LAP
	Author: Ilkka Juuso / University of Oulu
	Status: Incomplete demo
	Version history:
		2012-10-08	Some fixes
					- Utterance who attribute now cleaned. Uses the last used speaker id if none specified at the start of a new segment.
					- preview of previous/next segment added
					- some buttons added (bleep, comment, save to cache)
		2012-02-20	First feature complete version ready for testing
		2012-02-17	First line of code written
*/
//SETTINGS
	$DEBUG = false;
	$savingEnabled = true;
	$writeToFolder = '../../../Commons/Transcripts/';//'../../TRANSCRIPTIONS/';
	$transcriptionTemplate = 'components/transcriptionTemplate.xml';
	//transcription path: ../TRANSCRIPTIONS/<project>/<informant>/<reel>/<region>/<userhash>.xml
	
	$utteranceStartMarkers = array('R: ','A1: ','A2: ','I1: ','I2: ',);
	//Note: point markers only, so this may also contain attributes in addition to the tag name
	$markerToTagCorrespondence = array(
		'D: ' => 'doubt', 
		'X: ' => 'unintelligible', 
		'U: ' => 'nonword',
		'{BEEP}' => 'deletion type="pi"');//overlap? nonspeech?
	
if(!$savingEnabled)
{
	echo 'Error: Saving transcripts has been disabled';
}
else if($writeToFolder != "")
{
	if($DEBUG)
	{
		echo '<pre>';
		print_r($_POST);
		echo '</pre>';
	}
	global $errorEncountered;
	$errorEncountered = 'no';
	//Step 1: Check transcript metadata
	/*  [segmentBox27] => 27
		[scribeName] => Ilkka Juuso
		[scribeEmail] => ilkka.juuso@ee.oulu.fi
		[scribeInstitution] => UniOulu
		[mediaPath] => http://www.lap.uga.edu/Projects/DASS/Speakers/LAGS(INF040)/Audio/LAGS(INF040)1/LAGS(INF040)1 02 Family.mp3
		[submitForm] => Submit */
	//...Get meta needed for the save to location
	//... ... Recording
	$projectID = '';
	$informantID = '';
	$reelID = '';
	$regionID = '';
	//
	$topic = '';
	$format = '';
	//Parse the media path
	if(array_key_exists('mediaPath', $_POST))
	{
		$lastPartOfTheMediaPath = urldecode(substr($_POST['mediaPath'],strrpos($_POST['mediaPath'],'/')+1));
		//echo '<br/>S: '.$lastPartOfTheMediaPath;//LAGS%28INF040%291%2002%20Family.mp3 (LAGS(INF040)1 02 Family.mp3)
		$a = strpos($lastPartOfTheMediaPath,'(');
		$projectID = substr($lastPartOfTheMediaPath,0,$a);
		$b = strpos($lastPartOfTheMediaPath,'(');
		$c = strpos($lastPartOfTheMediaPath,')');
		$d = $c-$b;
		$informantID = substr($lastPartOfTheMediaPath,$b+1,$d-1);
		//$lastParameters
		$lastParameters = split(' ',substr($lastPartOfTheMediaPath,$b+1+$d)); 
		//echo 'COUNT: '.count($lastParameters);
		if(count($lastParameters) >= 3)//NOTE: The topic label may also contain spaces
		{
			$reelID = $lastParameters[0];
			$regionID = ltrim($lastParameters[1],'0');
			//echo '<br/>Topic+mp3: '.$lastParameters[2];
			$topic = substr($lastParameters[2], 0, strrpos($lastParameters[2],'.'));
			$format = substr($lastParameters[2], strrpos($lastParameters[2],'.')+1);
		}
		if($DEBUG)
		{
			echo '<br/>Project: '.$projectID;
			echo '<br/>Informant: '.$informantID;
			echo '<br/>Reel: '.$reelID;
			echo '<br/>Clip: '.$regionID;
			echo '<br/>...topic: '.$topic;
			echo '<br/>...format: '.$format;
		}
	}
	//... ... Transcriber
	$scribeName = '';
	if(array_key_exists('scribeName', $_POST))
		$scribeName = $_POST['scribeName'];
	$scribeEmail = '';
	if(array_key_exists('scribeEmail', $_POST))
		$scribeEmail = $_POST['scribeEmail'];
	$scribeInstitution = '';
	if(array_key_exists('scribeInstitution', $_POST))
		$scribeInstitution = $_POST['scribeInstitution'];
	$scribeComments = '';
	if(array_key_exists('scribeComments', $_POST))
		$scribeComments = $_POST['scribeComments'];
	
	//Do we have anything for this recording?
	//transcription path: ../<writeToFolder>/<project>/<informant>/<reel>/<region>/<userhash>.xml
	//...master folder
	if(file_exists($writeToFolder))
	{
		if($DEBUG) echo '<br/>Master folder exists';
	}
	else
	{
		if($DEBUG) echo '<br/>Master folder does NOT exist';
	}
	//...project folder
	if(file_exists($writeToFolder.$projectID))
	{
		if($DEBUG) echo '<br/>Project folder exists';
	}
	else
	{
		if($DEBUG) echo '<br/>Creating Project folder';
		mkdir($writeToFolder.$projectID);
	}
	//...informant folder
	if(file_exists($writeToFolder.$projectID.'/'.$informantID))
	{
		if($DEBUG) echo '<br/>Informant folder exists';
	}
	else
	{
		if($DEBUG) echo '<br/>Creating Informant folder';
		mkdir($writeToFolder.$projectID.'/'.$informantID);
	}
	//...reel folder
	if(file_exists($writeToFolder.$projectID.'/'.$informantID.'/'.$reelID))
	{
		if($DEBUG) echo '<br/>Reel folder exists';
	}
	else
	{
		if($DEBUG) echo '<br/>Creating Reel folder';
		mkdir($writeToFolder.$projectID.'/'.$informantID.'/'.$reelID);
	}
	//...region folder
	if(file_exists($writeToFolder.$projectID.'/'.$informantID.'/'.$reelID.'/'.$regionID))
	{
		if($DEBUG) echo '<br/>Region folder exists';
	}
	else
	{
		if($DEBUG) echo '<br/>Creating Region folder';
		mkdir($writeToFolder.$projectID.'/'.$informantID.'/'.$reelID.'/'.$regionID);
	}
	//...user hash
	$userHash = md5('laplichen'.preg_replace('/[^\w]+/','',$scribeName.$scribeEmail));
	if(file_exists($writeToFolder.'/'.$projectID.'/'.$informantID.'/'.$reelID.'/'.$regionID.'/'.$userHash.'.xml'))
	{
		//The user has already transcribed this file, append timecode to the user has to avoid overwrite
		//echo '<br/>User hash ('.$userHash.') file exists --- TODO TODO TODO';
		$userHash = $userHash.'_'.date('U');
	}
	else
	{
		//The user has not yet saved this transcription file
		if($DEBUG) echo '<br/>User hash ('.$userHash.') file does NOT exist';
		//mkdir($writeToFolder.'/'.$projectID.'/'.$projectID.'/'.$regionID);
	}
	//...save to path
	$saveToXMLPath = $writeToFolder.$projectID.'/'.$informantID.'/'.$reelID.'/'.$regionID.'/'.$userHash.'.xml';
	
	//Step 2: Copy / setup editing of the transcription template
	if(!file_exists(realpath($transcriptionTemplate)))
	{
		//Error in finding template
		echo 'ERROR: Transcription template is missing';
		$errorEncountered = 'yes';
	}
	else
	{
		//Continue
		if($DEBUG) echo '<br/>Template file exists: '.$transcriptionTemplate;
		global $xml;
		$xml = new DOMDocument();
		$loadSuccess = $xml->load(realpath($transcriptionTemplate));
		$root =  $xml->getElementsByTagName('TEI')->item(0);
		global $xpath;
		$xpath = new DOMXPath($xml);
		if(!$loadSuccess)
		{
			//Error in loading template
			echo 'ERROR: Transcription template is broken';
			$errorEncountered = 'yes';
		}
		else
		{
			if($DEBUG) echo 'Template loaded succefully';
			//echo 'template version: '.$root->getAttribute('template_version');
			
			//Step 3: Fill in <teiHeader>
			//Step 3.1 <fileDesc><titleStmt><title><!-- Insert Audio File INFO here --> Transcription</title>
			$titleValue = $projectID.' '.$informantID.' '.$reelID.' '.$regionID;
			setValue('//teiHeader/fileDesc/titleStmt/title', $titleValue);
			//Step 3.2 <fileDesc><extent><!-- Duration of interview --></extent>
			$durationValue = $_POST['mediaDuration'];
			setValue('//teiHeader/fileDesc/extent', durationSecondsToHrsMinSec($durationValue));
			//Step 3.3 <fileDesc><sourceDesc><recordingStmt><recording type="audio" corresp=""/> File path should be used for correspondence attribute.
			setAttribute('//teiHeader/fileDesc/sourceDesc/recordingStmt/recording', 'corresp', $_POST['mediaPath']);
			//Step 3.4  <revisionDesc> insert <change who="first and last name" affiliation="institution name" contact="e-mail address" when="month/day/year" >Transcribed interview audio.</change>
			setAttribute('//teiHeader/revisionDesc/change', 'who', $scribeName);
			setAttribute('//teiHeader/revisionDesc/change', 'affiliation', $scribeInstitution);
			setAttribute('//teiHeader/revisionDesc/change', 'contact', $scribeEmail);
			setAttribute('//teiHeader/revisionDesc/change', 'when', date("m/d/Y"));
			if($scribeComments != "")
				setValue('//teiHeader/revisionDesc/change', $scribeComments, true);
			
			//Step 4: Fill in <body>
			$bodyElement;
			$bodyNodeset = $xpath->query('//text/body');
			if($bodyNodeset->length > 0)
			{
				$bodyElement = $bodyNodeset->item(0);
			}
			//...Timeline
			/*  <timeline origin="#TS-p1" unit="s">
					<when xml:id="TS-p1" absolute="00:00:00"/>
					<when xml:id="TS-p2" interval="5.0" since="TS-p1"/>
					<when xml:id="TS-p3" interval="5.0" since="TS-p2"/>
					-- the segments would be labeled going forward according to however many we have
					for the duration of the mp3 --
					<when xml:id="TS-p#" absolute="ending time"/>
					-- This entry indicates the final segment. --
				</timeline> */	
			$timelineElement = $xml->createElement('timeline');
			$timelineElement->setAttribute('origin', '#TS-p1');
			$timelineElement->setAttribute('unit', 's');
			$mediaSegmentCount = $_POST['mediaSegmentCount'];
			$mediaSegmentLengthInSeconds = 5;
			//...when element
			for($seg = 1; $seg <= $mediaSegmentCount; $seg++)
			{
				$whenElement = $xml->createElement('when');
				if($seg == 1)
				{
					//First entry
					$whenElement->setAttribute('xml:id','TS-p'.$seg);
					$whenElement->setAttribute('absolute','00:00:00');
				}
				else if($seg == $mediaSegmentCount)
				{
					//Last entry
					$whenElement->setAttribute('xml:id','TS-p#');
					//$durationValue contains the duration in seconds (as a float perhaps)
					$whenElement->setAttribute('absolute',durationSecondsToHrsMinSec($durationValue));
				}
				else
				{
					//All other entries
					$whenElement->setAttribute('xml:id','TS-p'.$seg);
					$whenElement->setAttribute('interval',round($mediaSegmentLengthInSeconds,2));
					$whenElement->setAttribute('since','TS-p'.($seg-1));
				}
				$timelineElement->appendChild($whenElement);
			}
			$bodyElement->appendChild($timelineElement);
			//...region div element
			/* <div type="reel" n="1">
					<div type="region" n="1" who="#name">
						-- Not sure if you want these divisions or not. If you don't want the region name identified, 
						then I can take out all of the character declarations I have listed above.--
						<seg xml:id="TS-u1" start="#TS-p1" end="#TS-p2">
							<u who="#s1" overlap="yes">
						-- "overlap" is an attribute corresponding with your overlap button, or would you rather have it as a tag? --
								<doubt/>
								-- "doubt" corresponds with your doubt button. --
								<nonspeech/>
								-- "nonspeech" corresponds with your non-speech button. --
							</u>
							<u who="#i1">
								<unintelligible/>
								-- "unintelligible" corresponds with your unintelligible button. --
								<nonword/>
								-- "nonword" corresponds with your non-word button --
							</u>
						</seg>
					</div>
				</div> */
			//<div type="region" n="1" who="#name">
			//IMPORTANT: 	We skip the reel-level item because the file will be created on the clip level. This is easier to handle safely.
			//				Once we have files for a complete interview (may take hours, days, months, years or not happen at all) we can 
			//				combine these to a single file (handling multiple authors etc.) and save the file in the master archive
			$divElement = $xml->createElement('div');
			$divElement->setAttribute('type','region');
			$divElement->setAttribute('n',$regionID);
			$divElement->setAttribute('who','#name');
			
			/* 	$utteranceStartMarkers = array('R','A1','A2','I1','I2',);
				$markerToTagCorrespondence = array('?' => 'doubt', 'X:' => 'unintelligible', 'U:' => 'nonword');//overlap? nonspeech? */
			//Compose utterance split pattern
				$splitIntoUtterances = '';
				for($du = 0; $du < count($utteranceStartMarkers); $du++)
				{
					if($du != 0)
						$splitIntoUtterances .= '|';
					$splitIntoUtterances .= str_replace(':','\:',$utteranceStartMarkers[$du]);
				}
				$splitIntoUtterances = '/((?:'.$splitIntoUtterances.').+)/';
			
			if($DEBUG) echo ' mediaSegmentCount = '.$mediaSegmentCount;
			$speakerID = '';//set here so that we know the prvious speaker id in cases where the user forgot to re-enter it in the ne xt segment box
			for($seg = 1; $seg <= $mediaSegmentCount; $seg++)
			{
				//<seg xml:id="TS-u1" start="#TS-p1" end="#TS-p2">
				$segElement = $xml->createElement('seg');
				$segElement->setAttribute('start','#TS-p'.$seg);
				if($seg != $mediaSegmentCount)
					$segElement->setAttribute('end','#TS-p'.($seg+1));
				else
					$segElement->setAttribute('end','');
				//Set content
				$segContent = '';
				if(array_key_exists('segmentBox'.$seg, $_POST))
				{
					$segContent = $_POST['segmentBox'.$seg];
				}
				//...Process segment content
				//Perform pattern split
					//echo '<p>Content: '.$segContent.'</p>';
					if($DEBUG) echo '<br/><b>Split pattern: '.$splitIntoUtterances.'</b>';
					$utterances = preg_split($splitIntoUtterances, $segContent, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
					if($DEBUG) 
					{
						echo 'UTS: '.count($utterances);
						echo '<pre>';
						print_r($utterances);
						echo '</pre>';
					}
					$newSegContent = '';
					$markerToTagCorrespondenceKeys = array_keys($markerToTagCorrespondence);
					for($u=0; $u < count($utterances); $u++)
					{
						if(trim($utterances[$u]) != "")
						{
							//$speakerID = '';//relocated above
							$processedUtterance = '';
							$speakerIdCuttof = strpos($utterances[$u],':');
							if($speakerIdCuttof >= 0)
							{
								$speakerID = substr($utterances[$u],0,$speakerIdCuttof);
								//echo '<br/>_'.$speakerID.'_ Utterence: '.$utterances[$u];
								$processedUtterance = substr($utterances[$u],$speakerIdCuttof+1);
							}
							else
							{
								$processedUtterance = $utterances[$u];
							}
							for($pk = 0; $pk < count($markerToTagCorrespondenceKeys); $pk++)
							{
								$processedUtterance = str_replace($markerToTagCorrespondenceKeys[$pk], '<'.$markerToTagCorrespondence[$markerToTagCorrespondenceKeys[$pk]].'/>',$processedUtterance);
							}
							
							//Package the utterance
							$uElement = $xml->createElement('u');
							$uElement->setAttribute('who','#'.$speakerID);
							$uElement->nodeValue = $processedUtterance;
							$segElement->appendChild($uElement);
							//$processedUtterance = '<u who="#'.$speakerID.'">'.$processedUtterance.'</u>';
							if($DEBUG) echo '<br/>...Processed Utterance: '.$processedUtterance;//if($DEBUG) 
							//$newSegContent .= $processedUtterance;
						}
					}
				
				//...Store segment content
				//$segElement->nodeValue = $newSegContent;//note: string save method!
				$divElement->appendChild($segElement);
			}
			$bodyElement->appendChild($divElement);
			
			//Step 5: Save transcript in the right place
			$saveSuccess = $xml->save(($saveToXMLPath));
			if($saveSuccess > 0)
			{
				if($DEBUG) echo 'DONE. Transcription file saved.';
			}
			else
			{
				echo 'ERROR: Unable to save transcription file to: '.$saveToXMLPath;
				$errorEncountered = 'yes';
			}
			//Report success?
			if($errorEncountered == 'no')
			{
				//Success
				echo '<div class="operationNotification success">
					<h2>Thank you!</h2><p>The transcription was successfully saved to our server.</p>
					<a href="javascript:window.close();">Return to previous page</a>
					</div>';
				//javascript:window.close();
				//javascript:history.back();
			}
			else
			{
				//Failure
				echo '<div class="operationNotification error">An error has occured in saving the transcription file.</div>';
			}
	
			//End of script
			if($DEBUG) echo '(end of script)';
		}
	}
}
/** format the time string given in seconds to hh:mm:ss */
function durationSecondsToHrsMinSec($sec, $padHours = true) 
{
	// start with a blank string
	$hms = "";   
	// do the hours first: there are 3600 seconds in an hour, so if we divide
	// the total number of seconds by 3600 and throw away the remainder, we're
	// left with the number of hours in those seconds
	$hours = intval(intval($sec) / 3600); 
	// add hours to $hms (with a leading 0 if asked for)
	$hms .= ($padHours) 
		  ? str_pad($hours, 2, "0", STR_PAD_LEFT). ":"
		  : $hours. ":";
	// dividing the total seconds by 60 will give us the number of minutes
	// in total, but we're interested in *minutes past the hour* and to get
	// this, we have to divide by 60 again and then use the remainder
	$minutes = intval(($sec / 60) % 60); 
	// add minutes to $hms (with a leading 0 if needed)
	$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ":";
	// seconds past the minute are found by dividing the total number of seconds
	// by 60 and using the remainder
	$seconds = intval($sec % 60); 
	// add seconds to $hms (with a leading 0 if needed)
	$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
	// done!
	return $hms;
}
/* Convenience functions for editing the XML */
/** find element matching the xpath statement and set its value
	Note: does not create the element if it doesn't exist */
function setValue($xp, $value, $append = false)
{
	global $xpath, $errorEncountered, $DEBUG;
	$nodeset = $xpath->query($xp);
	if($nodeset->length > 0)
	{
		if($append)
			$value = $nodeset->item(0)->nodeValue.' '.$value;
		$nodeset->item(0)->nodeValue = $value;
		if($DEBUG) echo '<br/>set value: '.$xp.' = '.$value;
	}
	else
	{
		if($DEBUG) echo '<br/>Error: unable to set value';
		$errorEncountered = 'yes';
	}
}
/** find element matching the xpath statement and set an attribute value
	Note: does not create the element if it doesn't exist */
function setAttribute($xp, $attName, $attValue)
{
	global $xpath, $errorEncountered, $DEBUG;
	if($DEBUG) echo '<br/><b>'.$attName.' = '.$attValue.'</b>';
	$nodeset = $xpath->query($xp);
	if($nodeset->length > 0)
	{
		$nodeset->item(0)->setAttribute($attName, $attValue);
		if($DEBUG) echo '<br/>set attribute: '.$xp.' @'.$attName.' = '.$attValue;
	}
	else
	{
		if($DEBUG) echo '<br/>Error: unable to set attribute';
		$errorEncountered = 'yes';
	}
}
?>
