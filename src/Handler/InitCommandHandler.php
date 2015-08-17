<?php

namespace Puli\Cli\Handler;

use Puli\Manager\Api\Package\PackageFileSerializer;
use Puli\Manager\Api\Package\RootPackageFile;
use Puli\Manager\Api\Repository\PathMapping;
use Puli\Manager\Api\Storage\Storage;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
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
     * @var PackageFileSerializer
     */
    private $serializer;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param QuestionHelper $questionHelper
     * @param PackageFileSerializer $serializer
     * @param Storage $storage
     * @param Filesystem $filesystem
     */
    public function __construct(QuestionHelper $questionHelper, PackageFileSerializer $serializer, Storage $storage,
        Filesystem $filesystem)
    {
        $this->questionHelper = $questionHelper;
        $this->serializer = $serializer;
        $this->storage = $storage;
        $this->filesystem = $filesystem;
    }

    public function handle(Args $args, IO $io)
    {
        $input  = new ArgsInput($args->getRawArgs(), $args);
        $output = new IOOutput($io);

        $io->writeLine("<info>  Welcome to the Puli config generator  </info>");
        $io->writeLine('');
        $io->writeLine('<info>This command will guide you through creating your puli.json config.</info>');
        $io->writeLine('');

        $rootPackage = new RootPackageFile();

        $rootPackage->setPackageName(
            $this->askForPackageName($input, $output)
        );

        $projectType = $this->askWhetherApplicationOrLibrary($input, $output);

        $directoryCreations = array();
        if (null !== $resDirectory = $this->askForResourceDirectoryName($input, $output)) {
            $directoryCreations = $this->getDirectoryCreationList($input, $output, $resDirectory);
        }

        $from = null;
        if (self::PROJECT_TYPE_LIB === $projectType) {
            $from = '/' . $rootPackage->getPackageName();
        } elseif (self::PROJECT_TYPE_APP === $projectType) {
            $from = '/app';
        }

        if (null !== $from) {
            $question = new ConfirmationQuestion(
                sprintf('Register mapping %s => %s [<question>yes</question>]: ', $from, $resDirectory)
            );

            if ($this->questionHelper->ask($input, $output, $question)) {
                $rootPackage->addPathMapping(new PathMapping($from, $resDirectory));
            }
        }

        $io->writeLine('');

        if (!empty($directoryCreations)) {
            $io->writeLine('<info>Create following directories:</info>');
            $io->writeLine('');
            foreach ($directoryCreations as $dir) {
                $io->writeLine('- ' . $dir);
            }

            $io->writeLine('');
        }

        $serializedPackage = $this->serializer->serializeRootPackageFile($rootPackage);
        $io->writeLine('<info>Create following puli.json:</info>');
        $io->writeLine($serializedPackage);

        $io->writeLine('');

        $question = new ConfirmationQuestion(
            'Do you confirm generation [<question>yes</question>]? '
        );

        if (!$this->questionHelper->ask($input, $output, $question)) {
            $io->writeLine('<error>Command aborted</error>');
            return;
        }

        $this->filesystem->mkdir($directoryCreations);

        $this->storage->write(getcwd() . '/puli.json', $serializedPackage);
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
            $appendix = sprintf(' [<question>%s</question>]', $composerPackageName);
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
        $question = new Question(
            'Project is an (a)pplication, a (l)ibrary or (n)one of both [<question>n</question>]: ', ''
        );
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
                sprintf('Create directory "%s" [<question>yes</question>]: ', $directory)
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
        $question = new ConfirmationQuestion('Create a resource directory [<question>yes</question>]: ');

        $createResDir = $this->questionHelper->ask($input, $output, $question);

        if (!$createResDir) {
            return null;
        }

        $question = new Question('Name of the resource directory [<question>res</question>]: ', 'res');

        return $this->questionHelper->ask($input, $output, $question);
    }

    /**
     * @return string|null
     */
    private function getComposerPackageName()
    {
        $file = 'composer.json';

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