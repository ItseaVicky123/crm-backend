<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 * Trait CustomFilters
 * @package App\Traits
 */
trait CustomFilters
{
	/**
	 * @param Request $request
	 *
	 * @throws \Illuminate\Validation\ValidationException
	 */
	protected function validateCustomFilterRequest(Request $request)
	{
		$this->validate($request, [
			'filters' => 'required|array',
			'search'  => 'required|regex:/^[a-zA-Z0-9\s]+$/',
		]);
	}
}
