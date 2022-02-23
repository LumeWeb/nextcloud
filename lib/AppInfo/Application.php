<?php
declare( strict_types=1 );

namespace OCA\Skynet\AppInfo;

use OCA\Files_External\Config\UserPlaceholderHandler;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Config\IAuthMechanismProvider;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External\Service\BackendService;
use OCA\Skynet\Backend\Lume;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap, IBackendProvider, IAuthMechanismProvider {

	public function __construct( array $urlParams = [] ) {
		parent::__construct( 'skynet', $urlParams );
		\OC_Hook::connect( 'OC_User', 'pre_deleteUser', 'OCA\MyApp\Hooks\User', 'deleteUser' );
	}

	public function register( IRegistrationContext $context ): void {
		include_once __DIR__ . '/../../vendor/autoload.php';
		$backendService = $this->getContainer()->get( BackendService::class );
		$backendService->registerBackendProvider( $this );
		$backendService->registerAuthMechanismProvider( $this );
	}

	public function boot( IBootContext $context ): void {

	}

	public function getBackends() {
		$container = $this->getContainer();

		return [
			$container->query( Lume::class ),
		];
	}

	public function getAuthMechanisms() {
		$container = $this->getContainer();

		return [
			$container->query( \OCA\Skynet\Auth\Lume::class ),
		];
	}
}
