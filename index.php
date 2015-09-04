<?php
/*
	LICHEN2 Transcription tool for LAP
	Author: Ilkka Juuso / University of Oulu
	Status: Incomplete demo
	Version history:
		2012-04-19	Minor modifications to labels and shortcut keys as per feedback from Bill
					CTRL+F5 page reload and all other function key default behaviors disabled
		2012-02-20	First feature complete version ready for testing
		2011-11-28	Some of the changes made during the UGA meetings now incorporated
		2011-10-05	First line of code written
	Dependencies
		css/main.css
		scripts/3rdParty/jquery/jquery-1.4.2.min.js
		scripts/3rdParty/jquery/jquery-ui-1.8rc3.custom.min.js
		scripts/3rdParty/textinputs/textinputs_jquery.min.js
		resources/jplayer/js/jquery.jplayer.min.js
		resources/jplayer/skin/laplichen/styles.css
		saveTranscript.php (for saving the work)
*/
//SETTINGS
	$DEBUG = false;
	//
	$codeTitle = 'LAP/LICHEN Transcriber';
	$codeVersion = 'Version 0.9 (Beta 4), 2014-05-13';
	//Segment length
	$segmentLengthInSeconds = 5; 
	//Default mediaPath
	//$specifiedMediaPath = "http://www.lap.uga.edu/Projects/DASS/Speakers/LAGS%28INF040%29/Audio/LAGS%28INF040%291/LAGS%28INF040%291%2002%20Family.mp3";
	$specifiedMediaPath = "../testing_only/LAGS(INF657X)1%2003%20Family_N.mp3";

	//Function key definitions
	$functionKeys = array(
		'112' =>	array(	'shortcut' => 'F1',
							'tooltip' => 'Replay current segment (shortcut: CTRL + Space)',
							'function' => 'playAgain()', 
							'label' => '(Re)play'), 		
		/*'113' =>	array(	'shortcut' => 'F2',
							'tooltip' => 'Access text window: enter/edit text; use Tab (Shift Tab) to move to next (or previous) segment; use mouse to select statement [so, hit F2 + Tab/mouse to move between segments, and the sound window changes with the text window selected]',
							'function' => '', 
							'label' => '?'), */
		'113' =>	array(	'shortcut' => 'F2',
							'tooltip' => 'Pause playback',
							'function' => 'togglePause()', 
							'label' => 'Pause'),
							
		
		'114' =>	array(	'shortcut' => 'F3',
							'tooltip' => 'Start of speech by main interviewer',
							/*'function' => 'insertMarker(\'\rI1: \', \'point\')',*/
							'function' => array(
								'default' => 'insertMarker(\'\rI1: \', \'point\')',
								'shift' => 'insertMarker(\'\rI2: \', \'point\')',
								'ctrl' => 'insertMarker(\'\rI3: \', \'point\')',
								'alt' => 'insertMarker(\'\rI4: \', \'point\')',
								),
							'label' => 'Interviewer'),
		'115' =>	array(	'shortcut' => 'F4',
							'tooltip' => 'Start of speech by speaker',
							/*'function' => 'insertMarker(\'\rR: \', \'point\')',*/
							'function' => array(
								'default' => 'insertMarker(\'\rR: \', \'point\')',
								'shift' => 'insertMarker(\'\rA1: \', \'point\')',
								'ctrl' => 'insertMarker(\'\rA2: \', \'point\')',
								'alt' => 'insertMarker(\'\rIA3: \', \'point\')',
								),
							'label' => 'Respondent'),	


		'116' =>	array(	'shortcut' => 'F5',
							'tooltip' => 'Start of speech that overlaps speech by the previous speaker (use appropriate key F3-F7 immediately following F8)',
							'function' => 'insertMarker(\'{OVERLAP} \', \'point\')',
							'label' => 'Overlap'),
							
		'117' =>	array(	'shortcut' => 'F6',
							'tooltip' => 'Transcriber doubt about status of preceding word spelling (use immediately after a word with no space between word and F9)',
							'function' => array(
								'default' => 'insertMarker(\'D: \', \'point\')',
								'shift' => 'insertUserText(\'Doubt\',\'D:{\',\'}\')',
								),
							'label' => 'Doubt'),
		
		'118' => 	array(	'shortcut' => 'F7',
							'tooltip' => 'Comment',
							'function' => 'insertUserText(\'Type your comment here\',\'{\',\'}\')',
							'label' => 'Comment'),
		
						
		'119' => 	array(	'shortcut' => 'F8',
							'tooltip' => 'Unintelligible word(s) in utterance',
							'function' => 'insertMarker(\'X: \', \'point\')',
							'label' => 'Unintelligible'),
							
		'120' => 	array(	'shortcut' => 'F9',
							'tooltip' => 'Non-word utterance',
							'function' => 'insertMarker(\'U: \', \'point\')',
							'label' => 'Non-word'),
							
		'121' => 	array(	'shortcut' => 'F10',
							'tooltip' => 'Non-speech sound audible on recording',
							'function' => 'insertMarker(\'{NONSPEECH} \', \'point\')',
							'label' => 'Non-speech'),

		'122' => 	array(	'shortcut' => 'F11',
							'tooltip' => 'Beep',
							'function' => 'insertMarker(\'{BEEP} \', \'point\')',
							'label' => 'Beep'),
		

							
		'123' => 	array(	'shortcut' => 'F12',
							'tooltip' => 'Save',
							'function' => 'saveAllToLocalStorage()',
							'label' => 'Save to cache')
	);
	
	//Debug buttons code
	$debugButtons = '<b>DEBUG BUTTONS:</b> <a href="javascript:initialize();">Initialize tool</a> 
 <a href="javascript:writeAllSegmentsToLocalStorage();">Write all to memory</a> 
 <a href="javascript:refreshAllSegmentsFromLocalStorage();">Refresh all from memory</a> 
 <a href="javascript:writeProjectMetadataToLocalStorage();">Write project metadata</a> 
 <a href="javascript:clearAllSegments();">Clear all segments</a>  
 <a href="javascript:testDuration();" accesskey="d">Test duration</a> 
 <a href="javascript:testPlayClip();" accesskey="P">playClip</a>';

//REQUEST PARAMETERS
	if(array_key_exists('mediaPath',$_REQUEST))
	{
		$specifiedMediaPath = $_REQUEST['mediaPath'];
	}
//DO
	$debugableFieldtype = 'hidden';
	if($DEBUG)
	{
		$debugableFieldtype = 'text';
	}

echo '<!DOCTYPE html>';
echo '<html>';
//HEAD
echo '<head>';
	//Basic metadata
	echo '<meta charset="UTF-8">';
	echo '<title>'.$codeTitle.' - '.$codeVersion.'</title>';
	echo '<link href="../common/css/main.css" rel="stylesheet" type="text/css" />';
	//Load main scripts
	echo '<script src="../common/resources/jquery/jquery-1.4.2.min.js" type="text/javascript"></script>';
   	//jQuery UI addon
   	echo '<script src="../common/resources/jquery/jquery-ui-1.8rc3.custom.min.js" type="text/javascript"></script>';
	//Textinputs extension to jquery: http://code.google.com/p/rangyinputs/
	echo '<script src="../common/resources/textinputs/textinputs_jquery.min.js" type="text/javascript"></script>';
	//Jplayer - lap lichen mod
	echo '<link href="../common/resources/jplayer/skin/laplichen/styles.css" rel="stylesheet" type="text/css" />';
	echo '<script type="text/javascript" src="../common/resources/jplayer/js/jquery.jplayer.min.js"></script>';

?>
<script type="text/javascript">
	//Javascript Settings
	var segmentLengthInSeconds = <?php echo $segmentLengthInSeconds; ?>;
	var jplayerSwfPath = "../common/resources/jplayer/js";
	//Init
	var mediaLabel = "Current clip";
	var mediaPath = <?php echo '"'.$specifiedMediaPath.'"'; ?>;
	//Variables
	var currentSegmentCount = -1;
	var currentSegmentId = -1;

	var localStorageAvailable = false;
	var segmentPlayStartOffsetInSeconds = -1;
	
	var playbackPaused = false;
	var playbackPausePoint = -1;
	
	//Do
	$(document).ready(function() { preload(); });

	//Functions
//INITIALIZATION
	function preload()
	{
		//Init GUI
		//...hide the javascript not available message
		$("#javascriptDisabledNotice").hide();
		$(".hiddenIfToolDisabled").removeClass("hiddenIfToolDisabled");
		//...if we have required fields hide the submit buttons for now
		if($("input.required").length > 0)
		{
			$("#submitReady").hide();
			$("input.required").blur(checkSubmitReadiness);
		}
		//..Override submit button behavior
		$("input[type=submit]").click(performAjaxSubmit);
		//Create player
		$("#jquery_jplayer_1").jPlayer(
		{
			ready: function (event)
				{
					// Define the mp3 url, invoke play to load and pause immediately
					console.log('Loading media: '+mediaPath);
					$(this).jPlayer("setMedia", { mp3: mediaPath }).jPlayer("play", 0).jPlayer("pause");
					// Update systemBusyDisplay
					$("#systemBusy").html('<div class="systemBusyInfo loadingMedia">Looking up media... <a href="javascript:testPlayClip();">(click here if nothing happens)</a></div>');
					$("#systemBusy").css('display','inline');
					$("#systemReady").css('display','none');
				},
			loadeddata: function (event)
				{
					console.log('Loading done');
					 initialize();
				},
			progress: function (event)
				{
					//document.title = "Ready to initialize";
					var seekPercent = Math.round(event.jPlayer.status.seekPercent);
					if (seekPercent === 100)
					{
                        //initialize();
                    }
					else
					{
						$("#systemBusy").html('<div class="systemBusyInfo loadingMedia">Loading media. Please wait. '+seekPercent+'% done.</div>');
					}
				},
			pause: function (event) { playbackPausePoint = event.jPlayer.status.currentTime; },
			swfPath: jplayerSwfPath,
			supplied: "mp3",
			wmode: "window"
		});
	}

	function initialize()
	{
		// Remove the loadedmetadata events with the "initialize" namespace
		$("#jquery_jplayer_1").unbind($.jPlayer.event.canplaythrough + ".initialize");
		$("#jquery_jplayer_1").unbind($.jPlayer.event.progress);
		var currentMediaDuration = $("#jquery_jplayer_1").data("jPlayer").status.duration;
 		if(currentMediaDuration > 0)
		{
			currentSegmentCount = Math.ceil(currentMediaDuration/segmentLengthInSeconds);
			$("input[name=mediaDuration]").val(currentMediaDuration);
			$("input[name=mediaSegmentCount]").val(currentSegmentCount);
 			//Set infoDisplay
 			updateInfoDisplay("Media length is <b>"+Math.round(currentMediaDuration)+" seconds</b>, which means that we have <b>"+currentSegmentCount+" segments</b> of approx. "+segmentLengthInSeconds+" in length.");
			//Finalize GUI after media metadata available	
			insertSegmentBoxes(currentSegmentCount);
			//html5 local storage
			localStorageAvailable = isLocalStorageAvailable();
			if(localStorageAvailable)
			{
				var message = 'HTML5 Local Storage is available and will be used to save your work to your browser\'s local cache every time you move between segments.';
				//updateInfoDisplay(message);
				$('#mainHelp').append(' '+message);
				if(mediaPath == localStorage.getItem('transcriptionMediaPath'))
				{
					//mediaPath
					refreshAllSegmentsFromLocalStorage();
					refreshProjectMetadataFromLocalStorage();
					var message = '<strong>NB!</strong> This form was reloaded from your browser\'s local cache. If you want to start from scratch click <a href="javascript:resetForm();">here</a> to reset the form.';
					updateInfoDisplay(message);
					$('#mainHelp').append(' '+message);
				}
			}
			else
			{
				//WARNING: No HTML5 LocalStorage
				updateInfoDisplay('HTML5 Storage NOT available');
			}
			checkSubmitReadiness();
			//Finish
			$("#systemBusy").css('display','none');
			$("#systemReady").fadeIn('slow');
 		}
		else
		{
			//Error: Media length is unknown
			//Set infoDisplay
 			updateInfoDisplay("Error: Media length unknown");
		}
		updateSegmentPreview();
	}

	function insertSegmentBoxes(count)
	{
		if(currentSegmentId < 1 && count > 0)
			currentSegmentId = 1;
		for(var sbi = 1; sbi <= count; sbi++)
		{
			var initialMode = 'collapse';
			if(currentSegmentId == sbi)
				initialMode = 'expand';
			$("#segmentBoxes").append('<textarea id="segmentBox'+sbi+'" name="segmentBox'+sbi+'" class="segmentBox '+initialMode+'" cols="1" rows="20"></textarea>');
		}
		updateSegmentLabel();
	}

	function checkSubmitReadiness()
	{
		var allRequiredFieldsAreFilled = true;
		var allRequiredInputs = $("input.required");
		for (var i = 0; i < allRequiredInputs.length; i++)
		{
			if(allRequiredInputs[i].value == "")
			{
				allRequiredFieldsAreFilled = false;
			}
      	}
		//Do we have all required info? If so, enable submit
		if(allRequiredFieldsAreFilled)
		{
			$("#submitReady").fadeIn();
		}
		else
		{
			$("#submitReady").fadeOut();
		}
		return allRequiredFieldsAreFilled;
	}
	
	function performAjaxSubmit()
	{
		//organize the data properly
        var data = '';
        var allInputs = $(":input");
		for (var i = 0; i < allInputs.length; i++)
		{
			//document.title += "..."+allInputs[i].name+" = "+allInputs[i].value;
			data += allInputs[i].name+"="+allInputs[i].value+"&";
      	}
        //disabled all the text fields
        $('input[type=text]').attr('disabled','true');
		$('textarea').attr('disabled','true');
        //show the loading sign
		$('#systemBusy').prepend('<div id="submittingForm" class="initiallyHidden">Uploading...</div>');
		$('#submittingForm').fadeIn('slow');
        //start the ajax
        $.ajax({
            url: "saveTranscript.php",
            type: "POST",
            data: data,    
            //Do not cache the page
            cache: false,
            //success
            success: function (html) {             
                //if process.php returned 1/true (send mail success)
                if (html!=0)
				{
                    //hide the form
                    $('form').fadeOut('slow');
					//hide any busy messages
					$('#systemBusy').fadeOut('slow');
                    //show the success message
                    $('#systemReady').prepend('<div id="uploadComplete" class="initiallyHidden">'+html+'</div>');
					updateInfoDisplay('');
					if($('.operationNotification.success').length > 0)
					{
						//The report indicates success, clear the form & html5 local storage
						resetForm();
					}
					else
					{
						//Some error has occured
						$('#systemReady').append('<a href="javascript:returnToForm();" class="returnToForm">Return to form</a>');
					}
					$('#uploadComplete').fadeIn('slow');
                }
				else
				{
					alert('Sorry, unexpected error. Please try again later.');
				}
            }      
        });
        //cancel the submit button default behavior
        return false;
	}
	
	function returnToForm()
	{
		//hide the return link
		$('a.returnToForm').hide();
		//Enable all the text fields
		$('input[type=text]').removeAttr('disabled');
		$('textarea').removeAttr('disabled');
		//Show form
		$('form').fadeIn('slow');
	}
	
//BASIC GUI OPS	
	function updateSegmentLabel()
	{
		$("#segmentLabel").html('Segment '+currentSegmentId+" of "+currentSegmentCount+"<span class=\"additionalInfo\"> | "+(currentSegmentId-1)*segmentLengthInSeconds +" - "+(currentSegmentId)*segmentLengthInSeconds+" seconds</span>");
	}
	
	function updateSegmentPreview()
	{
		//previous
		var previewOfPreviousSegment = '';
		if(currentSegmentId-1 > 0 && currentSegmentId-1 <= (currentSegmentCount-1))
			previewOfPreviousSegment = $("#segmentBox"+(currentSegmentId-1)).val();
		if(previewOfPreviousSegment != '')
			previewOfPreviousSegment = '"'+previewOfPreviousSegment+'"';
		$("#segmentPreview .previous .content").html(previewOfPreviousSegment);
		
		//next
		var previewOfNextSegment = '';
		if(currentSegmentId+1 > 0 && currentSegmentId+1 <= (currentSegmentCount-1))
			previewOfNextSegment = $("#segmentBox"+(currentSegmentId+1)).val();
		if(previewOfNextSegment != '')
			previewOfNextSegment = '"'+previewOfNextSegment+'"';
		$("#segmentPreview .next .content").html(previewOfNextSegment);
	}

	function updateInfoDisplay(msg)
	{
		$("#infoDisplay").prepend(msg+'<br/>');
	}
	
//SEGMENT NAVIGATION
	function moveToNextSegment()
	{
		if(currentSegmentId > 0 && currentSegmentId <= (currentSegmentCount-1))
		{
			writeProgressToLocalStorage(currentSegmentId);
			var segmentBox = $("#segmentBox"+currentSegmentId);
			segmentBox.removeClass('expand');
			segmentBox.addClass('collapse');
			currentSegmentId = currentSegmentId+1;
			segmentBox = $("#segmentBox"+currentSegmentId);
			segmentBox.removeClass('collapse');
			segmentBox.addClass('expand');
		}
		updateSegmentLabel();
		updateSegmentPreview();
		playSegment(currentSegmentId);
	}

	function moveToPreviousSegment()
	{
		if(currentSegmentId-1 > 0 && currentSegmentId <= (currentSegmentCount))
		{
			writeProgressToLocalStorage(currentSegmentId);
			$("#segmentBox"+currentSegmentId).removeClass('expand');
			$("#segmentBox"+currentSegmentId).addClass('collapse');
			currentSegmentId = currentSegmentId-1;
			$("#segmentBox"+currentSegmentId).removeClass('collapse');
			$("#segmentBox"+currentSegmentId).addClass('expand');
		}
		updateSegmentLabel();
		updateSegmentPreview();
		playSegment(currentSegmentId);
	}

//MEDIA PLAYBACK
	function playSegment(segmentId)
	{
		var segmentStartPointInSeconds = (currentSegmentId-1)*segmentLengthInSeconds;
		var segmentEndPointInSeconds = (currentSegmentId)*segmentLengthInSeconds;
		updateInfoDisplay('Play from '+segmentStartPointInSeconds+' to '+segmentEndPointInSeconds);
		$("#jquery_jplayer_1").jPlayer("play", segmentStartPointInSeconds+segmentPlayStartOffsetInSeconds); 
		$("#jquery_jplayer_1").unbind($.jPlayer.event.timeupdate + ".playSegment"); 
		$("#jquery_jplayer_1").bind($.jPlayer.event.timeupdate + ".playSegment", 
			function(event) { 
		    	if(event.jPlayer.status.currentTime > segmentEndPointInSeconds) { 
    	    		$(this).jPlayer("pause"); 
	    		} 
			});
		playbackPaused = false;
	}
	function playAgain() { playSegment(currentSegmentId); }

	function togglePause()
	{
		//document.title = 'TOGGLE PLAY/PAUSE';
		if(playbackPaused)
		{
			//Play
			var segmentStartPointInSeconds = playbackPausePoint;
			var segmentEndPointInSeconds = (currentSegmentId)*segmentLengthInSeconds;
			if(playbackPausePoint < segmentEndPointInSeconds)
			{
				updateInfoDisplay('Play from '+Math.round(segmentStartPointInSeconds)+' to '+segmentEndPointInSeconds);
				$("#jquery_jplayer_1").jPlayer("play", segmentStartPointInSeconds+segmentPlayStartOffsetInSeconds); 
				$("#jquery_jplayer_1").unbind($.jPlayer.event.timeupdate + ".playSegment"); 
				$("#jquery_jplayer_1").bind($.jPlayer.event.timeupdate + ".playSegment", 
					function(event) { 
						if(event.jPlayer.status.currentTime > segmentEndPointInSeconds) { 
							$(this).jPlayer("pause"); 
						} 
					});
				playbackPaused = false;
			}
		}
		else
		{
			//Pause
			updateInfoDisplay('Pause');
			$("#jquery_jplayer_1").jPlayer("pause");
			playbackPaused = true;
		}
	}

//INSERT MARKERS
	function insertMarker(marker, type)
	{
		if(currentSegmentId > 0)
		{
			var sel = $("#segmentBox"+currentSegmentId).getSelection();
			//insertText(String text, Number pos, Boolean moveSelection)
			$("#segmentBox"+currentSegmentId).focus();//IE hack
			$("#segmentBox"+currentSegmentId).insertText(marker, sel.start, true);
			$("#segmentBox"+currentSegmentId).focus();
		}
	}

	function insertTimestamp()
	{
		var currentTimePosition = $("#jquery_jplayer_1").data("jPlayer").status.currentTime;
		if(currentTimePosition >= 0)
		{
			//var roundedCurrentTimePoistion = Math.round(currentTimePosition,0);
			//$.jPlayer.convertTime( Number: seconds )
			var formatedTime = $.jPlayer.convertTime(currentTimePosition);
			insertMarker("T: "+formatedTime+" \n", "position");
			//document.title = "CURRENT TIME: "+currentTimePosition+" / "+formatedTime;
		}
	}

	function insertUserText(promptText, preTag, postTag)
	{
		var commentText = prompt(promptText,"");
		if (commentText != null && commentText != "")
		{
			insertMarker(' '+preTag+commentText+postTag+' ', 'point');
		} 
	}

//KEYBOARD SHORTCUTS
	var isCtrl = false;
	var isShift = false;
	$(document).keyup(function (e) 
	{
		//Switch modifiers off
		switch(e.which)
		{
			case 16:
				isShift=false;
				break;
			case 17:
				isCtrl=false;
				break;
		}
	}).keydown(function (e) {
		//Switch modifiers on
		$thisKeyIsAModifier = false;
		switch(e.which)
		{
			case 16:
				isShift = true;
				$thisKeyIsAModifier = true;
				break;
			case 17:
				isCtrl = true;
				$thisKeyIsAModifier = true;
				break;
		}
		
		if($thisKeyIsAModifier)
		{
			return false;
		}
		else
		{
			switch(e.which)
			{
				/* SPACE */
				case 32:
					if(isCtrl == true)
					{
						//Re-play
						playAgain();
					}
					break;
				/* TAB */
				case 9:
					if(isShift == true)
					{
						//Move to the previous segment
						moveToPreviousSegment();
					}
					else
					{
						//Move to the next segment
						moveToNextSegment();
					}
					break;
	<?php
			//Insert keyboard shortcut handlers for function keys
			$functionArrayKeys = array_keys($functionKeys);
			for($fk=0; $fk < count($functionArrayKeys); $fk++)
			{
				$keyCode = $functionArrayKeys[$fk];
				$functionCall = $functionKeys[$functionArrayKeys[$fk]]['function'];
				if(is_array($functionCall))
				{
					//Modifier keys also supported
					/*'function' => array(
						'default' => 'insertMarker(\'\rA1: \', \'point\')',
						'shift' => 'insertMarker(\'\rA2: \', \'point\')',
						'ctrl' => 'insertMarker(\'\rA3: \', \'point\')',
						'shift+ctrl' => 'insertMarker(\'\rA4: \', \'point\')'
						),*/
						
					echo '
				case '.$keyCode.':
						if(isCtrl == false && isShift == false) { '.$functionCall['default'].'; }';
						//Shift
						if(array_key_exists('shift',$functionCall))
						{
							echo '
						else if(isCtrl == false && isShift == true) { '.$functionCall['shift'].'; }';
						}
						//Ctrl
						if(array_key_exists('ctrl',$functionCall))
						{
							echo '
						else if(isCtrl == true && isShift == false) { '.$functionCall['ctrl'].'; }';
						}
						//Shift+Ctrl
						if(array_key_exists('shift+ctrl',$functionCall))
						{
							echo '
						else if(isCtrl == true && isShift == true) { '.$functionCall['shift+ctrl'].'; }';
						}
						
						
					echo'
						return false;
						break;';//return false cancels the default handling (e.g. f5 for reload page)
				}
				else if(trim($functionCall) != "")
				{
					//Basic key functionality, no modifiers
					echo '
				case '.$keyCode.':
						if(isCtrl == false && isShift == false)
						{
							'.$functionCall.';
							return false;
						}
						break;';//return false cancels the default handling (e.g. f5 for reload page)
				}
			}
	?>
				default:
					//document.title = "DEFAULT: "+e.which;
					break;
			}
			//return false;
		}
	});

//HTML5 STORAGE
	function writeProgressToLocalStorage(segmentId)
	{
		writeSegmentToLocalStorage(segmentId);
		writeProjectMetadataToLocalStorage();
	}

	function isLocalStorageAvailable()
	{
		try
		{
			return 'localStorage' in window && window['localStorage'] !== null;
		}
		catch (e) { return false; }
	}
	
	function writeSegmentToLocalStorage(segmentId)
	{
		if(localStorageAvailable)
		{
			updateInfoDisplay('Write segment '+segmentId+' to local storage');
			//updateInfoDisplay('...ID-PATH: '+mediaPath);
			localStorage.setItem('transcriptionMediaPath',mediaPath);
			localStorage.setItem('transcriptionSegmentCount',currentSegmentCount);
			//Write item itself
			var thisSegmentContent = $("#segmentBox"+segmentId).val();
			localStorage.setItem('transcriptionSegment_'+segmentId,thisSegmentContent);
			//updateInfoDisplay('...Writing segment '+segmentId+': '+thisSegmentContent);
		}
		else
		{
			//ERROR SAVING TO LOCAL STORAGE: No HTML5 LocalStorage
			updateInfoDisplay('ERROR: HTML5 Storage NOT available');
		}
	}
	
	function writeProjectMetadataToLocalStorage()
	{
		if(localStorageAvailable)
		{
			$('#projectMetadata input').each(function(index)
			{
				//alert(index + ': ' + $(this).text());
				//updateInfoDisplay('Write meta '+index+' to local storage: '+$(this).attr('name')+" -- "+$(this).val());
				localStorage.setItem($(this).attr('name'),$(this).val());
			});
		}
		else
		{
			//ERROR SAVING TO LOCAL STORAGE: No HTML5 LocalStorage
			updateInfoDisplay('ERROR: HTML5 Storage NOT available');
		}
	}
	function refreshProjectMetadataFromLocalStorage()
	{
		if(localStorageAvailable)
		{
			$('#projectMetadata input').each(function(index)
			{
				if(localStorage.getItem($(this).attr('name')))
				{
					var storedValue = localStorage.getItem($(this).attr('name'));
					$(this).val(storedValue);
				}
				else
				{
					updateInfoDisplay('No entry for: '+$(this).attr('name'));
				}
			});
		}
		else
		{
			//ERROR SAVING TO LOCAL STORAGE: No HTML5 LocalStorage
			updateInfoDisplay('ERROR: HTML5 Storage NOT available');
		}
	}
	
	function writeAllSegmentsToLocalStorage()
	{
		if(localStorageAvailable)
		{
			//updateInfoDisplay('writeAllSegmentsToLocalStorage');
			updateInfoDisplay('Write '+currentSegmentCount+' segments to local storage');
			//updateInfoDisplay('...ID-PATH: '+mediaPath);
			localStorage.setItem('transcriptionMediaPath',mediaPath);
			localStorage.setItem('transcriptionSegmentCount',currentSegmentCount);
			for(var sbi = 1; sbi <= currentSegmentCount; sbi++)
			{
				var thisSegmentContent = $("#segmentBox"+sbi).val();
				localStorage.setItem('transcriptionSegment_'+sbi,thisSegmentContent);
				//updateInfoDisplay('...Writing segment '+sbi+': '+thisSegmentContent);
			}
			updateInfoDisplay('Saved '+sbi+' segments to local storage');
		}
		else
		{
			//ERROR SAVING TO LOCAL STORAGE: No HTML5 LocalStorage
			updateInfoDisplay('ERROR: HTML5 Storage NOT available');
		}
	}
	
	function saveAllToLocalStorage()
	{
		writeProjectMetadataToLocalStorage();
		writeAllSegmentsToLocalStorage();
	}
	
	/** Refresh all segments from local storage 
		NOTE: no new segment boxes will be inserted, so the number of segments boxes will not change even if there are too few on page */
	function refreshAllSegmentsFromLocalStorage()
	{
		if(localStorageAvailable)
		{
			currentSegmentCount = localStorage.getItem('transcriptionSegmentCount');
			for(var sbi = 1; sbi <= currentSegmentCount; sbi++)
			{
				var thisSegmentContent = localStorage.getItem('transcriptionSegment_'+sbi);
				$("#segmentBox"+sbi).val(thisSegmentContent);
				//updateInfoDisplay('...Read segment '+sbi+': '+thisSegmentContent);
			}
			updateInfoDisplay('Refreshed all '+currentSegmentCount+' segments from local storage');
		}
		else
		{
			//ERROR READING FROM LOCAL STORAGE: No HTML5 LocalStorage
			updateInfoDisplay('ERROR: HTML5 Storage NOT available');
		}
	}
	/** Clear all segments */
	function clearAllSegments()
	{		
		$("#segmentBoxes .segmentBox").val('');
		updateInfoDisplay('Reset all segments');
	}
	
	function resetForm()
	{
		clearAllSegments();
		if(localStorageAvailable)
		{
			//Clear HTML5 local storage
			localStorage.clear();
		}
	}

//TEST FUNCTIONS
	

	/** Test function for playing a clip. Used also when the browser prohibits download without a user trigger. */
	function testPlayClip() 
	{
    	$("#jquery_jplayer_1").jPlayer("play", 1); 
		$("#jquery_jplayer_1").bind($.jPlayer.event.timeupdate, 
			function(event) { 
		    	if(event.jPlayer.status.currentTime > 1) { 
    	    		$(this).jPlayer("pause"); 
	    		} 
			}); 
	}

</script>
<?php	
echo '</head>';
//BODY
echo '<body>';
//echo '<a class="play button" href="javascript:playAgain();"></a>';

echo '<div id="topbar">';
		
		echo '<div id="pagetitle">'.$codeTitle.' - '.$codeVersion.'</div>';
		echo '<div class="hiddenIfToolDisabled">';
		//jPlayer
		echo '<div id="jquery_jplayer_1" class="jp-jplayer"></div>
				<div id="jp_container_1" class="jp-audio">
					<div class="jp-type-single">
						<div class="jp-title">
							<ul>
								<li><span class="label">Media: </span>'.urldecode($specifiedMediaPath).'</li>
							</ul>
						</div>
						<div class="jp-gui jp-interface">';
							echo '<ul class="jp-controls">';
								/*echo '<li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
								<li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
								<li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>';*/
								echo '<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
								<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
								<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
							</ul>';
							echo '<div class="jp-progress">
								<div class="jp-seek-bar">
									<div class="jp-play-bar"></div>
								</div>
							</div>';
							echo '<div class="jp-volume-bar">
								<div class="jp-volume-bar-value"></div>
							</div>
							<div class="jp-time-holder">
								<div class="jp-current-time"></div>
								<div class="jp-duration"></div>';
							/*echo '<ul class="jp-toggles">
									<li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
									<li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
								</ul>';*/
							echo '</div>
						</div>
						
						<div class="jp-no-solution">
							<span>Update Required</span>
							To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
						</div>
					</div>
				</div>';
		//Info display
		echo '<div id="infoDisplay">';
		if($DEBUG)
		{
			echo $debugButtons;
		}
		echo '</div>';
		echo '</div>';//end of: hiddenIfToolDisabled
		
echo '</div>';//end of: topbar

echo '<div id="workarea">';
	//Warning message if javascript not enabled
	echo '<div id="javascriptDisabledNotice"><h2>Important!</h2><p>In order to use this tool you must have javascript enabled in your browser. If you are seeing this message javascript is disabled and you can\'t use the tool. If you do not know how to enable javascript you should contact your technical support or maybe try another browser. You can also find more information on enabling javascript <a href="http://support.google.com/bin/answer.py?hl=en&answer=23852" target="_blank">here</a>.</p><p>- Development team</p></div>';
	//System busy area
	echo '<div id="systemBusy"></div>';
	//System ready area
	echo '<div id="systemReady" class="initiallyHidden hiddenIfToolDisabled">';
	//Form	
		echo '<form id="segmentsForm" action="saveTranscript.php" method="post" name="segmentTranscription">';
		
		echo '<a class="discreteLink" href="javascript:window.close();" title="Cancel">&laquo; Cancel</a>';

		echo '<h2>Your transcript</h2>';
		echo '<p id="mainHelp">Write your transcription of the media file here. The media file has been split into segments of approx. '.$segmentLengthInSeconds.' seconds so that we can link the transcript with the audio. To move between the segments just click on the previous/next arrows below.</p>';
		
		//Segments
		echo '<a class="pane jumpButton jumpToPrevious" href="javascript:moveToPreviousSegment();" title="Move to the previous segment (shortcut: SHIFT + TAB)">Previous</a>
		<div class="pane centralPane">';
			
			//Segment label
			echo '<div id="segmentLabel"></div>';
			
			//Function keys
			echo '<div class="buttonbar">';
			$functionArrayKeys = array_keys($functionKeys); 
			for($fk=0; $fk < count($functionArrayKeys); $fk++)
			{
				echo '<a href="javascript:';
				$functionCall = $functionKeys[$functionArrayKeys[$fk]]['function'];
				if(is_array($functionCall))
				{
					//Multiple functions for the key
					echo $functionCall['default'];
				}
				else
				{
					//Just a single function for the key
					echo $functionCall;
				}
				echo ';" class="button" title="'.$functionKeys[$functionArrayKeys[$fk]]['tooltip'].'"><span class="keyhint">';
				if($functionKeys[$functionArrayKeys[$fk]]['shortcut'] != '')
					echo $functionKeys[$functionArrayKeys[$fk]]['shortcut'];
				else
					echo '-';
				echo '&nbsp;</span><span class="label">'.$functionKeys[$functionArrayKeys[$fk]]['label'].'</span>';
				if(is_array($functionCall))
				{
					//Multiple functions for the key
					//echo '^';
				}
				echo '</a>';
			}
			echo '<div style="clear: both; float: none;"></div>';
			echo '</div>';
			
			//Actual segment boxes
			echo '<div id="segmentBoxes"></div>';
			
			echo '<div id="segmentPreview">
				<div>
					<div class="previous">
						<span class="label"><a href="javascript:moveToPreviousSegment();">Previous segment</a></span><span class="content"></span>
					</div>
					<div class="next">
						<span class="label"><a href="javascript:moveToNextSegment();">Next Segment</a></span><span class="content"></span>
					</div>
				</div>
			</div>';
			
		echo '</div>
		<a class="pane jumpButton jumpToNext" href="javascript:moveToNextSegment();" title="Move to the next segment (shortcut: TAB)">Next</a>';
		
		//Project metadata
		echo '<div id="projectMetadata">';
			//Insert contributor info form
			echo '<div id="contributorInfo"><h2>Your information</h2>';
			include 'components/contributorInfo.php';
			echo '</div>';
			//Insert hidden project metadata
			echo '<input name="mediaPath" type="'.$debugableFieldtype.'" value="'.$specifiedMediaPath.'" />';
			echo '<input name="mediaDuration" type="'.$debugableFieldtype.'" value="" />';
			echo '<input name="mediaSegmentCount" type="'.$debugableFieldtype.'" value="" />';
		echo '</div>';

		//Submit form
		echo '<div id="submitReady">';// class="initiallyHidden"
			echo '<h2>Ready to submit?</h2>';
			echo '<p>Once you are happy with your transcript you can submit it to our server.</p>';
			echo '<input name="submitForm" type="submit" value="Submit" />';
		echo '</div>';

		echo '</form>';
	echo '</div>';	
echo '</div>';//end of: workarea

echo '</body>';
echo '</html>';
?>
