<?
class IPSownCloud extends IPSModule{

	public function Create(){
		parent::Create();
		$this->RegisterPropertyString("URL", "");
        $this->RegisterPropertyInteger("KalenderID", 0);
        $this->RegisterPropertyString("Username", "");
        $this->RegisterPropertyString("Password", "");
        $this->RegisterPropertyString("Feiertage", "--");
        $this->RegisterPropertyInteger("MaxDays", 31);
		$this->RegisterPropertyBoolean("visualoldtimes", true);
		$this->RegisterPropertyBoolean("visualreminder", true);
		$this->RegisterPropertyInteger("EmailID", 0);
		$this->RegisterPropertyString("Style11", "normal");
		$this->RegisterPropertyString("Style12", "normal");
		$this->RegisterPropertyString("Style13", "normal");
		$this->RegisterPropertyString("Style14", "normal");
		$this->RegisterPropertyBoolean("debug", false);
	}

	public function ApplyChanges(){
		//Never delete this line!
		parent::ApplyChanges();

		$url  =  $this->ReadPropertyString('URL');
		$kid  =  $this->ReadPropertyString('KalenderID');
		$user =  $this->ReadPropertyString('Username');
		$pass =  $this->ReadPropertyString('Password');
		$maxdays =  $this->ReadPropertyInteger('MaxDays');

        // Url prüfen
		if ($url == ''){
            // Status inaktiv
            $this->SetStatus(201);
        } else{
			// KalenderID prüfen
			if ($kid == ''){
				// Status inaktiv
				$this->SetStatus(202);
			} else{
				// Bentuzer prüfen
				if ($user == ''){
					// Status inaktiv
					$this->SetStatus(203);
				} else{
					// Password prüfen
					if ($pass == ''){
						// Status inaktiv
						$this->SetStatus(204);
					} else{
						// Anzahl Tage prüfen
						if ($maxdays == ''){
							// Status inaktiv
							$this->SetStatus(205);
						} else{
							$this->SetStatus(102);
						}
					}
				}
			}
		}

		// Variablen anlegen
		$this->RegisterVariableString("Heute",            "Heute",            "", 10);
		$this->RegisterVariableString("Morgen",           "Morgen",           "", 20);
		$this->RegisterVariableString("Uebermorgen",      "Übermorgen",      "", 30);
		$this->RegisterVariableString("Ueberuebermorgen", "Überübermorgen", "", 40);
		$this->RegisterVariableString("HeuteMorgen",      "Heute & Morgen",   "", 50);
		$this->RegisterVariableString("NaechsteTermine",  "Nächste Termine", "", 60);
		$this->RegisterVariableString("Kalender",         "Kalender",         "~HTMLBox", 100);


		// 1 Minuten Timer
        	$this->RegisterTimer("Timer", 60*1000, 'OWN_Update($_IPS[\'TARGET\']);');
		// Nach übernahme der Einstellungen oder IPS-Neustart einmal Update durchführen.
		$this->Update();
	}

    private function SetValueString($Ident, $value){
        $id = $this->GetIDForIdent($Ident);
        if (GetValueString($id) <> $value)
            SetValueString($id, $value);
    }


    public function Update(){

		$StyleText[0]  = $this->ReadPropertyBoolean('visualreminder');              // (true/false) Anzeige der Erinnerung Ein/Aus
		
		/******** Farbnamen, RGB-Formatierung, Hex-Zahlen müglich *********/
		$StyleText[1] = "lightgray"; 			// Textfarbe Datum
		$StyleText[2] = "gray"; 				// Textfarbe Wochentag
		$StyleText[3] = "lightblue";  			// Textfarbe Termin sonstige
		$StyleText[4] = "red"; 					// Textfarbe Termin Heute
		$StyleText[5] = "rgba(31,50,79,0)";    	// Texthintergrung Heute default: rgba(31,50,79,0)
		$StyleText[6] = "lightgreen";			// Textfarbe Datum wenn Feiertag

		$StyleText[7] = "#213245";    			// Verlaufsfarbe rechts
		$StyleText[8] = "100%";       			// übergang rechts
		$StyleText[9] = "rgba(31,50,79,0)"; 	// Verlaufsfarbe links
		$StyleText[10] = "40%";        			// üergang links

		/*** xx-small, x-small, small, normal, large, x-large, xx-large ***/
		/************** oder Angabe in pt - z.B. "12pt" *******************/
		$StyleText[11] = $this->ReadPropertyString('Style11'); 				// Textgrösse Datum
		$StyleText[12] = $this->ReadPropertyString('Style12'); 				// Textgrösse Wochentag
		$StyleText[13] = $this->ReadPropertyString('Style13'); 				// Textgrösse Eintrag
		$StyleText[14] = $this->ReadPropertyString('Style14'); 			// Textgrösse Heutiger Eintrag  AB V1.07

		$StyleText[20] = "font-family: Arial;"; // Style der Tabelle Änderbar AB V1.08
		
		$StyleText[30]  = $this->ReadPropertyBoolean('visualoldtimes');              // Sollen abgelaufene Termine von Heute angezeigt werden false = Termine nach Endzeit, werden nicht mehr angezeigt AB V1.07

		$url     =  $this->ReadPropertyString('URL');
		$kid     =  $this->ReadPropertyString('KalenderID');
		$user    =  $this->ReadPropertyString('Username');
		$pass 	 =  $this->ReadPropertyString('Password');
		$maxdays =  $this->ReadPropertyInteger('MaxDays');
		$bland 	 =  $this->ReadPropertyString('Feiertage');
		$debug   =  $this->ReadPropertyBoolean('debug');

		$calcData = $this->ReadCalendar($url, $kid, $user, $pass, $debug, $maxdays, $bland);
		if ($calcData != false)
		{
			$this->erzeugeKalender($calcData, $StyleText, $bland, $debug);
		}

	}

/*****************************************************************/
//
// Liest den kompletten Kalender
// Sortierung in eine Variable
// Auswertung jeder einzelnen Eintrages
// Erzeugung der Wiederholungen in Unterroutine
//
/*****************************************************************/
	private function ReadCalendar($url, $id, $username, $password, $debug, $maxdays, $bland){

		$calcData= array('');

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url."/index.php/apps/calendar/export.php?calid=".$id);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,20);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_USERPWD, $username.':'.$password);
		$result = curl_exec ($ch);
		curl_close($ch);
                if ($result === false)
                    return false;
		if(substr($result,0,15) == "BEGIN:VCALENDAR"){
			$kalender_arr_komplett = explode("BEGIN:VEVENT", $result);
			$insert = 0;
			foreach($kalender_arr_komplett as $key => $value){
				$reminderCount = 0;
				if (($value <> "") && ($key > 0)){ // Leere Einträge filtern						
					$sresult = explode("\r\n", $value);
					$startTime = '';
					$endTime = '';
					$reminderTime = -1;
					$rruleFreq = '';
					$rruleCount = '';
					$rruleEnd = '';
					$rruleEndtxt = '';
					$rruleDays = '';
					$rruleMonth = '';
					$rruleMonthDay = '';
					$rruleIntervall = '';
					$alarmData = false;
					$rettemp = array('');
					$thisData = '';
					$thisData['Bezeichnung'] = '';
					$thisData['Beschreibung'] = '';
					$thisData['Ort'] = "";
					$thisData['UserEvent'] = "";
					$thisData['Datum'] = '';
					$thisData['ZeitTxt'] = '';
					$thisData['DatumTxt'] = '';
					$thisData['EndDatum'] = '';
					$thisData['EndZeitTxt'] = '';
					$thisData['EndDatumTxt'] = '';
					$thisData['Wiederholungen'] = '';
					$thisData['RRuleFreq'] = '';
					$thisData['RRuleInterval'] = '';
					$thisData['RRuleEnd'] = '';
					$thisData['RRuleEndTxt'] = '';
					$thisData['RRuleCount'] = '';
					$thisData['RRuleDays'] = '';
					$thisData['RRuleMonth'] = '';
					$thisData['RRuleMonthDay'] = '';
					$thisData['ReminderTime'] = array('');
					$thisData['ReminderTimeTxt'] = array('');
					$thisData['ReminderDateTxt'] = array('');
					$thisData['ReminderTrigger'] = array('');

					if ($debug) IPS_LogMessage("ownCloud-Modul", "T:$key\n");
					foreach($sresult as $svalue){
						if ($svalue <> ""){
							if ($svalue == "BEGIN:VALARM") $alarmData = true;
							if ($svalue == "END:VALARM") $alarmData = false;
							$xvalue = explode(':',$svalue);

							if ($xvalue[0] == "SUMMARY"){
								$title = "";
								for($i = 1; $i < (count($xvalue) ); $i++){
									if ($i > 1) $title .= ":";
									$title.= $xvalue[$i];
								}
								$thisData['Bezeichnung'] = iconv('UTF-8','ISO-8859-15', $title);
							}
							
							if ($xvalue[0] == "LOCATION"){
								$thisData['Ort'] = $xvalue[1];
							}

							// Start Datum/Zeit filtern
							if (substr($xvalue[0],0,7) == "DTSTART"){
								if (strlen($xvalue[1]) == 8){
									$startyear = substr($xvalue[1],0,4);
									$startmonth = substr($xvalue[1],4,2);
									$startday = substr($xvalue[1],6,2);
									$startTime = strtotime( "$startday.$startmonth.$startyear 00:00" );
								}
								else{
									$startyear = substr($xvalue[1],0,4);
									$startmonth = substr($xvalue[1],4,2);
									$startday = substr($xvalue[1],6,2);
									$starthour = substr($xvalue[1],9,2);
									$startminute = substr($xvalue[1],11,2);
									$startTime = strtotime( "$startday.$startmonth.$startyear $starthour:$startminute" );
								}
							}

							// Ende Datum/Zeit filtern
							if (substr($xvalue[0],0,5) == "DTEND"){
								if (strlen($xvalue[1]) == 8){
									$endyear = substr($xvalue[1],0,4);
									$endmonth = substr($xvalue[1],4,2);
									$endday = substr($xvalue[1],6,2);
									$endTime = strtotime( "$endday.$endmonth.$endyear 00:00" );
								}
								else{
									$endyear = substr($xvalue[1],0,4);
									$endmonth = substr($xvalue[1],4,2);
									$endday = substr($xvalue[1],6,2);
									$endhour = substr($xvalue[1],9,2);
									$endminute = substr($xvalue[1],11,2);
									$endTime = strtotime( "$endday.$endmonth.$endyear $endhour:$endminute" );
								}
							}

							if (substr($xvalue[0],0,5) == "RRULE"){
								$rrule = explode(';',$xvalue[1]);
								foreach($rrule as $xrule){
									$srule = explode('=',$xrule);
									if ($xrule == "FREQ=DAILY")    $rruleFreq = "täglich";
									if ($xrule == "FREQ=WEEKLY")   $rruleFreq = "wöchentlich";
									if ($xrule == "FREQ=MONTHLY")  $rruleFreq = "monatlich";
									if ($xrule == "FREQ=YEARLY")   $rruleFreq = "jährlich";

									if ($srule[0] == "COUNT")      $rruleCount = $srule[1];
									if ($srule[0] == "BYDAY")      $rruleDays = $srule[1];
									if ($srule[0] == "BYMONTH")    $rruleMonth = $srule[1];
									if ($srule[0] == "BYMONTHDAY") $rruleMonthDay = $srule[1];
									if ($srule[0] == "INTERVAL")   $thisData['RRuleInterval'] = $srule[1];
									if ($srule[0] == "UNTIL"){
										$ryear = substr($xrule,6,4);
										$rmonth = substr($xrule,10,2);
										$rday = substr($xrule,12,2);
										$rhour = substr($xrule,15,2);
										$rminute = substr($xrule,17,2);
										$rruleEnd  = strtotime( "$rday.$rmonth.$ryear" );
										$rruleEndtxt = "$rday.$rmonth.$ryear";
									}
								}
							}

							if ((substr($xvalue[0],0,11) == "DESCRIPTION") && ($alarmData == false)){
								$thisData['Beschreibung'] = iconv('UTF-8','ISO-8859-15',$xvalue[1]);
							}

							if (substr($xvalue[0],0,8) == "LOCATION"){
								$thisData['Ort'] = iconv('UTF-8','ISO-8859-15',$xvalue[1]);
							}

							if (substr($xvalue[0],0,10) == "CATEGORIES"){
								$thisData['Kategorie'] = $xvalue[1];
							}

							if ((substr($xvalue[0],0,7) == "TRIGGER") ){
								$thisData['ReminderTrigger'][$reminderCount] = substr($xvalue[0],14).":".$xvalue[1];
								$reminderCount++;
							}
						}
					}

					$thisData['Datum'] = $startTime;
					$thisData['ZeitTxt'] = date("H:i", $startTime);
					$thisData['DatumTxt'] = date("d.m.Y", $startTime);

					if ($endTime>0){
						$thisData['EndDatum'] = $endTime;
						$thisData['EndZeitTxt'] = date("H:i", $endTime);
						$thisData['EndDatumTxt'] = date("d.m.Y", $endTime);
						if($thisData['ZeitTxt'] == "00:00" && $thisData['EndZeitTxt'] == "00:00"){
							$thisData['EndDatum'] = $endTime - 86400;
							$thisData['EndDatumTxt'] = date("d.m.Y", strtotime($thisData['EndDatumTxt']."-1 day"));
						}
					}
					else{
						$thisData['EndDatum'] = '';
						$thisData['EndZeitTxt'] = '';
						$thisData['EndDatumTxt'] = '';
					}
					$thisData['RRuleFreq'] = $rruleFreq;
					$thisData['RRuleEnd'] = $rruleEnd;
					$thisData['RRuleEndTxt'] = $rruleEndtxt;
					$thisData['RRuleCount'] = $rruleCount;
					$thisData['RRuleDays'] = $rruleDays;
					$thisData['RRuleMonth'] = $rruleMonth;
					$thisData['RRuleMonthDay'] = $rruleMonthDay;
					
					$rettemp = $this->CheckWiederholungen($thisData, $maxdays);
					if (count($rettemp) >= 2){
						$calcData[$insert] = $rettemp;
						$rettemp= "";
						$insert++;
					}						
				}
			}
			return $calcData;
		}
		else{
			if ($debug == true) IPS_LogMessage("ownCloud-Modul", "Keine Sinnvollen Daten von ownCloud erhalten\n\n".$url."/index.php/apps/calendar/export.php?calid=".$id."\n".$result."\n");
                        return false;
		}
	}

/*****************************************************************/
//
// Prüft einen einzelnen Termin auf Wiederholungen
// Wiederholt den Termin sooft bis der
// Terminstart ins jetzige Zeitfenster passt
//
// Erinnerungszeitpunkte werden entsprechend mit geführt
//
/*****************************************************************/
   private function CheckWiederholungen($Data, $maxdays)
   {
		$return = array('');
        // Termin mit Enddatum begrenzte Wiederholungen
		if ($Data['RRuleFreq'] <> '' && $Data['RRuleEnd'] <> '' && $Data['RRuleCount'] == ''){
			$day = $Data['Datum'];
			$interval = $Data['RRuleInterval'];
			if($interval == "") $interval = 1;
			$rend = strtotime($Data['RRuleEndTxt']);
			while ($rend >= $day){
				if (($Data['RRuleDays'] == '') || (strpos($Data['RRuleDays'],$this->formatTag(date("D", $day))) !== false )){
			        $Data['DatumTxt'] = date("d.m.Y", $day);
			        $Data['Datum'] = strtotime($Data['DatumTxt']." ".$Data['ZeitTxt']);
					$Data['EndDatum'] = strtotime(date("d.m.Y", $day)." ".$Data['EndZeitTxt']);
					$Data['EndDatumTxt'] = date("d.m.Y", $day);
					if (( $day >= strtotime(date("d.m.Y",time()))) && ( $Data['Datum'] <= strtotime("+$maxdays day") )){
						$remindercount = 0;
						foreach($Data['ReminderTrigger'] as $no => $trigger){
							$reminder = $this->get_Reminder( $Data['Datum'] , $trigger );
							if($reminder > 0){
								$Data['ReminderTime'][$remindercount] = $reminder;
								$Data['ReminderTimeTxt'][$remindercount] = date("H:i", $reminder);
								$Data['ReminderDateTxt'][$remindercount] = date("d.m.Y H:i", $reminder);
								$remindercount++;
							}
						}
						$return = $Data;
					}
				}
				if ($Data['RRuleFreq'] == 'täglich') $day = strtotime("+".$interval." day",$day);
				if ($Data['RRuleFreq'] == 'wöchentlich') $day = strtotime("+".($interval * 7)." day",$day);
				if ($Data['RRuleFreq'] == 'monatlich') $day = strtotime("+".$interval." month",$day);
				if ($Data['RRuleFreq'] == 'jährlich')  $day = strtotime("+".$interval." year",$day);
			}
		}

		// Termin mit Anzahl begrenzte Wiederholungen
		elseif ($Data['RRuleFreq'] <> '' && $Data['RRuleEnd'] == '' && $Data['RRuleCount'] <> ''){
			$day = $Data['Datum'];
			$interval = $Data['RRuleInterval'];
			if($interval == "") $interval = 1;
			$count = 0;
			while ($count < $Data['RRuleCount']){
				if (($Data['RRuleDays'] == '') || (strpos($Data['RRuleDays'],$this->formatTag(date("D", $day))) !== false ))
				{
					$Data['DatumTxt'] = date("d.m.Y", $day);
					$Data['Datum'] = strtotime($Data['DatumTxt']." ".$Data['ZeitTxt']);
					$Data['EndDatum'] = strtotime(date("d.m.Y", $day)." ".$Data['EndZeitTxt']);
					$Data['EndDatumTxt'] = date("d.m.Y", $day);
					if (( $day >= strtotime(date("d.m.Y",time()))) && ( $Data['Datum'] <= strtotime("+$maxdays day"))){
						$remindercount = 0;
						foreach($Data['ReminderTrigger'] as $no => $trigger){
							$reminder = $this->get_Reminder( $Data['Datum'] , $trigger );
							if($reminder > 0)
							{
								$Data['ReminderTime'][$remindercount] = $reminder;
								$Data['ReminderTimeTxt'][$remindercount] = date("H:i", $reminder);
								$Data['ReminderDateTxt'][$remindercount] = date("d.m.Y H:i", $reminder);
								$remindercount++;
							}
						}
						$return = $Data;
					}
				}
				if ($Data['RRuleFreq'] == 'täglich') $day = strtotime("+".$interval." day",$day);
				if ($Data['RRuleFreq'] == 'wöchentlich') $day = strtotime("+".($interval * 7)." day",$day);
				if ($Data['RRuleFreq'] == 'monatlich') $day = strtotime("+".$interval." month",$day);
				if ($Data['RRuleFreq'] == 'jährlich') $day = strtotime("+".$interval." year",$day);
				$count++;
			}
		}

		// Termin mit zeitlich unbegrenzte Wiederholungen
		elseif  ($Data['RRuleFreq'] <> '' && $Data['RRuleEnd'] == '' && $Data['RRuleCount'] == ''){
			$jahre = 0;
			$day = $Data['Datum'];
			$interval = $Data['RRuleInterval'];
			if($interval == "") $interval = 1;
			do{
				if (($Data['RRuleDays'] == '') || (strpos($Data['RRuleDays'],$this->formatTag(date("D", $day))) !== false )){
					$Data['DatumTxt'] = date("d.m.Y", $day);
					$Data['Datum'] = strtotime($Data['DatumTxt']." ".$Data['ZeitTxt']);
					$Data['EndDatum'] = strtotime(date("d.m.Y", $day)." ".$Data['EndZeitTxt']);
					$Data['EndDatumTxt'] = date("d.m.Y", $day);

					//$Data['ReminderTime'][0] = strtotime($Data['DatumTxt']." ".$Data['ReminderTimeTxt'][0]);
					if (( $day >= strtotime(date("d.m.Y",time()))) && ( $day <= strtotime("+$maxdays day") )){
						// Termin Array erzeugen
						$remindercount = 0;
						foreach($Data['ReminderTrigger'] as $no => $trigger){
							$reminder = $this->get_Reminder( $Data['Datum'] , $trigger );
							if($reminder > 0){
								$Data['ReminderTime'][$remindercount] = $reminder;
								$Data['ReminderTimeTxt'][$remindercount] = date("H:i", $reminder);
								$Data['ReminderDateTxt'][$remindercount] = date("d.m.Y H:i", $reminder);
								$remindercount++;
							}
						}
						$return = $Data;
					}
				}
				if ($Data['RRuleFreq'] == 'täglich') $day = strtotime("+".$interval." day",$day);
				if ($Data['RRuleFreq'] == 'wöchentlich') $day = strtotime("+".($interval * 7)." day",$day);
				if ($Data['RRuleFreq'] == 'monatlich') $day = strtotime("+".$interval." month",$day);
				if ($Data['RRuleFreq'] == 'jährlich') { $day = strtotime("+".$interval." year",$day); $jahre++; $Data['Wiederholungen'] = $jahre;}
			} while ( ( $day <= strtotime(date("d.m.Y",time()))) || ( $day <= strtotime("+$maxdays day")) );
		}

		// Termin ohne Wiederholungen
		else{
			if (( $Data['Datum'] >= strtotime(date("d.m.Y"))) && ( $Data['Datum'] <= strtotime("+$maxdays day"))){
				// Termin Array erzeugen
				$remindercount = 0;
				foreach($Data['ReminderTrigger'] as $no => $trigger){
					$reminder = $this->get_Reminder( $Data['Datum'] , $trigger );
					if($reminder > 0){
						$Data['ReminderTime'][$remindercount] = $reminder;
						$Data['ReminderTimeTxt'][$remindercount] = date("H:i", $reminder);
						$Data['ReminderDateTxt'][$remindercount] = date("d.m.Y H:i", $reminder);
						$remindercount++;
					}
				}
				$return = $Data;
			}
		}
		return $return;
	}

/*****************************************************************/
//
// Prüft einen einzelnen Termin auf Wiederholungen
// Wiederholt den Termin sooft bis der
// Terminstart ins jetzige Zeitfenster passt
//
// Erinnerungszeitpunkte werden entsprechend mit geführt
//
/*****************************************************************/
	private function erzeugeKalender($calcData, $StyleText, $bland){
		
		// Wochentage in Deutsch
		$tag = array();
		$tag[0] = "Sonntag";
		$tag[1] = "Montag";
		$tag[2] = "Dienstag";
		$tag[3] = "Mittwoch";
		$tag[4] = "Donnerstag";
		$tag[5] = "Freitag";
		$tag[6] = "Samstag";

		$heute = "";
		$morgen = "";
		$uemorgen = "";
		$ueuemorgen = "";
		$heuteumorgen = "";
		$next = "";
		$calDataTxt = "";
		$emailID =  $this->ReadPropertyInteger('EmailID');
		
		if (count($calcData) > 0){
			usort($calcData, array($this,'DateCompare'));
		
			// Starte Tabellenansicht
			$calDataTxt = "<table style='border-spacing:0px; width:100%; ".$StyleText[20]."'>"
						."\n\t<tr>"
						."\n\t\t<td>"
						."\n\t\t</td>"
						."\n\t\t<td style='text-align:right; font-size:xx-small;'>ownCloud Modul V 1.02"
						."\n\t\t</td>"
						."\n\t</tr>";
			$check_date = "";
			$debugCount = 0;
			foreach($calcData as $thisData){
				
				// Email Versand bei Erinnerungszeit
				foreach($thisData['ReminderDateTxt'] as $no => $reminderZeit){
					if( ($emailID > 0) &&  ($reminderZeit <> "") ){
						if  ($reminderZeit == date("d.m.Y H:i", time())){
							SMTP_SendMail($emailID, "Termin Erinnerung für ".$thisData['Bezeichnung'], "       Datum: ".$thisData['DatumTxt']."\n\r     Uhrzeit: ".$thisData['ZeitTxt']."\n\r Bezeichnung: ".$thisData['Bezeichnung']."\n\rBeschreibung: ".$thisData['Beschreibung']."\n\r");
						}
					}
				}

				// UserEvent ausführen
				if  ( ($thisData['DatumTxt']." ".$thisData['ZeitTxt'] == date("d.m.Y H:i", time())) && ($thisData['UserEvent'] <> "") ){
//						UserEvent($thisData['UserEvent']);
				}

				// Variable Heute füllen
				if( ( $thisData['DatumTxt'] == date("d.m.Y", time())  && $StyleText[30] == true ) ||
					(	$thisData['EndDatum'] >= strtotime(date("d.m.Y H:i", time())) && $thisData['EndDatum'] < strtotime(date("d.m.Y 23:59:59", time()))&& $StyleText[30] == false )){
						if ($heute == ""){
							$heute = $thisData['ZeitTxt']." ".$thisData['Bezeichnung'];
						}else{
							$heute = $heute.chr(13).chr(10).$thisData['ZeitTxt']." ".$thisData['Bezeichnung'];
						}
				}

				// Variable Morgen füllen
				if($thisData['DatumTxt'] == date("d.m.Y", time() + (24 * 60 * 60))){
					if ($morgen == ""){
						$morgen = $thisData['ZeitTxt']." ".$thisData['Bezeichnung'];
					}else{
						$morgen = $morgen.chr(13).chr(10).$thisData['ZeitTxt']." ".$thisData['Bezeichnung'];
					}
				}

				// Variable übermorgen füllen
				if($thisData['DatumTxt'] == date("d.m.Y", time()+(2 * 24 * 60 * 60))){
					if ($uemorgen == ""){
						$uemorgen = $thisData['ZeitTxt']." ".$thisData['Bezeichnung'];
					}else{
						$uemorgen = $uemorgen.chr(13).chr(10).$thisData['ZeitTxt']." ".$thisData['Bezeichnung'];
					}
				}

				// Variable überübermorgen füllen
				if($thisData['DatumTxt'] == date("d.m.Y", time()+(3 * 24 * 60 * 60))){
					if ($ueuemorgen == ""){
						$ueuemorgen = $thisData['ZeitTxt']." ".$thisData['Bezeichnung'];
					}else{
						$ueuemorgen = $ueuemorgen.chr(13).chr(10).$thisData['ZeitTxt']." ".$thisData['Bezeichnung'];
					}
				}

				// Variable Next füllen
				if(strtotime($thisData['DatumTxt']) >= strtotime(date("d.m.Y", time() + (2*24 * 60 * 60)))){
					if ($next == ""){
						$next = $thisData['Bezeichnung'];
					}else{
						$next = $next.chr(13).chr(10).$thisData['Bezeichnung'];
					}
				}

				// Variable "Heute & Morgen" füllen
				if ($heute == "") { $heuteumorgen = $morgen; } else { $heuteumorgen = $heute.chr(13).chr(10).$morgen; }

				// Variable Kalender füllen
				if(((strtotime($thisData['EndDatumTxt']) >= strtotime(date("d.m.Y", time()))) && $StyleText[30] == true ) ||
					($thisData['EndDatum'] >= strtotime(date("d.m.Y H:i", time())) && $StyleText[30] == false )){ //date("d.m.Y", strtotime("yesterday")))
					if($check_date != "" and $thisData['DatumTxt'] != $check_date)$calDataTxt .= "\n\t\t\t</table>\n\t\t</th>\n\t</tr>";
					if($thisData['DatumTxt'] != $check_date){
						if     ($thisData['DatumTxt'] == date("d.m.Y")) $headerTxt = "Heute:";
						elseif ($thisData['DatumTxt'] == date("d.m.Y", strtotime("+1 day"))) $headerTxt = "Morgen:";
						else    $headerTxt = $thisData['DatumTxt']." in ".$this->seDay($thisData['DatumTxt'],date("d.m.Y"),"dmY",".")." Tagen";
						$calDataTxt  .= "\n"
									."\n\t<tr>\n\t\t<td style=' padding:4px;"
									."\n\t\t\t\t\tbackground-color:".$StyleText[7].";"
									."\n\t\t\t\t\tbackground: -moz-linear-gradient(left, ".$StyleText[9]." ".$StyleText[10].", ".$StyleText[7]." ".$StyleText[8].");"
									."\n\t\t\t\t\tbackground: -webkit-gradient(linear, left top, right top, color-stop(4%,".$StyleText[9]."), color-stop(".$StyleText[8].",".$StyleText[7]."));"
									."\n\t\t\t\t\tbackground: -webkit-linear-gradient(left, ".$StyleText[9]." ".$StyleText[10].", ".$StyleText[7]." ".$StyleText[8].");"
									."\n\t\t\t\t\tbackground: -o-linear-gradient(left, ".$StyleText[9]." ".$StyleText[10].", ".$StyleText[7]." ".$StyleText[8].");"
									."\n\t\t\t\t\tbackground: -ms-linear-gradient(left, ".$StyleText[9]." ".$StyleText[10].", ".$StyleText[7]." ".$StyleText[8].");"
									."\n\t\t\t\t\tbackground: linear-gradient(to right, ".$StyleText[9]." ".$StyleText[10]."), ".$StyleText[7]." ".$StyleText[8].";'>";

						$feiertag = $this->get_Feiertag(strtotime($thisData['DatumTxt']), $bland);
						if ($feiertag <> ""){
							$calDataTxt  .= "\n\t\t\t\t<span style='color:".$StyleText[6].";font-weight:200;font-size:".$StyleText[11]."'>".$headerTxt." ( $feiertag )";
						}else{
							$calDataTxt  .= "\n\t\t\t\t<span style='color:".$StyleText[1].";font-weight:200;font-size:".$StyleText[11]."'>".$headerTxt;
						}

						$calDataTxt .= "\n\t\t\t\t</span>\n\t\t</td>"
									."\n\t\t<td style=' text-align:right; width:100px; padding:4px;background-color:".$StyleText[7]."'>"
									."\n\t\t\t\t<span style='color:".$StyleText[2].";font-weight:normal;font-size:".$StyleText[12]."'>".$tag[date("w", strtotime($thisData['DatumTxt']))]
									."\n\t\t\t\t</span>"
									."\n\t\t</td>"
									."\n\t</tr>"
									."\n\t<tr>"
									."\n\t\t<th colspan='2' style='text-align:left; padding-left:20px; padding-right:0px; padding-bottom:10px; padding-top:0px;'>";
						
						if($thisData['DatumTxt'] == date("d.m.Y")){
							$calDataTxt  .= "\n\t\t\t<table style='border-spacing:0px; width:100%; padding:5px; border:1px solid #1f3247; background-color: ".$StyleText[5]."; '>";
						}else{
							$calDataTxt  .= "\n\t\t\t<table style='border-spacing:0px; width:100%; padding:5px; border:1px solid #1f3247; background-color: ".$StyleText[9]."; '>";
						}
						$check_date = $thisData['DatumTxt'];
					}
					$calDataTxt .= $this->SetEintrag($thisData, $StyleText, $tag);
				}
			}
			
			$calDataTxt .= "\n\t\t</table>"
						."\n\t\t</th>"
						."\n\t</tr>"
						."\n</table>"; // Tabelle schlieüen

			$this->SetValueString("Heute", $heute);
			$this->SetValueString("Morgen", $morgen);
			$this->SetValueString("Uebermorgen", $uemorgen);
			$this->SetValueString("Ueberuebermorgen", $ueuemorgen);
			$this->SetValueString("HeuteMorgen", $heuteumorgen);
			$this->SetValueString("NaechsteTermine", $next);
			$this->SetValueString("Kalender", $calDataTxt);

		}
	}

/*****************************************************************/
//
// Erstellt Einzelnen Eintrag im Kalender
// Getrennt für Heute und restlichen Termine
//
//
/*****************************************************************/
	private function SetEintrag($thisData, $StyleText, $tag){


		if($thisData['ZeitTxt'] == "00:00"){
	        if($thisData['DatumTxt'] == $thisData['EndDatumTxt']) $thisData['ZeitTxt']="Ganzt&aumlgig";
	        else $thisData['ZeitTxt']="bis ".substr($tag[date("w", strtotime($thisData['EndDatumTxt']))],0,2).", ".$thisData['EndDatumTxt'];
		}

		$remind = "";
		if ($StyleText[0]){
			foreach($thisData['ReminderTimeTxt'] as $no => $Datum){
				if (($Datum <> "") && ($no == 0)) $remind .= "(".$Datum;
				if (($Datum <> "") && ($no  > 0)) $remind .= ", ".$Datum;
				if (($Datum <> "") && ($no == count($thisData['ReminderTimeTxt'])-1) ) $remind .= ")  ";
			}
		}

		$jahre = "";
		if ($thisData['Wiederholungen'] > 0) $jahre = " (".$thisData['Wiederholungen']."J)";
		
		if($thisData['DatumTxt'] == date("d.m.Y"))
		{
	        return "\n\t\t\t\t<tr>"
					."\n\t\t\t\t\t<td>"
					."\n\t\t\t\t\t\t<span style='font-weight:normal;font-size:".$StyleText[14]."; color:".$StyleText[4]."'>".$thisData['Bezeichnung'].$jahre
					."\n\t\t\t\t\t\t</span>"
					."\n\t\t\t\t\t</td>"
					."\n\t\t\t\t\t<td style='text-align:right;'>"
					."\n\t\t\t\t\t\t<span style='font-weight:normal;font-size:".$StyleText[14]."; color:".$StyleText[4]."'>".$remind.$thisData['ZeitTxt']
					."\n\t\t\t\t\t\t</span>"
					."\n\t\t\t\t\t</td>"
					."\n\t\t\t\t</tr>";
	    }
	    else{
	        return "\n\t\t\t\t<tr>"
					."\n\t\t\t\t\t<td>"
					."\n\t\t\t\t\t\t<span style='font-weight:normal;font-size:".$StyleText[13].";color:".$StyleText[3]."'>".$thisData['Bezeichnung'].$jahre
					."\n\t\t\t\t\t\t</span>"
					."\n\t\t\t\t\t</td>"
					."\n\t\t\t\t\t<td style='text-align:right'>"
					."\n\t\t\t\t\t\t<span style='font-weight:normal;font-size:".$StyleText[13].";color:".$StyleText[3]."'>".$remind.$thisData['ZeitTxt']
					."\n\t\t\t\t\t\t</span>"
					."\n\t\t\t\t\t</td>"
					."\n\t\t\t\t</tr>";
	    }
	}
	
/*****************************************************************/
// Erinnerungszeitpunkt erzeugen
// Abhängig ob festes Datum oder variables Datum
// Rückgabe genauer Zeitpunkt der Erinnerung in Unix Timecoder
//
/*****************************************************************/
	private function get_Reminder($startTime, $trigger){
		$reminder = 0;
		$xvalue = explode(":", $trigger);

		if ( (substr($xvalue[0],0) == "DURATION") || (count($xvalue) >= 2) ){ // Variables Erinnerungsprofil   Bei einigen Server fehlt das Duration!!
			// Zeitintervall zerlegen
			$str = substr($xvalue[1],strpos($xvalue[1], 'P') + 1);
			$stunden = substr(strstr($str, 'T'),1);
			$tage = strstr($str, 'D',true);
			$reminderTime = 0;

			// Sekunden, Minuten, Stunden weise Erinnerung
			if (substr($stunden,strlen($stunden)-1,strlen($stunden)) == "S"){
				$reminderTime = substr($stunden,0,strlen($stunden)-1);
			}
			else if (substr($stunden,strlen($stunden)-1,strlen($stunden)) == "M"){
				$reminderTime = substr($stunden,0,strlen($stunden)-1) * 60;
			}
			else if (substr($stunden,strlen($stunden)-1,strlen($stunden)) == "H"){
				$reminderTime = substr($stunden,0,strlen($stunden)-1) * 60 * 60;
			}

			// Tage weise Erinnerung
			if ($tage <> ""){
				$reminderTime = $reminderTime + ( $tage * 86400 );
			}

			// Neue exakte Erinnerungzeitpunkt erzeugen
			if(substr($xvalue[1],0,1) == "-"){
				$reminder = $startTime - $reminderTime;
			}else{
				$reminder = $startTime + $reminderTime;
			}
		}
		else if (substr($xvalue[0],0) == "DATE-TIME"){  // fester Erinnerungszeitpunkt
			$reminder = strtotime(substr($xvalue[1],6,2).".".substr($xvalue[1],4,2).".".substr($xvalue[1],0,4)." ".substr($xvalue[1],9,2).":".substr($xvalue[1],11,2));
		}
		return $reminder;
	}

/*****************************************************************/
//
//
//
//
//
/*****************************************************************/
	private function seDay($begin,$end,$format,$sep)
	{

		$pos1	= strpos($format, 'd');
		$pos2	= strpos($format, 'm');
		$pos3	= strpos($format, 'Y');

		$begin	= explode($sep,$begin);
		$end	= explode($sep,$end);

		$first 	= GregorianToJD($end[$pos2],$end[$pos1],$end[$pos3]);
		$second	= GregorianToJD($begin[$pos2],$begin[$pos1],$begin[$pos3]);

		if($first > $second)
			return $first - $second;
		else
			return $second - $first;

	}

/*****************************************************************/
//
// Vergleicht Datum
//
// Rückgabe: 0 ist identisch, -1 ist A älter als B, 1 ist A jünger als B
//
/*****************************************************************/
	private function DateCompare($a, $b)
	{
	    if ( $a['Datum'] == $b['Datum'] ) return 0;
	    if ( $a['Datum'] < $b['Datum'] )  return -1;
	    return 1;
	}

/*****************************************************************/
// Formartiert die 3 stelligen Wochentage zu 2 stelligen
// Rückgabe 2 stellig
//
/*****************************************************************/
	private function formatTag($DateItem) {
		$Translation = array(
		    'Mon'       => 'MO',
		    'Tue'       => 'TH',
		    'Wed'       => 'WE',
		    'Thu'       => 'TH',
		    'Fri'       => 'FR',
		    'Sat'       => 'SA',
		    'Sun'       => 'SU',
		);
		return $Translation[$DateItem];
	}

/*****************************************************************/
//
// Loste den Befehl aus der über die Variable übergeben wurde.
//
/*****************************************************************/
	private function UserEvent($value)
	{
		IPS_LogMessage("ownCloud-Modul", "UserEvent: $value");

		ob_start();
		eval($value);
		ob_get_clean();
	}

/*****************************************************************/
//
// Prüfen ob der gewählte Tage ein Feiertag ist
// Übergabe: zu prüfenden Tag
// Rückgabe: true = Feiertage; false = kein Feiertag
//
// Routine nicht von mir aber angepasst
//
/*****************************************************************/
	private function get_Feiertag( $date, $bland){
		if ($date == "") $date = mktime(0,0,0,date("m"),date("d"),date("y"));
		$Fdays = $this->getHolidays(Date('Y', $date), $bland);
		$return = "";
		if ( $Fdays == "") return;

		foreach($Fdays as $value) {
			list($key, $value) = each($Fdays);
			if ($date == $value) $return = $key;
		}
		return $return;
	}

/*****************************************************************/
//
// Routine nicht von mir
//
/*****************************************************************/
  	private function getEasterSundayTime($year)	{
   		$p = floor($year/100);
   		$r = floor($year/400);
   		$o = floor(($p*8+13)/25)-2;
   		$w = (19*($year%19)+(13+$p-$r-$o)%30)%30;
   		$e = ($w==29?28:$w);
   		if ($w==28&&($year%19)>10) $e=27;
   		$day = (2*($year%4)+4*($year%7)+6*$e+(4+$p-$r)%7)%7+22+$e;
   		$month = ($day>31?4:3);
   		if ($day>31) $day-=31;
   		return mktime(0, 0, 0, $month, $day, $year);
  	}

/*****************************************************************/
//
// Routine nicht von mir
//
/*****************************************************************/
	private function getHolidays($year, $bland) {

		if ( $bland == "--") return "";		// Feiertage abgeschaltet
		
		$time = $this->getEasterSundayTime($year);
		$days[""] 							= 0;
		$days["Neujahr"] 					= mktime(0, 0, 0, 1, 1, $year);
		$days["Karfreitag"] 				= $time-(86400*2);
		$days["Ostersonntag"] 				= $time;
		$days["Ostermontag"] 				= $time+(86400 /*[Objekt #20864 existiert nicht]*/);
		$days["Tag der Arbeit"]        		= mktime(0, 0, 0, 5, 1, $year);
		$days["Christi Himmelfahrt"] 		= $time+(86400*39);
		$days["Pfingstsonntag"] 			= $time+(86400*49);
		$days["Pfingstmontag"] 				= $time+(86400*50);
		$days["Tag der deutschen Einheit"] 	= mktime(0, 0, 0, 10, 3, $year);
		$days["Buß- und Bettag"] 			= mktime(0, 0, 0, 11, 26+(7-date('w', mktime(0, 0, 0, 11, 26, $year)))-11, $year); //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		$days["1. Weihnachtsfeiertag"] 		= mktime(0, 0, 0, 12, 25, $year);
		$days["2. Weihnachtsfeiertag"] 		= mktime(0, 0, 0, 12, 26, $year);

		//*******************************
		// Fester $Feiertag in BW, BY, ST
		//*******************************
		if (($bland == "BW") or ($bland == "BY") or ($bland == "ST")) {
				$days["Heilige 3 Könige"] 	= mktime(0, 0, 0, 1, 6, $year); //!!!!!!!!!!!!!!!!!!!!!!!
		}

		//***************************************
		// Fester $Feiertag in BB, MV, SA, ST, TH
		//***************************************
		if (($bland == "BB") or ($bland == "MV") or ($bland == "SA") or ($bland == "ST") or ($bland == "TH")) {
				$days["Reformationstag"] 	= mktime(0, 0, 0, 10, 31, $year); //!!!!!!!!!!!!!!!!!!!!!
		}

		//***************************************
		// Fester $Feiertag in BW, BY, NW, RP, SL
		//***************************************
		if (($bland == "BW") or ($bland == "BY") or ($bland == "NW") or ($bland == "RP") or ($bland == "SL")) {
				$days["Allerheiligen"] 		= mktime(0, 0, 0, 11, 1, $year);
		}

		//*******************************************
		// Fester $Feiertag in BY (nicht überall), SL
		//*******************************************
		if (($bland == "BY") or ($bland == "SL")) {
				$days["Maria Himmelfahrt"] 	= mktime(0, 0, 0, 08, 15, $year); //!!!!!!!!!!!!!!!!!!!!!
		}

		//**********************************************************************
		// Bewegliche Feiertage BW, BY, HE, NW, RP, SL, (SA, TH nicht überall)
		//**********************************************************************
		if (($bland == "BW") or ($bland == "BY") or ($bland == "HE") or ($bland == "NW") or ($bland == "RP") or ($bland == "SL") or ($bland == "SA") or ($bland == "TH")) {
					$days["Fronleichnam"] 	= $time+(86400*60); //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		}
		return $days;
	}
}
?>
