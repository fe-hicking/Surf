<?php
namespace TYPO3\Deploy\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Deploy".               *
 *                                                                        *
 *                                                                        */

/**
 * A shell command service
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ShellCommandService {

	/**
	 * Execute a shell command
	 *
	 * @param string $command
	 * @param \TYPO3\Deploy\Domain\Model\Node $node Node to execute command against, NULL means localhost
	 * @param \TYPO3\Deploy\Domain\Model\Deployment $deployment
	 * @param boolean TRUE if this command has to return a successful return code
	 * @return TRUE If the command execution was successful (zero return code)
	 */
	public function execute($command, $node, $deployment, $force = FALSE) {
		if ($node === NULL || $node->getHostname() === 'localhost') {
			$result = $this->executeLocalCommand($command, $deployment);
		} else {
			$result = $this->executeRemoteCommand($command, $node, $deployment);
		}
		if ($force && !$result) {
			throw new \Exception('Command ' . $command . ' return non-zero return code', 1311007746);
		}
		return $result;
	}

	/**
	 *
	 * @param string $command
	 * @param \TYPO3\Deploy\Domain\Model\Deployment $deployment 
	 * @return boolean
	 */
	public function executeLocalCommand($command, $deployment) {
		$deployment->getLogger()->log('Executing locally: "' . $command . '"', LOG_DEBUG);

		$fp = popen($command, 'r');
		while (($line = fgets($fp)) !== FALSE) {
			$deployment->getLogger()->log('> ' . $line);
	    }
	    $result = pclose($fp);

		return $result === 0;
	}


	/**
	 *
	 * @param string $command
	 * @param \TYPO3\Deploy\Domain\Model\Node $node
	 * @param \TYPO3\Deploy\Domain\Model\Deployment $deployment
	 * @return boolean
	 */
	public function executeRemoteCommand($command, $node, $deployment) {
		$deployment->getLogger()->log('Executing on ' . $node->getName() . ': "' . $command . '"', LOG_DEBUG);
		$username = $node->getOption('username');
		$hostname = $node->getHostname();

		// TODO Create SSH options
		$fp = popen('ssh -A ' . $username . '@' . $hostname . ' ' . escapeshellarg($command) . ' 2>&1', 'r');
		while (($line = fgets($fp)) !== FALSE) {
			$deployment->getLogger()->log('> ' . rtrim($line));
	    }
	    $result = pclose($fp);

		return $result === 0;
	}

}
?>