<?php
/**
 * deploy -	Upload files to remote server from svn folder.
 * 			It supports both FTP and sFTP.
 *
 * Copyright (c) 2013  Midhun Devasia (mail@midhundevasia.com)
 *
 * This program is free software and open source software; you can redistribute
 * it and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 * 
 * @author Midhun Devasia
 * @version 1.0
 * Requirements : php-cli, svn, php_ssh2
 *
 * Usage 	: php path/to/svn/folder/deploy/deploy.php --revision(-r)
 * Example 	: D:\xmapp\htdocs\myproject\deploy> php deploy.php -r 14550
 */

error_reporting(0);
if (php_sapi_name() != 'cli') {
	die('Must run from command line');
}

$deploy = new Deploy;
$deploy->run();
echo "\n";

/************************* CLASSES / FUNCTIONS ********************************/

/**
 * 
 */
class Uploader {
	
	var $credential 	= "";
	var $dirPaths		= "";
	var $uploadedFiles	= array();
	var $failedFiles	= array();
	var $deletedFiles	= array();

	private $connection = "";

	function ftpConnect() {

		try {
			$this->connection = @ssh2_connect($this->credential['ftpHost'], $this->credential['ftpPort']);
			if($this->connection) {
				echo "Connecting to {$this->credential['ftpHost']}...\n";
			}
			if($this->connection && @ssh2_auth_password($this->connection, $this->credential['ftpUsername'], $this->credential['ftpPassword'])) {
				//Initialize SFTP subsystem
				$this->SFTP = @ssh2_sftp($this->connection);
				if($this->SFTP) { echo "sftp started\n";}
			}else {
				echo "\nUnable to authenticate on server '{$this->credential['ftpHost']}:{$this->credential['ftpPort']}'\nCheck host address & port.";
				exit();
			}

		}catch (Exception $e) {
		    echo $e->getMessage() . "\n";
		    exit();
		}

	}

	function uploadFiles($fileLists = '') {

		echo "\n";
		$this->log = '';
		$format = '| %-10s | %-10s | %-10s '."\n";
		$this->consoleOut(sprintf("+%'-12s+%'-12s+%'%'-74s\n", "", "","", ""));
		$this->consoleOut(sprintf('| %-10s | %-10s | %-10s '."\n" . "+%'-100s\n", "STATUS", "ACTION", "FILE", ""));

		foreach ($fileLists as $type => $files) {
			
			// Modified files
			if ($type == "modified") {
				if (count($files)) {

					foreach ($files as $localFile => $remoteFile) {
						if (file_exists($localFile)) {
							if ($fstat = ssh2_sftp_stat($this->SFTP, $remoteFile)) {
								if(ssh2_scp_send($this->connection, $localFile, $remoteFile)) {
									$this->uploadedFiles[] = " MODIFIED  " . $remoteFile;
									$this->consoleOut(sprintf($format, "OK", "MODIFIED", $remoteFile, ""));
								}else {
									$this->failedFiles[] = " MODIFIED  " . $remoteFile;
									$this->consoleOut(sprintf($format, "FAILED", "MODIFIED", $remoteFile, ""));
								}
							}
						}else {}
					}
				}else {
					$msg = "\nNo modified file(s)";
				}
			}elseif ($type == "added") {
				if (count($files)) {
					foreach ($files as $localFile => $remoteFile) {
						// Check if the folder already exist
						if ($fstat = ssh2_sftp_stat($this->SFTP, dirname($remoteFile))) {
							// the move file
							if(ssh2_scp_send($this->connection, $localFile, $remoteFile)) {
								$this->uploadedFiles[] = " ADDED     " . $remoteFile;
								$this->consoleOut(sprintf($format, "OK", "ADD", $remoteFile, ""));
							}else {
								$this->failedFiles[] = " ADDED     " . $remoteFile;
								$this->consoleOut(sprintf($format, "FAILED", "ADD", $remoteFile, ""));
							}
						}else {
							// Find directory name and create directory
							if(ssh2_sftp_mkdir($this->SFTP, dirname($remoteFile), 0777, true)) {
								// the move file to the new directory
								if(ssh2_scp_send($this->connection, $localFile, $remoteFile)) {
									$this->uploadedFiles[] = " ADDED     " . $remoteFile;
									$this->consoleOut(sprintf($format, "OK", "ADD", $remoteFile, ""));
								}else {
									$this->failedFiles[] = " ADDED    " . $remoteFile;
									$this->consoleOut(sprintf($format, "FAILED", "ADD", $remoteFile, ""));
								}
							}
						}
					}
				}else {
					$msg = "\nNo new file(s) ";
				}
			}elseif ($type == "deleted") {
				if (count($files)) {
					foreach ($files as $localFile => $remoteFile) {
						if ($fstat = ssh2_sftp_stat($this->SFTP, $remoteFile)) {
							// remove if file exist
							if(ssh2_sftp_unlink($this->SFTP, $remoteFile)) {
								$this->uploadedFiles[] = " DELETE    " . $remoteFile;
								$this->consoleOut(sprintf($format, "OK", "DELETE", $remoteFile, ""));
							}else {
								$this->failedFiles[] = " DELETE    " . $remoteFile;
								$this->consoleOut(sprintf($format, "FAILED", "DELETE", $remoteFile, ""));
							}
						}else {

						}
					}
				}else {
					$msg = "\nNo file(s) deleted";
				}
			}
		}

		$this->consoleOut(sprintf("+%'-12s+%'-12s+%'%'-74s\n", "", "","", ""));
		$this->consoleOut(sprintf("(%s) file(s) Uploaded, (%s) file(s) failed.", count($this->uploadedFiles), count($this->failedFiles)));

	}

	function consoleOut($string) {
		echo $string;
		$this->log .= $string;
	}

	function writeLog($filename) {
		file_put_contents($filename, $this->log);
	}
}

/**
 * 
 */
Class CommandInterpreter {

	static function svnlog($core) {
		try {
			chdir($core->config['DIRPATHS']['localRepoPath']);
			$core->outPut = `$core->command`;
			return $core->outPut;
		}catch (Exception $e) {
		    echo $e->getMessage() . "\n";
		    exit();
		}
	}
}

/**
 * 
 */
Class DeployCore extends Uploader {
	
	var $command 		= "";
	var $outPut			= "";
	var $config			= "";
	var $commandType 	= "";
	
	function execute() {
		if (isset($this->command)) {
					
			switch ($this->commandType) {
				case 'svnlog'	:$this->outPut = CommandInterpreter::svnlog($this); 
								 $this->uploadFiles($this->getFiles());
								 break;
				
				default:	break;
			}
		}
	}

	function getFiles() {
		$rawOut = trim($this->outPut);
		$files = preg_split('/\n/', $rawOut);
		foreach ($files as $file) {
			// Find Modified files
			if(count($m = preg_split('/^M\s\//', trim($file))) == 2) {
				$fileLists['modified'][] = $m[1];
			}
			// Find Added files
			if(count($m = preg_split('/^A\s\//', trim($file))) == 2) {
				$fileLists['added'][] = $m[1];
			}
			// Find Deleted files
			if(count($m = preg_split('/^D\s\//', trim($file))) == 2) {
				$fileLists['deleted'][] = $m[1];
			}
		}

		// Prepare file paths for Modified/Added/Deleted files
		foreach ($fileLists as $type => $files) {
			// Modified files
			if ($type == "modified") {
				foreach ($files as $file) {
					extract(pathinfo($file));
					if ($basename) {
						$localDir = str_replace('trunk/', $this->config['DIRPATHS']['localRepoPath'], $dirname);
						$remoteDir = str_replace('trunk/', $this->config['DIRPATHS']['defaultRemoteDir'], $dirname);
						$localFilePath = $localDir . "\\" . $basename;
						$localFilePath = str_replace('/', '\\', $localFilePath);
						$remoteFilePath = $remoteDir . "/" . $basename;

						$fileList['modified'][$localFilePath] = $remoteFilePath;
					}
				}
			}elseif ($type == "added") {
				foreach ($files as $file) {
					extract(pathinfo($file));
					if ($basename) {
						$localDir = str_replace('trunk/', $this->config['DIRPATHS']['localRepoPath'], $dirname);
						$remoteDir = str_replace('trunk/', $this->config['DIRPATHS']['defaultRemoteDir'], $dirname);
						$localFilePath = $localDir . "\\" . $basename;
						$localFilePath = str_replace('/', '\\', $localFilePath);
						$remoteFilePath = $remoteDir . "/" . $basename;

						$fileList['added'][$localFilePath] = $remoteFilePath;
					}
				}
			}elseif ($type == "deleted") {
				foreach ($files as $file) {
					extract(pathinfo($file));
					if ($basename) {
						$localDir = str_replace('trunk/', $this->config['DIRPATHS']['localRepoPath'], $dirname);
						$remoteDir = str_replace('trunk/', $this->config['DIRPATHS']['defaultRemoteDir'], $dirname);
						$localFilePath = $localDir . "\\" . $basename;
						$localFilePath = str_replace('/', '\\', $localFilePath);
						$remoteFilePath = $remoteDir . "/" . $basename;
						
						$fileList['deleted'][$localFilePath] = $remoteFilePath;
					}
				}
			}
		}

		return $fileList;
	}

	function initCore() {
		$this->credential = $this->config['LOGIN'];
		$this->ftpConnect();
	}
}

/**
 * 
 */
Class Deploy extends DeployCore {

	function __construct() {
		echo "\n";
		$this->getConfig();
		$this->parseCommand();
		$this->initCore();
	}

	function parseCommand() {
		$this->revision = getopt("r:");
		if (count($this->revision)) {
			$this->command 		= 'svn log -qv -r ' . $this->revision['r'] . '';
			$this->commandType 	= "svnlog";
		}else {
			echo "Parameter(s) required.\nEg: deploy.php -r 1555\n";
			exit;
		}
	}

	function getConfig() {
		// Parse with sections
		$this->config = parse_ini_file("deploy.ini", true);
	}

	function run() {
		$this->execute();
		$this->writeLog($this->config['DEFAULT']['deployLogDir'] . 'r'.$this->revision['r'].".txt");
		echo "\n";
	}
}

function remove_empty($key) {
	if($key != '') return $key;
}
