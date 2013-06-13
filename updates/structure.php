<?php

$table = Db_Structure::table('social_providers');
	$table->primary_key('id');
	$table->column('code', db_varchar, 100)->index();
	$table->column('class_name', db_varchar, 100)->index();
	$table->column('config_data', db_text);
	$table->column('is_enabled', db_bool);

$table = Db_Structure::table('social_provider_users');
	$table->primary_key('id');
	$table->column('user_id', db_number)->index();
	$table->column('provider_code', db_varchar)->index();
	$table->column('provider_token', db_varchar);
	$table->column('is_enabled', db_bool);
