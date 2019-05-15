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

namespace Phalcon\Test\Unit\Firewall\Adapter\Acl;

use Codeception\Example;
use Phalcon\Acl as PhAcl;
use Phalcon\Acl\Adapter\Memory;
use Phalcon\Events\Manager;
use Phalcon\Firewall\Adapter\Acl;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Model\Binder;
use Phalcon\Test\Fixtures\Firewall\BindingRole;
use Phalcon\Test\Fixtures\Traits\DiTrait;
use Phalcon\Test\Fixtures\Traits\FirewallTrait;
use Phalcon\Test\Models\AlbumORama\Albums;
use UnitTester;
use function ob_end_clean;
use function ob_start;

class AfterBindingCest
{
    use DiTrait;
    use FirewallTrait;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Acl
     */
    protected $firewall;

    public function _before()
    {
        ob_start();

        $this->setNewFactoryDefault();
        $this->setDiMysql();

        $dispatcher = new Dispatcher();
        $dispatcher->setDefaultNamespace(
            'Phalcon\Test\Controllers\Firewall'
        );
        $dispatcher->setDI($this->container);
        $eventsManager = new Manager();
        $eventsManager->attach(
            'firewall:beforeException',
            function () {
                return false;
            }
        );
        $firewall = new Acl('acl');
        $firewall
            ->setEventsManager($eventsManager)
            ->setRoleCallback(
                function ($di) {
                    return $di->get('myrole');
                }
            )
            ->setAlwaysResolvingRole(true)
        ;
        $dispatcher->setEventsManager($eventsManager);
        $this->dispatcher = $dispatcher;
        $this->firewall   = $firewall;
    }

    public function _after()
    {
        ob_end_clean();
    }

    /**
     * Tests Phalcon\Firewall\Adapter\Acl :: afterBinding()
     *
     * @dataProvider getAfterBinding
     *
     * @author       Wojciech Ślawski <jurigag@gmail.com>
     * @since        2017-01-19
     */
    public function firewallAdapterAclAfterBinding(UnitTester $I, Example $example)
    {
        $I->wantToTest('Firewall\Adapter\Acl - afterBinding()');

        $acl = new Memory();
        $acl->setDefaultAction(PhAcl::DENY);
        $acl->addComponent('Four', ['first', 'second']);
        $acl->addRole('ROLE1');
        $acl->addRole('ROLE2');
        $acl->allow('ROLE1', 'Four', 'first');
        $acl->allow('ROLE1', 'Four', 'second');
        $acl->allow(
            'ROLE2',
            'Four',
            'first',
            function (BindingRole $user, Albums $album) {
                return $user->getId() == $album->artists_id;
            }
        );
        $acl->allow('ROLE2', 'Four', 'second');

        $this->container->set('acl', $acl);
        $binder = new Binder();
        $this->dispatcher->setModelBinder($binder);
        $eventsManager = new Manager();
        $eventsManager->attach('dispatch:afterBinding', $this->firewall);
        $this->dispatcher->setEventsManager($eventsManager);
        $dispatcher = $this->dispatcher;
        $this->container->set('dispatcher', $dispatcher);

        $returnedValue = $this->getReturnedValueFor(
            $this->container,
            $dispatcher,
            $example[0],
            $example[1],
            $example[2],
            $example[3]
        );
        $I->assertEquals($returnedValue, $example[4]);
    }

    private function getAfterBinding(): array
    {
        return [
            ['four', 'first', new BindingRole('ROLE1', 1), ['album' => 1], 'allowed'],
            ['four', 'first', new BindingRole('ROLE1', 2), ['album' => 1], 'allowed'],
            ['four', 'first', new BindingRole('ROLE2', 1), ['album' => 1], 'allowed'],
            ['four', 'first', new BindingRole('ROLE2', 2), ['album' => 1], null],
            ['four', 'second', new BindingRole('ROLE1', 1), ['album' => 1], 'allowed'],
            ['four', 'second', new BindingRole('ROLE1', 2), ['album' => 1], 'allowed'],
            ['four', 'second', new BindingRole('ROLE2', 1), ['album' => 1], 'allowed'],
            ['four', 'second', new BindingRole('ROLE2', 2), ['album' => 1], 'allowed'],
        ];
    }
}
