<?php
/**
 * ランサーズ案件： FBA受注データ取得処理
 *
 * ※ （実装の具合から） AM0:00～08:59 までに実行する必要がある。（それ以降に実行すると、未来の日付指定でエラー）
 *    http://tk2-217-18298.vs.sakura.ne.jp/issues/46108
 *
 * #117059 FBA注文情報取得バッチのエラー化
 *   2020/10 AmazonMWSの権限変更に伴い、この処理が使用できなくなった（配送先等の情報が空欄になる）
 *   既に目的のわからない処理のため、単純に削除しても良いが
 *   まれに対象データがあり、動作している形跡があるため、現時点では削除する代わりに、処理対象のデータが出た場合は通知を行う方針とする。
 *   1年程度様子を見て、対象データが出なければ削除して良いと思われる。
 */

	namespace Oyrworks\Bundle\AMCBundle\Command;
	
	use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Input\InputOption;
	use Symfony\Component\Console\Output\OutputInterface;
	use MiscBundle\Util\DbCommonUtil;
	use MiscBundle\Entity\SymfonyUsers;
	use MiscBundle\Util\WebAccessUtil;
		
	class ExportAmazonXMLWithConvertingToNextEngineCSVCommand extends ContainerAwareCommand
	{
		/** @var  SymfonyUsers */
  		private $account;
  		private $results;
		
		private $LogControl; // このプロセス専用のログを作成
		private $AmazonXMLControl; // アマゾンから取得したXMLファイルを処理
		
		private $logger;
		
		protected function configure()
		{
			$this->setName('amc:CreateCSVwithUploadNextEngine')
			     ->setDescription('AmazonMWSからのデータダウンロード->SQLへの接続->CSV作成->ネクストエンジンへのアップロードのテスト')
			     ->addOption('debug', null, InputOption::VALUE_NONE, 'debugging mode')
			     ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id');
		}
		
		protected function execute(InputInterface $inputInterface, OutputInterface $outputInterface)
		{
			// checking if debug mode is enabled
			$isDebug = $inputInterface->getOption('debug');
			if($isDebug) { echo PHP_EOL . "Debug Mode" . PHP_EOL; }
			
			// getting services
			$this->LogControl = $this->getContainer()->get('AMC.log_control');
			$this->AmazonXMLControl = $this->getContainer()->get('AMC.amazon_xml_control');
			
			// class instantiation
			$AmazonMWSControl = $this->getContainer()->get('AMC.amazon_mws_control'); // AmazonMWSへの接続に関する処理
			$CSVControl = $this->getContainer()->get('AMC.csv_control'); // CSVファイル作成
			
			// initialization for logger
			$container = $this->getContainer(); 
			$this->results = [];
			$this->logger = $this->getContainer()->get('misc.util.batch_logger');
			$this->logger->initLogTimer();

			$this->logger->info('AmazonMWSからXMLファイルのダウンロードしCSV作成およびネクストエンジンへのアップロードを開始しました。');

			// 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
			if ($accountId = $inputInterface->getOption('account'))
			{
				/** @var SymfonyUsers $account */
				$account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
				if ($account)
				{
					$this->account = $account;
					$this->logger->setAccount($account);
				}
    		}
			
			// DB記録＆通知処理
			$logExecTitle = 'AmazonMWS注文のCSV作成およびネクストエンジンへのアップロード';
    		$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
			
			/** @var WebAccessUtil $webAccessUtil */
			$webAccessUtil = $container->get('misc.util.web_access');
			if ($this->account)
			{
				$webAccessUtil->setAccount($this->account);
			}
			
			// log output for execution of command
			$this->LogControl->logOutput("START" , "Start Creating CSV for Next Engine to import Amazon MWS Data");
			
			// SQLの起動
			$MySQLParameters = $this->getContainer()->getParameter('MySQL');
			try
			{
				$sqlPDO = new \PDO('mysql:host=' . $MySQLParameters['host'] . ';dbname=' . $MySQLParameters['database'] . ';charset=utf8', $MySQLParameters['user'], $MySQLParameters['password']);
				$this->LogControl->logOutput("DATABASE" , "Success Connection to MySql");
				$this->results += ['MySQLへの接続' => '成功'];
			}
			catch (\PDOException $e) 
			{
				$errorMessage = $e->getMessage();
				$this->LogControl->logOutput("DATABASE" , "Failed to Connect to MySql :" . $errorMessage);
				$this->LogControl->logOutput("TERMINATED", "Operation Terminated in MySql");
				$this->results += ['MySQLへの接続' => '失敗'];
				$this->results += ['エラーメッセージ' => $errorMessage];
				$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日までのPendingデータの取得', 'エラー終了')->setInformation($this->results), true, 'MySQLdデータベースamcへの接続に失敗しました。', 'error');
				exit();
			}
			
			if ($isDebug) { echo 'Initialized SQL' . PHP_EOL; } // for debug
			
			// MySQLに保存されているアマゾン側での処理がPendingとなっていたデータを取得する。
			// AmazonOrderIdに基づきアマゾンMWSのGetOrderによりXMLファイルをダウンロード。
			// Pendingから変わったデータについて処理を行う。
			
			// SQLの保存データから前回までに配送処理が行われなかった注文のAmazonOrderIdとOrderStatusを取得
			$requestIDandStatus = [];
			$n = 0;
			foreach ($sqlPDO->query('select * from ' . $MySQLParameters['table']) as $row)
			{
				$requestIDandStatus[++$n] = [ 'PurchaseDate' => $row['PurchaseDate'],
				                              'OrderID'  => $row['OrderID'],
				                              'status' => $row['status'] ];
				$this->LogControl->logOutput("DATABASE" , "Success to fetch unshipped order data" . " : " 
				                                         . $requestIDandStatus[$n]['PurchaseDate'] . " : " 
				                                         . $requestIDandStatus[$n]['OrderID'] . " : " 
				                                         . $requestIDandStatus[$n]['status']);
			}
			$this->results += ['Pendingデータの取得' => '完了'];
			
			if ($isDebug) { echo 'Fetched Pending Orders' . PHP_EOL; } // for debug
			// 前回までに配送処理が行われなかった注文データから注文状況をAmazonMWSのGetOrderから取得
			$orderStatusChangedToShipped = [];
			$countOfStatusChangedToShipped = 0;
			$throtllingWeight = 0;
			$requestCountOfGetOrder = 0;
			if ($requestIDandStatus) {
				// GetOrderは1リクエストにつき50件までのAmazonOrderIdについての問い合わせが可能
				$requestCountForGetOrder = ceil(count($requestIDandStatus)/50); // リクエスト回数を算出
				for ($k = 1; $k <= $requestCountForGetOrder ; $k++)
				{
					$fileName = $AmazonMWSControl->RequestAction('GetOrder', array_slice($requestIDandStatus, 10*($k-1), 50)); // 1リクエストにつき50件までなので50件ずつAmazonOrderIdの配列を設定
					$throtllingWeight = $throtllingWeight + $AmazonMWSControl->ThrotllingWeight('GetOrder');
					++$requestCountOfGetOrder;
					if($AmazonMWSControl->AvoidThrotlling('GetOrder', $throtllingWeight)) // スロットルの回避処理
					{
						$this->LogControl->logOutput("INFO" , "Throtlling Invoked: GetOrder, count: " . $requestCountOfGetOrder);
					}							
					
					if(!self::ConfirmFile($fileName))
					{
						$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, 'Pendingデータの注文処理状況を確認', 'エラー終了')->setInformation($this->results), true, $fileName . ' を開けませんでした。', 'error');
						exit();
					}

					if(!$importedXML = self::ConfirmXML($fileName))
					{
						$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, 'Pendingデータの注文処理状況を確認', 'エラー終了')->setInformation($this->results), true, $fileName . ' の取り込みに失敗しました。', 'error');
						exit();
					}
					
					$verification = $this->AmazonXMLControl->VerifyXML($importedXML);
					if($verification['is_errors'])
					{
						self::OperationForErrorFromXMLData($importedXML, $fileName);
						$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, 'Pendingデータの注文処理状況を確認', 'エラー終了')->setInformation($this->results), true, 'Amazon MWSの接続に関するエラーが発生しました。' . $fileName, 'error');
						exit(); 
					}
					if($verification['is_getOrderResult'])
					{
						$orderFromGetOrder = [];
						$orderFromGetOrder = $this->AmazonXMLControl->ExtractListOrders($importedXML->GetOrderResult);
						$sqlQuery = $sqlPDO->prepare('delete from ' . $MySQLParameters['table'] . ' where OrderID = :orderId');
						for ($i = 1 ; $i <= count($orderFromGetOrder) ; $i++)
						{
							switch($orderFromGetOrder[$i]['OrderStatus'])
							{
								// OrderStatusがShippedの場合
								// SQLのデータからAmazonOrderIdとOrderStatusを削除、Orderデータを保存
								case "Shipped":
									$sqlQuery->bindParam(':orderId', $orderFromGetOrder[$i]['AmazonOrderId'], \PDO::PARAM_STR);
									$sqlQuery->execute();
									$orderStatusChangedToShipped[++$countOfStatusChangedToShipped] = $orderFromGetOrder[$i];
									continue 2;
								// OrderStatusがCanceledの場合
								// SQLのデータからAmazonOrderIdとOrderStatusを削除
								case "Canceled": 
									$sqlQuery->bindParam(':orderId', $orderFromGetOrder[$i]['AmazonOrderId'], \PDO::PARAM_STR);
									$sqlQuery->execute();
									continue 2;
								default:
									continue 2;
							}
						}
					}
					$this->LogControl->logOutput("ORDER STATUS" , "Counts of Order Status Changed to Shipped : " . $countOfStatusChangedToShipped);
				}
			}
			$this->results += ['Pendingデータの処理状況の取得' => '完了'];
			if ($isDebug) { echo 'Processed Pending Order Data' . PHP_EOL; } // for debug
			
			// 昨日24時間の間に発生した全注文に関するデータをAmazonMWSのListOrdersから取得。
			// OrderStatusがShippedはCSVを作成してネクストエンジンへアップロード
			// PendingのものはAmazonOrderIdをデータベースに登録
			// Canceledのものは無視（この後の処理はなし）
			$requestCountOfListOrders = 0;
			$fileName = $AmazonMWSControl->RequestAction('ListOrders', NULL);
			$throtllingWeight = $throtllingWeight + $AmazonMWSControl->ThrotllingWeight('LisetOrders');
			++$requestCountOfListOrders;
			if($AmazonMWSControl->AvoidThrotlling('ListOrders', $throtllingWeight)) // スロットルの回避処理
			{
				$this->LogControl->logOutput("INFO" , "Throtlling Invoked: ListOrders, count: " . $requestCountOfListOrders);
			}			
			
			if(!self::ConfirmFile($fileName))
			{
				$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文情報の取得（ListOrders）', 'エラー終了')->setInformation($this->results), true, $fileName . ' を開けませんでした。', 'error');
				exit();
			}
			
			if(!$importedXML = self::ConfirmXML($fileName))
			{
				$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文情報の取得（ListOrders）', 'エラー終了')->setInformation($this->results), true, $fileName . ' の取り込みに失敗しました。', 'error');
				exit();
			}
	
			$verification = $this->AmazonXMLControl->VerifyXML($importedXML);
			if($verification['is_errors'])
			{
				self::OperationForErrorFromXMLData($importedXML, $fileName);
				$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文情報の取得（ListOrders）', 'エラー終了')->setInformation($this->results), true, 'Amazon MWSの接続に関するエラーが発生しました。' . $fileName, 'error');
				exit();
			}
			if($verification['is_listOrdersResult'])
			{
				$this->LogControl->logOutput("INFO" , "ListOrders RequestID: " . $importedXML->ResponseMetadata->RequestId);
				
				$order = [];
				$order = $this->AmazonXMLControl->ExtractListOrders($importedXML->ListOrdersResult);
				while ($importedXML->ListOrdersResult->NextToken) // NextTokenがある場合は、NextTokenを使って続きのデータを取得
				{	
					$this->LogControl->logOutput("REQUEST", "ListOrdersByNextToken : NextToken = :" . $importedXML->ListOrdersResult->NextToken); 
					$option = [ 'NextToken' => $importedXML->ListOrdersResult->NextToken,
							    'FileName' => $fileName ];
					$fileName = $AmazonMWSControl->RequestAction('ListOrdersByNextToken', $option);
					$throtllingWeight = $throtllingWeight + $AmazonMWSControl->ThrotllingWeight('ListOrdersByNextToken');
					++$requestCountOfListOrders;
					if($AmazonMWSControl->AvoidThrotlling('ListOrdersByNextToken', $throtllingWeight)) // スロットルの回避処理
					{
						$this->LogControl->logOutput("INFO" , "Throtlling Invoked: ListOrdersByNextToken, count: " . $requestCountOfListOrders);
					}
					
					if(!self::ConfirmFile($fileName))
					{
						$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文情報の取得（ListOrders）', 'エラー終了')->setInformation($this->results), true, $fileName . ' を開けませんでした。', 'error');
						exit();
					}
					
					if(!$importedXML = self::ConfirmXML($fileName))
					{
						$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文情報の取得（ListOrders）', 'エラー終了')->setInformation($this->results), true, $fileName . ' の取り込みに失敗しました。', 'error');

						exit();
					}
					
					$verification = $this->AmazonXMLControl->VerifyXML($importedXML);
					if($verification['is_errors'])
					{
						self::OperationForErrorFromXMLData($importedXML, $fileName);
						$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文情報の取得（ListOrders）', 'エラー終了')->setInformation($this->results), true, 'Amazon MWSの接続に関するエラーが発生しました。' . $fileName, 'error');
						exit();
					}
					if($verification['is_listOrdersResult'])
					{
						$requestID = $importedXML->ResponseMetadata->RequestId;
						$nextToken = $importedXML->ListOrdersResult->NextToken;
						$this->LogControl->logOutput("INFO" , "ListOrders RequestID: " . $requestID);
						$order += $this->AmazonXMLControl->ExtractListOrders($importedXML->ListOrdersResult);
					}
				}
			}
			
			
			$this->results += ['昨日発生した全注文情報の取得' => '完了'];
			if ($isDebug) { echo 'Fetched All Orders In Yesterday' . PHP_EOL; } // for debug
			
			if($orderStatusChangedToShipped)
			{
				$countOrder = count($order);
				for ($i = 1 ; $i <= $countOfStatusChangedToShipped ; $i++)
				{
					$n = $i + $countOrder;
					$order[$n] = $orderStatusChangedToShipped[$i];
				}
			}
			
			$requestCountOfListOrderItems = 0;
			$sql = $sqlPDO->prepare('insert into ' . $MySQLParameters['table'] . ' (PurchaseDate, OrderID, status) values(:purchaseDate, :amazonOrderId, :status)');					
			for ( $i = 1; $i <= count($order); $i++ )
			{
				$amazonOrderId = $order[$i]['AmazonOrderId'];
				if($order[$i]['OrderStatus'] == "Canceled") // Canceledの場合はそれ以上処理は発生しないのでそのままスキップ
				{
					continue;
				} 
				if($order[$i]['OrderStatus'] != "Shipped") // Shipped以外の処理（Pending）の場合はデータベースに記録
				{
					$sql->bindParam(':purchaseDate', $order[$i]['PurchaseDate'], \PDO::PARAM_STR);
					$sql->bindParam(':amazonOrderId', $amazonOrderId, \PDO::PARAM_STR);
					$sql->bindParam(':status', $order[$i]['OrderStatus'], \PDO::PARAM_STR);
					$sql->execute();
					continue;
				}
				
				$fileName = $AmazonMWSControl->RequestAction('ListOrderItems', $amazonOrderId);
				$throtllingWeight = $throtllingWeight + $AmazonMWSControl->ThrotllingWeight('ListOrderItems');
				++$requestCountOfListOrderItems;
				if($AmazonMWSControl->AvoidThrotlling('ListOrderItems', $throtllingWeight))
				{
					$this->LogControl->logOutput("INFO" , "Throtlling Invoked: ListOrderItems, count: " . $requestCountOfListOrderItems);
				}
				
				if(!self::ConfirmFile($fileName))
				{
					$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文に関する個別の情報の取得（ListOrderItems）', 'エラー終了')->setInformation($this->results), true, $fileName . ' を開けませんでした。', 'error');
					exit();
				}
				
				if(!$importedXML = self::ConfirmXML($fileName))
				{
					$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文に関する個別の情報の取得（ListOrderItems）', 'エラー終了')->setInformation($this->results), true, $fileName . ' の取り込みに失敗しました', 'error');
					exit();
				}
				
				$verification = $this->AmazonXMLControl->VerifyXML($importedXML);		
				if($verification['is_errors'])
				{
					self::OperationForErrorFromXMLData($importedXML, $fileName);
					$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文に関する個別の情報の取得（ListOrderItems）', 'エラー終了')->setInformation($this->results), true, 'Amazon MWSの接続に関するエラーが発生しました。' . $fileName, 'error');
					exit();
				}
				if($verification['is_listOrderItemsResult'])
				{
					$orderItem[$i] = $this->AmazonXMLControl->ExtractOrderItems($importedXML->ListOrderItemsResult);
					while($importedXML->ListOrdersItemsResult->NextToken) // NextTokenがある場合はNextTokenを使って続きのデータを取得
					{ 
						$option = [ 'NextToken' => $importedXML->ListOrdersItemsResult->NextToken,
								    'FileName' => $fileName ];
						$fileName = $AmazonMWSControl->RequestAction('ListOrderItemsByNextToken', $option);
						$throtllingWeight = $throtllingWeight + $AmazonMWSControl->ThrotllingWeight('ListOrderItemsByNextToken');
						++$requestCountOfListOrderItems;
						if($AmazonMWSControl->AvoidThrotlling('ListOrderItemsByNextToken', $throtllingWeight))
						{
							$this->LogControl->logOutput("INFO" , "Throtlling Invoked: ListOrderItems, count: " .$requestCountOfListOrderItems);
						}
						
						if(!self::ConfirmFile($fileName))
						{
							$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文情報の取得（ListOrders）', 'エラー終了')->setInformation($this->results), true, $fileName . ' を開けませんでした。', 'error');
							exit();
						}
						
						if(!$importedXML = self::ConfirmXML($fileName))
						{
							$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文情報の取得（ListOrders）', 'エラー終了')->setInformation($this->results), true, $fileName . ' の取り込みに失敗しました。', 'error');
							exit();
						}
					
						$verification = $this->AmazonXMLControl->VerifyXML($importedXML);
						if($verification['is_errors'])
						{
							self::OperationForErrorFromXMLData($importedXML, $fileName);
							$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '昨日発生した全注文情報の取得（ListOrders）', 'エラー終了')->setInformation($this->results), true, 'Amazon MWSの接続に関するエラーが発生しました。' . $fileName, 'error');
							exit();
						}
						if($verification['is_listOrdersResult'])
						{
							$requestID = $importedXML->ResponseMetadata->RequestId;
							$nextToken = $importedXML->ListOrdersResult->NextToken;
							$this->LogControl->logOutput("INFO" , "ListOrders RequestID: " . $requestID);
							$orderItem[$i]  += $this->AmazonXMLControl->ExtractOrderItems($importedXML->ListOrderItemsResult);
						}
						
					}
				}	
			}
			$this->results += ['全注文の個別情報とPendingデータの更新' => '完了'];
			if ($isDebug) { echo 'Update Order Information and Pending Data' . PHP_EOL; } // for debug
			
			if($order)
			{
				$csvFileName = $CSVControl->CreateCSV($order, $orderItem);
				$this->LogControl->logOutput("COMPLETED", "csv file is created " . $csvFileName);
				$this->results += ['ネクストエンジン用CSVファイルの作成' => '完了'];
				$this->results += ['CSVファイル名' => $csvFileName];
			}
			else
			{
				$this->results += ['ネクストエンジン用CSVファイルの作成' => '対象なし'];
				$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, $logExecTitle, '対象無しのため終了')->setInformation($this->results));
				exit();
			}

			// #117059 ここに来たら（処理対象があったら）エラー通知
			$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, '処理対象データ発生', 'エラー終了')->setInformation($this->results), true, 'この機能での処理は既に想定していません。システム管理者に連絡してください', 'error');



// 			if ($isDebug) { echo 'Created CSV File For NextEngine' . PHP_EOL; } // for debug

// 			$this->LogControl->logOutput("UPLOAD", "start uploading csv file to Next Engine");
// 			/** @var DbCommonUtil $commonUtil */
// 			$commonUtil = $container->get('misc.util.db_common');

// 			$apiInfo = $this->getContainer()->getParameter('ne_api');
// 			$clientId = $apiInfo['client_id'];
// 			$clientSecret = $apiInfo['client_secret'];
// 			$redirectUrl = $apiInfo['redirect_url'];

// 			$accessToken = $commonUtil->getSettingValue('NE_API_ACCESS_TOKEN');
// 			if (!$accessToken)
// 			{
// 				$accessToken = null;
// 			}
// 			$refreshToken = $commonUtil->getSettingValue('NE_API_REFRESH_TOKEN');
// 			if (!$refreshToken)
// 			{
// 				$refreshToken = null;
// 			}

// 			$client = new \ForestNeApiClient($clientId, $clientSecret, $redirectUrl, $accessToken, $refreshToken);

// 			$loginId = $commonUtil->getSettingValue('NE_API_LOGIN_ID');
// 			$loginPassword = $commonUtil->getSettingValue('NE_API_LOGIN_PASSWORD');

// 			$client->setUserAccount($loginId, $loginPassword);

// 			// Added by K.Hirai
//       $mall = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_AMAZON);

// 			$csvData = file_get_contents($csvFileName);
// 			$csvGZ = gzencode($csvData, 9);
// 			$api_params = [ 'wait_flag' => 1,
// 							'receive_order_upload_pattern_id' => $mall->getNeOrderUploadPatternId(),
// 							'data_type_1' => 'gz',
// 							'data_1' => $csvGZ ];

//     		$response = $client->apiExecute('/api_v1_receiveorder_base/upload',$api_params);
// 			if (!$response)
// 			{
// 				$this->LogControl->logOutput("FAILED", "failed to upload csv file to Next Engine");
// 				$this->results += ['ネクストエンジンへ接続' => 'エラー終了'];
// 				$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, 'ネクストエンジンへ接続', 'エラー終了')->setInformation($this->results), true, 'ネクストエンジンへ接続に失敗しました。', 'error');
// 				exit();
// 			}
// 			if ($isDebug) { echo 'Uploaded CSV File To NextEngine' . PHP_EOL; } // for debug

// 			$textReponseFromNextEngineJSON = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
// 			$responseNextEngineJSONFileName = $this->getContainer()->getParameter('DataDirectory')['path'] . $this->getContainer()->getParameter('DataDirectory')['nextengine_result'];
// 			file_put_contents($responseNextEngineJSONFileName, $textReponseFromNextEngineJSON);
// 			$this->results += ['ネクストエンジンへのファイルアップロード' => $response];
// 			if ($response['result'] == "success")
// 			{
// 				$this->LogControl->logOutput("COMPLETED", "uploading csv file to Next Engine is completed");
// 			}
// 			else
// 			{
// 				$this->LogControl->logOutput("ERROR", "uploading csv file to Next Engine returns error");
// 				$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, 'ネクストエンジンへのファイルアップロード', 'エラー終了')->setInformation($this->results), true, 'ネクストエンジンへのファイルアップロードに失敗しました。', 'error');
// 				exit();
// 			}

// 			$this->results += [ 'COMPLETED' => "処理は正常に終了しました" ];
// 			$this->logger->addDbLog($this->logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
//       		$this->logger->logTimerFlush();
// 			$this->logger->info('AmazonMWSからXMLファイルのダウンロードしCSV作成、ネクストエンジンへのアップロードを終了しました。');
// 			if($isDebug) { echo 'Completed All Operations' . PHP_EOL; }

//       		return;
		}
		
		private function ConfirmFile($fileName)
		{
			$is_fileExists = FALSE;
			if (!file_exists($fileName))
			{
				$this->LogControl->logOutput("FAIL", "failed to load " . $fileName);
				$this->LogControl->logOutput("TERMINATED", "Operation Terminated in file handle");
				$this->results += ['failed to load' => $fileName];
				$is_fileExists = FALSE;
			}
			else
			{
				$this->LogControl->logOutput("SUCCESS" , "xml file is downloaded " . $fileName);
				$is_fileExists = TRUE;
			}
			
			return $is_fileExists;
		}
		
		private function ConfirmXML($fileName)
		{
			$importedXML = simplexml_load_file($fileName);
			if (!$importedXML)
			{
				$this->LogControl->logOutput("FAIL", "xml file is corrupted " . $fileName);
				$this->LogControl->logOutput("TERMINATED", "Operation Terminated in xml file corruption");
				$this->results += ['corrupted file' => $fileName];
			}
			else
			{
				$this->LogControl->logOutput("SUCCESS", "xml file is loaded " . $fileName);
			}
			
			return $importedXML;
		}
		
		private function OperationForErrorFromXMLData($importedXML, $fileName)
		{
			$error = [];
			$this->LogControl->logOutput("ERROR" , "Amazon MWS returns Error " . $fileName);
			$error = $this->AmazonXMLControl->ExtractErrors($importedXML->Error);
			$requestId = $importedXML->RequestId;
			foreach ($error as $element)
			{
				$i = 0;
				$this->LogControl->logOutput("ERROR", "Error Type: " . $element['Type'] . 
											        ", Error Code: " . $element['Code'] . 
											        ", Error Message: " . $element['Message'] .
											        ", Detail: " . $element['Detail'],
											        ", Request ID: " . $requestId);
				$this->results += ['Amazon Returns Error ' . $i++ => [ 'Error Type' => $element['Type'],
														   			   'Error Code' => $element['Code'],
														   			   'Error Message' => $element['Message'],
														   			   'Detail' => $element['Detail'],
														   			   'Request ID' => $requestId ] ];
			}
			$this->LogControl->logOutput("TERMINATED", "Operation Terminated in Recieving Error XML");
		}
	}