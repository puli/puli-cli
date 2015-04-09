<?php

/*
 * This file is part of the puli/cli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Cli\Tests\Handler;

use PHPUnit_Framework_MockObject_MockObject;
use Puli\Cli\Handler\PathCommandHandler;
use Puli\Manager\Api\Package\Package;
use Puli\Manager\Api\Package\PackageCollection;
use Puli\Manager\Api\Package\PackageFile;
use Puli\Manager\Api\Package\RootPackage;
use Puli\Manager\Api\Package\RootPackageFile;
use Puli\Manager\Api\Repository\PathConflict;
use Puli\Manager\Api\Repository\PathMapping;
use Puli\Manager\Api\Repository\PathMappingState;
use Puli\Manager\Api\Repository\RepositoryManager;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Args\StringArgs;
use Webmozart\Expression\Expr;
use Webmozart\Expression\Expression;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PathCommandHandlerTest extends AbstractCommandHandlerTest
{
    /**
     * @var Command
     */
    private static $listCommand;

    /**
     * @var Command
     */
    private static $mapCommand;

    /**
     * @var Command
     */
    private static $removeCommand;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|RepositoryManager
     */
    private $repoManager;

    /**
     * @var PackageCollection
     */
    private $packages;

    /**
     * @var PathCommandHandler
     */
    private $handler;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$listCommand = self::$application->getCommand('path')->getSubCommand('list');
        self::$mapCommand = self::$application->getCommand('path')->getSubCommand('map');
        self::$removeCommand = self::$application->getCommand('path')->getSubCommand('remove');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->repoManager = $this->getMock('Puli\Manager\Api\Repository\RepositoryManager');
        $this->packages = new PackageCollection(array(
            new RootPackage(new RootPackageFile('vendor/root'), '/root'),
            new Package(new PackageFile('vendor/package1'), '/package1'),
            new Package(new PackageFile('vendor/package2'), '/package2'),
        ));
        $this->handler = new PathCommandHandler($this->repoManager, $this->packages);
    }

    public function testListAllMappings()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
The following path mappings are currently enabled:

    vendor/root
    /root/enabled res, assets

    vendor/package1
    /package1/enabled res, @vendor/package2:res

    vendor/package2
    /package2/enabled res

The target paths of the following path mappings were not found:

    vendor/root
    /root/not-found res

    vendor/package1
    /package1/not-found res

    vendor/package2
    /package2/not-found res

The following path mappings have conflicting paths:
 (add the package names to the "override-order" key in puli.json to resolve)

    Conflict: /conflict1

    Mapped by:
    vendor/root     /conflict1 res, assets
    vendor/package1 /conflict1 res, @vendor/package2:res
    vendor/package2 /conflict1 res

    Conflict: /conflict2/sub/path

    Mapped by:
    vendor/package1 /conflict2 res
    vendor/package2 /conflict2 res


EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListRootPackageMappings()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--root'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
The following path mappings are currently enabled:

    /root/enabled res, assets

The target paths of the following path mappings were not found:

    /root/not-found res

The following path mappings have conflicting paths:
 (add the package names to the "override-order" key in puli.json to resolve)

    Conflict: /conflict1

    Mapped by:
    vendor/root     /conflict1 res, assets
    vendor/package1 /conflict1 res, @vendor/package2:res
    vendor/package2 /conflict1 res


EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListPackageMappings()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--package vendor/package1'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
The following path mappings are currently enabled:

    /package1/enabled res, @vendor/package2:res

The target paths of the following path mappings were not found:

    /package1/not-found res

The following path mappings have conflicting paths:
 (add the package names to the "override-order" key in puli.json to resolve)

    Conflict: /conflict1

    Mapped by:
    vendor/root     /conflict1 res, assets
    vendor/package1 /conflict1 res, @vendor/package2:res
    vendor/package2 /conflict1 res

    Conflict: /conflict2/sub/path

    Mapped by:
    vendor/package1 /conflict2 res
    vendor/package2 /conflict2 res


EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListRootAndPackageMappings()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--root --package vendor/package1'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
The following path mappings are currently enabled:

    vendor/root
    /root/enabled res, assets

    vendor/package1
    /package1/enabled res, @vendor/package2:res

The target paths of the following path mappings were not found:

    vendor/root
    /root/not-found res

    vendor/package1
    /package1/not-found res

The following path mappings have conflicting paths:
 (add the package names to the "override-order" key in puli.json to resolve)

    Conflict: /conflict1

    Mapped by:
    vendor/root     /conflict1 res, assets
    vendor/package1 /conflict1 res, @vendor/package2:res
    vendor/package2 /conflict1 res

    Conflict: /conflict2/sub/path

    Mapped by:
    vendor/package1 /conflict2 res
    vendor/package2 /conflict2 res


EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListMultiplePackageMappings()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--package vendor/package1 --package vendor/package2'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
The following path mappings are currently enabled:

    vendor/package1
    /package1/enabled res, @vendor/package2:res

    vendor/package2
    /package2/enabled res

The target paths of the following path mappings were not found:

    vendor/package1
    /package1/not-found res

    vendor/package2
    /package2/not-found res

The following path mappings have conflicting paths:
 (add the package names to the "override-order" key in puli.json to resolve)

    Conflict: /conflict1

    Mapped by:
    vendor/root     /conflict1 res, assets
    vendor/package1 /conflict1 res, @vendor/package2:res
    vendor/package2 /conflict1 res

    Conflict: /conflict2/sub/path

    Mapped by:
    vendor/package1 /conflict2 res
    vendor/package2 /conflict2 res


EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListEnabledMappings()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--enabled'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
vendor/root
/root/enabled res, assets

vendor/package1
/package1/enabled res, @vendor/package2:res

vendor/package2
/package2/enabled res


EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListNotFoundMappings()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--not-found'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
vendor/root
/root/not-found res

vendor/package1
/package1/not-found res

vendor/package2
/package2/not-found res


EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListConflictingMappings()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--conflict'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
Conflict: /conflict1

Mapped by:
vendor/root     /conflict1 res, assets
vendor/package1 /conflict1 res, @vendor/package2:res
vendor/package2 /conflict1 res

Conflict: /conflict2/sub/path

Mapped by:
vendor/package1 /conflict2 res
vendor/package2 /conflict2 res


EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListEnabledAndNotFoundMappings()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--enabled --not-found'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
The following path mappings are currently enabled:

    vendor/root
    /root/enabled res, assets

    vendor/package1
    /package1/enabled res, @vendor/package2:res

    vendor/package2
    /package2/enabled res

The target paths of the following path mappings were not found:

    vendor/root
    /root/not-found res

    vendor/package1
    /package1/not-found res

    vendor/package2
    /package2/not-found res


EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListEnabledMappingsFromRoot()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--enabled --root'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
/root/enabled res, assets

EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListEnabledMappingsFromPackage()
    {
        $this->initDefaultManager();

        $args = self::$listCommand->parseArgs(new StringArgs('--enabled --package vendor/package1'));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
/package1/enabled res, @vendor/package2:res

EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListNoMappings()
    {
        $this->repoManager->expects($this->any())
            ->method('getRootPathMappings')
            ->willReturn(array());

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $statusCode = $this->handler->handleList($args, $this->io);

        $expected = <<<EOF
No path mappings. Use "puli path map <path> <file>" to map a Puli path to a file or directory.

EOF;

        $this->assertSame(0, $statusCode);
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testAddMappingWithRelativePath()
    {
        $args = self::$mapCommand->parseArgs(new StringArgs('path1 res assets'));

        $this->repoManager->expects($this->once())
            ->method('hasRootPathMapping')
            ->with('/path1')
            ->willReturn(false);

        $this->repoManager->expects($this->once())
            ->method('addRootPathMapping')
            ->with(new PathMapping('/path1', array('res', 'assets')));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testAddMappingWithAbsolutePath()
    {
        $args = self::$mapCommand->parseArgs(new StringArgs('/path1 res assets'));

        $this->repoManager->expects($this->once())
            ->method('hasRootPathMapping')
            ->with('/path1')
            ->willReturn(false);

        $this->repoManager->expects($this->once())
            ->method('addRootPathMapping')
            ->with(new PathMapping('/path1', array('res', 'assets')));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testAddMappingForce()
    {
        $args = self::$mapCommand->parseArgs(new StringArgs('--force /path res'));

        $this->repoManager->expects($this->once())
            ->method('hasRootPathMapping')
            ->with('/path')
            ->willReturn(false);

        $this->repoManager->expects($this->once())
            ->method('addRootPathMapping')
            ->with(new PathMapping('/path', array('res')), RepositoryManager::NO_TARGET_PATH_CHECK);

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testReplaceMapping()
    {
        $args = self::$mapCommand->parseArgs(new StringArgs('/path res assets'));

        $this->repoManager->expects($this->once())
            ->method('hasRootPathMapping')
            ->with('/path')
            ->willReturn(true);

        $this->repoManager->expects($this->once())
            ->method('getRootPathMapping')
            ->with('/path')
            ->willReturn(new PathMapping('/path', array('previous')));

        $this->repoManager->expects($this->once())
            ->method('addRootPathMapping')
            ->with(new PathMapping('/path', array('res', 'assets')));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testAddPathReference()
    {
        $args = self::$mapCommand->parseArgs(new StringArgs('/path +assets'));

        $this->repoManager->expects($this->once())
            ->method('hasRootPathMapping')
            ->with('/path')
            ->willReturn(true);

        $this->repoManager->expects($this->once())
            ->method('getRootPathMapping')
            ->with('/path')
            ->willReturn(new PathMapping('/path', array('res')));

        $this->repoManager->expects($this->once())
            ->method('addRootPathMapping')
            ->with(new PathMapping('/path', array('res', 'assets')));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testRemovePathReference()
    {
        $args = self::$mapCommand->parseArgs(new StringArgs('/path -- -assets'));

        $this->repoManager->expects($this->once())
            ->method('hasRootPathMapping')
            ->with('/path')
            ->willReturn(true);

        $this->repoManager->expects($this->once())
            ->method('getRootPathMapping')
            ->with('/path')
            ->willReturn(new PathMapping('/path', array('res', 'assets')));

        $this->repoManager->expects($this->once())
            ->method('addRootPathMapping')
            ->with(new PathMapping('/path', array('res')));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testRemoveAllPathReferences()
    {
        $args = self::$mapCommand->parseArgs(new StringArgs('/path -- -res -assets'));

        $this->repoManager->expects($this->once())
            ->method('hasRootPathMapping')
            ->with('/path')
            ->willReturn(true);

        $this->repoManager->expects($this->once())
            ->method('getRootPathMapping')
            ->with('/path')
            ->willReturn(new PathMapping('/path', array('res', 'assets')));

        $this->repoManager->expects($this->once())
            ->method('removeRootPathMapping')
            ->with('/path');

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testRemoveMappingWithRelativePath()
    {
        $args = self::$removeCommand->parseArgs(new StringArgs('path1'));

        $this->repoManager->expects($this->once())
            ->method('removeRootPathMapping')
            ->with('/path1');

        $this->assertSame(0, $this->handler->handleRemove($args));
    }

    public function testRemoveMappingWithAbsolutePath()
    {
        $args = self::$removeCommand->parseArgs(new StringArgs('/path1'));

        $this->repoManager->expects($this->once())
            ->method('removeRootPathMapping')
            ->with('/path1');

        $this->assertSame(0, $this->handler->handleRemove($args));
    }

    private function initDefaultManager()
    {
        $conflictMappingRoot1 = new PathMapping('/conflict1', array('res', 'assets'));
        $conflictMappingPackage11 = new PathMapping('/conflict1', array('res', '@vendor/package2:res'));
        $conflictMappingPackage12 = new PathMapping('/conflict2', 'res');
        $conflictMappingPackage21 = new PathMapping('/conflict1', 'res');
        $conflictMappingPackage22 = new PathMapping('/conflict2', 'res');

        $conflictMappingRoot1->load($this->packages->getRootPackage(), $this->packages);
        $conflictMappingPackage11->load($this->packages->get('vendor/package1'), $this->packages);
        $conflictMappingPackage12->load($this->packages->get('vendor/package1'), $this->packages);
        $conflictMappingPackage21->load($this->packages->get('vendor/package2'), $this->packages);
        $conflictMappingPackage22->load($this->packages->get('vendor/package2'), $this->packages);

        $conflict1 = new PathConflict('/conflict1');
        $conflict1->addMappings(array(
            $conflictMappingRoot1,
            $conflictMappingPackage11,
            $conflictMappingPackage21,
        ));

        $conflict2 = new PathConflict('/conflict2/sub/path');
        $conflict2->addMappings(array(
            $conflictMappingPackage12,
            $conflictMappingPackage22,
        ));

        $this->repoManager->expects($this->any())
            ->method('findPathMappings')
            ->willReturnCallback($this->returnFromMap(array(
                array($this->packageAndState('vendor/root', PathMappingState::ENABLED), array(
                    new PathMapping('/root/enabled', array('res', 'assets')),
                )),
                array($this->packageAndState('vendor/package1', PathMappingState::ENABLED), array(
                    new PathMapping('/package1/enabled', array('res', '@vendor/package2:res')),
                )),
                array($this->packageAndState('vendor/package2', PathMappingState::ENABLED), array(
                    new PathMapping('/package2/enabled', 'res'),
                )),
                array($this->packageAndState('vendor/root', PathMappingState::NOT_FOUND), array(
                    new PathMapping('/root/not-found', 'res'),
                )),
                array($this->packageAndState('vendor/package1', PathMappingState::NOT_FOUND), array(
                    new PathMapping('/package1/not-found', 'res'),
                )),
                array($this->packageAndState('vendor/package2', PathMappingState::NOT_FOUND), array(
                    new PathMapping('/package2/not-found', 'res'),
                )),
                array($this->packagesAndState(array('vendor/root'), PathMappingState::CONFLICT), array(
                    $conflictMappingRoot1,
                )),
                array($this->packagesAndState(array('vendor/package1'), PathMappingState::CONFLICT), array(
                    $conflictMappingPackage11,
                    $conflictMappingPackage12,
                )),
                array($this->packagesAndState(array('vendor/package2'), PathMappingState::CONFLICT), array(
                    $conflictMappingPackage21,
                    $conflictMappingPackage22,
                )),
                array($this->packagesAndState(array('vendor/root', 'vendor/package1'), PathMappingState::CONFLICT), array(
                    $conflictMappingRoot1,
                    $conflictMappingPackage11,
                    $conflictMappingPackage12,
                )),
                array($this->packagesAndState(array('vendor/root', 'vendor/package2'), PathMappingState::CONFLICT), array(
                    $conflictMappingRoot1,
                    $conflictMappingPackage21,
                    $conflictMappingPackage22,
                )),
                array($this->packagesAndState(array('vendor/package1', 'vendor/package2'), PathMappingState::CONFLICT), array(
                    $conflictMappingPackage11,
                    $conflictMappingPackage12,
                    $conflictMappingPackage21,
                    $conflictMappingPackage22,
                )),
                array($this->packagesAndState(array('vendor/root', 'vendor/package1', 'vendor/package2'), PathMappingState::CONFLICT), array(
                    $conflictMappingRoot1,
                    $conflictMappingPackage11,
                    $conflictMappingPackage12,
                    $conflictMappingPackage21,
                    $conflictMappingPackage22,
                )),
            )));
    }

    private function packageAndState($packageName, $state)
    {
        return Expr::same(PathMapping::CONTAINING_PACKAGE, $packageName)
            ->andSame(PathMapping::STATE, $state);
    }

    private function packagesAndState(array $packageNames, $state)
    {
        return Expr::oneOf(PathMapping::CONTAINING_PACKAGE, $packageNames)
            ->andSame(PathMapping::STATE, $state);
    }

    private function returnFromMap(array $map)
    {
        return function (Expression $expr) use ($map) {
            foreach ($map as $arguments) {
                // Cannot use willReturnMap(), which uses ===
                if ($expr->equals($arguments[0])) {
                    return $arguments[1];
                }
            }

            return null;
        };
    }
}