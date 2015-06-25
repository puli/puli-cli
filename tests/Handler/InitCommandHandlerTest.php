<?php

namespace Puli\Cli\Tests\Handler;


use PHPUnit_Framework_MockObject_MockObject;
use Puli\Cli\Handler\InitCommandHandler;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\IO\IO;

class InitCommandHandlerTest extends AbstractCommandHandlerTest
{
    /**
     * @var Args|PHPUnit_Framework_MockObject_MockObject
     */
    protected $args;

    /**
     * @var IO|PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var InitCommandHandler
     */
    protected $handler;

    protected function setUp()
    {
        self::setUpBeforeClass();

        $this->args    = $this->getMock('Webmozart\Console\Api\Args\Args', array(), array(), '', false);
        $this->io      = $this->getMock('Webmozart\Console\Api\IO\IO');
        $this->handler = new InitCommandHandler(self::$tempDir);
    }

    /**
     *
     *
     * @return array
     */
    public function getTestCases()
    {
        return array(
            array(false, null),
            array(true, null),
            array(true, 'composer/package')
        );
    }

    /**
     * @dataProvider getTestCases
     */
    public function testAskingForPackageName($composerFileExists, $composerPackageName)
    {
        $composerData = array();
        if (null !== $composerPackageName) {
            $composerData['name'] = $composerPackageName;
        }

        if ($composerFileExists) {
            file_put_contents(self::$tempDir . '/composer.json', json_encode($composerData));
        }

        $atIndex = 0;

        $this->io
            ->expects($this->at($atIndex++))
            ->method('write')
            ->with(sprintf('Package name (<vendor>/<name>)%s: ',
                null === $composerPackageName ? '': (' [' . $composerPackageName . ']')
            ));

        $packageName = null !== $composerPackageName ? $composerPackageName : 'puli/package';

        $this->io
            ->expects($this->at($atIndex++))
            ->method('readLine')
            ->willReturn($packageName);

        $this->io
            ->expects($this->at($atIndex++))
            ->method('writeLine')
            ->with(sprintf('Your package name: %s', $packageName));

        $this->handler->handle($this->args, $this->io);
    }
}