<?php

define("__CLASSNAME__", "\\Stats");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Stats extends cb\Connector implements cb\Transformer
{
	private $n;
	private $tmp1;
	private $tmp2;
	private $sum;
	private $avg;
	private $std;
	
	public function transform($message, $in, $out)
	{
		$c = $message->content();
		$x = null;
		$name = null;
		if( isset($c['number']) && is_numeric($c['number']) )
		{
			$x = $c['number'];
			$name = 'number';
		}
		else
		{
			foreach( $c as $key=>$value )
			{
				if( is_numeric($value) )
				{
					$x = $value;
					$name = $key;
					break;
				}
			}
		}
		
		if( $x == null ) return null;
		
		$this->populate();
		
		$this->n++;
		$this->tmp1 = $x - $this->avg;
		$this->tmp2 = $this->tmp1 / $this->n;

		$this->sum += $x;
		$this->avg += $this->tmp2;
		$this->std = sqrt(($this->n-1) * $this->tmp1 * $this->tmp2);
		
		$this->update();
		
		switch( $out->key )
		{
			default:
			case 'avg':
				$c[$name] = $this->avg;
				break;
			case 'sum':
				$c[$name] = $this->sum;
				break;
			case 'std':
				$c[$name] = $this->std;
				break;
		}
		$message->content($c);

		return $message;
	}
	
	public function test()
	{
		return true;
	}
	
	private function populate()
	{
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		$sql = "SELECT 
			stat_n, 
			stat_tmp1, 
			stat_tmp2, 
			stat_avg, 
			stat_sum, 
			stat_std 
			FROM stats WHERE stat_identifier = '".$this->id()."'";
		$row = $mysql->selectOne($sql);
		
		if( $row == null )
		{
			$this->n = 0;
			$this->tmp1 = 0;
			$this->tmp2 = 0;
			$this->avg = 0;
			$this->sum = 0;
			$this->std = 0;
		}
		else
		{
			$this->n = $row['stat_n'];
			$this->tmp1 = $row['stat_tmp1'];
			$this->tmp2 = $row['stat_tmp2'];
			$this->avg = $row['stat_avg'];
			$this->sum = $row['stat_sum'];
			$this->std = $row['stat_std'];
		}
	}
	
	private function update()
	{
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		$sql = "INSERT INTO stats (stat_identifier, stat_n, stat_tmp1, stat_tmp2, stat_avg, stat_sum, stat_std) 
			VALUES ('".$this->id()."', '{$this->n}', '{$this->tmp1}', '{$this->tmp2}', '{$this->avg}', '{$this->sum}', '{$this->std}') 
			ON DUPLICATE KEY UPDATE stat_n = '{$this->n}', stat_tmp1 = '{$this->tmp1}', stat_tmp2 = '{$this->tmp2}', stat_avg = '{$this->avg}', stat_sum = '{$this->sum}', stat_std = '{$this->std}'";
		$mysql->insert($sql);
	}
}

?>