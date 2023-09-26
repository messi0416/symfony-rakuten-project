<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace Ne\ApiBundle\Command;


use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ApiFindCommand extends ContainerAwareCommand
{
    const CLIENT_ID = 'rbvx1EMpfyFQLU';
    const CLIENT_SECRET = '5t68NHBmEhRkpfQjiulg9VbrysPXnzKeIG4WTZC7';

    const CLIENT_ID_PROD = 'FQsRK1kNzXUrfW';
    const CLIENT_SECRET_PROD = 'lsb7rvMPwfpmZDC5YdTO2j8xR3SKtF1Bc6NuiJUH';

    const REDIRECT_URL = 'https://forest.plusnao.co.jp/callback.php';

    protected function configure()
    {
        $this
            ->setName('api:find')
            ->setDescription('check api')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $logger LoggerInterface */
        $logger = $this->getContainer()->get('logger');

        // この値を「アプリを作る->API->テスト環境設定」の値に更新して下さい。
        // (アプリを販売する場合は本番環境設定の値に更新して下さい)
        // このサンプルでは、利用者情報とマスタ情報にアクセスするため、許可して下さい。

        // 本SDKは、ネクストエンジンログインが必要になるとネクストエンジンのログイン画面に
        // リダイレクトします。ログイン成功後に、リダイレクトしたい
        // アプリケーションサーバーのURIを指定して下さい。
        // 呼び出すAPI毎にリダイレクト先を変更したい場合は、apiExecuteの引数に指定して下さい。
        // $pathinfo = pathinfo(strtok($_SERVER['REQUEST_URI'],'?')) ;
        // $redirect_uri = 'https://'.$_SERVER['HTTP_HOST'].$pathinfo['dirname'].'/'.$pathinfo['basename'] ;

        $client = new \ForestNeApiClient(self::CLIENT_ID, self::CLIENT_SECRET, self::REDIRECT_URL) ;
        $client->setLogger($logger);

        $loginAccount = $this->getContainer()->getParameter('ne_site_account');
        $loginId = $loginAccount['api']['account'];
        $loginPassword = $loginAccount['api']['password'];

        $client->setUserAccount($loginId, $loginPassword);

        $client->log('create instance.');

        ////////////////////////////////////////////////////////////////////////////////
        // 契約企業一覧を取得するサンプル
        ////////////////////////////////////////////////////////////////////////////////
        $under_contract_company = $client->apiExecuteNoRequiredLogin('/api_app/company') ;

        var_dump($under_contract_company);

        ////////////////////////////////////////////////////////////////////////////////
        // 利用者情報を取得するサンプル
        ////////////////////////////////////////////////////////////////////////////////
        $user = $client->apiExecute('/api_v1_login_user/info') ;

        var_dump($user);

        ////////////////////////////////////////////////////////////////////////////////
        // 商品マスタ情報を取得するサンプル
        ////////////////////////////////////////////////////////////////////////////////
        $query = array() ;
        // 検索結果のフィールド：商品コード、商品名、商品区分名、在庫数、引当数、フリー在庫数
        $query['fields'] = 'goods_id, goods_name, goods_type_name, stock_quantity, stock_allocation_quantity, stock_free_quantity' ;
        // $query['goods_id-like'] = '%red' ;
        // $query['goods_creation_date-lt'] = '2013-10-31 20:00:00' ;
        // 検索は0～50件まで
        $query['offset'] = '0' ;
        $query['limit'] = '50' ;

        // アクセス制限中はアクセス制限が終了するまで待つ。
        // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
        $query['wait_flag'] = '1' ;

        // 検索対象の総件数を取得
        $goods_cnt = $client->apiExecute('/api_v1_master_goods/count', $query) ;

        var_dump($goods_cnt);

        // 検索実行
        $goods = $client->apiExecute('/api_v1_master_goods/search', $query) ;
        var_dump($goods);

        // ---------------------------
        // 受注一括登録パターン 一覧取得
        // 検索対象の総件数を取得
        // $client = new \ForestNeApiClient(self::CLIENT_ID_PROD, self::CLIENT_SECRET_PROD, self::REDIRECT_URL) ;
        $client = new \ForestNeApiClient(self::CLIENT_ID, self::CLIENT_SECRET, self::REDIRECT_URL) ;
        $client->setLogger($logger);

        $client->setUserAccount($loginId, $loginPassword);
        $client->log('create instance.');

        $query = [];
        $patterns = $client->apiExecute('/api_v1_receiveorder_uploadpattern/info', $query) ;
        var_dump($patterns);

        $output->writeln('done!!');
    }

}
