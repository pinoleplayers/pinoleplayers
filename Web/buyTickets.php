<?
	ini_set("include_path", '/home/pcp/php:/home/pcp/phpinc:' . ini_get("include_path")  );
	session_start();

	require_once('recaptcha/recaptchalib.php');
	require_once('class.inputfilter_clean.php5');
	require_once('EmailAddressValidator.php');

	// include Pear libraries
	include('Mail.php');
	include('Mail/mime.php');

	$publickey = "6LePSwQAAAAAADxUr4RbCyd5B6l_s4a1SNxoygMl"; // you got this from the signup page
	$privatekey = "6LePSwQAAAAAACJqzKLyf62DVoa2TnSz0SipYJ_1";
	$recaptcha = recaptcha_get_html($publickey);

	$mysql_db = 'pcp_ticket';
	$mysql_user = 'pcp_ticket';
	$mysql_passwd = 'sJpAm9fY5S';

	$pageContent = "";

	$tixName = "";
	$tixAddress = "";
	$tixCity = "";
	$tixState = "";
	$tixZip = "";
	$tixEmail = "";
	$tixDayPhone = "";
	$tixNightPhone = "";
	$tixPerfIdPrices = "";
	$tixPerfId = "";
	$tixPerfPriceSenStu = "";
	$tixSenStu = "";
	$tixPerfPriceAdult = "";
	$tixAdult = "";
	$tixPayment = "";
	$tixSpecReq = "";
	$tixAddToMailingList = "20";
	$tixPaymentCheck = "";
	$tixPaymentCC = "";
	$errors;

	$tixMessage = <<<EOT
	<p style="text-align:center;border:solid 1px black;padding:1em; font-weight:bold">

	Tickets will be held at the door.  Ticket reservation is secured once payment is received or when credit card charges have been approved.
	<br><br>
	Cabaret seating &#8211; All seats reserved.  All reservations must be prepaid.<br>
	All sales final. Seats assigned upon payment

	</p>
EOT;

	if ($_SERVER["REQUEST_METHOD"] != "POST")
	{
		$pageContent = DisplayForm();
		$_SESSION["tixStep"] = "validateOrder";
		$_SESSION["emailSent"] = "";
	}
	else
	{
		switch($_SESSION["tixStep"])
		{
			case "validateOrder":
				ValidateOrder() && ShowConfirm();
				break;
			case "showConfirm":
				ShowConfirm();
				break;
			default:
				break;
		}
	}

function LoadFormVars()
{
	global $tixName,
		$tixAddress,
		$tixCity,
		$tixState,
		$tixZip,
		$tixEmail,
		$tixDayPhone,
		$tixNightPhone,
		$tixPerfIdPrices,
		$tixPerfId,
		$tixPerfPriceSenStu,
		$tixSenStu,
		$tixPerfPriceAdult,
		$tixAdult,
		$tixPayment,
		$tixSpecReq,
		$tixPaymentCheck,
		$tixPaymentCC;
		$tixAddToMailingList;

		$myFilter = new InputFilter();

		$_POST = $myFilter->process($_POST);

		$tixName = $_POST["tixName"];
		$tixAddress = $_POST["tixAddress"];
		$tixCity = $_POST["tixCity"];
		$tixState = $_POST["tixState"];
		$tixZip = $_POST["tixZip"];
		$tixEmail = $_POST["tixEmail"];
		$tixDayPhone = $_POST["tixDayPhone"];
		$tixNightPhone = $_POST["tixNightPhone"];
		$tixPerfIdPrices = $_POST["tixPerfIdPrices"];
		$tixPerfIdPricesFlds = explode( ":", $tixPerfIdPrices );
		$tixPerfId = $tixPerfIdPricesFlds[0];
		$tixPayment = $_POST["tixPayment"];
		$tixSpecReq = $_POST["tixSpecReq"];
		$tixAddToMailingList = $_POST["tixAddToMailingList"];

		$tixPerfPriceSenStu = floatval($tixPerfIdPricesFlds[1]);
		$tixPerfPriceAdult  = floatval($tixPerfIdPricesFlds[2]);

		$tixSenStu = intval($_POST["tixSenStu"]);
		$tixSenStu = ($tixSenStu < 0) ? 0 : $tixSenStu;
		$tixAdult = intval($_POST["tixAdult"]);
		$tixAdult = ($tixAdult < 0 ) ? 0 : $tixAdult;
}

function ValidateOrder()
{
	global $tixName,
		$tixAddress,
		$tixCity,
		$tixState,
		$tixZip,
		$tixEmail,
		$tixDayPhone,
		$tixNightPhone,
		$tixPerfIdPrices,
		$tixPerfId,
		$tixPerfPriceSenStu,
		$tixSenStu,
		$tixPerfPriceAdult,
		$tixAdult,
		$tixPayment,
		$tixSpecReq,
		$tixPaymentCheck,
		$tixPaymentCC,
		$recaptcha,
		$tixMessage,
		$pageContent,
		$errors,
		$privatekey;

	LoadFormVars();

	$errors = array();

	if ($tixName == "")
		$errors[] = "Please enter your name.";
	if ($tixAddress == "")
		$errors[] = "Please enter your address.";
	if ($tixCity == "")
		$errors[] = "Please enter your city.";
	if ($tixState == "")
		$errors[] = "Please enter your state.";
	if ($tixZip == "")
		$errors[] = "Please enter your zip code.";

	if ($tixEmail == "")
		$errors[] = "Please enter your email address.";
	$validator = new EmailAddressValidator;
	if ($tixEmail != "" && ! $validator->check_email_address($tixEmail))
		$errors[] = "Your email address is not in a recognized format.";

	if ($tixDayPhone == "" && $tixNightPhone == "")
		$errors[] = "Please enter either a day time or evening phone number.";

	if ($tixPerfId == "" || $tixPerfId == "0")
		$errors[] = "Please select a performance.";

	if ($tixPerfPriceSenStu == "")
		$errors[] = "Internal error with tixPerfPriceSenStu."."-".$tixPerfIdPrices."-".$tixPerfId."-".$tixPerfPriceSenStu."?";

	if ($tixPerfPriceAdult == "")
		$errors[] = "Internal error with tixPerfPriceAdult."."-".$tixPerfIdPrices."-".$tixPerfId."-".$tixPerfPriceAdult."?";;

	if (($tixSenStu == "" && $tixAdult == "") || (intval($tixSenStu) + intval($tixAdult) <= 0))
		$errors[] = "Please enter the number of tickets you would like.";

	if ($tixPayment == "")
	{
		$errors[] = "Please select a payment method.";
	}
	elseif ($tixPayment = "paymentCheck")
	{
		$tixPaymentCheck = "checked=\"checked\"";
	}
	elseif ($tixPayment = "paymentCC")
	{
		$tixPaymentCC = "checked=\"checked\"";
	}

	$resp = recaptcha_check_answer ($privatekey,
				$_SERVER["REMOTE_ADDR"],
				$_POST["recaptcha_challenge_field"],
				$_POST["recaptcha_response_field"]);

	if (!$resp->is_valid)
	{
		$errors[] = "The captcha was not entered correctly.";
	}
	elseif (count($errors) > 0)
	{
		$errors[] = "Be sure to enter the new captcha.";
	}

	if (count($errors) > 0)
	{
		$pageContent = DisplayForm();
		return false;
	}


	$_SESSION["tixStep"] = "showConfirm";
	return true;

//	$pageContent = print_r($_POST,1);
//	$pageContent = print_r($errors,1);

}

function ShowConfirm()
{
	global $tixName,
		$tixAddress,
		$tixCity,
		$tixState,
		$tixZip,
		$tixEmail,
		$tixDayPhone,
		$tixNightPhone,
		$tixPerfIdPrices,
		$tixPerfId,
		$tixPerfPriceSenStu,
		$tixSenStu,
		$tixPerfPriceAdult,
		$tixAdult,
		$tixPayment,
		$tixSpecReq,
		$tixAddToMailingList,
		$tixMessage,
		$pageContent,
		$mysql_db,
		$mysql_user,
		$mysql_passwd;

	LoadFormVars();

	$cn = mysql_connect('localhost', $mysql_user, $mysql_passwd);
	mysql_select_db($mysql_db, $cn);
	$perfQuery = "SELECT * FROM `performance` WHERE `id` = ".$tixPerfId;
	$perfRes = mysql_query($perfQuery,$cn);
	$perfInfo = mysql_fetch_object($perfRes);

	$performance = $perfInfo->show." - ".date( "D, M j - g:ia",strtotime($perfInfo->perfDateTime));

	$tixTotal = $tixSenStu + $tixAdult;

	if ($perfInfo->twofer)
	{
		$twofersAdult = intval($tixAdult  / 2);
		$tixAmtAdult  = $twofersAdult  * $perfInfo->tixPriceAdult;
		$twofersSenStu = intval($tixSenStu / 2);
		$tixAmtSenStu = $twofersSenStu * $perfInfo->tixPriceSeniorStudent;
		if ($tixAdult % 2 != 0)
		{
			$tixAmtAdult += $perfInfo->tixPriceAdult;
			++$twofersAdult;
		}
		else if ($tixSenStu % 2 != 0)
		{
			$tixAmtSenStu += $perfInfo->tixPriceSeniorStudent;
			++$twofersSenStu;
		}
		$tixDollarAmt = $tixAmtSenStu + $tixAmtAdult;
		$tixAdultTxt  = $tixAdult." (".$twofersAdult ." 2fers)";
		$tixSenStuTxt = $tixSenStu." (".$twofersSenStu." 2fers)";
	}
	else
	{
		$tixDollarAmt = ($tixSenStu * $perfInfo->tixPriceSeniorStudent) + ($tixAdult * $perfInfo->tixPriceAdult);
		$tixAmtSenStu = $tixSenStu * $perfInfo->tixPriceSeniorStudent;
		$tixSenStuTxt = $tixSenStu;
		$tixAmtAdult  = $tixAdult * $perfInfo->tixPriceAdult;
		$tixAdultTxt  = $tixAdult;
	}

	if ($tixPayment == "paymentCC")
	{
		$tixPayMethod = "We will call you for your credit card information.  (A $2 handling fee applies.)";
	}
	else
	{
		$tixPayMethod = "I will mail a check for <strong>$".$tixDollarAmt."</strong> along with a copy of this page to:<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pinole Community Players<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;P.O. Box 182<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pinole, CA  94564";
	}

	if ($_POST["tixAddToMailingList"] != "")
	{
		$tixMailList = "You will be added to our mailing list.";
	}
	else
	{
		$tixMailList = "You will not be added to our mailing list.";
	}

	$pageContent = <<<EOT
	<p>Thank you for your order.  It has been submitted to our box office staff.  Please review and print this page for your records.  If you need to make any changes to your order, please contact our box office at (510) 724-9844.</p>
	$tixMessage
	<table id="tixTable" cellpadding="5">
		<tr>
			<th>Name</th>
			<td>$tixName</td>
		</tr>
		<tr>
			<th>Address</th>
			<td>
				$tixAddress<br>
				$tixCity, $tixState $tixZip
			</td>
		</tr>
		<tr>
			<th>Day Time Phone</th>
			<td>$tixDayPhone</td>
		</tr>
		<tr>
			<th>Evening Phone</th>
			<td>$tixNightPhone</td>
		</tr>
		<tr>
			<th>Email Address</th>
			<td>$tixEmail</td>
		</tr>
		<tr>
			<th>Performance</th>
			<th>$performance</th>
		</tr>
		<tr>
			<th style="text-align:left">Number of Tickets</th>
			<td>
				<table name="TicketSummary" border="1" cellpadding="5">
					<tr>
						<td>&nbsp;</td>
						<th align="center">Tickets</th>
						<th align="right">Price</th>
						<th align="right">Cost</th>
					</tr>
					<tr>
						<th>Student/<br/>
							&nbsp;&nbsp;Senior
						</th>
						<td align="center">$tixSenStuTxt</td>
						<td align="right">$$perfInfo->tixPriceSeniorStudent</td>
						<td align="right">$$tixAmtSenStu</td>
					</tr>
					<tr>
						<th>Adult</th>
						<td align="center">$tixAdultTxt</td>
						<td align="right">$$perfInfo->tixPriceAdult</td>
						<td align="right">$$tixAmtAdult</td>
					</tr>
					<tr>
						<th>Total Order</th>
						<td align="center">$tixTotal</td>
						<td>&nbsp;</td>
						<td align="right">$$tixDollarAmt</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th>Payment Method</th>
			<td>$tixPayMethod</td>
		</tr>
		<tr>
			<th>Special Requests</th>
			<td>$tixSpecReq</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<th>$tixMailList</th>
		</tr>
	</table>
EOT;

	if ($_SESSION["emailSent"] != "true")
	{
		SendConfirmation($pageContent, $tixName, $tixEmail);
	}
	else
	{
		$pageContent = "<p style=\"border:1px solid black; padding:10px; background-color:gold;\">It appears you have either pressed the Submit Order button more than once, or refreshed this page.  The order below has already been submitted.  Please click here to <a href=\"buytickets.php\">place another order</a>.</p>"
					. $pageContent;
	}
}

function SendConfirmation($htmlContent, $name, $email)
{
		preg_match_all("/<p[^>]*>(.*?)<\/p>/s", $htmlContent, $matches);
		$txtOrderHeader = $matches[1][0] . "\n\n\n";

		preg_match_all("/<tr[^>]*>(.*?)<\/tr>/s", $htmlContent, $matches);
		// $rows = $matches[1];
		$rows = preg_replace("/&[^;]*;/s", " ", $matches[1]);

		foreach ($rows as $row)
		{
			preg_match_all("/<t[dh][^>]*>(.*?)<\/t[dh]>/s", $row, $matches);
			$data[] = $matches[1];
		}

		$txtContent = "";

		foreach ($data as $row)
		{
			$txtContent .= preg_replace("/<.*?>/s", " ", preg_replace("/\s\s+/s", " ", $row[0]))
						.  ": "
						.  preg_replace("/<.*?>/s", " ", preg_replace("/\s\s+/s", " ", $row[1]))
						.  "\n";
		}


		$htmlContent = <<<EOT
<html>
<head>
<style type="text/css">
#tixTable td {
	text-align:left;
	vertical-align:top;
}
#tixTable th {
	text-align:right;
	vertical-align:top;
}
</style>
</head>
<body>
$htmlContent
</body>
</html>
EOT;

		//send to box office
		$hdrs = array(
			'From'			=> 'boxoffice@pinoleplayers.org',
			'Subject'		=> 'Ticket Order for '.$name,
			'To'			=> 'boxoffice@pinoleplayers.org',
			'BCC'			=> 'mao@netcom.com',
			'Return-Path'	=> 'boxoffice@pinoleplayers.org'
		);

		$crlf = "\n";
		$mime = new Mail_mime($crlf);
		$mime->setTXTBody($txtContent);
//		$mime->setHTMLBody($htmlContent);

		$email_body = $mime->get();
		$hdrs = $mime->headers($hdrs);

		$mail =& Mail::factory('mail');
		$mail->send('boxoffice@pinoleplayers.org, pa-clark@comcast.net', $hdrs, $email_body);

		// send to person that ordered
		$hdrs = array(
			'From'			=> 'boxoffice@pinoleplayers.org',
			'Subject'		=> 'Your PCP Ticket Order',
			'To'			=> $email,
			'BCC'			=> 'mao@netcom.com',
			'Return-Path'	=> 'boxoffice@pinoleplayers.org'
		);

		$crlf = "\n";
		$mime = new Mail_mime($crlf);
		$mime->setTXTBody($txtOrderHeader . $txtContent);
		$mime->setHTMLBody($htmlContent);

		$email_body = $mime->get();
		$hdrs = $mime->headers($hdrs);

		$mail =& Mail::factory('mail');
		$mail->send($email, $hdrs, $email_body);

		$_SESSION["emailSent"] = "true";
}


function getPerfDates()
{
	$perfDatesHTML = '';

	global $mysql_db, $mysql_user, $mysql_passwd, $tixPerfId;

	$selected = "";
	if ($tixPerfId == "" || $tixPerfId == 0)
		$selected = "selected=\"selected\"";
	$perfDatesHTML = <<<EOT
	<option disabled="disabled" $selected value="0">Select Performance</option>
EOT;

	$cn = mysql_connect('localhost', $mysql_user, $mysql_passwd);
	mysql_select_db($mysql_db, $cn);

	$query = "SELECT * FROM `performance` WHERE `visible` = 1 ORDER BY perfDateTime ASC";
	$result = mysql_query($query,$cn);
	while ($row = mysql_fetch_object($result))
	{
		$perfIdPrices = $row->id . ":" . $row->tixPriceSeniorStudent . ":" . $row->tixPriceAdult;
		$performance = $row->show . " - ";
		$disabled = "";

		if ($row->preSold == 1)
		{
			$performance .= date("D, M j",strtotime($row->perfDateTime)) . " - Pre-Sold - Call 510-724-9844";
			$disabled = "disabled=\"disabled\" ";
		}
		else
		{
			$performance .= date( "D, M j - g:ia",strtotime($row->perfDateTime));
			if ($row->twofer == 1)
			{
				$performance .= "- 2for1";
			}
		}

	 	$selected = "";
		if ($row->soldOut == 1)
		{
			$performance .= " (SOLD OUT)";
			$disabled = "disabled=\"disabled\" ";
		}
		if ($row->comment == 1)
		{
			$disabled = "disabled=\"disabled\" ";
		}
		if ($tixPerfId == $row->id)
		{
			$selected = "selected=\"selected\"";
		}
		$perfDatesHTML .= <<<EOT
		<option value="$perfIdPrices" $disabled $selected>$performance</option>
EOT;
	}


	return $perfDatesHTML;
}

function DisplayForm()
{
	global $tixName,
		$tixAddress,
		$tixCity,
		$tixState,
		$tixZip,
		$tixEmail,
		$tixDayPhone,
		$tixNightPhone,
		$tixPerfIdPrices,
		$tixPerfId,
		$tixPerfPriceSenStu,
		$tixSenStu,
		$tixPerfPriceAdult,
		$tixAdult,
		$tixPayment,
		$tixSpecReq,
		$tixPaymentCheck,
		$tixPaymentCC,
		$recaptcha,
		$tixMessage,
		$errors;

	$perfDatesHTML = getPerfDates();

	$errorsHTML = "";

	if (count($errors) > 0)
	{
		$errorsHTML = "<p>There were some problems with your submission.  Please check the following and try again:</p>";
		$errorsHTML .= "<ul>";
		foreach ($errors as $error)
		{
			$errorsHTML .= "<li>".$error."</li>";
		}
		$errorsHTML .= "</ul>";
	}

	$form = <<<EOD
	$errorsHTML
	<form name="TicketForm" method="post" action="buytickets.php">
		<table id="tixTable" cellspacing="5">
			<tr>
				<th width="260">Name</th>
				<td><input tabindex="1" type="text" name="tixName" value="$tixName" size="50" /></td>
			</tr>
			<tr>
				<th>Street Address</th>
				<td><input type="text" name="tixAddress" value="$tixAddress" size="50" /></td>
			</tr>
			<tr>
				<th>City, State Zip</th>
				<td><input type="text" name="tixCity" value="$tixCity" />, <input type="text" name="tixState" value="$tixState" size="5" /> <input type="text" name="tixZip" value="$tixZip" size="10" /></td>
			</tr>
			<tr>
				<th>Day Time Phone</th>
				<td><input type="text" name="tixDayPhone" value="$tixDayPhone" /></td>
			</tr>
			<tr>
				<th>Evening Phone</th>
				<td><input type="text" name="tixNightPhone" value="$tixNightPhone" /></td>
			</tr>
			<tr>
				<th>Email Address</th>
				<td><input type="text" name="tixEmail" value="$tixEmail" size="50" /></td>
			</tr>
			<tr>
				<th>Performance</th>
				<td>
					<select name="tixPerfIdPrices" onchange="UpdatePrices()">
						$perfDatesHTML
					</select>
				</td>
			<tr>
				<th colspan="2" style="text-align:left">Number of Tickets</th>
			</tr>
			<tr>
				<th>Senior/Student Tickets</th>
				<td><input type="text" name="tixSenStu" length="2" size="2" value="$tixSenStu" />
					($<input tabindex="0" type="text" name="tixPerfPriceSenStu" value="$tixPerfPriceSenStu" align="right" length="3" size="3" readonly="readonly" /> each)
				</td>
			</tr>
			<tr>
				<th>Adult Tickets</th>
				<td><input type="text" name="tixAdult" length="2" size="2" value="$tixAdult" />
					($<input tabindex="0" type="text" name="tixPerfPriceAdult" value="$tixPerfPriceAdult" align="right" length="3" size="3" readonly="readonly" /> each)
				</td>
			</tr>
			<tr>
				<th>Payment will be made by:</th>
				<td>
					<input type="radio" name="tixPayment" $tixPaymentCheck id="tixPaymentCheck" value="paymentCheck">
					<label for="tixPaymentCheck">Check mailed to:<br/>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pinole Community Players<br/>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;P.O. Box 182<br/>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pinole, CA  94564<br/>
						</label>
					<input type="radio" name="tixPayment" $tixPaymentCC id="tixPaymentCC" value="paymentCC">
					<label for="tixPaymentCC">Please call me for my credit card information<br/>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;($2 handling fee applies)
						</label>
				</td>
			</tr>
			<tr>
				<th style="vertical-align:top">Any special requests<br />(seat location, wheel chair<br />access needed)</th>
				<td ><textarea name="tixSpecReq" cols="50" rows="4">$tixSpecReq</textarea></td>
			</tr>
			<tr>
				<td colspan="2">$tixMessage</td>
			</tr>
			<tr>
				<th>Add me to your mailing list</th>
				<td><input type="checkbox" name="tixAddToMailingList" value="yes">YES!</td>
			</tr>
			<tr>
				<th>Please Complete the Captcha</th><td>
					$recaptcha
				</td>

			</tr>
			<tr>
				<td colspan="2" style="text-align:center">
					<input type="Submit" value="Submit Order">&nbsp;&nbsp;&nbsp;&nbsp;<input type="reset" value="Clear Form">
				</td>
			</tr>
		</table>
	</form>
EOD;
	return $form;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<title>Order Tickets</title>

<link rel="stylesheet" type="text/css" href="css/pcp.css" />

<link rel="stylesheet" type="text/css" href="css/2leveltab.css" />

<style type="text/css">
#tixTable th {
	text-align:right;
	vertical-align:top;
}
#tixTable td {
	text-align:left;
	vertical-align:top;
}
</style>

<script type="text/javascript" src="js/easyDropDownContainer.js"></script>

<script type="text/javascript" src="js/2leveltab2.js">
	/***********************************************
	* 2 level Horizontal Tab Menu- by JavaScript Kit (www.javascriptkit.com)
	* This notice must stay intact for usage
	* Visit JavaScript Kit at http://www.javascriptkit.com/ for full source code
	* http://www.javascriptkit.com/script/script2/2leveltab.shtml
	***********************************************/
</script>
<script type=text/javascript>
function UpdatePrices() {
	var TicketForm = document.forms["TicketForm"];
	var selectedPerfIdPrices = TicketForm.elements["tixPerfIdPrices"].value;

	var tixPerfIdPricesFlds = selectedPerfIdPrices.split( ":" );

	TicketForm.elements["tixPerfPriceSenStu"].value = tixPerfIdPricesFlds[1];
	TicketForm.elements["tixPerfPriceAdult"].value  = tixPerfIdPricesFlds[2];
}
</script>
</head>

<body style="height:100%">
	<div id="mainContainer" style="height:100%">
	<center>
	<table id="mainTable" border="0" cellpadding="1" cellspacing="0" height="100%">
		<tbody>
<?
readfile("includes/PCPbanner-row.html");
readfile("includes/PCPmenu-row2.html");
?>
	<tr>
		<td colspan=3 id="mainContent" style="height:100%; margin-top:0pt; padding-top:0pt" valign="top" align="center">
			<div style="text-align:center; padding:0; padding-top:10px; margin-top:10px; background-color:#FFFF99">
				<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0">
					<tbody style="background-color:#FFFF99; font-size:12pt; color:ForestGreen">
						<tr>
							<td colspan=2>
								<div class="mainTitle" style="margin-top:0; padding-top:0">Order Tickets</div>
							</td>
						</tr>
						<tr>
							<td align="center">
								<table width="826" cellpadding="20"  border="2" bordercolor="blue">
									<tbody>
										<tr>
											<td>

<?= $pageContent ?>

											</td>
										</tr>
									</tbody>
								</table>
							<td>
						</tr>
					</tbody>
				</table>
			</div>
		</td>
	</tr>
<?
readfile("includes/PCPfooter-row.html");
?>
		</tbody>
	</table>
	</center>
	</div>
</body>
</html>
<!-- vim:set ts=2 sw=2: -->
