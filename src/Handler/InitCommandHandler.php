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

        $directoryCreations = array();
        if (null !== $resDirectory = $this->askForResourceDirectoryName($io)) {
            $directoryCreations = $this->getDirectoryCreationList($io, $resDirectory);
        }

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
            $answer = trim($io->read(1));

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
     * @param IO $io
     * @param $resDirectory
     * @return array
     */
    private function getDirectoryCreationList(IO $io, $resDirectory)
    {
        $directoryCreations = array();

        $configDir = $resDirectory . '/config';
        $publicDir = $resDirectory . '/public';
        $cssDir    = $publicDir . '/css';
        $imagesDir = $publicDir . '/images';
        $jsDir     = $publicDir . '/js';
        $viewsDir  = $resDirectory . '/views';
        $transDir  = $resDirectory . '/trans';

        if ($this->askForCreationOfDirectory($io, $configDir)) {
            $directoryCreations[] = $configDir;
        }

        if ($this->askForCreationOfDirectory($io, $publicDir)) {
            if ($this->askForCreationOfDirectory($io, $cssDir)) {
                $directoryCreations[] = $cssDir;
            }

            if ($this->askForCreationOfDirectory($io, $imagesDir)) {
                $directoryCreations[] = $imagesDir;
            }

            if ($this->askForCreationOfDirectory($io, $jsDir)) {
                $directoryCreations[] = $jsDir;
            }
        }

        if ($this->askForCreationOfDirectory($io, $viewsDir)) {
            $directoryCreations[] = $viewsDir;
        }

        if ($this->askForCreationOfDirectory($io, $transDir)) {
            $directoryCreations[] = $transDir;
        }

        return $directoryCreations;
    }

    /**
     * @param IO $io
     * @return null|string
     */
    private function askForResourceDirectoryName(IO $io)
    {
        $createResourceDirectory = null;
        do {
            $io->write('Create a resource directory [yes]: ');
            $createResourceDirectory = trim($io->readLine());

            if ('no' === $createResourceDirectory) {
                $createResourceDirectory = false;
            } else if (empty($createResourceDirectory) || 'yes' === $createResourceDirectory) {
                $createResourceDirectory = true;
            } else {
                $createResourceDirectory = null;
            }

        } while (null === $createResourceDirectory);

        if (!$createResourceDirectory) {
            return null;
        }

        $io->write('Name of the resource directory [res]: ');
        $resourceDirectory = trim($io->readLine());
        if (empty($resourceDirectory)) {
            $resourceDirectory = 'res';
        }

        return $resourceDirectory;
    }

    private function askForCreationOfDirectory(IO $io, $directory)
    {
        $createDirectory = null;
        do {
            $io->write(sprintf('Create directory "%s" [yes]: ', $directory));
            $createDirectory = trim($io->readLine());

            if ('no' === $createDirectory) {
                $createDirectory = false;
            } else if (empty($createDirectory) || 'yes' === $createDirectory) {
                $createDirectory = true;
            } else {
                $createDirectory = null;
            }

        } while (null === $createDirectory);

        return $createDirectory;
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