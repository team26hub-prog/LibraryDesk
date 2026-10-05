<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
	protected PDO $database;

	public function __construct(?PDO $database = null)
	{
		$this->database = $database ?? Database::connection();
	}
}
