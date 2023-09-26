<?php
namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// TODO: 各Commandの独自ファイル以外に、以下の2箇所を実装する。
// 　　　　(1) TbProcess テーブルに、追加クラスのレコードを追加
// 　　　　(2) PlusnaoBaseCommandの $processIdList に、追加クラスの定義を記載
/**
 * TODO： クラス宣言のドキュメンテーションコメントを記載
 */
class CommandTemplateCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    // TODO: Commandの定義を記載
    $this
      ->setName('batch:command-template')
      ->setDescription('*コマンドテンプレート*処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN) // このまま記載すること
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    // TODO: tb_logに出力されるログ出力に利用する、処理名を記載する
    $this->commandName = 'コマンドテンプレート処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    // TODO: 独自処理をここに記載する

    // アカウント登録や開始・終了ログなどは、親クラスで処理するためここでは不要

    // 最低限必要な内容については、UpdateProductSizeCommand クラスがシンプルで分かりやすい。
  }
}


