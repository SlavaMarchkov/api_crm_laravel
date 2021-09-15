<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
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
                                        {--api}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return int|void
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
	 * Creates Model at App\Modules\Name\Name\Models\Model\Model.php
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

	private function createController()
	{
	}

	private function createApiController()
	{
	}

	/**
	 * Creates Migration at App\Modules\Name\Name\Migrations\2014_10_12_100000_create_password_resets_table.php
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

	private function createVueComponent()
	{
	}

	private function createView()
	{
	}
}
