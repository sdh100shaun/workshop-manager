<?php

namespace PhpSchool\WorkshopManager;

use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class IOFactory
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class IOFactory
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return IOInterface
     */
    public function getIO(InputInterface $input, OutputInterface $output)
    {
        return new ConsoleIO($input, $output, new HelperSet);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return IOInterface
     */
    public function getNullableIO(InputInterface $input, OutputInterface $output)
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_VERBOSE:
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
            case OutputInterface::VERBOSITY_DEBUG:
                return new ConsoleIO($input, $output, new HelperSet);
            default:
                return new NullIO;
        }
    }
}
