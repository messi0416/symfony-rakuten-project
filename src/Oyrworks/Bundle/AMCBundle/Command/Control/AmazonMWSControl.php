<?php
	namespace Oyrworks\Bundle\AMCBundle\Command\Control;
	
	class AmazonMWSControl
	{
		private $SELLER_ID;
		private $AWS_ACCESS_KEY_ID;
		private $AWS_SECRET_KEY;
		private $MWS_Auth_Token;
		private $isDeveloper;
		private $DataDirectoryPath;
		
		public function __construct($AmazonMWSAuthorizingInformation, $DataDirectory)
		{
			$this->SELLER_ID = $AmazonMWSAuthorizingInformation['SELLER_ID'];
			$this->AWS_ACCESS_KEY_ID = $AmazonMWSAuthorizingInformation['AWS_ACCESS_KEY_ID'];
			$this->AWS_SECRET_KEY = $AmazonMWSAuthorizingInformation['AWS_SECRET_KEY'];
			$this->MWS_Auth_Token = $AmazonMWSAuthorizingInformation['MWS_Auth_Token'];
			$this->isDeveloper = $AmazonMWSAuthorizingInformation['isDeveloperAccount'];
			
			$this->DataDirectoryPath = $DataDirectory['path'] . $DataDirectory['data'];
		}

		public function RequestAction ($Action, $option)
		{
			
			$IDs = [ 'SellerId' => $this->SELLER_ID,
			         'AWSAccessKeyId' => $this->AWS_ACCESS_KEY_ID ];
			
			switch ($Action) {
				case 'ListOrders':
					$POSTKeys = [ 'MarketplaceId.Id.1' => 'A1VC38T7YXB528',
// 	            			      'MarketplaceId.Id.2' => 'A1VN0HAN483KP2', // Non-AmazonのチャンネルのマーケットプレイスID
	            			      'FulfillmentChannel.Channel.1' => 'AFN',
// 	            			      'OrderStatus.Status.1' => 'Shipped', // 特定のステータスを取得する場合に設定（テストで使用）
	                              'Action' => 'ListOrders',
	                              'SignatureVersion' => '2',
	                              'SignatureMethod' => 'HmacSHA256',
	                              'Version' => '2013-09-01',
	                              'CreatedAfter' => gmdate("Y-m-d\T15:00:00\Z", strtotime("-1 days")), // 前日の00:00:00のUTC
	                              'CreatedBefore' => gmdate("Y-m-d\T14:59:59\Z", strtotime("-0 days")), // 前日の23:59:59のUTC
	                              'Timestamp' => gmdate("Y-m-d" . "\T" . "H:i:s" . "\Z") ]; // アクセス時刻のUTC
	                $fileName = $this->DataDirectoryPath . 'ListOrders_' . str_replace(':', '_', $POSTKeys['CreatedAfter']) . '.xml';
	            	break;
	            case 'ListOrdersByNextToken':
	            	$POSTKeys = [ 'Action' => 'ListOrdersByNextToken',
	                              'SignatureVersion' => '2',
	                              'SignatureMethod' => 'HmacSHA256',
	                              'Version' => '2013-09-01',
	                              'NextToken' => $option['NextToken'],
	                              'Timestamp' => gmdate("Y-m-d" . "\T" . "H:i:s" . "\Z") ];
	                $fileName = str_replace('xml', '2', $option['FileName']) . '.xml';
	            	break;
	            case 'ListOrderItems':
	            	$POSTKeys = [ 'MarketplaceId.Id.1' => 'A1VC38T7YXB528',
	                              'Action' => 'ListOrderItems',
	                              'SignatureVersion' => '2',
	                              'SignatureMethod' => 'HmacSHA256',
	                              'Version' => '2013-09-01',
	                              'AmazonOrderId' => $option, // 問い合わせるAmazonOrderId
	                              'Timestamp' => gmdate("Y-m-d" . "\T" . "H:i:s" . "\Z") ];
	                $fileName = $this->DataDirectoryPath . 'ListOrderItems_' . str_replace(':', '_', $POSTKeys['AmazonOrderId']) . '.xml';
	                break;
	            case 'ListOrderItemsByNextToken':
	            	$POSTKeys = [ 'Action' => 'ListOrderItemsByNextToken',
	                              'SignatureVersion' => '2',
	                              'SignatureMethod' => 'HmacSHA256',
	                              'Version' => '2013-09-01',
	                              'NextToken' => $option['NextToken'],
	                              'Timestamp' => gmdate("Y-m-d" . "\T" . "H:i:s" . "\Z") ];
	                $fileName = str_replace('xml', '2', $option['FileName']) . '.xml';
	                break;
	            case 'GetOrder':
	            	$POSTKeys = [ 'Action' => 'GetOrder',
	                              'SignatureVersion' => '2',
	                              'SignatureMethod' => 'HmacSHA256',
	                              'Version' => '2013-09-01',
	                              'Timestamp' => gmdate("Y-m-d" . "\T" . "H:i:s" . "\Z") ];
					for ($i = 1 ; $i <= count($option) ; $i++) 
					{
						$POSTKeys += [ 'AmazonOrderId.ID.' . $i => $option[$i-1]['OrderID'] ]; // 一度に問い合わせ可能なのは50個まで
					}
					$fileName = $this->DataDirectoryPath . 'GetOrder_' . str_replace(':', '_', $POSTKeys['Timestamp']) . '.xml';
	                break;
	            default:
	            	return NULL;
	        }
	    
	    	if ($this->isDeveloper)
	    	{
	        	$POSTKeys = $POSTKeys + [ 'MWSAuthToken' => $this->MWS_Auth_Token ]; // 開発者アカウントの場合はMWSAuthTokenの記載が必要
	    	}
	    
	    	if(empty($IDs) || empty($POSTKeys))
	    	{
	        	return NULL;
	    	}
	    
	    	$requestQuery = $IDs + $POSTKeys;
	    	ksort($requestQuery);
	    
	    	$stringForSignature = 'POST' . "\n"
								. 'mws.amazonservices.jp' . "\n"
								. '/Orders/2013-09-01' . "\n";
	    
	    	foreach ($requestQuery as $key => $value)
	    	{
		  		$stringForSignature .= "{$key}" . "=" . str_replace('%7E', '~', rawurlencode($value)) . "&";
	    	}
	    	$stringForSignature = substr($stringForSignature, 0, -1);
	    	$signature = base64_encode(hash_hmac('sha256', $stringForSignature, $this->AWS_SECRET_KEY, TRUE));
	    	$url = 'https://mws.amazonservices.jp/Orders/2013-09-01?';
	    	foreach ($requestQuery as $key => $value)
	    	{
	        	$url .= "{$key}" . "=" . str_replace('%7E', '~', rawurlencode($value)) . "&";
	    	}
	    	$url .= "Signature=" . str_replace('%7E', '~', rawurlencode($signature));
	    	$task_cURL = curl_init($url);
	    	$fileHandle = fopen($fileName, 'w+');
	    	curl_setopt($task_cURL, CURLOPT_FILE, $fileHandle);
	    	curl_setopt($task_cURL, CURLOPT_POST, TRUE);
	    	curl_setopt($task_cURL, CURLOPT_HEADER, FALSE);
	    	curl_exec($task_cURL);
	    	curl_close($task_cURL);
	    	fclose($fileHandle);
	    
	    	return $fileName;
		}
		
		public function AvoidThrotlling($RequestAction, $weight)
		{
			$is_invoked = FALSE;
			
			switch ($RequestAction) {
				case 'ListOrders':
				case 'ListOrdersByNextToken':
				case 'GetOrder':
					if ($weight >= 30) {
						$is_invoked = TRUE;
						sleep(60);
					}
					break;
				case 'ListOrderItems':
				case 'ListOrderItemsByNextToken':
					if ($weight >= 30) {
						$is_invoked = TRUE;
						sleep(30);
					}
					break;
				default:
					break;
			}
				
			return $is_invoked;
		}
		
		public function ThrotllingWeight($RequestAction)
		{
			$weight = 0;
			
			switch ($RequestAction) {
				case 'ListOrders':
				case 'ListOrdersByNextToken':
				case 'GetOrder':
					$weight = 5;
					break;
				case 'ListOrderItems':
				case 'ListOrderItemsByNextToken':
					$weight = 1;
					break;
				default:
					break;
			}
				
			return $weight;
		}
			
	}
	
?>
