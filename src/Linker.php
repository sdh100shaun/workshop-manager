<?php

namespace PhpSchool\WorkshopManager;

use Composer\IO\IOInterface;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;

/**
 * Class Linker
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
final class Linker
{
    /**
     * @var ManagerState
     */
    private $state;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @param ManagerState $state
     * @param Filesystem $filesystem
     * @param IOInterface $io
     */
    public function __construct(ManagerState $state, Filesystem $filesystem, IOInterface $io)
    {
        $this->state      = $state;
        $this->filesystem = $filesystem;
        $this->io         = $io;
    }

    /**
     * @param Workshop $workshop
     * @param bool $force
     *
     * @return bool
     */
    public function symlink(Workshop $workshop, $force = false)
    {
        if (!$this->state->isWorkshopInstalled($workshop)) {
            $this->io->write(sprintf(' <error> Workshop "%s" not installed </error>', $workshop->getName()));
            return false;
        }

        $localTarget = $this->getLocalTargetPath($workshop);

        $this->removeWorkshopBin($localTarget, $force);

        return $this->useSytemPaths()
            ? $this->link($workshop, $localTarget) && $this->symlinkToSystem($workshop, $force)
            : $this->link($workshop, $localTarget);
    }

    /**
     * @param Workshop $workshop
     * @param bool $force
     *
     * @throws \RuntimeException
     */
    private function symlinkToSystem(Workshop $workshop, $force)
    {
        $localTarget  = $this->getLocalTargetPath($workshop);
        $systemTarget = $this->getSystemInstallPath($workshop->getName());

        if (!is_writable(dirname($systemTarget))) {
            $this->io->write([
                sprintf(
                    ' <error> The system directory: "%s" is not writeable. </error>',
                    dirname($systemTarget)
                ),
                sprintf(
                    ' <info>Workshop "%s" is installed but not linked to an executable path.</info>',
                    $workshop->getName()
                ),
                '',
                sprintf(' You have two options now:'),
                sprintf(
                    '  1. Add the PHP School local bin dir: <info>%s</info> to your PATH variable',
                    dirname($localTarget)
                ),
                sprintf(
                    '      e.g. Run <info>$ echo \'export PATH="$PATH:%s"\' >> ~/.bashrc && source ~/.bashrc</info>',
                    dirname($localTarget)
                ),
                '      Replacing ~/.bashrc with your chosen bash config file e.g. ~/.zshrc or ~/.profile etc',
                sprintf(
                    '  2. Run <info>%s</info> directly with <info>$ php %s</info>',
                    $workshop->getName(),
                    $localTarget
                )
            ]);

            throw new \RuntimeException;
        }

        $this->removeWorkshopBin($systemTarget, $force);

        $this->link($workshop, $systemTarget);
    }

    /**
     * @param Workshop $workshop
     * @param string $target
     *
     * @throws \RuntimeException
     */
    private function link(Workshop $workshop, $target)
    {
        if (!@symlink($this->getWorkshopSrcPath($workshop), $target)) {
            $this->io->write([
                ' <error> Unexpected error occurred </error>',
                sprintf(' <error> Failed symlinking workshop bin to path "%s" </error>', $target)
            ]);
            throw new \RuntimeException;
        }

        if (!chmod($target, 0755)) {
            $this->io->write([
                ' <error> Unable to make workshop executable </error>',
                ' You may have to run the following with elevated privilages:',
                sprintf(' <info>$ chmod +x %s</info>', $target)
            ]);
            throw new \RuntimeException;
        }
    }

    /**
     * @param Workshop $workshop
     * @param bool $force
     *
     * @throws WorkshopNotInstalledException
     * @throws \RuntimeException
     */
    public function unlink(Workshop $workshop, $force = false)
    {
        if (!$this->state->isWorkshopInstalled($workshop)) {
            throw new WorkshopNotInstalledException;
        }

        $systemTarget = $this->getSystemInstallPath($workshop->getName());
        $localTarget  = $this->filesystem->getAdapter()->applyPathPrefix(sprintf('bin/%s', $workshop->getName()));

        $removed = $this->removeWorkshopBin($systemTarget, $force) && $this->removeWorkshopBin($localTarget, $force);

        if (!$removed) {
            throw new \RuntimeException;
        }
    }

    /**
     * @param string $path
     * @param bool $force
     *
     * @return bool
     */
    private function removeWorkshopBin($path, $force)
    {
        if (!file_exists($path)) {
            return true;
        }

        if (!$force && !is_link($path)) {
            $this->io->write([
                sprintf(' <error> File already exists at path "%s" </error>', $path),
                ' <info>Try again using --force or manually remove the file</info>'
            ]);

            return false;
        }

        if (!unlink($path)) {
            $this->io->write([
                sprintf(' <error> Failed to remove file at path "%s" </error>', $path),
                ' <info>You may need to remove a blocking file manually with elevated privilages</info>'
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param Workshop $workshop
     * @return string
     */
    private function getWorkshopSrcPath(Workshop $workshop)
    {
        return $this->filesystem->getAdapter()->applyPathPrefix(sprintf(
            'workshops/%s/bin/%s',
            $workshop->getName(),
            $workshop->getName()
        ));
    }

    /**
     * @param Workshop $workshop
     * @return string
     */
    private function getLocalTargetPath(Workshop $workshop)
    {
        // Ensure bin dir exists
        $path = sprintf('bin/%s', $workshop->getName());
        $this->filesystem->createDir(dirname($path));

        return $this->filesystem->getAdapter()->applyPathPrefix($path);
    }

    /**
     * @param string $binary
     * @return string
     */
    private function getSystemInstallPath($binary)
    {
        return sprintf('/usr/local/bin/%s', $binary);
    }

    /**
     * Use system paths if PHP School dir is not in PATH variable
     *
     * @return bool
     */
    private function useSytemPaths()
    {
        return strpos(getenv('PATH'), $this->filesystem->getAdapter()->applyPathPrefix('bin')) === false;
    }
}