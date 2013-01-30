<?
	session_start();
	session_destroy();
	if ($_SERVER["REQUEST_METHOD"] == "POST")
	{
		echo '<pre>_POST:'; print_r( $_POST ); echo '</pre>';
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />

	<title>PUT TITLE HERE</title>

	<link rel="stylesheet" type="text/css" href="css/pcp.css" />

	<link rel="stylesheet" type="text/css" href="css/pcp-colors.css" />

	<link rel="stylesheet" type="text/css" href="css/2leveltab.css" />

	<style type="text/css">
		<!--.style2 {
			font-size: 16px;
			font-weight: bold;
		}
		.style4 {
			font-size: large;
			font-family: Georgia, "Times New Roman", Times, serif;
		}
		.style5 {
			font-size: large;
			font-family: Georgia, "Times New Roman", Times, serif;
		}
		.style6 {
			font-size: 14px;
			font-family: Georgia, "Times New Roman", Times, serif;
		}
		.style10 {	
			font-size: 16px;
			font-family: Georgia, "Times New Roman", Times, serif;
		}
		.mborder {
			border:1px #404040 double;
			border-top:1px #c0c0c0 double;
			border-right:1px #c0c0c0 solid;
		}
		.button {
			border: 1px solid #006;
			background: #ccf;
		}
		.button:hover {
			border: 1px solid #f00;
			background: #eef;
		}
		label {
	    display: block;
	    width: 135px;
	    float: right;
	    text-align: left;
		}
		.strikethru {
			text-decoration:line-through;
		}
		.bold {
			font-weight:bold;
		}
		-->
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
</head>

<body style="height:100%">
	<div id="mainContainer" style="height:100%">
		<center>
		<table id="mainTable" border="0" cellpadding="1" cellspacing="0" height="100%">
			<tbody>
				<tr>
					<td>!--#include virtual="includes/PCPbanner-row.html" --</td>
				</tr>
				<tr>
					<td>!--#include virtual="includes/PCPmenu-row2.html" --</td>
				</tr>
				<tr  class="mainTable-bg">
					<td colspan=3 id="mainContent" style="height:100%; margin-top:0pt; padding-top:0pt" valign="top" align="center;">
						<div style="text-align:center; padding:0; padding-top:10px;">
							<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td align="center">
											<table width="826" cellpadding="20"  border="0" bordercolor="blue">
											  <tbody>
													<tr class="mainTable-bg">
														<td align="center">
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=4th Annual Cabaret Dinner Show&NewOrder=true&paypalMode=demo&debuggingOn=1" target="_blank">Order Cabaret Tickets (PayPal demo and debuggingOn)<a><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Three Penny Opera&NewOrder=true&paypalMode=demo&debuggingOn=1" target="_blank">Order Three Penny Opera Tickets (PayPal demo and debuggingOn)<a><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Three Penny Opera&NewOrder=true&paypalMode=demo" target="_blank">Order Three Penny Opera Tickets (PayPal demo)<a><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Three Penny Opera&NewOrder=true&paypalMode=sandbox&debuggingOn=1" target="_blank">Order Three Penny Opera Tickets (PayPal Sandbox and debuggingOn)<a><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Three Penny Opera&NewOrder=true&paypalMode=sandbox" target="_blank">Order Three Penny Opera Tickets (PayPal Sandbox)<a><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Three Penny Opera&NewOrder=true&paypalMode=live" target="_blank">Order Three Penny Opera Tickets (PayPalLive)<a><br /><br /><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Next to Normal&NewOrder=true&paypalMode=demo&debuggingOn=1" target="_blank">Order Next to Normal Tickets (PayPal demo and debuggingOn)<a><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Next to Normal&NewOrder=true&paypalMode=demo" target="_blank">Order Next to Normal Tickets (PayPal demo)<a><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Next to Normal&NewOrder=true&paypalMode=sandbox&debuggingOn=1" target="_blank">Order Next to Normal Tickets (PayPal Sandbox and debuggingOn)<a><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Next to Normal&NewOrder=true&paypalMode=sandbox" target="_blank">Order Next to Normal Tickets (PayPal Sandbox)<a><br /><br />
															<a href="http://pinoleplayers.org/buyStuffPP.php?productCategory=Next to Normal&NewOrder=true&paypalMode=live" target="_blank">Order Next to Normal Tickets (PayPalLive)<a><br /><br /><br /><br />

															<form name="BuyN2N" method="post" action="buyStuffPP.php" target="_blank">
																<input type="hidden" name="productCategory" value="Next to Normal" />
																<input type="submit" class="button" value="Order Next to Normal Tickets" />
															</form>
															<form name="Patron" method="post" action="buyStuffPP.php" target="_blank">
																<input type="hidden" name="productCategory" value="Patron" />
																<input type="submit" class="button" value="Become a Patron of the Arts" />
															</form>
															<form name="NewSeats" method="post" action="buyStuffPP.php" target="_blank">
																<input type="hidden" name="productCategory" value="New Theatre Seats" />
																<input type="submit" class="button" value="Sponsor a New Theatre Seat" />
															</form>
															<form name="ccoptin" action="http://visitor.r20.constantcontact.com/d.jsp" target="_blank" method="post">
																<strong>Keep up-to-date with the<br />Pinole Community Playhouse:<br /><big>Join Our Email List!</big><br />
																Email:</strong>
																<input type="text" name="ea" size="20" value="" style="font-family: Arial; font-size:12px; border:1px solid #999999;">
																&nbsp;
																<input type="submit" name="go" value="Join" class="submit"  style="font-family:Arial,Helvetica,sans-serif; font-size:11px;">
																<input type="hidden" name="llr" value="kiqdpwdab">
																<input type="hidden" name="m" value="1103486894346">
																<input type="hidden" name="p" value="oi">
															</form>
														</td>
													</tr>
													<tr class="mainTable-bg">
														<td align="center">
<?
readfile("includes/ConstantContact-bubble.html");
?>
												</tbody>
											</table>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</td>
				</tr>
				<tr>
					<td>!--#include virtual="includes/PCPfooter-row.html" --</td>
				</tr>
			</tbody>
		</table>
		</center>
	</div>
</body>
</html>
<!-- vim:set ts=2 sw=2: -->
