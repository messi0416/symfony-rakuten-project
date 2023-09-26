<?php
namespace AppBundle\Controller;

use MiscBundle\Entity\Repository\SymfonyUsersRepository;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\StringUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;


class UnusedBoxNumberController extends BaseController
{

    /**
     * 商品ロケーション履歴
     */
    public function ListAction(Request $request)
    {
        // ログインアカウント一覧取得（プルダウン表示）
        /** @var SymfonyUsersRepository $repoUser */
        $repoUser = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
        $users = $repoUser->getActiveAccounts();


        $conditions = [];
        $conditions['number'] = '';
        $conditions['start'] = '';
        $conditions['end'] = '';


        if ($request->getMethod() === Request::METHOD_POST) {
            $conditions['number'] = $request->get('number');
            $conditions['start'] = $request->get('start');
            $conditions['end'] = $request->get('end');
            $start = (int)$conditions['start'];
            $end = (int)$conditions['end'];

            if (strlen($conditions['number']) == 0 || strlen($conditions['start']) == 0 || strlen($conditions['end']) == 0 || $start >= $end){
                $this->setFlash('danger', '箱番の形式が正しくありません。再入力してください。');
                return $this->render('AppBundle:UnusedBoxNumber:list.html.twig', [
                    'account' => $this->getLoginUser()
                    , 'users' => $users
                    , 'conditions' => $conditions
                ]);
            }

            $length_end = strlen($conditions['end']);

            $data = [];

            for ($i = $start; $i <= $end; $i++)
                if (strlen(sprintf('%d',$i )) <= $length_end) {
                    $box_code =  $conditions['number'] . str_repeat("0", $length_end - strlen(sprintf('%d',$i )) ) . sprintf('%d',$i );
                    $data[] = $box_code;
                }

            $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
            $locations = $repo->findAllLocations();
            $lists = [];
            $list_boxcode = [];
            foreach ( $locations as $location) {
                $list = explode('-', $location['location_code']);
                $count_list = count($list);
                $box_code = $list[$count_list-1];

                if ($count_list > 2 && strlen($box_code) > 0) {
                    if (!isset($list_boxcode[$box_code])){
                        $list_boxcode[$box_code] = true;
                        $lists[] = $box_code;
                    }
                }
            }
            $result=array_diff($data,$lists);
            $data = [];
            $lists = [];
            foreach ( $result as $boxcode) {
                $lists[] = ['boxcode' => $boxcode ];
            }

            /** @var StringUtil $stringUtil */
            $stringUtil = $this->get('misc.util.string');

            // ヘッダ
            $headers = [
                'boxcode'             => '未使用箱番号'
            ];

            $response = new StreamedResponse();
            $response->setCallback(
                function () use ($lists, $stringUtil, $headers) {
                    $file = new \SplFileObject('php://output', 'w');
                    $eol = "\r\n";

                    // ヘッダ
                    $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
                    $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
                    $file->fwrite($header);

                    foreach($lists as $log) {

                        $line = $stringUtil->convertArrayToCsvLine($log, array_keys($headers), [], ",") . $eol;
                        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

                        $file->fwrite($line);

                        flush();
                    }
                }
            );

            $fileName = sprintf('unused_box_number_%s.csv', (new \DateTime())->format('YmdHis'));

            $response->headers->set('Content-type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
            $response->send();
            return $response;
        }

        // 画面表示
        return $this->render('AppBundle:UnusedBoxNumber:list.html.twig', [
            'account' => $this->getLoginUser()
            , 'users' => $users
            , 'conditions' => $conditions
        ]);

    }

    public function getDulicateBoxcodeAction(Request $request)
    {
        /** @var BatchLogger $logger */
        $logger = $this->get('misc.util.batch_logger');

        $result = [
            'status' => 'ok'
            , 'message' => null
            , 'list' => []
        ];

        try {
            $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
            $locations = $repo->findAllLocations();
            $data = [];
            $list_boxcode = [];
            foreach ( $locations as $location) {
                $list = explode('-', $location['location_code']);
                $count_list = count($list);
                $box_code = $list[$count_list-1];

                if ($count_list > 2 && strlen($box_code) > 0) {
                    $list_boxcode[$box_code][] = $location;
                }
            }
            foreach ( $list_boxcode as $boxcode) {
                if (count($boxcode) > 1) {
                    $data[] = $boxcode;
                }
            }
            $result['list'] = $data;
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            $result['status'] = 'ng';
            $result['message'] = $e->getMessage();
        }

        return new JsonResponse($result);
    }
}
