<?php
namespace Eggbe\ServerBridge;

use \Eggbe\Helper\Arr;

class Bridge {

	/**
	 * @var array
	 */
	private $Callbacks = [];

	/**
	 * @param string $Keys
	 * @param \Closure $Callback
	 * @throws \Exception
	 * @return Bridge
	 */
	public function on($Keys, \Closure $Callback) {
		$this->Callbacks[implode(',', array_change_key_case((array)$Keys,
			CASE_LOWER))][] = $Callback;

		return $this;
	}

	/**
	 * @param array $Request
	 * @return string
	 * @throws \Exception
	 */
	public function dispatch(array $Request) {
		$Request = array_change_key_case($Request, CASE_LOWER);
		try {

			foreach ($this->Callbacks as $Rules => $Calbacks) {

				/**
				 * All positive conditions are parsed here.
				 *
				 * This type of conditions don't need any special character in the beginning
				 * and always checked in the first place.
				 */
				$Keys = array_filter($Rules = explode(',', $Rules), function ($key) {
					return !preg_match('/^[:!]/', $key);
				});
				if (count(array_diff($Keys, array_keys($Request))) < 1) {

					/**
					 * All negative conditions are parsed here.
					 *
					 * This type of conditions have to start from a special negation character
					 * and always checked in the second place.
					 */
					$Keys = array_map(function ($key) { return preg_replace('/^!/', null, $key); },
						array_filter($Rules, function ($key) { return preg_match('/^!/', $key); }));
					if (empty($Keys) || count(array_diff($Keys, array_keys($Request))) == count($Keys)) {

						/**
						 * All grabbable keys are parsed here.
						 *
						 * This list are always includes all positive conditions and conditions
						 * started by special colon character.
						 */
						$Request = Arr::like($Request, array_map(function ($key) { return preg_replace('/^:/', null, $key); },
							array_filter($Rules, function ($key) { return !preg_match('/^!/', $key); })));

						foreach ($Calbacks as $Callback) {
							call_user_func_array($Callback, $Request);

//							try {
//
//							} catch (\Exception $Exception) {
//
//							}
//							_dumpe($Callback);

//							if (!is_null(($Response = call_user_func_array($Callback, Arr::like($Request, $Keys))))) {
//								return json_encode(['error' => false,
//									'data' => $Response]);
//							}
						}

					}
				}

			}
		} catch (\Exception $Exception) {
			return json_encode(['error' => true,
				'message' => $Exception->getMessage()]);
		}

		return json_encode(['error' => false,
			'data' => null]);
	}

}
