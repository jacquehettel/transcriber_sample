<?php
/*
	LICHEN2 Snippet for contributor info
	Author: Ilkka Juuso / University of Oulu
	Status: Incomplete demo
	Version history:
		2012-02-13	First line of code written
*/
	echo '<p>All fields marked with an asterisk (<span class="obligatory">*</span>) are required. Filled in all the required fields but can\'t see the Submit button? Click <a href="#">here</a>.</p>';
	echo '<table>';
		echo '<tr><td>Your name <span class="obligatory">*</span></td><td>';
		echo '<input name="scribeName" type="text" value="" class="required"/>';
		echo '</td></tr>';
		
		echo '<tr><td>Your e-mail address <span class="obligatory">*</span></td><td>';
		echo '<input name="scribeEmail" type="text" value="" class="required"/>';
		echo '</td></tr>';
		
		echo '<tr><td>Your Institution</td><td>';
		echo '<input name="scribeInstitution" type="text" value="" />';
		echo '</td></tr>';
		
		echo '<tr><td>Comments</td><td>';
		echo '<textarea name="scribeComments" type="text"></textarea>';
		echo '</td></tr>';

	echo '</table>';

?>
