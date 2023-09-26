<?php

namespace AppBundle\Controller;

use MiscBundle\Entity\Repository\SymfonyUsersRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\Repository\TbCompanyRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class AdminController extends BaseController
{

  /**
   * 管理画面 TOP
   */
  public function indexAction()
  {
    $account = $this->getLoginUser();

    // 管理ページ表示
    return $this->render('AppBundle:Admin:index.html.twig', [
      'account' => $account
    ]);
  }


  /**
   * 管理画面 ユーザ一覧画面
   */
  public function accountAction(Request $request)
  {
    // 画面表示がtwigで実装されているのでPHPでソート
    // sortKeyが指定されていれば対応するものに変換
    $sortTargetKeys = [
      'isActive' => 'is_active',
      'lastLoginDatetime' => 'last_login_datetime',
      'lock' => 'is_locked'
    ];
    $requestSortKey = $request->get('sortKey') ?: 'isActive';
    $sortKey = isset($sortTargetKeys[$requestSortKey]) ? $sortTargetKeys[$requestSortKey] : 'is_active';

    $requestSortOrder = $request->get('sortOrder') ?: 'ASC';
    $sortOrder = in_array($requestSortOrder, ['ASC', 'DESC'], true) ? $requestSortOrder : 'ASC';

    // ステータスによる絞り込み
    $isActiveStatusOnly = $request->get('displayStatus') == "active" || $request->get('displayStatus') == null ? true: false;

    /** @var \Doctrine\ORM\EntityRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    $qb = $repo->createQueryBuilder('u')
      ->addOrderBy('u.' . $sortKey, $sortOrder)
      ->addOrderBy('u.id', 'ASC');
      if ($isActiveStatusOnly) {
        $qb->where('u.is_active <> 0');
      }
    $users = $qb->getQuery()
      ->getResult();
    
    // 管理ページ表示
    return $this->render('AppBundle:Admin:account.html.twig', [
      'users' => $users
    ]);
  }

  /**
   * (Ajax) アカウント情報取得
   */
  public function findAccountAction(Request $request)
  {
    $id = $request->get('id');
    $user = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($id);

    $logger = $this->get('misc.util.batch_logger');

    // TODO ADMIN権限がなければログイン中の自分のアカウントのみ取得可能

    $result = ['valid' => 0];
    if ($user) {
      $result = $user->toArray();
      $result['valid'] = 1;

      //現在の所有権限取得
      $roles = $user->getRoles();
      $allRoles = [
        'role_customer_manager',
        'role_delivery_manager',
        'role_system_manager',
        'role_system_user',
        'role_score_browsing',
        'role_sales_product_account',
        'role_sales_product_default_display',
        'role_product_management_browsing',
        'role_product_management_updating',
      ];

      $haveRole = [];
      foreach($roles as $role) {
        $haveRole[] = $role->getRole();
      }
      foreach ($allRoles as $allRole){
        $result[$allRole] = array_search(strtoupper($allRole),$haveRole) ? true : '';
      }
    }

    return new JsonResponse($result);
  }

  /**
   * (Ajax) ユーザ情報 更新処理
   */
  public function updateAccountAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'errors' => []
    ];
    $errors = [];

    try {
      $id = $request->get('id');
      if (!$request->get('id')) {
        throw new \RuntimeException('no id');
      }
      $em = $this->getDoctrine()->getManager('main');
      // 新規
      if ($id == 'new') {
        $user = new SymfonyUsers();
        $user->setRoles('ROLE_USER'); // 権限はひとまず決め打ち

        // 初期倉庫設定 （この手順が必要なのが Doctrineの最もiketeないところと思います。default設定してるのに...）
        /** @var TbWarehouseRepository $repoWarehouse */
        $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
        $warehouse = $repoWarehouse->find(TbWarehouseRepository::DEFAULT_WAREHOUSE_ID);
        $user->setWarehouse($warehouse);

        /** @var TbWarehouseRepository $repoWarehouse */
        $repoCompany = $this->getDoctrine()->getRepository('MiscBundle:TbCompany');
        $company = $repoCompany->find(TbCompanyRepository::DEFAULT_COMPANY_ID);
        $user->setCompany($company);

        $em->persist($user);

        $result['is_new'] = 1;

      } else {
        $user = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($id);
        if (!$user) {
          throw new \RuntimeException('ユーザ情報の取得に失敗しました。 [' . $id . ']');
        }

        // ADMIN権限がなければログイン中の自分のアカウントのみ更新可能
        $account = $this->getLoginUser();
        if ($account->getId() != $user->getId()) {
          /** @var AuthorizationChecker $authChecker */
          $authChecker = $this->get('security.authorization_checker');
          if (! $authChecker->isGranted('ROLE_ADMIN')) {
            throw new \RuntimeException('編集する権限がありません。');
          }
        }
      }
      // ユーザコードがすでに登録されている場合エラー
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
      $userCd = trim($request->get('user_cd')) === '' ? NULL : trim($request->get('user_cd')); // 空文字はnullに変換
      $checkUser = $repo->findByCdWithDifferentId($userCd, $id);
      if ($checkUser != null) {
        throw new \RuntimeException('既に登録されているユーザコードのため登録できません。');
      }

      // 他の入力チェックはなし。する場合は、諦めてForm(Type)を作ってしまう。
      $user->setUsername($request->get('username'));
      $user->setUserCd($userCd);
      if ($user->getUserCd() === '') {
        $user->setUserCd(null);
      }
      $user->setEmail($request->get('email'));
      $user->setIsActive($request->get('is_active'));
      $user->setIsLocked($request->get('is_locked'));
      if ($request->get('is_locked') === strval(SymfonyUsers::IS_LOCKED)) {
        $user->setLockedDatetime(new \DateTime());
      } else {
        $user->setLockedDatetime(null);
        $user->setLoginErrorCount(0);
      }
      $user->setBuyerOrder($request->get('buyer_order'));
      $user->setNeAccount($request->get('ne_account'));
      $user->setNePassword($request->get('ne_password'));
      // パスワードは設定されていれば更新する
      $password = trim($request->get('password'));
      if (strlen($password)) {
        $encoder = $this->container->get('security.password_encoder');
        $encoded = $encoder->encodePassword($user, $password);
        $user->setPassword($encoded);
        $user->setPasswordChangeDatetime(new \DateTime()); // 更新日付を最新に
      }


      //現在の権限取得
      $nowRoles = $user->getRoles();
      foreach($nowRoles as $nowRole) {
        $rolesStringList[] = $nowRole->getRole();
        $copyNowRoles[] = $nowRole->getRole();
      }

      $allRoles = [
        'role_customer_manager',
        'role_delivery_manager',
        'role_system_manager',
        'role_system_user',
        'role_score_browsing',
        'role_sales_product_account',
        'role_sales_product_default_display',
        'role_product_management_browsing',
        'role_product_management_updating',
      ];
      $i = 0;

      // todo コード修正
      foreach ($allRoles as $allRole){
        $bigRoleStr[] = strtoupper($allRole);
        //allRolesを代入
        $eachRoles[] = $allRole;
        if($request->get("$eachRoles[$i]") == 'false')
        {
          $judgeRoles[] = '';
        }
        else if($request->get("$eachRoles[$i]"))
        {
          $judgeRoles[] = 'true';
        }
        else
        {
          $judgeRoles[] = '';
        }
        //権限付与・削除処理
        //チェックがあれば
        if($judgeRoles[$i])
        {
          //現在の配列にチェックされた権限がなければ追加
          if(!array_search("$bigRoleStr[$i]",$copyNowRoles))
          {
            array_push($rolesStringList,$bigRoleStr[$i]);
          }
        }
        //チェックがなければ
        else
        {
          //現在の配列にチェックされなかった権限があれば削除
          if(array_search("$bigRoleStr[$i]",$copyNowRoles))
          {
            $rolesStringList = array_diff($rolesStringList,array("$bigRoleStr[$i]"));
            $rolesStringList = array_values($rolesStringList);

          }
        }
        $i++;
      }
      $resultRoles = implode("|", $rolesStringList);

      $user->setRoles($resultRoles);

      $em->flush();

      $result['data'] = [
          'id' => $user->getId()
        , 'username' => $user->getUsername()
        , 'user_cd' => $user->getUserCd()
        , 'password' => '' // いらない
        , 'email' => $user->getEmail()
        , 'ne_account' => $user->getNeAccount()
        , 'ne_password' => $user->getNePassword()
        , 'is_active' => $user->getIsActiveValue()
        , 'is_locked' => $user->getIsLocked()
        , 'buyer_order' => $user->getBuyerOrder()
        , 'role_customer_manager' => array_search('ROLE_CUSTOMER_MANAGER',$rolesStringList) ? true : ''
        , 'role_delivery_manager' => array_search('ROLE_DELIVERY_MANAGER',$rolesStringList) ? true : ''
        , 'role_system_manager' => array_search('ROLE_SYSTEM_MANAGER',$rolesStringList) ? true : ''
        , 'role_system_user' => array_search('ROLE_SYSTEM_USER',$rolesStringList) ? true : ''
        , 'role_score_browsing' => array_search('ROLE_SCORE_BROWSING',$rolesStringList) ? true : ''
        , 'role_sales_product_account' => array_search('ROLE_SALES_PRODUCT_ACCOUNT',$rolesStringList) ? true : ''
        , 'role_sales_product_default_display' => array_search('ROLE_SALES_PRODUCT_DEFAULT_DISPLAY',$rolesStringList) ? true : ''
        , 'role_product_management_browsing' => array_search('ROLE_PRODUCT_MANAGEMENT_BROWSING',$rolesStringList) ? true : ''
        , 'role_product_management_updating' => array_search('ROLE_PRODUCT_MANAGEMENT_UPDATING',$rolesStringList) ? true : ''
      ];

      $logger->info(print_r($result, true));

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());
      if (!$errors) {
        $errors[] = $e->getMessage();
      }
      $result['errors'] = $errors;

      $logger->info('account edit error: ' .print_r($result, true));
    }
    return new JsonResponse($result);
  }

  /**
   * (Ajax) ユーザ情報 削除処理
   */
  public function deleteAccountAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'errors' => []
    ];
    $errors = [];

    try {

      // ログインユーザが管理者権限でないと削除は不可
      /** @var AuthorizationChecker $authChecker */
      $authChecker = $this->get('security.authorization_checker');
      if (!$authChecker->isGranted('ROLE_ADMIN')) {
        $errors[] = '管理者権限が無いため、削除できません。';
        throw new \RuntimeException('no auth');
      }

      $id = $request->get('id');
      if (!$id) {
        $errors[] = 'no id';
        throw new \RuntimeException('no id');
      }

      $em = $this->getDoctrine()->getManager('main');

      /** @var SymfonyUsersRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
      /** @var SymfonyUsers $user */
      $user = $repo->find($id);

      if ($user) {

        // 管理権限ユーザは削除不可。（DBから直接削除する。）
        if ($user->hasRole('ROLE_ADMIN')) {
          throw new \RuntimeException('管理権限ユーザはこの画面から削除できません。DBから直接削除して下さい。');
        }

        $em->remove($user);
        $em->flush();

        $result['data'] = [
            'id' => $id
          , 'username' => $user->getUsername()
        ];

      } else {
        $errors[] = 'no user';
      }

    } catch (\Exception $e) {
      if (!$errors) {
        $errors[] = $e->getMessage();
      }
    }

    if ($errors) {
      $logger->info(print_r($errors, true));
    }

    $result['errors'] = $errors;
    return new JsonResponse($result);
  }

}
