<?php

namespace bot\connection;

use bot\common\Sync;
use PDO;
use Yii;
use yii\caching\CacheInterface;
use yii\base\{Component, InvalidConfigException, NotSupportedException};
use yii\db\{Command, Connection, Exception, QueryBuilder, Schema, TableSchema, Transaction};
use \Swoole\Database\{PDOPool, PDOConfig};
use yii\log\Logger;

class PdoConnect extends Component
{
    /**
     * @event \yii\base\Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';
    /**
     * @event \yii\base\Event an event that is triggered right before a top-level transaction is started
     */
    const EVENT_BEGIN_TRANSACTION = 'beginTransaction';
    /**
     * @event \yii\base\Event an event that is triggered right after a top-level transaction is committed
     */
    const EVENT_COMMIT_TRANSACTION = 'commitTransaction';
    /**
     * @event \yii\base\Event an event that is triggered right after a top-level transaction is rolled back
     */
    const EVENT_ROLLBACK_TRANSACTION = 'rollbackTransaction';

    /**
     * 主库存map键值
     */
    const PDO_TYPE_MASTER = 'pdo_master';

    /**
     * 从库存map键值
     */
    const PDO_TYPE_SLAVE = 'pdo_slave';

    /**
     * 事务存map键值
     */
    const SYNC_KEY_TRANS = 'db__transaction';

    /**
     * @var string $dsn
     */
    public $dsn;
    /**
     * @var string|null the username for establishing DB connection. Defaults to `null` meaning no username to use.
     */
    public $username;
    /**
     * @var string|null the password for establishing DB connection. Defaults to `null` meaning no password to use.
     */
    public $password;

    /**
     * @var PDO|null pdo实例 db链接
     * @see pdoClass
     */
    protected $pdo;

    /**
     * @var bool 是否可以缓存schema
     */
    public $enableSchemaCache = false;

    /**
     * @var int schema缓存你时间
     */
    public $schemaCacheDuration = 3600;

    /**
     * @var array list of tables whose metadata should NOT be cached. Defaults to empty array.
     * The table names may contain schema prefix, if any. Do not quote the table names.
     * @see enableSchemaCache
     */
    public $schemaCacheExclude = [];
    /**
     * @var CacheInterface|string the cache object or the ID of the cache application component that
     * is used to cache the table metadata.
     * @see enableSchemaCache
     */
    public $schemaCache = 'cache';
    /**
     * @var bool whether to enable query caching.
     * Note that in order to enable query caching, a valid cache component as specified
     * by [[queryCache]] must be enabled and [[enableQueryCache]] must be set true.
     * Also, only the results of the queries enclosed within [[cache()]] will be cached.
     * @see queryCache
     * @see cache()
     * @see noCache()
     */
    public $enableQueryCache = true;
    /**
     * @var int the default number of seconds that query results can remain valid in cache.
     * Defaults to 3600, meaning 3600 seconds, or one hour. Use 0 to indicate that the cached data will never expire.
     * The value of this property will be used when [[cache()]] is called without a cache duration.
     * @see enableQueryCache
     * @see cache()
     */
    public $queryCacheDuration = 3600;
    /**
     * @var CacheInterface|string the cache object or the ID of the cache application component
     * that is used for query caching.
     * @see enableQueryCache
     */
    public $queryCache = 'cache';
    /**
     * @var string|null 字符集
     */
    public $charset;
    /**
     * @var bool|null whether to turn on prepare emulation. Defaults to false, meaning PDO
     * will use the native prepare support if available. For some databases (such as MySQL),
     * this may need to be set true so that PDO can emulate the prepare support to bypass
     * the buggy native prepare support.
     * The default value is null, which means the PDO ATTR_EMULATE_PREPARES value will not be changed.
     */
    public $emulatePrepare;
    /**
     * @var string 表前缀
     */
    public $tablePrefix = '';
    /**
     * @var array driver => Schema
     */
    public $schemaMap = [
        'pgsql' => 'yii\db\pgsql\Schema', // PostgreSQL
        'mysqli' => 'yii\db\mysql\Schema', // MySQL
        'mysql' => 'yii\db\mysql\Schema', // MySQL
        'sqlite' => 'yii\db\sqlite\Schema', // sqlite 3
        'sqlite2' => 'yii\db\sqlite\Schema', // sqlite 2
        'sqlsrv' => 'yii\db\mssql\Schema', // newer MSSQL driver on MS Windows hosts
        'oci' => 'yii\db\oci\Schema', // Oracle driver
        'mssql' => 'yii\db\mssql\Schema', // older MSSQL driver on MS Windows hosts
        'dblib' => 'yii\db\mssql\Schema', // dblib drivers on GNU/Linux (and maybe other OSes) hosts
        'cubrid' => 'yii\db\cubrid\Schema', // CUBRID
    ];
    /**
     * @var string|null Custom PDO wrapper class. If not set, it will use [[PDO]] or [[\yii\db\mssql\PDO]] when MSSQL is used.
     * @see pdo
     */
    public $pdoClass;

    /**
     * @var array driver => Command
     */
    public $commandMap = [
        'pgsql' => 'yii\db\Command', // PostgreSQL
        'mysqli' => 'yii\db\Command', // MySQL
        'mysql' => 'yii\db\Command', // MySQL
        'sqlite' => 'yii\db\sqlite\Command', // sqlite 3
        'sqlite2' => 'yii\db\sqlite\Command', // sqlite 2
        'sqlsrv' => 'yii\db\Command', // newer MSSQL driver on MS Windows hosts
        'oci' => 'yii\db\oci\Command', // Oracle driver
        'mssql' => 'yii\db\Command', // older MSSQL driver on MS Windows hosts
        'dblib' => 'yii\db\Command', // dblib drivers on GNU/Linux (and maybe other OSes) hosts
        'cubrid' => 'yii\db\Command', // CUBRID
    ];
    /**
     * @var bool whether to enable [savepoint](https://en.wikipedia.org/wiki/Savepoint).
     * Note that if the underlying DBMS does not support savepoint, setting this property to be true will have no effect.
     */
    public $enableSavepoint = true;
    /**
     * @var CacheInterface|string|false the cache object or the ID of the cache application component that is used to store
     * the health status of the DB servers specified in [[masters]] and [[slaves]].
     * This is used only when read/write splitting is enabled or [[masters]] is not empty.
     * Set boolean `false` to disabled server status caching.
     * @see openFromPoolSequentially() for details about the failover behavior.
     * @see serverRetryInterval
     */
    public $serverStatusCache = 'cache';
    /**
     * @var int the retry interval in seconds for dead servers listed in [[masters]] and [[slaves]].
     * This is used together with [[serverStatusCache]].
     */
    public $serverRetryInterval = 600;

    /**
     * @var bool 允许走从库
     */
    public $enableSlaves = true;

    /**
     * @var array 从库配置 随机从中间找一个链接
     */
    public $slaves = [];

    /**
     * @var array 从库公共配置 会merge到从库配置
     */
    public $slaveConfig = [];

    /**
     * @var array 主库配置 随机从中间找一个链接
     */
    public $masters = [];

    /**
     * @var array 主库公共配置 会merge到主库配置
     */
    public $masterConfig = [];
    /**
     * @var bool 随机走一个主库配置
     */
    public $shuffleMasters = true;
    /**
     * @var bool whether to enable logging of database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @since 2.0.12
     * @see enableProfiling
     */
    public $enableLogging = true;
    /**
     * @var bool whether to enable profiling of opening database connection and database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @since 2.0.12
     * @see enableLogging
     */
    public $enableProfiling = true;

    /**
     * @var $masterPool PDOPool 主库连接池
     */
    protected static $masterPool = null;

    /**
     * @var $masterPool PDOPool 从库连接池
     */
    protected static $slavePool = null;

    /**
     * @var bool init完成
     */
    protected static $initDone = false;

    /**
     * @var array An array of [[setQueryBuilder()]] calls, holding the passed arguments.
     * Is used to restore a QueryBuilder configuration after the connection close/open cycle.
     *
     * @see restoreQueryBuilderConfiguration()
     */
    private $_queryBuilderConfigurations = [];

    /**
     * @var Schema the database schema
     */
    private $_schema;
    /**
     * @var string driver name
     */
    private $_driverName;
    /**
     * @var Connection|false the currently active master connection
     */
    private $_master = false;
    /**
     * @var Connection|false the currently active slave connection
     */
    private $_slave = false;
    /**
     * @var array query cache parameters for the [[cache()]] calls
     */
    private $_queryCacheInfo = [];
    /**
     * @var string[] quoted table name cache for [[quoteTableName()]] calls
     */
    private $_quotedTableNames;
    /**
     * @var string[] quoted column name cache for [[quoteColumnName()]] calls
     */
    private $_quotedColumnNames;

    /**
     * @var string 链接类型
     */
    public $conType = self::PDO_TYPE_MASTER;

    public function init()
    {
        //加标识
        $this->masterConfig = array_merge($this->masterConfig, [
            'conType' => self::PDO_TYPE_MASTER,
        ]);

        $this->slaveConfig = array_merge($this->slaveConfig, [
            'conType' => self::PDO_TYPE_SLAVE,
        ]);

        //从
        if ($this->getSlave(false)) {
            static::$slavePool = new PDOPool(
                $this->buildConfig($this->_slave)
            );
        }

        //主
        try {
            $this->open();
            $connect = $this->_master ?: $this;
            static::$masterPool = new PDOPool(
                $this->buildConfig($connect)
            );
        } catch (\Exception $e) {
            Yii::getLogger()->log('create connection error:' . $e->getMessage(), Logger::LEVEL_INFO);
        }

        /**
         * init完成 后续不允许直连pdo 从连接池拿
         */
        static::$initDone = true;
        $this->close();

        /**
         * 用完还回去
         */
        Yii::$app->on(Sync::EVENT_BEFORE_DELETE, [$this, 'putPdoBack']);
    }

    /**
     * db配置
     * @param  PdoConnect $connect
     * @return PDOConfig
     */
    protected function buildConfig($connect)
    {
        list(, $dsn) = explode(":", $connect->dsn, 2);
        $conf = collect(explode(';', $dsn))
            ->map(function ($item) {
                return explode('=', $item);
            })
            ->pluck(1, 0);

        return (new PDOConfig())
            ->withHost($conf->get('host'))
            ->withPort($conf->get('port', 3306))
            ->withDbName($conf->get('dbname'))
            ->withUsername($connect->username)
            ->withPassword($connect->password)
            ->withCharset($connect->charset, 'utf8mb4');
    }

    /**
     * 返回连接到连接池
     */
    public static function putPdoBack($event)
    {
        if ($pdo = Sync::map(self::PDO_TYPE_MASTER)) {
            static::$masterPool->put(self::resetPdo($pdo));
        }

        if ($pdo = Sync::map(self::PDO_TYPE_SLAVE)) {
            static::$slavePool->put(self::resetPdo($pdo));
        }
    }

    /**
     * 重置pdo:比如活跃的事务 可能还有其他的需要清理的东西 发现了再说吧
     * 直接关了比较干脆 就是需要频繁的创建连接
     * @param PDO $pdo
     * @return PDO
     */
    public static function resetPdo($pdo)
    {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return $pdo;
    }

    /**
     * 是否已经建立连接
     * @return booL
     */
    public function getIsActive()
    {
        return $this->getPdo() !== null;
    }

    /**
     * Uses query cache for the queries performed with the callable.
     *
     * When query caching is enabled ([[enableQueryCache]] is true and [[queryCache]] refers to a valid cache),
     * queries performed within the callable will be cached and their results will be fetched from cache if available.
     * For example,
     *
     * ```php
     * // The customer will be fetched from cache if available.
     * // If not, the query will be made against DB and cached for use next time.
     * $customer = $db->cache(function (Connection $db) {
     *     return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     * });
     * ```
     *
     * Note that query cache is only meaningful for queries that return results. For queries performed with
     * [[Command::execute()]], query cache will not be used.
     *
     * @param callable $callable a PHP callable that contains DB queries which will make use of query cache.
     * The signature of the callable is `function (Connection $db)`.
     * @param int|null $duration the number of seconds that query results can remain valid in the cache. If this is
     * not set, the value of [[queryCacheDuration]] will be used instead.
     * Use 0 to indicate that the cached data will never expire.
     * @param \yii\caching\Dependency|null $dependency the cache dependency associated with the cached query results.
     * @return mixed the return result of the callable
     * @throws \Throwable if there is any exception during query
     * @see enableQueryCache
     * @see queryCache
     * @see noCache()
     */
    public function cache(callable $callable, $duration = null, $dependency = null)
    {
        $this->_queryCacheInfo[] = [$duration === null ? $this->queryCacheDuration : $duration, $dependency];
        try {
            $result = call_user_func($callable, $this);
            array_pop($this->_queryCacheInfo);
            return $result;
        } catch (\Exception $e) {
            array_pop($this->_queryCacheInfo);
            throw $e;
        } catch (\Throwable $e) {
            array_pop($this->_queryCacheInfo);
            throw $e;
        }
    }

    /**
     * Disables query cache temporarily.
     *
     * Queries performed within the callable will not use query cache at all. For example,
     *
     * ```php
     * $db->cache(function (Connection $db) {
     *
     *     // ... queries that use query cache ...
     *
     *     return $db->noCache(function (Connection $db) {
     *         // this query will not use query cache
     *         return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     *     });
     * });
     * ```
     *
     * @param callable $callable a PHP callable that contains DB queries which should not use query cache.
     * The signature of the callable is `function (Connection $db)`.
     * @return mixed the return result of the callable
     * @throws \Throwable if there is any exception during query
     * @see enableQueryCache
     * @see queryCache
     * @see cache()
     */
    public function noCache(callable $callable)
    {
        $this->_queryCacheInfo[] = false;
        try {
            $result = call_user_func($callable, $this);
            array_pop($this->_queryCacheInfo);
            return $result;
        } catch (\Exception $e) {
            array_pop($this->_queryCacheInfo);
            throw $e;
        } catch (\Throwable $e) {
            array_pop($this->_queryCacheInfo);
            throw $e;
        }
    }

    /**
     * Returns the current query cache information.
     * This method is used internally by [[Command]].
     * @param int|null $duration the preferred caching duration. If null, it will be ignored.
     * @param \yii\caching\Dependency|null $dependency the preferred caching dependency. If null, it will be ignored.
     * @return array|null the current query cache information, or null if query cache is not enabled.
     * @internal
     */
    public function getQueryCacheInfo($duration, $dependency)
    {
        if (!$this->enableQueryCache) {
            return null;
        }

        $info = end($this->_queryCacheInfo);
        if (is_array($info)) {
            if ($duration === null) {
                $duration = $info[0];
            }
            if ($dependency === null) {
                $dependency = $info[1];
            }
        }

        if ($duration === 0 || $duration > 0) {
            if (is_string($this->queryCache) && Yii::$app) {
                $cache = Yii::$app->get($this->queryCache, false);
            } else {
                $cache = $this->queryCache;
            }
            if ($cache instanceof CacheInterface) {
                return [$cache, $duration, $dependency];
            }
        }

        return null;
    }

    /**
     * 根据配置创建一个pdo连接 初始化之后堵住
     * @throws Exception if connection fails
     * @throws InvalidConfigException
     */
    public function open()
    {
        if ($this->pdo !== null || static::$initDone) {
            return;
        }

        if (!empty($this->masters)) {
            $db = $this->getMaster();
            if ($db !== null) {
                $this->pdo = $db->pdo;
                return;
            }

            throw new InvalidConfigException('None of the master DB servers is available.');
        }

        if (empty($this->dsn)) {
            throw new InvalidConfigException('Connection::dsn cannot be empty.');
        }

        $token = 'Opening DB connection: ' . $this->dsn;
        $enableProfiling = $this->enableProfiling;
        try {
            if ($this->enableLogging) {
                Yii::info($token, __METHOD__);
            }

            if ($enableProfiling) {
                Yii::beginProfile($token, __METHOD__);
            }

            $this->pdo = $this->createPdoInstance();

            if ($enableProfiling) {
                Yii::endProfile($token, __METHOD__);
            }
        } catch (\PDOException $e) {
            if ($enableProfiling) {
                Yii::endProfile($token, __METHOD__);
            }

            throw new Exception($e->getMessage(), $e->errorInfo, $e->getCode(), $e);
        }
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        if ($this->_master) {
            if ($this->pdo === $this->_master->pdo) {
                $this->pdo = null;
            }

            $this->_master->close();
            $this->_master = false;
        }

        if ($this->pdo !== null) {
            Yii::debug('Closing DB connection: ' . $this->dsn, __METHOD__);
            $this->pdo = null;
        }

        if ($this->_slave) {
            $this->_slave->close();
            $this->_slave = false;
        }

        $this->_schema = null;
        $this->_driverName = null;
        $this->_queryCacheInfo = [];
        $this->_quotedTableNames = null;
        $this->_quotedColumnNames = null;
    }

    /**
     * Creates the PDO instance.
     * This method is called by [[open]] to establish a DB connection.
     * The default implementation will create a PHP PDO instance.
     * You may override this method if the default PDO needs to be adapted for certain DBMS.
     * @return PDO the pdo instance
     */
    protected function createPdoInstance()
    {
        $pdoClass = $this->pdoClass;
        if ($pdoClass === null) {
            $driver = null;
            if ($this->_driverName !== null) {
                $driver = $this->_driverName;
            } elseif (($pos = strpos($this->dsn, ':')) !== false) {
                $driver = strtolower(substr($this->dsn, 0, $pos));
            }
            switch ($driver) {
                case 'mssql':
                    $pdoClass = 'yii\db\mssql\PDO';
                    break;
                case 'dblib':
                    $pdoClass = 'yii\db\mssql\DBLibPDO';
                    break;
                case 'sqlsrv':
                    $pdoClass = 'yii\db\mssql\SqlsrvPDO';
                    break;
                default:
                    $pdoClass = 'PDO';
            }
        }

        $dsn = $this->dsn;
        if (strncmp('sqlite:@', $dsn, 8) === 0) {
            $dsn = 'sqlite:' . Yii::getAlias(substr($dsn, 7));
        }

        return new $pdoClass($dsn, $this->username, $this->password);
    }

    /**
     * Creates a command for execution.
     * @param string|null $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     * @return Command the DB command
     */
    public function createCommand($sql = null, $params = [])
    {
        $driver = $this->getDriverName();
        $config = !is_array($this->commandMap[$driver]) ? ['class' => $this->commandMap[$driver]] : $this->commandMap[$driver];
        $config['db'] = $this;
        $config['sql'] = $sql;
        /** @var Command $command */
        $command = Yii::createObject($config);
        return $command->bindValues($params);
    }

    /**
     * 当前事务
     * @return Transaction|null
     */
    public function getTransaction()
    {
        $trans = Sync::map(self::SYNC_KEY_TRANS);
        return $trans && $trans->getIsActive() ? $trans : null;
    }

    /**
     * 开启一个事务
     * @param string|null $isolationLevel 事务隔离级别
     * @return Transaction
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function beginTransaction($isolationLevel = null)
    {
        $this->open();

        if (($transaction = $this->getTransaction()) === null) {
            $transaction = new Transaction(['db' => $this]);
        }
        $transaction->begin($isolationLevel);
        Sync::map(self::SYNC_KEY_TRANS, $transaction);

        return $transaction;
    }

    /**
     * 包在事务里 没问题commit 抛异常rollback
     * @param callable $callback
     * @param string|null $isolationLevel 事务隔离级别
     * @throws \Throwable if there is any exception during query. In this case the transaction will be rolled back.
     * @return mixed result of callback function
     */
    public function transaction(callable $callback, $isolationLevel = null)
    {
        $transaction = $this->beginTransaction($isolationLevel);
        $level = $transaction->level;

        try {
            $result = call_user_func($callback, $this);
            if ($transaction->isActive && $transaction->level === $level) {
                $transaction->commit();
            }
        } catch (\Exception $e) {
            $this->rollbackTransactionOnLevel($transaction, $level);
            throw $e;
        } catch (\Throwable $e) {
            $this->rollbackTransactionOnLevel($transaction, $level);
            throw $e;
        }

        return $result;
    }

    /**
     * Rolls back given [[Transaction]] object if it's still active and level match.
     * In some cases rollback can fail, so this method is fail safe. Exception thrown
     * from rollback will be caught and just logged with [[\Yii::error()]].
     * @param Transaction $transaction Transaction object given from [[beginTransaction()]].
     * @param int $level Transaction level just after [[beginTransaction()]] call.
     */
    private function rollbackTransactionOnLevel($transaction, $level)
    {
        if ($transaction->isActive && $transaction->level === $level) {
            // https://github.com/yiisoft/yii2/pull/13347
            try {
                $transaction->rollBack();
            } catch (\Exception $e) {
                \Yii::error($e, __METHOD__);
                // hide this exception to be able to continue throwing original exception outside
            }
        }
    }

    /**
     * Returns the schema information for the database opened by this connection.
     * @return Schema the schema information for the database opened by this connection.
     * @throws NotSupportedException if there is no support for the current driver type
     */
    public function getSchema()
    {
        if ($this->_schema !== null) {
            return $this->_schema;
        }

        $driver = $this->getDriverName();
        if (isset($this->schemaMap[$driver])) {
            $config = !is_array($this->schemaMap[$driver]) ? ['class' => $this->schemaMap[$driver]] : $this->schemaMap[$driver];
            $config['db'] = $this;

            $this->_schema = Yii::createObject($config);
            $this->restoreQueryBuilderConfiguration();

            return $this->_schema;
        }

        throw new NotSupportedException("Connection does not support reading schema information for '$driver' DBMS.");
    }

    /**
     * Returns the query builder for the current DB connection.
     * @return QueryBuilder the query builder for the current DB connection.
     */
    public function getQueryBuilder()
    {
        return $this->getSchema()->getQueryBuilder();
    }

    /**
     * Can be used to set [[QueryBuilder]] configuration via Connection configuration array.
     *
     * @param array $value the [[QueryBuilder]] properties to be configured.
     * @since 2.0.14
     */
    public function setQueryBuilder($value)
    {
        Yii::configure($this->getQueryBuilder(), $value);
        $this->_queryBuilderConfigurations[] = $value;
    }

    /**
     * Restores custom QueryBuilder configuration after the connection close/open cycle
     */
    private function restoreQueryBuilderConfiguration()
    {
        if ($this->_queryBuilderConfigurations === []) {
            return;
        }

        $queryBuilderConfigurations = $this->_queryBuilderConfigurations;
        $this->_queryBuilderConfigurations = [];
        foreach ($queryBuilderConfigurations as $queryBuilderConfiguration) {
            $this->setQueryBuilder($queryBuilderConfiguration);
        }
    }

    /**
     * Obtains the schema information for the named table.
     * @param string $name table name.
     * @param bool $refresh whether to reload the table schema even if it is found in the cache.
     * @return TableSchema|null table schema information. Null if the named table does not exist.
     */
    public function getTableSchema($name, $refresh = false)
    {
        return $this->getSchema()->getTableSchema($name, $refresh);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     * @see https://www.php.net/manual/en/pdo.lastinsertid.php
     */
    public function getLastInsertID($sequenceName = '')
    {
        return $this->getSchema()->getLastInsertID($sequenceName);
    }

    /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     * @param string $value string to be quoted
     * @return string the properly quoted string
     * @see https://www.php.net/manual/en/pdo.quote.php
     */
    public function quoteValue($value)
    {
        return $this->getSchema()->quoteValue($value);
    }

    /**
     * Quotes a table name for use in a query.
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains special characters including '(', '[[' and '{{',
     * then this method will do nothing.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteTableName($name)
    {
        if (isset($this->_quotedTableNames[$name])) {
            return $this->_quotedTableNames[$name];
        }
        return $this->_quotedTableNames[$name] = $this->getSchema()->quoteTableName($name);
    }

    /**
     * Quotes a column name for use in a query.
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains special characters including '(', '[[' and '{{',
     * then this method will do nothing.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteColumnName($name)
    {
        if (isset($this->_quotedColumnNames[$name])) {
            return $this->_quotedColumnNames[$name];
        }
        return $this->_quotedColumnNames[$name] = $this->getSchema()->quoteColumnName($name);
    }

    /**
     * Processes a SQL statement by quoting table and column names that are enclosed within double brackets.
     * Tokens enclosed within double curly brackets are treated as table names, while
     * tokens enclosed within double square brackets are column names. They will be quoted accordingly.
     * Also, the percentage character "%" at the beginning or ending of a table name will be replaced
     * with [[tablePrefix]].
     * @param string $sql the SQL to be quoted
     * @return string the quoted SQL
     */
    public function quoteSql($sql)
    {
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                }

                return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
            },
            $sql
        );
    }

    /**
     * 从dsn中获取dirver
     * @return string|null
     * @throws InvalidConfigException
     */
    public function getDriverName()
    {
        if ($this->_driverName === null) {
            if (($pos = strpos((string)$this->dsn, ':')) !== false) {
                $this->_driverName = strtolower(substr($this->dsn, 0, $pos));
            } else {
                $this->_driverName = strtolower($this->getSlavePdo()->getAttribute(PDO::ATTR_DRIVER_NAME));
            }
        }

        return $this->_driverName;
    }

    /**
     * version
     * @return string
     * @throws NotSupportedException
     */
    public function getServerVersion()
    {
        return $this->getSchema()->getServerVersion();
    }

    /**
     * 从库连接
     * @param bool $fallbackToMaster 失败走主库
     * @return PDO|null
     * @throws InvalidConfigException
     */
    public function getSlavePdo($fallbackToMaster = true)
    {
        $db = $this->getSlave(false);
        if ($db === null) {
            return $fallbackToMaster ? $this->getMasterPdo() : null;
        }

        return $db->getPdo();
    }

    /**
     * 主库pdo
     * @return PDO the PDO instance for the currently active master connection.
     */
    public function getMasterPdo()
    {
        return $this->getPdo();
    }

    /**
     * 获取从库pdo
     * @param bool $fallbackToMaster 失败走主库
     * @return PdoConnect|null
     * @throws InvalidConfigException
     */
    public function getSlave($fallbackToMaster = true)
    {
        if (!$this->enableSlaves) {
            return $fallbackToMaster ? $this : null;
        }

        if ($this->_slave === false) {
            $this->_slave = $this->openFromPool($this->slaves, $this->slaveConfig);
        }

        return $this->_slave === null && $fallbackToMaster ? $this : $this->_slave;
    }

    /**
     * 获取主库connect
     * @return PdoConnect
     * @throws InvalidConfigException
     */
    public function getMaster()
    {
        if ($this->_master === false) {
            $this->_master = $this->shuffleMasters
                ? $this->openFromPool($this->masters, $this->masterConfig)
                : $this->openFromPoolSequentially($this->masters, $this->masterConfig);
        }

        return $this->_master;
    }

    /**
     * 可能会影响其他协程
     * 自己的用的时候注意不要在$callback里切就可以了
     * @param callable $callback
     * @return mixed the return value of the callback
     * @throws \Throwable if there is any exception thrown from the callback
     */
    public function useMaster(callable $callback)
    {
        if ($this->enableSlaves) {
            $this->enableSlaves = false;
            try {
                $result = call_user_func($callback, $this);
            } catch (\Exception $e) {
                $this->enableSlaves = true;
                throw $e;
            } catch (\Throwable $e) {
                $this->enableSlaves = true;
                throw $e;
            }
            // TODO: use "finally" keyword when miminum required PHP version is >= 5.5
            $this->enableSlaves = true;
        } else {
            $result = call_user_func($callback, $this);
        }

        return $result;
    }

    /**
     * 随机从配置池子中找一个 创建链接
     * @param array $pool 服务配置池
     * @param array $sharedConfig 公共配置
     * @return PdoConnect|null DB connection
     * @throws InvalidConfigException 如果配置不可用
     */
    protected function openFromPool(array $pool, array $sharedConfig)
    {
        shuffle($pool);
        return $this->openFromPoolSequentially($pool, $sharedConfig);
    }

    /**
     * 从所有配置中依次尝试连接 成功即返回
     * @param array $pool 服务配置池
     * @param array $sharedConfig 公共配置
     * @return PdoConnect|null
     * @throws InvalidConfigException if a configuration does not specify "dsn"
     */
    protected function openFromPoolSequentially(array $pool, array $sharedConfig)
    {
        if (empty($pool)) {
            return null;
        }

        if (!isset($sharedConfig['class'])) {
            $sharedConfig['class'] = get_class($this);
        }

        //缓存服务
        $cache = is_string($this->serverStatusCache) ? Yii::$app->get($this->serverStatusCache, false) : $this->serverStatusCache;

        foreach ($pool as $i => $config) {
            //合并公共配置
            $pool[$i] = $config = array_merge($sharedConfig, $config);
            if (empty($config['dsn'])) {
                throw new InvalidConfigException('The "dsn" option must be specified.');
            }

            //缓存中记录了失败的实例 短时间内不在尝试
            $key = [__METHOD__, $config['dsn']];
            if ($cache instanceof CacheInterface && $cache->get($key)) {
                continue;
            }

            /* @var $db Connection */
            $db = Yii::createObject($config);

            //尝试链接
            try {
                $db->open();
                return $db;
            } catch (\Exception $e) {
                Yii::warning("Connection ({$config['dsn']}) failed: " . $e->getMessage(), __METHOD__);

                //标记服务不可用
                if ($cache instanceof CacheInterface) {
                    $cache->set($key, 1, $this->serverRetryInterval);
                }
                unset($pool[$i]);
            }
        }

        /**
         * 所有配置都不可用 尝试下缓存中被标记的配置
         */
        if ($cache instanceof CacheInterface) {
            foreach ($pool as $config) {

                /* @var $db Connection */
                $db = Yii::createObject($config);
                try {
                    $db->open();
                } catch (\Exception $e) {
                    Yii::warning("Connection ({$config['dsn']}) failed: " . $e->getMessage(), __METHOD__);
                    continue;
                }

                // mark this server as available again after successful connection
                $cache->delete([__METHOD__, $config['dsn']]);

                return $db;
            }
        }
        return null;
    }

    /**
     * Close the connection before serializing.
     * @return array
     */
    public function __sleep()
    {
        $fields = (array) $this;

        unset($fields['pdo']);
        unset($fields["\000" . __CLASS__ . "\000" . '_master']);
        unset($fields["\000" . __CLASS__ . "\000" . '_slave']);
        unset($fields["\000" . __CLASS__ . "\000" . '_transaction']);
        unset($fields["\000" . __CLASS__ . "\000" . '_schema']);

        return array_keys($fields);
    }

    public function getPdo()
    {
        //先从内存里取
        if ($pdo = Sync::map($this->conType)) {
            return $pdo;
        }

        if (! $pool = $this->pdoPool()) {
            return null;
        }

        Sync::map($this->conType, $pdo = $pool->get());
        return $pdo;
    }

    /**
     * 获取连接池
     * @return PDOPool
     */
    protected function pdoPool()
    {
        return $this->conType == self::PDO_TYPE_MASTER ? static::$masterPool : static::$slavePool;
    }

    /**
     * Reset the connection after cloning.
     */
    public function __clone()
    {
        parent::__clone();

        $this->_master = false;
        $this->_slave = false;
        $this->_schema = null;
        if (strncmp($this->dsn, 'sqlite::memory:', 15) !== 0) {
            // reset PDO connection, unless its sqlite in-memory, which can only have one connection
            $this->pdo = null;
        }
    }
}
