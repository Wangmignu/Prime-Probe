<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2019, Phoronix Media
	Copyright (C) 2009 - 2019, Michael Larabel

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

class cpu_power extends phodevi_sensor
{
	const SENSOR_TYPE = 'cpu';
	const SENSOR_SENSES = 'power';
	static $cpu_energy = 0;
	static $last_time = 0;
	protected static $amd_energy_sockets = false;

	public function read_sensor()
	{
		if(phodevi::is_linux())
		{
			return $this->cpu_power_linux();
		}
		return -1;		// TODO make -1 a named constant
	}
	public static function get_unit()
	{
		$unit = null;

		if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input'))
		{
			$unit = 'Milliwatts';
		}
		else
		{
			$unit = 'Watts';
		}

		return $unit;
	}

	private function cpu_power_linux()
	{
		$cpu_power = -1;

		if(self::$amd_energy_sockets === false)
		{
			self::$amd_energy_sockets = array();
			foreach(pts_file_io::glob('/sys/class/hwmon/hwmon*/name') as $hwmon)
			{
				if(pts_file_io::file_get_contents($hwmon) == 'amd_energy')
				{
					$hwmon_dir = dirname($hwmon);

					foreach(glob($hwmon_dir . '/energy*_label') as $label)
					{
						if(strpos(file_get_contents($label), 'Esocket') !== false)
						{
							self::$amd_energy_sockets[] = str_replace('_label', '_input', $label);
						}
					}
					break;
				}
			}
		}

		if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input'))
		{
			$in_power1_input = pts_file_io::file_get_contents('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input');
			if(is_numeric($in_power1_input) && $in_power1_input > 1)
			{
				$cpu_power = $in_power1_input;
			}
		}
		else if(is_readable('/sys/class/powercap/intel-rapl/intel-rapl:0/energy_uj'))
		{
			$rapl_base_path = "/sys/class/powercap/intel-rapl/intel-rapl:";
			$total_energy = 0;
			for($x = 0; $x <= 128; $x++)
			{
				$rapl_base_path_1 = $rapl_base_path . $x;
				if(is_readable($rapl_base_path_1))
				{
					$energy_uj = pts_file_io::file_get_contents($rapl_base_path_1 . '/energy_uj');
					if(is_numeric($energy_uj))
					{
						$total_energy += $energy_uj;
					}
				}
				else
				{
					break;
				}
			}

			if($total_energy > 1)
			{
				if(self::$cpu_energy == 0)
				{
					self::$cpu_energy = $total_energy;
					self::$last_time = time();
					$cpu_power = 0;
				}
				else
				{
					$cpu_power = ($total_energy - self::$cpu_energy) / (time() - self::$last_time) / 1000000;
				}
				self::$last_time = time();
				self::$cpu_energy = $total_energy;
			}
		}
		else if(!empty(self::$amd_energy_sockets))
		{
			$tries = 0;
			do
			{
				$tries++;
				$j1 = 0;
				$j2 = 0;
				foreach(self::$amd_energy_sockets as $f)
				{
					$j1 += trim(file_get_contents($f));
				}
				sleep(1);
				foreach(self::$amd_energy_sockets as $f)
				{
					$j2 += trim(file_get_contents($f));
				}
				$cpu_power = ($j2 - $j1) * 0.0000010;

				// This loop is in case the counters roll over
			}
			while($cpu_power < 1 && $tries < 2);
		}
		else if(is_readable('/sys/class/hwmon/hwmon0/name') && pts_file_io::file_get_contents('/sys/class/hwmon/hwmon0/name') == 'zenpower')
		{
			foreach(pts_file_io::glob('/sys/class/hwmon/hwmon*/power*_label') as $label)
			{
				if(pts_file_io::file_get_contents($label) == 'SVI2_P_SoC')
				{
					$cpu_power += pts_file_io::file_get_contents(str_replace('_label', '_input', $label));
				}
			}
			if($cpu_power > 100000)
			{
				$cpu_power = $cpu_power / 100000;
			}
		}

		return round($cpu_power, 2);
	}
}

?>
