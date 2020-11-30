<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2020, Phoronix Media
	Copyright (C) 2010 - 2020, Michael Larabel

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

class pts_result_file_analyzer
{
	public static function condense_result_file_by_multi_option_tests(&$result_file)
	{
		$pmap = array();
		$do_proceed = false;
		foreach($result_file->get_result_objects() as $index => $ro)
		{
			if($ro->test_profile->get_identifier() == null || $ro->test_profile->get_display_format() != 'BAR_GRAPH')
			{
				continue;
			}
			$args = $ro->get_arguments_description();
			$scale = $ro->test_profile->get_result_scale();
			foreach(explode(' - ', $args) as $arg)
			{
				$args_without_current_arg = str_replace($arg, '', $args);
				if(!isset($pmap[$ro->test_profile->get_identifier()][$args_without_current_arg]))
				{
					$pmap[$ro->test_profile->get_identifier()][$args_without_current_arg] = array();
				}
				if(!isset($pmap[$ro->test_profile->get_identifier()][$args_without_current_arg][$scale]))
				{
					$pmap[$ro->test_profile->get_identifier()][$args_without_current_arg][$scale] = array();
				}
				$pmap[$ro->test_profile->get_identifier()][$args_without_current_arg][$scale][$arg] = $index;

				if(!$do_proceed && count($pmap[$ro->test_profile->get_identifier()][$args_without_current_arg][$scale]) > 1)
				{
					$do_proceed = true;
				}
			}
		}

		if($do_proceed)
		{
			$system_count = $result_file->get_system_count();
			$new_result_objects = array();
			$global_results_to_drop = array();
			foreach(array_keys($pmap) as $distinct_test)
			{
				foreach(array_keys($pmap[$distinct_test]) as $test_combo)
				{
					foreach(array_keys($pmap[$distinct_test][$test_combo]) as $scale)
					{
						$test_profile = new pts_test_profile($distinct_test);
						$test_result = new pts_test_result($test_profile);
						$test_result->test_profile->set_identifier(null);
						$test_result->test_profile->set_display_format('BAR_GRAPH');
						$test_result->test_profile->set_result_scale($scale);
						$test_result->set_used_arguments($test_combo);
						$test_result->test_result_buffer = new pts_test_result_buffer();
						$comparing = null;
						$results_to_drop = array();
						foreach($pmap[$distinct_test][$test_combo][$scale] as $arg => $ro_index)
						{
							if(strpos($arg, ': ') !== false)
							{
								list($comparing, $arg) = explode(': ', $arg);
							}
							if(($ro = $result_file->get_result_object_by_hash($ro_index)))
							{
								foreach($ro->test_result_buffer->get_buffer_items() as $old_bi)
								{
									$test_result->test_result_buffer->add_test_result(($system_count > 1 ? $old_bi->get_result_identifier() . ': ' : null) . $arg, $old_bi->get_result_value(), $old_bi->get_result_raw(), $old_bi->get_result_json_raw(), $old_bi->get_min_result_value(), $old_bi->get_max_result_value());
								}
								$results_to_drop[] = $ro_index;
							}
						}
						$tc = trim(trim(trim($test_combo), '-'));
						$test_result->set_used_arguments_description($comparing . ' Comparison' . ($tc ? ' (' . $tc . ')' : null));
						$test_result->test_profile->set_test_title($ro->test_profile->get_title());
						$test_result->test_profile->set_result_proportion($ro->test_profile->get_result_proportion());
						$test_result->test_profile->set_version($ro->test_profile->get_app_version());
						if($test_result->test_result_buffer->get_count() > 1)
						{
							$test_result->dynamically_generated = true;
							$new_result_objects[] = $test_result;
							$global_results_to_drop = array_merge($global_results_to_drop, $results_to_drop);
						}
					}
				}
			}
			if(count($new_result_objects) > 0)
			{
				$result_file->remove_result_object_by_id($global_results_to_drop);
				foreach($new_result_objects as $new_ro)
				{
					$result_file->add_result($new_ro);
				}
			}
		}
	}
	public static function condense_result_file_by_multi_version_tests(&$result_file)
	{
		$pmap = array();
		$do_proceed = false;
		foreach($result_file->get_result_objects() as $index => $ro)
		{
			if($ro->test_profile->get_identifier() == null || $ro->test_profile->get_display_format() != 'BAR_GRAPH')
			{
				continue;
			}
			$args = $ro->get_arguments_description();
			$scale = $ro->test_profile->get_result_scale();
			$test_identifier_no_version = $ro->test_profile->get_identifier(false);

			if(!isset($pmap[$test_identifier_no_version][$args]))
			{
				$pmap[$test_identifier_no_version][$args] = array();
			}
			if(!isset($pmap[$test_identifier_no_version][$args][$scale]))
			{
				$pmap[$test_identifier_no_version][$args][$scale] = array();
			}
			$pmap[$test_identifier_no_version][$args][$scale][$ro->test_profile->get_app_version()] = $index;

			if(!$do_proceed && count($pmap[$test_identifier_no_version][$args][$scale]) > 1)
			{
				$do_proceed = true;
			}
		}

		if($do_proceed)
		{
			$system_count = $result_file->get_system_count();
			$new_result_objects = array();
			$global_results_to_drop = array();
			foreach(array_keys($pmap) as $distinct_test)
			{
				foreach(array_keys($pmap[$distinct_test]) as $test_combo)
				{
					foreach(array_keys($pmap[$distinct_test][$test_combo]) as $scale)
					{
						$test_profile = new pts_test_profile($distinct_test);
						$test_result = new pts_test_result($test_profile);
						$test_result->test_profile->set_identifier(null);
						$test_result->test_profile->set_display_format('BAR_GRAPH');
						$test_result->test_profile->set_result_scale($scale);
						$test_result->set_used_arguments($test_combo);
						$test_result->test_result_buffer = new pts_test_result_buffer();
						$comparing = null;
						$results_to_drop = array();
						foreach($pmap[$distinct_test][$test_combo][$scale] as $version => $ro_index)
						{
							if(($ro = $result_file->get_result_object_by_hash($ro_index)))
							{
								foreach($ro->test_result_buffer->get_buffer_items() as $old_bi)
								{
									$test_result->test_result_buffer->add_test_result(($system_count > 1 ? $old_bi->get_result_identifier() . ': ' : null) . $version, $old_bi->get_result_value(), $old_bi->get_result_raw(), $old_bi->get_result_json_raw(), $old_bi->get_min_result_value(), $old_bi->get_max_result_value());
								}
								$results_to_drop[] = $ro_index;
							}
						}
						$tc = trim(trim(trim($test_combo), '-'));
						$test_result->set_used_arguments_description('Version Comparison' . ($tc ? ' (' . $tc . ')' : null));
						$test_result->test_profile->set_test_title($ro->test_profile->get_title());
						$test_result->test_profile->set_result_proportion($ro->test_profile->get_result_proportion());
						$test_result->test_profile->set_version('');
						if($test_result->test_result_buffer->get_count() > 1)
						{
							$test_result->dynamically_generated = true;
							$new_result_objects[] = $test_result;
							$global_results_to_drop = array_merge($global_results_to_drop, $results_to_drop);
						}
					}
				}
			}
			if(count($new_result_objects) > 0)
			{
				$result_file->remove_result_object_by_id($global_results_to_drop);
				foreach($new_result_objects as $new_ro)
				{
					$result_file->add_result($new_ro);
				}
			}
		}
	}
	public static function get_result_object_custom(&$result_file, $result_object, $identifier_mapping, $title_prepend = '', $unit = '')
	{
		if($result_object->test_profile->get_identifier() != null && $result_object->test_profile->get_display_format() == 'BAR_GRAPH')
		{
			$added_count = 0;
			$test_result = clone $result_object;
			$test_result->test_profile->set_identifier(null);
			$test_result->set_used_arguments_description($title_prepend . ($test_result->get_arguments_description() != null ? ' - ' . $test_result->get_arguments_description() : ''));
			$test_result->set_used_arguments($title_prepend . ' ' . $test_result->get_arguments());
			$test_result->test_result_buffer = new pts_test_result_buffer();

			foreach($identifier_mapping as $identifier => $value)
			{
				$result = $result_object->test_result_buffer->get_value_from_identifier($identifier);

				if($result_object->test_profile->get_result_proportion() == 'HIB')
				{
					$result = $result / $value;
					$result = round($result, ($result < 1 ? 4 : 2));
					$scale = $result_object->test_profile->get_result_scale() . ' Per ' . $unit;
				}
				else if($result_object->test_profile->get_result_proportion() == 'LIB')
				{
					$result = round($result * $value, 3);
					$scale = $result_object->test_profile->get_result_scale() . ' x ' . $unit;
				}

				if($result != 0)
				{
					if($title_prepend == 'Performance Per Clock')
					{
						$d = 'GHz base clock speed (use PTS sensors for real-time frequency/sensor reporting)';
					}
					else
					{
						$d = strtolower($unit);
					}
					$test_result->test_result_buffer->add_test_result($identifier, $result, null, array('install-footnote' => 'Detected ' . $d . ' count of ' . $value));
					$added_count++;
				}
			}

			if($added_count > 1)
			{
				$test_result->test_profile->set_result_scale($scale);
				return $test_result;
			}
		}

		return false;
	}
	public static function generate_perf_per_dollar(&$input, $generate, $unit = 'Dollar', $yield_on_unqualified_ros = false)
	{
		if($input instanceof pts_result_file)
		{
			$result_file = &$input;
			$ros = $input->get_result_objects();
		}
		else if($input instanceof pts_test_result)
		{
			$result_file = false;
			$ros = array($input);
		}

		foreach($ros as &$result_object)
		{
			if((!$yield_on_unqualified_ros && $result_object->test_profile->get_identifier() == null) || $result_object->test_profile->get_display_format() != 'BAR_GRAPH')
			{
				continue;
			}

			$computed = array();
			$footnotes = array();

			foreach($generate as $identifier => $value)
			{
				$result = $result_object->test_result_buffer->get_value_from_identifier($identifier);
				if(is_numeric($result) && $result > 0)
				{
					if($result_object->test_profile->get_result_proportion() == 'HIB')
					{
						$result = $result / $value;
						$result = pts_math::set_precision($result, ($result > 0.1 ? 3 : 8));
						$scale = $result_object->test_profile->get_result_scale() . ' Per ' . $unit;
					}
					else if($result_object->test_profile->get_result_proportion() == 'LIB')
					{
						$result = pts_math::set_precision($result * $value, 3);
						$scale = $result_object->test_profile->get_result_scale() . ' x ' . $unit;
					}
					else
					{
						continue;
					}

					if($result != 0)
					{
						$computed[$identifier] = $result;
						$footnotes[$identifier] = '$' . $value . ' reported cost.';
					}
				}
			}

			if(!empty($computed))
			{
				$ret = pts_result_file_analyzer::add_perf_per_graph($result_file, $result_object, $computed, $scale, $footnotes);
				if($result_file == false && $ret)
				{
					return $ret;
				}
			}
		}
	}
	public static function add_perf_per_graph(&$result_file, $test_result, $results, $scale, $footnote = null)
	{
		if(empty($results))
		{
			return false;
		}

		// This copy isn't needed but it's shorter and from port from system_monitor where there can be multiple items tracked
		$original_parent_hash = $test_result->get_comparison_hash(true, false);
		$test_result = clone $test_result;
		$test_result->test_profile->set_identifier(null);
		$test_result->set_used_arguments_description('Performance / Cost' . ($test_result->get_arguments_description() != null ? ' - ' . $test_result->get_arguments_description() : ''));
		$test_result->set_used_arguments('dollar comparison ' . $test_result->get_arguments());
		$test_result->test_profile->set_result_scale($scale);
		$test_result->test_result_buffer = new pts_test_result_buffer();
		foreach($results as $result_identifier => $result)
		{
			$test_result->test_result_buffer->add_test_result($result_identifier, $result, null, array('install-footnote' => (isset($footnote[$result_identifier]) ? $footnote[$result_identifier] : null)));
		}
		$test_result->set_parent_hash($original_parent_hash);

		if($result_file)
		{
			$result_file->add_result($test_result);
		}
		else
		{
			return $test_result;
		}
	}
	public static function generate_executive_summary($result_file, $selected_result = null,  &$error = null, $separator = PHP_EOL, $do_html = false)
	{
		$summary = array();

		if($result_file->get_test_count() < 6)
		{
			$error = 'Not enough tests to analyze...';
			return $summary;
		}
		if($result_file->get_system_count() < 2)
		{
			$error = 'Not enough results to analyze...';
			return $summary;
		}

		$wins_result = pts_result_file_analyzer::generate_wins_losses_results($result_file, true);
		$first_place_buffer = $wins_result->test_result_buffer->get_max_value(2);

		if($selected_result && ($sw = $wins_result->test_result_buffer->find_buffer_item($selected_result)))
		{
			if($do_html)
			{
				$selected_result = '<strong>' . $selected_result . '</strong>';
			}
			$summary[] = $selected_result . ' came in first place for ' . floor($sw->get_result_value() / $wins_result->test_result_buffer->get_total_value_sum() * 100) . '% of the tests.';
		}
		else if($first_place_buffer->get_result_identifier() != $selected_result)
		{
			// Most wins
			$selected_result = $first_place_buffer->get_result_identifier();
			if($do_html)
			{
				$selected_result = '<strong>' . $selected_result . '</strong>';
			}
			$summary[] = $first_place_buffer->get_result_identifier() . ' had the most wins, coming in first place for ' . floor($first_place_buffer->get_result_value() / $wins_result->test_result_buffer->get_total_value_sum() * 100) . '% of the tests.';
		}

		$geo_mean_result = pts_result_file_analyzer::generate_geometric_mean_result($result_file, true);
		$first_place_buffer = $geo_mean_result->test_result_buffer->get_max_value(2);
		$last_place_buffer = $geo_mean_result->test_result_buffer->get_min_value(2);

		$geo_bits = array();
		if($result_file->get_system_count() >= 3)
		{
			$prev_buffer = null;
			foreach($geo_mean_result->test_result_buffer->get_buffer_items() as $bi)
			{
				if($prev_buffer == null)
				{
					$prev_buffer = $bi;
					continue;
				}
				$rounded = round($bi->get_result_value() / $prev_buffer->get_result_value(), 3) . 'x';
				if($rounded === '1.000x')
				{
					continue;
				}
				$geo_bits[] = $bi->get_result_identifier() . ' was ' . $rounded . ' the speed of ' . $prev_buffer->get_result_identifier();
				$prev_buffer = $bi;
			}
		}
		switch(count($geo_bits))
		{
			case 0:
				$geo_bits = null;
				break;
			case 1:
				$geo_bits = array_pop($geo_bits) . '.';
				break;
			case 2:
				$geo_bits = implode(' and ', $geo_bits) . '.';
				break;
			default:
				if(count($geo_bits) > 10)
				{
					$geo_bits = null;
					break;
				}
				$geo_bits = implode(', ', $geo_bits) . '.';
				break;
		}

		$summary[] = trim('Based on the geometric mean of all complete results, the fastest (' . $first_place_buffer->get_result_identifier() . ') was ' . round($first_place_buffer->get_result_value() / $last_place_buffer->get_result_value(), 3) . 'x the speed of the slowest (' . $last_place_buffer->get_result_identifier() . '). ' . $geo_bits);

		if($result_file->get_test_count() > 16)
		{
			$results = $result_file->get_result_objects();
			$spreads = array();
			foreach($results as $i => &$result_object)
			{
				$spreads[$i] = $result_object->get_spread();
			}
			arsort($spreads);
			$spreads = array_slice($spreads, 0, 10, true);

			if(!empty($spreads))
			{
				$spread_text = array();
				foreach($spreads as $result_key => $spread)
				{
					$ro = $result_file->get_result_objects($result_key);
					if(!is_object($ro[0]))
					{
						continue;
					}
					if($do_html)
					{
						$spread_text[] = '<strong>' . $ro[0]->test_profile->get_title() . '</strong>' . ($ro[0]->get_arguments_description() != null ? ' (<em>' . $ro[0]->get_arguments_description() . '</em>)' : null) . ' at ' . round($spread, 3) . 'x';
					}
					else
					{
						$spread_text[] = $ro[0]->test_profile->get_title() . ($ro[0]->get_arguments_description() != null ? ' (' . $ro[0]->get_arguments_description() . ')' : null) . ' at ' . round($spread, 3) . 'x';
					}
				}
				if(!empty($spread_text))
				{
					$summary[] = 'The results with the greatest spread from best to worst included: ' . PHP_EOL . PHP_EOL . implode($separator, $spread_text) . '.';
				}
			}
		}

		return $summary;
	}
	public static function generate_wins_losses_results($result_file, $only_wins = false)
	{
		$results = null;
		$result_file_identifiers_count = $result_file->get_system_count();
		$wins = array();
		$losses = array();
		$tests_counted = 0;

		$possible_evaluate_result_count = 0;
		foreach($result_file->get_result_objects() as $result)
		{
			if($result->test_profile->get_identifier() == null)
			{
				continue;
			}
			$possible_evaluate_result_count++;
			if($result->test_result_buffer->get_count() < 2 || $result->test_result_buffer->get_count() < floor($result_file_identifiers_count / 2))
			{
				continue;
			}

			$tests_counted++;
			$winner = $result->get_result_first();
			$loser = $result->get_result_last();

			if(!isset($wins[$winner]))
			{
				$wins[$winner] = 1;
			}
			else
			{
				$wins[$winner]++;
			}

			if(!isset($losses[$loser]))
			{
				$losses[$loser] = 1;
			}
			else
			{
				$losses[$loser]++;
			}
		}

		if(empty($wins) || empty($losses))
		{
			return;
		}

		arsort($wins);
		arsort($losses);

		$test_profile = new pts_test_profile();
		$test_result = new pts_test_result($test_profile);
		$test_result->test_profile->set_test_title('Number Of First Place Finishes');
		$test_result->test_profile->set_identifier(null);
		$test_result->test_profile->set_version(null);
		$test_result->test_profile->set_result_proportion(null);
		$test_result->test_profile->set_display_format('PIE_CHART');
		$test_result->test_profile->set_result_scale('Wins');
		///$test_result->test_profile->set_result_proportion('HIB');
		$test_result->set_used_arguments_description('Wins - ' . $tests_counted . ' Tests');
		//$test_result->set_used_arguments('Geometric-Mean');
		$test_result->test_result_buffer = new pts_test_result_buffer();
		$test_result->dynamically_generated = true;

		foreach($wins as $identifier => $count)
		{
			$test_result->test_result_buffer->add_test_result($identifier, $count);
		}

		if($only_wins)
		{
			return count($wins) > 1 ? $test_result : null;
		}
		if(count($wins) > 1)
		{
			$results[] = $test_result;
		}

		$test_profile = new pts_test_profile();
		$test_result = new pts_test_result($test_profile);
		$test_result->test_profile->set_test_title('Number Of Last Place Finishes');
		$test_result->test_profile->set_identifier(null);
		$test_result->test_profile->set_version(null);
		$test_result->test_profile->set_result_proportion(null);
		$test_result->test_profile->set_display_format('PIE_CHART');
		$test_result->test_profile->set_result_scale('Losses');
		///$test_result->test_profile->set_result_proportion('HIB');
		$test_result->set_used_arguments_description('Losses - ' . $tests_counted . ' Tests');
		//$test_result->set_used_arguments('Geometric-Mean');
		$test_result->test_result_buffer = new pts_test_result_buffer();
		$test_result->dynamically_generated = true;

		foreach($losses as $identifier => $count)
		{
			$test_result->test_result_buffer->add_test_result($identifier, $count);
		}

		if(count($losses) > 1)
		{
			$results[] = $test_result;
		}

		return $results;
	}
	public static function generate_geometric_mean_result_for_suites_in_result_file(&$result_file, $allow_partial = true, $upper_limit = 0)
	{
		if($result_file->get_system_count() < 2)
		{
			return array();
		}

		$geo_mean_results = array();
		$suites_in_result_file = pts_test_suites::suites_in_result_file($result_file, $allow_partial, $upper_limit);
		if(empty($suites_in_result_file))
		{
			return array();
		}

		ksort($suites_in_result_file);
		foreach($suites_in_result_file as $suite_identifier => &$s)
		{
			list($suite, $contained_tests) = $s;
			$geo_mean = pts_result_file_analyzer::generate_geometric_mean_result($result_file, true, $contained_tests);
			if($geo_mean)
			{
				$geo_mean->test_profile->set_test_title('Geometric Mean Of ' . $suite->get_title() . (stripos($suite->get_title(), 'Test') === false ? ' Tests' : null));
				$geo_mean->normalize_buffer_values();
				$geo_mean->set_annotation('Geometric mean based upon tests: ' . pts_arrays::implode_list($contained_tests));
				$geo_mean_results[] = $geo_mean;
			}
		}

		return $geo_mean_results;
	}
	public static function generate_geometric_mean_result($result_file, $do_sort = false, $limit_to = false, $best_is_last = false)
	{
		$results = array();
		$system_count = $result_file->get_system_count();
		foreach($result_file->get_result_objects() as $result)
		{
			if($result->test_profile->get_identifier() == null || $result->test_profile->get_display_format() != 'BAR_GRAPH' || $system_count > $result->test_result_buffer->get_count())
			{
				// Skip data where it's not a proper test, not a singular data value, or not all systems ran within the result file
				continue;
			}
			if($limit_to)
			{
				if(is_array($limit_to))
				{
					if(!in_array($result->test_profile->get_identifier(), $limit_to) && !in_array($result->test_profile->get_identifier(false), $limit_to))
					{
						continue;
					}
				}
				else
				{
					if($limit_to != $result->test_profile->get_identifier())
					{
						continue;
					}
				}
			}

			foreach($result->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$r = $buffer_item->get_result_value();
				if(!is_numeric($r) || $r == 0)
				{
					continue;
				}
				if($result->test_profile->get_result_proportion() == 'LIB')
				{
					// convert to HIB
					$r = (1 / $r) * 100;
				}

				$ri = $buffer_item->get_result_identifier();

				if(!isset($results[$ri]))
				{
					$results[$ri] = array();
				}
				$results[$ri][] = $r;
			}
		}

		foreach($results as $identifier => $values)
		{
			if(count($values) < 2)
			{
				// If small result file with not a lot of data, don't bother showing...
				unset($results[$identifier]);
			}
		}

		if(!empty($results))
		{
			$test_profile = new pts_test_profile();
			$test_result = new pts_test_result($test_profile);
			$test_result->test_profile->set_test_title('Geometric Mean Of ' . ($limit_to && !is_array($limit_to) ? $limit_to : 'All Test Results'));
			$test_result->test_profile->set_identifier(null);
			$test_result->test_profile->set_version(null);
			$test_result->test_profile->set_result_proportion(null);
			$test_result->test_profile->set_display_format('BAR_GRAPH');
			$test_result->test_profile->set_result_scale('Geometric Mean');
			$test_result->test_profile->set_result_proportion('HIB');
			$test_result->set_used_arguments_description('Result Composite');
			$test_result->set_used_arguments('Geometric-Mean');
			$test_result->test_result_buffer = new pts_test_result_buffer();
			foreach($results as $identifier => $values)
			{
				if(count($values) < 2)
				{
					return false;
				}
				$values = pts_math::geometric_mean($values);
				$test_result->test_result_buffer->add_test_result($identifier, pts_math::set_precision($values, 3));
			}

			if((!$result_file->is_multi_way_comparison() && !$test_result->test_result_buffer->result_identifier_differences_only_numeric()) || $do_sort)
			{
				$test_result->sort_results_by_performance();
				if($best_is_last)
				{
					$test_result->test_result_buffer->buffer_values_reverse();
				}
			}
			$test_result->dynamically_generated = true;
			return $test_result;
		}

		return false;
	}
	public static function generate_geometric_mean_result_per_test($result_file, $do_sort = false, $selector = null, $best_is_last = false)
	{
		$geo_results = array();
		$results = array();
		$system_count = $result_file->get_system_count();
		foreach($result_file->get_result_objects() as $result)
		{
			if(($selector == null && $result->test_profile->get_identifier() == null) || $result->test_profile->get_display_format() != 'BAR_GRAPH' || $system_count > $result->test_result_buffer->get_count())
			{
				// Skip data where it's not a proper test, not a singular data value, or not all systems ran within the result file
				continue;
			}
			if($selector != null && strpos($result->get_arguments_description(), $selector) === false && strpos($result->test_profile->get_title(), $selector) === false && strpos($result->test_profile->get_result_scale(), $selector) === false)
			{
				continue;
			}

			foreach($result->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$r = $buffer_item->get_result_value();
				if(!is_numeric($r) || $r == 0)
				{
					continue;
				}
				if($result->test_profile->get_result_proportion() == 'LIB')
				{
					// convert to HIB
					$r = (1 / $r) * 100;
				}

				$ri = $buffer_item->get_result_identifier();

				if(!isset($results[$result->test_profile->get_title()]))
				{
					$results[$result->test_profile->get_title()] = array();
				}
				if(!isset($results[$result->test_profile->get_title()][$ri]))
				{
					$results[$result->test_profile->get_title()][$ri] = array();
				}
				$results[$result->test_profile->get_title()][$ri][] = $r;
			}
		}

		if(count($results) < 2)
		{
			return array();
		}

		foreach($results as $test => $test_results)
		{
			foreach($test_results as $identifier => $values)
			{
				if(false && count($values) < 4)
				{
					// If small result file with not a lot of data, don't bother showing...
					unset($results[$test][$identifier]);
				}
			}

			if(empty($results[$test]))
			{
				unset($results[$test]);
			}
		}

		foreach($results as $test_title => $test_results)
		{
			$test_profile = new pts_test_profile();
			$test_result = new pts_test_result($test_profile);
			$test_result->test_profile->set_test_title($test_title);
			$test_result->test_profile->set_identifier(null);
			$test_result->test_profile->set_version(null);
			$test_result->test_profile->set_result_proportion(null);
			$test_result->test_profile->set_display_format('BAR_GRAPH');
			$test_result->test_profile->set_result_scale('Geometric Mean');
			$test_result->test_profile->set_result_proportion('HIB');
			$test_result->set_used_arguments_description(($selector ? $selector . ' ' : null) . 'Geometric Mean');
			$test_result->set_used_arguments('Geometric-Mean');
			$test_result->test_result_buffer = new pts_test_result_buffer();
			foreach($test_results as $identifier => $values)
			{
				$values = pts_math::geometric_mean($values);
				$precised = pts_math::set_precision($values, 3);
				if($values != 0 && $precised == 0)
				{
					// Don't use precision if it ends up rounding result too small in certain situations
					$precised = $values;
				}
				$test_result->test_result_buffer->add_test_result($identifier, $precised);
			}

			if(!$result_file->is_multi_way_comparison() || $do_sort)
			{
				$test_result->sort_results_by_performance();
				if($best_is_last)
				{
					$test_result->test_result_buffer->buffer_values_reverse();
				}
			}
			$test_result->dynamically_generated = true;
			$geo_results[] = $test_result;
		}

		return $geo_results;
	}
	public static function generate_harmonic_mean_result($result_file, $do_sort = false, $best_is_last = false)
	{
		$results = array();
		$system_count = $result_file->get_system_count();
		foreach($result_file->get_result_objects() as $result)
		{
			if($result->test_profile->get_identifier() == null || $result->test_profile->get_display_format() != 'BAR_GRAPH' || $result->test_profile->get_result_proportion() == 'LIB' || $system_count > $result->test_result_buffer->get_count())
			{
				// Skip data where it's not a proper test, not a singular data value, or not all systems ran within the result file, or lower is better for results
				continue;
			}
			$rs = $result->test_profile->get_result_scale();
			if(strpos($rs, '/') === false && stripos($rs, ' per ') === false && stripos($rs, 'FPS') === false && stripos($rs, 'bps') === false && stripos($rs, 'iops') === false)
			{
				// Harmonic mean is relevant for tests of rates, MB/s, FPS, ns/day, etc.
				continue;
			}
			foreach($result->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$ri = $buffer_item->get_result_identifier();

				if(!isset($results[$rs][$ri]))
				{
					$results[$rs][$ri] = array();
				}
				$results[$rs][$ri][] = $buffer_item->get_result_value();
			}
		}

		foreach($results as $result_scale => $group)
		{
			foreach($group as $identifier => $values)
			{
				if(count($values) < 4)
				{
					// If small result file with not a lot of data, don't bother showing...
					unset($results[$result_scale][$identifier]);
				}
			}
		}

		if(!empty($results))
		{
			$test_results = array();
			foreach($results as $result_scale => $group)
			{
				$parsed = array();
				foreach($group as $identifier => $values)
				{
					$parsed[$identifier] = pts_math::harmonic_mean($values);
				}
				if(empty($parsed) || count($parsed) < 2)
				{
					continue;
				}

				$test_profile = new pts_test_profile();
				$test_result = new pts_test_result($test_profile);
				$test_result->test_profile->set_test_title('Harmonic Mean Of ' . $result_scale . ' Test Results');
				$test_result->test_profile->set_identifier(null);
				$test_result->test_profile->set_version(null);
				$test_result->test_profile->set_result_proportion(null);
				$test_result->test_profile->set_display_format('BAR_GRAPH');
				$test_result->test_profile->set_result_scale($result_scale);
				$test_result->test_profile->set_result_proportion('HIB');
				$test_result->set_used_arguments_description('Harmonic Mean');
				$test_result->set_used_arguments('Harmonic-Mean - ' . $result_scale);
				$test_result->test_result_buffer = new pts_test_result_buffer();
				foreach($parsed as $identifier => $values)
				{
					$test_result->test_result_buffer->add_test_result($identifier, pts_math::set_precision($values, 3));
				}
				if(!$result_file->is_multi_way_comparison() || $do_sort)
				{
					$test_result->sort_results_by_performance();
					if($best_is_last)
					{
						$test_result->test_result_buffer->buffer_values_reverse();
					}
				}
				$test_result->dynamically_generated = true;
				$test_results[] = $test_result;
			}
			return $test_results;
		}

		return array();
	}
	public static function display_result_file_stats_pythagorean_means($result_file, $highlight_identifier = null)
	{
		$ret = null;
		foreach(pts_result_file_analyzer::generate_harmonic_mean_result($result_file, true, true) as $harmonic_mean_result)
		{
			$ret .= pts_result_file_output::test_result_to_text($harmonic_mean_result, pts_client::terminal_width(), true, $highlight_identifier, true) . PHP_EOL;
		}

		$geometric_mean = pts_result_file_analyzer::generate_geometric_mean_result($result_file, true, false, true);
		if($geometric_mean)
		{
			$ret .= pts_result_file_output::test_result_to_text($geometric_mean, pts_client::terminal_width(), true, $highlight_identifier, true);
		}

		if($ret != null)
		{
			$ret .= PHP_EOL;
		}

		return $ret;
	}
	public static function display_results_wins_losses($result_file, $highlight_result_identifier = null, $prepend_lines = '   ')
	{
		$output = null;
		$result_file_identifiers_count = $result_file->get_system_count();
		$wins = array();
		$losses = array();
		$tests_counted = 0;

		$possible_evaluate_result_count = 0;
		foreach($result_file->get_result_objects() as $result)
		{
			if($result->test_profile->get_identifier() == null)
			{
				continue;
			}
			$possible_evaluate_result_count++;
			if($result->test_result_buffer->get_count() < 2 || $result->test_result_buffer->get_count() < floor($result_file_identifiers_count / 2))
			{
				continue;
			}

			$tests_counted++;
			$winner = $result->get_result_first();
			$loser = $result->get_result_last();

			if(!isset($wins[$winner]))
			{
				$wins[$winner] = 1;
			}
			else
			{
				$wins[$winner]++;
			}

			if(!isset($losses[$loser]))
			{
				$losses[$loser] = 1;
			}
			else
			{
				$losses[$loser]++;
			}
		}

		if(empty($wins) || empty($losses))
		{
			return;
		}

		arsort($wins);
		arsort($losses);

		$table = array();
		$table[] = array(pts_client::cli_colored_text('WINS:', 'green', true), '', '');
		$highlight_row = -1;
		foreach($wins as $identifier => $count)
		{
			$table[] = array($identifier . ': ', $count . ' ', ' [' . pts_math::set_precision($count / $tests_counted * 100, 1) . '%]');

			if($highlight_result_identifier && $highlight_result_identifier == $identifier)
			{
				$highlight_row = count($table) - 1;
			}
		}
		$table[] = array('', '', '');
		$table[] = array(pts_client::cli_colored_text('LOSSES: ', 'red', true), '', '');
		$highlight_row = -1;
		foreach($losses as $identifier => $count)
		{
			$table[] = array($identifier . ': ', $count, ' [' . pts_math::set_precision($count / $tests_counted * 100, 1) . '%]');

			if($highlight_result_identifier && $highlight_result_identifier == $identifier)
			{
				$highlight_row = count($table) - 1;
			}
		}
		$output .= pts_user_io::display_text_table($table, $prepend_lines, 0, 0, false, $highlight_row) . PHP_EOL;
		$output .= $prepend_lines . pts_client::cli_colored_text('TESTS COUNTED: ', 'cyan', true) . ($tests_counted == $possible_evaluate_result_count ? $tests_counted : $tests_counted . ' of ' . $possible_evaluate_result_count) .  PHP_EOL;
		return $output;
	}
	public static function display_results_baseline_two_way_compare($result_file, $drop_flat_results = false, $border_table = false, $rich_text = false, $prepend_to_lines = null)
	{
		$table = array(array('Test', 'Configuration', 'Relative'));
		$color_rows = array();

		foreach($result_file->get_result_objects() as $ro)
		{
			if($ro->test_profile->get_display_format() != 'BAR_GRAPH')
			{
				continue;
			}
			$analyze_ro = clone $ro;
			if($drop_flat_results)
			{
				$analyze_ro->remove_unchanged_results(0.3);
			}

			$buffer_identifiers = $analyze_ro->test_result_buffer->get_identifiers();
			if(count($buffer_identifiers) != 2)
			{
				continue;
			}

			$analyze_ro->normalize_buffer_values(pts_arrays::first_element($buffer_identifiers));
			$result = $analyze_ro->test_result_buffer->get_value_from_identifier(pts_arrays::last_element($buffer_identifiers));
			if(empty($result))
			{
				continue;
			}
			$result = round($result, 3);
			if($drop_flat_results && $result == 1)
			{
				continue;
			}
			if($rich_text && ($result < 0.97 || $result > 1.03))
			{
				$color_rows[count($table)] = $result < 1 ? 'red' : 'green';
			}
			$table[] = array($analyze_ro->test_profile->get_identifier_base_name(), $analyze_ro->get_arguments_description_shortened(), $result);
		}

		$bold_row = $rich_text ? 0 : -1;
		return count($table) < 2 ? null : PHP_EOL . pts_user_io::display_text_table($table, $prepend_to_lines, 0, 0, $border_table, $bold_row, $color_rows);
	}
	public static function analyze_result_file_intent(&$result_file, &$flagged_results = -1, $return_all_changed_indexes = false)
	{
		$identifiers = array();
		$hw = array();
		$sw = array();
		foreach($result_file->get_systems() as $system)
		{
			$identifiers[] = $system->get_identifier();
			$hw[] = $system->get_hardware();
			$sw[] = $system->get_software();
		}

		if(count($identifiers) < 2)
		{
			// Not enough tests to be valid for anything
			return false;
		}

		foreach($identifiers as $identifier)
		{
			if(pts_strings::string_only_contains($identifier, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH | pts_strings::CHAR_SPACE))
			{
				// All the identifiers are just dates or other junk
				return false;
			}
		}

		$hw_unique = array_unique($hw);
		$sw_unique = array_unique($sw);
		$desc = false;

		if(count($hw_unique) == 1 && count($sw_unique) == 1)
		{
			// The hardware and software is maintained throughout the testing, so if there's a change in results its something we aren't monitoring
			// TODO XXX: Not sure this below check is needed anymore...
			if(true || (count($hw) > 2 && $result_file->get_test_count() != count($hw)))
			{
				$desc = array('Unknown', implode(', ', $identifiers));
			}
		}
		else if(count($sw_unique) == 1)
		{
			// The software is being maintained, but the hardware is being flipped out
			$rows = array();
			$data = array();
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $hw);
			pts_result_file_analyzer::compact_result_table_data($data, $identifiers, true);
			$desc = pts_result_file_analyzer::analyze_system_component_changes($data, $rows, array(
				array('Processor', 'Motherboard', 'Chipset', 'Audio', 'Network'), // Processor comparison
				array('Processor', 'Motherboard', 'Chipset', 'Network'), // Processor comparison
				array('Processor', 'Chipset', 'Graphics'),
				array('Processor', 'Graphics'),
				array('Processor', 'Chipset'), // Processor comparison - Sandy/Ivy Bridge for Intel will change CPU/chipset reporting when still using same mobo
				array('Motherboard', 'Chipset'), // Motherboard comparison
				array('Motherboard', 'Chipset', 'Audio', 'Network'), // Also a potential motherboard comparison
				array('Graphics', 'Audio'), // GPU comparison
				array('Graphics'),
				), $return_all_changed_indexes);
		}
		else if(count($hw_unique) == 1)
		{
			// The hardware is being maintained, but the software is being flipped out
			$rows = array();
			$data = array();
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $sw);
			pts_result_file_analyzer::compact_result_table_data($data, $identifiers, true);
			$desc = pts_result_file_analyzer::analyze_system_component_changes($data, $rows, array(
				array('Display Driver', 'OpenGL'), array('OpenGL'), array('Display Driver') // Graphics driver comparisons
				), $return_all_changed_indexes);
		}
		else
		{
			// Both software and hardware are being flipped out
			$rows = array();
			$data = array();
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $hw);
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $sw);
			pts_result_file_analyzer::compact_result_table_data($data, $identifiers, true);
			$desc = pts_result_file_analyzer::analyze_system_component_changes($data, $rows, array(
				array('Memory', 'Graphics', 'Display Driver', 'OpenGL'),
				array('Graphics', 'Display Driver', 'OpenGL', 'Vulkan'), array('Graphics', 'Kernel', 'Display Driver', 'OpenGL', 'Vulkan'), array('Graphics', 'Display Driver', 'OpenGL', 'OpenCL', 'Vulkan'), array('Graphics', 'Display Driver', 'OpenCL'), array('Graphics', 'Monitor', 'Kernel', 'Display Driver', 'OpenGL'), array('Graphics', 'Monitor', 'Display Driver', 'OpenGL'), array('Graphics', 'Kernel', 'Display Driver', 'OpenGL'), array('Graphics', 'Display Driver', 'OpenGL'), array('Graphics', 'OpenGL'), array('Graphics', 'Kernel'), array('Graphics', 'Display Driver') // All potential graphics comparisons
			), $return_all_changed_indexes);
		}

		if($desc)
		{
			if($flagged_results === -1)
			{
				return $desc;
			}
			else
			{
				$mark_results = self::locate_interesting_results($result_file, $flagged_results);
				return array($desc[0], $desc[1], $mark_results);
			}
		}

		return false;
	}
	public static function locate_interesting_results(&$result_file, &$flagged_results = null)
	{
		$result_objects = array();

		if(!is_array($flagged_results))
		{
			$flagged_results = array();
			$system_id_keys = null;
			$result_object_index = -1;
			pts_ResultFileTable::result_file_to_result_table($result_file, $system_id_keys, $result_object_index, $flagged_results);
		}

		if(count($flagged_results) > 0)
		{
			asort($flagged_results);
			$flagged_results = array_slice(array_keys($flagged_results), -6);
			$flag_delta_objects = $result_file->get_result_objects($flagged_results);

			for($i = 0; $i < count($flagged_results); $i++)
			{
				$result_objects[$flagged_results[$i]] = $flag_delta_objects[$i];
				unset($flag_delta_objects[$i]);
			}
		}

		return $result_objects;
	}
	public static function analyze_system_component_changes($data, $rows, $supported_combos = array(), $return_all_changed_indexes = false)
	{
		$max_combo_count = 2;
		foreach($supported_combos as $combo)
		{
			if(($c = count($combo)) > $max_combo_count)
			{
				$max_combo_count = $c;
			}
		}

		$total_width = count($data);
		$first_objects = array_shift($data);
		$comparison_good = true;
		$comparison_objects = array();

		foreach($first_objects as $i => $o)
		{
			if($o->get_attribute('spans_col') == $total_width)
			{
				unset($first_objects[$i]);
			}
		}

		if(count($first_objects) <= $max_combo_count && count($first_objects) > 0)
		{
			$changed_indexes = array_keys($first_objects);
			$comparison_objects[] = ($return_all_changed_indexes ? array_map('strval', $first_objects) : implode('/', $first_objects));

			if(count($changed_indexes) <= $max_combo_count)
			{
				while($comparison_good && ($this_identifier = array_shift($data)) !== null)
				{
					if(empty($this_identifier))
					{
						continue;
					}

					$this_keys = array_keys($this_identifier);
					$do_push = false;

					if($this_keys != $changed_indexes)
					{
						foreach($this_keys as &$change)
						{
							$change = $rows[$change];
						}

						if(!in_array($this_keys, $supported_combos) && (count($this_keys) > 1 || array_search($this_keys[0], $supported_combos[0]) === false))
						{
							$comparison_good = false;
						}
						else
						{
							$do_push = true;
						}
					}
					else
					{
						$do_push = true;
					}

					if($do_push)
					{
						$comparison_objects[] = ($return_all_changed_indexes ? array_map('strval', $this_identifier) : implode('/', $this_identifier));
					}
				}
			}
			else
			{
				$comparison_good = false;
			}

			if($comparison_good)
			{
				$new_index = array();
				foreach($changed_indexes as &$change)
				{
					$new_index[$change] = $rows[$change];
				}
				$changed_indexes = $new_index;

				if(count($changed_indexes) == 1 || in_array(array_values($changed_indexes), $supported_combos))
				{
					if($return_all_changed_indexes == false)
					{
						$comparison_objects = implode(', ', $comparison_objects);
					}

					return array(($return_all_changed_indexes ? $changed_indexes : array_shift($changed_indexes)), $comparison_objects);
				}
			}
		}

		return false;
	}
	public static function system_components_to_table(&$table_data, &$columns, &$rows, $add_components)
	{
		$col_pos = 0;

		foreach($add_components as $info_string)
		{
			if(isset($columns[$col_pos]))
			{
				if(!isset($table_data[$columns[$col_pos]]))
				{
					$table_data[$columns[$col_pos]] = array();
				}

				foreach(explode(', ', $info_string) as $component)
				{
					$c_pos = strpos($component, ': ');

					if($c_pos !== false)
					{
						$index = substr($component, 0, $c_pos);
						$value = substr($component, ($c_pos + 2));

						if(($r_i = array_search($index, $rows)) === false)
						{
							$rows[] = $index;
							$r_i = count($rows) - 1;
						}
						$table_data[$columns[$col_pos]][$r_i] = self::system_value_to_ir_value($value, $index);
					}
				}
			}
			$col_pos++;
		}
	}
	public static function system_component_string_to_array($components, $do_check = false)
	{
		$component_r = array();
		$components = explode(', ', $components);

		foreach($components as &$component)
		{
			$component = explode(': ', $component);

			if(count($component) >= 2 && ($do_check == false || in_array($component[0], $do_check)))
			{
				$component_r[$component[0]] = $component[1];
			}
		}

		return $component_r;
	}
	public static function system_component_string_to_html($components)
	{
		$components = self::system_component_string_to_array($components);

		foreach($components as $type => &$component)
		{
			$component = self::system_value_to_ir_value($component, $type);
			$type = '<strong>' . $type . '</strong>';

			if(($href = $component->get_attribute('href')) != false)
			{
				$component = '<a href="' . $href . '">' . $component->get_value() . '</a>';
			}
			else
			{
				$component = $component->get_value();
			}

			$component = $type . ': ' . $component;
		}

		return implode(', ', $components);
	}
	public static function system_value_to_ir_value($value, $index)
	{
		// TODO XXX: Move this logic off to OpenBenchmarking.org script
		/*
		!in_array($index, array('Memory', 'System Memory', 'Desktop', 'Screen Resolution', 'System Layer')) &&
			$search_break_characters = array('@', '(', '/', '+', '[', '<', '*', '"');
			for($i = 0, $x = strlen($value); $i < $x; $i++)
			{
				if(in_array($value[$i], $search_break_characters))
				{
					$value = substr($value, 0, $i);
					break;
				}
			}
		*/
		$ir = new pts_graph_ir_value($value);

		if($value != 'Unknown' && $value != null)
		{
			$ir->set_attribute('href', 'http://openbenchmarking.org/s/' . $value);
		}

		return $ir;
	}
	public static function compact_result_table_data(&$table_data, &$columns, $unset_emptied_values = false)
	{
		// Let's try to compact the data
		$c_count = count($table_data);
		$c_index = 0;

		foreach(array_keys($table_data) as $c)
		{
			foreach(array_keys($table_data[$c]) as $r)
			{
				// Find next-to duplicates
				$match_to = &$table_data[$c][$r];

				if(($match_to instanceof pts_graph_ir_value) == false)
				{
					if($unset_emptied_values)
					{
						unset($table_data[$c][$r]);
					}

					continue;
				}

				$spans = 1;
				for($i = ($c_index + 1); $i < $c_count; $i++)
				{
					$id = $columns[$i];

					if(isset($table_data[$id][$r]) && $match_to == $table_data[$id][$r])
					{
						$spans++;

						if($unset_emptied_values)
						{
							unset($table_data[$id][$r]);
						}
						else
						{
							$table_data[$id][$r] = null;
						}
					}
					else
					{
						break;
					}
				}

				if($spans > 1)
				{
					$match_to->set_attribute('spans_col', $spans);
					$match_to->set_attribute('highlight', $spans < count($columns));
				}
			}

			$c_index++;
		}
	}
	public static function system_to_note_array(&$result_file_system, &$system_attributes)
	{
		$json = $result_file_system->get_json();
		$notes_string = $result_file_system->get_notes();
		$identifier = $result_file_system->get_identifier();

		if(isset($json['kernel-parameters']) && $json['kernel-parameters'] != null)
		{
			$system_attributes['Kernel'][$identifier] = $json['kernel-parameters'];
			unset($json['kernel-parameters']);
		}
		if(isset($json['environment-variables']) && $json['environment-variables'] != null)
		{
			$system_attributes['Environment'][$identifier] = $json['environment-variables'];
			unset($json['environment-variables']);
		}
		if(isset($json['compiler-configuration']) && $json['compiler-configuration'] != null)
		{
			$system_attributes['Compiler'][$identifier] = $json['compiler-configuration'];
			unset($json['compiler-configuration']);
		}
		if(isset($json['disk-scheduler']) && isset($json['disk-mount-options']))
		{
			$system_attributes['Disk'][$identifier] = $json['disk-scheduler'] . ' / ' . $json['disk-mount-options'];
			if(isset($json['disk-details']) && !empty($json['disk-details']))
			{
				$system_attributes['Disk'][$identifier] .= ' / ' . $json['disk-details'];
				unset($json['disk-details']);
			}
			unset($json['disk-scheduler']);
			unset($json['disk-mount-options']);
		}
		if(isset($json['cpu-scaling-governor']) || isset($json['cpu-microcode']) || isset($json['cpu-thermald']))
		{
			$cpu_data = array();

			if(!empty($json['cpu-scaling-governor']))
			{
				$cpu_data[] = 'Scaling Governor: ' . $json['cpu-scaling-governor'];
				unset($json['cpu-scaling-governor']);
			}

			if(!empty($json['cpu-microcode']))
			{
				$cpu_data[] = 'CPU Microcode: ' . $json['cpu-microcode'];
				unset($json['cpu-microcode']);
			}

			if(!empty($json['cpu-thermald']))
			{
				$cpu_data[] = 'Thermald ' . $json['cpu-thermald'];
				unset($json['cpu-thermald']);
			}

			$system_attributes['Processor'][$identifier] = implode(' - ', $cpu_data);
		}
		if(isset($json['cpu-smt']))
		{
			$system_attributes['Processor'][$identifier] = 'SMT (threads per core): ' . $json['cpu-smt'];
			unset($json['cpu-smt']);
		}
		if(isset($json['graphics-2d-acceleration']) || isset($json['graphics-aa']) || isset($json['graphics-af']))
		{
			$report = array();
			foreach(array('graphics-2d-acceleration', 'graphics-aa', 'graphics-af') as $check)
			{
				if(isset($json[$check]) && !empty($json[$check]))
				{
					$report[] = $json[$check];
					unset($json[$check]);
				}
			}
			$system_attributes['Graphics'][$identifier] = implode(' - ' , $report);
		}
		if(isset($json['graphics-compute-cores']))
		{
			$system_attributes['OpenCL'][$identifier] = 'GPU Compute Cores: ' . $json['graphics-compute-cores'];
			unset($json['graphics-compute-cores']);
		}
		if(!empty($notes_string))
		{
			$system_attributes['System'][$identifier] = $notes_string;
		}
		if(!empty($json) && is_array($json))
		{
			foreach($json as $key => $value)
			{
				if(!empty($value))
				{
					$system_attributes[ucwords(str_replace(array('_', '-'), ' ', $key))][$identifier] = $value;
				}
				unset($json[$key]);
			}
		}
	}
	public static function system_notes_to_formatted_array(&$result_file)
	{
		$system_attributes = array();

		foreach($result_file->get_systems() as $s)
		{
			pts_result_file_analyzer::system_to_note_array($s, $system_attributes);
		}

		if(isset($system_attributes['compiler']) && count($system_attributes['compiler']) == 1 && ($result_file->get_system_count() > 1 && ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)) && isset($intent[0]) && is_array($intent[0]) && array_shift($intent[0]) == 'Compiler') == false)
		{
			// Only show compiler strings when it's meaningful (since they tend to be long strings)
			unset($system_attributes['compiler']);
		}

		return $system_attributes;
	}
}

?>
