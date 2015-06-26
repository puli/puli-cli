<?php

namespace Puli\Cli\Handler;

use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\IO\IO;

class InitCommandHandler
{
    /**
     * @var string
     */
    private $rootDirectory;

    /**
     * @param string $rootDirectory
     */
    public function __construct($rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
    }

    public function handle(Args $args, IO $io)
    {
        $packageName = $this->askForPackageName($io);
        $this->askWhetherApplicationOrLibrary($io);

        $io->writeLine('Your package name: ' . $packageName);
    }

    /**
     * @param IO $io
     * @return string
     */
    private function askForPackageName(IO $io)
    {
        $appendix = '';
        if (null !== $composerPackageName = $this->getComposerPackageName()) {
            $appendix = sprintf(' [%s]', $composerPackageName);
        }

        do {
            $io->write(sprintf('Package name (<vendor>/<name>)%s: ', $appendix));
            $name = trim($io->readLine());
            if (empty($name)) {
                $name = $composerPackageName;
            }
        } while (empty($name));

        return $name;
    }

    /**
     * @param IO $io
     * @return bool|null true, if application
     *                   false, if library
     *                   null, if the user didn't specify the type
     */
    private function askWhetherApplicationOrLibrary(IO $io)
    {
        $answer = null;
        do {
            $io->write('Project is an [a]pplication or a [l]ibrary: ');
            $answer = $io->read(1);

            if (empty($answer)) {
                return null;
            } elseif ('a' === $answer) {
                return true;
            } elseif ('l' === $answer) {
                return false;
            } else {
                $answer = null;
            }
        } while (null === $answer);
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