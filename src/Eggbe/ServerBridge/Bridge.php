<?php
namespace Eggbe\ServerBridge;

use \Eggbe\Helpers\Arr;

class Bridge {

	/**
	 * @var array
	 */
	private $Bindings = [];

	/**
	 * @param string $Keys
	 * @param callable $Callback
	 * @throws \Exception
	 * @return Bridge
	 */
	public function on($Keys, \Closure $Callback){
		$this->Bindings[implode(',', array_change_key_case((array)$Keys,
			CASE_LOWER))][] = $Callback;

		return $this;
	}

	/**
	 * @param array $Request
	 * @return string
	 * @throws \Exception
	 */
	public function dispatch(array $Request){
		$Request = array_change_key_case($Request, CASE_LOWER);

		try {
			foreach ($this->Bindings as $Keys => $Binding) {
				$Keys = explode(',', $Keys);
				if (count(array_diff($Keys, array_keys($Request))) < 1) {
					foreach ($Binding as $Callback) {
						if (!is_null(($Response = $Response = call_user_func_array($Callback, Arr::like($Request, $Keys))))) {
							return json_encode(['error' => false,
								'data' => $Response]);
						}
					}
				}
			}
		} catch (\Exception $Exception) {
			return json_encode(['error' => false,
				'message' => $Exception->getMessage()]);
		}

		return false;
	}

}
