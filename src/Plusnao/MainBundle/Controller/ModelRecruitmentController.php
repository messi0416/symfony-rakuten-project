<?php

namespace Plusnao\MainBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use MiscBundle\Entity\TbRakutenReviews;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use MiscBundle\Entity;
use Symfony\Component\HttpFoundation\Response;

class ModelRecruitmentController extends BaseController
{
  /**
   * キッズモデル募集フォーム
   * @param Request $request
   * @return Response
   */
  public function kidsFormAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $data = [];

    try {
      if ($request->isMethod(Request::METHOD_POST)) {
        $isValid = true;

        $data['name']     = trim($request->get('name', ''));
        $data['address']  = trim($request->get('address', ''));
        $data['phone']    = trim($request->get('phone', ''));
        $data['mail']     = trim($request->get('mail', ''));
        $data['age_y']    = trim($request->get('age_y', ''));
        $data['age_m']    = trim($request->get('age_m', ''));
        $data['height']   = trim($request->get('height', ''));
        $data['weight']   = trim($request->get('weight', ''));
        $data['comment']  = trim($request->get('comment', ''));

        // 電話番号は半角変換してあげる。（本当はハイフンも統一したい）
        $data['mail'] = mb_convert_kana($data['mail'], 'as');
        if (!strlen($data['age_m'])) {
          $data['age_m'] = '0';
        }

        // バリデーション
        // 必須チェック
        $required = [
            'name' => '申し込み者名前'
          , 'address' => '住所'
          , 'age_y' => 'モデルの年齢（歳）'
          , 'age_m' => 'モデルの年齢（月）'
          , 'height' => 'モデルの身長'
          , 'weight' => 'モデルの体重'
        ];
        $errors = [];
        foreach($data as $k => $v) {
          if (isset($required[$k]) && !strlen($v)) {
            $errors[] = sprintf('%s を入力してください。', $required[$k]);
          }
        }

        // 電話 or メールアドレスが必須
        if (!strlen($data['phone']) && !strlen($data['mail'])) {
          $errors[] = sprintf('電話番号かメールアドレスのどちらかを必ず入力してください。');
        }

        // 電話 書式チェック
        if (!preg_match('/^[\d-]+$/', $data['phone'])) {
          $errors[] = '電話番号は、半角数字とハイフンのみで入力してください。';
        }

        // メール 書式超簡易チェック
        if (!preg_match('|^[0-9A-Za-z_./?+-]+@([0-9A-Za-z-]+\.)+[0-9A-Za-z-]+$|', $data['mail'])) {
          $errors[] = '受け付けられない書式のメールアドレスです。間違いが無いかご確認ください。';
        }

        // 画像チェック
        $images = $request->files->get('images');
        if (!$images || empty($images[0])) { // 空の場合は { [0] => NULL } の気持ち悪い配列
          $errors[] = '画像をアップロードしてください。';
        }

        $isValid = empty($errors);
        if ($isValid) {

          // データ登録
          $em = $this->getDoctrine()->getManager('main');
          $entry = new Entity\ModelRecruitmentEntryKids();

          $entry->setName($data['name']);
          $entry->setAddress($data['address']);
          $entry->setPhone($data['phone']);
          $entry->setMail($data['mail']);
          $entry->setAgeY(intval($data['age_y']));
          $entry->setAgeM(intval($data['age_m']));
          $entry->setAgeMonths($entry->getAgeY() * 12 + $entry->getAgeM());
          $entry->setHeight($data['height']);
          $entry->setWeight($data['weight']);
          $entry->setComment($data['comment']);

          $em->persist($entry);
          $em->flush();

          // 画像登録
          /** @var FileUtil $fileUtil */
          $fileUtil = $this->get('misc.util.file');
          $imageDir = sprintf('%s/images/model/%s', $fileUtil->getDataDir(), $entry->getImageDirName());

          $fs = new FileSystem();
          // すでにディレクトリがあれば何かおかしい。怖いので終了。
          if ($fs->exists($imageDir)) {
            throw new \RuntimeException(sprintf('画像の保存に失敗しました。（invalid directory : %d）', $entry->getId()));
          }
          $fs->mkdir($imageDir);

          /** @var UploadedFile $image */
          $registeredImages = [];
          foreach($images as $i => $image) {
            $fileName = sprintf('%02d.%s', $i+1, strtolower($image->getClientOriginalExtension()));
            $logger->info(sprintf('upload image : %s => %s/%s', $image->getClientOriginalName(), $imageDir, $fileName));

            $image->move($imageDir, $fileName);

            $registeredImages[] = sprintf('%s/%s', $imageDir, $fileName);
          }

          // メール送信（welcome および メールアドレス入力者）
          $fromAddress = $this->getParameter('front_mail_from');
          $fromName = $this->getParameter('front_mail_from_name');

          $subject = 'キッズモデルへのご応募ありがとうございました。';

          $templateData = $data;
          /** @var \Twig_Environment $twig */
          $twig = $this->get('twig');
          $mailTemplate = $twig->load('PlusnaoMainBundle:ModelRecruitment:kids-form-mail-body-admin.txt.twig');
          $body = $mailTemplate->render($templateData);

          /** @var \Swift_Mailer $mailer */
          $mailer = $this->get('mailer');
          $failed = [];

          // 運営充て
          $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($fromAddress, $fromName)
            ->setTo($fromAddress, $fromName) // 運営充てはFROMと同じ宛先
            ->setBody($body)
          ;

          // 画像添付
          foreach($registeredImages as $path) {
            $info = new File($path);

            $attachment = \Swift_Attachment::fromPath($path)
                              ->setContentType($info->getMimeType());
            $message->attach($attachment);
          }

          $mailer->send($message);

          // spool フラッシュ ... なぜspoolを使っているのか。・・・
          $transport = $mailer->getTransport();
          if (!$transport instanceof \Swift_Transport_SpoolTransport) {
            $errorMessage = 'エラー：応募メールが送信できませんでした。 SwiftMailer: no spool transport';
            $logger->error($errorMessage);
            throw new \RuntimeException($errorMessage);
          }

          $spool = $transport->getSpool();
          if (!$spool instanceof \Swift_MemorySpool) {
            $errorMessage = 'エラー：応募メールが送信できませんでした。 SwiftMailer: no file spool';
            $logger->error($errorMessage);
            throw new \RuntimeException($errorMessage);
          }

          /** @var \Swift_Transport $transportReal */
          $transportReal = $this->container->get('swiftmailer.transport.real');
          $result = $spool->flushQueue($transportReal, $failed);
          $logger->info('recruit mail send result' . print_r($result, true));

          // TODO 完了画面へリダイレクト （開発中はひとまずフォームへ戻る）
          $this->setFlash('success', "（開発中）登録完了！！\n" . $data['name']); // FOR DEBUG
          return $this->redirectToRoute('plusnao_model_recruitment_kids_complete'); // コメントアウトFOR DEBUG

        } else {
          $message = "入力エラーがあります。\n\n" . implode("\n", $errors);
          $this->setFlash('danger', $message);
        }
      }

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());
      $this->setFlash('danger', "エラーが発生したためデータの登録ができませんでした。");
    }

    return $this->render('PlusnaoMainBundle:ModelRecruitment:kids-form.html.twig', [
      'data' => $data
    ]);
  }

  /**
   * キッズモデル募集フォーム
   * @param Request $request
   * @return Response
   */
  public function kidsCompleteAction(Request $request)
  {
    return $this->render('PlusnaoMainBundle:ModelRecruitment:kids-complete.html.twig', [
    ]);
  }



}
