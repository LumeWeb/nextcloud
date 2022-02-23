<?php

namespace OCA\Skynet\Backend;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\NullMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Service\BackendService;
use OCP\IL10N;

class Lume extends Backend {
	public function __construct( IL10N $l, NullMechanism $legacyAuth ) {
		$this
			->setIdentifier( 'lume' )
			->setStorageClass( \OCA\Skynet\Storage\Skynet::class )
			->setText( $l->t( 'Lume' ) )
			->addParameters( [
				( new DefinitionParameter( 'portal', $l->t( 'Portal URL to access data with' ) ) )->setFlag( DefinitionParameter::FLAG_OPTIONAL ),
			] )
			->setAllowedVisibility( BackendService::VISIBILITY_ADMIN )
			->setPriority( BackendService::PRIORITY_DEFAULT + 50 )
			->addAuthScheme( 'skynet' )
			->setLegacyAuthMechanism( $legacyAuth );
	}
}
