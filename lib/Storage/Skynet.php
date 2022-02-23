<?php

namespace OCA\Skynet\Storage;


use OC\Files\Storage\Common;
use OCP\ILogger;
use Psr\Http\Client\ClientExceptionInterface;
use Skynet\Filesystem\Filesystem;
use function Skynet\Filesystem\createStreamingContext;
use function Skynet\Filesystem\filesystem;

class Skynet extends Common {
	/** @var ILogger */
	protected $logger;
	private Filesystem $filesystem;

	public function __construct( $arguments ) {
		$this->logger = $params['logger'] ?? \OC::$server->getLogger();

		$this->filesystem = filesystem()
			->setSeed( $arguments['seed'] );
	}

	public function getId() {
		return 'skynet:///';
	}

	public function rename( $path1, $path2 ) {

		$type = $this->filetype( $path1 );
		if ( ! $type ) {
			return false;
		}

		$this->removeCachedFile( $path1 );

		try {
			$node = 'dir' === $type ? $this->filesystem->directory( $path1 ) : $this->filesystem->file( $path1 );

			return $node->rename( $path2 );
		} catch ( ClientExceptionInterface $e ) {
			$this->logger->logException( $e, [ 'message' => sprintf( 'Error while copying %s', $type ) ] );

			return false;
		}
	}

	public function filetype( $path ) {
		try {
			$node = $this->filesystem->node( $path );

			if ( $node->isDirectory() ) {
				return 'dir';
			}
			if ( $node->isFile() ) {
				return 'file';
			}

			return false;
		} catch ( ClientExceptionInterface $e ) {
			$this->logger->logException( $e, [ 'message' => 'Error while fetching info' ] );

			return false;
		}
	}

	public function file_exists( $path ): bool {
		try {
			$node = $this->filesystem->node( $path );

			if ( $node->isDirectory() ) {
				return $this->filesystem->directory( $path )->exists();
			}

			if ( $node->isFile() ) {
				return $this->filesystem->file( $path )->exists();
			}

			return false;
		} catch ( ClientExceptionInterface $e ) {
			$this->logger->logException( $e, [ 'message' => 'Error while fetching info' ] );

			return false;
		}
	}

	public function mkdir( $path ): bool {
		try {
			return $this->filesystem->directory( $path )->create();
		} catch ( ClientExceptionInterface $e ) {
			$this->logger->logException( $e, [ 'message' => 'Error while creating folder' ] );
		}

		return false;
	}

	public function rmdir( $path ): bool {
		try {
			return $this->filesystem->directory( $path )->delete();
		} catch ( ClientExceptionInterface $e ) {
			$this->logger->logException( $e, [ 'message' => 'Error while deleting folder' ] );

			return false;
		}
	}

	public function opendir( $path ) {
		return opendir( 'skynet://' . $path );
	}

	public function stat( $path ) {
		try {
			$node = $this->filesystem->node( $path );

			if ( $node->isDirectory() ) {
				return $this->filesystem->directory( $path )->stat();
			}

			if ( $node->isFile() ) {
				return $this->filesystem->file( $path )->stat();
			}

			return [ 'mtime' => time() ];
		} catch ( ClientExceptionInterface $e ) {
			$this->logger->logException( $e, [ 'message' => 'Error while fetching info' ] );

			return false;
		}
	}

	public function unlink( $path ): bool {
		try {
			$node = $this->filesystem->file( $path );

			return $node->delete();
		} catch ( ClientExceptionInterface $e ) {
			$this->logger->logException( $e, [ 'message' => 'Error while deleting file' ] );

			return false;
		}
	}

	public function fopen( $path, $mode ) {
		/** @var \OCA\DAV\Connector\Sabre\Server $davServer */
		$davServer = $GLOBALS['server']->server;
		$range     = null;
		if ( isset( $davServer ) ) {
			if ( $httpRange = $davServer->getHTTPRange() ) {
				$range = createStreamingContext( $httpRange[0] );
			}
		}

		return fopen( 'skynet://' . $path, $mode, false, $range );
	}

	public function touch( $path, $mtime = null ): bool {
		try {
			$node = $this->filesystem->file( $path );

			return $node->touch();
		} catch ( ClientExceptionInterface $e ) {
			$this->logger->logException( $e, [ 'message' => 'Error while touching file' ] );

			return false;
		}
	}

	public function copy( $path1, $path2 ) {
		$type = $this->filetype( $path1 );
		if ( ! $type ) {
			return false;
		}

		try {
			$node = 'dir' === $type ? $this->filesystem->directory( $path1 ) : $this->filesystem->file( $path1 );

			if ( 'file' === $type ) {
				$this->removeCachedFile( $path2 );;
			}

			return $node->copy( $path2 );
		} catch ( ClientExceptionInterface $e ) {
			$this->logger->logException( $e, [ 'message' => sprintf( 'Error while copying %s', $type ) ] );

			return false;
		}
	}

	public function needsPartFile() {
		return false;
	}

}
