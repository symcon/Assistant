<?php

declare(strict_types=1);

if (defined('PHPUNIT_TESTSUITE')) {
    trait Simulate
    {
        public function SimulateData(array $data): array
        {
            return $this->ProcessData($data);
        }

        public function getTime() : int
        {
            return 0;
        }
    }
} else {
    trait Simulate
    {
    }
}
