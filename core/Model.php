<?php

namespace app\core;

abstract class Model 
{
	public const RULE_REQUIRED = 'required';
	public const RULE_EMAIL = 'email';
	public const RULE_MIN = 'min';
	public const RULE_MAX = 'max';
	public const RULE_MATCH = 'match';
	public const RULE_UNIQUE = 'unique';
	public const RULE_NUMERIC = 'numeric';
	public const RULE_INVALID = 'invalid';

	public array $errors = [];
	
	abstract public function rules();

	public function loadData($data)
	{
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->{$key} = $value;
			}
		}
	}


	public function labels(): array
	{
		return [];
	}

	public function getLabel($attribute)
	{
		return $this->labels()[$attribute] ?? $attribute;
	}
	public function validate()
	{
		foreach ($this->rules() as $attribute => $rules) {
			$value = $this->{$attribute};
			foreach ($rules as $rule){
				$ruleName = $rule;

				if (!is_string($ruleName))
				{
					$ruleName = $rule[0];
				}

				if ($ruleName === self::RULE_REQUIRED && empty($value))
				{
					$this->addError($attribute, self::RULE_REQUIRED);
				} 
				if ($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL))
				{
					$this->addError($attribute, self::RULE_EMAIL);	
				}
				if ($ruleName === self::RULE_MIN && strlen($value) < $rule['min'])
				{
					$this->addError($attribute, self::RULE_MIN, $rule);	
				}
				if ($ruleName === self::RULE_MAX && strlen($value) > $rule['max'])
				{
					$this->addError($attribute, self::RULE_MAX, $rule);	
				}
				if ($ruleName === self::RULE_MATCH && $value != $this->{$rule['match']})
				{
					$this->addError($attribute, self::RULE_MATCH, ['match' => $this->getLabel($rule['match'])]);	
				}
				if ($ruleName === self::RULE_NUMERIC && ctype_digit($value)){
					$this->addError($attribute, self::RULE_NUMERIC, ['field' => $this->getLabel($attribute)]);
				}
				if ($ruleName === self::RULE_UNIQUE){
					$className = $rule['class'];
					$uniqueAttribute = $ruel['attribute'] ?? $attribute;
					$tableName = $className::tableName(); 
					$stmt = Application::$app->db->prepare("SELECT * FROM $tableName WHERE $uniqueAttribute=:attr");
					$stmt->bindValue(":attr", $value);
					$stmt->execute();
					$record = $stmt->fetchObject();
					if($record){
						$this->addError($attribute, self::RULE_UNIQUE, ['field' => $attribute]);
					}


				}
			}
		}
		return empty($this->errors);
	}

	public function addError($attribute, $rule, $params = [])
	{
		$errMsg = $this->errorMessage()[$rule] ?? '';
		foreach ($params as $key => $value){
			$errMsg = str_replace("{{$key}}", $value, $errMsg);
		}
		$this->errors[$attribute][] =  $errMsg;
	}

	public function errorMessage()
	{
		return [
			self::RULE_REQUIRED => 'This field is required.',
			self::RULE_EMAIL => 'This field must be valud email',
			self::RULE_MIN => 'Min length of this field must be {min}',
			self::RULE_MAX => 'Max length of this field must be {max}',
			self::RULE_MATCH => 'This field must be the same as {match}',
			self::RULE_UNIQUE => 'Record with this {field} already exist',
			self::RULE_INVALID => 'Invalid {field}',
			self::RULE_NUMERIC => '{field} cannot be entirlly numeric.',
		];
	}

	public function hasError($attribute)
	{
		return $this->errors[$attribute] ?? false;
	}

	public function getFirstError($attribute)
	{
		return $this->errors[$attribute][0] ?? false;
	}
}

?>