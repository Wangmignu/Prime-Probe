<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017, Phoronix Media
	Copyright (C) 2017, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class dump_tests_to_git implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option will create a Git repository of OpenBenchmarking.org test profiles.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('OpenBenchmarking.org Tests To Git');
		$path_to_git = getenv('TEST_PROFILES_GIT_PATH') . '/';
		if(!pts_client::executable_in_path('git'))
		{
			echo PHP_EOL . 'git was not found on the system.' . PHP_EOL . PHP_EOL;
			return false;
		}
		if(empty($path_to_git) || !is_dir($path_to_git) || !is_writable($path_to_git))
		{
			echo PHP_EOL . 'TEST_PROFILES_GIT_PATH must be set or the set directory is not writable/present.' . PHP_EOL . PHP_EOL;
			return false;
		}

		shell_exec('cd ' . $path_to_git . ' && git pull');

		foreach(pts_openbenchmarking::linked_repositories() as $repo)
		{
			if($repo == 'local')
			{
				// Skip local since it's a fake repository
				continue;
			}
			if(!is_dir($path_to_git . $repo))
			{
				pts_file_io::mkdir($path_to_git . $repo);
			}
			$repo_index = pts_openbenchmarking::read_repository_index($repo);
			$changes = pts_openbenchmarking_client::fetch_repository_changelog_full($repo);

			foreach($changes['tests'] as $test_identifier => $d)
			{
				foreach($d['changes'] as $test_version => $dd)
				{
					$test = $repo . '/' .  $test_identifier . '-' . $test_version; //  . ' ' . $dd['commit_description'] . ' ' . date('d F Y', $dd['last_updated']) . PHP_EOL;
					if(is_dir($path_to_git . $test))
						continue;

					pts_openbenchmarking::download_test_profile($test, $path_to_git);
					if(is_dir($path_to_git . $test))
					{
						$test_profile = new pts_test_profile($test);
						$maintainer = $test_profile->get_maintainer() != null ? $test_profile->get_maintainer() : 'Phoronix Test Suite';
						echo 'git commit -m "' . $test . ': ' . $dd['commit_description'] . '" --author="' . $maintainer . ' <no-reply@openbenchmarking.org>" --date="' . date(DATE_RFC2822, $dd['last_updated']) . '" ' . $test . PHP_EOL;
						shell_exec('cd ' . $path_to_git . ' && git add ' . $test . ' && git commit -m "' . $test . ': ' . $dd['commit_description'] . '" --author="' . $maintainer . ' <no-reply@openbenchmarking.org>" --date="' . date(DATE_RFC2822, $dd['last_updated']) . '" ' . $test);
					}
				}
			}
		}
	}
}

?>
