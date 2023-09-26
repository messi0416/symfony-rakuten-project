<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbYahooApiAuth
 */
class MappedSuperClassTbYahooApiAuth
{
  use FillTimestampTrait;

  /**
   * 有効期限を秒数でセット
   * @param int $seconds
   * @param \DateTime $baseDateTime
   * @return TbYahooApiAuth
   */
  public function setExpirationWithSecondsTerm($seconds, $baseDateTime = null)
  {
    if (!$baseDateTime) {
      $baseDateTime = new \DateTime();
    }

    $baseDateTime->modify(sprintf('+%d seconds', $seconds));
    $this->setExpiration($baseDateTime);

    return $this;
  }



  // =================================================


  /**
   * @var int
   */
  private $id;

  /**
   * @var int
   */
  private $symfony_users_id = 0;

  /**
   * @var string
   */
  private $state = '';

  /**
   * @var string
   */
  private $nonce = '';

  /**
   * @var string
   */
  private $scopes = '';

  /**
   * @var string
   */
  private $redirect_url = '';

  /**
   * @var string
   */
  private $redirected_url = '';

  /**
   * @var string
   */
  private $auth_code = '';

  /**
   * @var string
   */
  private $access_token = '';

  /**
   * @var string
   */
  private $refresh_token = '';

  /**
   * @var \DateTime
   */
  private $expiration;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Get id
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set symfonyUsersId
   *
   * @param int $symfonyUsersId
   *
   * @return TbYahooApiAuth
   */
  public function setSymfonyUsersId($symfonyUsersId)
  {
    $this->symfony_users_id = $symfonyUsersId;

    return $this;
  }

  /**
   * Get symfonyUsersId
   *
   * @return int
   */
  public function getSymfonyUsersId()
  {
    return $this->symfony_users_id;
  }

  /**
   * Set state
   *
   * @param string $state
   *
   * @return TbYahooApiAuth
   */
  public function setState($state)
  {
    $this->state = $state;

    return $this;
  }

  /**
   * Get state
   *
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }

  /**
   * Set nonce
   *
   * @param string $nonce
   *
   * @return TbYahooApiAuth
   */
  public function setNonce($nonce)
  {
    $this->nonce = $nonce;

    return $this;
  }

  /**
   * Get nonce
   *
   * @return string
   */
  public function getNonce()
  {
    return $this->nonce;
  }

  /**
   * Set scopes
   *
   * @param string $scopes
   *
   * @return TbYahooApiAuth
   */
  public function setScopes($scopes)
  {
    $this->scopes = $scopes;

    return $this;
  }

  /**
   * Get scopes
   *
   * @return string
   */
  public function getScopes()
  {
    return $this->scopes;
  }

  /**
   * Set redirectUrl
   *
   * @param string $redirectUrl
   *
   * @return TbYahooApiAuth
   */
  public function setRedirectUrl($redirectUrl)
  {
    $this->redirect_url = $redirectUrl;

    return $this;
  }

  /**
   * Get redirectUrl
   *
   * @return string
   */
  public function getRedirectUrl()
  {
    return $this->redirect_url;
  }

  /**
   * Set redirectedUrl
   *
   * @param string $redirectedUrl
   *
   * @return TbYahooApiAuth
   */
  public function setRedirectedUrl($redirectedUrl)
  {
    $this->redirected_url = $redirectedUrl;

    return $this;
  }

  /**
   * Get redirectedUrl
   *
   * @return string
   */
  public function getRedirectedUrl()
  {
    return $this->redirected_url;
  }

  /**
   * Set authCode
   *
   * @param string $authCode
   *
   * @return TbYahooApiAuth
   */
  public function setAuthCode($authCode)
  {
    $this->auth_code = $authCode;

    return $this;
  }

  /**
   * Get authCode
   *
   * @return string
   */
  public function getAuthCode()
  {
    return $this->auth_code;
  }

  /**
   * Set accessToken
   *
   * @param string $accessToken
   *
   * @return TbYahooApiAuth
   */
  public function setAccessToken($accessToken)
  {
    $this->access_token = $accessToken;

    return $this;
  }

  /**
   * Get accessToken
   *
   * @return string
   */
  public function getAccessToken()
  {
    return $this->access_token;
  }

  /**
   * Set refreshToken
   *
   * @param string $refreshToken
   *
   * @return TbYahooApiAuth
   */
  public function setRefreshToken($refreshToken)
  {
    $this->refresh_token = $refreshToken;

    return $this;
  }

  /**
   * Get refreshToken
   *
   * @return string
   */
  public function getRefreshToken()
  {
    return $this->refresh_token;
  }

  /**
   * Set expiration
   *
   * @param \DateTime $expiration
   *
   * @return TbYahooApiAuth
   */
  public function setExpiration($expiration)
  {
    $this->expiration = $expiration;

    return $this;
  }

  /**
   * Get expiration
   *
   * @return \DateTime
   */
  public function getExpiration()
  {
    return $this->expiration;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbYahooApiAuth
   */
  public function setCreated($created)
  {
    $this->created = $created;

    return $this;
  }

  /**
   * Get created
   *
   * @return \DateTime
   */
  public function getCreated()
  {
    return $this->created;
  }

  /**
   * Set updated
   *
   * @param \DateTime $updated
   *
   * @return TbYahooApiAuth
   */
  public function setUpdated($updated)
  {
    $this->updated = $updated;

    return $this;
  }

  /**
   * Get updated
   *
   * @return \DateTime
   */
  public function getUpdated()
  {
    return $this->updated;
  }
}
