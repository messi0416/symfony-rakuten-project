<?php

namespace MiscBundle\Entity;

use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbSetting
 */
class TbSetting
{
    use ArrayTrait;
    use FillTimestampTrait;

    /** キー値：【YAHOOおとりよせ】PR設定。新着商品 新着扱い日数（設定変更はWeb） */
    const KEY_YAHOO_OTORIYOSE_PR_NEW_DAYS = 'YAHOO_OTORIYOSE_PR_NEW_DAYS';
    /** キー値：【YAHOOおとりよせ】PR設定。新着商品 PR料率（設定変更はWeb） */
    const KEY_YAHOO_OTORIYOSE_PR_NEW_PER = 'YAHOO_OTORIYOSE_PR_NEW_PER';
    /** キー値：【YAHOOおとりよせ】PR設定。その他商品 PR料率（設定変更はWeb） */
    const KEY_YAHOO_OTORIYOSE_PR_OTHER_PER = 'YAHOO_OTORIYOSE_PR_OTHER_PER';

    /** キー値：【YAHOOおとりよせ】PR設定。販売数量制限 集計日数（設定変更はWeb） */
    const KEY_YAHOO_OTORIYOSE_PR_AMOUNT_DAYS = 'YAHOO_OTORIYOSE_PR_AMOUNT_DAYS';
    /** キー値：【YAHOOおとりよせ】PR設定。販売数量制限 数量上限（これより多い場合はこの料率を適用）（設定変更はWeb） */
    const KEY_YAHOO_OTORIYOSE_PR_AMOUNT_NUM = 'YAHOO_OTORIYOSE_PR_AMOUNT_NUM';
    /** キー値：【YAHOOおとりよせ】PR設定。販売数量制限 PR料率（設定変更はWeb） */
    const KEY_YAHOO_OTORIYOSE_PR_AMOUNT_PER = 'YAHOO_OTORIYOSE_PR_AMOUNT_PER';
    /** キー値：【YAHOOおとりよせ】PR設定。即時出荷商品 PR料率（設定変更はWeb） */
    const KEY_YAHOO_OTORIYOSE_PR_READY_PER = 'YAHOO_OTORIYOSE_PR_READY_PER';

    /** キー値：【YAHOOおとりよせ】PR設定。季節外商品 PR料率（設定変更はWeb） */
    const KEY_YAHOO_OTORIYOSE_PR_OFF_PER = 'YAHOO_OTORIYOSE_PR_OFF_PER';

    /** キー値：【Yahoo共通】plusnao/kawa-e-mon共通 優良配送が有効か */
    const KEY_YAHOO_EXCELLENT_DELIVERY_AVAILABLE = 'YAHOO_EXCELLENT_DELIVERY_AVAILABLE';
    
    /** キー値：【YAHOO Kawa-e-mon】ページ非公開設定・個数条件・個数（設定変更はWeb） */
    const KEY_YAHOO_KAWA_E_MON_QUANTITY = 'YAHOO_KAWA_E_MON_QUANTITY';
    /** キー値：【YAHOO Kawa-e-mon】ページ非公開設定・個数条件・日数（設定変更はWeb） */
    const KEY_YAHOO_KAWA_E_MON_QUANTITY_DAYS = 'YAHOO_KAWA_E_MON_QUANTITY_DAYS';

    /** キー値：【YAHOO Kawa-e-mon】ページ非公開設定・売上条件・売上（設定変更はWeb） */
    const KEY_YAHOO_KAWA_E_MON_SALES = 'YAHOO_KAWA_E_MON_SALES';
    /** キー値：【YAHOO Kawa-e-mon】ページ非公開設定・売上条件・日数（設定変更はWeb） */
    const KEY_YAHOO_KAWA_E_MON_SALES_DAYS = 'YAHOO_KAWA_E_MON_SALES_DAYS';

    /** キー値：【楽天 motto-motto】ページ非公開設定・個数条件・個数（設定変更はWeb） */
    const KEY_MOTTO_QUANTITY = 'MOTTO_QUANTITY';
    /** キー値：【楽天 motto-motto】ページ非公開設定・個数条件・日数（設定変更はWeb） */
    const KEY_MOTTO_QUANTITY_DAYS = 'MOTTO_QUANTITY_DAYS';

    /** キー値：【楽天 motto-motto】ページ非公開設定・売上条件・売上（設定変更はWeb） */
    const KEY_MOTTO_SALES = 'MOTTO_SALES';
    /** キー値：【楽天 motto-motto】ページ非公開設定・売上条件・日数（設定変更はWeb） */
    const KEY_MOTTO_SALES_DAYS = 'MOTTO_SALES_DAYS';
    /** キー値：【楽天 LaForest】ページ非公開設定・レビュー条件・平均点数（設定変更はWeb） */
    const KEY_LAFOREST_REVIEW_POINT = 'LAFOREST_REVIEW_POINT';

    /** キー値：【楽天GOLD CSV出力】集計期間（設定変更はWeb） */
    const KEY_RAKUTEN_GOLD_AGGREGATE_DAYS = 'RAKUTEN_GOLD_AGGREGATE_DAYS';
    /** キー値：【楽天GOLD CSV出力】レビュー下限（設定変更はWeb） */
    const KEY_RAKUTEN_GOLD_MIN_REVIEW_POINT = 'RAKUTEN_GOLD_MIN_REVIEW_POINT';
    /** キー値：【楽天GOLD CSV出力】プチプライス価格上限（設定変更はWeb） */
    const KEY_RAKUTEN_GOLD_MAX_PETIT_PRICE = 'RAKUTEN_GOLD_MAX_PETIT_PRICE';

    /** キー値：【送料設定】デフォルトID：宅配便 */
    const KEY_SHIPPING_DEFAULT_TAKUHAIBIN = 'SHIPPING_DEFAULT_TAKUHAIBIN';
    /** キー値：【送料設定】デフォルトID：定形外郵便 */
    const KEY_SHIPPING_DEFAULT_TEIKEIGAI = 'SHIPPING_DEFAULT_TEIKEIGAI';
    /** キー値：【送料設定】デフォルトID：ゆうパケット */
    const KEY_SHIPPING_DEFAULT_YUU_PACKET = 'SHIPPING_DEFAULT_YUU_PACKET';

    /** キー値：【Yahoo】バリエーション画像登録基準となる在庫数 */
    const KEY_YAHOO_VARI_IMG_STOCK_BASE = 'YAHOO_VARI_IMG_STOCK_BASE';
    /** キー値：【YAHOO Kawa-e-mon】バリエーション画像登録基準となる在庫数 */
    const KEY_YAHOO_KAWA_VARI_IMG_STOCK_BASE = 'YAHOO_KAWA_VARI_IMG_STOCK_BASE';
    /** キー値：【YAHOOおとりよせ】バリエーション画像登録基準となる在庫数 */
    const KEY_YAHOO_OTORITISE_VARI_IMG_STOCK_BASE = 'YAHOO_OTORITISE_VARI_IMG_STOCK_BASE';

    /** キー値：【ピッキングスコア】1カラム目の取得数 （設定変更はWeb） */
    const KEY_PICKING_RECORD_LIMIT_1 = 'PICKING_RECORD_LIMIT_1';
    /** キー値：【ピッキングスコア】2カラム目の取得数 （設定変更はWeb） */
    const KEY_PICKING_RECORD_LIMIT_2 = 'PICKING_RECORD_LIMIT_2';
    /** キー値：【ピッキングスコア】3カラム目の取得数 （設定変更はWeb） */
    const KEY_PICKING_RECORD_LIMIT_3 = 'PICKING_RECORD_LIMIT_3';
    /** キー値：【ピッキングスコア】現在からこの値ヵ月前を取得範囲とする */
    const KEY_PICKING_LIMIT_MONTH = 'PICKING_LIMIT_MONTH';
    /** キー値：【ピッキングスコア】ピッキングリスト集計最短秒数。間隔がこれ以下のものを除外 */
    const KEY_PICKING_RECORD_SECOND_MIN = 'PICKING_RECORD_SECOND_MIN';
    /** キー値：【ピッキングスコア】ピッキングリスト集計最長秒数。間隔がこれ以上のものを除外 */
    const KEY_PICKING_RECORD_SECOND_MAX = 'PICKING_RECORD_SECOND_MAX';
    /** キー値：【ピッキングスコア】「○秒以内の打刻が○回連続した場合は集計から除外」中の秒 */
    const KEY_PICKING_RECORD_CONTINUE_TIME = 'PICKING_RECORD_CONTINUE_TIME';
    /** キー値：【ピッキングスコア】「○秒以内の打刻が○回連続した場合は集計から除外」中の回 */
    const KEY_PICKING_RECORD_CONTINUE_COUNT = 'PICKING_RECORD_CONTINUE_COUNT';

    /** キー値：【箱詰めスコア】1カラム目の取得数 （設定変更はWeb） */
    const KEY_BOXED_REFILL_RECORD_LIMIT_1 = 'BOXED_REFILL_RECORD_LIMIT_1';
    /** キー値：【箱詰めスコア】2カラム目の取得数 （設定変更はWeb） */
    const KEY_BOXED_REFILL_RECORD_LIMIT_2 = 'BOXED_REFILL_RECORD_LIMIT_2';
    /** キー値：【箱詰めスコア】3カラム目の取得数 （設定変更はWeb） */
    const KEY_BOXED_REFILL_RECORD_LIMIT_3 = 'BOXED_REFILL_RECORD_LIMIT_3';
    /** キー値：【箱詰めスコア】現在からこの値ヵ月前を取得範囲とする （設定変更はWeb） */
    const KEY_BOXED_REFILL_LIMIT_MONTH = 'BOXED_REFILL_LIMIT_MONTH';

    /** キー値：【在庫移動倉庫設定】倉庫在庫バッチでの移動先倉庫ID */
    const KEY_STOCK_MOVE_WAREHOUSE_ID = 'STOCK_MOVE_WAREHOUSE_ID';

    /** キー値：【出荷実績】SHOPLIST購入伝票の出荷の係数 */
    const KEY_WAREHOUSE_RESULT_HISTORY_COEFFICIENT_SHOPLIST = 'WAREHOUSE_RESULT_HISTORY_COEFFICIENT_SHOPLIST';
    /** キー値：【出荷実績】ゆうパック(RSL)ないし佐川急便での購入伝票の出荷の係数 */
    const KEY_WAREHOUSE_RESULT_HISTORY_COEFFICIENT_RSL_SAGAWA_YAMATO = 'WAREHOUSE_RESULT_HISTORY_COEFFICIENT_RSL_SAGAWA_YAMATO';

    /** キー値：【NextEngineCSV出力】原価・売価の差分を無視するか */
    const KEY_NE_PRODUCT_IGNORE_PRICE_DIFF = 'NE_PRODUCT_IGNORE_PRICE_DIFF';

    /** キー値：【伝票毎利益再集計】集計方法(全期間 or 一部期間) */
    const KEY_AGGREGATE_SALES_DETAIL_TYPE = 'AGGREGATE_SALES_DETAIL_TYPE';

    /** キー値：【伝票毎利益再集計】直近何か月分集計するか */
    const KEY_AGGREGATE_SALES_DETAIL_MONTHS = 'AGGREGATE_SALES_DETAIL_MONTHS';

    /** キー値：【商品売上担当者適用終了処理】何か月稼働していない商品を対象とするか */
    const KEY_PRODUCT_SALES_ACCOUNT_TERMINATE_MONTHS = 'PRODUCT_SALES_ACCOUNT_TERMINATE_MONTHS';
    
    /** キー値：【SHOPLISTスピード便】SHOPLIST納品可能倉庫 最低保管数量 */
    const KEY_SHOPLIST_SPEEDBIN_KEEP_STOCK = 'SHOPLIST_SPEEDBIN_KEEP_STOCK';

    /** キー値：【倉庫移動伝票一括作成】移動伝票に対して何明細で分割作成するか */
    const KEY_TRANSPORT_LIST_DETAIL_LIMIT = 'TRANSPORT_LIST_DETAIL_LIMIT';

    /** キー値：PPM FTPパスワード */
    const KEY_PPM_FTP_USER = 'PPM_FTP_USER';
    /** キー値：PPM FTPパスワード */
    const KEY_PPM_FTP_PASSWORD = 'PPM_FTP_PASSWORD';

    /** キー値：PPM ログインアカウント */
    const KEY_PPM_SITE_API_ACCOUNT = 'PPM_SITE_API_ACCOUNT';
    /** キー値：PPM ログインパスワード */
    const KEY_PPM_SITE_API_PASSWORD = 'PPM_SITE_API_PASSWORD';

    /** キー値：WOWMA FTPユーザー */
    const KEY_WOWMA_FTP_USER = 'WOWMA_FTP_USER';
    /** キー値：WOWMA FTPパスワード */
    const KEY_WOWMA_FTP_PASSWORD = 'WOWMA_FTP_PASSWORD';

    /** キー値：SHOPLIST パスワード */
    const KEY_SHOPLIST_PASSWORD = 'SHOPLIST_PASSWORD';
    /** キー値：SHOPLIST FTPパスワード */
    const KEY_SHOPLIST_FTP_PASSWORD = 'SHOPLIST_FTP_PASSWORD';

    /**
     * @var string
     */
    private $settingKey;

    /**
     * @var string
     */
    private $settingVal;

    /**
     * @var string
     */
    private $settingDesc;


    /**
     * Get settingKey
     *
     * @return string
     */
    public function getSettingKey()
    {
        return $this->settingKey;
    }

    /**
     * Set settingVal
     *
     * @param string $settingVal
     *
     * @return TbSetting
     */
    public function setSettingVal($settingVal)
    {
        $this->settingVal = $settingVal;

        return $this;
    }

    /**
     * Get settingVal
     *
     * @return string
     */
    public function getSettingVal()
    {
        return $this->settingVal;
    }

    /**
     * Set settingDesc
     *
     * @param string $settingDesc
     *
     * @return TbSetting
     */
    public function setSettingDesc($settingDesc)
    {
        $this->settingDesc = $settingDesc;

        return $this;
    }

    /**
     * Get settingDesc
     *
     * @return string
     */
    public function getSettingDesc()
    {
        return $this->settingDesc;
    }

    /**
     * Set settingKey
     *
     * @param string $settingKey
     *
     * @return TbSetting
     */
    public function setSettingKey($settingKey)
    {
        $this->settingKey = $settingKey;

        return $this;
    }
    /**
     * @var boolean
     */
    private $nonDisplayFlag;

    /**
     * @var integer
     */
    private $updateAccountId;

    /**
     * @var \DateTime
     */
    private $updated;


    /**
     * Set nonDisplayFlag
     *
     * @param boolean $nonDisplayFlag
     * @return TbSetting
     */
    public function setNonDisplayFlag($nonDisplayFlag)
    {
        $this->nonDisplayFlag = $nonDisplayFlag;

        return $this;
    }

    /**
     * Get nonDisplayFlag
     *
     * @return boolean 
     */
    public function getNonDisplayFlag()
    {
        return $this->nonDisplayFlag;
    }

    /**
     * Set updateAccountId
     *
     * @param integer $updateAccountId
     * @return TbSetting
     */
    public function setUpdateAccountId($updateAccountId)
    {
        $this->updateAccountId = $updateAccountId;

        return $this;
    }

    /**
     * Get updateAccountId
     *
     * @return integer 
     */
    public function getUpdateAccountId()
    {
        return $this->updateAccountId;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return TbSetting
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
    
    /**
     * @var boolean
     */
    private $adminOnlyFlg;

    /**
     * Set adminOnlyFlg
     *
     * @param boolean $adminOnlyFlg
     * @return TbSetting
     */
    public function setAdminOnlyFlg($adminOnlyFlg)
    {
        $this->adminOnlyFlg = $adminOnlyFlg;

        return $this;
    }

    /**
     * Get adminOnlyFlg
     *
     * @return boolean 
     */
    public function getAdminOnlyFlg()
    {
        return $this->adminOnlyFlg;
    }
}
