<?php

class DB {

	public $servername = "localhost";
	public $username = "mysql";
	public $password = "mysql";
	public $dbname = "lampochki";

	public $conn;

	public function __construct() {
		try {
		  $this->conn = new PDO("mysql:host={$this->servername};dbname={$this->dbname}", $this->username, $this->password);
		  $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
		  echo "Connection failed: " . $e->getMessage();
		}
	}

	public function insert($marka, $model, $vipusk, $posadka, $cokol) {
		$sql = "INSERT INTO cars (`marka`, `model`, `vipusk`, `posadka`, `cokol`) VALUES (\"$marka\", \"$model\", \"$vipusk\", \"$posadka\", \"$cokol\")";
		// echo $sql; exit;
		try {
			$this->conn->exec($sql);

		} catch (PDOException $e) {
			echo $sql . "<br>" . $e->getMessage();
		}
	}

	public function select($select = "*", $marka = "", $model = "", $vipusk = "", $posadka = "", $cokol = "") {
		$sql = "SELECT $select FROM cars WHERE 1";
		if ($marka) {
			$sql .= " AND marka=" . "\"{$marka}\"";
		}
		if ($model) {
			$sql .= " AND model=" . "\"{$model}\"";
		}
		if ($vipusk) {
			$sql .= " AND vipusk=" . "\"{$vipusk}\"";
		}
		if ($posadka) {
			$sql .= " AND posadka=" . "\"{$posadka}\"";
		}
		if ($cokol) {
			$sql .= " AND cokol=" . "\"{$cokol}\"";
		}
		try {	
			$stmt = $this->conn->prepare($sql);
	  		$stmt->execute();
	  		$stmt->setFetchMode(PDO::FETCH_ASSOC);
	  		return $stmt->fetchAll();

		} catch (PDOException $e) {
			echo $sql . "<br>" . $e->getMessage();
		}
	}

	public function getMarkas() {
		$markas = [];
		foreach ($this->select("marka") as $marka) {
			if (!in_array($marka['marka'], $markas)) {
				$markas[] = $marka['marka'];
			}
		}
		unset($markas[count($markas)-1]);
		return $markas;
	}

	public function getModels($marka) {
		$models = [];
		foreach ($this->select("marka, model", $marka) as $model) {
			if (!in_array($model['model'], $models)) {
				$models[] = $model['model'];
			}
		}
		unset($models[count($models)-1]);
		return $models;
	}

	public function getVipusks($marka, $model) {
		$vipusks = [];
		foreach ($this->select("marka, model, vipusk", $marka, $model) as $vipusk) {
			if (!in_array($vipusk['vipusk'], $vipusks)) {
				$vipusks[] = $vipusk['vipusk'];
			}
		}
		unset($vipusks[count($vipusks)-1]);
		return $vipusks;
	}

	public function getPosadkas($marka, $model, $vipusk) {
		$posadkas = [];
		foreach ($this->select("marka, model, vipusk, posadka", $marka, $model, $vipusk) as $posadka) {
			if (!in_array($posadka['posadka'], $posadkas)) {
				$posadkas[] = $posadka['posadka'];
			}
		}
		// последний элемент не убираем, потому что если он есть, то однозначный цоколь тоже есть 
		// unset($posadkas[count($posadkas)-1]);
		return $posadkas;
	}

	


}
