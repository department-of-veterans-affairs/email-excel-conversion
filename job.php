<?php
set_time_limit(3000); 
ini_set('display_errors', true);
ini_set('auto_detect_line_endings', true);

$Site_URL = "http://example.com/"; // The URL you are running your code
$Master_Email = "[Enter Your Email"; // Your email address
$Master_Password = "[Enter Your Password]"; // Your email password


function sendEmail($Master_Email,$Master_Password,$To_Email,$To_Name,$From_Email,$From_Name,$Subject,$Body)
	{
		
	$mail  = new PHPMailer();
	$mail->IsSMTP();
	 
	//GMAIL config
		$mail->SMTPAuth   = true;                  // enable SMTP authentication
		$mail->SMTPSecure = "ssl";                 // sets the prefix to the server
		$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
		$mail->Port       = 465;                   // set the SMTP port for the GMAIL server
		$mail->Username   = $Master_Email;  // GMAIL username
		$mail->Password   = $Master_Password;            // GMAIL password
	//End Gmail

	$mail->From       = $From_Email;
	$mail->FromName   = $From_Name;
	$mail->Subject    = $Subject;
	$mail->MsgHTML($Body);
	 
	$mail->AddAddress($To_Email,$To_Name);
	$mail->IsHTML(true);
	 
	if(!$mail->Send()) {
	  //echo "Mailer Error: " . $mail->ErrorInfo;
		} else  {
			echo "Message sent!";
		}
	
	}

function PrepareXMLName($PrepareString)
	{
		
	$PrepareString = str_replace(" ","",$PrepareString);
	$PrepareString = preg_replace('#\W#', '', $PrepareString);
	$PrepareString = str_replace("ZSPAZESZ","",$PrepareString);
	$PrepareString = strtolower($PrepareString);
	
	return $PrepareString;
	}	

// Function to convert CSV into associative array
function csvToArray($file, $delimiter) { 
  if (($handle = fopen($file, 'r')) !== FALSE) { 
    $i = 0; 
    while (($lineArray = fgetcsv($handle, 4000, $delimiter, '"')) !== FALSE) { 
      for ($j = 0; $j < count($lineArray); $j++) { 
        $arr[$i][$j] = $lineArray[$j]; 
      } 
      $i++; 
    } 
    fclose($handle); 
  } 
  return $arr; 
} 
 
require_once 'Classes/PHPExcel/IOFactory.php';
require_once 'Classes/class.phpmailer.php';  

// Open Manifest
$ManifestPath = "data/manifest.json";
$ManifestString = file_get_contents($ManifestPath);
$Manifest = json_decode($ManifestString,true);

// Open Access
$AccessPath = "data/access.json";
$AccessString = file_get_contents($AccessPath);
$Access = json_decode($AccessString,true);

/* connect to gmail with your credentials */
$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = $Master_Email; # e.g somebody@gmail.com
$password = $Master_Password;
 
/* try to connect */
$inbox = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());
 
/* get all new emails. If set to 'ALL' instead 
 * of 'NEW' retrieves all the emails, but can be 
 * resource intensive, so the following variable, 
 * $max_emails, puts the limit on the number of emails downloaded.
 * 
 */
$emails = imap_search($inbox,'ALL');
 
/* useful only if the above search is set to 'ALL' */
$max_emails = 16;
 
/* if any emails found, iterate through each email */
if($emails) {
 
    $count = 1;
 
    /* put the newest emails on top */
    rsort($emails);
	
	$output = '';
 
    /* for every email... */
    foreach($emails as $email_number) 
    {
 
        /* get information specific to this email */
        $overview = imap_fetch_overview($inbox,$email_number,0);
 
        /* get mail message */
        $message = imap_fetchbody($inbox,$email_number,2);
		
		/* output the email header information */
		$Seen = $overview[0]->seen;
		$Subject = $overview[0]->subject;
		$From = $overview[0]->from;
		$FromArray = explode("<",$From);
		$From_Name = trim($FromArray[0]);
		$From_Email = $FromArray[1];
		$From_Email = trim(str_replace(">","",$From_Email));
		$Email_Date = $overview[0]->date;
		
		$SenderArray = array();
		$SenderArray['name'] = $From_Name;
		$SenderArray['email'] = $From_Email;

		// If this user has name email in accesss.json
				
		if(array_search($SenderArray,$Access)!==false)
			{
		
	        /* get mail structure */
	        $structure = imap_fetchstructure($inbox, $email_number);
	 
	        $attachments = array();
	 
	        /* if any attachments found... */
	        if(isset($structure->parts) && count($structure->parts)) 
	        {
	            for($i = 0; $i < count($structure->parts); $i++) 
	            {
	                $attachments[$i] = array(
	                    'is_attachment' => false,
	                    'filename' => '',
	                    'name' => '',
	                    'attachment' => ''
	                );
	 
	                if($structure->parts[$i]->ifdparameters) 
	                {
	                    foreach($structure->parts[$i]->dparameters as $object) 
	                    {
	                        if(strtolower($object->attribute) == 'filename') 
	                        {
	                            $attachments[$i]['is_attachment'] = true;
	                            $attachments[$i]['filename'] = $object->value;
	                        }
	                    }
	                }
	 
	                if($structure->parts[$i]->ifparameters) 
	                {
	                    foreach($structure->parts[$i]->parameters as $object) 
	                    {
	                        if(strtolower($object->attribute) == 'name') 
	                        {
	                            $attachments[$i]['is_attachment'] = true;
	                            $attachments[$i]['name'] = $object->value;
	                        }
	                    }
	                }
	 
	                if($attachments[$i]['is_attachment']) 
	                {
	                    $attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);
	 
	                    /* 4 = QUOTED-PRINTABLE encoding */
	                    if($structure->parts[$i]->encoding == 3) 
	                    { 
	                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
	                    }
	                    /* 3 = BASE64 encoding */
	                    elseif($structure->parts[$i]->encoding == 4) 
	                    { 
	                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
	                    }
	                }
	            }
	        }
			
			$AttachmentCount = 0;
	 
	        /* iterate through each attachment and save it */
	        foreach($attachments as $attachment)
	        {
	            if($attachment['is_attachment'] == 1)
	            {
	                $filename = $attachment['name'];
					$FileNameArray = explode(".",$filename);
					$extension = $FileNameArray[1];
					
	                if($extension=="xlsx")
						{
	                	$filename = 'excel/'. $email_number . "-" . $filename;
						}
					else
						{
	                	$filename = 'files/'. $email_number . "-" . $filename;
						}				
					
	                if(empty($filename)) $filename = $attachment['filename'];
	 
	                if(empty($filename)) $filename = time() . ".dat";
	 
	                $fp = fopen($filename, "w+");				
	                fwrite($fp, $attachment['attachment']);
	                fclose($fp);
					
					$AttachmentCount++;
	            }
	 
	        }
			
			$ExcelFileName = $filename;
			$ExcelFileName = str_replace("excel/","",$filename);
			
			//echo "Seen: " . $Seen . "<br />";
			//echo "Subject: " . $Subject . "<br />";
			//echo "Name: " . $From_Name . "<br />";
			//echo "Email: " . $From_Email . "<br />";
			//echo "Date: " . $Email_Date . "<br />";		
			//echo "FileName: " . $filename . "<br />";	
			
			$ManifestEntry = array();
			$ManifestEntry['name'] = $From_Name;
			$ManifestEntry['email'] = $From_Email;
			$ManifestEntry['filename'] = $From_Name;
			$ManifestEntry['date'] = str_replace(".xlsx","",$filename);
			$ManifestEntry['processed'] = '0';
			
	    	if(array_search($ManifestEntry,$Manifest)==false)
	    		{
	    		array_push($Manifest, $ManifestEntry);	
	    		}	
					
			$ManifestJSON = json_encode($Manifest);		
		    $fp = fopen($ManifestPath, "w+");				
		    fwrite($fp, $ManifestJSON);
		    fclose($fp);	
		    
			// Go ahead and delete we have everything we need if next process is killed
			imap_delete($inbox, $email_number);	
			
			// Send process notification
			$To_Email = $From_Email;
			$To_Name = $From_Name;
			$From_Name = "Conversion - Processing";
			$Subject = "The Excel File (" . $ExcelFileName . ") Is Being Processed";
			$Body = $ExcelFileName . ' is being processed, if its not too large you will receive links in a few minutes, if it was too large or had errors it will be processed as part of nightly queue, and you will receive email when done.';
			sendEmail($Master_Email,$Master_Password,$To_Email,$To_Name,$Master_Email,$From_Name,$Subject,$Body);				
					
			// Generate CSV
			$CSVFileName = str_replace(".xlsx",".csv",$filename);
			$CSVFileName = str_replace("excel/","csv/",$CSVFileName);				
			$excel = PHPExcel_IOFactory::load($filename);
			$writer = PHPExcel_IOFactory::createWriter($excel, 'CSV');
			$writer->setDelimiter(",");
			//$writer->setEnclosure("");
			//$writer->setLineEnding("\r\n");
			$writer->setSheetIndex(0);
			$writer->save($CSVFileName);
			
			// Generate JSON
			$JSONFileName = str_replace(".csv",".json",$CSVFileName);
			$JSONFileName = str_replace("csv/","json/",$JSONFileName);	
			$keys = array();
			$newArray = array();	
			$data = csvToArray($CSVFileName, ',');
			$count = count($data) - 1;
			$labels = array_shift($data);  		 
			foreach ($labels as $label) {  $keys[] = $label; }
			$keys[] = 'id';		 
			for ($i = 0; $i < $count; $i++) {
			  $data[$i][] = $i;
			}
			for ($j = 0; $j < $count; $j++) {
			  $d = array_combine($keys, $data[$j]);
			  $newArray[$j] = $d;
			}				
			$JSONVersion = json_encode($newArray);
		    $fp = fopen($JSONFileName, "w+");				
		    fwrite($fp, $JSONVersion);
		    fclose($fp);		
	
			// Generate XML
			$XMLFileName = str_replace(".csv",".xml",$CSVFileName);
			$XMLFileName = str_replace("csv/","xml/",$XMLFileName);		
			
			// Open csv to read
			$inputFile  = fopen($CSVFileName, 'rt');
			
			// Get the headers of the file
			$headers = fgetcsv($inputFile);
			
			// Create a new dom document with pretty formatting
			$doc  = new DomDocument();
			$doc->formatOutput   = true;
			
			// Add a root node to the document
			$root = $doc->createElement('rows');
			$root = $doc->appendChild($root);
			
			// Loop through each row creating a <row> node with the correct data
			while (($row = fgetcsv($inputFile)) !== FALSE)
				{
				$container = $doc->createElement('row');
				foreach ($headers as $i => $header)
					{
					$header = str_replace(chr(32),"_",trim($header));
					$header = strtolower($header);
					if($header==''){ $header = 'empty';}
					$header = PrepareXMLName($header);
					if(is_numeric($header)) { $header = "number-". $header; }
					//echo "HERE: " . $header . "<br />";  
					$child = $doc->createElement($header);
					$child = $container->appendChild($child);
					$value = $doc->createTextNode($row[$i]);
					$value = $child->appendChild($value);
					}
				$root->appendChild($container);
				}
			
			$XMLVersion = $doc->saveXML();
		    $fp = fopen($XMLFileName, "w+");				
		    fwrite($fp, $XMLVersion);
		    fclose($fp);	
	
			// Send conversion notification
			$To_Email = $From_Email;
			$To_Name = $From_Name;
			$From_Name = "Conversion - Processing";
			$Subject = "The Excel File (" . $ExcelFileName . ") Has Been Converted";
			
			$Body = '<a href="' . $Site_URL . $CSVFileName . '">CSV File</a><br />';
			$Body .= '<a href="' . $Site_URL . $JSONFileName . '">JSON File</a><br />';
			$Body .= '<a href="' . $Site_URL . $XMLFileName . '">XML File</a><br />';			
			
			sendEmail($Master_Email,$Master_Password,$To_Email,$To_Name,$Master_Email,$From_Name,$Subject,$Body);	
	
	        if($count++ >= $max_emails) break;
    	}
    }
 
} 
 
/* close the connection */
imap_close($inbox);
?>