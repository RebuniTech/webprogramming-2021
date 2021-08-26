<?php

namespace app\core;

use app\core\Application;

abstract class DBModel extends Model
{
	abstract public function tableName(): string;
	abstract public function attributes(): array;

	public function save()
	{
		$tableName = $this->tableName();
		$attributes = $this->attributes();
		$params = array_map(fn($attr) => ":$attr", $attributes);
		$stmt = self::prepare("INSERT INTO $tableName (".implode(",", $attributes).") 
							  VALUES (".implode(",", $params).");");

		foreach ($attributes as $attribute) {
			$stmt->bindValue($attribute, $this->{$attribute});
		}
		$stmt->execute();
		
		return true;
	}

	public static function findOne($where)
	{
		$tableName = static::tableName();
		$attributes = array_keys($where);
		$sql = implode(" AND ", array_map(fn($attr) => "$attr = :$attr", $attributes));
		$stmt = self::prepare("SELECT * FROM $tableName WHERE $sql;");
		foreach ($where as $key => $value){
			$stmt->bindValue(":$key", $value);
		}
		$stmt->execute();
		return $stmt->fetchObject(static::class);
	}
	
	public function load($data)
	{
		foreach ($data as $key => $value){
			if (property_exists($this, $key)) {
				$this->{$key} = $value;
			}
		}
	}
	public static function loadRelated($relationTable, $relations)
	{	
		$sql = implode(" AND ", array_map(fn($attr) => "$attr = :$attr", array_keys($relations)));
		$stmt = self::prepare("SELECT * FROM $relationTable WHERE $sql;");
		$stmt->execute($relations);
		return $stmt->fetch();
	}
	public static function prepare($sql)
	{

		return 	Application::$app->db->pdo->prepare($sql);
	}
}

?>