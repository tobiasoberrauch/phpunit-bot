<?php

namespace Tob\PhpUnitBot\CommandTest;

use PHPUnit\Framework\TestCase;
use Tob\PhpUnitBot\Command\CreateFromSourceCommand;
use Tob\PhpUnitBot\Config\BotConfig;

/**
 * Class CreateFromSourceCommandTest
 *
 * PHP Version 7
 *
 * @category  PHP
 * @package   Tob\PhpUnitBot\CommandTest
 * @author    Simplicity Trade GmbH <development@simplicity.ag>
 * @copyright 2014-2017 Simplicity Trade GmbH
 * @license   Proprietary http://www.simplicity.ag
 */
class CreateFromSourceCommandTest extends TestCase
{

    /**
     * @var CreateFromSourceCommand
     */
    protected $createFromSourceCommand = null;

    /**
     * @var BotConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config = null;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->config = $this->createMock(BotConfig::class);

        $this->createFromSourceCommand = new CreateFromSourceCommand(
            $this->config
        );
    }

    /**
     * @return void
     */
    public function test()
    {
    }


}

