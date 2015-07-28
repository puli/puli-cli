<?php

namespace Puli\Cli\Handler;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Webmozart\Console\Adapter\ArgsInput;
use Webmozart\Console\Adapter\IOOutput;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\IO\IO;

class InitCommandHandler
{
    const PROJECT_TYPE_APP = 0;
    const PROJECT_TYPE_LIB = 1;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    /**
     * @var string
     */
    private $rootDirectory;

    /**
     * @param QuestionHelper $questionHelper
     * @param string $rootDirectory
     */
    public function __construct(QuestionHelper $questionHelper, $rootDirectory)
    {
        $this->questionHelper = $questionHelper;
        $this->rootDirectory = $rootDirectory;
    }

    public function handle(Args $args, IO $io)
    {
        $input  = new ArgsInput($args->getRawArgs(), $args);
        $output = new IOOutput($io);

        $packageName = $this->askForPackageName($input, $output);
        $projectType = $this->askWhetherApplicationOrLibrary($input, $output);

        $directoryCreations = array();
        if (null !== $resDirectory = $this->askForResourceDirectoryName($input, $output)) {
            $directoryCreations = $this->getDirectoryCreationList($input, $output, $resDirectory);
        }

        if (self::PROJECT_TYPE_LIB === $projectType) {
            $question = new ConfirmationQuestion(
                sprintf('Register mapping /%s => res [y]: ', $packageName)
            );

            $this->questionHelper->ask($input, $output, $question);
        } elseif (self::PROJECT_TYPE_APP === $projectType) {
            $question = new ConfirmationQuestion(
                sprintf('Register mapping /app => res [y]: ', $packageName)
            );

            $this->questionHelper->ask($input, $output, $question);
        }

        $io->writeLine('Your package name: ' . $packageName);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return string
     */
    private function askForPackageName(Input $input, Output $output)
    {
        $appendix = '';
        if (null !== $composerPackageName = $this->getComposerPackageName()) {
            $appendix = sprintf(' [%s]', $composerPackageName);
        }

        $question = new Question(
            sprintf('Package name (<vendor>/<name>)%s: ', $appendix),
            $composerPackageName
        );

        return $this->questionHelper->ask($input, $output, $question);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return string|null true, if application
     *                   false, if library
     * null, if the user didn't specify the type
     */
    private function askWhetherApplicationOrLibrary(Input $input, Output $output)
    {
        $question = new Question('Project is an (a)pplication, a (l)ibrary or (n)one of both [n]: ', '');
        $question->setValidator(function($value) {
            $value = trim($value);
            if (empty($value) || 'n' === $value) {
                return null;
            } else if ('a' === $value) {
                return InitCommandHandler::PROJECT_TYPE_APP;
            } else if ('l' === $value) {
                return InitCommandHandler::PROJECT_TYPE_LIB;
            }

            throw new \InvalidArgumentException();
        });

        return $this->questionHelper->ask($input, $output, $question);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @param $resDirectory
     * @return array
     */
    private function getDirectoryCreationList(Input $input, Output $output, $resDirectory)
    {
        $directoryCreations = array();

        $configDir = $resDirectory . '/config';
        $publicDir = $resDirectory . '/public';
        $cssDir    = $publicDir . '/css';
        $imagesDir = $publicDir . '/images';
        $jsDir     = $publicDir . '/js';
        $viewsDir  = $resDirectory . '/views';
        $transDir  = $resDirectory . '/trans';

        $questionHelper = $this->questionHelper;
        $helper = function($directory) use($input, $output, $questionHelper, &$directoryCreations) {
            $question = new ConfirmationQuestion(
                sprintf('Create directory "%s" [y]: ', $directory)
            );

            if ($answer = $questionHelper->ask($input, $output, $question)) {
                $directoryCreations[] = $directory;
            }

            return $answer;
        };

        $helper($configDir);

        if ($helper($publicDir)) {
            $helper($cssDir);
            $helper($imagesDir);
            $helper($jsDir);
        }

        $helper($viewsDir);
        $helper($transDir);

        return $directoryCreations;
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return null|string
     */
    private function askForResourceDirectoryName(Input $input, Output $output)
    {
        $question = new ConfirmationQuestion('Create a resource directory [y]: ');

        $createResDir = $this->questionHelper->ask($input, $output, $question);

        if (!$createResDir) {
            return null;
        }

        $question = new Question('Name of the resource directory [res]: ', 'res');

        return $this->questionHelper->ask($input, $output, $question);
    }

    /**
     * @return string|null
     */
    private function getComposerPackageName()
    {
        $file = $this->rootDirectory . '/composer.json';

        if (!file_exists($file)){
            return null;
        }

        if (false === $content = file_get_contents($file)) {
            return null;
        }

        if (false === $data = json_decode($content, true)) {
            return null;
        }

        if (!isset($data['name'])) {
            return null;
        }

        return $data['name'];
    }
}