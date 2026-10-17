<?php

namespace Zhylon\ZhylonAuth\Tests\Fixtures;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Zhylon\ZhylonAuth\Controllers\ZhylonAuthController;

class CustomController extends ZhylonAuthController
{
    public function redirect(Request $request): RedirectResponse
    {
        return redirect('/custom-redirect');
    }
}
