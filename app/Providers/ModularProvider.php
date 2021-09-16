<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModularProvider extends ServiceProvider
{

	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$modules = config( 'modular.modules' );
		$path    = config( 'modular.path' );

		if ( $modules ) {
			Route::group( [
				'prefix' => ''
			], function () use ( $modules, $path ) {
				foreach ( $modules as $parent => $submodule ) {
					foreach ( $submodule as $key => $item ) {
						$relativePath = "/$parent/$item";

						Route::middleware( 'web' )
						     ->group( function () use ( $parent, $item, $relativePath, $path ) {
							     $this->getWebRoutes( $parent, $item, $relativePath, $path );
						     } );

						Route::prefix( 'api' )
						     ->middleware( 'api' )
						     ->group( function () use ( $parent, $item, $relativePath, $path ) {
							     $this->getApiRoutes( $parent, $item, $relativePath, $path );
						     } );
					}
				}
			} );
		}

		$this->app['view']->addNamespace('Pub', base_path() . '/resources/views/Pub');
	}

	private function getWebRoutes( $parent, $item, $relativePath, $path )
	{
		$routesPath = $path . $relativePath . '/Routes/web.php'; // F:\OpenServer\domains\apicrm.local/app/Modules/Admin/Users/Routes/web.php

		if ( file_exists( $routesPath ) ) {
			if ( $parent != config( 'modular.groupWithoutPrefix' ) ) {
				Route::group(
					[
						'prefix'     => strtolower( $parent ),
						'middleware' => $this->getMiddleware( $parent ),
					],
					function () use ( $parent, $item, $routesPath ) {
						Route::namespace( "App\Modules\\$parent\\$item\Controllers" )->group( $routesPath );
					} );
			} else {
				Route::namespace( "App\Modules\\$parent\\$item\Controllers" )
				     ->middleware( $this->getMiddleware( $parent ) )
				     ->group( $routesPath );
			}
		}
	}

	private function getMiddleware( $parent, $key = 'web' )
	{
		$middleware = [];
		$config     = config( 'modular.groupMiddleware' );
		if ( isset( $config[ $parent ] ) ) {
			if ( array_key_exists( $key, $config[ $parent ] ) ) {
				$middleware = array_merge( $middleware, $config[ $parent ][ $key ] );
			}
		}

		return $middleware;
	}

	private function getApiRoutes( $parent, $item, $relativePath, $path )
	{
		$routesPath = $path . $relativePath . '/Routes/api.php'; // F:\OpenServer\domains\apicrm.local/app/Modules/Admin/Users/Routes/api.php

		if ( file_exists( $routesPath ) ) {
			Route::group(
				[
					'prefix'     => strtolower( $parent ),
					'middleware' => $this->getMiddleware( $parent, 'api' ),
				],
				function () use ( $parent, $item, $routesPath ) {
					Route::namespace( "App\Modules\\$parent\\$item\Controllers" )->group( $routesPath );
				} );
		}
	}
}
