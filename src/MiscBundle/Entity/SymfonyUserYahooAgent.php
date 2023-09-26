<?php

namespace MiscBundle\Entity;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use MiscBundle\Entity\EntityInterface\SymfonyUserClientInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
* SymfonyUsers
*/
class SymfonyUserYahooAgent implements AdvancedUserInterface, \Serializable, SymfonyUserClientInterface
{

  /**
   * @var string
   */
  private $shop_code;

  /**
   * @var string
   */
  private $app_id = '';

  /**
   * @var string
   */
  private $app_secret = '';

  /**
   * @var string
   */
  private $ftp_user = '';

  /**
   * @var string
   */
  private $ftp_password = '';

  /**
   * Set shopCode
   *
   * @param string $shopCode
   *
   * @return SymfonyUserYahooAgent
   */
  public function setShopCode($shopCode)
  {
    $this->shop_code = $shopCode;

    return $this;
  }

  /**
   * Get shopCode
   *
   * @return string
   */
  public function getShopCode()
  {
    return $this->shop_code;
  }

  /**
   * Set appId
   *
   * @param string $appId
   *
   * @return SymfonyUserYahooAgent
   */
  public function setAppId($appId)
  {
    $this->app_id = $appId;

    return $this;
  }

  /**
   * Get appId
   *
   * @return string
   */
  public function getAppId()
  {
    return $this->app_id;
  }

  /**
   * Set appSecret
   *
   * @param string $appSecret
   *
   * @return SymfonyUserYahooAgent
   */
  public function setAppSecret($appSecret)
  {
    $this->app_secret = $appSecret;

    return $this;
  }

  /**
   * Get appSecret
   *
   * @return string
   */
  public function getAppSecret()
  {
    return $this->app_secret;
  }

  /**
   * Set ftpUser
   *
   * @param string $ftpUser
   *
   * @return SymfonyUserYahooAgent
   */
  public function setFtpUser($ftpUser)
  {
    $this->ftp_user = $ftpUser;

    return $this;
  }

  /**
   * Get ftpUser
   *
   * @return string
   */
  public function getFtpUser()
  {
    return $this->ftp_user;
  }

  /**
   * Set ftpPassword
   *
   * @param string $ftpPassword
   *
   * @return SymfonyUserYahooAgent
   */
  public function setFtpPassword($ftpPassword)
  {
    $this->ftp_password = $ftpPassword;

    return $this;
  }

  /**
   * Get ftpPassword
   *
   * @return string
   */
  public function getFtpPassword()
  {
    return $this->ftp_password;
  }

  /**
   * 取引先アカウントかどうか
   * @return boolean
   */
  public function isClient()
  {
    return false;
  }

  /**
   * フォレストスタッフかどうか
   * @return boolean
   */
  public function isForestStaff()
  {
    return false;
  }

  /**
   * Yahoo代理店アカウントかどうか
   * @return boolean
   */
  public function isYahooAgent()
  {
    return true;
  }

  public function getAccountType()
  {
    return self::ACCOUNT_TYPE_YAHOO_AGENT;
  }












  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $username;

  /**
   * @var string
   */
  private $password;

  /**
   * @var string
   */
  private $client_name;

  /**
   * @var string
   */
  private $email = '';

  /**
   * @var boolean
   */
  private $is_active = -1;

  /**
   * @var \DateTime
   */
  private $created_at;

  /**
   * @var \DateTime
   */
  private $updated_at;


  const DISPLAY_IS_ACTIVE_YES = '有効';
  const DISPLAY_IS_ACTIVE_NO  = '無効';
  const IS_ACTIVE_YES = -1;
  const IS_ACTIVE_NO = 0;
  public static $DISPLAY_IS_ACTIVE = [
    self::IS_ACTIVE_YES => self::DISPLAY_IS_ACTIVE_YES
    , self::IS_ACTIVE_NO => self::DISPLAY_IS_ACTIVE_NO
  ];


  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set username
   *
   * @param string $username
   *
   * @return SymfonyUsers
   */
  public function setUsername($username)
  {
    $this->username = $username;

    return $this;
  }

  /**
   * Get username
   *
   * @return string
   */
  public function getUsername()
  {
    return $this->username;
  }

  /**
   * Set password
   *
   * @param string $password
   *
   * @return SymfonyUsers
   */
  public function setPassword($password)
  {
    $this->password = $password;

    return $this;
  }

  /**
   * Get password
   *
   * @return string
   */
  public function getPassword()
  {
    return $this->password;
  }

  /**
   * @param $clientName
   */
  public function setClientName($clientName)
  {
    $this->client_name = $clientName;
  }

  /**
   * @return string
   */
  public function getClientName()
  {
    return $this->client_name;
  }

  /**
   * Set email
   *
   * @param string $email
   *
   * @return SymfonyUsers
   */
  public function setEmail($email)
  {
    $this->email = $email;

    return $this;
  }

  /**
   * Get email
   *
   * @return string
   */
  public function getEmail()
  {
    return $this->email;
  }

  /**
   * Set isActive
   *
   * @param boolean $isActive
   *
   * @return SymfonyUsers
   */
  public function setIsActive($isActive)
  {
    $this->is_active = (bool)$isActive ? -1 : 0;

    return $this;
  }

  /**
   * Get isActive
   *
   * @return boolean
   */
  public function getIsActive()
  {
    return $this->is_active == 0 ? false : true;
  }

  /**
   * Get isActive By Numeric Value
   */
  public function getIsActiveValue()
  {
    return $this->is_active;
  }

  /**
   * Set createdAt
   *
   * @param \DateTime $createdAt
   *
   * @return SymfonyUsers
   */
  public function setCreatedAt($createdAt)
  {
    $this->created_at = $createdAt;

    return $this;
  }

  /**
   * Get createdAt
   *
   * @return \DateTime
   */
  public function getCreatedAt()
  {
    return $this->created_at;
  }

  /**
   * Set updatedAt
   *
   * @param \DateTime $updatedAt
   *
   * @return SymfonyUsers
   */
  public function setUpdatedAt(\DateTime $updatedAt)
  {
    $this->updated_at = $updatedAt;

    return $this;
  }

  /**
   * Get updatedAt
   *
   * @return \DateTime
   */
  public function getUpdatedAt()
  {
    return $this->updated_at;
  }


  /**
   * 保存前処理 タイムスタンプ更新
   * 更新日時の更新はDBのON UPDATEに任せる
   */
  public function fillTimestamps()
  {
    if (is_null($this->created_at)) {
      $this->created_at = new \DateTime();
    }

    if (is_null($this->updated_at)) {
      $this->updated_at = new \DateTime();
    }
  }

  /**
   *
   */
  public function preUpdate(PreUpdateEventArgs $event)
  {
    if ($event->hasChangedField('password')) {
      // var_dump('password changed!');
    } else {
      // var_dump('password NOT changed!');
    }
  }

  /**
   * 表示文言：有効・無効
   * @return string
   */
  public function getDisplayIsActive()
  {
    return isset(self::$DISPLAY_IS_ACTIVE[$this->getIsActiveValue()]) ? self::$DISPLAY_IS_ACTIVE[$this->getIsActiveValue()] : null;
  }

  /**
   * 配列へ変換
   */
  public function toArray()
  {
    return [
      'id' => $this->id
      , 'username' => $this->username
      , 'password' => $this->password
      , 'email' => $this->email
      , 'is_active' => $this->is_active
      , 'ne_account' => $this->ne_account
      , 'ne_password' => $this->ne_password
      , 'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null
      , 'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null
    ];
  }

  /**
   * role判定
   * @param string $roleStr (ROLE_ADMIN, ROLE_USER, ... etc)
   * @return bool
   */
  public function hasRole($roleStr)
  {
    $roles = $this->getRoles();
    foreach($roles as $role) {
      if ($role->getRole() === $roleStr) {
        return true;
      }
    }

    return false;
  }

  // --------------------------------------
  // implements interface methods
  // --------------------------------------

  /**
   * String representation of object
   * @link http://php.net/manual/en/serializable.serialize.php
   * @return string the string representation of the object or null
   * @since 5.1.0
   */
  public function serialize()
  {
    return serialize(array(
      $this->id,
      $this->username,
      $this->password,
      $this->is_active,
    ));
  }

  /**
   * Constructs the object
   * @link http://php.net/manual/en/serializable.unserialize.php
   * @param string $serialized <p>
   * The string representation of the object.
   * </p>
   * @return void
   * @since 5.1.0
   */
  public function unserialize($serialized)
  {
    list (
      $this->id,
      $this->username,
      $this->password,
      $this->is_active,
      ) = unserialize($serialized);
  }

  /**
   * Returns the roles granted to the user.
   *
   * <code>
   * public function getRoles()
   * {
   *     return array('ROLE_USER');
   * }
   * </code>
   *
   * Alternatively, the roles might be stored on a ``roles`` property,
   * and populated in any number of different ways when the user object
   * is created.
   *
   * @return Role[] The user roles
   */
  public function getRoles()
  {
    if (is_null($this->roleObjects)) {
      $this->roleObjects = [];
      $roleStrings = explode('|', $this->roles);
      foreach($roleStrings as $str) {
        $this->roleObjects[] = new Role($str);
      }

    }

    return $this->roleObjects;
  }

  /**
   * Returns the salt that was originally used to encode the password.
   *
   * This can return null if the password was not encoded using a salt.
   *
   * @return string|null The salt
   */
  public function getSalt()
  {
    return null;
  }

  /**
   * Removes sensitive data from the user.
   *
   * This is important if, at any given point, sensitive information like
   * the plain-text password is stored on this object.
   */
  public function eraseCredentials()
  {
    // TODO: Implement eraseCredentials() method.
  }

  /**
   * Checks whether the user's account has expired.
   *
   * Internally, if this method returns false, the authentication system
   * will throw an AccountExpiredException and prevent login.
   *
   * @return bool true if the user's account is non expired, false otherwise
   *
   * @see AccountExpiredException
   */
  public function isAccountNonExpired()
  {
    return true;
  }

  /**
   * Checks whether the user is locked.
   *
   * Internally, if this method returns false, the authentication system
   * will throw a LockedException and prevent login.
   *
   * @return bool true if the user is not locked, false otherwise
   *
   * @see LockedException
   */
  public function isAccountNonLocked()
  {
    return true;
  }

  /**
   * Checks whether the user's credentials (password) has expired.
   *
   * Internally, if this method returns false, the authentication system
   * will throw a CredentialsExpiredException and prevent login.
   *
   * @return bool true if the user's credentials are non expired, false otherwise
   *
   * @see CredentialsExpiredException
   */
  public function isCredentialsNonExpired()
  {
    return true;
  }

  /**
   * Checks whether the user is enabled.
   *
   * Internally, if this method returns false, the authentication system
   * will throw a DisabledException and prevent login.
   *
   * @return bool true if the user is enabled, false otherwise
   *
   * @see DisabledException
   */
  public function isEnabled()
  {
    return $this->getIsActive();
  }

  /**
   * @var string
   */
  private $roles;

  /**
   * @var Role[]
   */
  private $roleObjects = null;

  /**
   * Set roles
   *
   * @param string $roles
   *
   * @return SymfonyUsers
   */
  public function setRoles($roles)
  {
    $this->roles = $roles;

    return $this;
  }

}
