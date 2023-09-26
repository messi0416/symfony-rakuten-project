<?php
namespace MiscBundle\Util;

use Goutte\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\BrowserKit\Response;

class GoutteClientCustom extends Client
{
  protected function createResponse(ResponseInterface $response)
  {
    // 正しい charset を返さない一部サーバ（特にSHOPLISTとかSHOPLISTとか）のための補完処理
    $headers = $response->getHeaders();
    $contentType = $this->getContentType($response);

    if (!$contentType) {
      $headers['Content-Type'][0] = 'text/html; charset=UTF-8';

    } else {
      if (
           stripos($contentType, 'text/') !== false
        || stripos($contentType, 'json') !== false
        || stripos($contentType, 'xml') !== false
      ) {

        // charset がない場合
        if (stripos($contentType, 'charset=') === false) {
          if (substr($contentType, -1) !== ';') {
            $contentType = $contentType . ';';
          }
          $charset = $this->guessCharset($response);

          $contentType .= sprintf(' charset=%s', $charset);
          $headers['Content-Type'][0] = $contentType;

        // 一部サーバ（特にSHOPLISTとかSHOPLISTとか）で none とかつけられた場合の処理（※これがメイン）
        } elseif (stripos($contentType, 'charset=none') !== false) {

          $tmp = explode(';', $contentType);
          $types = [];
          foreach($tmp as $i => $phrase) {
            $ele = explode('=', $phrase);
            $k = trim($ele[0]);
            $v = isset($ele[1]) ? trim($ele[1]) : '';

            if (strtolower($k) == 'charset') {

              $charset = $this->guessCharset($response);
              $types[] = sprintf('%s=%s', $k, $charset);
            } else {
              $types[] = $phrase;
            }
          }

          $headers['Content-Type'][0] = implode('; ', $types);
        }
      }
    }

    return new Response((string) $response->getBody(), $response->getStatusCode(), $headers);
  }

  /**
   * 文字コード判定
   * @param ResponseInterface $response
   * @return string
   */
  private function guessCharset(ResponseInterface $response)
  {
    $contentType = $this->getContentType($response);
    $charset = null;

    // htmlの場合はMETAタグを確認
    if (stripos($contentType, 'text/html') !== false) {
      $body = $response->getBody();

      if (preg_match('|<meta http-equiv="Content-Type" content=".*charset=([^";]+)(?:;.*)?">|i', $body, $match)) {
        $charset = $match[1];
      } else if (preg_match('|<meta .*charset="([^"]+)">|i', $body, $match)) {
        $charset = $match[1];
      }
    }

    if ($charset) {
      return $charset;
    }

    // その他の場合は、Content から推測 ... はやめてざっくりUTF-8に決め打ち
    if (
         stripos($contentType, 'text/') !== false
      || stripos($contentType, 'json') !== false
      || stripos($contentType, 'xml') !== false
    ) {
      $charset = 'UTF-8';
    }

    return $charset;
  }

  /**
   * Content-Type 取得
   * @param ResponseInterface $response
   * @return string
   */
  private function getContentType(ResponseInterface $response)
  {
    $headers = $response->getHeaders();
    $contentType = isset($headers['Content-Type']) && is_array($headers['Content-Type']) && isset($headers['Content-Type'][0])
                 ? trim($headers['Content-Type'][0])
                 : null;
    return $contentType;
  }

}
