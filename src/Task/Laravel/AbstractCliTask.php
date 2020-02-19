<?php

namespace TYPO3\Surf\Task\Laravel;

/*
 * This file is part of TYPO3 Surf.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

use TYPO3\Flow\Utility\Files;
use TYPO3\Surf\Application\Laravel;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Abstract task for any remote Laravel cli action
 */
abstract class AbstractCliTask extends Task implements ShellCommandServiceAwareInterface
{
    use ShellCommandServiceAwareTrait;

    /**
     * The working directory. Either local or remote, and probably in a special application root directory
     *
     * @var string
     */
    protected $workingDirectory;

    /**
     * Localhost or deployment target node
     *
     * @var Node
     */
    protected $targetNode;

    protected function executeCliCommand(
        array $cliArguments,
        Node $node,
        Laravel $application,
        Deployment $deployment,
        array $options = []
    ) {
        $this->determineWorkingDirectoryAndTargetNode($node, $application, $deployment, $options);
        $phpBinaryPathAndFilename = $options['phpBinaryPathAndFilename'] ?? 'php';
        $commandPrefix = $phpBinaryPathAndFilename . ' ';

        $this->determineWorkingDirectoryAndTargetNode($node, $application, $deployment, $options);

        return $this->shell->executeOrSimulate(
            [
                'cd ' . escapeshellarg($this->workingDirectory),
                $commandPrefix . implode(' ', array_map('escapeshellarg', $cliArguments))
            ],
            $this->targetNode,
            $deployment
        );
    }

    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $this->execute($node, $application, $deployment, $options);
    }

    protected function determineWorkingDirectoryAndTargetNode(
        Node $node,
        Laravel $application,
        Deployment $deployment,
        array $options = []
    ) {
        if (!isset($this->workingDirectory, $this->targetNode)) {
            if (isset($options['useApplicationWorkspace']) && $options['useApplicationWorkspace'] === true) {
                $this->workingDirectory = $deployment->getWorkspacePath($application);
                $node = $deployment->getNode('localhost');
            } else {
                $this->workingDirectory = $deployment->getApplicationReleasePath($application);
            }
            $this->targetNode = $node;
        }
    }

    /**
     * Checks if a given directory exists.
     *
     * @param string $directory
     * @return bool
     */
    protected function directoryExists(
        $directory,
        Node $node,
        Laravel $application,
        Deployment $deployment,
        array $options = []
    ): bool {
        $this->determineWorkingDirectoryAndTargetNode($node, $application, $deployment, $options);
        $directory = Files::concatenatePaths([$this->workingDirectory, $directory]);
        return $this->shell->executeOrSimulate(
                'test -d ' . escapeshellarg($directory),
                $this->targetNode,
                $deployment,
                true
            ) !== false;
    }

    /**
     * Checks if a given file exists.
     *
     * @param string $pathAndFileName
     * @return bool
     */
    protected function fileExists(
        $pathAndFileName,
        Node $node,
        Laravel $application,
        Deployment $deployment,
        array $options = []
    ): bool {
        $this->determineWorkingDirectoryAndTargetNode($node, $application, $deployment, $options);
        $pathAndFileName = $this->workingDirectory . '/' . $pathAndFileName;
        return $this->shell->executeOrSimulate(
                'test -f ' . escapeshellarg($pathAndFileName),
                $this->targetNode,
                $deployment,
                true
            ) !== false;
    }
}
