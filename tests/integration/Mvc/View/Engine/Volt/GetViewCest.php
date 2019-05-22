<?php
declare(strict_types=1);

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalconphp.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Phalcon\Test\Integration\Mvc\View\Engine\Volt;

use IntegrationTester;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;

class GetViewCest
{
    /**
     * Tests Phalcon\Mvc\View\Engine\Volt :: getView()
     *
     * @author Sid Roberts <https://github.com/SidRoberts>
     * @since  2019-05-22
     */
    public function mvcViewEngineVoltGetView(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\View\Engine\Volt - getView()');

        $view = new View();

        $engine = new Volt($view);

        $I->assertSame(
            $view,
            $engine->getView()
        );
    }
}
