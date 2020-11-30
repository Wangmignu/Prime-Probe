<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017 - 2020, Phoronix Media
	Copyright (C) 2017 - 2020, Michael Larabel

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

class estimate_install_time implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option will provide estimates for test install/setup time length.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'identifier_to_object'))
		);
	}
	public static function run($args)
	{
		if($args[0] == 'pts/all' || empty($args))
		{
			$args = pts_openbenchmarking::available_tests(false);
		}

		$tests = array();
		$total_time = 0;
		$test_count = 0;
		foreach($args as $arg)
		{
			foreach(pts_types::identifiers_to_test_profile_objects($arg, false, true) as $t)
			{
				if($t->get_identifier() == null)
				{
					continue;
				}

				$tests[] = array($t->get_identifier(), pts_strings::format_time($t->get_estimated_install_time()));
				$total_time += $t->get_estimated_install_time();
				$test_count++;
			}
		}
		if($test_count > 1 && $total_time > 0)
		{
			echo pts_user_io::display_text_table($tests);
			echo PHP_EOL . PHP_EOL . pts_client::cli_just_bold('INSTALL TIME ESTIMATE: ') . pts_strings::format_time($total_time) . PHP_EOL . PHP_EOL;
		}
	}
}

?>
