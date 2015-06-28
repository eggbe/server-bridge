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

			$this->on('!namespace', function () {
				throw new \Exception('Undefined namespace');
			});

			$this->on('!method', function () {
				throw new \Exception('Undefined method!');
			});

			foreach ($this->Bindings as $keys => $Binding) {

				$Keys = array_filter(explode(',', $keys), function($key){
					return !preg_match('/^[:!]/', $key); });

				if (count(array_diff($Keys, array_keys($Request))) < 1) {

					$Keys = array_map(function($key){ return preg_replace('/^!/', null, $key); },
						array_filter(explode(',', $keys), function($key){ return preg_match('/^!/', $key); }));

					if (count($Keys) < 1 || count(array_diff($Keys, array_keys($Request))) > 0) {

						$Keys = array_map(function ($key) { return preg_replace('/^:/', null, $key); },
							array_filter(explode(',', $keys), function ($key) { return !preg_match('/^!/', $key); }));

						foreach ($Binding as $Callback) {
							if (!is_null(($Response = $Response = call_user_func_array($Callback, Arr::like($Request, $Keys))))) {
								return json_encode(['error' => false,
									'data' => $Response]);
							}
						}

					}
				}

			}
		} catch (\Exception $Exception) {
			return json_encode(['error' => false,
				'message' => $Exception->getMessage()]);
		}

		return json_encode(['error' => false,
			'data' => null]);
	}

}
