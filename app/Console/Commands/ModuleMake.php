<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ModuleMake extends Command
{

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'make:module {name}
                                        {--all}
                                        {--migration}
                                        {--vue}
                                        {--view}
                                        {--controller}
                                        {--model}
                                        {--api}'; // в этом свойстве храним объект класса Filesystem
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';
	private $files;

	/**
	 * Create a new command instance.
	 *
	 * @param Filesystem $filesystem
	 */
	public function __construct( Filesystem $filesystem )
	{
		parent::__construct();

		$this->files = $filesystem;
	}

	/**
	 * Execute the console command.
	 *
	 * @return int|void
	 * @throws FileNotFoundException
	 */
	public function handle()
	{
//		print_r( $this->option() );

		if ( $this->option( 'all' ) ) {
			$this->input->setOption( 'migration', TRUE );
			$this->input->setOption( 'vue', TRUE );
			$this->input->setOption( 'view', TRUE );
			$this->input->setOption( 'controller', TRUE );
			$this->input->setOption( 'model', TRUE );
			$this->input->setOption( 'api', TRUE );
		}

		if ( $this->option( 'model' ) ) {
			$this->createModel();
		}

		if ( $this->option( 'controller' ) ) {
			$this->createController();
		}

		if ( $this->option( 'api' ) ) {
			$this->createApiController();
		}

		if ( $this->option( 'migration' ) ) {
			$this->createMigration();
		}

		if ( $this->option( 'vue' ) ) {
			$this->createVueComponent();
		}

		if ( $this->option( 'view' ) ) {
			$this->createView();
		}

		return;
	}

	/**
	 * Создаем модель по пути App\Modules\Name\Name\Models\Model\Model.php
	 */
	private function createModel()
	{
		// На вход :: Admin\User-males
		// class_basename :: https://laravel.com/docs/8.x/helpers#method-class-basename
		$model = class_basename( $this->argument( 'name' ) ); // вернет User-males
		// The Str::studly method converts the given string to StudlyCase
		$model = Str::studly( $model ); // UserMales
		// The Str::singular method converts a string to its singular form. This function currently only supports the English language
		$model = Str::singular( $model ); // UserMale

		// создаем модель
		$this->call( 'make:model', [
			'name' => 'App\\Modules\\' . trim( $this->argument( 'name' ) ) . '\\Models\\' . $model,
		] );
	}

	/**
	 * Создаем контроллер
	 *
	 * @throws FileNotFoundException
	 */
	private function createController()
	{
		$controller = Str::studly( class_basename( $this->argument( 'name' ) ) );
		$modelName  = Str::singular( Str::studly( class_basename( $this->argument( 'name' ) ) ) );

		// путь
		// F:\OpenServer\domains\apicrm.local\app/Modules/Admin/User/Controllers/UserController.php
		$path = $this->getControllerPath( $this->argument( 'name' ) );

		// создание файла контроллера
		// base_path :: https://laravel.com/docs/8.x/helpers#method-base-path
		if ( $this->alreadyExists( $path ) ) {
			$this->error( 'Controller already exists.' );
		} else {
			// создаем папку для файла контроллера
			$this->makeDirectory( $path );

			// считываем данные из stub-файла в строковую переменную
			$stub = $this->files->get( base_path( 'resources/stubs/controller.model.api.stub' ) );

			// меняем в этом файле Dummy-значения на реальные
			$stub = str_replace(
				[
					'DummyNamespace',
					'DummyRootNamespace',
					'DummyClass',
					'DummyFullModelClass',
					'DummyModelClass',
					'DummyModelVariable',
				],
				[
					'App\\Modules\\' . trim( $this->argument( 'name' ) ) . '\\Controllers',
					$this->laravel->getNamespace(), // возвращает строку App\
					$controller . 'Controller',
					'App\\Modules\\' . trim( $this->argument( 'name' ) ) . '\\Models\\' . $modelName,
					$modelName,
					lcfirst( $modelName ),
				],
				$stub
			);

			// кладем эту строку в файл по указанному пути
			$this->files->put( $path, $stub );

			// выводим сообщение в консоль
			$this->info( 'Controller created successfully.' );

			// обновляем конфигурацию модуля для правильной маршрутизации
//			$this->updateModularConfig();
		}

		// создаем маршруты для обработки обычных запросов
		$this->createRoutes( $controller, $modelName );
	}

	/**
	 * Путь к контроллеру
	 *
	 * @param $argument
	 *
	 * @return string
	 */
	private function getControllerPath( $argument )
	{
		$controller = Str::studly( class_basename( $argument ) );

		// echo $this->laravel['path']; // F:\OpenServer\domains\apicrm.local\app
		return $this->laravel['path'] . '/Modules/' . str_replace( '\\', '/', $argument ) . '/Controllers/' . $controller . 'Controller.php';
	}

	/**
	 * Проверяет, действительно ли существует файл по переданному пути
	 *
	 * @param $path
	 *
	 * @return bool
	 */
	private function alreadyExists( $path ): bool
	{
		return $this->files->exists( $path );
	}

	/**
	 * Создаем директорию под файл
	 *
	 * @param $path - полный путь
	 *
	 * @return mixed
	 */
	private function makeDirectory( $path )
	{
		// dirname — Возвращает имя родительского каталога из указанного пути, отбрасывая имя файла.
		// Если такой директории нет, то создаем эту директорию.
		if ( ! $this->files->isDirectory( dirname( $path ) ) ) {
			$this->files->makeDirectory( dirname( $path ), 0777, TRUE, TRUE );
		}

		return $path;
	}

	/**
	 * Создаем маршруты (routes) из заранее заготовленных stubs
	 *
	 * @param string $controller
	 * @param string $modelName
	 *
	 * @throws FileNotFoundException
	 */
	private function createRoutes( string $controller, string $modelName ): void
	{
		$routePath = $this->getRoutesPath( $this->argument( 'name' ) );
		if ( $this->alreadyExists( $routePath ) ) {
			$this->error( 'Route already exists.' );
		} else {
			$this->makeDirectory( $routePath );
			$stub = $this->files->get( base_path( 'resources/stubs/routes.web.stub' ) );
			$stub = str_replace( [
				'DummyClass',
				'DummyRoutePrefix',
				'DummyModelVariable',
			], [
				$controller . 'Controller',
				Str::plural( Str::snake( lcfirst( $modelName ), '-' ) ),
				lcfirst( $modelName ),
			], $stub );
			$this->files->put( $routePath, $stub );
			$this->info( 'Route created successfully.' );
		}
	}

	/**
	 * Получаем путь к файлу с маршрутами
	 *
	 * @param $argument
	 *
	 * @return string
	 */
	private function getRoutesPath( $argument ): string
	{
		return $this->laravel['path'] . '/Modules/' . str_replace( '\\', '/', $argument ) . '/Routes/web.php';
	}

	/**
	 * Создаем API-контроллер
	 *
	 * @throws FileNotFoundException
	 */
	private function createApiController()
	{
		$controller = Str::studly( class_basename( $this->argument( 'name' ) ) );
		$modelName  = Str::singular( Str::studly( class_basename( $this->argument( 'name' ) ) ) );
		$path       = $this->getApiControllerPath( $this->argument( 'name' ) );

		if ( $this->alreadyExists( $path ) ) {
			$this->error( 'Controller already exists.' );
		} else {
			$this->makeDirectory( $path );
			$stub = $this->files->get( base_path( 'resources/stubs/controller.model.api.stub' ) );
			$stub = str_replace(
				[
					'DummyNamespace',
					'DummyRootNamespace',
					'DummyClass',
					'DummyFullModelClass',
					'DummyModelClass',
					'DummyModelVariable',
				],
				[
					'App\\Modules\\' . trim( $this->argument( 'name' ) ) . '\\Controllers\\Api',
					$this->laravel->getNamespace(), // возвращает строку App\
					$controller . 'Controller',
					'App\\Modules\\' . trim( $this->argument( 'name' ) ) . '\\Models\\' . $modelName,
					$modelName,
					lcfirst( $modelName ),
				],
				$stub
			);
			$this->files->put( $path, $stub );
			$this->info( 'API-controller created successfully.' );
//			$this->updateModularConfig();
		}

		// создаем маршруты для обработки API-запросов
		$this->createApiRoutes( $controller, $modelName );
	}

	/**
	 * Путь к API-контроллеру
	 *
	 * @param $argument
	 *
	 * @return string
	 */
	private function getApiControllerPath( $argument )
	{
		$controller = Str::studly( class_basename( $argument ) );

		return $this->laravel['path'] . '/Modules/' . str_replace( '\\', '/', $argument ) . '/Controllers/Api/' . $controller . 'Controller.php';
	}

	/**
	 * Создаем API-маршруты (routes) из заранее заготовленных stubs
	 *
	 * @param string $controller
	 * @param string $modelName
	 *
	 * @throws FileNotFoundException
	 */
	private function createApiRoutes( string $controller, string $modelName ): void
	{
		$routePath = $this->getApiRoutesPath( $this->argument( 'name' ) );
		if ( $this->alreadyExists( $routePath ) ) {
			$this->error( 'Route already exists.' );
		} else {
			$this->makeDirectory( $routePath );
			$stub = $this->files->get( base_path( 'resources/stubs/routes.api.stub' ) );
			$stub = str_replace( [
				'DummyClass',
				'DummyRoutePrefix',
				'DummyModelVariable',
			], [
				'Api\\' . $controller . 'Controller',
				Str::plural( Str::snake( lcfirst( $modelName ), '-' ) ),
				lcfirst( $modelName ),
			], $stub );
			$this->files->put( $routePath, $stub );
			$this->info( 'API-route created successfully.' );
		}
	}

	/**
	 * Получаем путь к файлу с API-маршрутами
	 *
	 * @param $argument
	 *
	 * @return string
	 */
	private function getApiRoutesPath( $argument ): string
	{
		return $this->laravel['path'] . '/Modules/' . str_replace( '\\', '/', $argument ) . '/Routes/api.php';
	}

	/**
	 * Создаем миграцию по пути App\Modules\Name\Name\Migrations\2014_10_12_100000_create_password_resets_table.php
	 */
	private function createMigration()
	{
		// 2014_10_12_100000_create_password_resets_table.php
		$table = Str::plural( Str::snake( class_basename( $this->argument( 'name' ) ) ) );

		try {
			$this->call( 'make:migration', [
				'name'     => 'create_' . $table . '_table',
				'--create' => $table,
				'--path'   => 'App\\Modules\\' . trim( $this->argument( 'name' ) ) . '\\Migrations',
			] );
		} catch ( Exception $e ) {
			$this->error( $e->getMessage() );
		}
	}

	/**
	 * Создаем компонент для View
	 *
	 * @throws FileNotFoundException
	 */
	private function createVueComponent()
	{
		$path      = $this->getVueComponentPath( $this->argument( 'name' ) );
		$component = Str::studly( class_basename( $this->argument( 'name' ) ) );

		if ( $this->alreadyExists( $path ) ) {
			$this->error( 'Vue component already exists.' );
		} else {
			$this->makeDirectory( $path );
			$stub = $this->files->get( base_path( 'resources/stubs/vue.component.stub' ) );
			$stub = str_replace(
				[
					'DummyClass',
				],
				[
					$component,
				],
				$stub
			);
			$this->files->put( $path, $stub );
			$this->info( 'Vue component created successfully.' );
		}
	}

	/**
	 * Получаем путь к vue компонентам
	 *
	 * @param $argument
	 *
	 * @return string
	 */
	private function getVueComponentPath( $argument ): string
	{
		return base_path( 'resources/js/components/' . str_replace( '\\', '/', $argument ) . '.vue' );
	}

	/**
	 * Создаем виды
	 *
	 * @throws FileNotFoundException
	 */
	private function createView()
	{
		$paths = $this->getViewPath( $this->argument( 'name' ) );
		foreach ( $paths as $path ) {
//			$view = Str::studly( class_basename( $this->argument( 'name' ) ) );

			if ( $this->alreadyExists( $path ) ) {
				$this->error( 'View already exists.' );
			} else {
				$this->makeDirectory( $path );
				$stub = $this->files->get( base_path( 'resources/stubs/view.stub' ) );
				$stub = str_replace(
					[
					],
					[
					],
					$stub
				);
				$this->files->put( $path, $stub );
				$this->info( 'View created successfully.' );
			}
		}
	}

	/**
	 * Получаем массив с путями к видам, которые будут располагаться в папке views
	 *
	 * @param $argument
	 *
	 * @return object
	 */
	private function getViewPath( $argument ): object
	{
		$arrFiles = collect( [
			'create',
			'edit',
			'index',
			'show',
		] );
		$paths    = $arrFiles->map( function ( $item ) use ( $argument ) {
			return base_path( 'resources/views/' . str_replace( '\\', '/', $argument ) . '/' . $item . '.blade.php' );
		} );

		return $paths;
	}
}
