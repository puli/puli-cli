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

    /**
     * @var int
     */
    protected $atIndex;

    protected function setUp()
    {
        self::setUpBeforeClass();

        $this->args    = $this->getMock('Webmozart\Console\Api\Args\Args', array(), array(), '', false);
        $this->io      = $this->getMock('Webmozart\Console\Api\IO\IO');
        $this->handler = new InitCommandHandler(self::$tempDir);
        $this->atIndex = 0;
    }

    /**
     *
     *
     * @return array
     */
    public function getTestCases()
    {
        return array(
            array(false, null, '', '', '', ''),
            array(true,  null, '', '', '', ''),
            array(true,  'composer/package', '', '', '', ''),


            array(false, null, 'a', '', '', ''),
            array(false, null, 'l', '', '', ''),
        );
    }

    private function addComposerNameCalls($fileExists, $packageName)
    {
        $composerData = array();
        if (null !== $packageName) {
            $composerData['name'] = $packageName;
        }

        if ($fileExists) {
            file_put_contents(self::$tempDir . '/composer.json', json_encode($composerData));
        }

        $this->io
            ->expects($this->at($this->atIndex++))
            ->method('write')
            ->with(sprintf('Package name (<vendor>/<name>)%s: ',
                null === $packageName ? '': (' [' . $packageName . ']')
            ));

        $packageName = null !== $packageName ? $packageName : 'puli/package';

        $this->io
            ->expects($this->at($this->atIndex++))
            ->method('readLine')
            ->willReturn($packageName);

        return $packageName;
    }

    private function addApplicationOrLibraryCalls($projectTypeAnswer)
    {
        $this->io
            ->expects($this->at($this->atIndex++))
            ->method('write')
            ->with('Project is an [a]pplication or a [l]ibrary: ');

        $this->io
            ->expects($this->at($this->atIndex++))
            ->method('read')
            ->willReturn($projectTypeAnswer);
    }

    /**
     * @dataProvider getTestCases
     */
    public function testAskingForPackageName($composerFileExists, $composerPackageName, $projectTypeAnswer)
    {
        $packageName = $this->addComposerNameCalls($composerFileExists, $composerPackageName);
        $this->addApplicationOrLibraryCalls($projectTypeAnswer);

        $this->io
            ->expects($this->at($this->atIndex++))
            ->method('writeLine')
            ->with(sprintf('Your package name: %s', $packageName));

        $this->handler->handle($this->args, $this->io);
    }
}