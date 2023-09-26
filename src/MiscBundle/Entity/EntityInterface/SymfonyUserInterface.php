<?php
namespace MiscBundle\Entity\EntityInterface;

/**
 * WEBシステム ログインアカウント インターフェース
 */
interface SymfonyUserInterface
{
  const ACCOUNT_TYPE_USER = 'user';
  const ACCOUNT_TYPE_PRODUCT_EDITOR = 'product_editor';
  const ACCOUNT_TYPE_CLIENT = 'client';
  const ACCOUNT_TYPE_YAHOO_AGENT = 'yahoo_agent';

  /**
   * @return int
   */
  public function getId();

  /**
   * @return string
   */
  public function getUsername();

  /**
   * @return string
   */
  public function getPassword();

  /**
   * @return string
   */
  public function getClientName();

  /**
   * 取引先アカウントかどうか
   * @return boolean
   */
  public function isClient();

  /**
   * フォレストスタッフかどうか
   * @return boolean
   */
  public function isForestStaff();

  /**
   * Yahoo代理店アカウントかどうか
   * @return boolean
   */
  public function isYahooAgent();

  /**
   * アカウント種別取得
   * @return string
   */
  public function getAccountType();
}
