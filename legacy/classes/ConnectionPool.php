<?php
/**
 * CloudClint 数据库连接池管理类
 * 提供高效的数据库连接管理和查询优化
 */

class ConnectionPool {
    private static $instance = null;
    private $connections = [];
    private $config;
    private $maxConnections = 10;
    private $currentConnections = 0;
    private $queryCache = [];
    private $cacheTimeout = 300; // 5分钟缓存
    private $slowQueryThreshold = 1000; // 1秒
    private $queryLog = [];
    private $maxLogEntries = 100;
    
    private function __construct() {
        $this->config = [
            'host' => 'mysql845',
            'dbname' => 'ztao',
            'username' => 'ztao',
            'password' => 'CntEfjATWD5kyNAf',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'",
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_TIMEOUT => 30
            ]
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 获取数据库连接
     */
    public function getConnection() {
        // 尝试重用现有连接
        foreach ($this->connections as $key => $connection) {
            if ($this->isConnectionAlive($connection['pdo'])) {
                $connection['last_used'] = time();
                $this->connections[$key] = $connection;
                return $connection['pdo'];
            } else {
                // 移除死连接
                unset($this->connections[$key]);
                $this->currentConnections--;
            }
        }
        
        // 创建新连接
        if ($this->currentConnections < $this->maxConnections) {
            return $this->createConnection();
        }
        
        // 连接池已满，返回最近最少使用的连接
        return $this->getLeastRecentlyUsedConnection();
    }
    
    /**
     * 创建新的数据库连接
     */
    private function createConnection() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
            
            $connectionId = uniqid('conn_');
            $this->connections[$connectionId] = [
                'pdo' => $pdo,
                'created' => time(),
                'last_used' => time(),
                'query_count' => 0
            ];
            
            $this->currentConnections++;
            return $pdo;
            
        } catch (PDOException $e) {
            error_log("数据库连接失败: " . $e->getMessage());
            throw new Exception("数据库连接失败");
        }
    }
    
    /**
     * 检查连接是否存活
     */
    private function isConnectionAlive($pdo) {
        try {
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * 获取最近最少使用的连接
     */
    private function getLeastRecentlyUsedConnection() {
        $lruConnection = null;
        $oldestTime = time();
        
        foreach ($this->connections as $connection) {
            if ($connection['last_used'] < $oldestTime) {
                $oldestTime = $connection['last_used'];
                $lruConnection = $connection['pdo'];
            }
        }
        
        return $lruConnection ?: $this->createConnection();
    }
    
    /**
     * 执行查询（带缓存）
     */
    public function query($sql, $params = [], $useCache = true) {
        $startTime = microtime(true);
        $cacheKey = $this->getCacheKey($sql, $params);
        
        // 检查缓存
        if ($useCache && isset($this->queryCache[$cacheKey])) {
            $cached = $this->queryCache[$cacheKey];
            if (time() - $cached['timestamp'] < $this->cacheTimeout) {
                $this->logQuery($sql, $params, microtime(true) - $startTime, true);
                return $cached['data'];
            } else {
                unset($this->queryCache[$cacheKey]);
            }
        }
        
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll();
            $executionTime = microtime(true) - $startTime;
            
            // 缓存SELECT查询结果
            if ($useCache && stripos(trim($sql), 'SELECT') === 0) {
                $this->queryCache[$cacheKey] = [
                    'data' => $result,
                    'timestamp' => time()
                ];
                
                // 限制缓存大小
                if (count($this->queryCache) > 100) {
                    $this->cleanupCache();
                }
            }
            
            $this->logQuery($sql, $params, $executionTime, false);
            return $result;
            
        } catch (PDOException $e) {
            $this->logQuery($sql, $params, microtime(true) - $startTime, false, $e->getMessage());
            error_log("查询执行失败: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("查询执行失败");
        }
    }
    
    /**
     * 执行非查询语句（INSERT, UPDATE, DELETE）
     */
    public function execute($sql, $params = []) {
        $startTime = microtime(true);
        
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            $executionTime = microtime(true) - $startTime;
            $this->logQuery($sql, $params, $executionTime, false);
            
            // 清除相关缓存
            $this->invalidateCache($sql);
            
            return $result;
            
        } catch (PDOException $e) {
            $this->logQuery($sql, $params, microtime(true) - $startTime, false, $e->getMessage());
            error_log("语句执行失败: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("语句执行失败");
        }
    }
    
    /**
     * 获取单行数据
     */
    public function fetchRow($sql, $params = [], $useCache = true) {
        $result = $this->query($sql, $params, $useCache);
        return $result ? $result[0] : null;
    }
    
    /**
     * 获取单个值
     */
    public function fetchValue($sql, $params = [], $useCache = true) {
        $row = $this->fetchRow($sql, $params, $useCache);
        return $row ? array_values($row)[0] : null;
    }
    
    /**
     * 获取缓存键
     */
    private function getCacheKey($sql, $params) {
        return md5($sql . serialize($params));
    }
    
    /**
     * 清理缓存
     */
    private function cleanupCache() {
        // 移除最旧的缓存项
        $oldestKey = null;
        $oldestTime = time();
        
        foreach ($this->queryCache as $key => $cache) {
            if ($cache['timestamp'] < $oldestTime) {
                $oldestTime = $cache['timestamp'];
                $oldestKey = $key;
            }
        }
        
        if ($oldestKey) {
            unset($this->queryCache[$oldestKey]);
        }
    }
    
    /**
     * 使缓存失效
     */
    private function invalidateCache($sql) {
        $sql = strtoupper(trim($sql));
        
        // 根据SQL类型清除相关缓存
        if (strpos($sql, 'INSERT') === 0 || strpos($sql, 'UPDATE') === 0 || strpos($sql, 'DELETE') === 0) {
            // 提取表名
            preg_match('/(?:INSERT INTO|UPDATE|DELETE FROM)\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $matches);
            if (isset($matches[1])) {
                $tableName = $matches[1];
                
                // 清除涉及该表的所有缓存
                foreach ($this->queryCache as $key => $cache) {
                    if (strpos($key, $tableName) !== false) {
                        unset($this->queryCache[$key]);
                    }
                }
            }
        }
    }
    
    /**
     * 记录查询日志
     */
    private function logQuery($sql, $params, $executionTime, $fromCache, $error = null) {
        $logEntry = [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => round($executionTime * 1000, 2), // 毫秒
            'from_cache' => $fromCache,
            'timestamp' => time(),
            'error' => $error
        ];
        
        $this->queryLog[] = $logEntry;
        
        // 限制日志大小
        if (count($this->queryLog) > $this->maxLogEntries) {
            array_shift($this->queryLog);
        }
        
        // 记录慢查询
        if (!$fromCache && $executionTime * 1000 > $this->slowQueryThreshold) {
            error_log("慢查询检测: {$logEntry['execution_time']}ms - SQL: $sql");
        }
    }
    
    /**
     * 获取查询统计信息
     */
    public function getQueryStats() {
        $totalQueries = count($this->queryLog);
        $cacheHits = array_filter($this->queryLog, function($log) {
            return $log['from_cache'];
        });
        $slowQueries = array_filter($this->queryLog, function($log) {
            return $log['execution_time'] > $this->slowQueryThreshold;
        });
        
        $avgExecutionTime = 0;
        if ($totalQueries > 0) {
            $totalTime = array_sum(array_column($this->queryLog, 'execution_time'));
            $avgExecutionTime = round($totalTime / $totalQueries, 2);
        }
        
        return [
            'total_queries' => $totalQueries,
            'cache_hits' => count($cacheHits),
            'cache_hit_rate' => $totalQueries > 0 ? round(count($cacheHits) / $totalQueries * 100, 2) : 0,
            'slow_queries' => count($slowQueries),
            'avg_execution_time' => $avgExecutionTime,
            'active_connections' => $this->currentConnections,
            'cached_queries' => count($this->queryCache)
        ];
    }
    
    /**
     * 获取慢查询日志
     */
    public function getSlowQueries() {
        return array_filter($this->queryLog, function($log) {
            return $log['execution_time'] > $this->slowQueryThreshold;
        });
    }
    
    /**
     * 清除所有缓存
     */
    public function clearCache() {
        $this->queryCache = [];
    }
    
    /**
     * 关闭所有连接
     */
    public function closeAllConnections() {
        $this->connections = [];
        $this->currentConnections = 0;
    }
    
    /**
     * 开始事务
     */
    public function beginTransaction() {
        $pdo = $this->getConnection();
        return $pdo->beginTransaction();
    }
    
    /**
     * 提交事务
     */
    public function commit() {
        $pdo = $this->getConnection();
        return $pdo->commit();
    }
    
    /**
     * 回滚事务
     */
    public function rollback() {
        $pdo = $this->getConnection();
        return $pdo->rollback();
    }
    
    /**
     * 获取最后插入的ID
     */
    public function lastInsertId() {
        $pdo = $this->getConnection();
        return $pdo->lastInsertId();
    }
    
    /**
     * 释放数据库连接
     */
    public function releaseConnection($pdo) {
        // 在连接池模式下，连接会被重用，不需要实际关闭
        // 只需要更新连接的最后使用时间
        foreach ($this->connections as $key => $connection) {
            if ($connection['pdo'] === $pdo) {
                $this->connections[$key]['last_used'] = time();
                $this->connections[$key]['query_count']++;
                break;
            }
        }
    }
    
    /**
     * 析构函数
     */
    public function __destruct() {
        $this->closeAllConnections();
    }
}

/**
 * 数据库查询构建器
 */
class QueryBuilder {
    private $pool;
    private $table;
    private $select = ['*'];
    private $where = [];
    private $joins = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $limit = null;
    private $offset = null;
    private $params = [];
    
    public function __construct($table) {
        $this->pool = ConnectionPool::getInstance();
        $this->table = $table;
    }
    
    public function select($columns) {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    public function where($column, $operator = '=', $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $placeholder = ':where_' . count($this->params);
        $this->where[] = "$column $operator $placeholder";
        $this->params[$placeholder] = $value;
        return $this;
    }
    
    public function whereIn($column, $values) {
        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholder = ':wherein_' . count($this->params);
            $placeholders[] = $placeholder;
            $this->params[$placeholder] = $value;
        }
        
        $this->where[] = "$column IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }
    
    public function whereLike($column, $value) {
        $placeholder = ':like_' . count($this->params);
        $this->where[] = "$column LIKE $placeholder";
        $this->params[$placeholder] = $value;
        return $this;
    }
    
    public function join($table, $first, $operator = '=', $second = null) {
        if (func_num_args() === 3) {
            $second = $operator;
            $operator = '=';
        }
        
        $this->joins[] = "INNER JOIN $table ON $first $operator $second";
        return $this;
    }
    
    public function leftJoin($table, $first, $operator = '=', $second = null) {
        if (func_num_args() === 3) {
            $second = $operator;
            $operator = '=';
        }
        
        $this->joins[] = "LEFT JOIN $table ON $first $operator $second";
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = "$column $direction";
        return $this;
    }
    
    public function groupBy($column) {
        $this->groupBy[] = $column;
        return $this;
    }
    
    public function limit($limit, $offset = null) {
        $this->limit = $limit;
        if ($offset !== null) {
            $this->offset = $offset;
        }
        return $this;
    }
    
    public function get() {
        $sql = $this->buildSelectSql();
        return $this->pool->query($sql, $this->params);
    }
    
    public function first() {
        $this->limit(1);
        $result = $this->get();
        return $result ? $result[0] : null;
    }
    
    public function count() {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];
        $result = $this->first();
        $this->select = $originalSelect;
        return $result ? (int)$result['count'] : 0;
    }
    
    private function buildSelectSql() {
        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= ' FROM ' . $this->table;
        
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }
        
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        
        return $sql;
    }
}

// 便捷函数
function db($table = null) {
    if ($table) {
        return new QueryBuilder($table);
    }
    return ConnectionPool::getInstance();
}
?>