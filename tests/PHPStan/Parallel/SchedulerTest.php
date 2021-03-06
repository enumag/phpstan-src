<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPUnit\Framework\TestCase;

class SchedulerTest extends TestCase
{

	public function dataSchedule(): array
	{
		return [
			[
				1,
				50,
				115,
				1,
				[50, 50, 15],
			],
			[
				16,
				30,
				124,
				5,
				[30, 30, 30, 30, 4],
			],
			[
				16,
				10,
				298,
				16,
				[10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 8],
			],
		];
	}

	/**
	 * @dataProvider dataSchedule
	 * @param int $cpuCores
	 * @param int $jobSize
	 * @param int $numberOfFiles
	 * @param int $expectedNumberOfProcesses
	 * @param array<int> $expectedJobSizes
	 */
	public function testSchedule(
		int $cpuCores,
		int $jobSize,
		int $numberOfFiles,
		int $expectedNumberOfProcesses,
		array $expectedJobSizes
	): void
	{
		$files = array_fill(0, $numberOfFiles, 'file.php');
		$scheduler = new Scheduler();
		$schedule = $scheduler->scheduleWork($cpuCores, $jobSize, $files);

		$this->assertSame($expectedNumberOfProcesses, $schedule->getNumberOfProcesses());
		$this->assertCount(count($expectedJobSizes), $schedule->getJobs());
		foreach ($expectedJobSizes as $i => $expectedJobSize) {
			$scheduledJob = $schedule->getJobs()[$i];
			$this->assertCount($expectedJobSize, $scheduledJob);
		}
	}

}
