<?php

namespace AppBundle\Security\User;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use MiscBundle\Entity\EntityInterface\SymfonyUserClientInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
*/
class SymfonyUserProductEditor implements AdvancedUserInterface, \Serializable, SymfonyUserClientInterface
{
  /**
   * @var string
   */
  private $username;


  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return null;
  }

  /**
   * Set username
   * @param string $username
   * @return self
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
   * Get password
   *
   * @return string
   */
  public function getPassword()
  {
    return null;
  }

  /**
   * Get client_name
   *
   * @return string
   */
  public function getClientName()
  {
    return $this->getUsername();
  }

  /**
   * Get isActive
   *
   * @return boolean
   */
  public function getIsActive()
  {
    return true;
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
      $this->username
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
      $this->username
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
    private $roles = 'ROLE_PRODUCT_EDITOR';

    /**
     * @var Role[]
     */
    private $roleObjects = null;

  /**
   * 取引先アカウントかどうか
   * @return boolean
   */
  public function isClient()
  {
    return true;
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
    return false;
  }

  public function getAccountType()
  {
    return self::ACCOUNT_TYPE_PRODUCT_EDITOR;
  }

}
