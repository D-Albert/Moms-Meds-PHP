<?php
/*
	A utility for tracking which medications someone is taking and when.
	
	Written in PHP because it's convenient for me.
	
	Note that this lacks a lot of the error checking and input validation I would normally
	give something like this.
	This is because it's just a little thing you use for yourself occasionally, so a bit
	of time adjusting your JSON isn't the biggest problem.

#-------- -------- -------- -------- -------- -------- -------- -------- -------- -------- 
#This program is available under the MIT License:
#Copyright (c) 2026 David Albert

#Permission is hereby granted, free of charge, to any person obtaining a copy of this
#software and associated documentation files (the "Software"), to deal in the Software
#without restriction, including without limitation the rights to use, copy, modify, merge,
#publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
#to whom the Software is furnished to do so, subject to the following conditions:

#The above copyright notice and this permission notice shall be included in all copies or
#substantial portions of the Software.

#THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
#INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
#PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
#FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
#OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
#DEALINGS IN THE SOFTWARE.
#-------- -------- -------- -------- -------- -------- -------- -------- -------- -------- 

*/

require_once ("PHPLibrary/PrescriptionClass.php");

date_default_timezone_set("America/Los_Angeles");
$datePrinted = date ("Y-m-d");


//Load the info:
$raw = "";
if (file_exists ("medications.dat") && !is_dir ("medications.dat")) {
	$raw = file_get_contents("medications.dat");
} else {
	echo "Medications file not found, nothing to display.<br />Load the information into a text file named \"medications.dat\" as JSON.";
	if (!file_exists ("medications.dat")) {
		//Create medications.dat for convenience.
		//Write some sample JSON into it for people to tweak rather than having to figure it out for themselves.
		$fp = fopen ("medications.dat", "w");
		$sampleJson = '
{
	"Name": "Patient Name Here",
	"DateUpdated": "2026-01-01",
	"Medications": [
		{
			"genericName": "Example Medication",
			"brandName": "",
			"describeAs": "pill",
			"notes": "",
			"prescribingDoctor": "Dr. Jane Example",
			"currentlyTaking": true,
			"dosageUnit": "mg",
			"dosePerPill": 10,
			"dosage": {
				"Morn": 20
				"Bed": 20
			}
		},
		{
			"genericName": "Sample Medication",
			"brandName": "Samplezine",
			"describeAs": "pill",
			"notes": "",
			"prescribingDoctor": "Dr. John Sample",
			"currentlyTaking": true,
			"dosageUnit": "mg",
			"dosePerPill": 5,
			"dosage": {
				"As needed": 2.5
			}
		}
	]
}
';
		fclose ($fp);
	}
	exit(0);
}
if (!trim ($raw)) {
	//There's nothing here.
	echo "No medications to display.";
	exit(0);
}

//Parse the info and load it into the PrescriptionClass class:
$medsList = [];
$name = "???";
$dateUpdated = "???";
try {
	$parsed = json_decode ($raw, true);
	if (!$parsed) {
		exit ("Either unable to parse JSON or there's nothing there.");
	}
	
	//Pull out name and updated date
	if (isset ($parsed["Name"])) {
		$name = $parsed["Name"];
	}
	if (isset ($parsed["DateUpdated"])) {
		$dateUpdated = $parsed["DateUpdated"];
	}
	
	
	//Break each medication out and load it into PrescriptionClass.
	if (!isset ($parsed["Medications"])) {
		$parsed["Medications"] = [];
	}
	$medsToProcess = $parsed["Medications"];
	for ($a = 0; $a < count($medsToProcess); $a++) {
		$temp = new PrescriptionClass($medsToProcess[$a]);
		//to do: consider adding some error checking here, to make sure it processed properly.
		array_push ($medsList, $temp);
	}
}
catch (Exception $ex) {
	exit ("Error: unable to parse JSON.");
}


//Generates HTML for the entire list of medications as a table.
function printMedsList ($medsList) {
	$ret = "<div id='medsList'>";
	
	for ($a = 0; $a < count($medsList); $a++) {
		$html = $medsList[$a]->printMedicationRow();
		$ret .= $html;
	}
	
	$ret .= "</div>";
	
	return $ret;
}


?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Mom's Meds</title>
	
	<link rel="stylesheet" href="medications.css" />
	
	<!-- include JS file here if I ever need one -->
</head>
<body>
	<div id="headerInfo">
		<span class="patientName">Patient: <?php echo $name; ?></span>
		<span class="pageNumber"></span>
		<!-- Page numbers coming sometime. For now, you can use the auto header/footers. -->
		<br />
		<span class="lastUpdated">Last updated: <?php echo $dateUpdated; ?></span>
		<span class="datePrinted">Printed: <?php echo $datePrinted; ?></span>
	</div>
	<div id="medsListWrapper">
		<?php echo printMedsList($medsList); ?>
	</div>
</body>
</html>