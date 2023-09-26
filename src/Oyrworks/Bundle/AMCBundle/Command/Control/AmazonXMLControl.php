<?php
 	namespace Oyrworks\Bundle\AMCBundle\Command\Control;
 	
 	class AmazonXMLControl
 	{
 		// ネクストエンジンに送るために必要な情報であるListOrders, ListOrderItems, GetOrder、およびErrorに関して判別
		// 基本的に1回のアクセスでは1種類の情報しか返さないが、何らかの処理等で1つのファイルに複数の情報があった場合も利用可能
 		public function VerifyXML ($importedXML) // インポートされたXMLファイルの情報の種類を確認
		{
			$is_listOrdersResult = FALSE;
			$is_listOrderItemsResult = FALSE;
			$is_getOrderResult = FALSE;
			$is_errors = FALSE;
			
			if($errors = $importedXML->Error)
			{
				$is_errors = TRUE;
			}
		
			if($listOrdersResult = $importedXML->ListOrdersResult)
			{
				$is_listOrdersResult = TRUE;
			}
		
			if($listOrderItemsResult = $importedXML->ListOrderItemsResult)
			{
				$is_listOrderItemsResult = TRUE;
			}
			
			if($listOrderItemsResult = $importedXML->GetOrderResult)
			{
				$is_getOrderResult = TRUE;
			}
		
			$verification = [ 'is_listOrdersResult' => $is_listOrdersResult, 
						 	  'is_listOrderItemsResult' => $is_listOrderItemsResult,
						 	  'is_getOrderResult' => $is_getOrderResult,
						 	  'is_errors' => $is_errors ];
		
			return $verification;       
		}
		
		// ListOrdersの情報の展開
		public function ExtractListOrders ($listOrdersResult)
		{
			
			$nextToken = $listOrdersResult->NextToken;
			$lastUpdatedBefore = $listOrdersResult->LastUpdatedBefore;
			$orders = $listOrdersResult->Orders;
			if (!$orders)
			{
				exit();
			}
			$orderCount = 0;
			$order = [];
			foreach ($orders->Order as $orderElement)
			{
				$paymentExecutionDetailItem = [];
				if ($orderElement->PaymentExecutionDetail)
				{
					$count = 0;
					foreach ($orderElement->PaymentExecutionDetail->PaymentExecutionDetailItem as $tmp_paymentExecutionDetailItem)
					{
						$paymentExecutionDetailItem[++$count] = [ "Amount" => $tmp_paymentExecutionDetailItem->Payment->Amount,
															  	  "CurrencyCode" => $tmp_paymentExecutionDetailItem->Payment->CurrencyCode,
															  	  "PaymentMethod" => $tmp_paymentExecutionDetailItem->PaymentMethod ]; 
					}
		    	}
		    
		    	$taxClassifications = [];
		    	if ($orderElement->BuyerTaxInfo->TaxClassifications)
		    	{
		    		$count = 0;
		    		foreach ($orderElement->BuyerTaxInfo->TaxClassifications->TaxClassification as $tmp_taxClassification)
		    		{
		    			$taxClassifications[++$count] = [ "Name" => $tmp_taxClassification->Name,
		    											  "Value" => $tmp_taxClassification->Value ];
					}
				}
			
				$order[++$orderCount] = [ "AmazonOrderId" => $orderElement->AmazonOrderId,
										  "SellerOrderId" => $orderElement->SellerOrderId,
										  "PurchaseDate" => $orderElement->PurchaseDate,
										  "LastUpdateDate" => $orderElement->LastUpdateDate,
										  "OrderStatus" => $orderElement->OrderStatus,
										  "FulfillmentChannel" => $orderElement->FulfillmentChannel,
										  "SalesChannel" => $orderElement->SalesChannel,
										  "OrderChannel" => $orderElement->OrderChannel,
										  "ShipServiceLevel" => $orderElement->ShipServiceLevel,
										  "ShippingAddress" => [ "Name" => $orderElement->ShippingAddress->Name,
										  						 "AddressLine1" => $orderElement->ShippingAddress->AddressLine1,
										  						 "AddressLine2" => $orderElement->ShippingAddress->AddressLine2,
										  						 "AddressLine3" => $orderElement->ShippingAddress->AddressLine3,
										  						 "City" => $orderElement->ShippingAddress->City,
										  						 "County" => $orderElement->ShippingAddress->County,
										  						 "District" => $orderElement->ShippingAddress->District,
										  						 "StateOrRegion" => $orderElement->ShippingAddress->StateOrRegion,
										  						 "PostalCode" => $orderElement->ShippingAddress->PostalCode,
										  						 "CountryCode" => $orderElement->ShippingAddress->CountryCode,
										  						 "Phone" => $orderElement->ShippingAddress->Phone,
										  						 "AddressType" => $orderElement->ShippingAddress->AddressType ],
										  "OrderTotal" => [ "CurrencyCode" => $orderElement->OrderTotal->CurrencyCode,
										  					"Amount" => $orderElement->OrderTotal->Amount ],
										  "NumberOfItemsShipped" => $orderElement->NumberOfItemsShipped,
										  "NumberOfItemsUnshipped" => $orderElement->NumberOfItemsUnshipped,
										  "PaymentExecutionDetailItem" => $paymentExecutionDetailItem,
										  "PaymentMethod" => $orderElement->PaymentMethod,
										  "PaymentMethodDetail" => $orderElement->PaymentMethodDetails->PaymentMethodDetail,
										  "MarketplaceId" => $orderElement->MarketplaceId,
										  "BuyerEmail" => $orderElement->BuyerEmail,
										  "BuyerName" => $orderElement->BuyerName,
										  "BuyerCounty" => $orderElement->BuyerCounty,
										  "CompanyLegalName" => $orderElement->BuyerTaxInfo->CompanyLegalName,
										  "TaxingRegion" => $orderElement->BuyerTaxInfo->TaxingRegion,
										  "TaxClassifications" => $taxClassifications,
										  "ShipmentServiceLevelCategory" => $orderElement->ShipmentServiceLevelCategory,
										  "ShippedByAmazonTFM" => $orderElement->ShippedByAmazonTFM,
										  "TFMShipmentStatus" => $orderElement->TFMShipmentStatus,
										  "CbaDisplayableShippingLabel" => $orderElement->CbaDisplayableShippingLabel,
										  "OrderType" => $orderElement->OrderType,
										  "EarliestShipDate" => $orderElement->EarliestShipDate,
										  "LatestShipDate" => $orderElement->LatestShipDate,
										  "EarliestDeliveryDate" => $orderElement->EarliestDeliveryDate,
										  "LatestDeliveryDate" => $orderElement->LatestDeliveryDate,
										  "IsBusinessOrder" => $orderElement->IsBusinessOrder,
										  "IsPrime" => $orderElement->IsPrime,
										  "IsPremiumOrder" => $orderElement->IsPremiumOrder,
										  "ReplacedOrderId" => $orderElement->ReplacedOrderId,
										  "IsReplacementOrder" => $orderElement->IsReplacementOrder,
										  "PromiseResponseDueDate" => $orderElement->PromiseResponseDueDate,
										  "IsEstimatedShipDateSet" => $orderElement->IsEstimatedShipDateSet ];
			}
		
			return $order;
		}
		
		// OrderItemsの情報の展開
		public function ExtractOrderItems ($listOrderItemsResult)
		{
			$amazonOrderId = $listOrderItemsResult->AmazonOrderId;
			$orderItems = $listOrderItemsResult->OrderItems;
			if (!$orderItems)
			{
				exit();
			}
		
			$orderItem = [];
			$orderItemCount = 0;
		
			foreach ($orderItems->OrderItem as $orderElement)
			{
				$pointsGranted = array();
				if($orderElement->PointsGranted)
				{
					$pointsGranted = [ "PointsNumber" => $orderElement->PointsGranted->PointsNumber,
									   "PointsMonetaryValue" => [ "CurrencyCode" => $orderElement->PointsGranted->PointsMonetaryValue->CurrencyCode,
												  				  "Amount" => $orderElement->PointsGranted->PointsMonetaryValue->Amount ] ];
				}
			
				$orderItem[++$orderItemCount] = [ "AmazonOrderId" => $amazonOrderId,
												  "ASIN" => $orderElement->ASIN,
												  "OrderItemId" => $orderElement->OrderItemId,
												  "SellerSKU" => $orderElement->SellerSKU,
												  "CustomizedURL" => $orderElement->BuyerCustomizedInfo->CustomizedURL,
												  "Title" => $orderElement->Title,
												  "QuantityOrdered" => $orderElement->QuantityOrdered,
												  "QuantityShipped" => $orderElement->QuantityShipped,
												  "PointsGranted" => $pointsGranted,
												  "NumberOfItems" => $orderElement->ProductInfo->NumberOfItems,
												  "ItemPrice" => [ "CurrencyCode" => $orderElement->ItemPrice->CurrencyCode,
												 				   "Amount" => $orderElement->ItemPrice->Amount ],
												  "ShippingPrice" => [ "CurrencyCode" => $orderElement->ShippingPrice->CurrencyCode,
											 					  	   "Amount" => $orderElement->ShippingPrice->Amount ],
											 	  "GiftWrapPrice" => [ "CurrencyCode" => $orderElement->GiftWrapPrice->CurrencyCode,
												 				  	   "Amount" => $orderElement->GiftWrapPrice->Amount ],
											 	  "TaxCollection" => [ "Model" => $orderElement->TaxCollection->Model,
											 						   "ResponsibleParty"=> $orderElement->TaxCollection->ResponsibleParty ],
												  "ItemTax" => [ "CurrencyCode" => $orderElement->ItemTax->CurrencyCode,
												 				 "Amount" => $orderElement->ItemTax->Amount ],
												  "ShippingTax" => [ "CurrencyCode" => $orderElement->ShippingTax->CurrencyCode,
												 				  	  "Amount" => $orderElement->ShippingTax->Amount ],
												  "GiftWrapTax" => [ "CurrencyCode" => $orderElement->GiftWrapTax->CurrencyCode,
												 				  	  "Amount" => $orderElement->GiftWrapTax->Amount ],
												  "ShippingDiscount" => [ "CurrencyCode" => $orderElement->ShippingDiscount->CurrencyCode,
												 				  	      "Amount" => $orderElement->ShippingDiscount->Amount ],
												  "PromotionDiscount" => [ "CurrencyCode" => $orderElement->PromotionDiscount->CurrencyCode,
												 				  	  	   "Amount" => $orderElement->PromotionDiscount->Amount ],
												  "PromotionIds" => $orderElement->PromotionIds,
												  "CODFee" => $orderElement->CODFee,
												  "CODFeeDiscount" => [ "CurrencyCode" => $orderElement->CODFeeDiscount->CurrencyCode,
												 				  	  	"Amount" => $orderElement->CODFeeDiscount->Amount ],
												  "IsGift" => $orderElement->IsGift,
												  "GiftMessageText" => $orderElement->GiftMessageText,
												  "GiftWrapLevel" => $orderElement->GiftWrapLevel,
												  "InvoiceData" => [ "InvoiceRequirement" => $orderElement->InvoiceData->InvoiceRequirement,
												 				  	 "BuyerSelectedInvoiceCategory" => $orderElement->InvoiceData->BuyerSelectedInvoiceCategory,
												 				  	 "InvoiceTitle" => $orderElement->InvoiceData->InvoiceTitle,
												 				  	 "InvoiceInformation" => $orderElement->InvoiceData->InvoiceInformation ],
												  "ConditionNote" => $orderElement->ConditionNote,
												  "ConditionId" => $orderElement->ConditionId,
												  "ConditionSubtypeId" => $orderElement->ConditionSubtypeId,
												  "ScheduledDeliveryStartDate" => $orderElement->ScheduledDeliveryStartDate,
												  "ScheduledDeliveryEndDate" => $orderElement->ScheduledDeliveryEndDate,
												  "PriceDesignation" => $orderElement->PriceDesignation ];
			}

			return $orderItem;
		}
		
		// Errorの情報の展開
		public function ExtractErrors($errors)
		{
			$error = [];
			$errorCount = 0;
			foreach ($errors as $tmp_error)
			{
				$error[++$errorCount] = [ "Type" => $tmp_error->Type,
										  "Code" => $tmp_error->Code,
										  "Message" => $tmp_error->Message,
										  "Detail" => $tmp_error->Detail ];
			}
			
			return $error;                    
		}
 	}
?>