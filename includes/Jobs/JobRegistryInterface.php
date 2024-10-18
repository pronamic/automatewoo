<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Exceptions\InvalidClass;

/**
 * Interface JobRegistryInterface.
 *
 * @since 5.1.0
 */
interface JobRegistryInterface {

	/**
	 * Get a single registered job.
	 *
	 * @param string $name
	 *
	 * @return JobInterface
	 *
	 * @throws JobException If the job is not found.
	 * @throws InvalidClass|InvalidArgument When there is an invalid job class.
	 */
	public function get( string $name ): JobInterface;

	/**
	 * Get an array of all registered jobs.
	 *
	 * @return JobInterface[]
	 *
	 * @throws InvalidClass|InvalidArgument When there is an error loading jobs.
	 */
	public function list(): array;
}
