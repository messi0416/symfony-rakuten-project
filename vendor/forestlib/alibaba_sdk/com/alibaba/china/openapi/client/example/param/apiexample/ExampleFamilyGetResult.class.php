<?php
include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamily.class.php');
class ExampleFamilyGetResult {
	private $result;
	
	/**
	 *
	 * @return
	 *
	 */
	public function getResult() {
		return $this->result;
	}
	
	/**
	 * 设置
	 * 
	 * @param ExampleFamily $result
	 *        	此参数必填
	 */
	public function setResult(ExampleFamily $result) {
		$this->result = $result;
	}
	public $stdResult;
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
		if (array_key_exists ( "result", $this->stdResult )) {
			$resultResult = $this->stdResult->{"result"};
			$this->result = new ExampleFamily ();
			$this->result->setStdResult ( $resultResult );
		}
	}
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
		if (array_key_exists ( "result", $this->arrayResult )) {
			$resultResult = $arrayResult ['result'];
			$this->result = new ExampleFamily ();
			$this->result->$this->setStdResult ( $resultResult );
		}
	}
}
?>
