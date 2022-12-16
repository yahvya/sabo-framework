<?php

namespace Model\Model;

use \PDO;
use \PDOException;
use \ReflectionClass;

use \Sabo\Sabo\Router;

use \Model\Attribute\TableColumn;
use \Model\Attribute\TableName;

use \Model\Exception\ModelException;

use \Model\Cond\ColumnCond;

/*
	models are based on mysql (change your engine to InnoDb engine to support transactions)
	encapsulate all model use with a try catch to prevent all ModelException
*/
abstract class AbstractModel 
{
	// pdo instance witch will be share to all models if don't give a con in construct
	private static PDO $shared_con;

	private static bool $debug_mode = false; 

	private PDO $con;

	private array $properties_data;
	private array $primary_keys;

	private string $table_name;

	// called by router to init the shared con
	public static function init_con(bool $debug_mode):bool
	{
		self::$debug_mode = $debug_mode;

		$con = self::get_con();

		if($con != NULL)
		{
			self::$shared_con = $con;

			return true;
		}

		return false;
	}

	// return an connexion instance with fetch default mode assoc
	public static function get_con():?PDO
	{
		switch(CONFIG_FILE_TYPE)
		{
			case Router::CLASSIC_ENV:
				list("db_host" => $host,"db_name" => $name,"db_user" => $user,"db_password" => $password) = $_ENV;
			; break;

			case Router::JSON_ENV:
				list("host" => $host,"name" => $name,"user" => $user,"password" => $password) = $_ENV["database"];
			; break;

			default:
				if(self::$debug_mode)
					throw new ModelException("Bad env format",false);
				else
					return NULL;
			;
		}

		try
		{
			$con = new PDO("mysql:host={$host};dbname={$name}",$user,$password,[
				PDO::ATTR_PERSISTENT => true,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ERRMODE_EXCEPTION => self::$debug_mode,
				PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8" 
			]);

			return $con;
		}
		catch(PDOException $e)
		{
			if(self::$debug_mode)
				throw $e;
		}

		return NULL;
	}

	// init the transaction with the shared con
	public static function begin_transation(?PDO $con = NULL):bool
	{
		try
		{
			return $con == NULL ? self::$shared_con->beginTransaction() : $con->beginTransaction();
		}	
		catch(PDOException){}

		return false;
	}

	// commit the transaction with the shared con
	public static function commit_transaction(?PDO $con = NULL):bool
	{
		try
		{
			return $con == NULL ? self::$shared_con->commit() : $con->commit();
		}
		catch(PDOException){}

		return false;
	}

	// rollback the transaction with the shared con
	public static function rollback_transaction(?PDO $con = NULL):bool
	{
		try
		{
			return $con == NULL ? self::$shared_con->rollBack() : $con->rollBack();
		}
		catch(PDOException){}

		return false;
	}	

	/*
		must be called with the model itself ArticleModel::find()
		conds format -> [property => value] if cond if empty it will select * | keys will not be verifed
		cannot return object if the fetch mode is set
	*/
	public static function find(array $conditions = [],bool $return_objects = true,string $order_by = "",bool $just_one = false,bool $use_regexp = false,?int $fetch_mode = NULL):array
	{
		$class = get_called_class();

		if($class == self::class)
			return [];

		$model_instance = (new ReflectionClass($class))->newInstance();

		$result = [];

		try
		{
			$to_bind = [];

			if(!empty($conditions) )
			{
				$conds = [];

				foreach($conditions as $property_name => $condition_value)
				{
					if($use_regexp)
						array_push($conds,"{$model_instance->properties_data[$property_name]["linked_col_name"]} REGEXP :{$property_name}");
					else
						array_push($conds,"{$model_instance->properties_data[$property_name]["linked_col_name"]}=:{$property_name}");

					if(!empty($model_instance->properties_data[$property_name] ) && $model_instance->properties_data[$property_name]["can_be_hashed"])
						$condition_value = self::hash_model_hashable($condition_value);

					$to_bind[":{$property_name}"] = $condition_value;
				}

				$conds = implode(" and ",$conds);

				if($just_one)
					$query = self::$shared_con->prepare("select * from {$model_instance->table_name} where $conds $order_by limit 1");
				else
					$query = self::$shared_con->prepare("select * from {$model_instance->table_name} where $conds $order_by");
			}
			elseif($just_one)
				$query = self::$shared_con->prepare("select * from {$model_instance->table_name} $order_by limit 1");
			else
				$query = self::$shared_con->prepare("select * from {$model_instance->table_name} $order_by");

			if($query != false && $query->execute($to_bind) )
			{
				if($fetch_mode == NULL)
					$result = $query->fetchAll();
				else 
					$result = $query->fetchAll($fetch_mode);

				// unshash hashed datas

				foreach($result as $key => $row_data)
				{
					foreach($model_instance->properties_data as $property_data)
					{
						if(!empty($row_data[$property_data["linked_col_name"] ]) && $property_data["can_be_hashed"])
							$result[$key][$property_data["linked_col_name"] ] = self::unhash_model_hashable($row_data[$property_data["linked_col_name"] ]);
					}
				}

				if($return_objects)
				{
					$result = array_map(
						fn(array $row_data):AbstractModel => $class::get_object_from_row($row_data),
						$result
					);
				}
			}
		}
		catch(PDOException $e)
		{
			$result = [];
		}

		return $result;
	}

	/*
		unserialize a model which you this->serialize model, return the model on success or null
		datas to replace format -> ["class_property" => data_to_replace]
		can throw modelexception
	*/
	public static function unserialize_model(string $serialized_version,array $datas_to_replace_in = [],?PDO $model_con = NULL):?AbstractModel
	{
		$model = unserialize($serialized_version);

		if(is_object($model) && is_subclass_of($model,self::class) )
		{
			$model->con = $model_con == NULL ? self::$shared_con : $model_con;

			foreach($datas_to_replace_in as $property_name => $value)
				$model->set_column($property_name,$value);

			return $model;
		}

		return NULL;
	}

	public static function hash_model_hashable(?string $data):?string
	{
		// website hash

		return $data;
	}

	public static function unhash_model_hashable(?string $data):?string
	{
		// website unhash

		return $data;
	}

	// throw exception if the model is badly formed
	public function __construct(?PDO $con = NULL)
	{
		$this->properties_data = [];
		$this->primary_keys = [];

		$reflection_class = new ReflectionClass($this);

		$table_name_attribute = $reflection_class->getAttributes(TableName::class);

		if(count($table_name_attribute) != 1)
			throw new ModelException("Model must have one TableName attribute",false);

		$this->table_name = $table_name_attribute[0]->newInstance()->get_table_name();

		$reflection_class = new ReflectionClass($this);

		foreach($reflection_class->getProperties() as $reflection_property)
		{
			$property_name = $reflection_property->getName();

			$column_attribute = $reflection_property->getAttributes(TableColumn::class);

			$count = count($column_attribute);

			if($count > 1)
				throw new ModelException("The model can't have multiple TableColumn attribute",false);

			if($count == 0)
				continue;

			$this->properties_data[$property_name] = $column_attribute[0]->newInstance()->get_all();

			if($this->properties_data[$property_name]["is_primary"])
				array_push($this->primary_keys,$property_name);

			$conds_attribute = $reflection_property->getAttributes(ColumnCond::class);

			$count = count($conds_attribute);

			if($count > 1)
				throw new ModelException("The model can't have multiple ColumnCond attribute",false);

			$this->properties_data[$property_name]['cond'] =  $count == 0 ? NULL : $conds_attribute[0]->newInstance();
		}

		$this->con = $con == NULL ? self::$shared_con : $con;
	}

	// return false if try to valid an auto increment primary key, true if success or the error message from the failed cond
	protected function data_is_valid_for_attribute(string $attribute_name,mixed $data):bool|string
	{
		if
		(
			!empty($this->properties_data[$attribute_name]) &&
			(
				!$this->properties_data[$attribute_name]["is_primary"] ||
				!$this->properties_data[$attribute_name]["is_auto_increment"]
			) 
		)
		{
			if($this->properties_data[$attribute_name]["cond"] != NULL)
			{
				if(($this->properties_data[$attribute_name]["is_nullable"] && empty($data) ) || $this->properties_data[$attribute_name]["cond"]->is_valid($data) )
					return true;
				else
					return $this->properties_data[$attribute_name]["cond"]->get_error_message();
			}
			else return true;
		}
		
		return false;
	}

	// throw a ModelException is failed to set
	public function set_attribute(string $attribute_name,mixed $data):self
	{
		$check_result = $this->data_is_valid_for_attribute($attribute_name,$data);

		if($check_result === true)
		{
			$this->$attribute_name = $data;

			return $this;
		}

		if($check_result === false)
			throw new ModelException("An autoincrement primary key can't be set",false);
		else
			throw new ModelException($check_result);
	}

	// return null if attribute not found or empty
	public function get_attribute(string $attribute_name):mixed
	{
		if(!empty($this->properties_data[$attribute_name]) && isset($this->$attribute_name) )
			return $this->$attribute_name;

		return NULL;
	}

	// start a transaction with the given con in the construct (if null the shared con will be use)
	public function begin_personnal_transation():bool
	{
		return self::begin_transation($this->con);
	}

	// commit a transaction with the given con in the construct (if null the shared con will be use)
	public function commit_personnal_transation():bool
	{
		return self::commit_transaction($this->con);
	}

	// rollback a transaction with the given con in the construct (if null the shared con will be use)
	public function rollback_personnal_transation():bool
	{
		return self::rollback_transaction($this->con);
	}

	/*
		try to insert in database (if a transaction is not iniate it will execute definitively the request)
		the table primary key will be set if not a composite primary key
	*/
	public function create():bool
	{
		$to_insert = [];
		$markers = [];
		$to_bind = [];

		foreach($this->properties_data as $property_name => $property_data)
		{
			if($property_data["is_primary"] && $property_data["is_auto_increment"])
				continue;

			// check if the property is not initialized and is nullable
			if($property_data["is_nullable"] && (isset($this->$property_name) && $this->$property_name == NULL) )
				continue;

			// if the property is not initialized
			if(!isset($this->$property_name) && !is_null($this->$property_name) )
				return false;

			array_push($to_insert,$property_data["linked_col_name"]);
			array_push($markers,":{$property_data["linked_col_name"]}");
			$to_bind[":{$property_data["linked_col_name"]}"] = $property_data["can_be_hashed"] ? self::hash_model_hashable($this->$property_name) : $this->$property_name;
		}

		$to_insert = implode(",",$to_insert);
		$markers = implode(",",$markers);

		if(empty($to_insert) )
			return false;

		$query = $this->con->prepare("insert into {$this->table_name}({$to_insert}) values({$markers})");

		try
		{
			if($query != false && $query->execute($to_bind) )
			{
				if(count($this->primary_keys) == 1)
					$this->{$this->primary_keys[0]} = $this->con->lastInsertId();

				return true;
			}
		}
		catch(PDOException){}

		return false;
	}

	public function delete():bool
	{
		try
		{
			$conds = [];
			$to_bind = [];

			foreach($this->primary_keys as $primary_attribute)
			{
				// check if primary key is not initialized
				if(!isset($this->$primary_attribute) )
					return false;

				array_push($conds,"{$this->properties_data[$primary_attribute]["linked_col_name"]} = :{$primary_attribute}");
				$to_bind[":{$primary_attribute}"] = $this->$primary_attribute;
			}

			if(empty($conds) )
				return false;

			$conds = implode(" and ",$conds);

			$query = $this->con->prepare("delete from $this->table_name where $conds");

			return $query != false && $query->execute($to_bind);
		}
		catch(PDOException){}

		return false;
	}

	public function update():bool
	{
		try
		{
			$conds = [];
			$to_set = [];
			$to_bind = [];

			foreach($this->properties_data as $property_name => $property_data)
			{
				// if the property is not initialized
				if(!isset($this->$property_name) )
					return false;

				if($property_data["is_primary"])
				{
					array_push($conds,"{$this->properties_data[$property_name]["linked_col_name"]}=:primary_{$property_name}");
					$to_bind[":primary_{$property_name}"] = $property_data["can_be_hashed"] ? self::hash_model_hashable($this->$property_name) : $this->$property_name;
				}

				array_push($to_set,"{$this->properties_data[$property_name]["linked_col_name"]}=:{$property_name}");
				$to_bind[":{$property_name}"] = $property_data["can_be_hashed"] ? self::hash_model_hashable($this->$property_name) : $this->$property_name;

				if(gettype($to_bind[":{$property_name}"]) == "boolean")
					$to_bind[":{$property_name}"] = $to_bind[":{$property_name}"] === false ? 0 : 1;
			}

			if(empty($to_set) || empty($conds) )
				return false;

			$to_set = implode(",",$to_set);
			$conds = implode(" and ",$conds);
			
			$query = $this->con->prepare("update $this->table_name set $to_set where $conds");

			return $query != false && $query->execute($to_bind);
		}
		catch(PDOException){}

		return false;
	}

	public function set_con(PDO $con):void
	{
		$this->con = $con;
	}

	// serialize the model and return it if you have fields to remove which are not tablecolumn you can override this method to remove them and call the parent method
	public function get_serialized_version():string
	{
		// removes elements which have to be remove based on table column attribute

		$to_replace = ["con" => $this->con];

		unset($this->con);

		foreach($this->properties_data as $property_name => $property_data)
		{
			if(!$property_data["is_serializable"])
			{
				$to_replace[$property_name] = $this->$property_name;

				unset($this->$property_name);
			}
		}

		$serialized_string = serialize($this);

		foreach($to_replace as $property_name => $value)
			$this->$property_name = $value;

		return $serialized_string;
	}	

	// abstract functions

	abstract protected static function get_object_from_row(array $row):AbstractModel;
}