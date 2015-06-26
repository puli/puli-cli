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
        $possibleValues = array(
            array(true, false),
            array(null, 'composer/package'),
        );

        $numberOfEntries = array();
        foreach ($possibleValues as $index => $values) {
            $numberOfEntries[$index] = 1;
            for ($testCaseIndex = $index + 1; $testCaseIndex < count($possibleValues); $testCaseIndex++) {
                $numberOfEntries[$index] *= count($possibleValues[$testCaseIndex]);
            }
        }
        $numberOfTestCases = count($possibleValues[0]) * $numberOfEntries[0];

        $testCases = array();
        for ($testCaseIndex = 0; $testCaseIndex < $numberOfTestCases; $testCaseIndex++) {
            $testCases[] = array();
        }

        foreach ($possibleValues as $index => $values) {
            $entries      = $numberOfEntries[$index];
            $remaining    = $entries;
            $elementIndex = 0;
            for ($testCaseIndex = 0; $testCaseIndex < $numberOfTestCases; $testCaseIndex++) {
                $testCases[$testCaseIndex][$index] = $values[$elementIndex];

                if (0 === --$remaining) {
                    $remaining = $entries;
                    $elementIndex++;
                    if ($elementIndex === count($values)) {
                        $elementIndex = 0;
                    }
                }
             }
        }

        // we create ALL possible combinations, but some don't make sense at all, so we'll remove those
        return array_filter($testCases, function($testCase) {
            if (!$testCase[0] && null !== $testCase[1]) { // no composer file, but a name in composer file...
                return false;
            }

            return true;
        });
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
            ->method('write')
            ->with('Project is an [a]pplication or a [l]ibrary: ');

        $this->io
            ->expects($this->at($atIndex++))
            ->method('read')
            ->willReturn('a');

        $this->io
            ->expects($this->at($atIndex++))
            ->method('writeLine')
            ->with(sprintf('Your package name: %s', $packageName));

        $this->handler->handle($this->args, $this->io);
    }
}