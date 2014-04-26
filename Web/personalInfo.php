	$orderItemNo = 0;

	for ( $i=1; $_POST["lastFormRowNum"]; ++$i )
	{
		if ( $_POST["priceId_{$i}] = "" || $_POST["priceId_{$i}] == "unavailable"
					|| $_POST["quantity_{$i}"] == "" || $_POST["quantity_{$i}"] == 0 )
		{
			continue;
		}
		#ignoring DB connection for now
		$productQuery = <<<EOD
				SELECT	category.categoryName,
								category.categoryType,
								category.categoryCompany,
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
								prince.priceUnits
					FROM ProductCategory AS category
								INNER JOIN Product AS product ON product.categoryId = category.categoryId
								INNER JOIN ProductPrice AS price ON price.productId = product.productId
					WHERE price.priceId = $_POST["priceId_{$i}"];
EOD;
		$productResult = mysql_query($productQuery,$cn);
		if (!$productResult) {
				echo "Could not successfully run query ($productQuery) from DB: " . mysql_error();
				exit;
		}
		$productNumRows = mysql_num_rows($productResult);
		if ( $productNumRows == 0 )
		{
			# error: no entry for priceId = {$_POST["priceId_{$i}"]}
				echo "No rows found, nothing to print so am exiting";
				exit;
		}
		$productDBrow = mysql_fetch_object($productResult);

		$orderItems[++$orderItemNo] = $productDBrow = mysql_fetch_object($productResult);

		$productUnixTime = strtotime($productDBrow->productDateTime);

		if ($currUnixTime >= $productUnixTime)
		{
			# error: item no longer available - performance has begun.
			continue;
		}

		if ( $productDBrow->productNameIsDateTime )
		{
			$productDesc = date("D, M j - g:ia",strtotime($productDBrow->productDateTime));
		}
		else
		{
			$productDesc = $categoryName;
		}
		$noInput = 0;

		if ($productDBrow->productComment == 1)
		{
			# error: doesn't seem possible - became comment
			$noInput = 1;
		}
		elseif ($productDBrow->productPreSold == 1)
		{
			# error: doesn't seem possible - just became presold
			$productDesc = "<span class=\"strikethru\">{$productDesc}</span> - Pre-Sold - Call 510-724-9844 for Info";
			$noInput = 1;
		}
		else if ($currUnixTime > ($productUnixTime - 4 * 60 * 60))
		{
			# error: item no longer available - too close to curtain
			$productDesc = "<span class=\"strikethru\">{$productDesc}</span> - Call 510-724-9844 to Order";
			$noInput = 1;
		}
		else if ($productDBrow->productTwofer == 1)
		{
			$productDesc .= ' <span class="bold">(2-for-1)</span>';
		}

		if ($productDBrow->productSoldOut == 1)
		{
			# error: item no longer available - sold out
			$productDesc = "<span class=\"strikethru\">{$productDesc}</span> <span class=\"bold\">(SOLD OUT)</span>";
			$noInput = 1;
		}

		$productsHTML .= "<tr>\n";

		if ( $orderItemNo == 0 )
		{
			$productsHTML .=
				"<td rowspan=100 align=\"center\" style=\"width:{$col1Width}px;\">\n";
			if ( $categoryDBrow->categoryLOGOfn )
			{
				$productsHTML .=
					"<img src=\"{$categoryDBrow->categoryLOGOfn}\" width=\"100\" alt=\"{$categoryName} Logo\" /><br />\n";
			}
			$productsHTML .= <<<EOT
					{$categoryName}
				</td>

EOT;
		}
		$productsHTML .=
			"<td align=\"left\">{$productDesc}</td>\n";

		$productsHTML .=
			"<td align=\"left\"";
		if ( $orderItemNo == 1 )
		{
			$productsHTML .=
				" style=\"width:{$col3Width}px;\"";
		}
		$productsHTML .= ">\n";

		$productsHTML .= <<<EOT
					<input type="hidden" name="priceID_{$orderItemNo}" value="{$priceDBrow->priceId}" />
					<input type="text" size=1 maxlength=1 name="quantity_{$orderItemNo}" value="{$quantity_{$i}" readonly />
					<label for "quantity_{$orderItemNo}">{$priceDBrow->priceClass} (\${$priceDBrow->classPrice})</label>
				</td>
			</tr>
	EOT;
	}
	}
<tr>
  <th width="260">Name</th>
  <td><input tabindex="1" type="text" name="orderName" value="$orderName" size="50" /></td></tr>
<tr>
  <th>Email Address</th>
  <td><input type="text" name="orderEmail" value="$orderEmail" size="50" /></td>
</tr>
