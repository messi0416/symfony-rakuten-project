<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use MiscBundle\Entity\EntityInterface\SymfonyUserClientInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
* SymfonyUsers
*/
class SymfonyUsers implements AdvancedUserInterface, \Serializable, SymfonyUserClientInterface
{
  
  const IS_ACTIVE_YES = -1;
  const IS_ACTIVE_NO = 0;
  
  const IS_LOCKED = -1;
  const IS_NOT_LOCKED = 0;
  
  const LIMIT_ERROR_TIME = 3; // エラーは3回まで
  const LOCK_EXPIRE_MINITES = 30; // ロック解除分。この時間が経過したら自動解除
  
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
  private $user_cd;

  /**
   * @var string
   */
  private $password;

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
  
  /**
   * @var string
   */
  private $ne_account = '';
  
  /**
   * @var string
   */
  private $ne_password = '';
  
  /**
   * @var string
   */
  private $roles;
  
  /**
   * @var Role[]
   */
  private $roleObjects = null;
  
  
  /**
   * @var \DateTime
   */
  private $last_login_datetime;
  
  /**
   * @var integer
   */
  private $login_error_count = 0;
  
  /**
   * @var \DateTime
   */
  private $password_change_datetime;
  
  /**
   * @var integer
   */
  private $is_locked = 0;
  
  /**
   * @var \DateTime
   */
  private $locked_datetime;

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
   * Set user_cd
   *
   * @param string $userCd
   * @return SymfonyUsers
   */
  public function setUserCd($userCd)
  {
    $this->user_cd = $userCd;
    
    return $this;
  }
  
  /**
   * Get user_cd
   *
   * @return string
   */
  public function getUserCd()
  {
    return $this->user_cd;
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
   * 配列へ変換
   */
  public function toArray()
  {
    return [
        'id' => $this->id
      , 'username' => $this->username
      , 'user_cd' => $this->user_cd
      , 'password' => $this->password
      , 'email' => $this->email
      , 'is_active' => $this->is_active
      , 'last_login_datetime' => $this->last_login_datetime
      , 'login_error_count' => $this->login_error_count
      , 'password_change_datetime' => $this->password_change_datetime
      , 'is_locked' => $this->is_locked
      , 'locked_datetime' => $this->locked_datetime
      , 'buyer_order' => $this->buyer_order
      , 'ne_account' => $this->ne_account
      , 'ne_password' => $this->ne_password
      , 'roles' => array_reduce($this->getRoles(), function($carry, $role){ /** @var Role $role */ $carry[] = $role->getRole(); return $carry; }, [])
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
   * ロックは一定時間で自動解除。
   *
   * @return bool true if the user is not locked, false otherwise
   *
   * @see LockedException
   */
  public function isAccountNonLocked()
  {
    // ロックされていない
    if ($this->getIsLocked() === self::IS_NOT_LOCKED) {
      return true;
    }
    $expiredAt = (new \DateTime())->modify('-' . self::LOCK_EXPIRE_MINITES . ' minutes'); // これより古いロックは無視し、エラー回数をクリア
    if ($this->locked_datetime < $expiredAt) {
      $this->login_error_count = 0;
      $this->is_locked = 0;
      $this->locked_datetime = null;
      return true;
    }
    return false;
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
     * Set neAccount
     *
     * @param string $neAccount
     *
     * @return SymfonyUsers
     */
    public function setNeAccount($neAccount)
    {
        $this->ne_account = $neAccount;

        return $this;
    }

    /**
     * Get neAccount
     *
     * @return string
     */
    public function getNeAccount()
    {
        return $this->ne_account;
    }

    /**
     * Set nePassword
     *
     * @param string $nePassword
     *
     * @return SymfonyUsers
     */
    public function setNePassword($nePassword)
    {
        $this->ne_password = $nePassword;

        return $this;
    }

    /**
     * Get nePassword
     *
     * @return string
     */
    public function getNePassword()
    {
        return $this->ne_password;
    }

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

  /**
   * 取引先様用画面 アカウント名
   * @return string
   */
  public function getClientName()
  {
    return $this->getUsername();
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
    return true;
  }

  /**
   * Yahoo代理店アカウントかどうか
   * @return boolean
   */
  public function isYahooAgent()
  {
    return false;
  }

  public function getAccountType()
  {
    return self::ACCOUNT_TYPE_USER;
  }

  /**
   * @var integer
   */
  private $warehouse_id = 12;

  /**
   * Set warehouse_id
   *
   * @param integer $warehouseId
   * @return SymfonyUsers
   */
  public function setWarehouseId($warehouseId)
  {
    $this->warehouse_id = $warehouseId;

    return $this;
  }

  /**
   * Get warehouse_id
   *
   * @return integer
   */
  public function getWarehouseId()
  {
    return $this->warehouse_id;
  }
  
  /**
   * @var \MiscBundle\Entity\TbWarehouse
   */
  private $warehouse;

  /**
   * Set warehouse
   *
   * @param \MiscBundle\Entity\TbWarehouse $warehouse
   * @return SymfonyUsers
   */
  public function setWarehouse(\MiscBundle\Entity\TbWarehouse $warehouse = null)
  {
    $this->warehouse = $warehouse;

    return $this;
  }

  /**
   * Get warehouse
   *
   * @return \MiscBundle\Entity\TbWarehouse 
   */
  public function getWarehouse()
  {
    return $this->warehouse;
  }

  /**
   * @var integer
   */
  private $company_id = -1;

  /**
   * Set company_id
   *
   * @param integer $companyId
   * @return SymfonyUsers
   */
  public function setCompanyId($companyId)
  {
    $this->company_id = $companyId;

    return $this;
  }

  /**
   * Get company_id
   *
   * @return integer
   */
  public function getCompanyId()
  {
    return $this->company_id;
  }
  
  /**
   * @var \MiscBundle\Entity\TbCompany
   */
  private $company;

  /**
   * Set warehouse
   *
   * @param \MiscBundle\Entity\TbCompany $company
   * @return SymfonyUsers
   */
  public function setCompany(\MiscBundle\Entity\TbCompany $company = null)
  {
    $this->company = $company;

    return $this;
  }

  /**
   * Get warehouse
   *
   * @return \MiscBundle\Entity\TbCompany 
   */
  public function getCompany()
  {
    return $this->company;
  }

    /**
     * @var integer
     */
    private $buyer_order = 0;


    /**
     * Set buyer_order
     *
     * @param integer $buyerOrder
     * @return SymfonyUsers
     */
    public function setBuyerOrder($buyerOrder)
    {
        $this->buyer_order = $buyerOrder;

        return $this;
    }

    /**
     * Get buyer_order
     *
     * @return integer 
     */
    public function getBuyerOrder()
    {
        return $this->buyer_order;
    }

    /**
     * Set last_login_datetime
     *
     * @param \DateTime $lastLoginDatetime
     * @return SymfonyUsers
     */
    public function setLastLoginDatetime($lastLoginDatetime)
    {
        $this->last_login_datetime = $lastLoginDatetime;

        return $this;
    }

    /**
     * Get last_login_datetime
     *
     * @return \DateTime 
     */
    public function getLastLoginDatetime()
    {
        return $this->last_login_datetime;
    }

    /**
     * Set login_error_count
     *
     * @param integer $loginErrorCount
     * @return SymfonyUsers
     */
    public function setLoginErrorCount($loginErrorCount)
    {
        $this->login_error_count = $loginErrorCount;

        return $this;
    }

    /**
     * Get login_error_count
     *
     * @return integer 
     */
    public function getLoginErrorCount()
    {
        return $this->login_error_count;
    }

    /**
     * Set password_change_datetime
     *
     * @param \DateTime $passwordChangeDatetime
     * @return SymfonyUsers
     */
    public function setPasswordChangeDatetime($passwordChangeDatetime)
    {
        $this->password_change_datetime = $passwordChangeDatetime;

        return $this;
    }

    /**
     * Get password_change_datetime
     *
     * @return \DateTime 
     */
    public function getPasswordChangeDatetime()
    {
        return $this->password_change_datetime;
    }

    /**
     * Set is_locked
     *
     * @param integer $isLocked
     * @return SymfonyUsers
     */
    public function setIsLocked($isLocked)
    {
        $this->is_locked = $isLocked;

        return $this;
    }

    /**
     * Get is_locked
     *
     * @return integer
     */
    public function getIsLocked()
    {
        return $this->is_locked;
    }

    /**
     * Set locked_datetime
     *
     * @param \DateTime $lockedDatetime
     * @return SymfonyUsers
     */
    public function setLockedDatetime($lockedDatetime)
    {
        $this->locked_datetime = $lockedDatetime;

        return $this;
    }

    /**
     * Get locked_datetime
     *
     * @return \DateTime
     */
    public function getLockedDatetime()
    {
        return $this->locked_datetime;
    }
}
