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
            array(false, null, '', '', '', '', ''),
            array(true,  null, '', '', '', '', ''),
            array(true,  'composer/package', '', '', '', '', ''),

            array(false, null, 'a', '', '', '', ''),
            array(false, null, 'l', '', '', '', ''),

            array(false, null, '', 'no', '', '', ''),
            array(false, null, '', 'yes', '', '', ''),
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

    private function addResDirCalls($createResDirAnswer, $resDirNameAnswer, $createConfigDir, $createPublicDirAnswer)
    {
        $this->io
            ->expects($this->at($this->atIndex++))
            ->method('write')
            ->with('Create a resource directory [yes]: ');

        $this->io
            ->expects($this->at($this->atIndex++))
            ->method('readLine')
            ->willReturn($createResDirAnswer);

        $directories = array();
        if (in_array($createResDirAnswer, array('', 'yes'))) {
            $this->io
                ->expects($this->at($this->atIndex++))
                ->method('write')
                ->with('Name of the resource directory [res]: ');

            $this->io
                ->expects($this->at($this->atIndex++))
                ->method('readLine')
                ->willReturn($resDirNameAnswer);

            if (empty($resDirNameAnswer)) {
                $resDirNameAnswer = 'res';
            }

            $configDir = $resDirNameAnswer . '/config';
            $this->io
                ->expects($this->at($this->atIndex++))
                ->method('write')
                ->with(sprintf('Create directory "%s" [yes]: ', $configDir));

            $this->io
                ->expects($this->at($this->atIndex++))
                ->method('readLine')
                ->willReturn($createConfigDir);

            if (in_array($createConfigDir, array('', 'yes'))) {
                $directories[] = $configDir;
            }

            $publicDir = $resDirNameAnswer . '/public';
            $this
                ->io
                ->expects($this->at($this->atIndex++))
                ->method('write')
                ->with(sprintf('Create directory "%s" [yes]: ', $publicDir));

            $this->io
                ->expects($this->at($this->atIndex++))
                ->method('readLine')
                ->willReturn($createPublicDirAnswer);
        }
    }

    /**
     * @dataProvider getTestCases
     */
    public function testAskingForPackageName($composerFileExists, $composerPackageName, $projectTypeAnswer,
        $createResDirAnswer, $resDirNameAnswer, $createConfigDir, $createPublicDirAnswer)
    {
        $packageName = $this->addComposerNameCalls($composerFileExists, $composerPackageName);
        $this->addApplicationOrLibraryCalls($projectTypeAnswer);
        $this->addResDirCalls($createResDirAnswer, $resDirNameAnswer, $createConfigDir, $createPublicDirAnswer);

        /*
        $this->io
            ->expects($this->at($this->atIndex++))
            ->method('writeLine')
            ->with(sprintf('Your package name: %s', $packageName));
        */

        $this->handler->handle($this->args, $this->io);
    }

    public function dataProviderForTestComposerNameWillBeRecommended()
    {
        return array(
            array(false, null),
            array(true, null),
            array(true, 'composer/package')
        );
    }

    /**
     * @dataProvider dataProviderForTestComposerNameWillBeRecommended
     */
    public function testComposerNameWillBeRecommended($composerFileExists, $composerPackageName)
    {
        $packageName = $this->addComposerNameCalls($composerFileExists, $composerPackageName);

        $this->io
            ->expects($this->once())
            ->method('writeLine')
            ->with(sprintf('Your package name: %s', $packageName));

        $this->handler->handle($this->args, $this->io);
    }

    public function dataProviderTestAskForCreationOfDirectories()
    {
        return array(
            array('a')
        );
    }

    /**
     * @dataProvider
     */
    public function testAskForCreationOfDirectories($x)
    {
        $this->addComposerNameCalls(false, null);
        $this->addApplicationOrLibraryCalls('');


        $this->handler->handle($this->args, $this->io);
        /*
        $publicDir = $resDirNameAnswer . '/public';
        $this
            ->io
            ->expects($this->at($this->atIndex++))
            ->method('write')
            ->with(sprintf('Create directory "%s" [yes]: ', $publicDir));

        $this->io
            ->expects($this->at($this->atIndex++))
            ->method('readLine')
            ->willReturn($createPublicDirAnswer);
        */
    }

    /*
    public function testCleanRun()
    {
        $this->addComposerNameCalls(false, null);
        $this->addApplicationOrLibraryCalls('a');
    }
    */
}