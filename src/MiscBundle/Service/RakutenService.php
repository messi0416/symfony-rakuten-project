<?php

namespace MiscBundle\Service;

use MiscBundle\Entity\Repository\TbRakutenGenreAttributeRepository;
use MiscBundle\Exception\BusinessException;

/**
 * 楽天関連のビジネスロジックを管理するServiceクラス。
 *
 * モール用のService相当の処理は、BatchBundle\MallProcessが存在するが
 * BatchBundle配下をAppBundleから参照するのを避けるため、新規追加。
 * バッチ用処理は引き続き
 * @author a-jinno
 *
 */
class RakutenService
{

  use ServiceBaseTrait;

  /** 商品属性関連API 秒間最大リクエスト数 */
  const MAX_ATTRIBUTES_API_REQUESTS_PER_SECOND = 4;  // 実際の最大値 - 1（画面からの実行と重複する場合を考慮）

  /**
   * APIを利用して、指定したジャンルID一覧について、ジャンルID毎に属性情報を配列で返却。
   * @param array $genreIds 取得対象のジャンルIDの配列
   * @return array ジャンルIDをキー、属性情報の配列を値に持った連想配列の配列
   */
  public function findGenresAttributesListByApi($genreIds)
  {
    $requestCountPerSecond = 0;
    $attributesList = [];
    foreach ($genreIds as $genreId) {
      if ($requestCountPerSecond === self::MAX_ATTRIBUTES_API_REQUESTS_PER_SECOND) {
        sleep(1);
        $requestCountPerSecond = 0;
      }

      $genreAttributes = $this->findGenresAttributesByApi($genreId);
      $requestCountPerSecond++;
      if (empty($genreAttributes)) {
        continue;
      }
      $attributesList[$genreId] = $genreAttributes['genre']['attributes'];
    }
    return $attributesList;
  }

  /**
   * APIを利用して、指定したジャンルIDに紐づく商品属性情報を配列で返却。
   *
   * findGenreAttributesDetailByApi() と比べて、推奨値関係分のみ情報量が減る。
   * 不正なIDの場合、処理は止めずにログを残して空配列を返却。
   * @param integer $genreId 楽天ジャンルID
   * @return array 商品属性情報の連想配列
   */
  public function findGenresAttributesByApi($genreId)
  {
    $logger = $this->getLogger();
    /** @var \MiscBundle\Util\WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    $client = $webAccessUtil->getWebClient();

    $processName = '楽天商品属性情報取得';

    // 本番環境のみAPIを使用し、開発・検証ではダミーjsonを使用
    if ($this->getContainer()->get('kernel')->getEnvironment() !== 'prod') {
      return @json_decode(file_get_contents(
        "/home/workuser/working/ne_api/web/rakuten_genres_attributes_dummy.json"
      ), true);
    }

    $url = "https://api.rms.rakuten.co.jp/es/2.0/navigation/genres/{$genreId}/attributes";
    $rakutenApi = $this->getContainer()->getParameter('rakuten_api');
    $serviceSecret = $rakutenApi['service_secret'];
    $licenseKey = $rakutenApi['license_key'];
    $authorization = 'ESA ' . base64_encode($serviceSecret . ':' . $licenseKey);
    $client->setHeader('Authorization', $authorization);
    $client->request('get', $url);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $genreAttributes = @json_decode($response->getContent(), true);

    if (!is_array($genreAttributes)) {
      $errorMessage = json_last_error_msg();
      $info = "{$processName}でエラー発生 ジャンルID: $genreId 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      return [];
    }

    if ($response->getStatus() !== 200) {
      $errorMessage = '';
      if (isset($genreAttributes['errors'][0])) {
        $code = $genreAttributes['errors'][0]['code'];
        $message = $genreAttributes['errors'][0]['message'];
        $errorMessage = $code . ': ' . $message;
      }
      $info = "{$processName}でエラー発生 ジャンルID: $genreId 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      return [];
    }

    return $genreAttributes;
  }

  /**
   * APIを利用して、指定したジャンルIDに紐づく商品属性情報・推奨値を配列で返却。
   *
   * findGenreAttributesByApi() と比べて、推奨値関係分のみ情報量が増える。
   * @param integer $genreId 楽天ジャンルID
   * @return array 商品属性情報・推奨値の連想配列
   */
  public function findGenreAttributesDetailByApi($genreId)
  {
    $logger = $this->getLogger();
    /** @var \MiscBundle\Util\WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    $client = $webAccessUtil->getWebClient();

    $processName = '楽天商品属性情報・推奨値取得';

    // 本番環境のみAPIを使用し、開発・検証ではダミーjsonを使用
    if ($this->getContainer()->get('kernel')->getEnvironment() !== 'prod') {
      return @json_decode(file_get_contents(
        "/home/workuser/working/ne_api/web/rakuten_genres_attributes_dictionary_values_dummy.json"
      ), true);
    }

    $url = "https://api.rms.rakuten.co.jp/es/2.0/navigation/genres/{$genreId}/attributes/-/dictionaryValues";
    $rakutenApi = $this->getContainer()->getParameter('rakuten_api');
    $serviceSecret = $rakutenApi['service_secret'];
    $licenseKey = $rakutenApi['license_key'];
    $authorization = 'ESA ' . base64_encode($serviceSecret . ':' . $licenseKey);
    $client->setHeader('Authorization', $authorization);
    $client->request('get', $url);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $genreAttributesDetail = @json_decode($response->getContent(), true);

    if (!is_array($genreAttributesDetail)) {
      $errorMessage = json_last_error_msg();
      $info = "{$processName}でエラー発生 ジャンルID: $genreId 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException(
        "楽天ジャンルIDが正しくありません ジャンルID: $genreId"
      );
    }

    if ($response->getStatus() !== 200) {
      $errorMessage = '';
      if (isset($genreAttributesDetail['errors'][0])) {
        $code = $genreAttributesDetail['errors'][0]['code'];
        $message = $genreAttributesDetail['errors'][0]['message'];
        $errorMessage = $code . ': ' . $message;
      }
      $info = "{$processName}でエラー発生 ジャンルID: $genreId 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException(
        "楽天ジャンルIDが正しくありません ジャンルID: $genreId"
      );
    }

    return $genreAttributesDetail;
  }

  /**
   * APIを利用して、R-Cabinetの利用状況を取得。
   */
  public function getCabinetUsageByApi()
  {
    $logger = $this->getLogger();
    /** @var \MiscBundle\Util\WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    $client = $webAccessUtil->getWebClient();

    $processName = '楽天R-Cabinet利用状況取得';

    $url = "https://api.rms.rakuten.co.jp/es/1.0/cabinet/usage/get";
    $rakutenApi = $this->getContainer()->getParameter('motto_api');
    $serviceSecret = $rakutenApi['service_secret'];
    $licenseKey = $rakutenApi['license_key'];
    $authorization = 'ESA ' . base64_encode($serviceSecret . ':' . $licenseKey);
    $client->setHeader('Authorization', $authorization);
    $client->request('get', $url);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();

    $xml = simplexml_load_string($response->getContent());

    if ($xml->status->systemStatus != 'OK') {
      $errorMessage = $xml->status->message;
      $info = "{$processName}でエラー発生 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException("楽天Cabinetフォルダ取得 エラー");
    }

    if ($response->getStatus() !== 200) {
      $errorMessage = '';
      if (isset($xml['errors'][0])) {
        $code = $xml['errors'][0]['code'];
        $message = $xml['errors'][0]['message'];
        $errorMessage = $code . ': ' . $message;
      }
      $info = "{$processName}でエラー発生 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException("楽天Cabinetフォルダ取得 エラー");
    }

    return $xml->status->systemStatus;
  }

  /**
   * APIを利用して、Cabinetフォルダ一覧を取得
   */
  public function getCabinetFoldersByApi($page = 1, $limit = 100)
  {
    $logger = $this->getLogger();
    /** @var \MiscBundle\Util\WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    $client = $webAccessUtil->getWebClient();

    $processName = '楽天Cabinetフォルダ取得';

    $url = "https://api.rms.rakuten.co.jp/es/1.0/cabinet/folders/get?offset={$page}&limit={$limit}";
    $rakutenApi = $this->getContainer()->getParameter('motto_api');
    $serviceSecret = $rakutenApi['service_secret'];
    $licenseKey = $rakutenApi['license_key'];
    $authorization = 'ESA ' . base64_encode($serviceSecret . ':' . $licenseKey);
    $client->setHeader('Authorization', $authorization);
    $client->request('get', $url);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();

    $xml = simplexml_load_string($response->getContent());

    if ($xml->status->systemStatus != 'OK') {
      $errorMessage = $xml->status->message;
      $info = "{$processName}でエラー発生 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException("楽天Cabinetフォルダ取得 エラー");
    }

    if ($response->getStatus() !== 200) {
      $errorMessage = '';
      if (isset($xml['errors'][0])) {
        $code = $xml['errors'][0]['code'];
        $message = $xml['errors'][0]['message'];
        $errorMessage = $code . ': ' . $message;
      }
      $info = "{$processName}でエラー発生 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException("楽天Cabinetフォルダ取得 エラー");
    }

    return $xml->cabinetFoldersGetResult;
  }

  /**
   * APIを利用して、Cabinetフォルダ内のファイル一覧を取得
   */
  public function getCabinetFolderFilesByApi($folderId, $page = 1, $limit = 100)
  {
    $logger = $this->getLogger();
    /** @var \MiscBundle\Util\WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    $client = $webAccessUtil->getWebClient();

    $processName = '楽天Cabinetフォルダ内のファイル取得';

    $url = "https://api.rms.rakuten.co.jp/es/1.0/cabinet/folder/files/get?folderId={$folderId}&offset={$page}&limit={$limit}";
    $rakutenApi = $this->getContainer()->getParameter('motto_api');
    $serviceSecret = $rakutenApi['service_secret'];
    $licenseKey = $rakutenApi['license_key'];
    $authorization = 'ESA ' . base64_encode($serviceSecret . ':' . $licenseKey);
    $client->setHeader('Authorization', $authorization);
    $client->request('get', $url);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();

    $xml = simplexml_load_string($response->getContent());

    if ($xml->status->systemStatus != 'OK') {
      $errorMessage = $xml->status->message;
      $info = "{$processName}でエラー発生 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException("楽天Cabinetフォルダ取得 エラー");
    }

    if ($response->getStatus() !== 200) {
      $errorMessage = '';
      if (isset($xml['errors'][0])) {
        $code = $xml['errors'][0]['code'];
        $message = $xml['errors'][0]['message'];
        $errorMessage = $code . ': ' . $message;
      }
      $info = "{$processName}でエラー発生 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException("楽天Cabinetフォルダ取得 エラー");
    }

    return $xml->cabinetFolderFilesGetResult;
  }

  /**
   * APIを利用して、Cabinetファイルを削除
   */
  public function deleteCabinetFilesByApi($fileId)
  {
    $logger = $this->getLogger();
    /** @var \MiscBundle\Util\WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    $client = $webAccessUtil->getWebClient();

    $processName = '楽天Cabinetファイル削除';

    $url = "https://api.rms.rakuten.co.jp/es/1.0/cabinet/file/delete";
    $rakutenApi = $this->getContainer()->getParameter('motto_api');
    $serviceSecret = $rakutenApi['service_secret'];
    $licenseKey = $rakutenApi['license_key'];
    $authorization = 'ESA ' . base64_encode($serviceSecret . ':' . $licenseKey);
    $client->setHeader('Content-Type', 'text/xml');
    $client->setHeader('Authorization', $authorization);
    $xml = "<?xml version='1.0' encoding='UTF-8'?>
      <request>
        <fileDeleteRequest>
          <file>
            <fileId>{$fileId}</fileId>
          </file>
        </fileDeleteRequest>
      </request>";

    $client->request('post', $url, [], [], [], $xml);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();

    $xml = simplexml_load_string($response->getContent());

    if ($xml->status->systemStatus != 'OK') {
      $errorMessage = $xml->status->message;
      $info = "{$processName}でエラー発生 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException("楽天Cabinetファイル削除 エラー");
    }

    if ($response->getStatus() !== 200) {
      $errorMessage = '';
      if (isset($xml['errors'][0])) {
        $code = $xml['errors'][0]['code'];
        $message = $xml['errors'][0]['message'];
        $errorMessage = $code . ': ' . $message;
      }
      $info = "{$processName}でエラー発生 「{$errorMessage}」";
      $logger->error($info);
      $logger->addDbLog(
        $logger->makeDbLog($processName, 'エラー終了')->setInformation($info),
        true,
        "{$processName}でエラーが発生しました。",
        'error'
      );
      throw new BusinessException("楽天Cabinetファイル削除 エラー");
    }

    return $xml->cabinetFolderFilesGetResult;
  }
}
