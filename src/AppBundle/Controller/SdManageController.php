<?php
 
namespace AppBundle\Controller;
 
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\TbMainproducts;
use AppBundle\Entity\TbMainproductsCal;
use AppBundle\Entity\TbRakuteninformation;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbSdInformation;
use MiscBundle\Entity\TbSdInformationSku;
use MiscBundle\Entity\TbSdGenre;

/**
* スーパーデリバリー商品情報管理
* 
* @Route("/sdmanage")
*/
class SdManageController extends Controller
{
    /** ホーム表示
    * @Route("/", name="sdmanage_index")
    * 
    * @param Request $request
    * @return Response
    **/
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:SdManage:index.html.twig', [
        ]);

    }

    /**
    * 新規登録商品データCSV出力
    * プラスナオ登録商品より、スーパーデリバリーに登録する商品情報を取得する
    * 
    * @Route("/pltosd/{number}", requirements={"number" = "\d+"}, defaults={"number"="20"}, name="pltosd")
    * @Method({"GET", "POST"})
    *
    * @param Request $request
    * @return Response
    **/
    public function pltosdAction(Request $request, $number)
    {
        //tb_mainproductsよりtb_sd_informationに存在しない商品を取得する
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb
            ->select('tbmp')
            ->from('AppBundle:TbMainproducts', 'tbmp')
            ->leftjoin(
                'MiscBundle:TbSdInformation', 'tbsd',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'tbmp.daihyoSyohinCode = tbsd.daihyoSyohinCode'
            )
            ->where('tbsd.daihyoSyohinCode is null AND tbmp.soZaikoSu > 0')
            ->orderBy('tbmp.soZaikoSu', 'DESC')
            ->setMaxResults($number)
            ;
        $newinfo = $qb->getQuery()->getResult();
        
        if( $number != count($newinfo) ){
            $number = count($newinfo);
        }

        $datas = $this->get('request')->request->all();
        if($datas){
            return $this->pltosdExpcsvAction($datas['registFlag'], $number);
        }

        return $this->render('AppBundle:SdManage:expcsv.html.twig', [
             'maininfos' => $newinfo
            ,'number' => $number
        ]);

    }

    /**
    * 新規登録商品データCSV取込（registration_state=1）
    * プラスナオ登録商品より、スーパーデリバリーに登録する商品情報を取得する
    * 
    * @Route("/pltosd/expcsv", name="pltosd_expcsv")
    *
    * @return Response
    **/
    public function pltosdExpcsvAction(array $targets, $number)
    {
        // ini_set
        // 実行時間が長く、使うメモリが多ければ、設定したほうがいい
        ini_set('memory_limit', '2048M');
    	ini_set('max_execution_time', 0);

        // 出力する内容
        $contents = "";
        $fields = [
             'daihyoSyohinCode' => '代表商品コード'
            ,'daihyoSyohinName' => '商品名'
            ,'genre' => 'ジャンル'
            ,'target' => 'ターゲット'
            ,'syohinZokusei' => '商品属性'
            ,'aboutSize' => 'サイズ・容量'
            ,'aboutSozai' => '素材・成分'
            ,'shiyouChui' => '注意事項'
            ,'syohinCommentPC' => 'コメント'
            ,'NEDirectoryID' => 'NEディレクトリID'
            ,'YahooDirectoryID' => 'YAHOOディレクトリID'
            ];
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb
            ->select(
                'tbmp.daihyoSyohinCode
                ,tbmp.daihyoSyohinName
                ,9999 as genre
                ,99 as target
                ,99999 as syohinZokusei
                ,tbmp.aboutSize
                ,tbmp.aboutSozai
                ,tbmp.shiyouChui
                ,tbmp.syohinCommentPC
                ,tbmp.NEDirectoryID
                ,tbmp.YahooDirectoryID'
                )
            ->from('AppBundle:TbMainproducts', 'tbmp')
            ->leftjoin(
                'MiscBundle:TbSdInformation', 'tbsd',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'tbmp.daihyoSyohinCode = tbsd.daihyoSyohinCode'
            )
            ->where('tbsd.daihyoSyohinCode is null AND tbmp.soZaikoSu > 0')
            ->orderBy('tbmp.soZaikoSu', 'DESC')
            ->setMaxResults($number)
            ;
        $dat = $qb->getQuery()->getResult();

        /** @var StringUtil $stringUtil */
        $stringUtil = $this->get('misc.util.string'); 
        $contents .= $stringUtil->convertArrayToCsvLine($fields, array_keys($fields)).("\r\n");
        foreach($dat as $row){
            if(in_array($row['daihyoSyohinCode'], $targets)){
                $contents .= $stringUtil->convertArrayToCsvLine($row, array_keys($fields)).("\r\n");
            }
        }

        // uft8の文字コードをsjisに変換
    	$contents = mb_convert_encoding($contents, 'SJIS-win', 'UTF-8');
 
        // response作成
    	$response =  new Response($contents);
        // 出力csvファイルの名前を指定
    	$response->headers->set('Content-Type', "application/octet-stream; name=pltosd_expcsv_".date('YmdHis').".csv");
    	$response->headers->set('Content-Disposition', "attachment; filename=pltosd_expcsv_".date('YmdHis').".csv");

        return $response;

    }
        
    /**
    * 新規登録商品データCSV取り込み
    * 
    * @Route("/pltosd/impcsv/{exe}", defaults={"exe"="pre"}, name="pltosd_impcsv")
    *
    * @param Request $request
    * @return Response
    **/
    public function pltosdImpcsvAction(Request $request, $exe)
    {
        if($exe == 'exe'){
            //アップロードボタン押下後の取込処理
            /** @var BatchLogger $logger */
            $logger = $this->get('misc.util.batch_logger');
            $logger->info('superdelivery csv pltosd_import: start.');

            $logger->info(print_r($_FILES, true));
            $result = [
                'message' => null
            , 'info' => []
            ];

            try{
                //ファイル取得
                $files = $request->files->get('upload');
                if(count($files) > 1){
                    throw new \RuntimeException('アップロードできるファイルは１つです。');
                }
                $logger->info('uploaded : ' . print_r($files[0], true));

                //CSVからarrayへ変換
                $datas = $this->convertCsvToArray($files[0]);
                if(!$datas || count($datas) == 1){
                    throw new \RuntimeException('データがありません。');
                }else{
                    /**
                    *  @var EntityManager $em
                    */
                    $em = $this->getDoctrine()->getManager();
                    array_splice($datas, 0, 1); //1行目は項目名なので削除
                    $cnt = 1;
                    $date = (new \DateTime())->format('Y-m-d');

                    // TbSdInformation、TbSdInformationSkuクラスのメタデータを取得
                    $TbSdInfoMD = $em->getClassMetadata('MiscBundle:TbSdInformation');
                    $TbSdInfoSkuMD = $em->getClassMetadata('MiscBundle:TbSdInformationSku');

                    //CSV仕様("代表商品コード","商品名","ジャンル","ターゲット","商品属性","サイズ・容量","素材・成分","注意事項","コメント")
                    foreach($datas as $data){
                        //在庫限り（売り切り[1]）の判定
                        // tb_mainproducts_cal.受発注可能フラグ退避F(orderingAvoidFlg)⇒-1なら売り切り[1]
                        // tb_productchoiceitems.受発注可能フラグ(orderEnabled)⇒一つでも0なら売り切り[1]
                        $urikiriFlag = 0;
                        $repository = $em->getRepository(TbMainproductsCal::class);
                        $tbmpcal = $repository->findOneBy(
                            array('daihyoSyohinCode' => $data[0])
                        );
                        $ordertingAvoidFlg = $tbmpcal->getOrderingAvoidFlg();
                        if($ordertingAvoidFlg){
                            $urikiriFlag = 1;
                        }
                        $repository = null;
                        $repository = $em->getRepository(TbProductchoiceitems::class);
                        $tbpc = $repository->findBy(
                            array('daihyoSyohinCode' => $data[0],
                                  'orderEnabled' => 0)
                        );
                        if($tbpc){
                            $urikiriFlag = 1;
                        }
                        $repository = null;
                        // オブジェクトにセット
                        $tbsdinfo =  new TbSdInformation();
                        $tbsdinfo->setDaihyoSyohinCode($data[0]);
                        $tbsdinfo->setDaihyoSyohinName($data[1]);
                        $tbsdinfo->setBaseSize($data[5]);
                        $tbsdinfo->setBaseSozai($data[6]);
                        $tbsdinfo->setBaseChuiJiko($data[7]);
                        $tbsdinfo->setBaseComment($data[8]);
                        $tbsdinfo->setSdbango($cnt++);
                        $tbsdinfo->setRegistrationState(1);
                        $tbsdinfo->setSyohinTitle(mb_strcut($data[1], 0, $TbSdInfoMD->fieldMappings['syohinTitle']['length']));
                        $tbsdinfo->setGenre($data[2]);
                        $tbsdinfo->setTarget($data[3]);
                        $tbsdinfo->setSyohinZokusei($data[4]);
                        $tbsdinfo->setSize(mb_strcut($data[5], 0, $TbSdInfoMD->fieldMappings['size']['length']));
                        $tbsdinfo->setSozai(mb_strcut($data[6], 0, $TbSdInfoMD->fieldMappings['sozai']['length']));
                        $tbsdinfo->setSyohinFuda(3);
                        $tbsdinfo->setChuiJiko(mb_strcut($data[7], 0, $TbSdInfoMD->fieldMappings['chuiJiko']['length']));
                        $tbsdinfo->setComment(mb_strcut($data[8]. "\n\n" .$data[1], 0, $TbSdInfoMD->fieldMappings['comment']['length']));
                        $tbsdinfo->setSyukkaNoki(1);
                        $tbsdinfo->setZaikoKagiri($urikiriFlag);
                        $tbsdinfo->setBettoSoryo(0);
                        $tbsdinfo->setlastUpdate($date);
                        $em->persist($tbsdinfo);
                    }
                    $result['info']['tbsdinfo'] = 'tb_sd_information:' .($cnt - 1) .'件のデータ作成';
                    //****トランザクション開始****//
                    $em->getConnection()->beginTransaction();
                    try{
                        $em->flush();
                        $em->clear();
                        //更新したTbSdInformationをもとに、TbSdInformationSkuを更新する
                        //新規登録対象はTbSdInformation.syuppinDate(出品日)=nullのレコード
                        //TbProductchoiceitemsとTbRakuteninformationを結合する
                        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
                        $qb
                            ->select(
                                'tbsd.daihyoSyohinCode
                                ,tbsd.sdBango
                                ,tbpc.neSyohinSyohinCode
                                ,tbpc.colname
                                ,tbpc.rowname
                                ,tbrk.baikaTanka')
                            ->from('MiscBundle:TbSdInformation', 'tbsd')
                            ->leftjoin(
                                'MiscBundle:TbProductchoiceitems', 'tbpc',
                                \Doctrine\ORM\Query\Expr\Join::WITH,
                                'tbsd.daihyoSyohinCode = tbpc.daihyoSyohinCode'
                            )
                            ->leftjoin(
                                'AppBundle:TbRakuteninformation', 'tbrk',
                                \Doctrine\ORM\Query\Expr\Join::WITH,
                                'tbsd.daihyoSyohinCode = tbrk.daihyoSyohinCode'
                            )
                            ->where('tbsd.syuppinDate is null')
                            ->orderBy('tbsd.sdBango', 'ASC')
                            ->addOrderBy('tbpc.displayOrder', 'ASC')
                            ;
                        $newTbsdinfos = $qb->getQuery()->getResult();
                        if(!empty($newTbsdinfos)){
                            $cnt = 1;
                            $crtDaihyoSyohinCode = '';
                            foreach($newTbsdinfos as $data){
                                // 200セットまでしか登録できないため、元SKUの数に応じてセット毎数量を調整する
                                // 1～66件⇒1個、10個、30個、67～100件⇒10個、30個、101件～200件⇒30個、201件～⇒エラー表示
                                if($crtDaihyoSyohinCode <> $data['daihyoSyohinCode']){
                                    $crtDaihyoSyohinCode = $data['daihyoSyohinCode'];
                                    $repository = $em->getRepository(TbProductchoiceitems::class);
                                    $tbpc = $repository->findBy(
                                        array('daihyoSyohinCode' => $crtDaihyoSyohinCode)
                                    );
                                    $repository = null;
                                }
                                if(count($tbpc) < 67){
                                    //**** セット毎数量＝1 ****//
                                    $tbsdinfosku = new TbSdInformationSku();
                                    $tbsdinfosku->setDaihyoSyohinCode($data['daihyoSyohinCode']);
                                    $tbsdinfosku->setColname($data['colname']);
                                    $tbsdinfosku->setRowname($data['rowname']);
                                    $tbsdinfosku->setSdBango($data['sdBango']);
                                    $tbsdinfosku->setSetBango($cnt++);
                                    $tbsdinfosku->setKisyaHinban($data['neSyohinSyohinCode']);
                                    $tbsdinfosku->setUchiwake(mb_strcut($data['colname'] . '×'. $data['rowname'], 0, $TbSdInfoSkuMD->fieldMappings['uchiwake']['length']));
                                    $tbsdinfosku->setSankoKakakuSyubetsu(4);
                                    $tbsdinfosku->setSankoKakaku(0);
                                    $tbsdinfosku->setHanbaiTanka($data['baikaTanka']);
                                    $tbsdinfosku->setSetGotoSuryo(1);
                                    $tbsdinfosku->setZaikoSu(0);
                                    $tbsdinfosku->setZaikoRendo(1);
                                    $tbsdinfosku->setLastUpdate($date);
                                    $em->persist($tbsdinfosku);
                                }
                                if(count($tbpc) < 101){
                                    //**** セット毎数量＝10 ****//
                                    $tbsdinfosku = new TbSdInformationSku();
                                    $tbsdinfosku->setDaihyoSyohinCode($data['daihyoSyohinCode']);
                                    $tbsdinfosku->setColname($data['colname']);
                                    $tbsdinfosku->setRowname($data['rowname']);
                                    $tbsdinfosku->setSdBango($data['sdBango']);
                                    $tbsdinfosku->setSetBango($cnt++);
                                    $tbsdinfosku->setKisyaHinban($data['neSyohinSyohinCode']);
                                    $tbsdinfosku->setUchiwake(mb_strcut($data['colname'] . '×'. $data['rowname'], 0, $TbSdInfoSkuMD->fieldMappings['uchiwake']['length']));
                                    $tbsdinfosku->setSankoKakakuSyubetsu(4);
                                    $tbsdinfosku->setSankoKakaku(0);
                                    $tbsdinfosku->setHanbaiTanka(round($data['baikaTanka'] * 0.95)); //5%OFF
                                    $tbsdinfosku->setSetGotoSuryo(10);
                                    $tbsdinfosku->setZaikoSu(0);
                                    $tbsdinfosku->setZaikoRendo(1);
                                    $tbsdinfosku->setLastUpdate($date);
                                    $em->persist($tbsdinfosku);
                                }
                                if(count($tbpc) < 201){
                                    //**** セット毎数量＝30 ****//
                                    $tbsdinfosku = new TbSdInformationSku();
                                    $tbsdinfosku->setDaihyoSyohinCode($data['daihyoSyohinCode']);
                                    $tbsdinfosku->setColname($data['colname']);
                                    $tbsdinfosku->setRowname($data['rowname']);
                                    $tbsdinfosku->setSdBango($data['sdBango']);
                                    $tbsdinfosku->setSetBango($cnt++);
                                    $tbsdinfosku->setKisyaHinban($data['neSyohinSyohinCode']);
                                    $tbsdinfosku->setUchiwake(mb_strcut($data['colname'] . '×'. $data['rowname'], 0, $TbSdInfoSkuMD->fieldMappings['uchiwake']['length']));
                                    $tbsdinfosku->setSankoKakakuSyubetsu(4);
                                    $tbsdinfosku->setSankoKakaku(0);
                                    $tbsdinfosku->setHanbaiTanka(round($data['baikaTanka'] * 0.92)); //8%OFF
                                    $tbsdinfosku->setSetGotoSuryo(30);
                                    $tbsdinfosku->setZaikoSu(0);
                                    $tbsdinfosku->setZaikoRendo(1);
                                    $tbsdinfosku->setLastUpdate($date);
                                    $em->persist($tbsdinfosku);
                                }else{
                                    throw new \RuntimeException('代表商品コード:' .$data['daihyoSyohinCode'] .':元SKUが' .count($tbpc) .'件あります。');
                                }
                            $em->flush();
                            $em->clear();
                            }
                            $result['info']['tbsdinfosku'] = 'tb_sd_information_sku:' .($cnt - 1) .'件のデータ作成';
                        }
                        //****コミット****//
                        $em->getConnection()->commit();
                        $result['info']['msg'] = '管理データ作成完了しました。アップロード用ファイルをダウンロードできます。';
                    } catch( \Exception $e) {
                        //****ロールバック****//
                        $em->getConnection()->rollback();
                        $em->close();
                        $logger->error($e->getMessage());
                        $logger->error($e->getTraceAsString());

                        $result['error'] = $e->getMessage();
                    }
                }
            } catch(\Exception $e){
                $logger->error($e->getMessage());
                $logger->error($e->getTraceAsString());

                $result['error'] = $e->getMessage();
            }
            return new JsonResponse($result);
        }
        return $this->render('AppBundle:SdManage:impcsv.html.twig', [
        ]);
    }

    /**
    * SDアップロード用新規登録商品データCSV出力
    * 
    * @Route("/sdexp/new", name="sdexp_new")
    *
    * @param Request $request
    * @return Response
    **/
    public function sdexpNewAction(Request $request)
    {
        // ini_set
        // 実行時間が長く、使うメモリが多ければ、設定したほうがいい
        ini_set('memory_limit', '2048M');
    	ini_set('max_execution_time', 0);

        // 出力する内容
        $contents = "";
        $fields = [
             'sdBango' => '番号'
            ,'syohinTitle' => '商品名'
            ,'brandName' => 'ブランド名'
            ,'genre' => 'ジャンル'
            ,'target' => 'ターゲット'
            ,'syohinZokusei' => '商品属性'
            ,'size' => 'サイズ・容量'
            ,'sozai' => '素材・成分'
            ,'seisanchi' => '生産地'
            ,'naiyoRyo' => '内容量'
            ,'genzaiRyo' => '原材料'
            ,'hozonHoho' => '保存方法'
            ,'kikakuHozoku' => '規格補足'
            ,'package' => 'パッケージ'
            ,'seizoNen' => '製造年'
            ,'syohinFuda' => '商品札'
            ,'tokuteiHoken' => '特定保健用食品'
            ,'chuiJiko' => '注意事項'
            ,'comment' => 'コメント'
            ,'kyokaBango' => '許可番号'
            ,'syukkaNoki' => '出荷（納期）'
            ,'syukkaYotei' => '出荷予定'
            ,'zaikoKagiri' => '在庫限り'
            ,'bettoSoryo' => '別途送料'
            ,'kisyaHinban' => '貴社品番'
            ,'janCode' => 'JAN'
            ,'uchiwake' => '内訳'
            ,'sankoKakakuSyubetsu' => '参考価格種別'
            ,'sankoKakaku' => '参考価格'
            ,'hanbaiTanka' => '販売単価'
            ,'setGotoSuryo' => 'セット毎数量'
            ,'zaikoSu' => '在庫数'
            ,'zaikoRendo' => '在庫連動'
            ];
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb
            ->select(
                'tbsd.sdBango
                ,tbsd.syohinTitle
                ,tbsd.brandName
                ,tbsd.genre
                ,tbsd.target
                ,tbsd.syohinZokusei
                ,tbsd.size
                ,tbsd.sozai
                ,tbsd.seisanchi
                ,tbsd.naiyoRyo
                ,tbsd.genzaiRyo
                ,tbsd.hozonHoho
                ,tbsd.kikakuHosoku
                ,tbsd.package
                ,tbsd.seizoNen
                ,tbsd.syohinFuda
                ,tbsd.tokuteiHoken
                ,tbsd.chuiJiko
                ,tbsd.comment
                ,tbsd.kyokaBango
                ,tbsd.syukkaNoki
                ,tbsd.syukkaYotei
                ,tbsd.zaikoKagiri
                ,tbsd.bettoSoryo
                ,tbsdsku.kisyaHinban
                ,tbsdsku.janCode
                ,tbsdsku.uchiwake
                ,tbsdsku.sankoKakakuSyubetsu
                ,tbsdsku.sankoKakaku
                ,tbsdsku.hanbaiTanka
                ,tbsdsku.setGotoSuryo
                ,tbsdsku.zaikoSu
                ,tbsdsku.zaikoRendo'
                )
            ->from('MiscBundle:TbSdInformation', 'tbsd')
            ->leftjoin(
                'MiscBundle:TbSdInformationSku', 'tbsdsku',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'tbsd.sdBango = tbsdsku.sdBango'
            )
            ->where('tbsd.registrationState = 1')
            ->orderBy('tbsd.sdBango', 'ASC')
            ->addOrderBy('tbsdsku.setBango', 'ASC')
            ;
        $dat = $qb->getQuery()->getResult();


        /** @var StringUtil $stringUtil */
        $stringUtil = $this->get('misc.util.string'); 
        $contents .= $stringUtil->convertArrayToCsvLine($fields, array_keys($fields)).("\r\n");
        foreach($dat as $row){
            $contents .= $stringUtil->convertArrayToCsvLine($row, array_keys($fields)).("\r\n");
        }

        // uft8の文字コードをsjisに変換
    	$contents = mb_convert_encoding($contents, 'SJIS-win', 'UTF-8');
 
        // response作成
    	$response =  new Response($contents);
        // 出力csvファイルの名前を指定
    	$response->headers->set('Content-Type', "application/octet-stream; name=sdupload_".date('YmdHis').".csv");
    	$response->headers->set('Content-Disposition', "attachment; filename=sdupload_".date('YmdHis').".csv");

        return $response;

    }

    /**
    * SDアップロード用新規登録商品データ画像出力
    * 
    * @Route("/sdexp/img", name="sdexp_img")
    *
    * @param Request $request
    * @return Response
    **/
    public function sdexpImgAction(Request $request)
    {
        //*** 対象の代表商品コードに紐づく画像情報を取得 ***//
        // TbSdInformation.RegistrationState=1のレコード
        // TbMainproductsと代表商品コードで内部結合して情報取得
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb
            ->select(
                'tbmp.picnameP1
                ,tbmp.picnameP2
                ,tbmp.picnameP3
                ,tbmp.picnameP4
                ,tbmp.picnameP5
                ,tbmp.picnameP6
                ,tbmp.picnameP7
                ,tbmp.picnameP8
                ,tbmp.picnameP9
                ,tbmp.picfolderP1
                ,tbmp.picfolderP2
                ,tbmp.picfolderP3
                ,tbmp.picfolderP4
                ,tbmp.picfolderP5
                ,tbmp.picfolderP6
                ,tbmp.picfolderP7
                ,tbmp.picfolderP8
                ,tbmp.picfolderP9
                ,tbsd.daihyoSyohinCode'
            )
            ->from('AppBundle:TbMainproducts', 'tbmp')
            ->innerJoin(
                'MiscBundle:TbSdInformation', 'tbsd',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'tbmp.daihyoSyohinCode = tbsd.daihyoSyohinCode'
            )
            ->where('tbsd.registrationState = 1')
            ->orderBy('tbsd.sdBango', 'ASC')
            ;
        $picinfo = $qb->getQuery()->getResult();

        //*** Zipファイル作成 ***//
        // zipクラスのインスタンスを生成
        $zip = new \ZipArchive();
 
        // 出力時のファイル名
        $outFileName = 'sdupload_' .date('YmdHis') .'.zip';
 
        // 作業ファイルパスを生成
        $tmpFilePath = tempnam(sys_get_temp_dir(), 'tmpimg');
 
        // 作業ファイルをオープン
        $result = $zip->open($tmpFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if($result !== true) {
            return false;
        }

        // 画像ディレクトリ
        $productImageDir = $this->getParameter('product_image_dir');

        // 貴社品番取得のためのレポジトリ
        $repository = $this->getDoctrine()->getRepository(TbSdInformationSku::class);

        foreach($picinfo as $dat){
            // 貴社品番取得
            $tbsdinfosku = $repository->findOneBy(
                array('daihyoSyohinCode' => $dat['daihyoSyohinCode'])
            );
            $kisyaHinban = $tbsdinfosku->getKisyaHinban();
            for($i = 1; $i <= 9; $i++){
                //配列のキーを動的に作成
                $keyPicfolder = 'picfolderP' .$i;
                $keyPicname = 'picnameP' .$i;
                if(!$dat[$keyPicname]){
                    break;
                }
                // 圧縮するファイルを定義
                $addFilePath = file_get_contents($productImageDir .'/' .$dat[$keyPicfolder] .'/' .$dat[$keyPicname]);
                //ファイル名は「貴社品番_01.jpg～」
                $addFileName = $kisyaHinban .sprintf('_%02d.jpg', $i);
                // zipファイルに追加（繰り返せば複数ファイル追加可能）
                $zip->addFromString($addFileName , $addFilePath);
            }
        }

        // ストリームを閉じる
        $zip->close();
        
        // response作成
    	$response = new Response();
        // 出力csvファイルの名前を指定
    	$response->headers->set('Content-Type', "application/octet-stream; name=" .$outFileName);
    	$response->headers->set('Content-Disposition', "attachment; filename=" .$outFileName);
        $response->sendHeaders();
        $response->setContent(readfile($tmpFilePath));
    	// $response->headers->set('Content-Length: '.filesize($tmpFilePath));

        // 一時ファイルを削除
        unlink($tmpFilePath);

        return $response;
    }

    /**
    * 新規管理データ一覧表示
    * 
    * @Route("/newlist", name="sdnew_list")
    * @Method({"GET", "POST"})
    *
    * @param Request $request
    * @return Response
    **/
    public function newlistAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        // 削除メソッドへデータを渡す
        $datas = $this->get('request')->request->all();
        if(isset($datas['registFlag'])){
            return $this->newdeleteAction($datas['registFlag']);
        }
        // 新規管理データを一覧表示する
        // tb_sd_informationのregistration_state=1のデータを取得
        $repository = $em->getRepository(TbSdInformation::class);
        $tbsd = $repository->findBy(
            array('registrationState' => 1)
        );
        $message = '';
        if(!$tbsd){
            $message = '新規管理データがありません';
        }
        return $this->render('AppBundle:SdManage:newlist.html.twig', [
             'tbsd' => $tbsd
            ,'message' => $message
        ]);
    }

    /**
    * 新規管理データ編集
    * 
    * @Route("/newedit/{dscode}",defaults={"dscode"="pre"}, name="sdnew_edit")
    * @Method({"GET", "POST"})
    *
    * @param Request $request
    * @return Response
    **/
    public function neweditAction(Request $request, $dscode)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(TbSdInformation::class);
        $tbsd = $repository->findOneBy(
            array('daihyoSyohinCode' => $dscode
                ,'registrationState' => 1)
        );
        if(!$tbsd){
            throw $this->createNotFoundException('代表商品コード：' .$dscode .'は新規管理データでないか、新規登録されていません。');
        }
        $repository = null;
        $repository = $em->getRepository(TbSdInformationSku::class);
        $tbsdsku = $repository->findBy(
            array('daihyoSyohinCode' => $dscode)
        );
        if(!$tbsdsku){
            throw $this->createNotFoundException('代表商品コード：' .$dscode .'のSKU情報が登録されていません。');
        }

        // SKUの削除対象がある場合はSKU情報を削除する
        $datas = $this->get('request')->request->all();
        if(isset($datas['registFlag'])){
            /** @var BatchLogger $logger */
            $logger = $this->get('misc.util.batch_logger');
            $logger->info('superdelivery csv sdnew_edit_sku_delete: start.');
            $qb=$em->createQueryBuilder();
            try{
                foreach($datas['registFlag'] as $target){
                    $qb
                        ->delete('MiscBundle:TbSdInformationSku', 'tbsdsku')
                        ->where('tbsdsku.daihyoSyohinCode = :key1')
                        ->andWhere('tbsdsku.setBango = :key2')
                        ->setParameters(array(
                            'key1' => $dscode,
                            'key2' => $target
                        ))
                        ;
                    $result = $qb->getQuery()->getResult();
                }
            }catch( \Exception $e) {
                $logger->error($e->getMessage());
                $logger->error($e->getTraceAsString());
            }
            return $this->redirect($this->generateUrl('sdnew_edit', array('dscode' => $dscode)));
        }

        $form = $this->createFormBuilder($tbsd)
                    ->add('genre', 'text', ['label' => 'ジャンル', 'attr' => array('class' => 'form-control')])
                    ->add('target', 'text', ['label' => 'ターゲット', 'attr' => array('class' => 'form-control')])
                    ->add('syohinZokusei', 'text', ['label' => '商品属性', 'attr' => array('class' => 'form-control')])
                    ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
             
            return $this->redirectToRoute('sdnew_list');
        }

       return $this->render('AppBundle:SdManage:newedit.html.twig', [
             'tbsd' => $tbsd
            ,'tbsdsku' => $tbsdsku
            ,'form' => $form->createView()
        ]);
    }
    /**
    * 新規管理データ削除
    * 
    * @Route("/newdelete", name="sdnew_delete")
    * 
    * @return Response
    **/
    public function newdeleteAction(array $targets)
    {
        /** @var BatchLogger $logger */
        $logger = $this->get('misc.util.batch_logger');
        $logger->info('superdelivery csv new_delete: start.');

        $em = $this->getDoctrine()->getManager();
        //****トランザクション開始****//
        $em->getConnection()->beginTransaction();
        $qb=$em->createQueryBuilder();
        try{
            foreach($targets as $target){
                $qb
                    ->delete('MiscBundle:TbSdInformation', 'tbsd')
                    ->where('tbsd.daihyoSyohinCode = :key')
                    ->setparameter('key', $target)
                    ;
                $result = $qb->getQuery()->getResult();
                $qb
                    ->delete('MiscBundle:TbSdInformationSku', 'tbsdsku')
                    ->where('tbsdsku.daihyoSyohinCode = :key')
                    ->setparameter('key', $target)
                    ;
                $result = $qb->getQuery()->getResult();
            }
            $em->getConnection()->commit();
        }catch( \Exception $e) {
            //****ロールバック****//
            $em->getConnection()->rollback();
            $em->close();
            $logger->error($e->getMessage());
            $logger->error($e->getTraceAsString());
        }
        $logger->info('superdelivery csv new_delete: end.');
        return $this->redirect($this->generateUrl('sdnew_list'));
    }

    /**
    * 新規登録SDデータCSV取り込み
    * TbSdInformationとTbSdInformationSkuをSD情報と同期
    * 
    * @Route("/sddat/impcsv/{exe}", defaults={"exe"="pre"}, name="sddat_impcsv")
    *
    * @param Request $request
    * @return Response
    **/
    public function sddatImpcsvAction(Request $request, $exe)
    {
        if($exe == 'exe'){
            //アップロードボタン押下後の取込処理
            /** @var BatchLogger $logger */
            $logger = $this->get('misc.util.batch_logger');
            $logger->info('superdelivery csv sddat_import: start.');

            $logger->info(print_r($_FILES, true));
            $result = [
                'message' => null
            , 'info' => []
            ];

            try{
                //ファイル取得
                $files = $request->files->get('upload');
                if(count($files) > 1){
                    throw new \RuntimeException('アップロードできるファイルは１つです。');
                }
                $logger->info('uploaded : ' . print_r($files[0], true));

                //CSVからarrayへ変換
                $datas = $this->convertCsvToArray($files[0]);
                if(!$datas || count($datas) == 1){
                    throw new \RuntimeException('データがありません。');
                }else{
                    /**
                    * バリデーションとDBアクセスはエンティティマネージャを通して行う
                    *  @var EntityManager $em
                    */
                    $em = $this->getDoctrine()->getManager();
                    array_splice($datas, 0, 1); //1行目は項目名なので削除
                    $crtSdBango = '';
                    //****トランザクション開始****//
                    $em->getConnection()->beginTransaction();
                    try{
                        foreach($datas as $data){
                            // TbSdInformationSkuの貴社品番とセット毎数量より更新対象レコードを決定
                            // $repository = $em->getRepository(TbSdInformationSku::class);
                            $tbsdinfosku = $em->getRepository(TbSdInformationSku::class)->findOneBy(
                                array('kisyaHinban' => $data[25], 'setGotoSuryo' => $data[32])
                            );
                            if (!$tbsdinfosku) {
                                throw $this->createNotFoundException('[TbSdInformationSku]貴社品番:'.$data[25] .'とセット毎数量:' .$data[32] .'に合致するレコードがありません');
                            }
                            $tbsdinfosku->setSdBango($data[0]);
                            $tbsdinfosku->setSetBango($data[1]);
                            $tbsdinfosku->setSetHyojiJun($data[27]);
                            $tbsdinfosku->setSyuppinJokyo($data[34]);
                            $tbsdinfosku->setSyuppinDate($data[36]);
                            $tbsdinfosku->setLastUpdate($data[37]);
                            $em->flush();

                            // SD番号が変わった時だけ実行
                            if($crtSdBango <> $data[0]){
                                $crtSdBango = $data[0];
                                $tbsdinfo =  $em->getRepository(TbSdInformation::class)->findOneBy(
                                    array('daihyoSyohinCode' => $tbsdinfosku->getDaihyoSyohinCode())
                                );
                                if (!$tbsdinfo) {
                                    throw $this->createNotFoundException('[TbSdInformation]代表商品コード:'.$tbsdinfosku->getDaihyoSyohinCode() .'に合致するレコードがありません');
                                }
                                $tbsdinfo->setSdBango($data[0]);
                                $tbsdinfo->setRegistrationState(2);
                                $tbsdinfo->setSyuppinDate($data[36]);
                                $tbsdinfo->setLastUpdate($data[37]);
                                $em->flush();
                            }
                        }
                        //****コミット****//
                        $em->getConnection()->commit();
                        $result['info'] = 'アップロードが完了しました。';
                    } catch( \Exception $e) {
                        //****ロールバック****//
                        $em->getConnection()->rollback();
                        $em->close();
                        $logger->error($e->getMessage());
                        $logger->error($e->getTraceAsString());

                        $result['error'] = $e->getMessage();
                    }
                }
            } catch(\Exception $e){
                $logger->error($e->getMessage());
                $logger->error($e->getTraceAsString());

                $result['error'] = $e->getMessage();
            }
            return new JsonResponse($result);
        }
        return $this->render('AppBundle:SdManage:sddat.html.twig', [
        ]);
    }

    /**
    * SDアップロード用新規登録商品データCSV出力
    * 
    * @Route("/sddat/sjchg/new", name="sjchg_new")
    *
    * @param Request $request
    * @return Response
    **/
    public function sjchgNewAction(Request $request)
    {
        // 対象レコードの出品状況を更新する
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb
            ->select(
                'tbsdsku'
                )
            ->from('MiscBundle:TbSdInformation', 'tbsd')
            ->leftjoin(
                'MiscBundle:TbSdInformationSku', 'tbsdsku',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'tbsd.sdBango = tbsdsku.sdBango'
            )
            ->where('tbsd.registrationState = 2 AND tbsdsku.syuppinJokyo = 1')
            ->orderBy('tbsd.sdBango', 'ASC')
            ->addOrderBy('tbsdsku.setBango', 'ASC')
            ;
        $tbsdinfosku = $qb->getQuery()->getResult();
        //****トランザクション開始****//
        $em->getConnection()->beginTransaction();
        try{
            foreach($tbsdinfosku as $dat){
                $dat->setSyuppinJokyo(2);
                $em->flush();
            }
            //****コミット****//
            $em->getConnection()->commit();
        } catch( \Exception $e) {
            //****ロールバック****//
            $em->getConnection()->rollback();
            $em->close();
            $logger->error($e->getMessage());
            $logger->error($e->getTraceAsString());
        }

        // ini_set
        // 実行時間が長く、使うメモリが多ければ、設定したほうがいい
        ini_set('memory_limit', '2048M');
    	ini_set('max_execution_time', 0);

        // 出力する内容
        // CSVの項目名をスーパーデリバリーのCSVと同じにしないとアップロードできないので注意
        $contents = "";
        $fields = [
             'sdBango' => 'SD品番※'
            ,'setBango' => 'セット番号※'
            ,'syohinTitle' => '商品名※(100)'
            ,'brandName' => 'ブランド名(200)'
            ,'genre' => 'ジャンル※'
            ,'target' => 'ターゲット'
            ,'syohinZokusei' => '商品属性'
            ,'size' => 'サイズ・容量(2000)'
            ,'sozai' => '素材・成分(1000)'
            ,'seisanchi' => '生産地(60)'
            ,'naiyoRyo' => '内容量(2000)'
            ,'genzaiRyo' => '原材料(1000)'
            ,'hozonHoho' => '保存方法※(400)'
            ,'kikakuHozoku' => '規格補足(800)'
            ,'package' => 'パッケージ(200)'
            ,'seizoNen' => '製造年(4)'
            ,'syohinFuda' => '商品札※'
            ,'tokuteiHoken' => '特定保健用食品'
            ,'chuiJiko' => '注意事項(2000)'
            ,'comment' => 'コメント(3000)'
            ,'kyokaBango' => '許可番号(2000)'
            ,'syukkaNoki' => '出荷（納期）※'
            ,'syukkaYotei' => '出荷予定'
            ,'zaikoKagiri' => '在庫限り'
            ,'bettoSoryo' => '別途送料'
            ,'kisyaHinban' => '貴社品番(50)'
            ,'janCode' => 'JAN(100)'
            ,'setHyojiJun' => 'セット表示順'
            ,'uchiwake' => '内訳※(500)'
            ,'sankoKakakuSyubetsu' => '参考価格種別※'
            ,'sankoKakaku' => '参考価格※(12)'
            ,'hanbaiTanka' => '販売単価※(12)'
            ,'setGotoSuryo' => 'セット毎数量※(7)'
            ,'zaikoSu' => '在庫数※(8)'
            ,'syuppinJokyo' => '出品状況※'
            ,'zaikoRendo' => '在庫連動'
            ];
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb
            ->select(
                'tbsd.sdBango
                ,tbsdsku.setBango
                ,tbsd.syohinTitle
                ,tbsd.brandName
                ,tbsd.genre
                ,tbsd.target
                ,tbsd.syohinZokusei
                ,tbsd.size
                ,tbsd.sozai
                ,tbsd.seisanchi
                ,tbsd.naiyoRyo
                ,tbsd.genzaiRyo
                ,tbsd.hozonHoho
                ,tbsd.kikakuHosoku
                ,tbsd.package
                ,tbsd.seizoNen
                ,tbsd.syohinFuda
                ,tbsd.tokuteiHoken
                ,tbsd.chuiJiko
                ,tbsd.comment
                ,tbsd.kyokaBango
                ,tbsd.syukkaNoki
                ,tbsd.syukkaYotei
                ,tbsd.zaikoKagiri
                ,tbsd.bettoSoryo
                ,tbsdsku.kisyaHinban
                ,tbsdsku.janCode
                ,tbsdsku.setHyojiJun
                ,tbsdsku.uchiwake
                ,tbsdsku.sankoKakakuSyubetsu
                ,tbsdsku.sankoKakaku
                ,tbsdsku.hanbaiTanka
                ,tbsdsku.setGotoSuryo
                ,tbsdsku.zaikoSu
                ,tbsdsku.syuppinJokyo
                ,tbsdsku.zaikoRendo'
                )
            ->from('MiscBundle:TbSdInformation', 'tbsd')
            ->leftjoin(
                'MiscBundle:TbSdInformationSku', 'tbsdsku',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'tbsd.sdBango = tbsdsku.sdBango'
            )
            ->where('tbsd.registrationState = 2 AND tbsdsku.syuppinJokyo = 2')
            ->orderBy('tbsd.sdBango', 'ASC')
            ->addOrderBy('tbsdsku.setBango', 'ASC')
            ;
        $dat = $qb->getQuery()->getResult();

        /** @var StringUtil $stringUtil */
        $stringUtil = $this->get('misc.util.string'); 
        $contents .= $stringUtil->convertArrayToCsvLine($fields, array_keys($fields)).("\r\n");
        foreach($dat as $row){
            $contents .= $stringUtil->convertArrayToCsvLine($row, array_keys($fields)).("\r\n");
        }

        // uft8の文字コードをsjisに変換
    	$contents = mb_convert_encoding($contents, 'SJIS-win', 'UTF-8');
 
        // response作成
    	$response =  new Response($contents);
        // 出力csvファイルの名前を指定
    	$response->headers->set('Content-Type', "application/octet-stream; name=sddat_sjchg_".date('YmdHis').".csv");
    	$response->headers->set('Content-Disposition', "attachment; filename=sddat_sjchg_".date('YmdHis').".csv");

        return $response;

    }

    /**
    * SDアップロード用商品データ閲覧
    * 
    * @Route("/sdinfo/show", name="sdinfo_show")
    *
    * @param Request $request
    * @return Response
    **/
    public function sdinfoShowAction(Request $request)
    {
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb
            ->select(
                'tbsd,tbsdsku'
                )
            ->from('MiscBundle:TbSdInformation', 'tbsd')
            ->leftjoin(
                'MiscBundle:TbSdInformationSku', 'tbsdsku',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'tbsd.sdBango = tbsdsku.sdBango'
            )
            ->where('tbsd.syuppinDate is null')
            ->orderBy('tbsd.sdBango', 'ASC')
            ->addOrderBy('tbsdsku.setBango', 'ASC')
            ;
        $tbsdinfo = $qb->getQuery()->getResult();
        return $this->render('AppBundle:SdManage:sdshow.html.twig', [
              'maininfos' => $tbsdinfo
            //  ,'skuinfos' => $tbsdinfosku
        ]);
        
    }

    /**
    * ジャンル・ターゲット・商品属性表示
    * 
    * @Route("/codeview/{category1}/{category2}",defaults={"category1"="pre", "category2"="pre"},  name="code_view")
    *
    * @param Request $request
    * @return Response
    **/
    public function codeviewAction(Request $request, $category1)
    {
        /** @var TbSdGenreRepository */
        $repository = $this->getDoctrine()->getRepository('MiscBundle:TbSdGenre');
        if($category1 <> 'pre'){
            if($category2 <> 'pre'){

            }
            $tbgenre = $repository->findBy(
                array('category1' => $category1),
                array(
                    'category1' => 'ASC'
                , 'category2' => 'ASC'
                , 'genreName' => 'ASC'
                )
            );
        }else{
            $tbgenre = $repository->findBy(
                array(),
                array(
                    'category1' => 'ASC'
                , 'category2' => 'ASC'
                , 'genreName' => 'ASC'
                )
            );
        }

        return $this->render('AppBundle:SdManage:code.html.twig', [
             'genres' => $tbgenre
        ]);

    }

    /**
    * 文字コードをUTF-8へ変換し、CSVデータを配列へ格納する
    * @param UploadedFile $file
    * @return array
    */
    public function convertCsvToArray($file)
    {
        //ファイル・ディレクトリチェック
        /** @var FileUtil $fileUtil */
        $fileUtil = $this->get('misc.util.file');
        $fs = new Filesystem();
        $uploadDir = sprintf('%s/SuperDelivery/%s', $fileUtil->getWebCsvDir(), (new \DateTime())->format('YmdHis'));
        if (!$fs->exists($uploadDir)) {
            $fs->mkdir($uploadDir, 0755);
        }

        // 2行目（最初のデータ）で文字コード判定
        $fp = fopen($file->getPathname(), 'rb');
        fgets($fp); // 先頭行を捨てる
        $secondLine = fgets($fp);
        fclose($fp);
        if (!$secondLine) { // 2行目がなければnullを返す
            return null;
        }
        $charset = mb_detect_encoding($secondLine, ['SJIS-WIN', 'UTF-8', 'EUCJP-WIN']);
        if (!$charset) {
            throw new \RuntimeException(sprintf('CSVファイルの文字コードが判定できませんでした。[%s]', $file->getClientOriginalName()));
        }


        //文字コードをUTF-8に変換して一時ファイル作成
        $newFilePath = tempnam($uploadDir, 'utf_');
        chmod($newFilePath, 0666);
        $fp = fopen($newFilePath, 'wb');
        $fileUtil->createConvertedCharsetTempFile($fp, $file->getPathname(), $charset, 'UTF-8');
        fclose($fp);

        //SplFileObjectでデータ読み込み
        $newFile = new \SplFileObject($newFilePath);
        $newFile->setFlags(
            \SplFileObject::DROP_NEW_LINE | // 行末の改行無視
            \SplFileObject::READ_AHEAD | // 先読み
            \SplFileObject::SKIP_EMPTY | // 空行無視
            \SplFileObject::READ_CSV // CSVとして読み込み
        );
        $csv  = array();
        foreach($newFile as $line) {
            $csv[] = $line;
        }
        //SplFileObject解放
        $newFile = null;

        return $csv;

    }

    /**
    * CSV出力テスト
    * 
    * @Route("/dlnewcsv", name="dl_new_csv")
    * @Method({"POST", "DLNEWCSV"})
    *
    * @param Request $request
    * @return Response
    */
    public function csvDownloadAction(Request $request)
    {
        // ini_set
        // 実行時間が長く、使うメモリが多ければ、設定したほうがいい
        ini_set('memory_limit', '2048M');
    	ini_set('max_execution_time', 0);

        // 出力する内容
        $contents = "";
        $fields = [
             'sd_bango' => 'SD番号'
            ,'syohin_title' => '商品名'
            ];
        $stmt = $this->getDoctrine()->getEntityManager()->getConnection()->prepare('
            select * from tb_sd_information
        ');
        $stmt->execute();

        /** @var StringUtil $stringUtil */
        $stringUtil = $this->get('misc.util.string'); 
        $contents .= $stringUtil->convertArrayToCsvLine($fields, array_keys($fields)).("\r\n");
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $contents .= $stringUtil->convertArrayToCsvLine($row, array_keys($fields)).("\r\n");
        }

        // uft8の文字コードをsjisに変換
    	$contents = mb_convert_encoding($contents, 'SJIS-win', 'UTF-8');
 
        // response作成
    	$response =  new Response($contents);
        // 出力csvファイルの名前を指定
    	$response->headers->set('Content-Type', "application/octet-stream; name=sd_new_csv".date('YmdHis').".csv");
    	$response->headers->set('Content-Disposition', "attachment; filename=sd_new_csv".date('YmdHis').".csv");

    	return $response;

    }

}