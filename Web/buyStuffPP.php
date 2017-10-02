<?
	ini_set("include_path", '/home/pcp/php:/home/pcp/phpinc:' . ini_get("include_path")  );
	session_set_cookie_params(0);
	session_start();

	require_once('class.inputfilter_clean.php5');
	require_once('EmailAddressValidator.php');

	// include Pear libraries
	require_once('Mail.php');
	require_once('Mail/mime.php');

	$buyStuffPP = "buyStuffPP.php";
	
	$GlobalParams = array();
	
	class UnknownProductCategoryException extends Exception { }

	$mysql_db = 'pcp_cart';
	$mysql_user = 'pcp_ticket';
	$mysql_passwd = 'sJpAm9fY5S';
	$mysql_conn;

	$metaHTTPequiv = <<<EOT
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
EOT;

	$pageTitle = "PCP Box Office";

	$pageContent = "";

	$orderFirstName = "";
	$orderLastName = "";
	$orderAddress = "";
	$orderCity = "";
	$orderState = "CA";
	$orderZip = "";
	$orderEmail = "";
	$orderPhone = "";
	$orderItems = array();
	$orderSmallDonationAmount = 0;
	$orderPaymentMethod = "paymentPayPal";
	$orderSpecReq = "";

	$priceId2quantity = array();

	$formVarsLoaded = false;
	
	$formErrors = array();

	$col1Width = "120px";
	$col3Width = "175px";

	date_default_timezone_set("America/Los_Angeles");
	
// Connect to mysql and select db
	$mysql_conn = mysql_connect('localhost', $mysql_user, $mysql_passwd);
	if ( ! $mysql_conn )
	{
		internalError( "can't connect to mysql: " . mysql_error() );
		exit;
	}
	if ( ! mysql_select_db($mysql_db, $mysql_conn) )
	{
		internalError( "can't use {$mysql_db}: " . mysql_error() );
		exit;
	}

	LoadGlobalParams();

	$debuggingOn	= getREQUESTorSESSION( "debuggingOn", "0" );		// 0=live, 1=debug for quick testing
	$debugFlags		= getREQUESTorSESSION( "debugFlags", "none" );	// "none", "all", haven't figured out the other flags.
	$paypalMode		= getREQUESTorSESSION( "paypalMode", "live" );	// "live" or "sandbox" or "demo"

if( $debuggingOn )
{
	dumpEnvironmentVars();
}

	$pageContent = "";
	
	if ( isset($_REQUEST["PayPalReturn"]) )
	{
		$_SESSION["orderStep"] = "showConfirm";
		$_SESSION["emailSent"] = "";
	}
	else if ( isset($_REQUEST["DebugPayPalReturn"] ) )
	{
		$_SESSION["orderStep"] = "showConfirm";
		$_SESSION["emailSent"] = "";
	}
	else if ( $_REQUEST["productCategory"] == "" )
	{ // whatever...
		#$_REQUEST["productCategory"] = SelectCategory();
		$_SESSION["orderStep"] = "displayForm";
		//$_SESSION["emailSent"] = "";
	}
	else if ( isset( $_REQUEST["NewOrder"] ) )
	{
		session_unset();
		// put back debugging parameters
		$debuggingOn	= getREQUESTorSESSION( "debuggingOn", "0" );		// 0=live, 1=debug for quick testing
		$debugFlags		= getREQUESTorSESSION( "debugFlags", "none" );	// "none", "all", haven't figured out the other flags.
		$paypalMode		= getREQUESTorSESSION( "paypalMode", "live" );	// "live" or "sandbox" or "demo"

		$_SESSION["orderStep"] = "displayForm";
		$_SESSION["emailSent"] = "";
	}
	else if ( isset($_REQUEST["changeOrder"] ) )
	{
		$pageTitle = "Change Your Order";
		$_SESSION["orderStep"] = "displayForm";
		//$_SESSION["emailSent"] = "";
	}
	else if ( isset($_REQUEST["placeOrder"] ) )
	{
		$_SESSION["orderStep"] = "showConfirm";
		//$_SESSION["emailSent"] = "";
	}
	else if ( ! isset($_SESSION["orderStep"]) )
	{
		$_SESSION["orderStep"] = "displayForm";
		//$_SESSION["emailSent"] = "";
	}
	LoadFormVars();

	$categoryDBrow = getCategoryDBrow( $orderCategoryName );

	switch($_SESSION["orderStep"])
	{
		case "displayForm":
			try {
				$pageContent .= DisplayProductsForm( $orderCategoryName );
			}
			catch (UnknownProductCategoryException $unknownProductCategory) {
				$pageContent = DisplayUnknownProductCategoryPage( $unknownProductCategory );
				break;
			}
			$_SESSION["orderStep"] = "validateOrder";
			break;

		case "validateOrder":
			$formErrorCount = ValidateOrder();

			if ( $formErrorCount > 0)
			{
				try {
					$pageContent .= DisplayProductsForm( $orderCategoryName );
				}
				catch (UnknownProductCategoryException $unknownProductCategory) {
					$pageContent = DisplayUnknownProductCategoryPage( $unknownProductCategory );
					break;
				}
			}
			else
			{
				$_SESSION["orderStep"] = "showConfirm";
				$pageContent .= ShowOrder( "Review" );
			}
			break;

		case "showConfirm":
			$pageContent .= ShowOrder( "Confirm" );
			obliterateSession();
			break;

		default:
			internalError( "unknown orderStep ({$_SESSION["orderStep"]}" );
			exit; // tho internalError never returns and exits, we'll just make sure
	}

function debugFlagSet( $flags="" )
{
	global	$GlobalParams,
					$debuggingOn,
					$debugFlags,
					$paypalMode;

	if ( ! $debuggingOn || $debugFlags =="none" )
	{
		return false;
	}
	else if ( $debugFlags == "all" || $flags == "" )
	{
		return true;
	}
	else
	{
		return preg_match( "/{$flags}/", $debugFlags );
	}
}

function showSESSIONandDebugVars( $tag )
{
	global	$GlobalParams,
					$debuggingOn,
					$debugFlags,
					$paypalMode;

	echo "<pre>{$tag}: SESSION: "; echo print_r( $_SESSION ); echo "</pre>";
	echo "<pre>check debug flags: debuggingOn: {$debuggingOn}| debugFlags: {$debugFlags}| paypalMode: {$paypalMode}|</pre>\n";
}

function internalError( $errorMessage )
{
	global	$GlobalParams,
					$debuggingON,
					$paypalMode;

	echo "<pre>";
	echo "You have encountered a problem with the order process.\n";
	echo "It was caused by something the webmaster did. Or didn't do.\n";
	echo "We apologize for any inconvenience. He will be sternly\n";
	echo "admonished. Sternly! And then he will undo whatever he did.\n";
	echo "Or do whatever it was he didn't do.\n\n";
	echo "In the mean time, please call our boxoffice at "
				. makeNonBreaking($GlobalParams["PCPboxofficePhoneNumber"])
				. ".\n\n\n";

	// send an email with the $errorMessage and the rest of the stuff
	echo "{$errorMessage}\n";
	echo "</pre>";

	exit;
}

function dumpEnvironmentVars()
{
	echo '<pre>dumpEnvironmentVars: ';
	echo "    REQUEST_METHOD: {$_SERVER["REQUEST_METHOD"]}|\n";
	echo "    debuggingOn: {$_REQUEST["debuggingOn"]}| debugFlags: {$_REQUEST["debugFlags"]}|\n";
	echo "    paypalMode: {$_REQUEST["paypalMode"]}|\n";
	echo "    productCategory: {$_REQUEST["productCategory"]}|\n";
	echo "    orderStep: {$_SESSION["orderStep"]}|\n";
	echo "    changeOrder: {$_REQUEST["changeOrder"]}|\n";
	echo "    placeOrder:{$_REQUEST["placeOrder"]}|\n";
	echo "    session_name: "; echo session_name(); echo "| session_id: "; echo session_id(); echo "| SID: {$SID}|\n";
//echo '_SERVER:'; print_r( $_SERVER );
	echo '_REQUEST:'; print_r( $_REQUEST );
	echo '_SESSION:'; print_r( $_SESSION );
//echo 'GLOBALS:'; print_r( $GLOBALS );
	echo '</pre>';
}

function wipeSESSIONvars()
{
	$sessionName = session_name();
	foreach ( $_SESSION as $key )
	{
		if ( $key != $sessionName )
		{
			unset( $GLOBALS["$_SESSION"][$key] );
		}
	}
}

function obliterateSession()
{
	global	$GlobalParams,
					$debuggingON,
					$paypalMode;

if ($debuggingOn==1)
	{echo "<pre>entering obliterateSession</pre>";}

	// Unset all of the session variables.
	$_SESSION = array();

	// also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get("session.use_cookies"))
	{
		$params = session_get_cookie_params();
		setcookie( session_name(), '', time() - 42000,
								$params["path"], $params["domain"],
									$params["secure"], $params["httponly"] );
	}
	// Finally, destroy the session.
	session_destroy();

if ($debuggingOn==1)
	{echo "<pre>leaving obliterateSession</pre>";}
}

function LoadGlobalParams()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$mysql_conn;

	$parameterQuery = <<<EOQ
			SELECT	parameter.parameterKey,
							parameter.parameterValue
				FROM	GlobalParameters AS parameter;
EOQ;
	$parameterResult = mysql_query($parameterQuery,$mysql_conn);
	if (!$parameterResult)
	{
		internalError( "Could not successfully run query ({$parameterQuery}) from DB: " . mysql_error() );
	}
	while ($parameterDBrow = mysql_fetch_object($parameterResult))
	{
		$GlobalParams[$parameterDBrow->parameterKey] = $parameterDBrow->parameterValue;
	}

if($debuggingOn==1)
	{echo '<pre>leaving LoadGlobalParams</pre>';}

}

function is_digits($element)
{
  return ! preg_match( "/[^0-9]/", $element);
}

function mainTableSeparatorRowHTML( $height=4 )
{
	$separatorHTML = <<<EOT
			<tr style="height:1px; padding:0; font-size:{$height}px; background-color: #7286A7;">
				<td></td>
				<td colspan="5"></td>
			</tr>
EOT;
	return $separatorHTML;
}

function getREQUESTorSESSION( $key, $default="" )
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode;

if ( $debuggingON )
		{echo "<pre>entering getREQUESTorSESSION: key:{$key}| REQUEST:{$_REQUEST[$key]}| SESSION:{$_SESSION[$key]}| default:{$default}|\n</pre>";}

	if ( isset( $_REQUEST[$key] ) )
	{
		$value = $_SESSION[$key] = stripslashes( $_REQUEST[$key] );
	}
	else if ( isset( $_SESSION[$key] ) )
	{
		$value = stripslashes( $_SESSION[$key] );
	}
	else
	{
		$value = $_SESSION[$key] = "";
	}
	if ( $value == "" && $default != "" )
	{
		$value = $_SESSION[$key] = $default;
	}
if ( $debuggingON )
	{echo "<pre>leaving getREQUESTorSESSION: value:{$value}|\n</pre>";}
	return $value;
}

function getSelectionColHdr( $categoryType )
	{
		global	$debuggingOn;

		switch ( $categoryType )
		{
			case "show":
				$selectionColHdr = "Specify the Number of Tickets You Desire for ANY Performances You Wish to Attend";
				break;

			case "donation":
			case "sponsor":
				$selectionColHdr = "Choose the Amount You Wish to Donate";
				break;

			case "event":
				$selectionColHdr = "Specify the Number of Tickets You Desire for ANY Events You Wish to Attend";
				break;

			default:
				$selectionColHdr = "Specify the Number of " . $categoryType . " You Desire";
				break;
		}
if ( $debuggingOn )
	{echo "<pre>leaving getSelectionColHdr: categoryType:{$categoryType}|selectionColHdr:{$selectionColHdr}|\n</pre>";}
		return $selectionColHdr;
	}

function getProductColHdr( $categoryType )
	{
		global	$debuggingOn;

		switch ( $categoryType )
		{
			case "show":
				$productColHdr = "Available Performances";
				break;

			case "donation":
			case "sponsor":
				$productColHdr = "Donation Levels";
				break;

			case "event":
				$productColHdr = "Available Events";
				break;

			default:
				$productColHdr = $categoryType;
				break;
		}
if ( $debuggingOn )
	{echo "<pre>leaving getProductColHdr: categoryType:{$categoryType}|productColHdr:{$productColHdr}|\n</pre>";}
		return $productColHdr;
	}

function getOrderMessage( $categoryType, $categoryOrderMessageFn )
	{
		global	$debuggingOn;

		$orderMessage = <<<EOT
				<div class="textBlock" style="text-align:center; font-weight:bold">
EOT;

		switch ( $categoryType )
		{
			case "show":
			case "event":
				$orderMessage .= <<<EOT
				All seats reserved.<br />
				All reservations must be prepaid.<br />
				Ticket reservation is secured once payment is received.<br />
				TICKETS WILL BE HELD AT THE DOOR.<br />
				ALL SALES FINAL<br /><br />
				Box office and lobby open one hour before show time.<br />
				Doors to the theatre open 30 minutes before show time.
EOT;
				$assignedReservedSeating = <<<EOT
				<br />
				Assigned Reserved Seating
EOT;
				$orderMessage .= $assignedReservedSeating;

				break;

			case "donation":
			case "sponsor":
				$orderMessage .= <<<EOT
					<h3 style="text-align:center;">Thank You for your Donation</h3>
					<div style="display:block; width:75%; text-align:center;">
						Our productions would never make it to the stage without the generous support of our donors and theatre-goers.<br /><br />
						Remember, we are a certified 501(c)(3) non-profit organization.<br />
						Your contributon is tax-deductible.
					</div>
EOT;
				break;

			default:
				$orderMessage .= $categoryType;
				break;
		}
		if ( $categoryOrderMessageFn && file_exists( $categoryOrderMessageFn ) && is_file( $categoryOrderMessageFn ) )
		{
			$orderMessage .= file_get_contents( $categoryOrderMessageFn );
		}

		$orderMessage .= "</div>\n";

if ( $debuggingOn )
	{echo "<pre>leaving getOrderMessage: categoryType:{$categoryType}|categoryOrderMessageFn:{$categoryOrderMessageFn}|orderMessage:{$orderMessage}|\n</pre>";}

		return $orderMessage;
	}

function showFormVars()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$orderCategoryName,
					$lastFormRowNo,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderHandlingFee,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderSpecReq;

	echo "<pre>showFormVars:\n";
	echo "    orderCategoryName: {$orderCategoryName}\n";
	echo "    lastFormRowNo: {$lastFormRowNo}\n";
	echo "    orderFirstName: {$orderFirstName}\n";
	echo "    orderLastName: {$orderLastName}\n";
	echo "    orderAddress: {$orderAddress}\n";
	echo "    orderCity: {$orderCity}\n";
	echo "    orderState: {$orderState}\n";
	echo "    orderZip: {$orderZip}\n";
	echo "    orderEmail: {$orderEmail}\n";
	echo "    orderPhone: {$orderPhone}\n";
	echo "    orderHandlingFee: {$orderHandlingFee}\n";
	echo "    orderSmallDonationAmount: {$orderSmallDonationAmount}\n";
	echo "    orderPaymentMethod: {$orderPaymentMethod}\n";
	echo "    orderPaymentInstructions: {$orderPaymentInstructions}\n";
	echo "    orderSpecReq: {$orderSpecReq}\n";
	echo "</pre>";
}

function LoadFormVars()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$orderCategoryName,
					$lastFormRowNo,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderItems,
					$orderHandlingFee,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderSpecReq,
					$oneSelectedPriceId,
					$priceId2quantity,
					$mysql_conn,
					$formVarsLoaded;

if($debuggingOn==1)
	{echo '<pre>entering LoadFormVars</pre>';}

	if ( ! $formVarsLoaded )
	{
		$formVarsLoaded = true;

		$myFilter = new InputFilter();

		$_REQUEST = $myFilter->process($_REQUEST);

		$productCategory		= getREQUESTorSESSION( "productCategory" );
//	$orderCategoryName	= getREQUESTorSESSION( "orderCategoryName" );
		$orderCategoryName	= $productCategory;
		$orderFormRowNo			= getREQUESTorSESSION( "orderFormRowNo" );
		$orderFirstName			= getREQUESTorSESSION( "orderFirstName" );
		$orderLastName			= getREQUESTorSESSION( "orderLastName" );
		$orderAddress				= getREQUESTorSESSION( "orderAddress" );
		$orderCity					= getREQUESTorSESSION( "orderCity" );
		$orderState					= getREQUESTorSESSION( "orderState", "CA" );
		$orderZip						= getREQUESTorSESSION( "orderZip" );
		$orderEmail					= getREQUESTorSESSION( "orderEmail" );
		$orderPhone					= getREQUESTorSESSION( "orderPhone" );
		$orderPaymentMethod = getREQUESTorSESSION( "orderPaymentMethod", "paymentPayPal" );
		$orderPaymentInstructions = getREQUESTorSESSION( "orderPaymentInstructions" );
		$orderSpecReq				= getREQUESTorSESSION( "orderSpecReq" );
		$orderHandlingFee		= getREQUESTorSESSION( "orderHandlingFee" );
		$orderSmallDonationAmount = sprintf( "%.2f", getREQUESTorSESSION( "orderSmallDonationAmount", "0" ) );
		$oneSelectedPriceId	= getREQUESTorSESSION( "oneSelectedPriceId", "0" );

		$lastFormRowNo			= getREQUESTorSESSION( "lastFormRowNo" );

	  $orderItemNo = 0;

		$priceId2quantity = array();

		for ( $i=1; $i <= intval($lastFormRowNo); ++$i )
		{
			$priceIDi = getREQUESTorSESSION( "priceId_{$i}" );
			$quantity = getREQUESTorSESSION( "quantity_{$i}", "0" );
			//$quantity = sprintf( "%d", getREQUESTorSESSION( "quantity_{$i}", "0" ) );

			if ( $priceIDi == "" || $priceIDi == "unavailable" )
			{
				continue;
			}
			else
			{
				$priceId2quantity[ $priceIDi ] = $quantity;

				if ( $quantity == "" || $quantity == "0" )
				{
					continue;
				}
			}

			if ( $oneSelectedPriceId != 0 && $priceIDi != $oneSelectedPriceId )
			{
				continue;
			}
			++$orderItemNo;

			$orderItems[$orderItemNo] = array(
																				"priceId" => $priceIDi,
																				"quantity" => $quantity,
																				"itemInfo" => getOrderItemInfo( $priceIDi )
																				);
		}
	}
	else
	{
if($debuggingOn==1)
	{echo '<pre>LoadFormVars already called</pre>';}
	}
if($debuggingOn==1)
	{
	echo '<pre>leaving LoadFormVars: orderItems: ';
		print_r($orderItems);
			echo 'priceId2quantity: ';
				print_r($priceId2quantity);
					echo '</pre>';
	showFormVars();
	}
}
function getOrderItemInfo( $priceID )
	{
	global	$debuggingOn,
					$orderCategoryName,
					$lastFormRowNo,
					$oneSelectedPriceId,
					$priceId2quantity,
					$mysql_conn;

			$productQuery = <<<EOQ
					SELECT	category.categoryName,
									category.categoryType,
									category.categoryLOGOfn,
									category.categoryProductionCompany,
									category.categoryQuantityInput,
									category.categorySelectionType,
									category.categorySalesBlackout,
									product.productId,
									product.categoryId,
									product.productDateTime,
									product.productName,
									product.productNameIsDateTime,
									product.productSoldOut,
									product.productVisible,
									product.productPreSold,
									product.productTwofer,
									product.productIsAcomment,
									price.priceId,
									price.priceClass,
									price.classPrice,
									price.priceUnits
						FROM ProductCategory AS category
									INNER JOIN Product AS product ON product.categoryId = category.categoryId
									INNER JOIN ProductPrice AS price ON price.productId = product.productId
						WHERE price.priceId = {$priceID}
						ORDER BY category.categoryName ASC, product.productDateTime ASC, price.classPrice DESC;
EOQ;
			$productResult = mysql_query($productQuery,$mysql_conn);
			if (!$productResult)
			{
				echo '<pre>' . "Could not successfully run query ({$productQuery}) from DB: " . mysql_error() . '</pre>';
				exit;
			}
			$productNumRows = mysql_num_rows($productResult);
			if ( $productNumRows == 0 )
			{
				# error: no entry for priceId = {$_REQUEST["priceId_{$orderItemIndex}"]}
				echo '<pre>' . "No DB entry for priceId == {$priceId}" . '</pre>';
				exit;
			}

			$itemInfo = mysql_fetch_object( $productResult );

			$orderCategoryName = $itemInfo->categoryName;
			if ( $orderCategoryName	!= getREQUESTorSESSION( "productCategory" ) )
			{
				echo '<pre>getOrderItemInfo: REQUESTorSESSION productCategory ('
							. getREQUESTorSESSION( "productCategory" )
							. ") != DB (priceID: {$priceID}) categoryName ({$orderCategoryName})"
							. '</pre>';
				exit;
			}
			mysql_free_result($productResult);

			return $itemInfo;
	}

function buildProductDescription( $itemInfo )
{
	global	$GlobalParams,
					$debuggingON,
					$paypalMode;

if($debuggingOn==1)
	{echo "<pre>entering buildProductDescription: itemInfo->productName: "; print_r($itemInfo->productName); echo "|</pre>";}

	if ( $itemInfo->productNameIsDateTime )
	{
		$productDesc = date("l, M j - g:ia",strtotime($itemInfo->productDateTime));
	}
	else
	{
		$productDesc = $itemInfo->productName;
	}
	$noInput = 0;

	if ($itemInfo->productComment == 1)
	{
		$noInput = 1;
	}
	elseif ($itemInfo->productSoldOut == 1)
	{
		$productDesc = "<span class=\"strikethru\">{$productDesc}</span> SOLD OUT";
		$noInput = 1;
	}
	elseif ($itemInfo->productPreSold == 1)
	{
		$productDesc = "<span class=\"strikethru\">{$productDesc}</span> Pre-Sold - Call "
										. makeNonBreaking($GlobalParams["PCPboxofficePhoneNumber"]) . " for Info";
		$noInput = 1;
	}
	elseif ($currUnixTime > ($productUnixTime - ($itemInfo->categorySalesBlackout * 60 * 60)))
	{
		$productDesc = "<span class=\"strikethru\">{$productDesc}</span> - Too Late for Online Order - Call "
										. makeNonBreaking($GlobalParams["PCPboxofficePhoneNumber"]) . " to Order";
		$noInput = 1;
	}
	elseif ($itemInfo->productTwofer == 1)
	{
		$productDesc .= " (" . makeNonBreaking("2-for-1") . ")";
	}

if($debuggingOn==1)
	{echo "<pre>leaving buildProductDescription: productDesc: "; print_r($productDesc); echo "| noInput: "; print_r($noInput); echo "|</pre>";}

	return array( $productDesc, $noInput );
}

function ValidateOrder()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$categoryDBrow,
					$orderCategoryName,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCustInfoErrors,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderHandlingFee,
					$orderSpecReq,
					$oneSelectedPriceId,
					$mysql_conn,
					$formErrors,
					$privatekey;

if($debuggingOn==1)
	{echo "<pre>entering ValidateOrder</pre>";}

	$orderCustInfoErrors = "";

	//$formErrors[] = "testing ValidateOrder";

	if ( $categoryDBrow->categorySelectionType == "One" )
	{
		if ( !isset($oneSelectedPriceId) || $oneSelectedPriceId == 0 )
		{
		$formErrors[] = "Please choose one item.";
		$orderCustInfoErrors .= "I";
		}
	}
	else
	{
		if ( ! isset($orderItems) || count($orderItems) == 0)
		{
			$formErrors[] = "Please specify a quantity for at least one item.";
			$orderCustInfoErrors .= "I";
		}
		else
		{
			foreach ( $orderItems as $orderItemNo => $orderItem )
			{
				if ( ! is_digits( $orderItem["quantity"] ) )
				{
					$formErrors[] = "Please use numbers to specify quantities of items.";
					$orderCustInfoErrors .= sprintf( "%02d", $orderItemNo );
				}
			}
		}
	}

	if ($orderFirstName . $orderLastName == "")
	{
		$formErrors[] = "Please enter your first and last names.";
		$orderCustInfoErrors .= "FL";
	}
	else
	{
		if ($orderFirstName == "")
		{
			$formErrors[] = "Please enter your first name.";
			$orderCustInfoErrors .= "F";
		}
		if ($orderLastName == "")
		{
			$formErrors[] = "Please enter your last name.";
			$orderCustInfoErrors .= "L";
		}
	}
	if ($orderEmail == "")
	{
		$formErrors[] = "Please enter your email address.";
		$orderCustInfoErrors .= "E";
	}
	else
	{
		$validator = new EmailAddressValidator;
		if ($orderEmail != "" && ! $validator->check_email_address($orderEmail))
		{
			$formErrors[] = "Your email address is not in a recognized format.";
			$orderCustInfoErrors .= "E";
		}
	}
	if ($orderPhone == "" || strlen( preg_replace( "/[^0-9]/", "", $orderPhone, -1 ) ) != 10 )
	{
		$formErrors[] = "Please enter your preferred contact phone number, including area code.";
		$orderCustInfoErrors .= "P";
	}
	if ($orderAddress == "")
	{
		$formErrors[] = "Please enter your street address.";
		$orderCustInfoErrors .= "A";
	}
	if ($orderCity == "")
	{
		$formErrors[] = "Please enter your city.";
		$orderCustInfoErrors .= "C";
	}
	if ($orderState == "")
	{
		$formErrors[] = "Please enter your state.";
		$orderCustInfoErrors .= "S";
	}
	if ($orderZip == "")
	{
		$formErrors[] = "Please enter your zip code.";
		$orderCustInfoErrors .= "Z";
	}
	else
	{
		if ( preg_match( "/^[0-9]{5}(-[0-9]{4}){0,1}$/", preg_replace( "/[ 	]/", "", $orderZip, -1 ) ) != 1 )
		{
			$formErrors[] = "Please enter your zip code as ##### or #####-####.";
			$orderCustInfoErrors .= "Z";
		}
	}
	if ($orderPaymentMethod == "")
	{
		$formErrors[] = "Please select a payment method.";
	}

if($debuggingOn==1)
	{echo "<pre>leaving ValidateOrder: orderCustInfoErrors: {$orderCustInfoErrors}</pre>";}

	return count($formErrors);

//	$pageContent; print_r($_REQUEST,1);
//	$pageContent; print_r($formErrors,1);
}

function calculateOrderTotalAmount()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCategoryName,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderSpecReq,
					$oneSelectedPriceId,
					$orderMessage,
					$orderHandlingFee,
					$pageTitle,
					$col1Width,
					$col3Width,
					$mysql_conn;

if($debuggingOn==1)
	{echo "<pre>entering calculateOrderTotalAmount</pre>";}
	
	$orderTotalAmount = 0;

	for ( $i=1; $i <= count($orderItems); ++$i )
	{
		$orderItem = &$orderItems[$i];
		$orderItemInfo = $orderItem["itemInfo"];

		if ( $orderItemInfo->productTwofer )
		{
			$orderItem["amount"] = intval($orderItem["quantity"] / 2) * $orderItemInfo->classPrice; 
			$orderItem["amount"] += intval($orderItem["quantity"] % 2) * $orderItemInfo->classPrice; 
		}
		else
		{
			$orderItem["amount"] = $orderItem["quantity"] * $orderItemInfo->classPrice; 
		}
		$orderTotalAmount += $orderItem["amount"];
		$orderItem["amount"] = sprintf( "%.2f", $orderItem["amount"] ); 
if($debuggingOn==1)
	{echo "<pre>calculateOrderTotalAmount: 2fer: {$orderItemInfo->productTwofer}| [quantity]: {$orderItem["quantity"]}| ->classPrice: {$orderItemInfo->classPrice}| orderTotalAmount: {$orderTotalAmount}| orderItem[amt]: {$orderItem["amount"]}|</pre>";}
	}
if($debuggingOn==1)
	{echo "<pre>calculateOrderTotalAmount: orderHandlingFee: {$orderHandlingFee} orderSmallDonationAmount: {$orderSmallDonationAmount}</pre>";}

	$orderTotalAmount += ($orderHandlingFee + $orderSmallDonationAmount);
	$orderTotalAmount = sprintf("%.2f", $orderTotalAmount);

if($debuggingOn==1)
	{echo "<pre>leaving calculateOrderTotalAmount: orderTotalAmount: "; print_r($orderTotalAmount); echo "|</pre>";}

	return $orderTotalAmount;
}

function makeNonBreaking( $str )
{ // " " with nbsp, "-" with non-breaking dash
	return preg_replace( "/ /", "&nbsp;", preg_replace( "/-/", "&#8209;", $str ) );
}

function splitPhoneNumber( $phoneNumber )
{
	if ( preg_match( "/^([0-9]{3})([0-9]{3})([0-9]{4})$/", preg_replace( "/[^0-9]/", "", $phoneNumber ), $matches ) )
	{
		return array( $matches[1], $matches[2], $matches[3] );
	}
	return array( FALSE, 0, 0 );
}

function getBuyButtonLabelHTML( $orderPaymentMethod, $categoryType )
{
	global	$debuggingOn;

	if ( $orderPaymentMethod == "paymentPayPal" )
	{
		if ( $categoryType == "sponsor" )
		{
			$buyButtonLabel = "Donate Now";
			$buyButtonHTML = "<img src=\"/images/donate.png\" height=\"25\" alt=\"{$buyButtonLabel}\" style=\"vertical-align:text-bottom;\" />";
		}
		else
		{
			$buyButtonLabel = "Pay Now";
			$buyButtonHTML = "<img src=\"/images/paynow.png\" height=\"25\" alt=\"{$buyButtonLabel}\" style=\"vertical-align:text-bottom;\" />";
		}
	}
	else
	{
		if ( $categoryType == "sponsor" )
		{
			$buyButtonLabel = "Donate Now";
		}
		else
		{
			$buyButtonLabel = "Place Order";
		}
		$buyButtonHTML = "<b class=\"button\" style=\"font-size:small; vertical-align:5px;\">{$buyButtonLabel}</b>";
	}
	return array( $buyButtonLabel, $buyButtonHTML );
}

function ShowOrder( $reviewOrConfirm )
{
	global	$GlobalParams,
					$buyStuffPP,
					$debuggingOn,
					$paypalMode,
					$categoryDBrow,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCategoryName,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderSpecReq,
					$oneSelectedPriceId,
					$orderMessage,
					$orderHandlingFee,
					$pageTitle,
					$col1Width,
					$col3Width,
					$mysql_conn;

if($debuggingOn==1)
	{echo "<pre>entering ShowOrder: reviewOrConfirm: {$reviewOrConfirm}|</pre>";}
	
	switch ( $reviewOrConfirm )
	{
		case "Review":
			$pageTitle = "Review Order";
			list( $buyButtonLabel, $buyButtonHTML ) = getBuyButtonLabelHTML( $orderPaymentMethod, $categoryDBrow->categoryType );
			if ( $orderPaymentMethod == "paymentPayPal" )
			{
				if ( $paypalMode == "live" )
				{
					$payNowAction = "https://www.paypal.com/cgi-bin/webscr";
					$paypalBusinessEmail = $GlobalParams["PayPalBusinessEmail"];
				}
				else
				{
					$payNowAction = "https://www.sandbox.paypal.com/cgi-bin/webscr";
					$paypalBusinessEmail = $GlobalParams["TestPayPalBusinessEmail"];
				}
			}
			else
			{
				$payNowAction = $buyStuffPP;
			}
			$introText = <<<EOT
				<p>
					<b style="font-size:large;">PLEASE REVIEW YOUR ORDER</b><br /><br />
					If we got it right, click the
					{$buyButtonHTML}
					button at the bottom right.<br /><br />
					If you need to make any changes, click on the
					<b class="button" style="font-size:small; vertical-align:5px;">Change Order</b>
					button at the bottom left.
				</p>
EOT;
			break;
		default:
			echo "<pre>ShowOrder: unknown value for reviewOrConfirm: {$reviewOrConfirm}</pre>\n";
			$reviewOrConfirm = "Confirm";
			// NOTE that we are falling thru
		case "Confirm":
			$pageTitle = "Order Confirmation";
			$introText = "<p>\n";
			if ( $orderPaymentMethod == "paymentPayPal" )
			{
				if ( isset( $_REQUEST["DebugPayPalReturn"] ) )
				{
					$introText .= <<<EOT
						THIS IS THE DEMO MODE FOR PAYPAL<br />
						In the live version, clicking on the PAY NOW button would have taken you to the PayPal site,
						where you would have provided your credit card or PayPal account info. When that transaction
						was complete, you would have clicked on a link that brought you to this page:<br /><br />
EOT;
				}
				$introText .= <<<EOT
					Your
					<img src="http://pinoleplayers.org/images/PayPal-LOGO-38h-72ppi.jpg" height="18" alt="PayPal" style="vertical-align:text-bottom;" />
					transaction is complete.
					<img src="http://pinoleplayers.org/images/PayPal-LOGO-38h-72ppi.jpg" height="18" alt="PayPal" style="vertical-align:text-bottom;" />
					will email you a receipt.<br /><br />
EOT;
			}
			$introText .= <<<EOT
					<b style="font-size:large;">THANK YOU FOR YOUR ORDER</b><br /><br />
					It has been submitted to our box office staff.
					We have also emailed a copy for your files.<br /><br />
					If you need to make any changes to your order, please call 
EOT;
			$introText .= makeNonBreaking($GlobalParams["PCPboxofficePhoneNumber"]) . ".";
			$introText .= <<<EOT
				</p>
				<p>
					You will be contacted ONLY if there is a problem with your order.
				</p>
EOT;
			break;
	}

	processPaymentMethod( $reviewOrConfirm );

	$orderTotalAmount = calculateOrderTotalAmount();

if($debuggingOn==1)
	{echo "<pre>ShowOrder: orderTotalCost: {$orderTotalAmount} orderItems:"; print_r($orderItems); echo '</pre>';}

	$pageContent = <<<EOT
		<table class="productTable" width="826" cellspacing="0" border="2" bordercolor="#7286A7">
			<tbody>
				<tr class="page-bg">
					<td colspan="6">
						<div class="textBlock" align="center">
								$introText
						</div>
					</td>
				</tr>
EOT;
	$pageContent .= mainTableSeparatorRowHTML();
	$pageContent .= <<<EOT
				<tr>
					<th>Name</th>
					<td colspan="5" style="padding-left:10px; font-weight:bold;">$orderFirstName $orderLastName</td>
				</tr>
				<tr>
					<th>Address</th>
					<td colspan="5" style="padding-left:10px; font-weight:bold;">
						$orderAddress<br />
						$orderCity, $orderState $orderZip
					</td>
				</tr>
				<tr>
					<th>Phone</th>
					<td colspan="5" style="padding-left:10px; font-weight:bold;">$orderPhone</td>
				</tr>
				<tr>
					<th>Email Address</th>
					<td colspan="5" style="padding-left:10px; font-weight:bold;">$orderEmail</td>
				</tr>
				<tr>
					<th>Special Requests</th>
					<td colspan="5" style="text-align:left; padding-left:10px;">
EOT;
	if ( $orderSpecReq )
	{
	$pageContent .= <<<EOT
						<form name="dummy">
							<textarea readonly name="orderSpecReq" cols="80" rows="4" wrap="physical"
									style="text-align:left; padding:2px 5px; border:3px solid #cccccc; font-size:medium;">{$orderSpecReq}</textarea>
						</form>
					</td>
				</tr>
EOT;
	}
	else
	{
	$pageContent .= <<<EOT
						None
					</td>
				</tr>
EOT;
	}
// display the orderItems
	$itemBecameUnavailable = false;
	$itemCount = count($orderItems);
	if ($itemCount == 0)
	{
		echo '<pre>ShowOrder: No items in order. How did we get here?</pre>\n';
	}
	if ( $paypalMode == "live" || $paypalMode == "sandbox" )
	{
		$payPalCartFormHTML = <<<EOT
			<form name="PayPalCartForm" method="POST" action="{$payNowAction}">
EOT;
	}
	else
	{
		$payPalCartFormHTML = <<<EOT
			<form name="PayPalCartForm" method="POST" action="{$buyStuffPP}">
				<input type="hidden" name="DebugPayPalReturn" value="1" />
EOT;
	}
	list( $orderAreaCode, $orderPhonePrefix, $orderPhone4Digits ) = splitPhoneNumber( $orderPhone );

	// maybe eventually
	// <input type="hidden" name="cancel_return" value="http://pinoleplayers.org/{$buyStuffPP}?PayPalCancel" />

	$payPalCartFormHTML .= <<<EOT
				<input type="hidden" name="business" value="{$paypalBusinessEmail}" />
				<input type="hidden" name="cmd" value="_cart" />
				<input type="hidden" name="upload" value="1" />
				<input type="hidden" name="image_url" value="http://pinoleplayers.org/images/PCP_Logo-150w-50h-150ppi.png" />
				<input type="hidden" name="no_shipping" value="1" />
				<input type="hidden" name="return" value="http://pinoleplayers.org/{$buyStuffPP}?PayPalReturn=1&debuggingOn={$debuggingOn}&paypalMode={$paypalMode}" />
				<input type="hidden" name="rm" value="2" />
				<input type="hidden" name="handling_cart" value="{$GlobalParams["HandlingFee-PayPal"]}" />
				<input type="hidden" name="first_name" value="{$orderFirstName}" />
				<input type="hidden" name="last_name" value="{$orderLastName}" />
				<input type="hidden" name="email" value="{$orderEmail}" />
				<input type="hidden" name="night_phone_a" value="{$orderAreaCode}" />
				<input type="hidden" name="night_phone_b" value="{$orderPhonePrefix}" />
				<input type="hidden" name="night_phone_c" value="{$orderPhone4Digits}" />
				<input type="hidden" name="address1" value="{$orderAddress}" />
				<input type="hidden" name="city" value="{$orderCity}" />
				<input type="hidden" name="state" value="{$orderState}" />
				<input type="hidden" name="zip" value="{$orderZip}" />
EOT;
	$orderHTML .= mainTableSeparatorRowHTML();
	$orderHTML .= <<<EOT
			<tr class="bold">
				<th style="width:{$col1Width};">&nbsp;</th>
				<th style="text-align:left; padding-left:10px; vertical-align:middle;">Your Selections</th>
				<th colspan="2" style="text-align:left; vertical-align:middle;">Quantity</th>
				<th style="text-align:center; vertical-align:middle; width:50px;">Price</th>
				<th style="text-align:center; vertical-align:middle; width:60px;">Amount</th>
			</tr>
EOT;

	$orderMessage = getOrderMessage( $categoryDBrow->categoryType, $categoryDBrow->categoryOrderMessageFn );

	for ( $i=1; $i <= $itemCount; ++$i )
	{
		$orderItem = $orderItems[$i];
		$orderItemInfo = $orderItem["itemInfo"];

		$productUnixTime = strtotime($orderItemInfo->productDateTime);
		
		list( $productDesc, $itemBecomeUnavailable ) = buildProductDescription( $orderItemInfo );

		$orderHTML .= "<tr>\n";

		if ( $i == 1 )
		{
			$orderHTML .=
				"<td rowspan={$itemCount} style=\"text-align:center; vertical-align:top; padding:5px;\">\n";
			if ( $orderItemInfo->categoryLOGOfn )
			{
				$orderHTML .=
					"<img src=\"http://pinoleplayers.org/{$orderItemInfo->categoryLOGOfn}\" width=\"130\" alt=\"{$orderItemInfo->categoryName} Logo\" /><br />\n";
			}
			$orderHTML .= <<<EOT
					<div style="font-weight:bolder;">{$orderItemInfo->categoryName}</div>
				</td>
EOT;
		}

		$orderHTML .= <<<EOT
				<td style="font-weight:bold; text-align:left; vertical-align:middle; padding-left:10px;"> {$productDesc}</td>
				</td>
				<td style="text-align:right; vertical-align:middle; width:20px;">{$orderItem["quantity"]}</td>
				<td style="text-align:left; vertical-align:middle; width:205px; padding-left:5px;">
					{$orderItemInfo->priceClass} {$orderItemInfo->priceUnits}
				</td>
				<td style="text-align:right; vertical-align:middle;">\${$orderItemInfo->classPrice}</td>
				<td style="text-align:right; vertical-align:middle;">\${$orderItem["amount"]}</td>
			</tr>
EOT;
		//												Next to Normal - Sat, Feb 9 - 8:00pm (2-for-1) - 		1 												Adult 												Tickets									@$ 20.00
		$payPalItemDescription = strip_tags( "{$orderItemInfo->categoryName} - {$productDesc} - {$orderItem["quantity"]} {$orderItemInfo->priceClass} {$orderItemInfo->priceUnits}@\${$orderItemInfo->classPrice}" );

if($debuggingOn==1)
	{echo "<pre>payPalItemDescription: "; print_r($payPalItemDescription); echo "|</pre>";}

		$payPalCartFormHTML .= <<<EOT
				<input type="hidden" name="item_name_{$i}" value="{$payPalItemDescription}" />
				<input type="hidden" name="amount_{$i}" value="{$orderItem["amount"]}" />
EOT;

	}
	if ( $orderSmallDonationAmount != "" && $orderSmallDonationAmount != 0 )
	{
		$orderHTML .= <<<EOT
			<tr>
				<td colspan="5" style="text-align:right; vertical-align:middle;">Thank You for your Donation</td>
				<td style="text-align:right; vertical-align:middle;">\${$orderSmallDonationAmount}</td>
			</tr>
EOT;
		$payPalCartFormHTML .= <<<EOT
					<input type="hidden" name="item_name_{$i}" value="Donation - Thank You" />
					<input type="hidden" name="amount_{$i}" value="{$orderSmallDonationAmount}" />
EOT;
	}
	if ( $categoryDBrow->categoryType == "sponsor" )
	{
		$payImg = "donate.png";
	}
	else
	{
		$payImg = "paynow.png";
	}
	$payPalCartFormHTML .= <<<EOT
				<div class="bold" style="margin-left:10px; float:right; display:block; font-size:xx-small; text-align:center; padding:0px 3px;">
					<input type="image" src="/images/{$payImg}" name="Pay Now" name="Pay Now at PayPal" value="Pay Now at PayPal" style="text-align:center; vertical-align:top; padding-bottom:3px;" /><br />
					with
					<img src="/images/PayPal-LOGO-29h-72ppi.png" height="14" alt="PayPal" style="vertical-align:middle;" /><br />
					<img src="/images/CC_mc_vs_dc_ae.jpg" alt="Pay with a Credit Card at PayPal" height="20" style="vertical-align:text-bottom;" /><br />
					<img src="/images/PayPal-LOGO-29h-72ppi.png" height="14" alt="PayPal" style="vertical-align:middle;" /><span style="font-size:x-small;">ACCOUNT NOT REQUIRED</span>
				</div>
			</form>
EOT;

	if ( $orderHandlingFee != "" && $orderHandlingFee != 0 )
	{
		$orderHTML .= <<<EOT
			<tr>
				<th colspan="5" style="text-align:right; vertical-align:middle; padding-right:10px;">Handling Fee</td>
				<td style="text-align:right; vertical-align:middle; padding-left:10px;">\${$orderHandlingFee}</td>
			</tr>
EOT;
	}
	if ( $reviewOrConfirm == "Confirm" )
	{
		$orderDateTime = date( "l, M j, Y - g:ia T" );
		$orderHTML .= <<<EOT
			<tr>
				<th style="text-align:right; vertical-align:middle; padding-right:10px;">Order Date</td>
				<td style="text-align:left; vertical-align:middle; padding-left:10px;">{$orderDateTime}</td>
				<th colspan="3" style="text-align:right; vertical-align:middle; padding-right:10px;">Total</td>
EOT;
	}
	else
	{
		$orderHTML .= <<<EOT
			<tr>
				<th colspan="5" style="text-align:right; vertical-align:middle; padding-right:10px;">Total</td>
EOT;
	}
		$orderHTML .= <<<EOT
				<td style="text-align:right; vertical-align:middle;">\${$orderTotalAmount}</td>
			</tr>
EOT;
	$orderHTML .= mainTableSeparatorRowHTML();
	$orderHTML .= <<<EOT
			<tr>
				<th>Payment<br />Instructions</th>
				<td colspan="5" style="text-align:left; vertical-align:middle; padding-left:10px;">
					$orderPaymentInstructions
				</td>
			</tr>
EOT;
	if ( $orderPaymentMethod == "paymentPayPal" )
	{
		$orderCommitHTML = <<<EOT
					</form>
				</td>
				<td colspan="5" style="text-align:right;">
					{$payPalCartFormHTML}
EOT;
if($debuggingOn==1)
  {echo "<pre>ShowOrder: payPalCartFormHTML: |${payPalCartFormHTML}|</pre>";}
	}
	else
	{
		list( $buyButtonLabel, $buyButtonHTML ) = getBuyButtonLabelHTML( $orderPaymentMethod, $categoryDBrow->categoryType );
		$orderCommitHTML = <<<EOT
				</td>
				<td colspan="5" style="text-align:right; padding-right:10px;">
						<input type="hidden" name="productCategory" value="{$orderCategoryName}" />
						<input type="submit" name="placeOrder" value="{$buyButtonLabel}" class="button" />
					</form>
EOT;
	}
	if ( $reviewOrConfirm == "Review" )
	{
	$orderHTML .= <<<EOT
			<tr>
				<td style="text-align:center; padding:3px;">
					<form name="OrderForm" method="post" action="{$buyStuffPP}">
						<input type="submit" name="changeOrder" value="Change Order" class="button" /><br />
						<div style="padding-top:5px; font-size:10px; font-weight:bold; text-align:center; text-transform:uppercase;">
							Close this window<br />
							to cancel this order
						</div>
				{$orderCommitHTML}
				</td>
			</tr>
EOT;
	}
	else
	{
	$orderHTML .= <<<EOT
			<tr>
				<td colspan="6">$orderMessage</td>
			</tr>
EOT;
	}

		$pageContent .= $orderHTML;

		if ( $reviewOrConfirm == "Confirm" )
		{
			$pageContent .= mainTableSeparatorRowHTML();
			$pageContent .= <<<EOT
				<tr>
					<td colspan="6" style="text-align:center; color:#7286A7;">
EOT;
			$pageContent .= preg_replace( "/value=\"\"/", "value=\"" . $orderEmail . "\"", file_get_contents("includes/ConstantContact-bubble.html") );
		
			$pageContent .= <<<EOT
					</td>
				</tr>
EOT;
		}
		$pageContent .= <<<EOT
		</tbody>
	</table>
EOT;
	if ( $reviewOrConfirm == "Confirm" )
	{
		if ($_SESSION["emailSent"] != "true")
		{
			SendConfirmation($pageContent, $orderFirstName . " " . $orderLastName, $orderEmail);
		}
		else
		{
			$pageContent = "<p style=\"border:1px solid black; padding:10px; background-color:gold;\">It appears you have either pressed the Place Order button more than once, or refreshed this page.  The order below has already been submitted.  Please click here to <a href=\"{$buyStuffPP}\">place another order</a>.</p>"
						. $pageContent;
		}
	}
if($debuggingOn==1)
	{echo "<pre>leaving ShowOrder</pre>";}

	return $pageContent;
}

function rePOSTorderFormHTML()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$lastFormRowNo,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCategoryName,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderSpecReq,
					$oneSelectedPriceId,
					$orderMessage,
					$orderHandlingFee;

	//$slashedSpecReq = addslashes( $orderSpecReq );
	$rePOSTorderFormHTML = <<<EOT
				<input type="hidden" name="lastFormRowNo" value="{$lastFormRowNo}" />
				<input type="hidden" name="productCategory" value="{$orderCategoryName}" />
				<input type="hidden" name="orderFirstName" value="{$orderFirstName}" />
				<input type="hidden" name="orderLastName" value="{$orderLastName}" />
				<input type="hidden" name="orderEmail" value="{$orderEmail}" />
				<input type="hidden" name="orderPhone" value="{$orderPhone}" />
				<input type="hidden" name="orderAddress" value="{$orderAddress}" />
				<input type="hidden" name="orderCity" value="{$orderCity}" />
				<input type="hidden" name="orderState" value="{$orderState}" />
				<input type="hidden" name="orderZip" value="{$orderZip}" />
				<input type="hidden" name="orderPaymentMethod" value="{$orderPaymentMethod}" />
				<input type="hidden" name="orderSpecReq" value="{$slashedSpecReq}" />
				<input type="hidden" name="orderHandlingFee" value="{$orderHandlingFee}" />
				<input type="hidden" name="orderSmallDonationAmount" value="{$orderSmallDonationAmount}" />
EOT;
	for ( $i=1; $i <= intval($lastFormRowNo); ++$i )
	{
		$rePOSTorderFormHTML .= <<<EOT
				<input type="hidden" name="priceId_{$i}" value="{$_REQUEST["priceId_{$i}"]}" />
				<input type="hidden" name="quantity_{$i}" value="{$_REQUEST["quantity_{$i}"]}" />
EOT;
	}
	return $rePOSTorderFormHTML;
}

function processPaymentMethod( $reviewOrConfirm )
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$categoryDBrow,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCategoryName,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderHandlingFee,
					$orderSpecReq,
					$oneSelectedPriceId,
					$orderMessage,
					$pageTitle,
					$col1Width,
					$col3Width,
					$mysql_conn;

if($debuggingOn==1)
	{echo "<pre>entering processPaymentMethod</pre>";}

	list( $buyButtonLabel, $buyButtonHTML ) = getBuyButtonLabelHTML( $orderPaymentMethod, $categoryDBrow->categoryType );

	switch ( $orderPaymentMethod )
	{
		case "paymentPayPal":
			$orderHandlingFee = $GlobalParams["HandlingFee-PayPal"];
			switch ( $reviewOrConfirm )
			{
				case "Review":
					$orderPaymentInstructions = <<<EOT
						Click
						{$buyButtonHTML}
						to pay with your Credit Card or
						<img src="/images/PayPal-LOGO-29h-72ppi.png" height="16" alt="PayPal" style="vertical-align:middle;" />account.<br />
EOT;
					break;
				case "Confirm":
					$orderPaymentInstructions = <<<EOT
						Thank You for using
						<img src="http://pinoleplayers.org/images/PayPal-LOGO-38h-72ppi.jpg" height="18" alt="PayPal" style="vertical-align:text-bottom;" />
						for your payment.
EOT;
					break;
			}
			break;
		case "paymentCC":
			$orderHandlingFee = $GlobalParams["HandlingFee-CC"];
			$orderPaymentInstructions = "We will call you for your credit card information.";
			break;
		case "paymentCheck":
			$orderHandlingFee = $GlobalParams["HandlingFee-Check"];
			$orderTotalAmount = calculateOrderTotalAmount();
			$checksTo = "Pinole Community Players";
			// $checksTo = "City of Pinole";
			$orderPaymentInstructions = <<<EOT
				I will mail a check for \${$orderTotalAmount} payable to
				<span style="color:blue; font-style:italic;">{$checksTo}</span>
				and a copy of this page to:<br/>
				<div style="display:block; text-align:left; padding-left:110px;">
					{$GlobalParams["PCPmailingAddress"]}
				</div>
EOT;
			break;
		default:
			$orderHandlinFee = "No Fee";
			$orderPaymentInstructions = "No Instructions";
if($debuggingOn==1)
	{echo "<pre>unknown orderPaymentMethod ({$orderPaymentMethod})</pre>";}

	}
	if ( $orderHandlingFee != "" )
	{
		$orderPaymentInstructions .= " <span style=\"font-size:smaller\">"
																		. makeNonBreaking( "(A \${$orderHandlingFee} handling fee applies.)" )
																		. "</span>\n";
	}

if($debuggingOn==1)
	{echo "<pre>leaving processPaymentMethod</pre>";}
}

function SendConfirmation( $htmlContent, $name, $email )
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$orderCategoryName;
		
if($debuggingOn==1)
	{echo "<pre>entering SendConfirmation: name: {$name}| email: {$email}|</pre>";}

	// they'll be looking at the email, so
	$htmlContent = preg_replace( "/We have also emailed a copy to you./", " ", $htmlContent );

	$txtOrderHeader = "";
	// extract the textBlocks
	preg_match_all("/<div[^>]*?textBlock.*?>(.*?)<\/div>/s", $htmlContent, $matches);
	foreach ( $matches[1] as $textBlock )
	{
		$txtOrderHeader .= preg_replace( "/<br( \/)?>/", "\n", $textBlock ) . "\n\n";
	}
	$txtOrderHeader .= "\n";
if($debuggingOn==1)
	{echo "<pre>SendConfirmation: txtOrderHeader: {$txtOrderHeader}|</pre>";}

	// extract rows
	preg_match_all("/<tr[^>]*>(.*?)<\/tr>/s", $htmlContent, $matches);
	// $rows = $matches[1];
	// eliminate all &...; after converting non-breaking dash to -
	$rows = preg_replace("/&[^;]*;/s", " ", preg_replace("/&#8209;/", "-", $matches[1]) );

	foreach ($rows as $row)
	{
		// extract data items
		preg_match_all("/<t[dh][^>]*>(.*?)<\/t[dh]>/s", $row, $matches);
		$data[] = $matches[1];
	}

	foreach ($data as $row)
	{
		if (preg_match("/<div[^>]*?textBlock.*?>(.*?)<\/div>/s", $row[0]) == 0 || $row[0] != "")
		{
			foreach ( $row as $col )
			{
				// eliminate Constant Contact
				$col = preg_replace("/<!-- BEGIN: Constant Contact Bubble Opt-in Email List Form -->.*?<!-- END: SafeSubscribe -->/s", " ", $col );
				// eliminate all tags
				$txtContent .= preg_replace( "/<.*?>/s", " ", preg_replace( "/\s\s+/s", " ", $col ) )
												. ": ";
			}
			$txtContent .= "\n";
if($debuggingOn==9)
	{echo "<pre>SendConfirmation: row: "; print_r($row); echo "</pre>";}
		}
	}
	$htmlContent = <<<EOT
<html>
	<head>
		<style type="text/css">
			#orderTable td {
				text-align:left;
				vertical-align:top;
			}
			#orderTable th {
				text-align:right;
				vertical-align:top;
			}
		</style>
	</head>
<body>
	{$htmlContent}
</body>
</html>
EOT;

	// send to person that ordered
	$hdrs = array(
		'From'				=> $GlobalParams["PCPboxofficeEmail"],
		'Subject'			=> "Your Pinole Playhouse '{$orderCategoryName}' Ticket Order",
		'To'					=> $email,
		'BCC'					=> $GlobalParams["PCPwebmasterEmail"],
		'Return-Path'	=> $GlobalParams["PCPboxofficeEmail"]
	);

	$crlf = "\n";
	$mime = new Mail_mime($crlf);
	$mime->setTXTBody( $txtOrderHeader . $txtContent );
	$mime->setHTMLBody( $htmlContent );
	$email_body = $mime->get();
	$cu_hdrs = $mime->headers( $hdrs );
if($debuggingOn==1)
	{echo "<pre>SendConfirmation: email: {$email}| cu_hdrs: "; print_r($cu_hdrs); echo "</pre>";}

	$mail =& Mail::factory('mail');
	$mail->send( $email, $cu_hdrs, $email_body );

	//send to box office
	$mime = new Mail_mime($crlf);
	$mime->setTXTBody( $txtContent );
	// eliminate the textBlocks
	$htmlContent = preg_replace("/<div[^>]*?textBlock.*?>.*?<\/div>/s", " ", $htmlContent);
	// eliminate Constant Contact
	$htmlContent = preg_replace("/<!-- BEGIN: Constant Contact Bubble Opt-in Email List Form -->.*?<!-- END: SafeSubscribe -->/s", " ", $htmlContent);
	$mime->setHTMLBody( $htmlContent );
	$email_body = $mime->get();

	$hdrs['Subject'] 	= "Pinole Playhouse '{$orderCategoryName}' Ticket Order for '{$name}'";
	$hdrs['To'] 			= $GlobalParams["PCPboxofficeEmail"];

	$bo_hdrs = $mime->headers($hdrs);
if($debuggingOn==1)
	{echo "<pre>SendConfirmation: bo_hdrs: "; print_r($bo_hdrs); echo "</pre>";}

	$mail->send( $GlobalParams["PCPboxofficeEmail"] . ', ' . $GlobalParams["PCPticketCzarEmail"], $bo_hdrs, $email_body);

	$_SESSION["emailSent"] = "true";
if($debuggingOn==1)
	{echo "<pre>leaving SendConfirmation</pre>";}
}

function CustomerInfoInputHTML()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCategoryName,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderSpecReq,
					$oneSelectedPriceId,
					$recaptcha,
					$orderMessage,
					$orderHandlingFee,
					$col1Width,
					$col3Width,
					$errorItemColor,
					$validItemColor,
					$mysql_conn;

if($debuggingOn==1)
	{echo "<pre>entering CustomerInfoInputHTML</pre>";}

	$errorItemColor = " color:red;";
	$validItemColor = "";

	$customerInfoInputHTML = <<<EOT
	<table width="80%" align="center" border=0 cellspacing=0 cellpadding=2>
		<tbody style="font-size:12px;">
			<tr>
				<th align="right" style="width:{$col1Width}; padding:5px;{${itemColor("F")}}">First Name</th>
				<td style="padding:5px; text-weight:bold;"><input tabindex="1" type="text" name="orderFirstName" value="{$orderFirstName}" size="40" style="font-size:14px;" /></td>
				<th align="right" style="width:{$col1Width}; padding:5px;{${itemColor("L")}}">Last Name</th>
				<td style="padding:5px; text-weight:bold;"><input tabindex="2" type="text" name="orderLastName" value="{$orderLastName}" size="40" style="font-size:14px;" /></td>
			</tr>
			<tr>
			  <th align="right" style="padding:5px; margin-bottom:15px;{${itemColor("E")}}">Email Address</th>
			  <td style="padding:5px; text-weight:bold;"><input tabindex="3" type="text" name="orderEmail" value="{$orderEmail}" size="40" style="font-size:14px;" /></td>
			  <th align="right" style="padding:5px; margin-bottom:15px;{${itemColor("P")}}">Phone Number</th>
			  <td style="padding:5px; text-weight:bold;"><input tabindex="4" type="text" name="orderPhone" value="${orderPhone}" size="40" style="font-size:14px;" /></td>
			</tr>
			<tr>
			  <th align="right" style="padding:5px; margin-bottom:15px;{${itemColor("A")}}">Street Address</th>
			  <td style="padding:5px; text-weight:bold;"><input tabindex="5" type="text" name="orderAddress" value="{$orderAddress}" size="40" style="font-size:14px;" /></td>
			  <th align="right" style="padding:5px; margin-bottom:15px;{${itemColor("C")}}">City</th>
			  <td style="padding:5px; text-weight:bold;"><input tabindex="6" type="text" name="orderCity" value="${orderCity}" size="40" style="font-size:14px;" /></td>
			</tr>
			<tr>
			  <th align="right" style="padding:5px; margin-bottom:15px;{${itemColor("S")}}">State</th>
			  <td style="padding:5px; text-weight:bold;"><input tabindex="7" type="text" name="orderState" value="{$orderState}" size="40" style="font-size:14px;" /></td>
			  <th align="right" style="padding:5px; margin-bottom:15px;{${itemColor("Z")}}">ZIP</th>
			  <td style="padding:5px; text-weight:bold;"><input tabindex="8" type="text" name="orderZip" value="${orderZip}" size="40" style="font-size:14px;" /></td>
			</tr>
		</tbody>
	</table>
EOT;

if($debuggingOn==1)
	{echo "<pre>leaving CustomerInfoInputHTML</pre>";}

	return $customerInfoInputHTML;
}

function PaymentChoiceHTML()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCategoryName,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderSpecReq,
					$oneSelectedPriceId,
					$recaptcha,
					$orderMessage,
					$col1Width,
					$col3Width,
					$mysql_conn;

if($debuggingOn==1)
	{echo "<pre>entering PaymentChoiceHTML</pre>";}

	$paymentChoiceHTML = <<<EOT
					<table cellspacing=0 border=1 bordercolor="#7286A7">
						<tr>
							<td style="vertical-align:top;"><input type="radio" name="orderPaymentMethod"
EOT;
		if ( $orderPaymentMethod == "paymentPayPal" )
		{
			$paymentChoiceHTML .= " checked=\"checked\" ";
		}
		$paymentChoiceHTML .= <<<EOT
									id="orderPaymentPayPal" value="paymentPayPal" /></td>
							<td width="100%">
								<label for="orderPaymentPayPal">
									I will pay online with <img src="/images/PayPal-LOGO-38h-72ppi.jpg" height="18" alt="PayPal" style="vertical-align:text-bottom;" />
									<img src="/images/CC_mc_vs_dc_ae.jpg" alt="Pay with a Credit Card at PayPal" height="18" style="vertical-align:text-bottom;" /><br />
									<img src="/images/PayPal-LOGO-38h-72ppi.jpg" height="14" alt="PayPal Logo" style="vertical-align:text-bottom;" />
									<span style="font-size:x-small";">
									ACCOUNT NOT REQUIRED
									</span>
									<span style="font-size:smaller";">
EOT;
		$paymentChoiceHTML .= makeNonBreaking( "(A \${$GlobalParams["HandlingFee-PayPal"]} handling fee applies.)\n" );
		$paymentChoiceHTML .= <<<EOT
									</span>
								</label>
							</td>
						</tr>
						<tr>
							<td style="vertical-align:top;"><input type="radio" name="orderPaymentMethod"
EOT;
		if ( $orderPaymentMethod == "paymentCheck" )
		{
			$paymentChoiceHTML .= " checked=\"checked\" ";
		}
		$paymentChoiceHTML .= <<<EOT
									id="orderPaymentCheck" value="paymentCheck" /></td>
							<td>
								<label for="orderPaymentCheck">
									<img src="/images/blankCheck3.jpg" alt="Mail a Check" height="18" style="vertical-align:text-bottom;" />
									I will mail you a check.
								</label>
							</td>
						</tr>
EOT;
/* NO MORE CC PAYMENTS - LEAVE THE CODE FOR OLD TIMES SAKE
		$paymentChoiceHTML .= <<<EOT
						<tr>
							<td style="vertical-align:top;"><input type="radio" name="orderPaymentMethod"
EOT;
		if ( $orderPaymentMethod == "paymentCC" )
		{
			$paymentChoiceHTML .= " checked=\"checked\" ";
		}
		$paymentChoiceHTML .= <<<EOT
									id="orderPaymentCC" value="paymentCC" /></td>
							<td>
								<label for="orderPaymentCC">
									<img src="/images/VisaMasterCard.gif" alt="We accept Visa and MasterCard" height="18" style="vertical-align:text-bottom;" />
									Call me for credit card information.
									<span style="font-size:smaller";">
EOT;
		$paymentChoiceHTML .= makeNonBreaking( "(A \${$GlobalParams["HandlingFee-CC"]} handling fee applies.)\n" );
		$paymentChoiceHTML .= <<<EOT
									</span>
								</label>
							</td>
						</tr>
*/
		$paymentChoiceHTML .= <<<EOT
					</table>
EOT;

if($debuggingOn==1)
	{echo "<pre>leaving PaymentChoiceHTML</pre>";}

	return $paymentChoiceHTML;
}

function getSmallDonationHTML()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$orderSmallDonationAmount,
					$mysql_conn;

if($debuggingOn==1)
	{echo "<pre>entering getSmallDonationHTML</pre>";}

	$checked = "";
	if ( $orderSmallDonationAmount == 0 )
	{
		$checked = "checked=\"checked\"";
	}

	$smallDonationHTML = <<<EOT
		<table width="100%" cellspacing=0 cellpadding=0 border=2 bordercolor="#7286A7">
			<tr>
				<td style="text-align:center;">
					<input type="radio" name="orderSmallDonationAmount" {$checked} id="orderSmallDonation0" value="0" />
					<label for="orderSmallDonation0">
						Maybe Next Time
					</label>
				</td>
EOT;

	$smallDonationQuery = <<<EOQ
			SELECT	price.priceClass,
							price.classPrice
				FROM ProductCategory AS category
							INNER JOIN Product AS product ON product.categoryId = category.categoryId
							INNER JOIN ProductPrice AS price ON price.productId = product.productId
				WHERE category.categoryName = 'Impulse Donation'
				ORDER BY price.classPrice ASC;
EOQ;
	$smallDonationResult = mysql_query($smallDonationQuery,$mysql_conn);
	if (!$smallDonationResult)
	{
		internalError( "Could not successfully run query ({$smallDonationQuery}) from DB: " . mysql_error() );
		exit;
	}
	$i = 0;
	while ($smallDonationDBrow = mysql_fetch_object($smallDonationResult))
	{
		++$i;

		$checked = "";
		if (  $orderSmallDonationAmount == $smallDonationDBrow->classPrice )
		{
			$checked .= " checked=\"checked\" ";
		}
		$smallDonationHTML .= <<<EOT
				<td style="text-align:center;">
					<input type="radio" name="orderSmallDonationAmount" {$checked} id="orderSmallDonation{$i}" value="{$smallDonationDBrow->classPrice}" />
					<label for="orderSmallDonation{$i}">
						{$smallDonationDBrow->priceClass}
					</label>
				</td>
EOT;
	}
	$smallDonationHTML .= <<<EOT
			</tr>
		</table>
EOT;

if($debuggingOn==1)
	{echo "<pre>leaving getSmallDonationHTML</pre>";}

	return $smallDonationHTML;
}

function getSpecialRequestsHTML()
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCategoryName,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderSpecReq,
					$oneSelectedPriceId,
					$recaptcha,
					$orderMessage,
					$orderHandlingFee,
					$col1Width,
					$col3Width,
					$mysql_conn;

	$orderSpecReq = preg_replace( "/\\([\"'])/", "\1", $orderSpecReq );

	$specialRequestHTML = <<<EOT
			<tr>
				<th style="vertical-align:top">Any special requests<br />(seat location, wheel chair<br />access needed)</th>
				<td colspan="2" style="text-align:center">
					<textarea name="orderSpecReq" cols="80" rows="4"
							style="padding:2px 5px; border:3px solid #cccccc; font-size:medium;">
						{$orderSpecReq}
					</textarea>
				</td>
			</tr>
			<tr>
				<td colspan="3">$orderMessage</td>
			</tr>
EOT;
	return $specialRequestHTML;
}

function splitUnavailableProductDescription( $productDesc, $unavailablePattern )
{
	if ( preg_match( $unavailablePattern, $productDesc, $matches ) )
	{
		$pattern = "/" . $matches[0] . "/";
		$productDesc = preg_replace( $pattern, "", $productDesc, -1, $replaces_done );
if($debuggingOn==1)
	{echo "<pre>splitUnavailableProductDescription: replaces_done: {$replaces_done}| productDesc: {$productDesc}| matches[0]: {$matches[0]}|</pre>";}
	}
	return array( $productDesc, $matches[0] );
}

function getCategoryDBrow( $categoryName )
{
	global	$mysql_conn;
					$debuggingOn;

if($debuggingOn==1)
	{echo '<pre>entering getCategoryDBrow: categoryName:'; print_r($categoryName); echo '|</pre>';}

	$categoryQuery = <<<EOQ
			SELECT	categoryId,
							categoryName,
							categoryDescription,
							categoryType,
							categoryLOGOfn,
							categoryProductionCompany,
							categoryQuantityInput,
							categorySelectionType,
							categorySalesBlackout,
							categoryOrderMessageFn
			FROM	ProductCategory AS category
			WHERE	category.categoryName = '{$categoryName}';
EOQ;
	$categoryResult = mysql_query($categoryQuery, $mysql_conn);
	if (!$categoryResult)
	{
if($debuggingOn==1)
		{echo '<pre>' . "Could not successfully run query ({$categoryQuery}) from DB: " . mysql_error() . '</pre>';}
	}
	if ( ! ($categoryDBrow = mysql_fetch_object($categoryResult)) )
	{
if($debuggingOn==1)
		{echo '<pre>fetch_object failed to find $categoryName: ' . mysql_error() . '</pre>';}
	
	throw new UnknownProductCategoryException();
	}
	mysql_free_result($categoryResult);

if($debuggingOn==1)
	{echo '<pre>leaving getProductRowsHTML</pre>';}

	return $categoryDBrow;
}

function getProductRowsHTML( $categoryName )
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$categoryDBrow,
					$lastFormRowNo,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCategoryName,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderSpecReq,
					$oneSelectedPriceId,
					$priceId2quantity,
					$recaptcha,
					$orderMessage,
					$orderHandlingFee,
					$errorItemColor,
					$validItemColor,
					$col1Width,
					$col3Width,
					$mysql_conn;

if($debuggingOn==1)
	{echo '<pre>entering getProductRowsHTML: categoryName:'; print_r($categoryName); echo '|</pre>';}

	$productsHTML .= "";

	$formRowNo = 0;

	$currUnixTime = time();

	// $categoryDBrow = getCategoryDBrow( $categoryName );

	$productQuery = <<<EOQ
			SELECT	product.productId,
							product.categoryId,
							product.productDateTime,
							product.productName,
							product.productNameIsDateTime,
							product.productSoldOut,
							product.productVisible,
							product.productPreSold,
							product.productTwofer,
							product.productIsAcomment
				FROM	Product AS product
				WHERE	product.categoryId = '{$categoryDBrow->categoryId}' and product.productVisible = 1
				ORDER	BY product.productDateTime ASC;
EOQ;
	$productResult = mysql_query($productQuery,$mysql_conn);
	if (!$productResult)
	{
if($debuggingOn==1)
	{echo '<pre>' . "Could not successfully run query ({$productQuery}) from DB: " . mysql_error() . '</pre>';}
	}
	while ($productDBrow = mysql_fetch_object($productResult))
	{
		$productUnixTime = strtotime($productDBrow->productDateTime);
		if ($currUnixTime >= $productUnixTime)
		{
			continue;
		}
		list( $productDesc, $noInput ) = buildProductDescription( $productDBrow );

		$priceQuery = <<<EOQ
				SELECT	price.priceId,
								price.productId,
								price.priceClass,
								price.classPrice,
								price.priceUnits
				FROM	ProductPrice AS price
				WHERE	price.productID = "{$productDBrow->productId}"
				ORDER BY	price.classPrice DESC;
EOQ;

		$priceResult = mysql_query($priceQuery,$mysql_conn);
		if (!$priceResult)
		{
if($debuggingOn==1)
	{echo '<pre>' . "Could not successfully run query ({$priceQuery}) from DB: " . mysql_error() . '</pre>';}
		}
		
		$priceNumRows = mysql_num_rows($priceResult);

		$productsHTML .= "<tr>\n";

		if ( $formRowNo == 0 )
		{
			$productsHTML .=
				"<td rowspan=%ITEMROWCOUNT% style=\"width:{$col1Width}; padding:5px; text-align:center; vertical-align:middle;\">\n";
			if ( $categoryDBrow->categoryLOGOfn )
			{
				$productsHTML .=
					"<img src=\"{$categoryDBrow->categoryLOGOfn}\" width=\"130\" alt=\"{$categoryName} Logo\" /><br />\n";
			}
		$productsHTML .= <<<EOT
					<div style="font-weight:bolder;">{$categoryName}</div>
				</td>
EOT;
		}
		if ($priceNumRows == 0 || $noInput)
		{
			++$formRowNo;

			list( $mod_productDesc, $unavailable ) = splitUnavailableProductDescription( $productDesc, "/ ((SOLD OUT)|(Pre-Sold))/" );
			$productsHTML .=
				"<td align=\"left\" style=\"font-size:larger; font-weight:bold; padding-left:10px;\">{$mod_productDesc}</td>\n";
			$productsHTML .= <<<EOT
				<td style="width:{col3Width}; text-align:center; font-weight:bold; color:blue;">{$unavailable}
					<input type="hidden" name="priceId_{$formRowNo}" value="unavailable">
					<input type="hidden" name="quantity_{$formRowNo}" value="0">&nbsp;
				</td>
			</tr>
EOT;
		}
		else
		{
			$priceNo = 0;
	
			while ($priceDBrow = mysql_fetch_object($priceResult))
			{
				++$formRowNo;
				++$priceNo;

				if ( $priceNo == 1 )
				{
					$productsHTML .=
						"<td rowspan={$priceNumRows} align=\"left\" style=\"font-size:larger; font-weight:bold; padding-left:10px;\">{$productDesc}</td>\n";
				}
				$productsHTML .= <<<EOT
					<td align="left" style="width:{$col3Width}; padding-left:3px;">
						<table class="productTableQtyInput" width="100%">
							<tr>
								<td rowspan="2">
EOT;
				if ( $categoryDBrow->categoryQuantityInput )
				{
					$qty = 0;
					if ( isset( $priceId2quantity[ $priceDBrow->priceId ] ) && $priceId2quantity[ $priceDBrow->priceId ] != "" )
					{
						$qty = $priceId2quantity[ $priceDBrow->priceId ];
					}
					$itemColor = itemColor( sprintf("%02d",$formRowNo) );
if($debuggingOn==1)
	{echo "<pre>itemColor: \$itemColor: {$itemColor}| \$\$itemColor: {${$itemColor}}|</pre>";}
					$productsHTML .=
							"<input type=\"text\" size=2 maxlength=2 name=\"quantity_{$formRowNo}\" value=\"{$qty}\" id=\"item_{$formRowNo}\" style=\"font-size:14px; {${$itemColor}}\" />\n";
				}
				else
				{
					switch ($categoryDBrow->categorySelectionType ) 
					{
						case "One":
							if ( $oneSelectedPriceId != "" && $oneSelectedPriceId != $priceDBrow->priceId )
							{
								$checked = "";
							}
							else
							{
								$checked = "checked=\"checked\"";
							}
							$productsHTML .= <<<EOT
										<input type="radio" name="oneSelectedPriceId" {$checked} value="{$priceDBrow->priceId}" id="item_{$formRowNo}" />
										<input type="hidden" name="quantity_{$formRowNo}" value="1" />
EOT;
							break;
						case "Any":
							$productsHTML .=
										"<input type=\"checkbox\" name=\"quantity_{$formRowNo}\" value=\"1\" id=\"item_{$formRowNo}\" />";
							break;
						default:
							$productsHTML .= "categorySelectionType:$categoryDBrow->categorySelectionType";
							break;
					}
				}
				$productsHTML .= <<<EOT
							</td>
							<td align="right" style="font-weight:bold; vertical-align:bottom; padding:0px 3px;">
								<label for="item_{$formRowNo}" style="font-weight:bold;">{$priceDBrow->priceClass}</label>
								<input type="hidden" name="priceId_{$formRowNo}" value="{$priceDBrow->priceId}" />
							</td>
						</tr>
						<tr>
							<td align="right" style="font-weight:bold; vertical-align:top; padding:0px 3px;">
								<label for="item_{$formRowNo}" style="font-weight:bold;">\${$priceDBrow->classPrice}</label>
							</td>
						</tr>
					</table>
						</td>
					</tr>
EOT;
			}
		}
	}
	$lastFormRowNo = $formRowNo;

	$productsHTML = preg_replace( "/%ITEMROWCOUNT%/", "{$formRowNo}", $productsHTML, 1 );
	$productsHTML .= <<<EOT
					<input type="hidden" name="lastFormRowNo" value="{$formRowNo}" />
					<input type="hidden" name="productCategory" value="{$categoryName}">
EOT;
	#mysql_free_result($priceResult);
	mysql_free_result($productResult);

if($debuggingOn==1)
	{echo '<pre>leaving getProductRowsHTML</pre>';}

	return $productsHTML;
}

function itemColor( $labelCode )
{
	global	$GlobalParams,
					$debuggingOn,
					$paypalMode,
					$orderCustInfoErrors;

	if ( preg_match( "/{$labelCode}/", $orderCustInfoErrors ) == 1 )
	{
if($debuggingOn==1)
	{echo "<pre>errorItemColor: orderCustInfoErrors: {$orderCustInfoErrors}| labelCode: {$labelCode}|</pre>";}
		return "errorItemColor";
	}
	else
	{
if($debuggingOn==1)
	{echo "<pre>validItemColor: orderCustInfoErrors: {$orderCustInfoErrors}| labelCode: {$labelCode}|</pre>";}
		return "validItemColor";
	}
}

function displayFormErrors( $formErrors )
{
	$formErrorsHTML = "";

	if (count($formErrors) > 0)
	{
		$formErrorsHTML = <<<EOT
					<div class="errorTextBlock">
						<p class=\"bold\">
							There were some problems with your entries.
							Please check the following and try again:
						</p>
						<ul>

EOT;
		foreach ($formErrors as $error)
		{
			$formErrorsHTML .= "<li>".$error."</li>\n";
		}
		$formErrorsHTML .= <<<EOT
						</ul>
					</div>

EOT;
	}
	return $formErrorsHTML;
}

function payPalLogoHTML()
{
	$payPalLogoHTML = <<<EOT
			<!-- PayPal Logo -->
			<table border="0" cellpadding="0" cellspacing="0" align="center">
				<tbody style="padding: 0px 3px;">
					<tr>
						<td align="center">
							<div class="bold" style="display:block color:#144C7D; font-size:small; text-align:center; background-color:white; padding:0px 3px;">
								<img src="/images/AM_SbyPP_mc_vs_dc_ae-80h-72ppi.jpg" height="60" border="0" alt="PayPal Acceptance Mark"><br />
								<img src="/images/PayPal-LOGO-38h-72ppi.jpg" height="16" alt="PayPal Logo" style="vertical-align:text-bottom;" />
								ACCOUNT NOT REQUIRED
							</div>
						</td>
				</tbody>
			</table>
			<!-- PayPal Logo -->
EOT;
	return $payPalLogoHTML;
}
function DisplayUnknownProductCategoryPage( UnknownProductCategoryException $unknownProductCategory )
{
	global	$GlobalParams,
					$debuggingOn,
					$debugFlags,
					$paypalMode,
					$pageTitle,
					$metaHTTPequiv;

	$metaHTTPequiv = <<<EOT
<meta http-equiv="refresh" content="20; url=http://www.pinoleplayers.org">
EOT;
	$pageTitle = "Redirect to Pinole Players Home Page";
	$unknownProductCategoryHTML = <<<EOT
		<table class="productTable" width="826" cellspacing="0" border="2" bordercolor="#7286A7">
			<tbody>
				<tr class="page-bg">
					<td colspan="6">
						<div style="padding:10px;" align="center">
								<div style="color:blue; font-size:xx-large; font-weight:900; padding:20px;" align="center">
									OOPS!
								</div>
								<div style="font-size:medium;" align="center">
									It looks like you may have clicked on an old link to our Online BoxOffice.<br /><br />
									In 20 seconds you will be redirected to the Pinole Players' Home Page<br />
									where you will find our current links.<br /><br />
									If that does not happen, or you don't want to wait,
									<a href="http://www.pinoleplayers.org">Click Here<br /><br />
									http://www.pinoleplayers.org</a><br /><br />
									Should you end up on this page a second time, please call 
EOT;
			$unknownProductCategoryHTML .= makeNonBreaking($GlobalParams["PCPboxofficePhoneNumber"]) . ".<br />";
			$unknownProductCategoryHTML .= <<<EOT
									to place your order and be sure to mention this problem.<br /><br />
									We apology for any inconvenience.
								</div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
EOT;
	return $unknownProductCategoryHTML;
}

function DisplayProductsForm( $categoryName )
{
	global	$GlobalParams,
					$buyStuffPP,
					$debuggingOn,
					$paypalMode,
					$categoryDBrow,
					$orderFirstName,
					$orderLastName,
					$orderAddress,
					$orderCity,
					$orderState,
					$orderZip,
					$orderEmail,
					$orderPhone,
					$orderCategoryName,
					$orderItems,
					$orderSmallDonationAmount,
					$orderPaymentMethod,
					$orderPaymentInstructions,
					$orderSpecReq,
					$oneSelectedPriceId,
					$recaptcha,
					$orderMessage,
					$orderHandlingFee,
					$formErrors,
					$pageTitle,
					$col1Width,
					$col3Width,
					$mysql_conn;

if($debuggingOn==1)
	{echo "<pre>entering DisplayProductsForm: categoryName: {$categoryName}|</pre>";}
	
	try {
		$productRowsHTML = getProductRowsHTML( $categoryName );
	}
	catch (UnknownProductCategoryException $unknownProductCategory) {
		# pass it up
		throw $unknownProductCategory;
	}

	$pageContents = displayFormErrors( $formErrors );
	$pageContents .= <<<EOT
						<div class="textBlock" style="text-align:center;">
EOT;

	$pageContents .= <<<EOT
							<h3>GOING IN A GROUP?</h3>
							<p style="margin-bottom:5px;">
								<strong>For Groups of 15 or more, Tickets are $18 each.</strong><br /><br />
								Please leave your name and phone number on the Box Office Line at 510-724-9844<br />
								and our Ticket Coordinator will return your call to take your Group order.
							</p>
EOT;
	$pageContentsDiscarded .= <<<EOT
							<div style="text-align:center; font-size:larger; font-variant:small-caps;">
								<b>all reservations must be prepaid</b>
							</div>
EOT;
	$pageContents .= <<<EOT
						</div>
EOT;
	
	$pageContents .= <<<EOT
	<form name="OrderForm" method="post" action="{$buyStuffPP}">
		<input type="hidden" name="productCategory" value="{$categoryName}" />
EOT;

	$productColHdr = getProductColHdr( $categoryDBrow->categoryType );
	$selectionColHdr = getSelectionColHdr( $categoryDBrow->categoryType );

	$pageContents .= <<<EOT
	<table class="productTable" width="100%" border=1 bordercolor="#7286A7" cellspacing=0>
		<tbody style="font-size:12px;">
			<tr>
				<th style="width:120px; text-align:center; font-size:large;">.</th>
				<th style="text-align:left; font-size:large; padding-left:10px;">{$productColHdr}</th>
				<th style="width:{$col3Width}; font-size:10px; text-align:center; text-transform:uppercase;">
					{$selectionColHdr}
				</th>
			</tr>
EOT;

	$pageContents .= $productRowsHTML;

	$pageContents .= mainTableSeparatorRowHTML();

	$pageContents .= <<<EOT
			<tr>
				<td class="bold" style="text-align:right; font-size:larger; padding-right:15px;">Contact Info</td>
				<td colspan="2">

EOT;
	$pageContents .= CustomerInfoInputHTML();

	$pageContents .= <<<EOT
				</td>
			</tr>
			<tr>
				<td class="bold" style="text-align:right; font-size:larger; padding-right:15px;">Special Requests
					<div style="font-weight:normal; font-size:smaller;">
						(e.g., seat location, wheel chair access required)
					</div>
				</td>
				<td colspan="2" style="text-align:left; padding-left:10px;">
					<div style="text-transform:uppercase; font-variant:small-caps; margin:5px auto; width:90%; text-align:center; line-height:1.2;">
						<span style="font-weight:bold; font-size:larger;">ASSIGNED RESERVED SEATING</span>
						- Please specify any special needs or location preferences.<br />
						<span style="font-weight:bold; color:red;">If you are attending with a group, include the group organizer's name here.</span>
					</div>
					<textarea name="orderSpecReq" cols="80" rows="4" wrap="physical"
						style="padding:2px 5px; border: 3px solid #cccccc; font-size:medium;">$orderSpecReq</textarea>
				</td>
			</tr>
EOT;
	$pageContents .= mainTableSeparatorRowHTML();
	/*$pageContents .= <<<EOT
						<div class="textBlock" align="center">
							<p>
								When you are satisfied with your order, click the Pay with PayPal button.
							</p>
							<p>
								You may still cancel your order on the PayPal site.
								Once you choose to pay, your credit card or PayPal account will be charged.
							</p>
						</div>
EOT; */
	$pageContents .= <<<EOT
			<tr>
				<td class="bold" style="text-align:right; font-size:larger; padding-right:15px;">Payment Options</td>
				<td colspan="2">

EOT;
	$pageContents .= PaymentChoiceHTML();

	$pageContents .= <<<EOT
				</td>
			</tr>

EOT;
	$pageContents .= <<<EOT
			<tr>
				<td class="bold" style="text-align:center; padding:10px; width:120px;">
					Please consider
					adding a small
					donation to
					this order
				</td>
				<td>

EOT;
	$pageContents .= getSmallDonationHTML();
	
	$pageContents .= <<<EOT
				</td>
				<td class="bold" style="text-align:center; width:{$col3Width};">
					Every little bit<br />
					helps.<br />
					<span style="font-size:large;">Thank You</span>
				</td>
			</tr>
EOT;
	$pageContents .= <<<EOT
			<tr>
				<td style="text-align:center;">
					<div style="font-size:15px; font-weight:bold; text-align:center; text-transform:uppercase;">
						Close this window<br />
						to cancel this order
					</div>
				</td>
				<td align="right">
EOT;
	$pageContents .=  payPalLogoHTML();

	$pageContents .= <<<EOT
				</td>
				<td style="text-align:center; width:{$col3Width};">
					<input type="submit" value="Review Order" class="button" />
					<div style="padding-top:5px; font-size:10px; font-weight:bold; text-align:center; text-transform:uppercase;">
						You will submit<br />
						your order on<br />
						the next page
					</div>
				</td>
			</tr>
EOT;
	$pageContents .= "</form>\n";

if($debuggingOn==1)
	{echo "<pre>leaving DisplayProductsForm</pre>";}

	return $pageContents;
}
if ($debuggingOn)
	{dumpEnvironmentVars();}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?= $metaHTTPequiv ?>

<title>
<?= $pageTitle ?>
</title>

<link rel="stylesheet" type="text/css" href="css/pcp.css" />

	<link rel="stylesheet" type="text/css" href="css/pcp-colors.css" />

<link rel="stylesheet" type="text/css" href="css/2leveltab.css" />

<style type="text/css">
#orderTable th {
	text-align:right;
	vertical-align:top;
}
#orderTable td {
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
	var OrderForm = document.forms["OrderForm"];
	var selectedPerfIdPrices = OrderForm.elements["orderPerfIdPrices"].value;

	var orderPerfIdPricesFlds = selectedPerfIdPrices.split( ":" );

	OrderForm.elements["orderPerfPriceSenStu"].value = orderPerfIdPricesFlds[1];
	OrderForm.elements["orderPerfPriceAdult"].value  = orderPerfIdPricesFlds[2];
}
</script>
</head>

<body style="height:100%">
	<div id="mainContainer" style="height:100%">
	<center>
	<table id="mainTable" border="0" cellpadding="1" cellspacing="0" height="100%">
		<tbody>
<?
readfile("includes/PCPboxoffice-banner-row.html");
?>
			<tr class="mainTable-bg">
				<td colspan=3 id="mainContent" style="height:100%; margin-top:0pt; padding-top:0pt" valign="top" align="center">
					<div style="text-align:center; padding:0; padding-top:10px;">
						<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0">
							<tbody style="font-size:12pt;">
								<tr>
									<td align="center">
										<table width="826" cellspacing="3" cellpadding="0" border="2" bordercolor="#7286A7">
											<tbody class="page-bg">
												<tr>
													<td>
<?= $pageContent ?>
													</td>
												</tr>
											</tbody>
										</table>
									</td>
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
<!-- vim:set ai ts=2 sw=2: -->
