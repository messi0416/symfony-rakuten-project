<?php
    namespace Oyrworks\Bundle\AMCBundle\Command\Control;
 	
 	class LogControl
 	{
 	   private $LogFile;
 	   
 	   public function __construct($DataDirectory)
 	   {
 	       $this->LogFile = $DataDirectory['path'] . $DataDirectory['log_file'];
 	   }   
 	   
 	   public function logOutput($condition, $logString)
 	   {
 	       $timeStamp = date('Y-m-d\ H:i:s') . "JST"; // ログに記載する時刻（日本時間）
 	       $outputString =  "[" . $timeStamp . "] : [" . $condition . "] : " . $logString . PHP_EOL;
 	       file_put_contents($this->LogFile, $outputString, FILE_APPEND);
 	       return;
		}
 	}
?>