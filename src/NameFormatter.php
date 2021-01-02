<?php

declare(strict_types=1);

namespace Baraja\SelectboxTree;


interface NameFormatter
{
	public function format(string $name): string;
}
