<?php
/*
	This file contains a class for storing medications.

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



class PrescriptionClass {
	//PrescriptionClass: stores information about one medication the patient has been prescribed.
	
	//Information about this medication:
	
	//String
	//The generic name for the medication.
	private $genericName;
	
	//String if present, false if not.
	//The brand name for the medication.
	private $brandName;
	
	//String
	//whether this is to be called a pill, a tablet, a capsule, or something else.
	private $describeAs;
	
	//String
	//The unit of measurement for this medication. Defaults to "mg"
	private $dosageUnit;
	
	//Numeric
	//The dosage per pill.
	private $dosePerPill;
	
	//Associative Array in the format
	//[
	//	"time period" => dosage to be taken at that time
	//]
	//The main time periods are "Morn" for morning, "Bed" for bedtime / evening, and "Other" for anything else.
	private $dosage;
	
	//String
	//A field for listing miscellaneous notes about the medication. ie: "take as needed" or "take with food."
	private $notes;
	
	//String
	//The name of the doctor who prescribed the medication.
	private $prescribingDoctor;
	
	//Boolean
	//Indicates whether or not the medication is currently being taken.
	private $currentlyTaking;
	
	
	
	//Constructor
	function __construct($info = []) {
		//We might receive a JSON string instead of an array or whatever.
		if (is_string ($info)) {
			try {
				$temp = json_decode ($info);
				if ($temp) {
					$info = $temp;
				} else {
					//We couldn't parse it.
					$info = [];
					//to do: complain to a log file?
				}
			}
			catch (Exception $ex) {
				//to do: complain to a log file?
				exit("Error: could not decode JSON");
			}
		} else if (!is_array($info)) {
			//We received something in an unknown format, so we'll overwrite it with a blank list.
			//to do: complain to a log file?
			$info = [];
		}
		
		//Information about the medication.
		$this->genericName			= isset ($info["genericName"])			? $info["genericName"]			: "";
		$this->brandName			= isset ($info["brandName"])			? $info["brandName"]			: false;
		$this->describeAs			= isset ($info["describeAs"])			? $info["describeAs"]			: "pill";
		$this->dosageUnit			= isset ($info["dosageUnit"])			? $info["dosageUnit"]			: "mg";
		$this->dosePerPill			= isset ($info["dosePerPill"])			? $info["dosePerPill"]			: 0;
		
		//Other information.
		$this->notes				= isset ($info["notes"])				? $info["notes"]				: "";
		$this->prescribingDoctor	= isset ($info["prescribingDoctor"])	? $info["prescribingDoctor"]	: "";
		$this->currentlyTaking		= isset ($info["currentlyTaking"])		? $info["currentlyTaking"]		: true;
		
		//Dosage
		if (isset ($info["dosage"])) {
			//Preferred method: the object is in the same format that this uses.
			$this->dosage = $info["dosage"];
		} else {
			//It's not here, so use the default.
			$this->dosage = [];
		}
		//to do: check these to make sure they look right.
			//Right now it's blindly assuming that the timing info is in the right format.
	}
	
	
	
	//Public Functions
	//-------- -------- -------- -------- -------- -------- -------- -------- 
	
	//Returns the name information as a string.
	//If there's no brand name, simply returns the generic name.
	//If there is a brand name, returns "$brandName ($genericName)"
	public function getCombinedName() {
		if ($this->brandName) {
			return $this->brandName . " (" . $this->genericName . ")";
		} else {
			return $this->genericName;
		}
	}
	
	//Prints this medication as a div.
	public function printMedicationRow() {
		$ret = "<div class='medicationRow";
		if ($this->currentlyTaking) {
			$ret .= " currentlyTaking";
		} else {
			$ret .= " notCurrentlyTaking";
		}
		$ret .= "'>";
		
		//Name row.
		$ret .= "<div class='medicationNameRow'>" . $this->getCombinedName() . "</div>";
		
		//Dosage information.
		$dosageMorn = $this->getTextForNumberOfPills("Morn");
		$dosageBed = $this->getTextForNumberOfPills("Bed");
		$dosageOther = "";
		foreach ($this->dosage as $timing => $dosage) {
			if (strtolower($timing) == "morn") {
				continue;
			} else if (strtolower($timing) == "bed") {
				continue;
			}
			
			$temp = $this->getTextForNumberOfPills($timing);
			$dosageOther .= $temp;
		}
		
		$ret .=
			"\r\n".
			"<div class='medicationDosageRow flexRow'>".
				"<div class='medicationDosageItem timingMorning'>Morning<br />".$dosageMorn."</div>".
				"<div class='medicationDosageItem timingOther'>Other<br />".$dosageOther."</div>".
				"<div class='medicationDosageItem timingBed'>Bedtime<br />".$dosageBed."</div>".
			"</div>\r\n";
		
		//Area for notes, including blank area for handwriting additional notes.
		$ret .= "<div class='medicationNotesRow'>". $this->notes ."</div>";
		$ret .= "<div class='medicationBlankRow'></div>";
		
		//Which doctor prescribed this medication.
		$ret .= "<div class='medicationDoctorRow'>Prescribed By: " . $this->prescribingDoctor . "</div>";
		
		$ret .= "</div>\r\n";
		
		return $ret;
	}
	
	
	
	//Private / Internal Functions
	//-------- -------- -------- -------- -------- -------- -------- -------- 
	
	//Returns all the info needed to deal with the medication for a particular time of day.
	private function getTimeInfo($time) {
		$dosePerPill = 0;
		$doseNow = 0;
		$description = "";
		$dosageUnit = "mg";
		
		$timeLower = strtolower($time);
		
		//Collect basic information.
		$dosePerPill = $this->dosePerPill;
		$dosageUnit = $this->dosageUnit;
		
		//Collect information about this time period.
		if ($timeLower == "morn" || $timeLower == "morning") {
			if (isset ($this->dosage["Morn"])) {
				$doseNow = $this->dosage["Morn"];
			} else {
				$doseNow = 0;
			}
		} else if ($timeLower == "bed" || $timeLower == "bedtime" || $timeLower == "evening") {
			if (isset ($this->dosage["Bed"])) {
				$doseNow = $this->dosage["Bed"];
			} else {
				$doseNow = 0;
			}
		} else {
			//This seems to be something for the Other column.
			if (isset ($this->dosage[$time])) {
				$doseNow = $this->dosage[$time];
			} else {
				$doseNow = 0;
			}
		}
		
		return [
			"dosePerPill" => $dosePerPill,
			"dosageUnit" => $dosageUnit,
			"doseNow" => $doseNow
		];
	}
	
	//Generates the text for listing how many pills will be required.
	//Generally lists it in the format "X pills (Ymg)"
	//Returns a Human-readable error message in the event of an error.
	private function getTextForNumberOfPills ($time) {
		$info = $this->getTimeInfo($time);
		$unit = $info["dosageUnit"];
		$dosage = $info["doseNow"];
		
		$numPills = false;
		if ($info["dosePerPill"]) {
			$numPills = $info["doseNow"] / $info["dosePerPill"];
		} else {
			if ($info["doseNow"]) {
				//We're only missing the dosage that's in each pill, so we just list the dosage in mg (or whatever) for this time.
				//Leave $numPills false.
			} else {
				//We don't have the dosage to take or the dosage per pill, just leave this spot blank.
				return "";
			}
		}
		
		//Early exit: if there's nothing to take at this time, leave the space blank.
		if ($numPills <= 0) {
			return "";
		}
		
		$ret = "";
		//If this is for a time other than morning or bedtime, we need to display the timing.
		if (strtolower($time) != "morn" && strtolower($time) != "bed") {
			//This is something for the "Other" column, so we need to display the timing.
			$ret .= $time.":<br />";
		}
		
		if ($numPills) {
			$ret .= $numPills." ";
			
			if ($numPills == 1) {
				$ret .= $this->describeAs;
			} else if ($numPills != 1) {
				//Special cases for some pill/whatever types.
				if ($this->describeAs == "gummy") {
					$ret .= "gummies";
				} else {
					//For pill / tablet / etc - we can just slap an S on the end and be fine.
					$ret .= $this->describeAs."s";
				}
			}
			
			$ret .= "<br />(".$dosage." ".$unit.")";
		} else {
			//We don't know how much is in each pill, so we'll leave out the 'number of pills' calculation and just list Xmg or whatever).
			$ret .= $dosage." ".$unit;
		}
		
		return $ret;
	}
}

?>