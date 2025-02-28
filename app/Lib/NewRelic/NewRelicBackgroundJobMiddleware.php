<?php

namespace App\Lib\NewRelic;

use Closure;
use Illuminate\Http\Request;

/**
 * Class NewRelicBackgroundJobMiddleware
 * @package App\Lib\NewRelic
 */
class NewRelicBackgroundJobMiddleware extends NewRelicMiddleware
{
   /**
    * @inheritdoc
    */
   public function handle(Request $request, Closure $next)
   {
      // Mark the request as a background job
      $this->newRelic->backgroundJob();

      return $next($request);
   }
}
