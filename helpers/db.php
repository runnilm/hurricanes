<?php
class DB
{
    public $pdo;
    protected $select = '*';
    protected $from = '';
    protected $where = '';
    protected $whereParams = [];
    protected $orderBy = '';
    protected $limit = '';

    public function __construct($host, $user, $pass, $dbname)
    {
        $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    public function from($table)
    {
        $this->from = $table;
        return $this;
    }

    public function select($columns = '*')
    {
        if (is_array($columns)) {
            $columns = implode(',', $columns);
        }
        $this->select = $columns;
        return $this;
    }

    public function where($condition, $params = [])
    {
        $this->where = "WHERE $condition";
        $this->whereParams = $params;
        return $this;
    }

    public function orderBy($clause)
    {
        $this->orderBy = "ORDER BY $clause";
        return $this;
    }

    public function limit($start, $length)
    {
        $this->limit = "LIMIT $start, $length";
        return $this;
    }

    public function count()
    {
        $sql = "SELECT COUNT(*) AS cnt FROM {$this->from} {$this->where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->whereParams);
        return (int)$stmt->fetchColumn();
    }

    public function get()
    {
        $sql = "SELECT {$this->select} FROM {$this->from} {$this->where} {$this->orderBy} {$this->limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->whereParams);
        return $stmt->fetchAll();
    }

    public function getColumns($dbName, $tableName)
    {
        $sql = "SELECT COLUMN_NAME, COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table ORDER BY ORDINAL_POSITION";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':db' => $dbName, ':table' => $tableName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function instance($host = 'localhost', $user = 'root', $pass = '', $dbname = 'runni')
    {
        return new self($host, $user, $pass, $dbname);
    }
}
