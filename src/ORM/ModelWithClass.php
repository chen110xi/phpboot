<?php
namespace PhpBoot\ORM;

use Doctrine\Common\Cache\Cache;
use PhpBoot\DB\DB;

class ModelWithClass
{
    /**
     * Model constructor.
     * @param DB $db
     * @param string $entityName
     * @param Cache $cache
     */
    public function __construct(DB $db, $entityName, Cache $cache)
    {
        $this->db = $db;
        $builder = $db->getApp()->get(ModelContainerBuilder::class);
        $this->entity = $builder->build($entityName);
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function find($id)
    {
        $row = $this->db->select($this->getColumns())
            ->from($this->entity->getTable())
            ->where("`{$this->entity->getPK()}` = ?", $id)
            ->getFirst();
        if($row){
            return $this->entity->make($row, false);
        }else{
            return null;
        }
    }

    /**
     * @return int rows deleted
     */
    public function delete($id)
    {
        return $this->db->deleteFrom($this->entity->getTable())
            ->where([$this->entity->getPK()=>$id])
            ->limit(1)
            ->exec()->rows;
    }

    /**
     * where 语法见 @see WhereRule
     * @param array|string $expr
     * @param mixed|null $_
     * @return \PhpBoot\DB\rules\basic\OrderByRule
     */
    public function deleteWhere($conditions, $_=null)
    {
        $query = $this->db->deleteFrom($this->entity->getTable());
        return call_user_func_array([$query, 'where'], func_get_args());
    }

    /**
     * @return false|int
     */
    public function count()
    {
        return $this->db->select($this->getColumns())
            ->from($this->entity->getTable())
            ->count();
    }

    /**
     * where 语法见 @see WhereRule
     * @param array|string|null $conditions
     * @param string $_
     * @return \PhpBoot\DB\rules\select\GroupByRule
     */
    public function findWhere($conditions=null, $_=null)
    {
        $query =  $this->db->select($this->getColumns())
            ->from($this->entity->getTable());
        $query->context->resultHandler = function ($result){
            foreach ($result as &$i){
                $i = $this->entity->make($i, false);
            }
            return $result;
        };
        return call_user_func_array([$query, 'where'], func_get_args());
    }

    /**
     * where 语法见 @see WhereRule
     * @param array|string $expr
     * @param mixed|null $_
     * @return \PhpBoot\DB\rules\basic\WhereRule
     */
    public function update($expr, $_=null)
    {
        $query =  $this->db->update($this->entity->getTable());
        return call_user_func_array([$query, 'set'], func_get_args());
    }

    protected function getColumns()
    {
        $columns = [];
        foreach ($this->entity->getProperties() as $p){
            $columns[] = $p->name;
        }
        return $columns;
    }
    /**
     * @var ModelContainer
     */
    protected $entity;
    /**
     * @var DB
     */
    protected $db;
}