<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';
require_once 'models.php';

/**
 * Class CommentApp
 *
 * @property CommentManager $manager
 */
class CommentApp extends AbricosApplication {

    protected function GetClasses(){
        return array(
            'Statistic' => 'CommentStatistic',
            'StatisticList' => 'CommentStatisticList',
            'Comment' => 'Comment',
            'CommentList' => 'CommentList'
        );
    }

    protected function GetStructures(){
        return 'Statistic,Comment';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'commentList':
                return $this->CommentListToJSON($d->module, $d->type, $d->ownerid);
        }
        return null;
    }

    public function IsRaiting(){
        $modURating = Abricos::GetModule("urating");
        return !empty($modURating);
    }

    private $_cache = array();

    private function GetOwnerApp($moduleName){
        if (!isset($this->_cache['app'])){
            $this->_cache['app'] = array();
        }
        if (isset($this->_cache['app'][$moduleName])){
            return $this->_cache['app'][$moduleName];
        }
        $module = Abricos::GetModule($moduleName);
        if (empty($module)){
            return null;
        }
        $manager = $module->GetManager();
        if (empty($manager)){
            return null;
        }
        if (!method_exists($manager, 'GetApp')){
            return null;
        }
        return $this->_cache['app'][$moduleName] = $manager->GetApp();
    }


    public function CommentListToJSON($module, $type, $ownerid){
        $ret = $this->CommentList($module, $type, $ownerid);
        return $this->ResultToJSON('commentList', $ret);
    }

    /**
     * @param $module
     * @param $type
     * @param $ownerid
     * @return CommentList|int
     */
    public function CommentList($module, $type, $ownerid){
        $ownerApp = $this->GetOwnerApp($module);
        if (empty($ownerApp)){
            return 500;
        }
        if (!method_exists($ownerApp, 'Comment_IsList')){
            return 500;
        }
        if (!$ownerApp->Comment_IsList($type, $ownerid)){
            return 403;
        }

        $models = $this->models;

        /** @var CommentList $list */
        $list = $models->InstanceClass('CommentList');

        $rows = CommentQuery::CommentList($this->db, $module, $type, $ownerid);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($models->InstanceClass('Comment', $d));
        }

        return $list;
    }

    /**
     * @param string $module Owner Module
     * @param string $type Owner Ids Type (Field Name)
     * @param int|array[int] $ownerid Owner Id
     *
     */
    public function Statistic($module, $type, $ownerid){
        $rows = CommentQuery::StatisticList($this->db, $module, $type, [$ownerid]);
        $d = $this->db->fetch_array($rows);
        if (empty($d)){
            return null;
        }

        return $this->models->InstanceClass('Statistic', $d);
    }

    /**
     * @param string $module Owner Module
     * @param string $type Owner Ids Type (Field Name)
     * @param int|array[int] $ownerids Owner Ids
     * @return CommentStatisticList
     */
    public function StatisticList($module, $type, $ownerids){
        $models = $this->models;

        /** @var CommentStatisticList $list */
        $list = $models->InstanceClass('StatisticList');

        $rows = CommentQuery::StatisticList($this->db, $module, $type, $ownerids);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($models->InstanceClass('Statistic', $d));
        }
        return $list;
    }

}

?>