<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace XF\Db\PostgreSql;

use XF\Db\AbstractStatement;

class Statement extends AbstractStatement
{
	/**
	 * @var resource
	 */
	protected $statement;

	/**
	 * @var resource
	 */
	protected $result;

	public function prepare()
	{
		if ($this->statement)
		{
			throw new \LogicException("Statement has already been prepared");
		}

		$connection = $this->adapter->getConnectionForQuery($this->query);

		$this->statement = pg_prepare($connection, '', $this->query);
		if (!$this->statement)
		{
			$error = pg_last_error($connection);
			throw $this->getException("PostgreSQL statement prepare error: $error");
		}
	}

	public function execute()
	{
		if (!$this->statement)
		{
			$this->prepare();
		}

		$connection = $this->adapter->getConnectionForQuery($this->query);

		$this->adapter->logQueryExecution($this->query, $this->params);
		$result = pg_execute($connection, '', $this->params);
		$this->adapter->logQueryStage('execute');

		if ($result === false)
		{
			$error = pg_last_error($connection);
			throw $this->getException("PostgreSQL query error: $error");
		}

		$this->adapter->logQueryCompletion();

		$keys = [];

		$count = pg_num_fields($result);
		for ($i = 0; $i < $count; $i++)
		{
			$keys[] = pg_field_name($result, $i);
		}

		$this->keys = $keys;
		$this->result = $result;

		return $this->result;
	}

	public function fetchRowValues()
	{
		$result = $this->result;
		if (!$result)
		{
			return false;
		}

		$row = pg_fetch_row($result);

		if ($row === false)
		{
			return false;
		}

		return $row;
	}

	public function rowsAffected()
	{
		if ($this->result)
		{
			return pg_affected_rows($this->result);
		}
		else
		{
			return 0;
		}
	}

	public function reset()
	{
		return;
	}

	protected function closeStatement()
	{
		return;
	}
}