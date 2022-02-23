<?php

namespace OCA\Skynet\Auth;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICredentialsManager;
use function Skynet\Filesystem\filesystem;

class Lume extends AuthMechanism {
	public const CREDENTIALS_IDENTIFIER = 'lume';

	/** @var ICredentialsManager */
	protected $credentialsManager;

	public function __construct( IL10N $l, ICredentialsManager $credentialsManager ) {
		$this->credentialsManager = $credentialsManager;

		$this
			->setIdentifier( self::CREDENTIALS_IDENTIFIER )
			->setVisibility( BackendService::VISIBILITY_DEFAULT )
			->setScheme( 'skynet' )
			->setText( $l->t( 'Lume credentials' ) )
			->addParameters( [
				( new DefinitionParameter( 'email', $l->t( 'Email' ) ) )->setType( DefinitionParameter::VALUE_TEXT )->setFlag( DefinitionParameter::FLAG_OPTIONAL),
				( new DefinitionParameter( 'password', $l->t( 'Password' ) ) )->setType( DefinitionParameter::VALUE_PASSWORD )->setFlag( DefinitionParameter::FLAG_OPTIONAL),
				( new DefinitionParameter( 'session', null ) )->setType( DefinitionParameter::VALUE_HIDDEN )->setFlag( DefinitionParameter::FLAG_OPTIONAL),
				( new DefinitionParameter( 'seed', $l->t( 'Account/Encryption Seed' ) ) )->setType( DefinitionParameter::VALUE_PASSWORD ),
			] );
	}

	/*
		public function saveBackendOptions( IUser $user, $id, $backendOptions ) {
			// backendOptions are set when invoked via Files app
			// but they are not set when invoked via ext storage settings
			if ( ! isset( $backendOptions['user'] ) || ! isset( $backendOptions['password'] ) || ! isset( $backendOptions['seed'] ) ) {
				return;
			}
			// make sure we're not setting any unexpected keys
			$credentials = [
				'user'     => $backendOptions['user'],
				'password' => $backendOptions['password'],
				'seed'     => $backendOptions['seed'],
			];
			$this->credentialsManager->store( $user->getUID(), self::CREDENTIALS_IDENTIFIER, $credentials );
		}*/


	public function manipulateStorageConfig( StorageConfig &$storage, IUser $user = null ) {
		$filesystem = filesystem();
		$options    = $storage->getBackendOptions();

		$setLogin = function () use ( $storage, $options, &$filesystem ) {
			$filesystem->setOption( 'portal', [
				'url'      => $options['portal'] ?? null,
				'email'    => $options['email'],
				'password' => $options['password'],
			] );

			$storage->setBackendOption( 'session', $filesystem->getClient()->getSkynet()->getSessionKey() );

			/** @var GlobalStoragesService $storageService */
			$storageService = \OC::$server->get( GlobalStoragesService::class );
			$storageService->updateStorage( $storage );
		};

		if ( ! empty( $options['session'] ) ) {
			try {
				$filesystem->getClient()->getSkynet()->setPortalSession( $options['session'] );
			} catch ( \Exception $e ) {
				$setLogin();
			}
		} else {
			$setLogin();
		}
	}
}
