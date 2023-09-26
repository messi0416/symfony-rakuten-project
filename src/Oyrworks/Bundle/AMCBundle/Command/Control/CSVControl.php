<?php
 	namespace Oyrworks\Bundle\AMCBundle\Command\Control;
 	
 	class CSVControl
 	{
 		private $csvDirectoryPath;
 		
 		public function __construct($DataDirectory)
 		{
 			$this->csvDirectoryPath = $DataDirectory['path'] . $DataDirectory['csv'];
 		}
		
		// ネクストエンジンにアップロードするCSVの作成
		// Amazon FBAのAmazon発送のものはネクストエンジン側では処理してくれないので汎用のものを使う	
 		public function CreateCSV($order, $orderItem)
		{			
			$header = "店舗伝票番号,受注日,受注郵便番号,受注住所1,受注住所2,受注名,受注名カナ,受注電話番号,受注メールアドレス,発送郵便番号,発送先住所１,発送先住所２,発送先名,発送先カナ,発送電話番号,支払方法,発送方法,商品計,税金,発送料,手数料,ポイント,その他費用,合計金額,ギフトフラグ,時間帯指定,日付指定,作業者欄,備考,商品名,商品コード,商品価格,受注数量,商品オプション,出荷済フラグ,顧客区分,顧客コード" . PHP_EOL;
		
			$outputCSVData = "";
			for ($i = 1 ; $i <= count($order) ; $i++)
			{
				foreach ($orderItem as $itemElement)
				{
					for ($j = 1 ; $j <= count($itemElement) ; $j++)
					{
						if((string)$itemElement[$j]['AmazonOrderId'] === (string)$order[$i]['AmazonOrderId'])
						{
							if((string)$itemElement[$j]['IsGift'] === "false") // 通常の場合は0、ギフトの場合は1
							{
								$isGift = 0;
							}
							else
							{
								$isGift = 1;
							}
							
							$itemPriceAmount = intval($itemElement[$j]['ItemPrice']['Amount']) / intval($itemElement[$j]['QuantityOrdered']); // Amazonから返される金額は合計金額なのでひとつあたりの金額に直す
							
							$adressLine2 = $order[$i]['ShippingAddress']['AddressLine2'];
							if($order[$i]['ShippingAddress']['AddressLine3'])
							{
								$adressLine2 .= ' ' . $order[$i]['ShippingAddress']['AddressLine3'];
							}	
							
							$outputCSVData .= 	
								'"' . $order[$i]['AmazonOrderId'] . '",' . //店舗伝票番号：必須 ListOrders=>AmazonOrderId
								'"' . gmdate("Y-m-d\ H:i:s",strtotime("+9 hour", strtotime((string)$order[$i]['PurchaseDate']))) . '",' . //受注日：必須 ListOders=>PurchaseDate
								'"' . $order[$i]['ShippingAddress']['PostalCode'] . '",' . //受注郵便番号：必須 ListOrders=>ShippingAddress=>PostalCode
								'"' . $order[$i]['ShippingAddress']['AddressLine1'] . '",' . //受注住所1：必須 ListOrders=>ShippingAddress=>AddressLine1
								'"' . $adressLine2 . '",' . //受注住所2：必須 ListOrders=>ShippingAddress=>AddressLine2, AddressLine3
								'"' . $order[$i]['BuyerName'] . '",' . //受注名：必須 ListOrders=>BuyerName
								"" . "," . //受注名カナ
								"00000" . "," . //受注電話番号
								'"' . $order[$i]['BuyerEmail'] . '",' . //受注メールアドレス：必須 ListOrders=>BuyerEmail
								'"' . $order[$i]['ShippingAddress']['PostalCode'] . '",' . //発送郵便番号：必須 ListOrders=>ShippingAddress=>PostalCode
								'"' . $order[$i]['ShippingAddress']['AddressLine1'] . '",' . //発送先住所１：必須 ListOrders=>ShippingAddress=>AddressLine1
								'"' . $adressLine2 . '",' . //発送先住所２ ListOrders=>ShippingAddress=>AddressLine2, AddressLine3
								'"' . $order[$i]['ShippingAddress']['Name'] . '",' . //発送先名：必須 ListOrders=>ShippingAddress=>Name
								"" . "," . //発送先カナ
								'"' . $order[$i]['ShippingAddress']['Phone'] . '",' . //発送電話番号：必須 ListOrders=>ShippingAddress=>Phone
								"Amazonペイメント" . "," . //支払方法：必須
								"FBA代行" . "," . //発送方法：必須
								'"' . $itemElement[$j]['ItemPrice']['Amount'] . '",' . //商品計：必須 1つの商品についての単価x個数
								"0" . "," . //税金
								"0" . "," . //発送料
								"0" . "," . //手数料
								"" . "," . //ポイント
								"" . "," . //その他費用
								'"' . $order[$i]['OrderTotal']['Amount'] . '",' . //合計金額：必須 注文全体の合計金額
								'"' . $isGift . '"' . "," . //ギフトフラグ：必須 ListOrderItems=>isGift true->1 false->0
								"" . "," . //時間帯指定
								"" . "," . //日付指定
								"" . "," . //作業者欄
								"" . "," . //備考
								'"' . $itemElement[$j]['Title'] . '",' . //商品名 ListOrderItems=>Title
								'"' . $itemElement[$j]['SellerSKU'] . '",' . //商品コード：必須 ListOrderItems=>SellerSKU
								'"' . $itemPriceAmount . '",' . //商品価格：必須 ListOrderItems=>ItemPrice
								'"' . $itemElement[$j]['QuantityOrdered'] . '",' . //受注数量：必須 ListOrderItems=>QuantityOrdered
								"" . "," . //商品オプション
								"1" . "," .//出荷済フラグ：必須
								"0" . "," . //顧客区分
								"" . PHP_EOL;//顧客コード
						}
					}
				}
			}
			
			$timeStamp = date("Y-m-d\_H:i:s") . 'JST';
			$csvFileName = str_replace(":", "_", $this->csvDirectoryPath . $timeStamp . '.csv');
			$fileHandle = fopen($csvFileName, "w+");
			fwrite($fileHandle, $header);
			fwrite($fileHandle, $outputCSVData);	
			fclose($fileHandle);
			
			return $csvFileName;
		}
	}

?>
